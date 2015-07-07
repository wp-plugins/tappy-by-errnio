<?php
/*
Plugin Name: Tappy by errnio
Plugin URI: http://errnio.com
Description: Tappy offers your mobile site visitors useful site and web information on any text selection they perform, enhancing experience, content circulation and overall usefulness of your mobile site.
Version: 1.0
Author: Errnio
Author URI: http://errnio.com
*/

/***** Constants ******/

define('ERRNIO_INSTALLER_NAME', 'wordpress_tappy_by_errnio');

define('ERRNIO_OPTION_NAME_TAGID', 'errnio_id');
define('ERRNIO_OPTION_NAME_TAGTYPE', 'errnio_id_type');

define('ERRNIO_EVENT_NAME_ACTIVATE', 'wordpress_activated');
define('ERRNIO_EVENT_NAME_DEACTIVATE', 'wordpress_deactivated');
define('ERRNIO_EVENT_NAME_UNINSTALL', 'wordpress_uninstalled');

define('ERRNIO_TAGTYPE_TEMP', 'temporary');
define('ERRNIO_TAGTYPE_PERM', 'permanent');

/***** Utils ******/

function errnio_do_curl_request($url, $data) {
	$data = json_encode( $data );
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response);
}

function errnio_send_event($eventType) {
	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);
	if ($tagId) {
		$urlpre = 'http://customer.errnio.com';
	 	$createTagUrl = $urlpre.'/sendEvent';

	 	$params = array('tagId' => $tagId, 'eventName' => $eventType);
	 	$response = errnio_do_curl_request($createTagUrl, $params);
	}
	// No tagId - no point sending an event
}

function errnio_mobile_gestures_create_tagid() {
	$urlpre = 'http://customer.errnio.com';
 	$createTagUrl = $urlpre.'/createTag';
 	$params = array('installerName' => ERRNIO_INSTALLER_NAME);
 	$response = errnio_do_curl_request($createTagUrl, $params);

	if ($response && $response->success) {
		$tagId = $response->tagId;
		add_option(ERRNIO_OPTION_NAME_TAGID, $tagId);
	 	add_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_TEMP);
		return $tagId;
	}
	
	return NULL;
}

function errnio_check_need_register() {
	$tagtype = get_option(ERRNIO_OPTION_NAME_TAGTYPE);
	$needregister = true;
	
	if ($tagtype == ERRNIO_TAGTYPE_PERM) {
		$needregister = false;
	}
	
	return $needregister;
}

/***** Activation / Deactivation / Uninstall hooks ******/

function errnio_mobile_gestures_activate() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;
	
	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

	if ( $tagId === FALSE || empty($tagId) ) {
		// First time activation
		$tagId = errnio_mobile_gestures_create_tagid();
	} else {
		// Previously activated - meaning tagType + tagId should exists
	}
	
	// Send event - activated
	errnio_send_event(ERRNIO_EVENT_NAME_ACTIVATE);
}

function errnio_mobile_gestures_deactivate() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;
	
	// Send event - deactivated
	errnio_send_event(ERRNIO_EVENT_NAME_DEACTIVATE);
}

function errnio_mobile_gestures_uninstall() {
	if ( ! current_user_can( 'activate_plugins' ) )
	        return;
	
	// Send event - uninstall
	errnio_send_event(ERRNIO_EVENT_NAME_UNINSTALL);	
	
	delete_option(ERRNIO_OPTION_NAME_TAGID);
	delete_option(ERRNIO_OPTION_NAME_TAGTYPE);
}

register_activation_hook( __FILE__, 'errnio_mobile_gestures_activate' );
register_deactivation_hook( __FILE__, 'errnio_mobile_gestures_deactivate' );
register_uninstall_hook( __FILE__, 'errnio_mobile_gestures_uninstall' );

/***** Client side script load ******/

