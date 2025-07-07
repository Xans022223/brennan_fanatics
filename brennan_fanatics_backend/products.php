<?php
// Start the session to manage user sessions
session_start();

// Set the response content type to JSON
header("Content-Type: application/json");

// Include the database configuration file to connect to the database
require_once "config.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit;
}

// Get the HTTP request method
$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        // List products
        $sql = "SELECT id, name, price, quantity, image_path, created_at, updated_at FROM products";
        $result = $conn->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
        break;

    case "POST":
        // Add product
        if (!isset($_POST["name"], $_POST["price"], $_POST["quantity"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // Sanitize input data
        $name = $conn->real_escape_string($_POST["name"]);
        $price = floatval($_POST["price"]);
        $quantity = intval($_POST["quantity"]);

        // Handle image upload
        $image_path = null;
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/products/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $filename = uniqid() . "." . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
                $image_path = "uploads/products/" . $filename;
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to upload image"]);
                exit;
            }
        }

        $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdiss", $name, $price, $quantity, $image_path);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Product added successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add product"]);
        }
        $stmt->close();
        break;

    case "PUT":
        // Update product
        parse_str(file_get_contents("php://input"), $put_vars);
        if (!isset($_GET["id"])) {
            http_response_code(400);
            echo json_encode(["error" => "Product ID is required"]);
            exit;
        }
        $id = intval($_GET["id"]);

        // Sanitize input data if provided
        $name = isset($put_vars["name"]) ? $conn->real_escape_string($put_vars["name"]) : null;
        $price = isset($put_vars["price"]) ? floatval($put_vars["price"]) : null;
        $quantity = isset($put_vars["quantity"]) ? intval($put_vars["quantity"]) : null;

        // Build update query dynamically based on provided fields
        $fields = [];
        $params = [];
        $types = "";

        if ($name !== null) {
            $fields[] = "name = ?";
            $params[] = $name;
            $types .= "s";
        }
        if ($price !== null) {
            $fields[] = "price = ?";
            $params[] = $price;
            $types .= "d";
        }
        if ($quantity !== null) {
            $fields[] = "quantity = ?";
            $params[] = $quantity;
            $types .= "i";
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["error" => "No fields to update"]);
            exit;
        }

        $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Product updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update product"]);
        }
        $stmt->close();
        break;

    case "DELETE":
        // Delete product
        if (!isset($_GET["id"])) {
            http_response_code(400);
            echo json_encode(["error" => "Product ID is required"]);
            exit;
        }
        $id = intval($_GET["id"]);

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Product deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete product"]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
?>
