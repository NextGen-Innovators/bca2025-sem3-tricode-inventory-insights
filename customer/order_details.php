<?php
session_start();
require_once '../includes/config.php';

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer_id = $_SESSION['customer_id'];

// Get order details
$order_stmt = $conn->prepare("SELECT o.*, s.shop_name, s.phone as store_phone, s.address as store_address FROM orders o 
                             JOIN stores s ON o.store_id = s.id 
                             WHERE o.id = ? AND o.customer_id = ?");
$order_stmt->bind_param("ii", $order_id, $customer_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if(!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

// Status colors
$status_colors = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'preparing' => 'primary',
    'ready' => 'info',
    'delivered' => 'success',
    'cancelled' => 'danger'
];
$status_color = $status_colors[$order['order_status']] ?? 'secondary';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Order Details</h2>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $order_items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>Rs. <?php echo number_format($item['total_price'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Order Number</small>
                            <p class="mb-0 fw-bold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Order Status</small>
                            <p class="mb-0">
                                <span class="badge bg-<?php echo $status_color; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Order Date</small>
                         <p class="mb-0"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Payment Method</small>
                            <p class="mb-0"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Payment Status</small>
                            <p class="mb-0">
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Store Information -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Store Information</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($order['shop_name']); ?></h6>
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['store_address'])); ?></p>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($order['store_phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>