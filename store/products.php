<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Products - WasteWise</title>
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
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-icon {
            font-size: 5rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }
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
                    <a href="../logout.php">🚪 Logout</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>📦 Your Products</h2>
                    <a href="add_product.php" class="btn btn-success">
                        ➕ Add Your First Product
                    </a>
                </div>
                
                <!-- Check if store has any products -->
                <?php
                $product_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE store_id = $store_id")->fetch_assoc()['count'];
                
                if($product_count == 0): ?>
                
                <!-- EMPTY STATE - No products yet -->
                <div class="card">
                    <div class="card-body empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>No Products Yet</h3>
                        <p class="text-muted mb-4">Start by adding your first product manually.</p>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5>How to add products:</h5>
                                        <ol class="text-start">
                                            <li>Click "Add Product" button</li>
                                            <li>Enter product details manually</li>
                                            <li>Set expiry date (for perishable items)</li>
                                            <li>Set buying and selling prices</li>
                                            <li>Save and start tracking!</li>
                                        </ol>
                                    </div>
                                </div>
                                <a href="add_product.php" class="btn btn-success btn-lg">
                                    ➕ Add Your First Product
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- Show products if store has added any -->
                <div class="alert alert-info">
                    You have <strong><?php echo $product_count; ?></strong> products in your inventory.
                    <a href="add_product.php" class="btn btn-sm btn-success ms-3">Add More</a>
                </div>
                
                <!-- Your existing products display code here -->
                <!-- ... -->
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>