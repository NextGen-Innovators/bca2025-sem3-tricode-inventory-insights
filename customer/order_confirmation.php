<?php
session_start();
require_once '../includes/config.php';

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get order_id from URL or session
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$customer_id = $_SESSION['customer_id'];

// If no order_id in URL, try session
if($order_id == 0 && isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
}

if($order_id == 0) {
    // No order to confirm, redirect to orders page
    header("Location: orders.php");
    exit();
}

// Fetch order details
$order_stmt = $conn->prepare("SELECT o.*, s.shop_name, s.address as store_address, s.phone as store_phone 
                              FROM orders o 
                              JOIN stores s ON o.store_id = s.id 
                              WHERE o.id = ? AND o.customer_id = ?");
$order_stmt->bind_param("ii", $order_id, $customer_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if(!$order) {
    // Order not found or doesn't belong to customer
    header("Location: orders.php");
    exit();
}

// Fetch order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();
$shipping_fee = 50;
// Get customer address for display
$customer_stmt = $conn->prepare("SELECT address FROM customers WHERE id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_address = $customer_stmt->get_result()->fetch_assoc()['address'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-card {
            border: 2px solid #198754;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(25, 135, 84, 0.1);
        }
        .success-icon {
            color: #198754;
            font-size: 4rem;
        }
        .order-number {
            font-size: 1.8rem;
            color: #198754;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .store-info-card {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i> WasteWise Shopping
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            </div>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Success Message -->
                <div class="confirmation-card mb-5">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle success-icon"></i>
                            <h1 class="mt-3 mb-3">Order Confirmed Successfully!</h1>
                            <p class="lead text-muted">Thank you for your order. We're preparing it for you.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Your Order Number</h5>
                            <div class="order-number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <small class="text-muted">Keep this number for reference</small>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Order Status:</strong> Your order has been received and is being processed.
                            <?php if($order['payment_method'] == 'cash_on_delivery'): ?>
                                Please have cash ready for delivery.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Details -->
                <div class="row">
                    <!-- Order Summary -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $items_total = 0;
                                            while($item = $order_items->fetch_assoc()): 
                                                $items_total += $item['total_price'];
                                            ?>
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
                                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                                <td><strong>Rs. <?php echo number_format($items_total, 2); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                                  <td><strong>Rs. <?php echo number_format($shipping_fee, 2); ?></strong></td>
                                            </tr>
                                            <tr class="table-success">
                                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                                <td><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order & Delivery Information -->
                    <div class="col-lg-6">
                        <!-- Order Info -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Order Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Order Date</small>
                                      <p>Order Date: <?php echo date('F d, Y, h:i A', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Order Time</small>
                                       <p class="mb-3"><?php echo date('h:i A', strtotime($order['created_at'])); ?></p>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Payment Method</small>
                                        <p class="mb-3">
                                            <?php 
                                            $payment_methods = [
                                                'cash_on_delivery' => 'Cash on Delivery',
                                                'esewa' => 'eSewa',
                                                'khalti' => 'Khalti'
                                            ];
                                            echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Payment Status</small>
                                        <p class="mb-3">
                                            <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <?php if(!empty($order['notes'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted">Order Notes</small>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Delivery Information -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Delivery Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Delivery Address</small>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Expected Delivery</small>
                                    <p class="mb-0">1-3 business days</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Store Information -->
                        <div class="card store-info-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-store me-2"></i> Store Information</h5>
                            </div>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($order['shop_name']); ?></h6>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['store_address'])); ?></p>
                                <?php if(!empty($order['store_phone'])): ?>
                                    <p class="mb-0"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($order['store_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="text-center mt-5">
                    <a href="orders.php" class="btn btn-success btn-lg me-3">
                        <i class="fas fa-history me-2"></i> View Order History
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-store me-2"></i> Continue Shopping
                    </a>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-print me-1"></i>
                            <a href="javascript:window.print()" class="text-decoration-none">Print this confirmation</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>WasteWise Nepal</h5>
                    <p>Thank you for helping reduce waste by purchasing soon-to-expire products!</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Need help? <a href="#" class="text-white">Contact Support</a></p>
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> WasteWise Nepal</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to top on page load
        window.onload = function() {
            window.scrollTo(0, 0);
        };
    </script>
</body>
</html>