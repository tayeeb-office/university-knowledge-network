document.addEventListener("DOMContentLoaded", function () {
  var navMenu = document.getElementById("navMenu");
  var navLinks = navMenu ? navMenu.querySelectorAll(".nav-link") : [];

  // Close the mobile menu after tapping a nav link.
  navLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      if (navMenu.classList.contains("show")) {
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(navMenu);
        bsCollapse.hide();
      }
    });
  });

  // Highlight the current section's nav link while scrolling.
  var sections = Array.prototype.map.call(navLinks, function (link) {
    var id = link.getAttribute("href");
    return id && id.charAt(0) === "#" ? document.querySelector(id) : null;
  });

  function setActiveLink() {
    var scrollPos = window.scrollY + 96;
    var activeIndex = -1;

    sections.forEach(function (section, index) {
      if (section && section.offsetTop <= scrollPos) {
        activeIndex = index;
      }
    });

    navLinks.forEach(function (link, index) {
      link.classList.toggle("active", index === activeIndex);
    });
  }

  window.addEventListener("scroll", setActiveLink, { passive: true });
  setActiveLink();

  // Password visibility toggle (Login page).
  var pwToggle = document.getElementById("togglePassword");
  var pwInput = document.getElementById("loginPassword");

  if (pwToggle && pwInput) {
    pwToggle.addEventListener("click", function () {
      var showing = pwInput.type === "password";
      pwInput.type = showing ? "text" : "password";
      pwToggle.classList.toggle("pw-visible", showing);
      var label = showing ? "Hide password" : "Show password";
      pwToggle.setAttribute("aria-label", label);
      pwToggle.setAttribute("title", label);
    });
  }

  // Login form validation (frontend only — no submission, no real auth).
  var loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      loginForm.classList.add("was-validated");
    });
  }

  // Password visibility toggles (Register page — Password + Confirm Password).
  var pwToggleButtons = document.querySelectorAll("[data-toggle-for]");

  pwToggleButtons.forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute("data-toggle-for"));
    if (!input) return;

    btn.addEventListener("click", function () {
      var showing = input.type === "password";
      input.type = showing ? "text" : "password";
      btn.classList.toggle("pw-visible", showing);
      var label = showing ? "Hide password" : "Show password";
      btn.setAttribute("aria-label", label);
      btn.setAttribute("title", label);
    });
  });

  // Register page: live password strength hint + bio character counter + validation.
  var registerForm = document.getElementById("registerForm");

  if (registerForm) {
    var regPassword = document.getElementById("regPassword");
    var regConfirm = document.getElementById("regConfirm");
    var pwHint = document.getElementById("pwHint");
    var pwGood = document.getElementById("pwGood");
    var regBio = document.getElementById("regBio");
    var bioCount = document.getElementById("bioCount");
    var formError = document.getElementById("registerFormError");

    function isPasswordStrong(value) {
      return value.length >= 8 && /[0-9]/.test(value);
    }

    function updatePasswordHint() {
      var strong = isPasswordStrong(regPassword.value);
      pwHint.classList.toggle("d-none", regPassword.value.length === 0 || strong);
      pwGood.classList.toggle("d-none", !strong);
    }

    function updateConfirmMatch() {
      if (regConfirm.value && regConfirm.value !== regPassword.value) {
        regConfirm.setCustomValidity("Passwords do not match.");
      } else {
        regConfirm.setCustomValidity("");
      }
    }

    regPassword.addEventListener("input", function () {
      updatePasswordHint();
      updateConfirmMatch();
    });

    regConfirm.addEventListener("input", updateConfirmMatch);

    if (regBio && bioCount) {
      regBio.addEventListener("input", function () {
        bioCount.textContent = regBio.value.length + " / 300";
      });
    }

    // Live per-field validation feedback once a field has been visited.
    var liveFields = registerForm.querySelectorAll(".form-control, .form-select");
    liveFields.forEach(function (field) {
      field.addEventListener("blur", function () {
        field.classList.add("auth-touched");
        if (field.classList.contains("auth-touched")) {
          field.classList.toggle("is-valid", field.checkValidity());
          field.classList.toggle("is-invalid", !field.checkValidity());
        }
      });
      field.addEventListener("input", function () {
        if (field.classList.contains("auth-touched")) {
          field.classList.toggle("is-valid", field.checkValidity());
          field.classList.toggle("is-invalid", !field.checkValidity());
        }
      });
    });

    registerForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      updateConfirmMatch();

      if (!registerForm.checkValidity()) {
        registerForm.classList.add("was-validated");
        formError.textContent = "Please complete the required fields above.";
        formError.classList.remove("d-none");
        var firstInvalid = registerForm.querySelector(":invalid");
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      formError.classList.add("d-none");
      registerForm.classList.add("was-validated");
    });
  }

  // Forgot Password form validation (frontend only — no email is actually sent).
  var forgotForm = document.getElementById("forgotForm");

  if (forgotForm) {
    var forgotEmail = document.getElementById("forgotEmail");
    var resetSuccess = document.getElementById("resetSuccess");
    var resetSentTo = document.getElementById("resetSentTo");
    var useDifferentEmail = document.getElementById("useDifferentEmail");

    forgotForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (!forgotForm.checkValidity()) {
        forgotForm.classList.add("was-validated");
        forgotEmail.focus();
        return;
      }

      resetSentTo.textContent = forgotEmail.value.trim();
      forgotForm.classList.add("d-none");
      resetSuccess.classList.remove("d-none");
    });

    if (useDifferentEmail) {
      useDifferentEmail.addEventListener("click", function () {
        forgotForm.reset();
        forgotForm.classList.remove("was-validated");
        resetSuccess.classList.add("d-none");
        forgotForm.classList.remove("d-none");
        forgotEmail.focus();
      });
    }
  }
});
