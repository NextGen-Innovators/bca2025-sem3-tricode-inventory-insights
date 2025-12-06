<?php
// This is just a landing page showing two registration options
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #157347;
            --light-green: #d1e7dd;
            --primary-blue: #0d6efd;
            --dark-blue: #0b5ed7;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            max-width: 900px;
            margin: 50px auto;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .options-container {
            padding: 40px;
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .option-card {
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .storekeeper-card {
            border-color: var(--primary-green);
        }
        
        .buyer-card {
            border-color: var(--primary-blue);
        }
        
        .storekeeper-icon {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
        }
        
        .buyer-icon {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
        }
        
        .btn-storekeeper {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-storekeeper:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        
        .btn-buyer {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-buyer:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        
        .feature-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 25px;
        }
        
        .feature-list li {
            padding: 5px 0;
            padding-left: 25px;
            position: relative;
        }
        
        .feature-list li i {
            position: absolute;
            left: 0;
            top: 8px;
        }
        
        .storekeeper-features i {
            color: var(--primary-green);
        }
        
        .buyer-features i {
            color: var(--primary-blue);
        }
        
        @media (max-width: 768px) {
            .register-container {
                margin: 20px;
            }
            .options-container {
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
            <div class="navbar-nav ms-auto">
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <!-- Registration Options -->
    <div class="container register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus me-2"></i> Create Your Account</h2>
            <p>Choose your role to get started</p>
        </div>
        
        <div class="options-container">
            <div class="row g-4">
                <!-- Storekeeper Option -->
                <div class="col-md-6">
                    <div class="card option-card storekeeper-card h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="storekeeper-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <h3 class="card-title">Storekeeper</h3>
                                <p class="text-muted">Retail Store Owner</p>
                            </div>
                            
                            <ul class="feature-list storekeeper-features">
                                <li><i class="fas fa-check-circle me-2"></i> Manage store inventory</li>
                                <li><i class="fas fa-check-circle me-2"></i> List near-expiry products</li>
                                <li><i class="fas fa-check-circle me-2"></i> Track sales analytics</li>
                                <li><i class="fas fa-check-circle me-2"></i> Reduce waste & losses</li>
                                <li><i class="fas fa-check-circle me-2"></i> Connect with customers</li>
                            </ul>
                            
                            <div class="d-grid mt-4">
                                <a href="store/register.php" class="btn btn-storekeeper">
                                    <i class="fas fa-store me-2"></i> Register as Storekeeper
                                </a>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small>Already have a store account? <a href="login.php">Login here</a></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer/Buyer Option -->
                <div class="col-md-6">
                    <div class="card option-card buyer-card h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="buyer-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h3 class="card-title">Customer/Buyer</h3>
                                <p class="text-muted">Shopper & Conscious Consumer</p>
                            </div>
                            
                            <ul class="feature-list buyer-features">
                                <li><i class="fas fa-check-circle me-2"></i> Find discounted products</li>
                                <li><i class="fas fa-check-circle me-2"></i> Support local stores</li>
                                <li><i class="fas fa-check-circle me-2"></i> Save money on groceries</li>
                                <li><i class="fas fa-check-circle me-2"></i> Help reduce food waste</li>
                                <li><i class="fas fa-check-circle me-2"></i> Get personalized deals</li>
                            </ul>
                            
                            <div class="d-grid mt-4">
                                <a href="customer/register.php" class="btn btn-buyer">
                                    <i class="fas fa-user me-2"></i> Register as Customer
                                </a>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small>Already have a customer account? <a href="login.php">Login here</a></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 pt-3 border-top">
                <p class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    Both roles use the same login page. Choose the role that best describes you.
                </p>
                <a href="login.php" class="btn btn-outline-success">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login Page
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>