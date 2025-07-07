function loadDashboardData() {
  console.log("Loading dashboard data...");

  // Fetch employee count from backend
  fetch("../brennan_fanatics_backend/get_employee_count.php")
    .then(response => {
      console.log("Employee count response status:", response.status);
      return response.json();
    })
    .then(data => {
      console.log("Employee count data:", data);
      const employeeCount = data.total_employees || 0;
      document.getElementById("employees").textContent = `${employeeCount} staff`;
    })
    .catch(error => {
      console.error("Error fetching employees count:", error);
      document.getElementById("employees").textContent = "0 staff";
    });

  // Fetch employee list from backend
  fetch("../brennan_fanatics_backend/get_employees.php")
    .then(response => {
      console.log("Employee list response status:", response.status);
      return response.json();
    })
    .then(employees => {
      console.log("Employee list data:", employees);
      const listContainer = document.getElementById("employeeListContainer");
      if (!listContainer) return;
      listContainer.innerHTML = "";

      if (employees.length === 0) {
        listContainer.innerHTML = "<p>No employees yet.</p>";
        return;
      }

      const ul = document.createElement("ul");
      employees.forEach(emp => {
        const li = document.createElement("li");
        li.textContent = `${emp.full_name} (${emp.position}) - ${emp.email}`;
        ul.appendChild(li);
      });
      listContainer.appendChild(ul);
    })
    .catch(error => {
      console.error("Error fetching employee list:", error);
    });

  // Fetch inventory from backend
  fetch("../brennan_fanatics_backend/get_inventory.php")
    .then(response => {
      console.log("Inventory response status:", response.status);
      return response.json();
    })
    .then(products => {
      console.log("Inventory data:", products);
      const tbody = document.querySelector(".inventory-table tbody");
      tbody.innerHTML = "";

      if (products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3">No products yet.</td></tr>`;
        document.getElementById("inventory").textContent = "0 items";
        return;
      }

      document.getElementById("inventory").textContent = `${products.length} items`;

      products.forEach(product => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${product.name}</td>
          <td>${product.quantity}</td>
          <td>₱${parseFloat(product.price).toFixed(2)}</td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => {
      console.error("Error fetching inventory:", error);
    });

  // Fetch total sales from backend
  fetch("../brennan_fanatics_backend/get_total_sales.php")
    .then(response => {
      console.log("Total sales response status:", response.status);
      return response.json();
    })
    .then(data => {
      console.log("Total sales data:", data);
      const totalSales = data.total_sales || 0;
      document.getElementById("sales").textContent =
        `₱${totalSales.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

      // Update sales chart data
      salesChart.data.datasets[0].data = [10, 20, 30, 10, 20, totalSales];
      salesChart.update();
    })
    .catch(error => {
      console.error("Error fetching total sales:", error);
    });

  // Fetch customer orders from backend
  fetch("../brennan_fanatics_backend/get_customer_orders.php")
    .then(response => {
      console.log("Customer orders response status:", response.status);
      return response.json();
    })
    .then(orders => {
      console.log("Customer orders data:", orders);
      const tbody = document.getElementById("ordersTableBody");
      tbody.innerHTML = "";

      if (orders.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6">No customer orders found.</td></tr>`;
        return;
      }

      orders.forEach(order => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${order.id}</td>
          <td>${order.name}</td>
          <td>${order.address}</td>
          <td>${order.email}</td>
          <td>
            <ul>
              ${order.items.map(item => `<li>${item.qty} x ${item.name}</li>`).join("")}
            </ul>
          </td>
          <td>₱${order.total.toFixed(2)}</td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => {
      console.error("Error fetching customer orders:", error);
    });
}

// Call this once when the page loads
loadDashboardData();

// Live date/time
function updateDateTime() {
  const now = new Date();
  const formatted = now.toLocaleString("en-PH", {
    weekday: "short", year: "numeric", month: "short",
    day: "numeric", hour: "2-digit", minute: "2-digit"
  });
  document.getElementById("dateTime").textContent = formatted;
}

updateDateTime();
setInterval(updateDateTime, 60000);

// Logout function
function logout() {
  // Clear session or any auth tokens here if needed
  sessionStorage.clear();
  localStorage.clear();
  window.location.href = "logIn.html";
}

// Bind logout button
document.getElementById("logoutBtn").addEventListener("click", logout);

// Initialize Chart.js sales chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [{
      label: 'Sales (₱)',
      data: [10, 20, 30, 10, 20, 50],
      borderColor: '#2a5298',
      backgroundColor: 'rgba(42, 82, 152, 0.3)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          boxWidth: 20,
          padding: 15
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          drawBorder: false,
          color: '#e0e0e0'
        }
      },
      x: {
        grid: {
          drawBorder: false,
          color: '#e0e0e0'
        }
      }
    }
  }
});
