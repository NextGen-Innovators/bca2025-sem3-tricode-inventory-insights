<?php
// ajax/get_cart_count.php - SEPARATE FILE
session_start();

header('Content-Type: application/json');

if(isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
    echo json_encode([
        'success' => true,
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode([
        'success' => true,
        'cart_count' => 0
    ]);
}
?>