<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];

// Handle order confirmation
if (isset($_POST['confirm_order'])) {
    $order_id = intval($_POST['order_id']);
    $conn->query("UPDATE orders SET order_status='confirmed' WHERE id=$order_id");
    header("Location: get_orders.php?confirmed=1");
    exit();
}

// Fetch all orders for this store
$sql = "SELECT o.*, c.full_name AS customer_name, c.phone AS customer_phone
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.store_id = $store_id
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Orders - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        .sidebar {
            background: #198754;
            min-height: 100vh;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 5px;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .order-card {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .status-pending { background: #ffc107; }
        .status-confirmed { background: #28a745; color: white; }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-2 p-0 sidebar">
            <div class="p-3">
                <h5>🏪 <?php echo $_SESSION['shop_name']; ?></h5>
                <hr>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="products.php">📦 Products</a>
                <a href="add_product.php">➕ Add Product</a>
                <a href="alerts.php">⚠️ Alerts</a>
                <a href="get_orders.php">📌 Get Orders</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">

            <?php if(isset($_GET['confirmed'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Order Confirmed Successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h2 class="mb-4">📌 Orders</h2>

            <?php if ($result->num_rows > 0): ?>
                <?php while($order = $result->fetch_assoc()): ?>

                    <div class="order-card">
                        <div class="d-flex justify-content-between">
                            <h5>Order #<?= $order['id'] ?> — <?= $order['customer_name'] ?></h5>
                            <span class="badge status-<?= $order['order_status'] ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </div>

                        <hr>

                        <p><strong>📞 Phone:</strong> <?= $order['customer_phone'] ?></p>
                        <p><strong>💰 Amount:</strong> NPR <?= number_format($order['total_amount'], 2) ?></p>
                        <p><strong>📍 Address:</strong> <?= $order['shipping_address'] ?></p>
                        <p><strong>💳 Payment:</strong>
                            <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                            (<?= ucfirst($order['payment_status']) ?>)
                        </p>
                        <p><strong>📝 Notes:</strong> <?= $order['notes'] ?></p>
                        <p><strong>⏱ Created:</strong> <?= $order['created_at'] ?></p>
                        <p><strong>🚚 Delivery Date:</strong> <?= $order['delivery_date'] ?? 'N/A' ?></p>

                        <?php if ($order['order_status'] == 'pending'): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" name="confirm_order" class="btn btn-success">
                                    ✔ Confirm Order
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center mt-5">
                    <p class="text-muted">No orders found.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
