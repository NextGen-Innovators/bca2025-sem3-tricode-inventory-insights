<?php
session_start();

// If already logged in, redirect
if(isset($_SESSION['store_id'])) {
    header("Location: store/dashboard.php");
    exit();
}
if(isset($_SESSION['customer_id'])) {
    header("Location: customer/dashboard.php");
    exit();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'wastewise_nepal');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || empty($password)) {
        $error = "Please enter both email and password!";
    } else {

        // -------------------------
        // TRY STORE LOGIN FIRST
        // -------------------------
        $stmt = $conn->prepare("SELECT id, shop_name, owner_name, password FROM stores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $storeResult = $stmt->get_result();

        if($storeResult->num_rows === 1) {
            $store = $storeResult->fetch_assoc();

            // Accept BOTH plain text + hashed passwords
            if ($store['password'] === $password || password_verify($password, $store['password'])) {

                // SET SESSION
                $_SESSION['store_id'] = $store['id'];
                $_SESSION['shop_name'] = $store['shop_name'];
                $_SESSION['owner_name'] = $store['owner_name'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'storekeeper';

                header("Location: store/dashboard.php");
                exit();
            } else {
                $error = "Invalid password for store account!";
            }
        } else {

            // -------------------------
            // TRY CUSTOMER LOGIN
            // -------------------------
            $stmt = $conn->prepare("SELECT id, full_name, password FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $custResult = $stmt->get_result();

            if($custResult->num_rows === 1) {
                $customer = $custResult->fetch_assoc();

                // Accept BOTH plain text + hashed passwords
                if ($customer['password'] === $password || password_verify($password, $customer['password'])) {

                    // SET SESSION
                    $_SESSION['customer_id'] = $customer['id'];
                    $_SESSION['full_name'] = $customer['full_name'];
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'customer';

                    header("Location: customer/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid password for customer account!";
                }
            } else {
                $error = "No account found with this email!";
            }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            width: 900px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-section {
            flex: 1;
            background: linear-gradient(to bottom right, #28a745, #1e7e34);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .welcome-section p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .features {
            list-style: none;
            margin-top: 20px;
        }
        
        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        
        .features i {
            margin-right: 12px;
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-section {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-section h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .login-section .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-with-icon input:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .login-btn {
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: linear-gradient(to right, #218838, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(40, 167, 69, 0.2);
        }
        
        .error-message {
            background: #ffe6e6;
            color: #d32f2f;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #d32f2f;
            font-size: 0.95rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 0.95rem;
        }
        
        .register-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #1e7e34;
            text-decoration: underline;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 2.2rem;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 100%;
                max-width: 500px;
            }
            
            .welcome-section, .login-section {
                padding: 40px 30px;
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left welcome section -->
        <div class="welcome-section">
            <div class="logo">
                <i class="fas fa-recycle"></i>
                WasteWise Nepal
            </div>
            <h1>Welcome Back!</h1>
            <p>Sign in to your account to manage waste effectively and contribute to a cleaner Nepal.</p>
            
            <ul class="features">
                <li><i class="fas fa-check"></i> Manage waste collection schedules</li>
                <li><i class="fas fa-check"></i> Track your recycling points</li>
                <li><i class="fas fa-check"></i> Connect with local stores</li>
                <li><i class="fas fa-check"></i> Eco-friendly community</li>
            </ul>
        </div>
        
        <!-- Right login form section -->
        <div class="login-section">
            <h2>Sign In</h2>
            <p class="subtitle">Enter your credentials to access your account</p>
            
            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Account
                </button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    
    <script>
        // Simple form validation
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                event.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>