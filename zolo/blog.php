<?php
session_start();

require_once 'config.php';

// Get all published posts
$posts = $conn->query("SELECT bp.*, bc.name as category_name 
    FROM blog_posts bp 
    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
    WHERE bp.status = 'published' 
    ORDER BY bp.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM blog_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Filter by category
$selected_category = $_GET['category'] ?? '';
if ($selected_category) {
    $posts = $conn->query("SELECT bp.*, bc.name as category_name 
        FROM blog_posts bp 
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
        WHERE bp.status = 'published' AND bc.slug = '$selected_category'
        ORDER BY bp.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .blog-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .blog-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .blog-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .blog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .blog-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 40px;
        }

        /* Sidebar */
        .blog-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .sidebar-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .sidebar-section h3 {
            color: #1a1a2e;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 10px;
        }

        .category-list a {
            color: #666;
            text-decoration: none;
            padding: 8px 15px;
            display: block;
            border-radius: 8px;
            transition: 0.3s;
        }

        .category-list a:hover,
        .category-list a.active {
            background: #667eea;
            color: white;
        }

        /* Blog Posts */
        .blog-posts {
            display: grid;
            gap: 30px;
        }

        .blog-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .blog-card-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: #eee;
        }

        .blog-card-body {
            padding: 30px;
        }

        .blog-card-category {
            background: #e7f3ff;
            color: #667eea;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 15px;
        }

        .blog-card-title {
            color: #1a1a2e;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .blog-card-excerpt {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .blog-card-meta {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .read-more-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
        }

        .read-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .blog-layout {
                grid-template-columns: 1fr;
            }
            .blog-sidebar {
                position: static;
            }
            .blog-card-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="blog_manager.php">📝 Blog Manager</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn-small">Login</a>
                    <a href="register.php" class="register-btn-small">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="blog-header">
        <h1>📰 Zolo Blog</h1>
        <p>Latest insights, tips and news from our digital marketing experts</p>
    </div>

    <!-- Main Container -->
    <div class="blog-container">
        <div class="blog-layout">
            <!-- Sidebar -->
            <div class="blog-sidebar">
                <div class="sidebar-section">
                    <h3>📂 Categories</h3>
                    <ul class="category-list">
                        <li><a href="blog.php" class="<?php echo !$selected_category ? 'active' : ''; ?>">All Posts</a></li>
                        <?php foreach ($categories as $cat): ?>
                            <li><a href="blog.php?category=<?php echo $cat['slug']; ?>" class="<?php echo $selected_category === $cat['slug'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Blog Posts -->
            <div class="blog-posts">
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="blog-card">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-card-image">
                            <?php endif; ?>
                            <div class="blog-card-body">
                                <?php if ($post['category_name']): ?>
                                    <span class="blog-card-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <?php endif; ?>
                                <h2 class="blog-card-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                                <div class="blog-card-meta">
                                    By <?php echo htmlspecialchars($post['author_name']); ?> | 
                                    <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                                </div>
                                <p class="blog-card-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <a href="blog_single.php?id=<?php echo $post['id']; ?>" class="read-more-btn">Read More →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <h3>No Posts Found</h3>
                        <p>Check back later for new content!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

</body>
</html>