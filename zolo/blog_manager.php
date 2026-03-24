<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

// ✅ FIXED: Fetch user data from database first
$user_query = $conn->prepare("SELECT plan FROM users WHERE id = ?");
$user_query->bind_param("i", $_SESSION['user_id']);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

$user_plan = $user['plan'] ?? 'none';
$_SESSION['user_plan'] = $user_plan; // Store in session for convenience

echo "<!-- DEBUG: User Plan = '$user_plan' -->"; // TEMP DEBUG

// ✅ FIXED Access Control Logic
$can_create = in_array($user_plan, ['pro', 'gold', 'premium']);
$can_edit_delete = in_array($user_plan, ['gold', 'premium']);

// Handle form submissions
$message = "";
$messageType = "";

// Create new post - Only if they have permission
if (isset($_POST['create_post']) && $can_create) {
    $title = trim($_POST['title']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', str_replace(' ', '-', $title))));
    $excerpt = trim($_POST['excerpt']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $author_id = $_SESSION['user_id'];
    $author_name = $_SESSION['user_name'] ?? 'Anonymous';
    
    // Handle featured image upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $uploadDir = 'uploads/blog/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
            $featured_image = $targetPath;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, excerpt, featured_image, category_id, author_id, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
    $stmt->bind_param("ssssis", $title, $slug, $excerpt, $featured_image, $category_id, $author_id);
    if ($stmt->execute()) {
        $message = "Blog post created successfully!";
        $messageType = "success";
    } else {
        $message = "Error creating post!";
        $messageType = "error";
    }
    $stmt->close();
}

// Delete post - Only if they have permission
if (isset($_GET['delete_post']) && $can_edit_delete) {
    $post_id = intval($_GET['delete_post']);
    $conn->query("DELETE FROM blog_sections WHERE post_id = $post_id");
    $conn->query("DELETE FROM blog_posts WHERE id = $post_id");
    $message = "Post deleted successfully!";
    $messageType = "success";
}

// Add section to post - Only if they have permission
if (isset($_POST['add_section']) && $can_edit_delete) {
    $post_id = intval($_POST['post_id']);
    $section_title = trim($_POST['section_title']);
    $section_content = trim($_POST['section_content']);
    
    // Get current max order
    $result = $conn->query("SELECT MAX(section_order) as max_order FROM blog_sections WHERE post_id = $post_id");
    $row = $result->fetch_assoc();
    $new_order = ($row['max_order'] ?? 0) + 1;
   
    // Handle section image
    $section_image = '';
    if (isset($_FILES['section_image']) && $_FILES['section_image']['error'] === 0) {
        $uploadDir = 'uploads/blog/sections/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
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
    $stmt->close();
}

// Delete section - Only if they have permission
if (isset($_GET['delete_section']) && $can_edit_delete) {
    $section_id = intval($_GET['delete_section']);
    $conn->query("DELETE FROM blog_sections WHERE id = $section_id");
    $message = "Section deleted successfully!";
    $messageType = "success";
}

// Get all posts
$posts_result = $conn->query("
    SELECT bp.*, bc.name as category_name,
           COALESCE(u.full_name, u.username, 'Anonymous') as author_name
    FROM blog_posts bp 
    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
    LEFT JOIN users u ON bp.author_id = u.id 
    ORDER BY bp.created_at DESC
");

$posts = [];
while ($row = $posts_result->fetch_assoc()) {
    $posts[] = $row;
}

// Get categories
$categories_result = $conn->query("SELECT * FROM blog_categories ORDER BY name");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get single post + sections (only if they can edit)
$edit_post = null;
$edit_sections = [];
if (isset($_GET['edit_post']) && $can_edit_delete) {
    $post_id = intval($_GET['edit_post']);
    
    $edit_post_result = $conn->query("
        SELECT bp.*, COALESCE(u.full_name, u.username) as author_name
        FROM blog_posts bp 
        LEFT JOIN users u ON bp.author_id = u.id 
        WHERE bp.id = $post_id
    ");
    $edit_post = $edit_post_result->fetch_assoc();
    
    $sections_result = $conn->query("SELECT * FROM blog_sections WHERE post_id = $post_id ORDER BY section_order ASC");
    while ($row = $sections_result->fetch_assoc()) {
        $edit_sections[] = $row;
    }
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
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .blog-manager-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 40px 0; 
            text-align: center; 
            margin-bottom: 40px;
        }
        .blog-manager-header .container { display: flex; justify-content: space-between; align-items: center; }
        .blog-manager-header h1 { font-size: 2.5rem; margin: 0; }
        .plan-badge {
            background: rgba(255,255,255,0.2); 
            padding: 8px 20px; 
            border-radius: 25px; 
            font-weight: 600;
            font-size: 1rem;
        }
        .back-btn { 
            background: rgba(255,255,255,0.2); 
            color: white; 
            padding: 12px 25px; 
            border-radius: 25px; 
            text-decoration: none; 
            font-weight: 600;
        }
        .back-btn:hover { background: rgba(255,255,255,0.3); }

        .tabs { 
            display: flex; 
            background: white; 
            border-radius: 15px; 
            padding: 5px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
            gap: 5px;
        }
        .tab-btn { 
            flex: 1; 
            padding: 15px 20px; 
            border: none; 
            background: #f8f9fa; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all 0.3s; 
            color: #666;
        }
        .tab-btn.active, .tab-btn:hover { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .posts-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 25px; 
        }
        .post-card { 
            background: white; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); 
            transition: transform 0.3s; 
        }
        .post-card:hover { transform: translateY(-10px); }
        .post-image { width: 100%; height: 200px; object-fit: cover; }
        .post-content { padding: 25px; }
        .post-category { 
            background: #667eea; 
            color: white; 
            padding: 5px 15px; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600; 
        }
        .post-title { 
            margin: 15px 0 10px 0; 
            color: #1a1a2e; 
            font-size: 1.4rem; 
        }
        .post-excerpt { color: #666; margin-bottom: 15px; }
        .post-meta { color: #999; font-size: 0.9rem; margin-bottom: 20px; }
        .post-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-btn { 
            padding: 10px 20px; 
            border-radius: 25px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.9rem; 
            display: inline-flex; 
            align-items: center;
            transition: all 0.3s;
        }
        .edit-btn { 
            background: #28a745; 
            color: white; 
        }
        .delete-btn { 
            background: #dc3545; 
            color: white; 
        }
        .action-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .disabled-btn { 
            background: #6c757d !important; 
            color: #aaa !important; 
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        .form-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); 
        }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            font-size: 1rem; 
            transition: border-color 0.3s; 
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            outline: none; 
            border-color: #667eea; 
        }
        .submit-btn { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 18px 40px; 
            border: none; 
            border-radius: 15px; 
            font-size: 1.1rem; 
            font-weight: 800; 
            cursor: pointer; 
            width: 100%; 
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.4); }
        .alert { 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 30px; 
            font-weight: 600; 
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .section-item { 
            background: #f8f9fa; 
            padding: 25px; 
            border-radius: 15px; 
            margin-bottom: 20px; 
            border-left: 5px solid #667eea; 
        }
        .sections-list { max-height: 600px; overflow-y: auto; }

        /* Plan Info Cards */
        .plan-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .plan-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .feature {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 12px;
        }
        .upgrade-cta {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1rem;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <div class="top-bar-right">
        <span>👤 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?> 
            <span class="plan-badge"><?php echo strtoupper($user_plan); ?></span>
        </span>
        <a href="index.php">🏠 Home</a>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="blog.php">📖 Blog</a>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
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
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Plan Info & Permissions -->
        <div class="plan-info">
            <h3>🎯 Your Plan: <strong><?php echo strtoupper($user_plan); ?></strong></h3>
            <div class="plan-features">
                <div class="feature">
                    <div style="font-size: 2rem;">📝</div>
                    <?php if ($can_create): ?>
                        ✅ Create Posts
                    <?php else: ?>
                        ❌ Create Posts
                    <?php endif; ?>
                </div>
                <div class="feature">
                    <div style="font-size: 2rem;">✏️</div>
                    <?php if ($can_edit_delete): ?>
                        ✅ Edit/Delete
                    <?php else: ?>
                        ❌ Edit/Delete
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$can_create): ?>
                <a href="subscription.php" class="upgrade-cta">🚀 Upgrade Now</a>
            <?php endif; ?>
        </div>

        <!-- Tabs - Dynamic based on permissions -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('all-posts')">📄 All Posts</button>
            <?php if ($can_create): ?>
                <button class="tab-btn" onclick="showTab('create-post')">➕ Create New</button>
            <?php endif; ?>
            <?php if ($can_edit_delete && $edit_post): ?>
                <button class="tab-btn" onclick="showTab('edit-sections')">✏️ Edit Sections</button>
            <?php endif; ?>
        </div>

        <!-- All Posts Tab - ALWAYS VISIBLE -->
        <div id="all-posts" class="tab-content active">
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <?php if ($post['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                        <?php else: ?>
                            <div class="post-image" style="display: flex; align-items: center; justify-content: center; font-size: 3rem; background: #eee;">📷</div>
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
                            
                            <!-- ✅ DYNAMIC ACTION BUTTONS BASED ON PLAN -->
                            <div class="post-actions">
                                <?php if ($can_edit_delete): ?>
                                    <a href="blog_manager.php?edit_post=<?php echo $post['id']; ?>" class="action-btn edit-btn">✏️ Edit Sections</a>
                                    <a href="blog_manager.php?delete_post=<?php echo $post['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this post and all sections?')">🗑️ Delete</a>
                                <?php else: ?>
                                    <span class="action-btn disabled-btn">👀 View Only</span>
                                    <?php if ($can_create): ?>
                                        <span style="color: #28a745; font-weight: 600; font-size: 0.9rem;">
                                            ✅ You can create new posts!
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($posts)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 20px;">📝</div>
                        <h3>No posts yet</h3>
                        <?php if ($can_create): ?>
                            <p style="font-size: 1.2rem;">Start creating your first blog post! 👆</p>
                        <?php else: ?>
                            <p style="font-size: 1.2rem;">Upgrade to PRO, Gold, or Premium to create posts!</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create New Post Tab - Only for PRO/GOLD/PREMIUM -->
        <?php if ($can_create): ?>
        <div id="create-post" class="tab-content">
            <div class="form-card">
                <h2>➕ Create New Blog Post</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Post Title *</label>
                        <input type="text" name="title" required placeholder="Enter post title">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">No Category</option>
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
                        <textarea name="excerpt" rows="4" placeholder="Write a short description (shows on blog list)..." required></textarea>
                    </div>
                    
                    <button type="submit" name="create_post" class="submit-btn">🚀 Create Post</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Sections Tab - Only for GOLD/PREMIUM -->
        <?php if ($can_edit_delete && $edit_post): ?>
        <div id="edit-sections" class="tab-content">
            <div class="form-card">
                <h2>📝 Manage Sections: <strong><?php echo htmlspecialchars($edit_post['title']); ?></strong></h2>
                
                <!-- Add New Section Form -->
                <form method="POST" enctype="multipart/form-data" style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; color: #1a1a2e;">➕ Add New Section</h3>
                    <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
                    
                    <div class="form-group">
                        <label>Section Title (Optional)</label>
                        <input type="text" name="section_title" placeholder="e.g. 'Introduction'">
                    </div>
                    
                    <div class="form-group">
                        <label>Section Content *</label>
                        <textarea name="section_content" rows="6" placeholder="Write detailed content..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Section Image (Optional)</label>
                        <input type="file" name="section_image" accept="image/*">
                    </div>
                    
                    <button type="submit" name="add_section" class="submit-btn">✅ Add Section</button>
                </form>
                
                <!-- Existing Sections -->
                <h3 style="margin-bottom: 20px;">📋 Current Sections (<?= count($edit_sections) ?>)</h3>
                <div class="sections-list">
                    <?php if (!empty($edit_sections)): ?>
                        <?php foreach ($edit_sections as $index => $section): ?>
                            <div class="section-item">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <?php if ($section['section_title']): ?>
                                        <h4><?php echo htmlspecialchars($section['section_title']); ?></h4>
                                    <?php endif; ?>
                                    <a href="?edit_post=<?php echo $edit_post['id']; ?>&delete_section=<?php echo $section['id']; ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Delete section #<?= $index+1 ?>?')" 
                                       style="font-size: 0.8rem; padding: 8px 16px;">
                                        🗑️ Delete
                                    </a>
                                </div>
                                <div style="margin: 15px 0; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #667eea;">
                                    <?php echo nl2br(htmlspecialchars($section['section_content'])); ?>
                                </div>
                                <?php if ($section['section_image']): ?>
                                    <img src="<?php echo htmlspecialchars($section['section_image']); ?>" alt="Section Image" style="max-width: 400px; border-radius: 8px; margin: 15px 0; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                <?php endif; ?>
                                <small style="color: #666;">Order: <?= $section['section_order'] ?> | ID: <?= $section['id'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="section-item" style="text-align: center; color: #666;">
                            <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                            <p>No sections yet. Add your first section above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- No Access Message for Edit Attempts -->
        <?php if (isset($_GET['edit_post']) && !$can_edit_delete): ?>
        <div style="background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; padding: 40px; border-radius: 20px; text-align: center; margin: 40px 0; border: 2px solid #dc3545;">
            <div style="font-size: 4rem; margin-bottom: 20px;">⚠️</div>
            <h2>Edit Access Required</h2>
            <p>You need <strong>GOLD or PREMIUM</strong> plan to edit/delete posts and sections.</p>
            <a href="subscription.php" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 1.1rem; display: inline-block; box-shadow: 0 10px 30px rgba(40,167,69,0.4);">🚀 Upgrade to GOLD</a>
        </div>
        <?php endif; ?>

    </div>

    <script>
    function showTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
            event.target.classList.add('active');
        }
    }

    // Auto-show edit tab if editing AND have permission
    <?php if (isset($_GET['edit_post']) && $can_edit_delete): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showTab('edit-sections');
    });
    <?php endif; ?>
    </script>

</body>
</html>