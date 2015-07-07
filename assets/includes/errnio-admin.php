  <div id="register" class="section register">
    <div class="container">
      <h1 class="title">Become a publisher</h1>

      <div class="fields">
        <input id="nameInput" type="text" placeholder="Enter your name" tabindex="1">
        <span class="inputMsg name"></span>
        <input id="websiteURLInput" type="url" placeholder="Enter your website" tabindex="2">
        <span class="inputMsg site"></span>
        <input id="emailInput" type="email" placeholder="YourEmail@domain.com" tabindex="3">
        <span class="inputMsg email"></span>
        <input id="passwordInput" type="password" placeholder="Password" tabindex="4">
        <span class="inputMsg password"></span>
        <input id="verifyPasswordInput" type="password" placeholder="Confirm password" tabindex="5">
        <span class="inputMsg verifyPassword"></span>
        <button id="submitRegister" type="button">Go!</button>
      </div>
      <h2>Already have your Site Id? <a id="iHaveId" href="#">Enter it here</a></h2>
    </div>
    <div class="footer">
      <div class="support-wrapper center">
        Having trouble? contact us:
        <a class="support-link" href="mailto:support@errnio.com">support@errnio.com</a>
      </div>

      <a class="logo center" href="http://errnio.com" target="_blank"></a>
    </div>
  </div>
  <div id="enterid" class="modal">
    <div class="container">
      <h1>Enter your Id here:</h1>

      <div class="fields">
        <input id="siteIdInput" type="text" placeholder="Site Id" tabindex="1">
        <span class="inputMsg siteid"></span>
        <button id="submitSiteId" type="button">Go!</button>
      </div>
    </div>
  </div>
  <div id="successregister" class="modal">
    <div class="container">
      <h1>Thank you!</h1>
      <p>
        Go check your inbox :-)
      </p>
    </div>
  </div>
  <div id="successsiteid" class="modal">
    <div class="container">
      <h1>Thank you!</h1>
      <p>
        See you at <a href="http://errnio.com">errnio.com</a>
      </p>
    </div>
  </div>
  <div id="fail" class="modal">
    <div class="container">
      <h1>Oops</h1>
      <p class="fail-text">
        Please try again :-/
      </p>
    </div>
  </div>