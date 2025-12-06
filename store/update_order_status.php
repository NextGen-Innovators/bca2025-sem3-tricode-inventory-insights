<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['store_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['orderId'] ?? null;
$status = $data['status'] ?? null;

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$store_id = $_SESSION['store_id'];

// Verify store owns this order
$checkSql = "SELECT id FROM orders WHERE id = ? AND store_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $orderId, $store_id);
$checkStmt->execute();

if ($checkStmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Update status
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>