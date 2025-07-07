<?php
// Start the session to manage user sessions
session_start();

// Set the response content type to JSON
header("Content-Type: application/json");

// Check if the request method is GET, otherwise return 405 Method Not Allowed
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Return success message as JSON
echo json_encode(["message" => "Logout successful"]);
?>
