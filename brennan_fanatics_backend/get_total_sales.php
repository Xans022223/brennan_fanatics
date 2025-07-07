<?php
header("Content-Type: application/json");
require_once "config.php";

$sql = "SELECT SUM(total) as total_sales FROM orders";
$result = $conn->query($sql);

$totalSales = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $totalSales = (float)$row["total_sales"];
}

echo json_encode(["total_sales" => $totalSales]);
$conn->close();
?>
