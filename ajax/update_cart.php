<?php
// ajax/update_cart.php - SEPARATE FILE
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

// Check if product exists
$stmt = $conn->prepare("SELECT current_stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if(!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

if($quantity > $product['current_stock']) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
    exit();
}

if($quantity == 0) {
    // Remove from cart
    unset($_SESSION['cart'][$product_id]);
} else {
    // Update quantity
    $_SESSION['cart'][$product_id] = $quantity;
}

echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
?>