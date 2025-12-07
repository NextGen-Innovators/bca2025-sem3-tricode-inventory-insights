<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

// Get store stats
$store_id = $_SESSION['store_id'];
$total_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE store_id = $store_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WasteWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3">
                    <h5><?php echo $_SESSION['shop_name']; ?></h5>
                    <hr>
                    <a href="dashboard.php"> Dashboard</a>
                    <a href="products.php">📦 Products</a>
                    <a href="add_product.php">➕ Add Product</a>
                    <a href="alerts.php">⚠️ Alerts</a>
                    <a href="get_orders.php">📌Get Order</a>
                    <a href="../logout.php">🚪 Logout</a>

                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2>Welcome, <?php echo $_SESSION['owner_name']; ?>! </h2>
                <p class="text-muted">Smart waste reduction dashboard</p>
                
                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total Products</h6>
                                <h2><?php echo $total_products['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h6>Expiring Soon</h6>
                                <h2>0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Low Stock</h6>
                                <h2>0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Waste Saved</h6>
                                <h2>रु 0</h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="add_product.php" class="btn btn-primary w-100">
                                    ➕ Add New Product
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="products.php" class="btn btn-info w-100">
                                    📦 View Products
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="alerts.php" class="btn btn-danger w-100">
                                    ⚠️ Check Alerts
                                </a>

                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="get_orders.php" class="btn btn-danger w-100">
                                    📌 Get Orders
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
                

            </div>
        </div>
    </div>
</body>
</html>