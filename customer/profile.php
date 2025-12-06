<?php
session_start();
require_once '../includes/config.php';

if(!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error = '';
$success = '';

// Get customer info
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Update profile if form submitted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Update basic info
    $update_stmt = $conn->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $full_name, $phone, $address, $customer_id);
    
    if($update_stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success .= "Profile updated successfully! ";
    } else {
        $error .= "Failed to update profile. ";
    }
    
    // Update password if provided
    if(!empty($current_password) && !empty($new_password)) {
        if(password_verify($current_password, $customer['password'])) {
            if($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
                $pass_stmt->bind_param("si", $hashed_password, $customer_id);
                
                if($pass_stmt->execute()) {
                    $success .= "Password changed successfully!";
                } else {
                    $error .= "Failed to change password. ";
                }
            } else {
                $error .= "New passwords do not match. ";
            }
        } else {
            $error .= "Current password is incorrect. ";
        }
    }
    
    // Refresh customer data
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - WasteWise Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #198754, #157347);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 50px;
            color: #198754;
            border: 5px solid white;
        }
        .nav-pills .nav-link.active {
            background-color: #198754;
        }
    </style>
</head>
<body>
    <!-- Navigation (same as dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i> WasteWise Shopping
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link me-3" href="cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2 fs-5"></i>
                        <span><?php echo $customer['full_name']; ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-history me-2"></i> Order History</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-4">
                <div class="card profile-card mb-4">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($customer['full_name']); ?></h4>
                        <p class="mb-0">Customer</p>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="#personal-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="fas fa-user-circle me-2"></i> Personal Information
                            </a>
                            <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-lock me-2"></i> Change Password
                            </a>
                            <a href="orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-history me-2"></i> Order History
                            </a>
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-store me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card profile-card">
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal-info">
                                <h4 class="mb-4"><i class="fas fa-user-circle me-2"></i> Personal Information</h4>
                                <form method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($customer['full_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Member Since</label>
                                            <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($customer['created_at'])); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="change-password">
                                <h4 class="mb-4"><i class="fas fa-lock me-2"></i> Change Password</h4>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password">
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-success">
                                        <i class="fas fa-key me-2"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate tab based on URL hash
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="list"]'))
            triggerTabList.forEach(function (triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl)
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault()
                    tabTrigger.show()
                })
            })
        });
    </script>
</body>
</html>