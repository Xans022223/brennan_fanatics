<?php
// Include the database configuration file to connect to the database
require_once "config.php";

// Define default users to be added to the database
$users = [
    [
        "full_name" => "Admin User",
        "email" => "admin@test.com",
        "password" => "1234",
        "position" => "Admin"
    ],
    [
        "full_name" => "Cashier User",
        "email" => "cashier@test.com",
        "password" => "1234",
        "position" => "Cashier"
    ]
];

// Loop through each default user
foreach ($users as $user) {
    // Check if user already exists in the database by email
    $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
    $stmt->bind_param("s", $user["email"]);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // If user exists, print message and skip insertion
        echo "User with email {$user['email']} already exists.\n";
        $stmt->close();
        continue;
    }
    $stmt->close();

    // Hash the user's password securely
    $password_hash = password_hash($user["password"], PASSWORD_DEFAULT);

    // Prepare an SQL statement to insert the new user
    $stmt = $conn->prepare("INSERT INTO employees (full_name, email, password_hash, position) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user["full_name"], $user["email"], $password_hash, $user["position"]);

    // Execute the insert statement and print success or failure message
    if ($stmt->execute()) {
        echo "User with email {$user['email']} added successfully.\n";
    } else {
        echo "Failed to add user with email {$user['email']}.\n";
    }
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
