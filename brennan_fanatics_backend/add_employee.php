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

// Validate that all required fields are present in the input data
if (!isset($data["fullName"], $data["email"], $data["password"], $data["position"], $data["photo"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Sanitize input data to prevent SQL injection
$fullName = $conn->real_escape_string($data["fullName"]);
$email = $conn->real_escape_string($data["email"]);
$password = $data["password"]; // Password will be hashed later
$position = $conn->real_escape_string($data["position"]);
$photoBase64 = $data["photo"];

// Check if a user with the same email already exists in the database
$stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    // If user exists, return 409 Conflict error
    http_response_code(409);
    echo json_encode(["error" => "User with this email already exists"]);
    $stmt->close();
    exit;
}
$stmt->close();

// Handle photo base64 upload
$photo_path = null;
if ($photoBase64) {
    // Extract base64 data
    if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $type)) {
        $photoBase64 = substr($photoBase64, strpos($photoBase64, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid image type"]);
            exit;
        }

        $photoData = base64_decode($photoBase64);
        if ($photoData === false) {
            http_response_code(400);
            echo json_encode(["error" => "Base64 decode failed"]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid image data"]);
        exit;
    }

    $upload_dir = __DIR__ . "/uploads/employees/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid() . "." . $type;
    $file_path = $upload_dir . $filename;

    if (file_put_contents($file_path, $photoData) === false) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save image"]);
        exit;
    }

    $photo_path = "uploads/employees/" . $filename;
}

// Hash the password securely using PHP's password_hash function
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Prepare an SQL statement to insert the new employee into the database including photo_path
$stmt = $conn->prepare("INSERT INTO employees (full_name, email, password_hash, position, photo_path) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullName, $email, $password_hash, $position, $photo_path);

// Execute the insert statement and check if it was successful
if ($stmt->execute()) {
    // Log success to a file for debugging
    file_put_contents(__DIR__ . "/debug.log", "Employee added: $fullName, $email\n", FILE_APPEND);

    // Return success message as JSON
    echo json_encode(["message" => "Employee added successfully"]);
} else {
    // Log failure to a file for debugging
    file_put_contents(__DIR__ . "/debug.log", "Failed to add employee: $fullName, $email\n", FILE_APPEND);

    // Return server error if insertion failed
    http_response_code(500);
    echo json_encode(["error" => "Failed to add employee"]);
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>
