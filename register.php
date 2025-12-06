<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $shop_name = trim($_POST['shop_name']);
    $owner_name = trim($_POST['owner_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($shop_name)) {
        $errors[] = "Shop name is required";
    }
    
    if (empty($owner_name)) {
        $errors[] = "Owner name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_sql = "SELECT id FROM stores WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $errors[] = "Email already registered. Please login instead.";
        }
    }
    
    // If no errors, register the store
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // For demo, using md5 (simpler for hackathon)
        $hashed_password = md5($password);
        
        $sql = "INSERT INTO stores (shop_name, owner_name, email, phone, address, password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $shop_name, $owner_name, $email, $phone, $address, $hashed_password);
        
        if ($stmt->execute()) {
            // Get the new store ID
            $store_id = $stmt->insert_id;
            
            // Create session
            $_SESSION['store_id'] = $store_id;
            $_SESSION['shop_name'] = $shop_name;
            $_SESSION['owner_name'] = $owner_name;
            $_SESSION['email'] = $email;
            
            $success = "Registration successful! Redirecting to dashboard...";
            
            // Add some sample products for the new store
            addSampleProducts($conn, $store_id);
            
            // Redirect after 2 seconds
            header("refresh:2;url=store/dashboard.php");
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Function to add sample products for new store
function addSampleProducts($conn, $store_id) {
    $sample_products = [
        ['Amul Milk 500ml', 'dairy', 40, 60, 25, date('Y-m-d', strtotime('+3 days'))],
        ['Sunrise Bread', 'bakery', 25, 40, 15, date('Y-m-d', strtotime('+2 days'))],
        ['Wai Wai Noodles', 'snacks', 20, 30, 50, date('Y-m-d', strtotime('+180 days'))],
        ['Coca Cola 500ml', 'beverages', 35, 60, 30, date('Y-m-d', strtotime('+365 days'))],
        ['Chakra Rice 5kg', 'groceries', 400, 550, 10, date('Y-m-d', strtotime('+365 days'))],
        ['Local Bananas (1kg)', 'fruits_veg', 50, 80, 20, date('Y-m-d', strtotime('+5 days'))],
        ['Lux Soap', 'personal_care', 30, 45, 40, NULL],
        ['Classmate Copy', 'stationery', 50, 70, 100, NULL]
    ];
    
    $sql = "INSERT INTO products (store_id, name, category, buying_price, selling_price, current_stock, expiry_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_products as $product) {
        $stmt->bind_param("issddds", $store_id, $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Store - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #157347;
            --light-green: #d1e7dd;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            max-width: 600px;
            margin: 30px auto;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.1;
        }
        
        .register-header h2 {
            position: relative;
            z-index: 1;
            font-weight: 700;
        }
        
        .register-header p {
            position: relative;
            z-index: 1;
            opacity: 0.9;
        }
        
        .register-card {
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        
        .input-group-text {
            background-color: var(--light-green);
            border: 2px solid #e9ecef;
            border-right: none;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        .login-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .benefits-list {
            background-color: var(--light-green);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .benefits-list h6 {
            color: var(--dark-green);
            margin-bottom: 15px;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .benefit-icon {
            color: var(--primary-green);
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .password-strength {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            border-radius: 3px;
        }
        
        .password-strength-weak {
            background-color: #dc3545;
            width: 33%;
        }
        
        .password-strength-medium {
            background-color: #ffc107;
            width: 66%;
        }
        
        .password-strength-strong {
            background-color: #198754;
            width: 100%;
        }
   
        
        @media (max-width: 768px) {
            .register-container {
                margin: 10px;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--dark-green);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-recycle me-2"></i> WasteWise Nepal
            </a>
            <div class="navbar-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="container register-container">
        <div class="register-header">
            <h2><i class="fas fa-store me-2"></i> Register Your Store</h2>
            <p>Join our mission to reduce retail waste in Nepal</p>
        </div>
        
        <div class="card register-card">
            <div class="card-body">
              
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registrationForm">
                    <h5 class="mb-4 text-success"><i class="fas fa-store-alt me-2"></i> Store Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shop Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-store"></i></span>
                                <input type="text" class="form-control" name="shop_name" 
                                       placeholder="e.g., Kathmandu Kirana Store" 
                                       value="<?php echo isset($_POST['shop_name']) ? htmlspecialchars($_POST['shop_name']) : ''; ?>" 
                                       required>
                            </div>
                            <small class="text-muted">Your business/store name</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="owner_name" 
                                       placeholder="Your full name"
                                       value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>" 
                                       required>
                            </div>
                            <small class="text-muted">Name of the store owner</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="store@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                            <small class="text-muted">We'll never share your email</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="phone" 
                                       placeholder="98XXXXXXXX"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                       required>
                            </div>
                            <small class="text-muted">For order notifications</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Store Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" name="address" rows="2" 
                                      placeholder="Enter your store address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                        <small class="text-muted">Optional: For donation pickups</small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-4 text-success"><i class="fas fa-lock me-2"></i> Account Security</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="password" 
                                       id="password" placeholder="Minimum 6 characters" 
                                       required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="passwordStrength"></div>
                            </div>
                            <small class="text-muted" id="passwordHint">Enter a strong password</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="confirm_password" 
                                       id="confirmPassword" placeholder="Re-enter password" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i> Register Store
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
                
                <!-- Benefits Section -->
                <div class="benefits-list">
                    <h6><i class="fas fa-gift me-2"></i> Benefits of Joining WasteWise:</h6>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fas fa-chart-line"></i></span>
                        <span>Reduce inventory waste by up to 40%</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fas fa-hand-holding-heart"></i></span>
                        <span>Connect with charities for donations</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fas fa-bullhorn"></i></span>
                        <span>Smart alerts for expiring products</span>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon"><i class="fas fa-chart-pie"></i></span>
                        <span>Detailed analytics and reports</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>By registering, you agree to use WasteWise Nepal for legitimate business purposes only.</p>
                    <p>You are responsible for maintaining the accuracy of your inventory data.</p>
                    <p>We provide tools to reduce waste, but ultimate responsibility lies with the store owner.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>We store your store information securely and never share it with third parties.</p>
                    <p>Inventory data is used only for waste reduction suggestions.</p>
                    <p>You can delete your account and data at any time.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-4">
        <div class="container text-center">
            <p class="mb-0">♻️ WasteWise Nepal - Smart Retail Waste Reduction Platform</p>
            <small>Team Tricode - NextGen Innovators Club Hackathon</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            
            if (confirmInput.type === 'password') {
                confirmInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const hint = document.getElementById('passwordHint');
            
            // Reset
            strengthBar.className = 'password-strength-bar';
            hint.textContent = 'Enter a strong password';
            hint.className = 'text-muted';
            
            if (password.length === 0) {
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            
            // Contains numbers
            if (/\d/.test(password)) strength++;
            
            // Contains special characters
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            // Contains both uppercase and lowercase
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            // Update UI
            if (strength <= 1) {
                strengthBar.classList.add('password-strength-weak');
                hint.textContent = 'Weak password';
                hint.className = 'text-danger';
            } else if (strength <= 3) {
                strengthBar.classList.add('password-strength-medium');
                hint.textContent = 'Medium strength password';
                hint.className = 'text-warning';
            } else {
                strengthBar.classList.add('password-strength-strong');
                hint.textContent = 'Strong password!';
                hint.className = 'text-success';
            }
        });
        
        // Password match checker
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Passwords do not match</span>';
            }
        });
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions!');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registering...';
            submitBtn.disabled = true;
        });
        
        // Auto-format phone number
        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substring(0, 10);
            e.target.value = value;
        });
    </script>
</body>
</html>