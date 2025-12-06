<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WasteWise Nepal - Smart Retail Waste Reduction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(rgba(25, 135, 84, 0.9), rgba(21, 115, 71, 0.95));
            min-height: 100vh;
            color: white;
        }
        .tagline {
            font-size: 1.5rem;
            margin: 20px 0 40px;
        }
        .btn-custom {
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #157347;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                ♻️ WasteWise Nepal
            </a>
        </div>
    </nav>

    <section class="hero d-flex align-items-center">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-4">Welcome to WasteWise Nepal</h1>
            <p class="tagline">Reduce waste, increase profit. Smart inventory management for Nepali retailers.</p>
            
            <div class="row justify-content-center mb-5">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body p-5">
                            <h4 class="mb-4">Smart Waste Reduction Platform</h4>
                            <p>Predict unsold inventory and suggest discounts, donations, or repurposing.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5>🎯 Smart Discounts</h5>
                                        <small>Auto-apply discounts on expiring items</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5>❤️ Charity Donations</h5>
                                        <small>Connect with NGOs for donations</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded">
                                        <h5>🔄 Creative Repurposing</h5>
                                        <small>Recipe ideas for soon-to-expire items</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="login.php" class="btn btn-light btn-custom">Login</a>
                <a href="register.php" class="btn btn-outline-light btn-custom">Register </a>
            </div>
            
            
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>Team Tricode - NextGen Innovators Club Hackathon</p>
        </div>
    </footer>
</body>
</html>