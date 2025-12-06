<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkLogin();

$store_id = $_SESSION['store_id'];

// Function to calculate days left
function getDaysLeft($expiry_date) {
    if(!$expiry_date) return null;
    
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    $interval = $today->diff($expiry);
    
    // If expiry is in the past, return 0
    if($today > $expiry) {
        return 0;
    }
    
    return $interval->days;
}

// Handle actions first
$action = $_GET['action'] ?? '';

switch($action) {
    case 'apply_discount':
        $product_id = intval($_GET['product_id']);
        $percent = intval($_GET['percent']);
        
        // Get product info with expiry date
        $product_query = $conn->query("SELECT selling_price, expiry_date FROM products WHERE id = $product_id AND store_id = $store_id");
        if($product_query->num_rows > 0) {
            $product = $product_query->fetch_assoc();
            $discount_price = $product['selling_price'] * (100 - $percent) / 100;
            
            // Calculate CURRENT days left
            $current_days_left = getDaysLeft($product['expiry_date']);
            
            $update_stmt = $conn->prepare("UPDATE products SET discount_price = ? WHERE id = ? AND store_id = ?");
            $update_stmt->bind_param("dii", $discount_price, $product_id, $store_id);
            $update_stmt->execute();
            
            // Update ALL alerts for this product with current days
            $update_alert_stmt = $conn->prepare("
                UPDATE waste_alerts 
                SET is_resolved = TRUE, 
                    days_remaining = ?,
                    resolved_at = NOW()
                WHERE product_id = ? AND is_resolved = FALSE
            ");
            $update_alert_stmt->bind_param("ii", $current_days_left, $product_id);
            $update_alert_stmt->execute();
            
            // If stock is 0 after discount, delete product
            $conn->query("DELETE FROM products WHERE id = $product_id AND current_stock = 0");
            
            header("Location: alerts.php?message=Discount applied successfully! (Days left: " . ($current_days_left ?? 'N/A') . ")");
            exit();
        }
        break;
        
    case 'mark_donation':
        $product_id = intval($_GET['product_id']);
        
        // Get expiry date before deleting
        $product_info = $conn->query("SELECT expiry_date FROM products WHERE id = $product_id")->fetch_assoc();
        $current_days_left = getDaysLeft($product_info['expiry_date']);
        
        // Update alert with current days
        $update_alert = $conn->prepare("
            UPDATE waste_alerts 
            SET is_resolved = TRUE, 
                suggested_action = 'donated',
                days_remaining = ?,
                resolved_at = NOW()
            WHERE product_id = ?
        ");
        $update_alert->bind_param("ii", $current_days_left, $product_id);
        $update_alert->execute();
        
        // Remove product after donation
        $conn->query("DELETE FROM products WHERE id = $product_id");
        
        header("Location: alerts.php?message=Marked as donated! Product removed. (Was expiring in: " . ($current_days_left ?? 'N/A') . " days)");
        exit();
        break;
        
    case 'resolve':
        $alert_id = intval($_GET['id']);
        
        // Get product info first
        $alert_info = $conn->query("
            SELECT wa.product_id, p.expiry_date 
            FROM waste_alerts wa
            JOIN products p ON wa.product_id = p.id
            WHERE wa.id = $alert_id
        ")->fetch_assoc();
        
        // Calculate current days
        $current_days_left = getDaysLeft($alert_info['expiry_date']);
        
        // Update with current days
        $update_alert = $conn->prepare("
            UPDATE waste_alerts 
            SET is_resolved = TRUE, 
                days_remaining = ?,
                resolved_at = NOW()
            WHERE id = ?
        ");
        $update_alert->bind_param("ii", $current_days_left, $alert_id);
        $update_alert->execute();
        
        // Check if product stock is 0, then delete
        $product_check = $conn->query("SELECT current_stock FROM products WHERE id = {$alert_info['product_id']}")->fetch_assoc();
        
        if($product_check['current_stock'] <= 0) {
            $conn->query("DELETE FROM products WHERE id = {$alert_info['product_id']}");
        }
        
        header("Location: alerts.php?message=Alert resolved! (Days left: " . ($current_days_left ?? 'N/A') . ")");
        exit();
        break;
        
    case 'delete_product':
        $product_id = intval($_GET['product_id']);
        
        // Get product name for message
        $product_query = $conn->query("SELECT name FROM products WHERE id = $product_id AND store_id = $store_id");
        
        if($product_query->num_rows > 0) {
            $product_name = $product_query->fetch_assoc()['name'];
            
            // Delete any alerts for this product
            $conn->query("DELETE FROM waste_alerts WHERE product_id = $product_id");
            
            // Delete the product
            $delete_result = $conn->query("DELETE FROM products WHERE id = $product_id AND store_id = $store_id");
            
            if($delete_result) {
                header("Location: alerts.php?message=✅ Product '$product_name' deleted successfully!");
            } else {
                header("Location: alerts.php?error=❌ Failed to delete product");
            }
        } else {
            header("Location: alerts.php?error=❌ Product not found");
        }
        exit();
        break;
        
    case 'generate_alerts':
        // First, clear ALL old unresolved alerts
        $conn->query("DELETE wa FROM waste_alerts wa 
                      JOIN products p ON wa.product_id = p.id 
                      WHERE p.store_id = $store_id AND wa.is_resolved = FALSE");
        
        // Get all products that need alerts (expiring OR expired OR low stock)
        $products = $conn->query("
            SELECT 
                id,
                expiry_date,
                current_stock,
                min_stock,
                selling_price
            FROM products 
            WHERE store_id = $store_id 
            AND (
                (expiry_date IS NOT NULL)  -- Any product with expiry date
                OR 
                (current_stock <= min_stock)
            )
        ");
        
        $alerts_created = 0;
        
        while($product = $products->fetch_assoc()) {
            $product_id = $product['id'];
            $current_days_left = getDaysLeft($product['expiry_date']);
            
            // Check if product is expired
            if($product['expiry_date'] && $current_days_left !== null && $current_days_left <= 0) {
                // EXPIRED PRODUCT - Create delete alert
                $alert_type = 'expired';
                $suggested_action = 'delete';
                $discount_percent = NULL;
                $severity = 'critical';
                
                $stmt = $conn->prepare("
                    INSERT INTO waste_alerts 
                    (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssii", $product_id, $alert_type, $severity, $suggested_action, $discount_percent, 0);
                
            } elseif($product['expiry_date'] && $current_days_left !== null && $current_days_left <= 7) {
                // Expiring soon alert (HIGHER PRIORITY)
                $alert_type = 'expiring_soon';
                $suggested_action = 'discount';
                $discount_percent = $current_days_left <= 2 ? 70 : 50;
                $severity = $current_days_left <= 2 ? 'critical' : 'high';
                
                $stmt = $conn->prepare("
                    INSERT INTO waste_alerts 
                    (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssii", $product_id, $alert_type, $severity, $suggested_action, $discount_percent, $current_days_left);
                
            } elseif($product['current_stock'] <= $product['min_stock']) {
                // Low stock alert (LOWER PRIORITY - only if NOT expiring/expired)
                $alert_type = 'low_stock';
                $suggested_action = 'bundle';
                $discount_percent = NULL;
                $severity = 'medium';
                
                $stmt = $conn->prepare("
                    INSERT INTO waste_alerts 
                    (product_id, alert_type, severity, suggested_action, discount_percent, days_remaining) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssii", $product_id, $alert_type, $severity, $suggested_action, $discount_percent, NULL);
            } else {
                // No alert needed
                continue;
            }
            
            if($stmt->execute()) {
                $alerts_created++;
            }
        }
        
        header("Location: alerts.php?message=$alerts_created alerts generated!");
        exit();
        break;
}

// Get active alerts - FIXED to prevent duplicates
$stmt = $conn->prepare("
    SELECT 
        wa.*, 
        p.name, 
        p.category, 
        p.expiry_date, 
        p.current_stock, 
        p.selling_price, 
        p.discount_price,
        DATEDIFF(p.expiry_date, CURDATE()) as current_days_left
    FROM waste_alerts wa
    JOIN products p ON wa.product_id = p.id
    WHERE p.store_id = ? 
    AND wa.is_resolved = FALSE
    AND wa.id IN (
        -- Get only ONE alert per product (the latest one)
        SELECT MAX(wa2.id) 
        FROM waste_alerts wa2
        WHERE wa2.is_resolved = FALSE
        GROUP BY wa2.product_id
    )
    ORDER BY 
        CASE wa.severity 
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        wa.created_at DESC
");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$alerts_result = $stmt->get_result();

// Store alerts in array to check for duplicates
$unique_alerts = [];
while($alert = $alerts_result->fetch_assoc()) {
    // Use CURRENT calculated days, not stored days
    $days_to_show = $alert['current_days_left'];
    if($days_to_show < 0) $days_to_show = 0;
    $alert['days_remaining'] = $days_to_show; // Override with current days
    
    if(!isset($unique_alerts[$alert['product_id']])) {
        $unique_alerts[$alert['product_id']] = $alert;
    }
}

// Get alert statistics (counting unique products)
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT wa.product_id) as total,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low
    FROM (
        SELECT wa2.* 
        FROM waste_alerts wa2
        WHERE wa2.is_resolved = FALSE
        AND wa2.id IN (
            SELECT MAX(id) 
            FROM waste_alerts 
            WHERE is_resolved = FALSE 
            GROUP BY product_id
        )
    ) wa
    JOIN products p ON wa.product_id = p.id
    WHERE p.store_id = $store_id
")->fetch_assoc();

// This month's progress (waste saved)
$month_start = date('Y-m-01');
$progress = $conn->query("
    SELECT 
        COUNT(*) as items_saved,
        SUM(p.selling_price * 0.5) as money_saved
    FROM waste_alerts wa
    JOIN products p ON wa.product_id = p.id
    WHERE p.store_id = $store_id 
    AND wa.is_resolved = TRUE
    AND DATE(wa.resolved_at) >= '$month_start'
")->fetch_assoc();

// Resolved count
$resolved_count = $conn->query("
    SELECT COUNT(*) as count FROM waste_alerts wa
    JOIN products p ON wa.product_id = p.id
    WHERE p.store_id = $store_id AND wa.is_resolved = TRUE
")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waste Alerts - WasteWise</title>
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
        .alert-card {
            border-left: 5px solid;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        .critical { border-color: #dc3545; }
        .high { border-color: #fd7e14; }
        .medium { border-color: #ffc107; }
        .low { border-color: #0dcaf0; }
        .expired { 
            border-color: #343a40 !important;
            background-color: #f8f9fa !important;
        }
        .progress-card {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            border-radius: 15px;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .action-btn {
            margin: 2px;
            font-size: 0.85rem;
        }
        .no-duplicates {
            border: 2px solid #28a745;
            background-color: #f8fff9;
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
                    <a href="alerts.php" style="background: rgba(255,255,255,0.2);">⚠️ Alerts</a>
                    <a href="../logout.php">🚪 Logout</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>⚠️ Smart Waste Alerts</h2>
                        <p class="text-muted mb-0">One alert per product - No duplicates!</p>
                    </div>
                    <div class="btn-group">
                        <a href="alerts.php?action=generate_alerts" class="btn btn-warning">
                            🔄 Generate Alerts
                        </a>
                        <a href="products.php" class="btn btn-outline-primary">
                            📦 View Products
                        </a>
                    </div>
                </div>
                
                <!-- Success Message -->
                <?php if(isset($_GET['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- This Month's Progress -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card progress-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>This Month's Progress</h6>
                                        <h2 class="mb-0"><?php echo $progress['items_saved'] ?? 0; ?> Items</h2>
                                        <p class="mb-0">Saved from waste</p>
                                    </div>
                                    <div class="stat-icon">📈</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Critical Alerts</h6>
                                        <h2 class="mb-0"><?php echo $stats['critical'] ?? 0; ?></h2>
                                        <p class="mb-0">Need action NOW</p>
                                    </div>
                                    <div class="stat-icon">🔥</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Money Saved</h6>
                                        <h2 class="mb-0">रु <?php echo number_format($progress['money_saved'] ?? 0, 0); ?></h2>
                                        <p class="mb-0">This month</p>
                                    </div>
                                    <div class="stat-icon">💰</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Active Alerts -->
                <div class="card no-duplicates">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Active Alerts (No Duplicates)</h5>
                        <div>
                            <span class="badge bg-success me-2">1 alert per product</span>
                            <span class="badge bg-danger"><?php echo $stats['total'] ?? 0; ?> unique alerts</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($unique_alerts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Type</th>
                                            <th>Severity</th>
                                            <th>Action</th>
                                            <th>Stock</th>
                                            <th>Days Left</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($unique_alerts as $alert): 
                                            $days_left = $alert['days_remaining'];
                                            $is_expired = ($days_left !== null && $days_left <= 0);
                                            
                                            // Category icon
                                            $icons = [
                                                'dairy' => '🥛', 'bakery' => '🍞', 'fruits_veg' => '🍎',
                                                'beverages' => '🥤', 'groceries' => '🍚', 'snacks' => '🍫'
                                            ];
                                            $icon = $icons[$alert['category']] ?? '📦';
                                        ?>
                                        <tr class="alert-card <?php echo $is_expired ? 'expired' : $alert['severity']; ?>">
                                            <td>
                                                <strong><?php echo $icon . ' ' . htmlspecialchars($alert['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $is_expired ? 'dark' : 
                                                           ($alert['severity'] == 'critical' ? 'danger' : 
                                                           ($alert['severity'] == 'high' ? 'warning' : 'info'));
                                                ?>">
                                                    <?php echo $is_expired ? 'EXPIRED' : ucfirst($alert['severity']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($alert['suggested_action'] == 'discount'): ?>
                                                    <span class="badge bg-success">
                                                        Discount <?php echo $alert['discount_percent']; ?>%
                                                    </span>
                                                <?php elseif($alert['suggested_action'] == 'delete'): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">
                                                        <?php echo ucfirst($alert['suggested_action']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $alert['current_stock']; ?> units
                                                <?php if($alert['current_stock'] == 1): ?>
                                                    <span class="badge bg-danger">LAST ONE!</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($is_expired): ?>
                                                    <span class="badge bg-dark">
                                                        <i class="bi bi-exclamation-triangle"></i> EXPIRED
                                                    </span>
                                                <?php elseif($days_left > 0): ?>
                                                    <span class="badge bg-<?php echo $days_left <= 2 ? 'danger' : 'warning'; ?>">
                                                        <?php echo $days_left; ?> days
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if($is_expired): ?>
                                                        <!-- DELETE PRODUCT BUTTON (for expired items) -->
                                                        <a href="alerts.php?action=delete_product&product_id=<?php echo $alert['product_id']; ?>" 
                                                           class="btn btn-dark action-btn"
                                                           onclick="return confirm('⚠️ WARNING: This product has EXPIRED!\\n\\nDelete \"<?php echo htmlspecialchars($alert['name']); ?>\" permanently? This cannot be undone.')">
                                                            <i class="bi bi-trash"></i> Delete Product
                                                        </a>
                                                        
                                                    <?php else: ?>
                                                        <!-- Normal actions for non-expired items -->
                                                        <?php if($alert['suggested_action'] == 'discount'): ?>
                                                            <a href="alerts.php?action=apply_discount&product_id=<?php echo $alert['product_id']; ?>&percent=<?php echo $alert['discount_percent']; ?>" 
                                                               class="btn btn-success action-btn"
                                                               onclick="return confirm('Apply <?php echo $alert['discount_percent']; ?>% discount?\\nStock will be cleared if sold out.')">
                                                                <i class="bi bi-percent"></i> Apply
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Mark as Donated -->
                                                        <a href="alerts.php?action=mark_donation&product_id=<?php echo $alert['product_id']; ?>" 
                                                           class="btn btn-info action-btn"
                                                           onclick="return confirm('Mark as donated and remove from inventory?')">
                                                            <i class="bi bi-heart"></i> Donate
                                                        </a>
                                                        
                                                        <!-- Resolve Alert -->
                                                        <a href="alerts.php?action=resolve&id=<?php echo $alert['id']; ?>" 
                                                           class="btn btn-secondary action-btn"
                                                           onclick="return confirm('Mark as resolved? Product will be removed if stock is 0.')">
                                                            <i class="bi bi-check-circle"></i> Done
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Info Notice -->
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> 
                                • <span class="badge bg-dark">EXPIRED</span> items can only be deleted
                                • <span class="badge bg-danger">CRITICAL</span> items expire in ≤2 days
                                • <span class="badge bg-warning">HIGH</span> priority items expire in 3-7 days
                            </div>
                            
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-check-circle display-1 text-success"></i>
                                <h4 class="mt-3">No Active Alerts!</h4>
                                <p class="text-muted">All products are properly managed. Great job!</p>
                                <a href="alerts.php?action=generate_alerts" class="btn btn-primary mt-2">
                                    <i class="bi bi-arrow-clockwise"></i> Check for New Alerts
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-remove message after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if(alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                    alert.remove();
                }
            });
        }, 5000);
        
        // Confirm before removing last item
        document.addEventListener('click', function(e) {
            if(e.target.closest('.action-btn') && e.target.textContent.includes('LAST ONE!')) {
                if(!confirm("⚠️ This is the LAST item! Are you sure you want to continue?")) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>