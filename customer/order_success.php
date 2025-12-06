<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

$order_id = $_GET['id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed Successfully - WasteWise Nepal</title>
    <style>
        .success-container {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Order Placed Successfully! 🎉</h2>
        <p>Your order #<?php echo $order_id; ?> has been received by the store.</p>
        <p>The store will review and accept your order shortly.</p>
        
        <div class="btn-group">
            <a href="track_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                <i class="fas fa-map-marker-alt"></i> Track Order
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>