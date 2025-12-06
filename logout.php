<?php
// logout.php
session_start();

// Store session data for the alert message if needed
$owner_name = $_SESSION['owner_name'] ?? 'User';

// Destroy all session data
session_unset();
session_destroy();

// Clear any session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Use HTML page with JavaScript for alert and redirect
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - WasteWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #198754, #20c997);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #198754;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner"></div>
        <h3 class="mb-3">Logging Out...</h3>
        <p class="text-muted">You have been successfully logged out.</p>
        <p class="text-muted">Redirecting to home page...</p>
    </div>

    <script>
        // Show alert and redirect
        alert('✅ You have been logged out successfully!\n\nThank you for using WasteWise.');
        
        // Redirect to index.php after 2 seconds
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2000);
    </script>
</body>
</html>