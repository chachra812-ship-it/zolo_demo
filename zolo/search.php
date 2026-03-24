<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Zolo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <a href="login.php" class="login-btn-small">Login</a>
                <a href="register.php" class="register-btn-small">Register</a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Zolo<span>.</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#blog">Blog</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" style="padding: 50px 20px;">
        <h1>Search Results</h1>
        <p>Find what you are looking for</p>
        
        <!-- Search Bar -->
        <form action="search.php" method="GET" class="search-container">
            <input type="text" name="q" placeholder="Search services, blog posts..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" required>
            <button type="submit">Search</button>
        </form>
    </section>

    <!-- Main Content -->
    <div class="container">
        <div class="search-results">
            <?php
            $conn = new mysqli("localhost", "root", "", "zolo_db");
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            
            $query = $_GET['q'] ?? '';
            
            if (empty($query)) {
                echo '<div style="text-align: center; padding: 50px;">';
                echo '<h2 style="color: #1a1a2e;">Please enter a search term</h2>';
                echo '<a href="index.php" class="back-btn" style="margin-top: 20px;">← Back to Home</a>';
                echo '</div>';
                $conn->close();
                exit;
            }
            
            $searchTerm = "%$query%";
            $resultsCount = 0;
            
            // Search in Services
            echo '<h2 style="margin-bottom: 20px; color: #1a1a2e;">Services</h2>';
            $stmt = $conn->prepare("SELECT id, slug, title, short_description FROM services WHERE title LIKE ? OR short_description LIKE ?");
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $services = $stmt->get_result();
            
            if ($services->num_rows > 0) {
                while ($row = $services->fetch_assoc()) {
                    $resultsCount++;
                    echo '<div class="search-result-item">';
                    echo '<h3><a href="service.php?slug=' . $row['slug'] . '">' . $row['title'] . '</a></h3>';
                    echo '<p>' . $row['short_description'] . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: #666;">No services found.</p>';
            }
            
            // Search in Blog Posts
            echo '<h2 style="margin: 30px 0 20px; color: #1a1a2e;">Blog Posts</h2>';
            $stmt = $conn->prepare("SELECT id, title, excerpt FROM blog_posts WHERE title LIKE ? OR excerpt LIKE ?");
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $posts = $stmt->get_result();
            
            if ($posts->num_rows > 0) {
                while ($row = $posts->fetch_assoc()) {
                    $resultsCount++;
                    echo '<div class="search-result-item">';
                    echo '<h3><a href="#">' . $row['title'] . '</a></h3>';
                    echo '<p>' . $row['excerpt'] . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: #666;">No blog posts found.</p>';
            }
            
            if ($resultsCount === 0) {
                echo '<div style="text-align: center; padding: 50px;">';
                echo '<h2 style="color: #1a1a2e;">No Results Found</h2>';
                echo '<p>Try searching with different keywords.</p>';
                echo '</div>';
            }
            
            echo '<div style="text-align: center; margin-top: 30px;">';
            echo '<a href="index.php" class="back-btn">← Back to Home</a>';
            echo '</div>';
            
            $conn->close();
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

</body>
</html>