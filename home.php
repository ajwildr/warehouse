<?php
require './includes/db_connect.php';

// Fetch statistics
$stats = [];

// Total employees
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['employees'] = $result->fetch_assoc()['count'];

// Total products managed
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $result->fetch_assoc()['count'];

// Total suppliers
$result = $conn->query("SELECT COUNT(*) as count FROM suppliers");
$stats['suppliers'] = $result->fetch_assoc()['count'];

// Total stock transactions
$result = $conn->query("SELECT COUNT(*) as count FROM stock");
$stats['transactions'] = $result->fetch_assoc()['count'];

// Total stock handled
$result = $conn->query("SELECT SUM(quantity) as total FROM stock WHERE transaction_type = 'Incoming'");
$stats['total_stock'] = $result->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Leading Warehouse Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --text-color: #2c3e50;
            --text-light: #6c757d;
        }

        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }

        section {
            padding: 5rem 0;
        }

        /* Navigation */
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)),
                        url('https://images.unsplash.com/photo-1553413077-190dd305871c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 8rem 0 6rem;
            margin-top: 60px;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Features Section */
        .features {
            background-color: var(--light-bg);
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            margin: 1rem 0;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background: var(--secondary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
        }

        .timeline-item {
            padding: 2rem 0;
        }

        .timeline-content {
            position: relative;
            width: 45%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .timeline-content::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            background: var(--secondary-color);
            border-radius: 50%;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
        }

        .timeline-item:nth-child(odd) .timeline-content::after {
            left: -60px;
        }

        .timeline-item:nth-child(even) .timeline-content::after {
            right: -60px;
        }

        /* Testimonials */
        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 1rem 0;
        }

        .testimonial-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        /* Login Button */
        .login-btn {
            background-color: var(--accent-color);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            color: white;
        }

        /* Contact Section */
        .contact-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Footer */
        footer {
            background: var(--dark-bg);
            color: white;
            padding: 3rem 0;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .timeline::before {
                left: 31px;
            }

            .timeline-content {
                width: calc(100% - 80px);
                margin-left: 80px !important;
            }

            .timeline-item:nth-child(odd) .timeline-content::after,
            .timeline-item:nth-child(even) .timeline-content::after {
                left: -45px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 6rem 0 4rem;
            }

            .stats-card {
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-warehouse me-2"></i>CloudWare
            </a>
            <a href="pages/login.php" class="login-btn btn">Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Next-Generation Warehouse Management</h1>
            <p class="lead mb-4">Transforming warehouse operations with cutting-edge technology since 2010</p>
            <a href="pages/login.php" class="btn login-btn btn-lg">
                Get Started <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Impact in Numbers</h2>
                <p class="lead text-muted">Trusted by businesses worldwide</p>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users stats-icon me-3"></i>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['employees']); ?></div>
                                <div class="stats-label">Team Members</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-box stats-icon me-3"></i>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['products']); ?></div>
                                <div class="stats-label">Products</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-handshake stats-icon me-3"></i>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['suppliers']); ?></div>
                                <div class="stats-label">Partners</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exchange-alt stats-icon me-3"></i>
                            <div>
                                <div class="stats-number"><?php echo number_format($stats['transactions']); ?></div>
                                <div class="stats-label">Transactions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Why Choose CloudWare?</h2>
                <p class="lead text-muted">Industry-leading features that set us apart</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h3>Mobile First</h3>
                        <p>Access your warehouse data anywhere, anytime with our responsive mobile interface.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h3>Real-time Analytics</h3>
                        <p>Make data-driven decisions with our powerful analytics and reporting tools.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h3>Secure & Reliable</h3>
                        <p>Enterprise-grade security to protect your valuable business data.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company History -->
    <section>
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Journey</h2>
                <p class="lead text-muted">A decade of innovation and growth</p>
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4>2010 - The Beginning</h4>
                        <p>Founded with a vision to revolutionize warehouse management using cloud technology.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4>2015 - Global Expansion</h4>
                        <p>Expanded operations to 15 countries, serving over 1000 businesses worldwide.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4>2018 - Innovation Award</h4>
                        <p>Received the Global Innovation Award for our AI-powered inventory management system.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4>2024 - Industry Leader</h4>
                        <p>Now managing over 1 million transactions daily with 99.99% accuracy.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2>What Our Clients Say</h2>
                <p class="lead text-muted">Success stories from satisfied customers</p>
            </div>
            <div class="row">
            <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/1.jpg" alt="Jane Smith" class="testimonial-image">
                        <h4>Jane Smith</h4>
                        <p class="text-muted">Supply Chain Director, Global Logistics</p>
                        <p>"The mobile-first approach has allowed our team to work efficiently from anywhere. Outstanding system!"</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/2.jpg" alt="Mike Johnson" class="testimonial-image">
                        <h4>Mike Johnson</h4>
                        <p class="text-muted">CEO, Fast Delivery Inc</p>
                        <p>"CloudWare's analytics have given us insights that helped us reduce costs by 35%."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section>
        <div class="container">
            <div class="text-center mb-5">
                <h2>Get in Touch</h2>
                <p class="lead text-muted">Have questions? We're here to help</p>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="contact-info">
                        <h3><i class="fas fa-map-marker-alt me-2"></i>Visit Us</h3>
                        <p>123 Business Avenue<br>Tech District, Silicon Valley<br>CA 94025, USA</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="contact-info">
                        <h3><i class="fas fa-envelope me-2"></i>Contact Us</h3>
                        <p>Email: info@cloudware.com<br>Phone: +1 (555) 123-4567<br>Support: 24/7 Available</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4><i class="fas fa-warehouse me-2"></i>CloudWare</h4>
                    <p>Next-generation warehouse management system powering businesses worldwide.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h4>Quick Links</h4>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Features</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Pricing</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> CloudWare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>