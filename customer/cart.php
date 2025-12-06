<?php
// customer/cart.php
session_start();
require_once '../includes/config.php';

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total_amount = 0;

if(!empty($cart)) {
    $product_ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $query = "SELECT p.*, s.shop_name FROM products p 
              JOIN stores s ON p.store_id = s.id 
              WHERE p.id IN ($placeholders)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $products = $stmt->get_result();
    
    while($product = $products->fetch_assoc()) {
        $quantity = $cart[$product['id']];
        $price = !empty($product['discount_price']) ? $product['discount_price'] : $product['selling_price'];
        $subtotal = $price * $quantity;
        $total_amount += $subtotal;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-item {
            border-bottom: 1px solid #dee2e6;
            padding: 20px 0;
        }
        .product-image-small {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #198754;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #0d6efd;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i> WasteWise Shopping
            </a>
        </div>
    </nav>
    
    <div class="container py-4">
        <h2><i class="fas fa-shopping-cart me-2"></i> Shopping Cart</h2>
        
        <?php if(empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4>Your cart is empty</h4>
                <p class="text-muted">Add some products to get started!</p>
                <a href="dashboard.php" class="btn btn-success">
                    <i class="fas fa-store me-1"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach($cart_items as $item): 
                        $product = $item['product'];
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
                        $cat_icon = $category_icons[$product['category']] ?? 'fas fa-box';
                    ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="product-image-small">
                                    <i class="<?php echo $cat_icon; ?>"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted">Store: <?php echo htmlspecialchars($product['shop_name']); ?></small>
                            </div>
                            <div class="col-md-2">
                                <p class="mb-0">Rs. <?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $product['id']; ?>, -1)">-</button>
                                    <input type="number" class="form-control text-center" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $product['current_stock']; ?>" id="qty-<?php echo $product['id']; ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $product['id']; ?>, 1)">+</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <p class="mb-0 fw-bold">Rs. <?php echo number_format($item['subtotal'], 2); ?></p>
                                <button class="btn btn-sm btn-danger mt-1" onclick="removeFromCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Items (<?php echo array_sum($cart); ?>)</span>
                                <span>Rs. <?php echo number_format($total_amount, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery</span>
                                <span>Rs. 50.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total</span>
                                <span>Rs. <?php echo number_format($total_amount + 50, 2); ?></span>
                            </div>
                            <button class="btn btn-success w-100 mt-3" onclick="checkout()">
                                <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-store me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
<script>
    // ADD TO CART FUNCTION
    function addToCart(productId) {
        console.log('Adding product ID:', productId); // Debug log
        
        // Show loading state
        const button = event.target.closest('.btn-add-cart') || event.target;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Make AJAX request
        fetch('../ajax/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=1'
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug
            // Restore button
            button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            button.disabled = false;
            
            if(data.success) {
                showToast('✅ ' + data.message, 'success');
                updateCartCount(data.cart_count || 0);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error details:', error); // Debug
            button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            button.disabled = false;
            showToast('❌ Network error: ' + error.message, 'error');
        });
    }
    
    // Toast notification
    function showToast(message, type) {
        // Simple alert for now
        alert(message);
    }
    
    // Update cart count
    function updateCartCount(count) {
        let cartBadge = document.getElementById('cart-badge');
        if (!cartBadge) {
            cartBadge = document.createElement('span');
            cartBadge.id = 'cart-badge';
            cartBadge.className = 'badge bg-danger rounded-pill';
            cartBadge.style.marginLeft = '5px';
            
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
</script>
</body>
</html>
