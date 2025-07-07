<?php
header("Content-Type: application/json");
require_once "config.php";

$sql = "SELECT id, customer_name, address, email, items, total FROM orders";
$result = $conn->query($sql);

$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            "id" => $row["id"],
            "name" => $row["customer_name"],
            "address" => $row["address"],
            "email" => $row["email"],
            "items" => json_decode($row["items"], true),
            "total" => (float)$row["total"]
        ];
    }
}

echo json_encode($orders);
$conn->close();
?>
