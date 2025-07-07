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

// Validate that email and password are present in the input data
if (!isset($data["email"]) || !isset($data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

// Sanitize the email input to prevent SQL injection
$email = $conn->real_escape_string($data["email"]);

// Get the password from input (not sanitized because it will be verified with hash)
$password = $data["password"];

// Prepare an SQL statement to select the user by email
$sql = "SELECT id, full_name, email, password_hash, position FROM employees WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if a user with the given email exists
if ($result->num_rows === 0) {
    // If no user found, return 401 Unauthorized error
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

// Fetch the user data as an associative array
$user = $result->fetch_assoc();

// Verify the password against the stored password hash
if (!password_verify($password, $user["password_hash"])) {
    // If password does not match, return 401 Unauthorized error
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

// Set session variables for the logged-in user
$_SESSION["user_id"] = $user["id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["email"] = $user["email"];
$_SESSION["position"] = $user["position"];

// Return success message and user info as JSON
echo json_encode([
    "message" => "Login successful",
    "user" => [
        "id" => $user["id"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "position" => $user["position"]
    ]
]);
?>
