<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$customer_id = $_SESSION['customer_id'];

if($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit();
}

// Check if order belongs to customer and is pending
$check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ? AND order_status = 'pending'");
$check_stmt->bind_param("ii", $order_id, $customer_id);
$check_stmt->execute();

if($check_stmt->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel this order']);
    exit();
}

// Update order status to cancelled
$update_stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
$update_stmt->bind_param("i", $order_id);

if($update_stmt->execute()) {
    // Restore product stock
    $items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result();
    
    while($item = $items->fetch_assoc()) {
        $restore_stmt = $conn->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
        $restore_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $restore_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
}
?>




