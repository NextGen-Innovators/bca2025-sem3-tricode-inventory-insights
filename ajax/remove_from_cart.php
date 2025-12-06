<?php
// ajax/remove_from_cart.php - SEPARATE FILE
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

if(isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not in cart']);
}
?>