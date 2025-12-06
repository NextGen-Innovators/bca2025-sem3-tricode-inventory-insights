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
    
    // Validation array
    $errors = [];
    
    // Shop Name Validation: Only letters, spaces, and basic punctuation
    if (empty($shop_name)) {
        $errors[] = "Shop name is required";
    } elseif (!preg_match('/^[a-zA-Z\s\-\.\&\',]+$/u', $shop_name)) {
        $errors[] = "Shop name can only contain letters, spaces, hyphens, dots, ampersands, and apostrophes";
    } elseif (strlen($shop_name) < 3) {
        $errors[] = "Shop name must be at least 3 characters";
    }
    
    // Owner Name Validation: Only letters and spaces
    if (empty($owner_name)) {
        $errors[] = "Owner name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/u', $owner_name)) {
        $errors[] = "Owner name can only contain letters and spaces";
    } elseif (strlen($owner_name) < 3) {
        $errors[] = "Owner name must be at least 3 characters";
    }
    
    // Email Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Phone Number Validation: Must start with 97 or 98 and be 10 digits
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^(97|98)[0-9]{8}$/', $phone)) {
        $errors[] = "Phone number must start with 97 or 98 and be 10 digits total (e.g., 9812345678)";
    }
    
    // Password Validation: Must include capital, small letter, symbol, and number
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&* etc.)";
    }
    
    // Confirm Password
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
        
        $sql = "INSERT INTO stores (shop_name, owner_name, email, phone, address, password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $shop_name, $owner_name, $email, $phone, $address, $hashed_password);
        
        if ($stmt->execute()) {
            // Get the new store ID
            $store_id = $stmt->insert_id;
            
            // Create session
            session_start();
            $_SESSION['store_id'] = $store_id;
            $_SESSION['shop_name'] = $shop_name;
            $_SESSION['owner_name'] = $owner_name;
            $_SESSION['email'] = $email;
            
            $success = "Registration successful! Redirecting to dashboard...";
            
            // Redirect after 2 seconds
            header("refresh:2;url=store/dashboard.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
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
            max-width: 700px;
            margin: 30px auto;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
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
        }
        
        .form-control {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        
        .input-group-text {
            background-color: var(--light-green);
            border: 2px solid #dee2e6;
            border-right: none;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        
        .validation-rules {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid var(--primary-green);
        }
        
        .validation-rules h6 {
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        
        .rule-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .rule-item i {
            margin-right: 10px;
            width: 20px;
        }
        
        .rule-valid {
            color: var(--primary-green);
        }
        
        .rule-invalid {
            color: #dc3545;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-meter {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.3s;
        }
        
        .strength-0 { width: 0%; background-color: #dc3545; }
        .strength-1 { width: 25%; background-color: #dc3545; }
        .strength-2 { width: 50%; background-color: #ffc107; }
        .strength-3 { width: 75%; background-color: #17a2b8; }
        .strength-4 { width: 100%; background-color: #28a745; }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }
        
        .valid-message {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registrationForm" novalidate>
                    <h5 class="mb-4 text-success"><i class="fas fa-store-alt me-2"></i> Store Information</h5>
                    
                    <!-- Shop Name -->
                    <div class="mb-3">
                        <label class="form-label">Shop Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-store"></i></span>
                            <input type="text" class="form-control" name="shop_name" id="shop_name"
                                   placeholder="e.g., Kathmandu Kirana Store" 
                                   value="<?php echo isset($_POST['shop_name']) ? htmlspecialchars($_POST['shop_name']) : ''; ?>"
                                   required>
                        </div>
                        <div class="error-message" id="shop_name_error"></div>
                        <div class="valid-message" id="shop_name_valid">✓ Valid shop name</div>
                        <small class="text-muted">Letters, spaces, hyphens, dots, &, ' only</small>
                    </div>
                    
                    <!-- Owner Name -->
                    <div class="mb-3">
                        <label class="form-label">Owner Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="owner_name" id="owner_name"
                                   placeholder="Your full name"
                                   value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>"
                                   required>
                        </div>
                        <div class="error-message" id="owner_name_error"></div>
                        <div class="valid-message" id="owner_name_valid">✓ Valid owner name</div>
                        <small class="text-muted">Letters and spaces only</small>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" id="email"
                                   placeholder="store@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>
                        <div class="error-message" id="email_error"></div>
                        <div class="valid-message" id="email_valid">✓ Valid email format</div>
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <div class="input-group">
                            <span class="input-group-text">+977</span>
                            <input type="tel" class="form-control" name="phone" id="phone"
                                   placeholder="9812345678"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   maxlength="10"
                                   required>
                        </div>
                        <div class="error-message" id="phone_error"></div>
                        <div class="valid-message" id="phone_valid">✓ Valid phone number</div>
                        <small class="text-muted">Must start with 97 or 98 (10 digits total)</small>
                    </div>
                    
                    <!-- Address -->
                    <div class="mb-3">
                        <label class="form-label">Store Address</label>
                        <textarea class="form-control" name="address" rows="2" 
                                  placeholder="Enter your store address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-4 text-success"><i class="fas fa-lock me-2"></i> Account Security</h5>
                    
                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" name="password" id="password"
                                   placeholder="Create a strong password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Password Strength Meter -->
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <small id="strengthText" class="text-muted"></small>
                        </div>
                        
                        <!-- Password Rules -->
                        <div class="validation-rules mt-3">
                            <h6>Password must contain:</h6>
                            <div class="rule-item" id="rule-length">
                                <i class="fas fa-circle" id="rule-length-icon"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="rule-item" id="rule-uppercase">
                                <i class="fas fa-circle" id="rule-uppercase-icon"></i>
                                <span>At least one uppercase letter (A-Z)</span>
                            </div>
                            <div class="rule-item" id="rule-lowercase">
                                <i class="fas fa-circle" id="rule-lowercase-icon"></i>
                                <span>At least one lowercase letter (a-z)</span>
                            </div>
                            <div class="rule-item" id="rule-number">
                                <i class="fas fa-circle" id="rule-number-icon"></i>
                                <span>At least one number (0-9)</span>
                            </div>
                            <div class="rule-item" id="rule-special">
                                <i class="fas fa-circle" id="rule-special-icon"></i>
                                <span>At least one special character (!@#$%^&* etc.)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label">Confirm Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                                   placeholder="Re-enter your password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="confirm_password_error"></div>
                        <div class="valid-message" id="confirm_password_valid">✓ Passwords match</div>
                    </div>
                    
                    <!-- Terms -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a>
                        </label>
                        <div class="error-message" id="terms_error"></div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-register" id="submitBtn">
                        <i class="fas fa-user-plus me-2"></i> Register Store
                    </button>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
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
                    <p>By registering, you agree to use WasteWise Nepal for legitimate business purposes.</p>
                    <p>You are responsible for maintaining accurate inventory data.</p>
                    <p>We provide tools to reduce waste, but ultimate responsibility lies with the store owner.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmInput.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        // Validation functions
        function validateShopName() {
            const shopName = document.getElementById('shop_name').value.trim();
            const errorEl = document.getElementById('shop_name_error');
            const validEl = document.getElementById('shop_name_valid');
            
            if (shopName.length === 0) {
                errorEl.textContent = 'Shop name is required';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (!/^[a-zA-Z\s\-\.\&\',]+$/u.test(shopName)) {
                errorEl.textContent = 'Only letters, spaces, hyphens, dots, &, \' allowed';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (shopName.length < 3) {
                errorEl.textContent = 'Must be at least 3 characters';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            errorEl.style.display = 'none';
            validEl.style.display = 'block';
            return true;
        }
        
        function validateOwnerName() {
            const ownerName = document.getElementById('owner_name').value.trim();
            const errorEl = document.getElementById('owner_name_error');
            const validEl = document.getElementById('owner_name_valid');
            
            if (ownerName.length === 0) {
                errorEl.textContent = 'Owner name is required';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (!/^[a-zA-Z\s]+$/u.test(ownerName)) {
                errorEl.textContent = 'Only letters and spaces allowed';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (ownerName.length < 3) {
                errorEl.textContent = 'Must be at least 3 characters';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            errorEl.style.display = 'none';
            validEl.style.display = 'block';
            return true;
        }
        
        function validateEmail() {
            const email = document.getElementById('email').value.trim();
            const errorEl = document.getElementById('email_error');
            const validEl = document.getElementById('email_valid');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length === 0) {
                errorEl.textContent = 'Email is required';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (!emailRegex.test(email)) {
                errorEl.textContent = 'Please enter a valid email address';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            errorEl.style.display = 'none';
            validEl.style.display = 'block';
            return true;
        }
        
        function validatePhone() {
            const phone = document.getElementById('phone').value.trim();
            const errorEl = document.getElementById('phone_error');
            const validEl = document.getElementById('phone_valid');
            const phoneRegex = /^(97|98)[0-9]{8}$/;
            
            if (phone.length === 0) {
                errorEl.textContent = 'Phone number is required';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (!phoneRegex.test(phone)) {
                errorEl.textContent = 'Must start with 97 or 98 and be 10 digits';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            errorEl.style.display = 'none';
            validEl.style.display = 'block';
            return true;
        }
        
        function validatePassword() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            const rules = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            // Update rule icons
            Object.keys(rules).forEach(rule => {
                const icon = document.getElementById(`rule-${rule}-icon`);
                const ruleEl = document.getElementById(`rule-${rule}`);
                if (rules[rule]) {
                    icon.className = 'fas fa-check-circle rule-valid';
                    ruleEl.classList.add('rule-valid');
                    ruleEl.classList.remove('rule-invalid');
                    strength++;
                } else {
                    icon.className = 'fas fa-times-circle rule-invalid';
                    ruleEl.classList.add('rule-invalid');
                    ruleEl.classList.remove('rule-valid');
                }
            });
            
            // Update strength meter
            strengthFill.className = `strength-fill strength-${strength}`;
            
            // Update strength text
            const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColors = ['text-danger', 'text-danger', 'text-warning', 'text-info', 'text-success'];
            strengthText.textContent = strengthLabels[strength];
            strengthText.className = strengthColors[strength];
            
            return strength === 5; // All rules must pass
        }
        
        function validateConfirmPassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorEl = document.getElementById('confirm_password_error');
            const validEl = document.getElementById('confirm_password_valid');
            
            if (confirmPassword.length === 0) {
                errorEl.textContent = 'Please confirm your password';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            if (password !== confirmPassword) {
                errorEl.textContent = 'Passwords do not match';
                errorEl.style.display = 'block';
                validEl.style.display = 'none';
                return false;
            }
            
            errorEl.style.display = 'none';
            validEl.style.display = 'block';
            return true;
        }
        
        function validateTerms() {
            const terms = document.getElementById('terms').checked;
            const errorEl = document.getElementById('terms_error');
            
            if (!terms) {
                errorEl.textContent = 'You must agree to the terms';
                errorEl.style.display = 'block';
                return false;
            }
            
            errorEl.style.display = 'none';
            return true;
        }
        
        // Real-time validation
        document.getElementById('shop_name').addEventListener('input', validateShopName);
        document.getElementById('shop_name').addEventListener('blur', validateShopName);
        
        document.getElementById('owner_name').addEventListener('input', validateOwnerName);
        document.getElementById('owner_name').addEventListener('blur', validateOwnerName);
        
        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('email').addEventListener('blur', validateEmail);
        
        document.getElementById('phone').addEventListener('input', function(e) {
            // Auto-format: only numbers, max 10 digits
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
            validatePhone();
        });
        
        document.getElementById('phone').addEventListener('blur', validatePhone);
        
        document.getElementById('password').addEventListener('input', function() {
            validatePassword();
            // Also validate confirm password when password changes
            if (document.getElementById('confirm_password').value.length > 0) {
                validateConfirmPassword();
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', validateConfirmPassword);
        
        document.getElementById('terms').addEventListener('change', validateTerms);
        
        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const validations = [
                validateShopName(),
                validateOwnerName(),
                validateEmail(),
                validatePhone(),
                validatePassword(),
                validateConfirmPassword(),
                validateTerms()
            ];
            
            const allValid = validations.every(v => v === true);
            
            if (allValid) {
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registering...';
                submitBtn.disabled = true;
                
                // Submit form
                this.submit();
            } else {
                // Scroll to first error
                const firstError = document.querySelector('.error-message[style*="display: block"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Initialize validation on page load for pre-filled values
        document.addEventListener('DOMContentLoaded', function() {
            validateShopName();
            validateOwnerName();
            validateEmail();
            validatePhone();
            validatePassword();
            validateConfirmPassword();
        });
    </script>
</body>
</html>