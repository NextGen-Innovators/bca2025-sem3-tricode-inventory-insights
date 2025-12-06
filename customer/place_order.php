<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch stores for selection
$stores = [];
$sql = "SELECT id, shop_name, owner_name, address FROM stores";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $stores[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_id = $_POST['store_id'];
    $items = $_POST['items'];
    $total_amount = $_POST['total_amount'];
    $notes = $_POST['notes'] ?? '';
    
    $customer_id = $_SESSION['customer_id'];
    $customer_name = $_SESSION['full_name'];
    
    // Get customer details
    $sql = "SELECT phone, address FROM customers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    // Insert order
    $sql = "INSERT INTO orders (customer_id, store_id, items, total_amount, order_notes, customer_name, customer_phone, customer_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssss", $customer_id, $store_id, $items, $total_amount, $notes, $customer_name, $customer['phone'], $customer['address']);
    
    if ($stmt->execute()) {
        header("Location: order_success.php?id=" . $stmt->insert_id);
        exit();
    } else {
        $error = "Failed to place order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - WasteWise Nepal</title>
    <style>
        .order-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        select, textarea, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="order-container">
        <h2><i class="fas fa-cart-plus"></i> Place New Order</h2>
        
        <?php if (isset($error)): ?>
            <div style="background: #ffe6e6; color: #d32f2f; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="store_id"><i class="fas fa-store"></i> Select Store</label>
                <select name="store_id" id="store_id" required>
                    <option value="">-- Select a store --</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo $store['id']; ?>">
                            <?php echo htmlspecialchars($store['shop_name'] . ' - ' . $store['address']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="items"><i class="fas fa-shopping-basket"></i> Order Items</label>
                <textarea name="items" id="items" placeholder="Describe what you want to order..." required></textarea>
            </div>
            
            <div class="form-group">
                <label for="total_amount"><i class="fas fa-rupee-sign"></i> Total Amount (NPR)</label>
                <input type="number" name="total_amount" id="total_amount" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="notes"><i class="fas fa-sticky-note"></i> Additional Notes (Optional)</label>
                <textarea name="notes" id="notes" placeholder="Any special instructions..."></textarea>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Place Order
            </button>
        </form>
    </div>
</body>
</html>