<?php
session_start();
header("Content-Type: application/json");
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"], $data["fullName"], $data["email"], $data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$id = intval($data["id"]);
$fullName = $conn->real_escape_string($data["fullName"]);
$email = $conn->real_escape_string($data["email"]);
$password = $data["password"];

// Check if email is used by another employee
$stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Email already in use by another employee"]);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password if changed (assuming password is plain text)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Update employee
$stmt = $conn->prepare("UPDATE employees SET full_name = ?, email = ?, password_hash = ? WHERE id = ?");
$stmt->bind_param("sssi", $fullName, $email, $password_hash, $id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Employee updated successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update employee"]);
}

$stmt->close();
$conn->close();
?>
