<?php
session_start();
require_once '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['full_name'] ?? 'Customer';
$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total_amount = 0;
$shipping_fee = 50;

// Load cart items
if(!empty($cart)) {
    $product_ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $query = "SELECT p.*, s.shop_name, s.id as store_id FROM products p 
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

// Handle checkout form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cash_on_delivery';
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];

    if(empty($shipping_address)) {
        $errors[] = "Please enter delivery address";
    }

    if(empty($cart_items)) {
        $errors[] = "Your cart is empty";
    }

    if(empty($errors)) {
        $store_id = $cart_items[0]['product']['store_id'];
        $final_total = $total_amount + $shipping_fee;

        $conn->begin_transaction();

        try {
            $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, store_id, total_amount, shipping_address, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $order_stmt->bind_param("iidsss", $customer_id, $store_id, $final_total, $shipping_address, $payment_method, $notes);
            $order_stmt->execute();
            $order_id = $conn->insert_id;

            foreach($cart_items as $item) {
                $product = $item['product'];

                if($product['current_stock'] < $item['quantity']) {
                    throw new Exception("Not enough stock for " . $product['name']);
                }

                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $item_stmt->bind_param("iisidd", $order_id, $product['id'], $product['name'], $item['quantity'], $item['price'], $item['subtotal']);
                $item_stmt->execute();

                $update_stmt = $conn->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
                $update_stmt->bind_param("ii", $item['quantity'], $product['id']);
                $update_stmt->execute();
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            $_SESSION['last_order_id'] = $order_id;

            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit();

        } catch(Exception $e) {
            $conn->rollback();
            $error = "Order failed: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
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
    .cart-item { border-bottom: 1px solid #dee2e6; padding: 20px 0; }
    .product-image-small { width: 80px; height: 80px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #198754; }
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
                        <div class="product-image-small"><i class="<?php echo $cat_icon; ?>"></i></div>
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
                        <span>Rs. <?php echo number_format($shipping_fee, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span>Rs. <?php echo number_format($total_amount + $shipping_fee, 2); ?></span>
                    </div>

                    <form method="POST" action="cart.php" class="mt-3">
                        <input type="hidden" name="checkout" value="1">
                        <div class="mb-2">
                            <label for="shipping_address" class="form-label">Delivery Address</label>
                            <textarea name="shipping_address" id="shipping_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-2">
                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash_on_delivery" selected>Cash on Delivery</option>
                                <option value="online_payment">Online Payment</option>
                            </select>
                        </div>

                        <?php if(isset($error) && $error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            <i class="fas fa-credit-card me-2"></i> Confirm Order
                        </button>
                    </form>

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
// Placeholder functions for quantity and remove (AJAX can be added later)
function updateQuantity(id, change) {
    let qtyInput = document.getElementById('qty-' + id);
    let newQty = parseInt(qtyInput.value) + change;
    if(newQty >= 1) { qtyInput.value = newQty; }
}

function removeFromCart(id) {
    if(confirm('Remove this item from cart?')) {
        // Remove logic (can be AJAX or form submit)
        alert('Item removed. Implement server-side removal.');
    }
}
</script>
</body>
</html>
