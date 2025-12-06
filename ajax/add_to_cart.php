<?php
// ajax/add_to_cart.php - ONLY THIS CONTENT
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit();
}

// Check if product exists and is in stock
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND current_stock >= ?");
$stmt->bind_param("ii", $product_id, $quantity);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if(!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not available or out of stock']);
    exit();
}

// Initialize cart if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add/Update item in cart
if(isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = $quantity;
}

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart!',
    'cart_count' => array_sum($_SESSION['cart'])
]);
?>