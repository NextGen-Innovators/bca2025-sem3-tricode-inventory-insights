<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as customer
if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get customer info
$customer_id = $_SESSION['customer_id'];
$customer_stmt = $conn->prepare("SELECT full_name, email, phone, address FROM customers WHERE id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();

// Get search/filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;

// SIMPLE QUERY - NO COMPLEX PAGINATION FOR NOW
$query = "SELECT p.*, s.shop_name, s.owner_name, 
          CASE 
            WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 
            THEN p.discount_price 
            ELSE p.selling_price 
          END as final_price,
          ROUND(((p.selling_price - COALESCE(p.discount_price, p.selling_price)) / p.selling_price * 100), 0) as discount_percent
          FROM products p 
          JOIN stores s ON p.store_id = s.id 
          WHERE p.status = 'active' AND p.current_stock > 0";

$params = [];
$types = "";

// Add filters one by one
if(!empty($search)) {
    $query .= " AND (p.name LIKE ? OR s.shop_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if(!empty($category) && $category != 'all') {
    $query .= " AND p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if(!empty($store_id)) {
    $query .= " AND p.store_id = ?";
    $params[] = $store_id;
    $types .= "i";
}

// Always add price range
$query .= " AND (CASE 
          WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 
          THEN p.discount_price 
          ELSE p.selling_price 
        END) BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

$query .= " ORDER BY p.added_date DESC";

// DEBUG: Uncomment to see the query
// echo "<!-- Query: " . htmlspecialchars($query) . " -->";
// echo "<!-- Params: " . print_r($params, true) . " -->";

// Prepare and execute query
$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
$total_products = $products_result->num_rows;

// Get all stores for filter dropdown
$stores = $conn->query("SELECT id, shop_name FROM stores ORDER BY shop_name");

