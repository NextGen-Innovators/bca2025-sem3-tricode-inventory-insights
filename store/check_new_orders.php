<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['store_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$store_id = $_SESSION['store_id'];
$last_id = $_GET['last_id'] ?? 0;

$sql = "SELECT * FROM orders WHERE store_id = ? AND id > ? AND status = 'pending' ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $store_id, $last_id);
$stmt->execute();
$result = $stmt->get_result();

$newOrders = [];
while ($row = $result->fetch_assoc()) {
    $newOrders[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['newOrders' => $newOrders]);
?>