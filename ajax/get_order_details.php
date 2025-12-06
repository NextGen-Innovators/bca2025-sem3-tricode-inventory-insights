<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$customer_id = $_SESSION['customer_id'];

if($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit();
}

// Get order details
$order_stmt = $conn->prepare("SELECT o.*, s.shop_name, s.address as store_address FROM orders o 
                             JOIN stores s ON o.store_id = s.id 
                             WHERE o.id = ? AND o.customer_id = ?");
$order_stmt->bind_param("ii", $order_id, $customer_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if(!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
?>