function errnio_mobile_gestures_load_client_script() {
	$list = 'enqueued';
	$handle = 'errnio_script';

	// Script already running on this page
	if (wp_script_is($handle, $list)) {
		return;
	}

	$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

	if (!$tagId || empty($tagId)) {
		$tagId = errnio_mobile_gestures_create_tagid();
	}

	if ($tagId) {
		$script_url = "//service.errnio.com/loader?tagid=".$tagId;
		wp_register_script($handle, $script_url, false, '1.0', true);
		wp_enqueue_script($handle );
	}
}

function errnio_mobile_gestures_load_client_script_add_async_attr( $url ) {
	if(FALSE === strpos( $url, 'service2.errnio.com')){
		return $url;
	}

	return "$url' async='async";
}

add_filter('clean_url', 'errnio_mobile_gestures_load_client_script_add_async_attr', 11, 1);
add_action('wp_enqueue_scripts', 'errnio_mobile_gestures_load_client_script', 99999 );

/***** Admin ******/

function errnio_mobile_gestures_add_settings_menu_option() {
    add_menu_page (
        'Errnio Options',
        'Errnio Settings',
        'manage_options',
        'errnio-options',
        'errnio_mobile_gestures_admin_page',
		'dashicons-smartphone'
    );
}

function errnio_mobile_gestures_add_settings_link_on_plugin($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
		$adminpage_url = admin_url( 'admin.php?page=errnio-options' );
        $settings_link = '<a href="'.$adminpage_url.'">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

function errnio_mobile_gestures_admin_notice() {
	$needregister = errnio_check_need_register();
	$settingsurl = admin_url( 'admin.php?page=errnio-options' );
	
	if($needregister){
		echo( '<div class="error" style="font-weight:bold;font-size=22px;color:red;"> <p>Please register your site in the errnio settings section <a href="'.$settingsurl.'">here</a>.</p> </div>');
	}
}

function errnio_mobile_gestures_admin_page() {
	$stylehandle = 'errnio-style';
	$jshandle = 'errnio-js';
	wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=Exo+2:700,400,200,700italic,300italic,300');
	wp_enqueue_style('googleFonts');
	wp_register_style($stylehandle, plugins_url('assets/css/errnio.css', __FILE__));
	wp_enqueue_style($stylehandle);
	wp_register_script($jshandle, plugins_url('assets/js/errnio.js', __FILE__), array('jquery'));
	wp_enqueue_script($jshandle);
	wp_localize_script($jshandle, 'errniowp', array('ajax_url' => admin_url( 'admin-ajax.php' )));
    ?>
    <div class="wrap">
		<?php
		$needregister = errnio_check_need_register();
		$tagId = get_option(ERRNIO_OPTION_NAME_TAGID);

		echo '<h2>Errnio Options</h2>';

		if (!$needregister) {
			echo '<div class="bold"><p>Your new errnio plugin is up and running.<br/>For configuration and reports please visit your dashboard at <a href="http://brutus.errnio.com/">brutus.errnio.com</a></p><br/><img src="'.plugins_url('assets/img/logo-366x64.png', __FILE__).'"/></div>';
		} else {
			if ($tagId) {
				echo '<div class="errnio" height="100%" width="100%" data-tagId="'.$tagId.'" data-installName="'.ERRNIO_INSTALLER_NAME.'">';
				include 'assets/includes/errnio-admin.php';
				echo '</div>';
			} else {
				echo '<p>There was an error :( Contact <a href="mailto:support@errnio.com">support@errnio.com</a> for help.</p>';
			}
		};

		?>
    </div>
    <?php
}

function errnio_register_callback() {
	$type = $_POST['type'];
	$tagId = $_POST['tag_id'];

	if ($type == 'switchTag') {
		update_option(ERRNIO_OPTION_NAME_TAGID, $tagId);
	}

	update_option(ERRNIO_OPTION_NAME_TAGTYPE, ERRNIO_TAGTYPE_PERM);

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('admin_menu', 'errnio_mobile_gestures_add_settings_menu_option');
add_filter('plugin_action_links', 'errnio_mobile_gestures_add_settings_link_on_plugin', 10, 2);
add_action('admin_notices', 'errnio_mobile_gestures_admin_notice');
add_action('wp_ajax_errnio_register', 'errnio_register_callback');