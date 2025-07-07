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

// Query to count total employees
$sql = "SELECT COUNT(*) as total FROM employees";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $total = intval($row['total']);
    // Log for debugging
    file_put_contents(__DIR__ . "/debug.log", "Total employees count fetched: $total\n", FILE_APPEND);
    echo json_encode(["total_employees" => $total]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch employee count"]);
}

$conn->close();
?>
