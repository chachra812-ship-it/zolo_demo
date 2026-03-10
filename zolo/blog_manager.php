<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

// Handle form submissions
$message = "";
$messageType = "";

// Create new post
if (isset($_POST['create_post'])) {
    $title = trim($_POST['title']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', str_replace(' ', '-', $title))));
    $excerpt = trim($_POST['excerpt']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $author_id = $_SESSION['user_id'];
    $author_name = $_SESSION['user_name'];
    
    // Handle featured image upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $uploadDir = 'uploads/blog/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
            $featured_image = $targetPath;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, excerpt, featured_image, category_id, author_id, author_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'published')");
    $stmt->bind_param("ssssiss", $title, $slug, $excerpt, $featured_image, $category_id, $author_id, $author_name);
    
    if ($stmt->execute()) {
        $post_id = $stmt->insert_id;
        $message = "Blog post created successfully!";
        $messageType = "success";
    } else {
        $message = "Error creating post!";
        $messageType = "error";
    }
}

// Delete post
if (isset($_GET['delete_post'])) {
    $post_id = intval($_GET['delete_post']);
    $conn->query("DELETE FROM blog_sections WHERE post_id = $post_id");
    $conn->query("DELETE FROM blog_posts WHERE id = $post_id");
    $message = "Post deleted successfully!";
    $messageType = "success";
}

// Add section to post
if (isset($_POST['add_section'])) {
    $post_id = intval($_POST['post_id']);
    $section_title = trim($_POST['section_title']);
    $section_content = trim($_POST['section_content']);
    
    // Get current max order
    $result = $conn->query("SELECT MAX(section_order) as max_order FROM blog_sections WHERE post_id = $post_id");
    $row = $result->fetch_assoc();
    $new_order = ($row['max_order'] ?? -1) + 1;
    
    // Handle section image
    $section_image = '';
    if (isset($_FILES['section_image']) && $_FILES['section_image']['error'] === 0) {
        $uploadDir = 'uploads/blog/sections/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['section_image']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['section_image']['tmp_name'], $targetPath)) {
            $section_image = $targetPath;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO blog_sections (post_id, section_title, section_content, section_image, section_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $post_id, $section_title, $section_content, $section_image, $new_order);
    $stmt->execute();
    
    $message = "Section added successfully!";
    $messageType = "success";
}

// Delete section
if (isset($_GET['delete_section'])) {
    $section_id = intval($_GET['delete_section']);
    $conn->query("DELETE FROM blog_sections WHERE id = $section_id");
    $message = "Section deleted successfully!";
    $messageType = "success";
}

// Get all posts
$posts = $conn->query("SELECT bp.*, bc.name as category_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.id ORDER BY bp.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM blog_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get single post with sections
$edit_post = null;
if (isset($_GET['edit_post'])) {
    $post_id = intval($_GET['edit_post']);
    $edit_post = $conn->query("SELECT * FROM blog_posts WHERE id = $post_id")->fetch_assoc();
    $edit_sections = $conn->query("SELECT * FROM blog_sections WHERE post_id = $post_id ORDER BY section_order")->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Manager | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .blog-manager-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
        }

        .blog-manager-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .blog-manager-header h1 {
            font-size: 1.8rem;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .tab-btn {
            padding: 12px 25px;
            background: transparent;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: #666;
            border-radius: 8px;
            transition: 0.3s;
        }

        .tab-btn.active {
            background: #667eea;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Form Styles */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .form-card h2 {
            color: #1a1a2e;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Posts List */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .post-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #eee;
        }

        .post-content {
            padding: 20px;
        }

        .post-category {
            background: #e7f3ff;
            color: #667eea;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 10px;
        }

        .post-title {
            color: #1a1a2e;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .post-excerpt {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .post-meta {
            color: #999;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .post-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .edit-btn {
            background: #667eea;
            color: white;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
        }

        /* Sections List */
        .sections-list {
            margin-top: 30px;
        }

        .section-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .section-item h4 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .section-item p {
            color: #666;
            margin-bottom: 10px;
        }

        .section-item img {
            max-width: 300px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .action-link {
            padding: 12px 25px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .action-link:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>

   <div class="top-bar-right">
    <span>👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="blog.php">Blog</a>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>
    <!-- Header -->
    <div class="blog-manager-header">
        <div class="container">
            <h1>📝 Blog Manager</h1>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">

        <!-- Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('all-posts')">📄 All Posts</button>
            <button class="tab-btn" onclick="showTab('create-post')">➕ Create New Post</button>
            <?php if ($edit_post): ?>
                <button class="tab-btn" onclick="showTab('edit-sections')">📝 Manage Sections</button>
            <?php endif; ?>
        </div>

        <!-- All Posts Tab -->
        <div id="all-posts" class="tab-content active">
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <?php if ($post['featured_image']): ?>
                            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                        <?php else: ?>
                            <div class="post-image" style="display: flex; align-items: center; justify-content: center; font-size: 3rem;">📷</div>
                        <?php endif; ?>
                        <div class="post-content">
                            <?php if ($post['category_name']): ?>
                                <span class="post-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                            <?php endif; ?>
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                                        <div class="post-meta">
                                By <?php echo htmlspecialchars($post['author_name']); ?> | 
                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </div>
                            <div class="post-actions">
                                <a href="blog_manager.php?edit_post=<?php echo $post['id']; ?>" class="action-btn edit-btn">✏️ Edit Sections</a>
                                <a href="blog_manager.php?delete_post=<?php echo $post['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this post?')">🗑️ Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($posts) === 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                        <p style="font-size: 3rem;">📝</p>
                        <h3>No posts yet</h3>
                        <p>Create your first blog post!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create New Post Tab -->
        <div id="create-post" class="tab-content">
            <div class="form-card">
                <h2>➕ Create New Blog Post</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Post Title</label>
                        <input type="text" name="title" required placeholder="Enter post title">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="featured_image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Excerpt / Short Description</label>
                        <textarea name="excerpt" placeholder="Write a short description..."></textarea>
                    </div>
                    
                    <button type="submit" name="create_post" class="submit-btn">Create Post</button>
                </form>
            </div>
        </div>

        <!-- Edit Sections Tab -->
        <?php if ($edit_post): ?>
        <div id="edit-sections" class="tab-content">
            <div class="form-card">
                <h2>📝 Manage Sections: <?php echo htmlspecialchars($edit_post['title']); ?></h2>
                
                <!-- Add New Section Form -->
                <form method="POST" enctype="multipart/form-data" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px;">➕ Add New Section</h3>
                    <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
                    
                    <div class="form-group">
                        <label>Section Title (Optional)</label>
                        <input type="text" name="section_title" placeholder="Enter section title">
                    </div>
                    
                    <div class="form-group">
                        <label>Section Content</label>
                        <textarea name="section_content" placeholder="Write section content..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Section Image (Optional)</label>
                        <input type="file" name="section_image" accept="image/*">
                    </div>
                    
                    <button type="submit" name="add_section" class="submit-btn">Add Section</button>
                </form>
                
                <!-- Existing Sections -->
                <h3 style="margin-bottom: 15px;">📋 Existing Sections</h3>
                <div class="sections-list">
                    <?php if (isset($edit_sections) && count($edit_sections) > 0): ?>
                        <?php foreach ($edit_sections as $section): ?>
                            <div class="section-item">
                                <?php if ($section['section_title']): ?>
                                    <h4><?php echo htmlspecialchars($section['section_title']); ?></h4>
                                <?php endif; ?>
                                <p><?php echo nl2br(htmlspecialchars($section['section_content'])); ?></p>
                                <?php if ($section['section_image']): ?>
                                    <img src="<?php echo $section['section_image']; ?>" alt="Section Image">
                                <?php endif; ?>
                                <div style="margin-top: 10px;">
                                    <a href="?edit_post=<?php echo $edit_post['id']; ?>&delete_section=<?php echo $section['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this section?')">🗑️ Delete Section</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666;">No sections added yet. Add your first section above!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script>
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>

</body>
</html>