// Get unique categories
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE status = 'active' ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #157347;
            --light-green: #d1e7dd;
            --light-blue: #e3f2fd;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-customer {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 180px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-green);
        }
        
        .badge-discount {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .store-badge {
            background: var(--light-blue);
            color: #0d6efd;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        .price-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .price-final {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .expiry-badge {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .btn-add-cart {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .btn-add-cart:hover {
            background: var(--dark-green);
            transform: scale(1.05);
        }
        
        .category-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-customer">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-shopping-bag me-2"></i> WasteWise Shopping
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2 fs-5"></i>
                        <span><?php echo $customer['full_name']; ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="cart.php"><i class="fas fa-shopping-cart me-2"></i> My Cart</a></li>
                        <li><a class="dropdown-item" href="order_confirmation.php"><i class="fas fa-history me-2"></i> Order History</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold">Welcome, <?php echo $customer['full_name']; ?>!</h1>
                    <p class="mb-0">Shop fresh products from local stores and help reduce food waste</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-4 py-2 d-inline-block">
                        <i class="fas fa-map-marker-alt text-success me-2"></i>
                        <span class="text-dark"><?php echo $customer['address'] ?: 'Kathmandu, Nepal'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-card">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Search products, stores..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="all" <?php echo ($category == 'all' || empty($category)) ? 'selected' : ''; ?>>All Categories</option>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category']; ?>" <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords(str_replace('_', ' ', $cat['category'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" name="store_id">
                                <option value="">All Stores</option>
                                <?php while($store = $stores->fetch_assoc()): ?>
                                    <option value="<?php echo $store['id']; ?>" <?php echo ($store_id == $store['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($store['shop_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Price Range (Rs.)</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>" min="0">
                                </div>
                                <div class="col-auto align-self-center">to</div>
                                <div class="col">
                                    <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo $max_price; ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Products Count -->
        <div class="row mb-3">
            <div class="col-12">
                <h4>Available Products 
                    <span class="badge bg-success"><?php echo $total_products; ?> items</span>
                </h4>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="row">
            <?php if($products_result->num_rows > 0): ?>
                <?php 
                $category_icons = [
                    'dairy' => 'fas fa-cheese',
                    'bakery' => 'fas fa-bread-slice',
                    'fruits_veg' => 'fas fa-apple-alt',
                    'beverages' => 'fas fa-wine-bottle',
                    'groceries' => 'fas fa-shopping-basket',
                    'snacks' => 'fas fa-cookie',
                    'personal_care' => 'fas fa-soap',
                    'stationery' => 'fas fa-pen',
                    'other' => 'fas fa-box'
                ];
                ?>
                
                <?php while($product = $products_result->fetch_assoc()): 
                    $has_discount = !empty($product['discount_price']) && $product['discount_price'] > 0;
                    $final_price = $has_discount ? $product['discount_price'] : $product['selling_price'];
                    
                    // Calculate days until expiry
                    $today = new DateTime();
                    $expiry = new DateTime($product['expiry_date']);
                    $days_remaining = $today->diff($expiry)->days;
                    $is_expiring_soon = $days_remaining <= 3;
                    
                    // Category icon
                    $cat_icon = $category_icons[$product['category']] ?? 'fas fa-box';
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card">
                        <!-- Discount Badge -->
                        <?php if($has_discount): ?>
                            <div class="badge-discount">
                                <?php echo $product['discount_percent']; ?>% OFF
                            </div>
                        <?php endif; ?>
                        
                        <!-- Product Image/Icon -->
                        <div class="product-image">
                            <i class="<?php echo $cat_icon; ?>"></i>
                        </div>
                        
                        <div class="p-3">
                            <!-- Store Info -->
                            <div class="store-badge">
                                <i class="fas fa-store me-1"></i> <?php echo htmlspecialchars($product['shop_name']); ?>
                            </div>
                            
                            <!-- Product Name -->
                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h6>
                            
                            <!-- Category -->
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-tag me-1"></i> <?php echo ucwords(str_replace('_', ' ', $product['category'])); ?>
                            </small>
                            
                            <!-- Stock Info -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-box me-1"></i> Stock: <?php echo $product['current_stock']; ?>
                                </small>
                                <?php if($product['current_stock'] < 10): ?>
                                    <span class="badge bg-warning">Low Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Expiry Date -->
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i> Expires: <?php echo date('M d, Y', strtotime($product['expiry_date'])); ?>
                                </small>
                                <?php if($is_expiring_soon): ?>
                                    <span class="expiry-badge ms-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Expiring soon!
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Price -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <?php if($has_discount): ?>
                                        <small class="price-original me-2">Rs. <?php echo number_format($product['selling_price'], 2); ?></small>
                                    <?php endif; ?>
                                    <span class="price-final">Rs. <?php echo number_format($final_price, 2); ?></span>
                                </div>
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try changing your search filters</p>
                        <a href="?" class="btn btn-success">Clear All Filters</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>WasteWise Nepal</h5>
                    <p>Helping reduce retail waste by connecting customers with discounted products.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; <?php echo date('Y'); ?> WasteWise Nepal. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ADD TO CART FUNCTION FOR DASHBOARD
    function addToCart(productId) {
        // Show loading state on the button
        const button = event.target.closest('.btn-add-cart') || event.target;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // AJAX call to add product to cart
        fetch('../ajax/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            // Restore button
            button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            button.disabled = false;
            
            // Show notification
            if(data.success) {
                showToast('✅ ' + data.message, 'success');
                updateCartCount(data.cart_count || 0);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            button.disabled = false;
            showToast('❌ Network error. Please try again.', 'error');
        });
    }
    
    // Toast notification function
    function showToast(message, type) {
        // Create toast container if doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast show align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="document.getElementById('${toastId}').remove()"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (document.getElementById(toastId)) {
                document.getElementById(toastId).remove();
            }
        }, 3000);
    }
    
    // Update cart count in navbar
    function updateCartCount(count) {
        let cartBadge = document.getElementById('cart-badge');
        if (!cartBadge) {
            // Create cart badge if doesn't exist
            cartBadge = document.createElement('span');
            cartBadge.id = 'cart-badge';
            cartBadge.className = 'badge bg-danger rounded-pill';
            cartBadge.style.marginLeft = '5px';
            
            // Try to find cart link in dropdown
            const cartLink = document.querySelector('a[href="cart.php"]');
            if (cartLink) {
                cartLink.appendChild(cartBadge);
            }
        }
        
        if (cartBadge) {
            if (count > 0) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'inline-block';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }
    
    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we have cart items in session
        fetch('../ajax/get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    updateCartCount(data.cart_count || 0);
                }
            })
            .catch(error => {
                console.error('Error getting cart count:', error);
            });
    });
</script>
</body>
</html>