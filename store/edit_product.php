<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];
$error = '';
$success = '';

// Get product ID
if(!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
$stmt->bind_param("ii", $product_id, $store_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $buying_price = floatval($_POST['buying_price']);
    $selling_price = floatval($_POST['selling_price']);
    $current_stock = intval($_POST['current_stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    
    // Validation
    if(empty($name)) {
        $error = "Product name is required!";
    } elseif($buying_price <= 0) {
        $error = "Buying price must be greater than 0!";
    } elseif($selling_price <= 0) {
        $error = "Selling price must be greater than 0!";
    } else {
        // Update product
        $sql = "UPDATE products SET 
                name = ?, category = ?, buying_price = ?, selling_price = ?, 
                current_stock = ?, min_stock = ?, expiry_date = ?
                WHERE id = ? AND store_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssddiisii", $name, $category, $buying_price, $selling_price, 
                         $current_stock, $min_stock, $expiry_date, $product_id, $store_id);
        
        if($stmt->execute()) {
            $success = "Product updated successfully!";
            
            // Update waste alerts if expiry changed
            if($expiry_date) {
                $today = date('Y-m-d');
                $expiry = new DateTime($expiry_date);
                $today_date = new DateTime($today);
                $interval = $today_date->diff($expiry);
                $days_left = $interval->days;
                
                // Delete old alerts
                $conn->query("DELETE FROM waste_alerts WHERE product_id = $product_id");
                
                if($days_left <= 7) {
                    $severity = ($days_left <= 2) ? 'critical' : 'high';
                    $discount = ($days_left <= 2) ? 70 : 50;
                    
                    $alert_sql = "INSERT INTO waste_alerts (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                                 VALUES (?, 'expiring_soon', ?, 'discount', ?, ?)";
                    $alert_stmt = $conn->prepare($alert_sql);
                    $alert_stmt->bind_param("isii", $product_id, $severity, $discount, $days_left);
                    $alert_stmt->execute();
                }
            }
        } else {
            $error = "Error updating product: " . $conn->error;
        }
    }
}

// Categories
$categories = [
    'dairy' => 'Dairy 🥛',
    'bakery' => 'Bakery 🍞',
    'fruits_veg' => 'Fruits & Vegetables 🍎',
    'beverages' => 'Beverages 🥤',
    'groceries' => 'Groceries 🍚',
    'snacks' => 'Snacks 🍫',
    'personal_care' => 'Personal Care 🧴',
    'stationery' => 'Stationery 🖊️',
    'other' => 'Other 📦'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - WasteWise</title>
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
                    <h2>✏️ Edit Product</h2>
                    <a href="products.php" class="btn btn-outline-secondary">← Back to Products</a>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <?php if($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <?php if($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label>Product Name *</label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($product['name']); ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label>Category *</label>
                                        <select name="category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach($categories as $key => $value): ?>
                                                <option value="<?php echo $key; ?>"
                                                    <?php echo ((isset($_POST['category']) && $_POST['category'] == $key) || $product['category'] == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label>Buying Price (रु) *</label>
                                            <input type="number" step="0.01" name="buying_price" class="form-control" 
                                                   value="<?php echo isset($_POST['buying_price']) ? $_POST['buying_price'] : $product['buying_price']; ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label>Selling Price (रु) *</label>
                                            <input type="number" step="0.01" name="selling_price" class="form-control" 
                                                   value="<?php echo isset($_POST['selling_price']) ? $_POST['selling_price'] : $product['selling_price']; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label>Current Stock *</label>
                                            <input type="number" name="current_stock" class="form-control" 
                                                   value="<?php echo isset($_POST['current_stock']) ? $_POST['current_stock'] : $product['current_stock']; ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label>Min Stock Alert</label>
                                            <input type="number" name="min_stock" class="form-control" 
                                                   value="<?php echo isset($_POST['min_stock']) ? $_POST['min_stock'] : $product['min_stock']; ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label>Expiry Date</label>
                                            <input type="date" name="expiry_date" class="form-control" 
                                                   value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : $product['expiry_date']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <button type="submit" class="btn btn-success w-100">
                                                💾 Update Product
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <a href="products.php" class="btn btn-outline-secondary w-100">
                                                Cancel
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5>📊 Product Info</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Added:</strong> <?php echo date('M d, Y', strtotime($product['added_date'])); ?></li>
                                    <li><strong>Last Updated:</strong> Now</li>
                                    <?php if($product['discount_price']): ?>
                                        <li class="text-success">
                                            <strong>Discount Active:</strong> 
                                            रु <?php echo number_format($product['discount_price'], 2); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                
                                <hr>
                                
                                <h6>Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <a href="products.php?delete_id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Delete this product?')">
                                        🗑️ Delete Product
                                    </a>
                                    
                                    <?php if($product['expiry_date']): 
                                        $days_left = (new DateTime($product['expiry_date']))->diff(new DateTime())->days;
                                        if($days_left <= 7 && !$product['discount_price']): ?>
                                        <a href="products.php?apply_discount=1&product_id=<?php echo $product['id']; ?>&percent=<?php echo ($days_left <= 2 ? 70 : 50); ?>" 
                                           class="btn btn-warning"
                                           onclick="return confirm('Apply discount?')">
                                            🏷️ Apply <?php echo ($days_left <= 2 ? '70%' : '50%'); ?> Discount
                                        </a>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>