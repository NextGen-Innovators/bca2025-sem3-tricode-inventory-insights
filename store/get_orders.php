<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['store_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$store_id = $_SESSION['store_id'];

$sql = "SELECT * FROM orders WHERE store_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders);
?>