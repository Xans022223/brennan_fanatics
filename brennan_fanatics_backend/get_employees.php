<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session to manage user sessions
// session_start(); // Disabled session check to allow public access for debugging

// Set the response content type to JSON
header("Content-Type: application/json");

// Include the database configuration file to connect to the database
require_once "config.php";

// Check if the request method is GET, otherwise return 405 Method Not Allowed
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Prepare and execute SQL query to select employee details including photo_path
$sql = "SELECT id, full_name, email, position, photo_path FROM employees";
$result = $conn->query($sql);

// Initialize an array to hold employee data
$employees = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Return the employee data as JSON
echo json_encode($employees);

// Close the database connection
$conn->close();
?>
