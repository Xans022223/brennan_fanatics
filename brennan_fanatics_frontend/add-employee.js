let selectedPosition = "";

const adminBtn = document.getElementById("adminBtn");
const cashierBtn = document.getElementById("cashierBtn");
const authFields = document.querySelectorAll(".auth-fields");

adminBtn.addEventListener("click", () => {
  selectedPosition = "Admin";
  adminBtn.classList.add("selected");
  cashierBtn.classList.remove("selected");
  authFields.forEach(f => f.style.display = "block");
});

cashierBtn.addEventListener("click", () => {
  selectedPosition = "Cashier";
  cashierBtn.classList.add("selected");
  adminBtn.classList.remove("selected");
  authFields.forEach(f => f.style.display = "block");
});

document.getElementById("employeeForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const fullName = document.getElementById("fullName").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  if (!fullName || !selectedPosition || !email || !password) {
    alert("Please fill out all fields.");
    return;
  }

  const newEmployee = {
    fullName,
    email,
    password,
    position: selectedPosition
  };

  fetch("../brennan_fanatics_backend/add_employee.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(newEmployee)
  })
  .then(response => response.json())
  .then(data => {
    if (data.message === "Employee added successfully") {
      alert(`${fullName} added successfully!`);
      if (selectedPosition === "Admin") {
        window.location.href = "indexLogin.html";
      } else {
        window.location.href = "cashier.html";
      }
    } else if (data.error) {
      alert(`Error: ${data.error}`);
    } else {
      alert("Unknown error occurred.");
    }
  })
  .catch(error => {
    alert("Error connecting to server.");
    console.error("Add employee error:", error);
  });
});
