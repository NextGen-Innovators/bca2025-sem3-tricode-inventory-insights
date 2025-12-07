<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get ALL data from form manually
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $buying_price = floatval($_POST['buying_price']);
    $selling_price = floatval($_POST['selling_price']);
    $current_stock = intval($_POST['current_stock']);
    $min_stock = intval($_POST['min_stock']);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    
    // Manual validation
    if(empty($name)) {
        $error = "Product name is required!";
    } elseif($buying_price <= 0) {
        $error = "Buying price must be greater than 0!";
    } elseif($selling_price <= 0) {
        $error = "Selling price must be greater than 0!";
    } elseif($selling_price < $buying_price) {
        $error = "Selling price cannot be less than buying price!";
    } else {
        // Insert product MANUALLY
        $sql = "INSERT INTO products (store_id, name, category, buying_price, selling_price, current_stock, min_stock, expiry_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issddiis", $store_id, $name, $category, $buying_price, $selling_price, $current_stock, $min_stock, $expiry_date);
        
        if($stmt->execute()) {
            $product_id = $stmt->insert_id;
            $success = "Product added successfully!";
            
            // Check if expiry date is set and create alert if needed
            if($expiry_date) {
                $today = new DateTime();
                $expiry = new DateTime($expiry_date);
                $days_left = $today->diff($expiry)->days;
                
                if($days_left <= 7) {
                    // Auto-create waste alert
                    $severity = $days_left <= 2 ? 'critical' : 'high';
                    $discount = $days_left <= 2 ? 70 : 50;
                    
                    $alert_sql = "INSERT INTO waste_alerts (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                                 VALUES (?, 'expiring_soon', ?, 'discount', ?, ?)";
                    $alert_stmt = $conn->prepare($alert_sql);
                    $alert_stmt->bind_param("isii", $product_id, $severity, $discount, $days_left);
                    $alert_stmt->execute();
                    
                    $success .= " Waste alert created (expires in $days_left days)!";
                }
            }
            
            // Clear form
            $_POST = [];
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    }
}

// Categories for manual selection
$categories = [
    'dairy' => 'Dairy 🥛',
    'bakery' => 'Bakery 🍞', 
    'fruits_veg' => 'Fruits & Vegetables 🍎',
    'beverages' => 'Beverages 🥤',
    'groceries' => 'Groceries 🍚',
    'snacks' => 'Snacks 🍫',
    'personal_care' => 'Personal Care 🧴',
    'other' => 'Other 📦'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - WasteWise</title>
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
        .form-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3">
                    <h5> <?php echo $_SESSION['shop_name']; ?></h5>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>➕ Add Product Manually</h2>
                    <a href="products.php" class="btn btn-outline-secondary">
                        ← Back to Products
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card form-card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Enter Product Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <?php if($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <!-- Product Name -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Product Name *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                               placeholder="e.g., Milk, Bread, Rice" required>
                                        <small class="text-muted">Enter exact product name as in your shop</small>
                                    </div>
                                    
                                    <!-- Category -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Category *</label>
                                        <select class="form-select" name="category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach($categories as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" 
                                                    <?php echo (isset($_POST['category']) && $_POST['category'] == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <!-- Buying Price -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Buying Price (रु) *</label>
                                            <input type="number" step="0.01" class="form-control" name="buying_price" 
                                                   value="<?php echo isset($_POST['buying_price']) ? $_POST['buying_price'] : ''; ?>" 
                                                   placeholder="0.00" required min="0.01">
                                            <small class="text-muted">Price you pay to supplier</small>
                                        </div>
                                        
                                        <!-- Selling Price -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Selling Price (रु) *</label>
                                            <input type="number" step="0.01" class="form-control" name="selling_price" 
                                                   value="<?php echo isset($_POST['selling_price']) ? $_POST['selling_price'] : ''; ?>" 
                                                   placeholder="0.00" required min="0.01">
                                            <small class="text-muted">Price you sell to customers</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <!-- Current Stock -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Current Stock *</label>
                                            <input type="number" class="form-control" name="current_stock" 
                                                   value="<?php echo isset($_POST['current_stock']) ? $_POST['current_stock'] : '0'; ?>" 
                                                   required min="0">
                                            <small class="text-muted">Units available now</small>
                                        </div>
                                        
                                        <!-- Min Stock -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Min Stock Alert</label>
                                            <input type="number" class="form-control" name="min_stock" 
                                                   value="<?php echo isset($_POST['min_stock']) ? $_POST['min_stock'] : '5'; ?>" 
                                                   min="1">
                                            <small class="text-muted">Alert when stock goes below this</small>
                                        </div>
                                        
                                        <!-- Expiry Date -->
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Expiry Date</label>
                                                <input type="date" name="expiry_date" id="expiry_date"
                                                      class="form-control" required   value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ''; ?>">
                                            <small class="text-muted">For perishable items only</small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-success btn-lg">
                                             Save Product
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"> Manual Entry Tips</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <strong>Everything is manual!</strong> You must enter all details yourself.
                                </div>
                                
                                <h6>For Waste Reduction:</h6>
                                <ul class="list-unstyled">
                                    <li>✅ <strong>Set expiry dates</strong> for perishable items</li>
                                    <li>✅ System will create <strong>automatic alerts</strong></li>
                                    <li>✅ Get <strong>discount suggestions</strong> for expiring items</li>
                                    <li>✅ <strong>Track waste</strong> and save money</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>Example Products:</h6>
                                <small class="text-muted">
                                    • Milk (Dairy) - Expires in 3 days<br>
                                    • Bread (Bakery) - Expires in 2 days<br>
                                    • Rice (Groceries) - No expiry<br>
                                    • Soap (Personal Care) - No expiry
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                                                
    <script>
        document.addEventListener("DOMContentLoaded", () => {
    const expiryInput = document.getElementById("expiry_date");

    if (expiryInput) {
        let today = new Date().toISOString().split("T")[0];
        expiryInput.setAttribute("min", today);
    }
});
        // Auto-set expiry to 7 days from now as default
        document.addEventListener('DOMContentLoaded', function() {
            const expiryInput = document.querySelector('input[name="expiry_date"]');
            if(expiryInput && !expiryInput.value) {
                const nextWeek = new Date();
                nextWeek.setDate(nextWeek.getDate() + 7);
                expiryInput.valueAsDate = nextWeek;
            }
            
            // Auto-calculate 30% profit margin
            const buyingInput = document.querySelector('input[name="buying_price"]');
            const sellingInput = document.querySelector('input[name="selling_price"]');
            
            if(buyingInput && sellingInput) {
                buyingInput.addEventListener('input', function() {
                    if(this.value && !sellingInput.value) {
                        const sellingPrice = parseFloat(this.value) * 1.3;
                        sellingInput.value = sellingPrice.toFixed(2);
                    }
                });
            }
        });
    </script>
</body>
</html>

