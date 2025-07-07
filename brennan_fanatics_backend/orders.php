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
        // List orders
        // Admin can see all orders, Cashier can see only their orders (if implemented)
        $sql = "SELECT * FROM orders ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            // Fetch order items
            $order_id = $row["id"];
            $items_sql = "SELECT oi.id, oi.product_id, p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
            $stmt = $conn->prepare($items_sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $items_result = $stmt->get_result();
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
            $stmt->close();

            $row["items"] = $items;
            $orders[] = $row;
        }
        echo json_encode($orders);
        break;

    case "POST":
        // Create order
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data["customer_name"], $data["customer_address"], $data["customer_email"], $data["items"], $data["total_amount"], $data["payment_amount"], $data["change_amount"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // Sanitize input data
        $customer_name = $conn->real_escape_string($data["customer_name"]);
        $customer_address = $conn->real_escape_string($data["customer_address"]);
        $customer_email = $conn->real_escape_string($data["customer_email"]);
        $items = $data["items"];
        $total_amount = floatval($data["total_amount"]);
        $payment_amount = floatval($data["payment_amount"]);
        $change_amount = floatval($data["change_amount"]);

        // Generate unique order ID
        $order_id_str = "ORD" . time();

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert order details into orders table
            $stmt = $conn->prepare("INSERT INTO orders (order_id, customer_name, customer_address, customer_email, total_amount, payment_amount, change_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssddd", $order_id_str, $customer_name, $customer_address, $customer_email, $total_amount, $payment_amount, $change_amount);
            $stmt->execute();
            $order_db_id = $stmt->insert_id;
            $stmt->close();

            // Insert order items and update product stock
            foreach ($items as $item) {
                $product_id = intval($item["product_id"]);
                $quantity = intval($item["quantity"]);
                $price = floatval($item["price"]);

                // Check stock availability
                $stock_check_sql = "SELECT quantity FROM products WHERE id = ?";
                $stock_stmt = $conn->prepare($stock_check_sql);
                $stock_stmt->bind_param("i", $product_id);
                $stock_stmt->execute();
                $stock_result = $stock_stmt->get_result();
                $stock_row = $stock_result->fetch_assoc();
                $stock_stmt->close();

                if (!$stock_row || $stock_row["quantity"] < $quantity) {
                    throw new Exception("Insufficient stock for product ID $product_id");
                }

                // Insert order item into order_items table
                $insert_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_item_sql);
                $insert_stmt->bind_param("iiid", $order_db_id, $product_id, $quantity, $price);
                $insert_stmt->execute();
                $insert_stmt->close();

                // Update product stock quantity
                $update_stock_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_stock_sql);
                $update_stmt->bind_param("ii", $quantity, $product_id);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Commit transaction
            $conn->commit();
            echo json_encode(["message" => "Order created successfully", "order_id" => $order_id_str]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    default:
        // Method not allowed
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
?>
