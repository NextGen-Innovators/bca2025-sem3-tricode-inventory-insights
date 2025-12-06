<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];

// Check if product ID is provided
if(!isset($_GET['id'])) {
    header("Location: products.php?error=No product selected");
    exit();
}

$product_id = intval($_GET['id']);

// Verify product belongs to the store
$check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND store_id = ?");
$check_stmt->bind_param("ii", $product_id, $store_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows == 0) {
    header("Location: products.php?error=Product not found");
    exit();
}

// Remove discount
$update_stmt = $conn->prepare("UPDATE products SET discount_price = NULL WHERE id = ? AND store_id = ?");
$update_stmt->bind_param("ii", $product_id, $store_id);

if($update_stmt->execute()) {
    // If product was expiring, create new alert
    $product_info = $conn->query("SELECT expiry_date FROM products WHERE id = $product_id")->fetch_assoc();
    
    if($product_info['expiry_date']) {
        $today = new DateTime();
        $expiry = new DateTime($product_info['expiry_date']);
        $days_left = $today->diff($expiry)->days;
        
        if($days_left <= 7) {
            // Delete any existing alerts
            $conn->query("DELETE FROM waste_alerts WHERE product_id = $product_id");
            
            // Create new alert
            $severity = $days_left <= 2 ? 'critical' : 'high';
            $discount = $days_left <= 2 ? 70 : 50;
            
            $alert_sql = "INSERT INTO waste_alerts (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                         VALUES (?, 'expiring_soon', ?, 'discount', ?, ?)";
            $alert_stmt = $conn->prepare($alert_sql);
            $alert_stmt->bind_param("isii", $product_id, $severity, $discount, $days_left);
            $alert_stmt->execute();
        }
    }
    
    header("Location: products.php?message=Discount removed successfully!");
} else {
    header("Location: products.php?error=Failed to remove discount");
}

exit();
?>