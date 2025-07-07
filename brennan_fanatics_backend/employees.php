<?php
// Start the session to manage user sessions
session_start();

// Set the response content type to JSON
header("Content-Type: application/json");

// Include the database configuration file to connect to the database
require_once "config.php";

// Check if user is logged in and is Admin
if (!isset($_SESSION["user_id"]) || $_SESSION["position"] !== "Admin") {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit;
}

// Get the HTTP request method
$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        // List employees
        $sql = "SELECT id, full_name, email, position, photo_path, created_at, updated_at FROM employees";
        $result = $conn->query($sql);
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        echo json_encode($employees);
        break;

    case "POST":
        // Add employee
        if (!isset($_POST["full_name"], $_POST["email"], $_POST["password"], $_POST["position"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // Sanitize input data
        $full_name = $conn->real_escape_string($_POST["full_name"]);
        $email = $conn->real_escape_string($_POST["email"]);
        $password = $_POST["password"];
        $position = $conn->real_escape_string($_POST["position"]);

        // Validate position
        if (!in_array($position, ["Admin", "Cashier"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid position"]);
            exit;
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["error" => "Email already exists"]);
            exit;
        }
        $stmt->close();

        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/employees/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $filename = uniqid() . "." . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_path)) {
                $photo_path = "uploads/employees/" . $filename;
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to upload photo"]);
                exit;
            }
        }

        // Hash the password securely
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new employee into the database
        $stmt = $conn->prepare("INSERT INTO employees (full_name, email, password_hash, position, photo_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $password_hash, $position, $photo_path);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee added successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add employee"]);
        }
        $stmt->close();
        break;

    case "PUT":
        // Update employee
        parse_str(file_get_contents("php://input"), $put_vars);
        if (!isset($_GET["id"])) {
            http_response_code(400);
            echo json_encode(["error" => "Employee ID is required"]);
            exit;
        }
        $id = intval($_GET["id"]);

        // Sanitize input data if provided
        $full_name = isset($put_vars["full_name"]) ? $conn->real_escape_string($put_vars["full_name"]) : null;
        $email = isset($put_vars["email"]) ? $conn->real_escape_string($put_vars["email"]) : null;
        $password = isset($put_vars["password"]) ? $put_vars["password"] : null;
        $position = isset($put_vars["position"]) ? $conn->real_escape_string($put_vars["position"]) : null;

        // Validate position if provided
        if ($position !== null && !in_array($position, ["Admin", "Cashier"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid position"]);
            exit;
        }

        // Build update query dynamically based on provided fields
        $fields = [];
        $params = [];
        $types = "";

        if ($full_name !== null) {
            $fields[] = "full_name = ?";
            $params[] = $full_name;
            $types .= "s";
        }
        if ($email !== null) {
            $fields[] = "email = ?";
            $params[] = $email;
            $types .= "s";
        }
        if ($password !== null) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= "s";
        }
        if ($position !== null) {
            $fields[] = "position = ?";
            $params[] = $position;
            $types .= "s";
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["error" => "No fields to update"]);
            exit;
        }

        $sql = "UPDATE employees SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update employee"]);
        }
        $stmt->close();
        break;

    case "DELETE":
        // Delete employee
        if (!isset($_GET["id"])) {
            http_response_code(400);
            echo json_encode(["error" => "Employee ID is required"]);
            exit;
        }
        $id = intval($_GET["id"]);

        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete employee"]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
?>
