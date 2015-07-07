;(function (window, $, undefined) {
  'use strict';

  var domParts,
    queryParams = {};

  $(function () {  //Entry Point
    domParts = {
      enteridmodal: $("#enterid"),
      successsiteidmodal: $("#successsiteid"),
      successregistermodal: $("#successregister"),
      failmodal: $("#fail"),
      nameInput: $("#nameInput"),
      websiteURLInput: $("#websiteURLInput"),
      emailInput: $("#emailInput"),
      passwordInput: $("#passwordInput"),
      verifyPasswordInput: $("#verifyPasswordInput"),
      submitRegister: $("#submitRegister"),
      siteIdButton: $("#iHaveId"),
      siteIdInput: $("#siteIdInput"),
      submitSiteId: $("#submitSiteId")
    };

	queryParams['tagId'] = $('#register').parent().attr('data-tagId');
	queryParams['installName'] = $('#register').parent().attr('data-installName');
		
    domParts.submitRegister.on('click', onSendRegisterForm);

    $('#register').find('.fields > input').on('keydown', function(ev){
      var keyCcode = ev.keyCode || ev.which;
      if (keyCcode == 13) { //Enter keycode
        onSendRegisterForm(ev);
      }
    });

    domParts.passwordInput.on('blur', function(ev){
      var password = domParts.passwordInput.val();
      if (!isValidPassword(password)) {
        $('.inputMsg.password').text('Password should be at least 7 characters');
      }
      else {
        $('.inputMsg.password').text('');
      }
    });

    // Enter-it-here click handle
    domParts.siteIdButton.on('click', function(e) {
      toggleModal(domParts.enteridmodal, true);
      e.stopPropagation();
      e.preventDefault();
      return false;
    });

    /* EnterId screen
     * --------------
     * */
    domParts.submitSiteId.on('click', onSendSiteIdForm);

    domParts.enteridmodal.find('.fields > input').on('keydown', function(ev){
      var keyCcode = ev.keyCode || ev.which;
      if (keyCcode == 13) { //Enter keycode
        onSendSiteIdForm(ev);
      }
    });

    /* General modal behaviour - close for any tap/click outside elements button or input
     * -----------------------
     * */
    $(".modal").on('click', onModalClick);

    //Auto focus first input
    domParts.nameInput.focus();
  });

  function onSendRegisterForm(ev){
    $('.inputMsg').text('');  //Clear previous messages

    var inputData = getInputDataAndValidate();

    if(!inputData) {
      return;
    }

    inputData.registrationSource = 'wordpress';
    inputData.installerName = getDataFromUrlParams('installName');
    inputData.tagId = getDataFromUrlParams('tagId');

    submitRegisterForm(inputData, 'register');

    ev.preventDefault();
    return false;
  }

  function getInputDataAndValidate() {
    //Validate name
    var name = domParts.nameInput.val();
    if (!name) {
      $('.inputMsg.name').text('Please insert your name');
      return false;
    }

    //Validate URL
    var url = domParts.websiteURLInput.val();
    if (!url.match(/^[a-zA-Z]+:\/\//)) {  //Add protocol
      url = 'http://' + url;
    }
    if (!isValidURL(url)) {
      $('.inputMsg.site').text('Please use a valid website address');
      return false;
    }

    //Validate email
    var email = domParts.emailInput.val();
    if (!isValidEmail(email)) {
      $('.inputMsg.email').text('Please insert a valid email address');
      return false;
    }

    //Validate password
    var password = domParts.passwordInput.val();
    if (!isValidPassword(password)) {
      $('.inputMsg.password').text('Password should be at least 7 characters');
      return false;
    }

    //Validate verify password
    var verifyPassword = domParts.verifyPasswordInput.val();
    if (!isValidVerifyPassword(verifyPassword)) {
      $('.inputMsg.verifyPassword').text('Passwords don\'t match');
      return false;
    }

    return {
      name: name,
      domain: url,
      email: email,
      password: password,
      verifyPassword: verifyPassword
    };
  }

  function getDataFromUrlParams(paramName) {
    return queryParams[paramName] || '';
  }

  function onSendSiteIdForm(ev){
    $('.inputMsg').text('');  //Clear previous messages

    //Validate name
    var newTagId = domParts.siteIdInput.val();
    if (!newTagId) {
      $('.inputMsg.siteid').text('Please enter site id');
      return 0;
    }

    var oldTagId = getDataFromUrlParams('tagId');

    submitRegisterForm({ newTagId: newTagId, oldTagId: oldTagId }, 'switchTag');

    ev.preventDefault();
    return false;
  }

  function submitRegisterForm(data, type) {
    onSubmitSuccess(type);

    $.ajax({
      url: getPostUrl(type),
      type: "POST",
      data : JSON.stringify(data),
      dataType: "json",
      contentType: "application/json",
      crossDomain: true,
      cache: false,
      success: function (response, status) {
        if(response && response.success == true && status === 'success') {
          postMessageToWP({ type: type, tagId: (type === 'register') ? data.tagId : data.newTagId });
        }
        else {
          onSubmitError({ type: type, formData: data, message: response.message, status: status });
        }
      },
      error: function (xhr, status, error) {
        onSubmitError({ type: type, requestData: data, statusCode: status, responseText: error });
      }
    });
  }

  function onSubmitError(errorData) {
    //Set the fail message
    var failText = 'Please try again :-/';
    if(errorData.message === 'User already exists with this email') {
      failText = errorData.message;
    }
    domParts.failmodal.find('.fail-text').text(failText);

    $(".modal").on('click', onModalClick);

    toggleModal(domParts.successsiteidmodal, false);
    toggleModal(domParts.successregistermodal, false);
    toggleModal(domParts.enteridmodal, false);
    toggleModal(domParts.failmodal, true);
  }

  function onSubmitSuccess(type) {
    $(".modal").off('click', onModalClick);
    toggleModal(domParts.enteridmodal, false);
    toggleModal(domParts.failmodal, false);

    if (type == 'register') {
      toggleModal(domParts.successregistermodal, true);
    } else {
      toggleModal(domParts.successsiteidmodal, true);
    }
  }

  function postMessageToWP (data) {
	$.ajax({
		url : errniowp.ajax_url,
		type : 'post',
		data : {
			'action': 'errnio_register',
			'tag_id': data.tagId,
			'type': data.type
		},
		success : function( response ) {
		}
	});
  }

  function onModalClick(e) {
    var tag = e.target.tagName.toLowerCase();

    if (tag != 'input' && tag != 'button') {
      toggleModal($(this), false);
    }
  }

  function toggleModal($el, shouldShow) {
    if (shouldShow) {
      $el.addClass('show');
    } else {
      $el.removeClass('show');
    }
  }

  function isValidEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
  }

  function isValidPassword(password) {
    return (password.length >= 7)
  }

  function isValidVerifyPassword(verifyPassword) {
    var password = domParts.passwordInput.val();
    return (verifyPassword === password);
  }

  function isValidURL(url) {
    var expression = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
    var regex = new RegExp(expression);
    return !!url.match(regex);
  }

  function getPostUrl(type) {
    return 'https://customer.errnio.com/' + type;
  }

}(window, window.jQuery));
