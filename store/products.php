<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Handle Delete
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND store_id = ?");
    $delete_stmt->bind_param("ii", $delete_id, $store_id);
    if($delete_stmt->execute()) {
        // Also delete related alerts
        $conn->query("DELETE FROM waste_alerts WHERE product_id = $delete_id");
        header("Location: products.php?deleted=1");
        exit();
    }
}

// Handle Apply Discount
if(isset($_GET['apply_discount']) && isset($_GET['product_id']) && isset($_GET['percent'])) {
    $product_id = intval($_GET['product_id']);
    $percent = intval($_GET['percent']);
    
    // Calculate discount price
    $product_query = $conn->query("SELECT selling_price FROM products WHERE id = $product_id AND store_id = $store_id");
    if($product_query->num_rows > 0) {
        $product = $product_query->fetch_assoc();
        $discount_price = $product['selling_price'] * (100 - $percent) / 100;
        
        $update_stmt = $conn->prepare("UPDATE products SET discount_price = ? WHERE id = ? AND store_id = ?");
        $update_stmt->bind_param("dii", $discount_price, $product_id, $store_id);
        $update_stmt->execute();
        
        // Mark alert as resolved
        $conn->query("UPDATE waste_alerts SET is_resolved = TRUE WHERE product_id = $product_id");
        
        header("Location: products.php?discount_applied=1");
        exit();
    }
}

// Build query for products
$sql = "SELECT * FROM products WHERE store_id = ?";
$params = ["i", $store_id];

if($search) {
    $sql .= " AND name LIKE ?";
    $params[0] .= "s";
    $params[] = "%$search%";
}

if($category && $category != 'all') {
    $sql .= " AND category = ?";
    $params[0] .= "s";
    $params[] = $category;
}

$sql .= " ORDER BY expiry_date ASC, added_date DESC";

$stmt = $conn->prepare($sql);

// Bind parameters dynamically
if(count($params) > 1) {
    $stmt->bind_param(...$params);
} else {
    $stmt->bind_param($params[0], $store_id);
}

$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE store_id = $store_id");

// Get product count
$product_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE store_id = $store_id")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Products - WasteWise</title>
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
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-icon {
            font-size: 5rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        .product-card {
            border-radius: 10px;
            transition: transform 0.3s;
            border: 1px solid #dee2e6;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .expiry-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .category-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
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
                      <a href="get_orders.php">📌Get Order</a>
                    <a href="../logout.php">🚪 Logout</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Success Messages -->
                <?php if(isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Product deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['discount_applied'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Discount applied successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>📦 Your Products</h2>
                    <a href="add_product.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </a>
                </div>
                
                <!-- Check if store has any products -->
                <?php if($product_count == 0): ?>
                
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
                                    <i class="bi bi-plus-circle"></i> Add Your First Product
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- Search & Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="category" class="form-select">
                                    <option value="all">All Categories</option>
                                    <?php while($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['category']; ?>" 
                                            <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="row">
                    <?php while($product = $products->fetch_assoc()): 
                        $days_left = $product['expiry_date'] ? 
                            (new DateTime($product['expiry_date']))->diff(new DateTime())->days : null;
                        
                        // Determine badge color
                        if($days_left !== null) {
                            if($days_left <= 2) $badge_class = 'bg-danger';
                            elseif($days_left <= 7) $badge_class = 'bg-warning';
                            else $badge_class = 'bg-success';
                        } else {
                            $badge_class = 'bg-secondary';
                        }
                        
                        // Get icon
                        $icons = [
                            'dairy' => '🥛', 'bakery' => '🍞', 'fruits_veg' => '🍎',
                            'beverages' => '🥤', 'groceries' => '🍚', 'snacks' => '🍫',
                            'personal_care' => '🧴', 'stationery' => '🖊️'
                        ];
                        $icon = $icons[$product['category']] ?? '📦';
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="category-icon"><?php echo $icon; ?></span>
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    </div>
                                    <span class="badge <?php echo $badge_class; ?> expiry-badge">
                                        <?php if($days_left !== null): ?>
                                            <?php echo $days_left; ?>d
                                        <?php else: ?>
                                            No Expiry
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="badge bg-secondary"><?php echo $product['category']; ?></span>
                                    <?php if($product['discount_price']): ?>
                                        <span class="badge bg-success">Discounted</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Buying Price</small>
                                        <p class="mb-0 fw-bold">रु <?php echo number_format($product['buying_price'], 2); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Selling Price</small>
                                        <p class="mb-0 fw-bold">
                                            <?php if($product['discount_price']): ?>
                                                <span class="text-danger">रु <?php echo number_format($product['discount_price'], 2); ?></span>
                                                <small class="text-muted text-decoration-line-through d-block">
                                                    रु <?php echo number_format($product['selling_price'], 2); ?>
                                                </small>
                                            <?php else: ?>
                                                रु <?php echo number_format($product['selling_price'], 2); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Stock</small>
                                        <p class="mb-0">
                                            <?php echo $product['current_stock']; ?> units
                                            <?php if($product['current_stock'] <= $product['min_stock']): ?>
                                                <span class="badge bg-warning">Low!</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Expiry</small>
                                        <p class="mb-0">
                                            <?php echo $product['expiry_date'] ? $product['expiry_date'] : 'N/A'; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons mt-3">
                                    <!-- Edit Button -->
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <a href="products.php?delete_id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete <?php echo htmlspecialchars($product['name']); ?>?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                    
                                    <!-- Apply Discount Button (for expiring items) -->
                                    <?php if($days_left !== null && $days_left <= 7 && !$product['discount_price']): ?>
                                        <a href="products.php?apply_discount=1&product_id=<?php echo $product['id']; ?>&percent=<?php echo ($days_left <= 2 ? 70 : 50); ?>" 
                                           class="btn btn-sm btn-warning"
                                           onclick="return confirm('Apply <?php echo ($days_left <= 2 ? 70 : 50); ?>% discount?')">
                                            <i class="bi bi-percent"></i> 
                                            <?php echo ($days_left <= 2 ? '70%' : '50%'); ?> Off
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Remove Discount Button -->
                                    <?php if($product['discount_price']): ?>
                                        <a href="remove_discount.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-info"
                                           onclick="return confirm('Remove discount?')">
                                            <i class="bi bi-x-circle"></i> Remove Discount
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Stats Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            $stats = $conn->query("
                                SELECT 
                                    COUNT(*) as total,
                                    SUM(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                                    SUM(CASE WHEN current_stock <= min_stock THEN 1 ELSE 0 END) as low_stock,
                                    SUM(CASE WHEN discount_price IS NOT NULL THEN 1 ELSE 0 END) as discounted
                                FROM products 
                                WHERE store_id = $store_id
                            ")->fetch_assoc();
                            ?>
                            <div class="col-md-3">
                                <h3><?php echo $stats['total']; ?></h3>
                                <small class="text-muted">Total Products</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning"><?php echo $stats['expiring_soon']; ?></h3>
                                <small class="text-muted">Expiring Soon</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-danger"><?php echo $stats['low_stock']; ?></h3>
                                <small class="text-muted">Low Stock</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success"><?php echo $stats['discounted']; ?></h3>
                                <small class="text-muted">Discounted</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh after actions
        if(window.location.search.includes('deleted') || window.location.search.includes('discount_applied')) {
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 2000);
        }
    </script>
</body>
</html>