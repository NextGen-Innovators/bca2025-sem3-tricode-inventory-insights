<?php
session_start();
require_once '../includes/config.php';

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Build query
$query = "SELECT o.*, s.shop_name FROM orders o 
          JOIN stores s ON o.store_id = s.id 
          WHERE o.customer_id = ?";
$params = [$customer_id];
$types = "i";

if($status_filter != 'all') {
    $query .= " AND o.order_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
$query .= " ORDER BY o.created_at DESC";


$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-card {
            border-left: 4px solid #198754;
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i> WasteWise Shopping
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link me-3" href="cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2 fs-5"></i>
                        <span><?php echo $_SESSION['full_name']; ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-history me-2"></i> Order History</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history me-2"></i> Order History</h2>
            <div>
                <a href="dashboard.php" class="btn btn-outline-success">
                    <i class="fas fa-store me-1"></i> Continue Shopping
                </a>
            </div>
        </div>
        
        <!-- Status Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?status=all" class="btn btn-sm <?php echo $status_filter == 'all' ? 'btn-success' : 'btn-outline-success'; ?>">All Orders</a>
                    <a href="?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                    <a href="?status=confirmed" class="btn btn-sm <?php echo $status_filter == 'confirmed' ? 'btn-info' : 'btn-outline-info'; ?>">Confirmed</a>
                    <a href="?status=preparing" class="btn btn-sm <?php echo $status_filter == 'preparing' ? 'btn-primary' : 'btn-outline-primary'; ?>">Preparing</a>
                    <a href="?status=ready" class="btn btn-sm <?php echo $status_filter == 'ready' ? 'btn-info' : 'btn-outline-info'; ?>">Ready</a>
                    <a href="?status=delivered" class="btn btn-sm <?php echo $status_filter == 'delivered' ? 'btn-success' : 'btn-outline-success'; ?>">Delivered</a>
                    <a href="?status=cancelled" class="btn btn-sm <?php echo $status_filter == 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
                </div>
            </div>
        </div>
        
        <?php if($orders->num_rows > 0): ?>
            <div class="row">
                <?php while($order = $orders->fetch_assoc()): 
                    // Get status badge color
                    $status_colors = [
                        'pending' => 'bg-warning',
                        'confirmed' => 'bg-info',
                        'preparing' => 'bg-primary',
                        'ready' => 'bg-info',
                        'delivered' => 'bg-success',
                        'cancelled' => 'bg-danger'
                    ];
                    $status_color = $status_colors[$order['order_status']] ?? 'bg-secondary';
                    
                    // Get order items count
                    $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count, SUM(total_price) as items_total FROM order_items WHERE order_id = ?");
                    $count_stmt->bind_param("i", $order['id']);
                    $count_stmt->execute();
                    $order_summary = $count_stmt->get_result()->fetch_assoc();
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card order-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-store me-1"></i> <?php echo htmlspecialchars($order['shop_name']); ?>
                                    </p>
                                </div>
                                <span class="status-badge <?php echo $status_color; ?> text-white">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Order Date</small>
                                    <p class="mb-0"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Items</small>
                                    <p class="mb-0"><?php echo $order_summary['item_count']; ?> items</p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted">Delivery Address</small>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($order['shipping_address'], 0, 100))); ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-success">Rs. <?php echo number_format($order['total_amount'], 2); ?></h5>
                                <div>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if($order['order_status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                <h4>No orders found</h4>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="dashboard.php" class="btn btn-success">
                    <i class="fas fa-store me-1"></i> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if(confirm('Are you sure you want to cancel this order?')) {
                fetch('../ajax/cancel_order.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Order cancelled successfully');
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>