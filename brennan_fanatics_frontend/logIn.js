document.querySelector("form").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const messageBox = document.querySelector(".message-box");

  fetch("http://localhost/brennan_fanatics_backend/login.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ email, password })
  })
  .then(response => response.json())
  .then(data => {
    if (data.message === "Login successful") {
      messageBox.className = "message-box success";
      messageBox.innerText = `✅ Welcome, ${data.user.full_name}! Redirecting...`;
      messageBox.style.display = "block";

      setTimeout(() => {
        if (data.user.position === "Admin") {
          window.location.href = "indexLogin.html";
        } else if (data.user.position === "Cashier") {
          window.location.href = "cashier.html";
        } else {
          alert("User has no valid role.");
        }
      }, 1500);
    } else {
      messageBox.className = "message-box";
      messageBox.innerText = "❌ Invalid email or password.";
      messageBox.style.display = "block";
    }
  })
  .catch(error => {
    messageBox.className = "message-box";
    messageBox.innerText = "❌ Error connecting to server.";
    messageBox.style.display = "block";
    console.error("Login error:", error);
  });
});
