<?php
require_once 'includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, shop_name, owner_name FROM stores WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $store = $result->fetch_assoc();
        $_SESSION['store_id'] = $store['id'];
        $_SESSION['shop_name'] = $store['shop_name'];
        $_SESSION['owner_name'] = $store['owner_name'];
        header("Location: store/dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .login-header {
            background: #198754;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-container">
            <div class="login-header text-center">
                <h4>♻️ WasteWise Nepal</h4>
                <p class="mb-0">Store Owner Login</p>
            </div>
            
            <div class="card shadow">
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small>New store? <a href="register.php">Register here</a></small><br>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>