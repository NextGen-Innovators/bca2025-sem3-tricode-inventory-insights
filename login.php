<?php
require_once 'includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = '';
    
    // Try to login as storekeeper first
    $stmt = $conn->prepare("SELECT id, shop_name, owner_name, password FROM stores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $store = $result->fetch_assoc();
        if(password_verify($password, $store['password'])) {
            session_start();
            $_SESSION['store_id'] = $store['id'];
            $_SESSION['shop_name'] = $store['shop_name'];
            $_SESSION['owner_name'] = $store['owner_name'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'storekeeper';
            header("Location: store/dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        // Try to login as customer
        $stmt = $conn->prepare("SELECT id, full_name, password FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $customer = $result->fetch_assoc();
            if(password_verify($password, $customer['password'])) {
                session_start();
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['full_name'] = $customer['full_name'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
                header("Location: customer/dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #157347;
            --primary-blue: #0d6efd;
        }
        
        .login-container {
            max-width: 450px;
            margin: 80px auto;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .login-card {
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .role-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-green);
        }
        
        .role-info i {
            color: var(--primary-green);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-register {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .login-container {
                margin: 20px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--dark-green);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-recycle me-2"></i> WasteWise Nepal
            </a>
        </div>
    </nav>
    
    <div class="container login-container">
        <div class="login-header">
            <h4><i class="fas fa-sign-in-alt me-2"></i> Login to WasteWise Nepal</h4>
            <p class="mb-0">Access your account</p>
        </div>
        
        <div class="card login-card">
            <div class="card-body p-4">
                <div class="role-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Login with your registered email. Same login for both Storekeepers and Customers.</small>
                </div>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Your password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-login mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                    
                    <div class="d-grid">
                        <a href="register.php" class="btn btn-register">
                            <i class="fas fa-user-plus me-2"></i> Create New Account
                        </a>
                    </div>
                    
                    <div class="text-center mt-4">
                        <div class="row">
                            <div class="col-6">
                                <small><a href="#" class="text-decoration-none">Forgot Password?</a></small>
                            </div>
                            <div class="col-6 text-end">
                                <small><a href="index.php" class="text-decoration-none">Back to Home</a></small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>