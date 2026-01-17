<?php


require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metropolitan College - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1>🎓 Metropolitan College</h1>
            </div>
            <div class="nav-menu" id="navMenu">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="nav-link">Logout (<?php echo sanitizeOutput(getCurrentUsername()); ?>)</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link btn-primary">Register</a>
                <?php endif; ?>
                <button class="nav-toggle" id="navToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Welcome to Our University</h2>
                <p>Excellence in Education, Innovation in Research</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary btn-large">Get Started</a>
                        <a href="login.php" class="btn btn-secondary btn-large">Sign In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <h2>About Our Campus</h2>
            <div class="about-grid">
                <div class="about-card">
                    <div class="card-icon">📚</div>
                    <h3>Academic Excellence</h3>
                    <p>Our university offers world-class education with state-of-the-art facilities and renowned faculty members committed to student success.</p>
                </div>
                <div class="about-card">
                    <div class="card-icon">🔬</div>
                    <h3>Research & Innovation</h3>
                    <p>We are at the forefront of groundbreaking research across multiple disciplines, fostering innovation and discovery.</p>
                </div>
                <div class="about-card">
                    <div class="card-icon">🌍</div>
                    <h3>Global Community</h3>
                    <p>Join a diverse community of students and scholars from around the world in a vibrant learning environment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Campus Images -->
    <section class="campus-images">
        <div class="container">
            <h2>Campus Life</h2>
            <div class="image-grid">
                <div class="image-card">
                    <img src="pictures/campus1.jpg" alt="Campus Building">
                    <p>Main Campus Building</p>
                </div>
                <div class="image-card">
                    <img src="pictures/campus2.jpg" alt="Library">
                    <p>University Library</p>
                </div>
                <div class="image-card">
                    <img src="pictures/campus3.jpg" alt="Sports Center">
                    <p>Sports Center</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2>Find Us</h2>
            <p class="map-description">Visit our beautiful campus located in the heart of Marousi</p>
            <div id="map"></div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p>📧 info@metropolitan.edu.gr</p>
                    <p>📞 +30 210 619 9891</p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <p>Stay connected with our community</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025-2026 Metropolitan College. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/main.js"></script>
</body>
</html>