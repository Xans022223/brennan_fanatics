<?php
// Start the session to manage user sessions
session_start();

// Set the response content type to JSON
header("Content-Type: application/json");

// Include the database configuration file to connect to the database
require_once "config.php";

// Check if the request method is POST, otherwise return 405 Method Not Allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Get the JSON input from the request body and decode it into an associative array
$data = json_decode(file_get_contents("php://input"), true);

// Validate that the employee ID is present in the input data
if (!isset($data["id"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing employee ID"]);
    exit;
}

// Convert the employee ID to an integer
$id = intval($data["id"]);

// Prepare an SQL statement to delete the employee by ID
$stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);

// Execute the delete statement and check if it was successful
if ($stmt->execute()) {
    // Return success message as JSON
    echo json_encode(["message" => "Employee deleted successfully"]);
} else {
    // Return server error if deletion failed
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete employee"]);
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>
