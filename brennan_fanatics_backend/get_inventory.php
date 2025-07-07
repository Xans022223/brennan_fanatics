<?php
header("Content-Type: application/json");
require_once "config.php";

$sql = "SELECT id, name, quantity, price FROM products";
$result = $conn->query($sql);

$inventory = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory[] = [
            "id" => $row["id"],
            "name" => $row["name"],
            "quantity" => (int)$row["quantity"],
            "price" => (float)$row["price"]
        ];
    }
}

echo json_encode($inventory);
$conn->close();
?>
