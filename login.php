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
    <style>
        body { background: #f5f5f5; font-family: Arial; }
        .login-box {
            width: 400px; margin: 100px auto; background: white;
            padding: 30px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #28a745; color: white; padding: 10px; border: none; width: 100%; cursor: pointer; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>

        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required value="<?php echo $_POST['email'] ?? ''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p style="margin-top: 20px;">
            <a href="register.php">Register new account</a> | 
            <a href="reset_password.php">Forgot password?</a>
        </p>
    </div>
</body>
</html>
