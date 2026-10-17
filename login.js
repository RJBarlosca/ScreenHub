// ============================================================
//  LOGIN FORM
// ============================================================
document.addEventListener("DOMContentLoaded", () => {
  const form     = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");

  if (!form || !errorBox) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    errorBox.textContent = "";

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;

    if (!username || !password) {
      errorBox.textContent = "Please enter your username/email and password.";
      return;
    }

    try {
      const response = await fetch("login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password })
      });
      const result = await response.json();

      if (result.status === "success") {
        window.location.href = "dashboard.php";
      } else {
        errorBox.textContent = result.message;
      }
    } catch {
      errorBox.textContent = "Connection error. Make sure XAMPP is running.";
    }
  });
});

// ============================================================
//  FORGOT PASSWORD — 3-step flow:
//  Step 1) Send Code   →  sendResetBtn
//  Step 2) Verify Code →  verifyCodeBtn
//  Step 3) Update Pass →  updatePassBtn
// ============================================================
document.addEventListener("DOMContentLoaded", () => {
  const forgotEmail     = document.getElementById("forgotEmail");
  const sendBtn         = document.getElementById("sendResetBtn");
  const codeSection     = document.getElementById("codeSection");
  const codeInput       = document.getElementById("codeInput");
  const verifyCodeBtn   = document.getElementById("verifyCodeBtn");
  const passwordSection = document.getElementById("passwordSection");
  const newPass         = document.getElementById("newPass");
  const confirmPass     = document.getElementById("confirmPass");
  const updatePassBtn   = document.getElementById("updatePassBtn");
  const forgotStatus    = document.getElementById("forgotStatus");

  function showStatus(msg, isError) {
    forgotStatus.textContent = msg;
    forgotStatus.className = "forgot-status " + (isError ? "forgot-error" : "forgot-success");
    forgotStatus.style.display = "block";
  }

  // ----------------------------------------------------------
  //  STEP 1: Send 4-digit code to email
  // ----------------------------------------------------------
  if (sendBtn) {
    sendBtn.addEventListener("click", async () => {
      const email = forgotEmail.value.trim();
      if (!email) { showStatus("Please enter your email.", true); return; }

      sendBtn.disabled = true;
      sendBtn.textContent = "Sending...";

      try {
        const res    = await fetch("forgot_password.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email })
        });
        const result = await res.json();

        if (result.status === "success") {
          showStatus("Code sent! Check your inbox (or spam). Expires in 10 min.", false);
          codeSection.style.display = "block";
          codeInput.focus();
        } else {
          showStatus(result.message, true);
        }
      } catch {
        showStatus("Connection error. Is XAMPP running?", true);
      } finally {
        sendBtn.disabled = false;
        sendBtn.textContent = "Send Code";
      }
    });
  }

  // ----------------------------------------------------------
  //  STEP 2: Verify the 4-digit code
  // ----------------------------------------------------------
  if (verifyCodeBtn) {
    verifyCodeBtn.addEventListener("click", async () => {
      const email = forgotEmail.value.trim();
      const code  = codeInput.value.trim();

      if (code.length !== 4) { showStatus("Enter the 4-digit code.", true); return; }

      verifyCodeBtn.disabled = true;
      verifyCodeBtn.textContent = "Verifying...";

      try {
        const res    = await fetch("verify_code.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email, code })
        });
        const result = await res.json();

        if (result.status === "success") {
          showStatus("Code verified! Now set your new password.", false);
          passwordSection.style.display = "block";
          newPass.focus();
          // Hide the code section so UI is clean
          codeSection.style.display = "none";
          sendBtn.style.display     = "none";
          forgotEmail.disabled      = true;
        } else {
          showStatus(result.message, true);
        }
      } catch {
        showStatus("Connection error.", true);
      } finally {
        verifyCodeBtn.disabled = false;
        verifyCodeBtn.textContent = "Verify Code";
      }
    });
  }

  // ----------------------------------------------------------
  //  STEP 3: Update the password
  // ----------------------------------------------------------
  if (updatePassBtn) {
    updatePassBtn.addEventListener("click", async () => {
      const email    = forgotEmail.value.trim();
      const password = newPass.value;
      const confirm  = confirmPass.value;

      if (password.length < 8) {
        showStatus("Password must be at least 8 characters.", true); return;
      }
      if (password !== confirm) {
        showStatus("Passwords do not match.", true); return;
      }

      updatePassBtn.disabled = true;
      updatePassBtn.textContent = "Updating...";

      try {
        const res    = await fetch("verify_code.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email, code: codeInput.value.trim(), password })
        });
        const result = await res.json();

        if (result.status === "success") {
          forgotStatus.innerHTML =
            'Password updated! <a href="index.php" style="color:#e50914;">← Sign In</a>';
          forgotStatus.className = "forgot-status forgot-success";
          forgotStatus.style.display = "block";
          passwordSection.style.display = "none";
        } else {
          showStatus(result.message, true);
        }
      } catch {
        showStatus("Connection error.", true);
      } finally {
        updatePassBtn.disabled = false;
        updatePassBtn.textContent = "Update Password";
      }
    });
  }
});
