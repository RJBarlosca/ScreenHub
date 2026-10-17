// signup.js
const form = document.getElementById("signupForm");
const errorBox = document.getElementById("signupError");

form.addEventListener("submit", async function (e) { // Added 'async'
  e.preventDefault();

  const username = document.getElementById("newUsername").value.trim();
  const email = document.getElementById("newEmail").value.trim().toLowerCase();
  const password = document.getElementById("newPassword").value;
  const confirm = document.getElementById("confirmPassword").value;

  errorBox.textContent = "";

  // 1. Existing Validations
  if (!username || !email || !password || !confirm) {
    errorBox.textContent = "Please fill in all fields.";
    return;
  }

  if (!isStrongPassword(password)) {
    errorBox.textContent = "Password must be at least 8 characters and include uppercase, lowercase, number and special character.";
    return;
  }

  if (password !== confirm) {
    errorBox.textContent = "Passwords do not match.";
    return;
  }

  // 2. NEW: Send data to the Database instead of localStorage
  try {
    const response = await fetch('register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: username,
        email: email,
        password: password
      })
    });

    const result = await response.json();

    if (result.status === "success") {
      alert("Registration successful!");
      window.location.href = "index.html"; // Go to login page
    } else {
      errorBox.textContent = result.message; // Show "Username already exists" etc.
    }
  } catch (error) {
    errorBox.textContent = "Server error. Please try again later.";
  }
});

// Keep your existing isStrongPassword and DOMContentLoaded functions below...