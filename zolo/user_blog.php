<?php
session_start();
require_once 'config.php';
require_once 'permission.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['username'] ?? 'User';
$userPlan = strtolower($_SESSION['user_plan'] ?? 'free');

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    // CREATE NEW POST
    if (isset($_POST['create_post'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $excerpt = trim($_POST['excerpt']);
        
        if (!empty($title) && !empty($content)) {
            $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($title));
            
            // Image upload
            $featured_image = '';
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
                $uploadDir = 'uploads/blogs/' . $user_id . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $target = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
                        $featured_image = $target;
                    }
                }
            }
            
            $is_published = isset($_POST['is_published']) ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO blogs (title, slug, content, excerpt, image, author_id, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssii", $title, $slug, $content, $excerpt, $featured_image, $user_id, $is_published);
            
            if ($stmt->execute()) {
                $message = "✅ Blog post created successfully!";
                $messageType = "success";
            } else {
                $message = "❌ Failed to create post!";
                $messageType = "error";
            }
            $stmt->close();
        }
    }
    
    // UPDATE POST
    if (isset($_POST['update_post'])) {
        $post_id = intval($_POST['post_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $excerpt = trim($_POST['excerpt']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        $featured_image = $_POST['current_image'] ?? '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $uploadDir = 'uploads/blogs/' . $user_id . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
                    $featured_image = $target;
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE blogs SET title=?, content=?, excerpt=?, image=?, is_published=?, updated_at=NOW() WHERE id=? AND author_id=?");
        $stmt->bind_param("ssssiii", $title, $content, $excerpt, $featured_image, $is_published, $post_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "✅ Blog post updated successfully!";
            $messageType = "success";
        }
        $stmt->close();
    }
    
    // DELETE POST
    if (isset($_POST['delete_post'])) {
        $post_id = intval($_POST['post_id']);
        $conn->query("DELETE FROM blogs WHERE id=$post_id AND author_id=$user_id");
        $message = "✅ Blog post deleted!";
        $messageType = "success";
    }
    
    // PUBLISH/UNPUBLISH
    if (isset($_POST['toggle_publish'])) {
        $post_id = intval($_POST['post_id']);
        $is_published = intval($_POST['is_published']);
        $stmt = $conn->prepare("UPDATE blogs SET is_published=?, updated_at=NOW() WHERE id=? AND author_id=?");
        $stmt->bind_param("iii", $is_published, $post_id, $user_id);
        $stmt->execute();
        $message = $is_published ? "✅ Post published!" : "✅ Post set to draft!";
        $messageType = "success";
        $stmt->close();
    }
}

// Get user's posts
$stmt = $conn->prepare("SELECT * FROM blogs WHERE author_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Blog Dashboard | Zolo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 1.1rem; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
        
        .main-content { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        
        .alert { padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; border-left: 5px solid; }
        .alert-success { background: #d4edda; color: #155724; border-color: #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        
        .section { margin-bottom: 2rem; }
        .section h2 { color: #1a1a2e; margin-bottom: 1rem; font-size: 1.5rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem; }
        
        .blog-form { background: #f8f9fa; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; 
            font-size: 1rem; transition: all 0.3s; font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; 
               text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 1rem; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        .btn-danger { background: linear-gradient(135deg, #e74c3c 0%, #dc3545 100%); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .post-card { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 5px 20px rgba(0,0,0,0.08); border-left: 4px solid #667eea; }
        .post-title { font-size: 1.3rem; margin-bottom: 0.5rem; color: #1a1a2e; }
        .post-meta { color: #666; font-size: 0.9rem; margin-bottom: 1rem; }
        .post-preview { color: #555; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem; max-height: 60px; overflow: hidden; }
        .post-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        
        .sidebar { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); height: fit-content; }
        
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; gap: 1.5rem; }
            .header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✍️ My Blog Dashboard</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($userName); ?></strong> | Plan: <strong><?php echo ucfirst($userPlan); ?></strong></p>
        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">← Back to Dashboard</a>
        <a href="logout.php" class="btn" style="background: #6c757d; margin-left: 1rem;">Logout</a>
    </div>

    <div class="container">
        <!-- Main Content -->
        <div class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Quick Post Form -->
            <div class="section">
                <h2>📝 Quick Post</h2>
                <form method="POST" enctype="multipart/form-data" class="blog-form">
                    <div class="form-group">
                        <label>Blog Title *</label>
                        <input type="text" name="title" placeholder="Enter your blog title..." required maxlength="200">
                    </div>
                    
                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="featured_image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Short Description</label>
                        <textarea name="excerpt" placeholder="Write a short intro (optional)..." maxlength="300"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Blog Content *</label>
                        <textarea name="content" placeholder="Write your blog post here..." required rows="8"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                            <input type="checkbox" name="is_published" checked> 
                            <span>Publish immediately</span>
                        </label>
                    </div>
                    
                    <button type="submit" name="create_post" class="btn btn-success">🚀 Publish Post</button>
                </form>
            </div>

            <!-- User's Posts -->
            <div class="section">
                <h2>📊 My Posts (<?php echo count($user_posts); ?>)</h2>
                <?php if (empty($user_posts)): ?>
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
                        <h3>No posts yet</h3>
                        <p>Create your first blog post above!</p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($user_posts as $post): ?>
                            <div class="post-card">
                                <?php if ($post['image']): ?>
                                    <img src="<?php echo htmlspecialchars($post['image']); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">
                                <?php endif; ?>
                                
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                
                                <div class="post-meta">
                                    <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?> 
                                    | Views: <?php echo $post['views'] ?? 0; ?>
                                </div>
                                
                                <span class="status-badge status-<?php echo $post['is_published'] ? 'published' : 'draft'; ?>">
                                    <?php echo $post['is_published'] ? 'Published' : 'Draft'; ?>
                                </span>
                                
                                <p class="post-preview">
                                    <?php echo htmlspecialchars(substr($post['excerpt'] ?: $post['content'], 0, 100)); ?>...
                                </p>
                                
                                <div class="post-actions">
                                    <a href="#edit-<?php echo $post['id']; ?>" class="btn" style="background: #17a2b8; color: white; padding: 8px 16px; font-size: 0.9rem;">✏️ Edit</a>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Publish this post?')">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="hidden" name="is_published" value="<?php echo $post['is_published'] ? 0 : 1; ?>">
                                        <button type="submit" name="toggle_publish" class="btn" style="background: <?php echo $post['is_published'] ? '#ffc107' : '#28a745'; ?>; color: white; padding: 8px 16px; font-size: 0.9rem;">
                                            <?php echo $post['is_published'] ? 'Unpublish' : 'Publish'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this post?')">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" name="delete_post" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.9rem;">🗑️ Delete</button>
                                    </form>
                                </div>
                                
                                <!-- Edit Form (Hidden) -->
                                <div id="edit-<?php echo $post['id']; ?>" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; display: none;">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($post['image']); ?>">
                                        
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" style="font-size: 1.1rem; font-weight: 600;" required>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <textarea name="excerpt" rows="2" style="font-size: 0.9rem;"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <textarea name="content" rows="4" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                        </div>
                                        
                                                                               <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                                            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                                                <input type="checkbox" name="is_published" value="1" <?php echo $post['is_published'] ? 'checked' : ''; ?>> 
                                                Publish
                                            </label>
                                            <input type="file" name="featured_image" accept="image/*" style="font-size: 0.9rem;">
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="submit" name="update_post" class="btn btn-primary" style="padding: 8px 16px;">💾 Update</button>
                                            <a href="#" onclick="this.parentElement.parentElement.parentElement.style.display='none'; return false;" style="color: #666; text-decoration: none;">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <h2>📈 Quick Stats</h2>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: #667eea;">📝</div>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1a1a2e; margin-bottom: 0.5rem;">
                    <?php echo count($user_posts); ?>
                </div>
                <div>Total Posts</div>
                
                <?php 
                $published = array_filter($user_posts, fn($p) => $p['is_published']);
                $drafts = count($user_posts) - count($published);
                ?>
                <div style="margin-top: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Published:</span> <span style="font-weight: 600; color: #28a745;"><?php echo count($published); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Drafts:</span> <span style="font-weight: 600; color: #ffc107;"><?php echo $drafts; ?></span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <h3 style="color: #1a1a2e; margin-bottom: 1rem;">💡 Tips</h3>
                <ul style="color: #666; line-height: 1.6; font-size: 0.95rem;">
                    <li>• Use compelling titles (under 60 chars)</li>
                    <li>• Add a featured image</li>
                    <li>• Write engaging first paragraph</li>
                    <li>• Publish regularly for better reach</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Toggle edit forms
        document.querySelectorAll('.post-card a[href^="#edit-"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.getAttribute('href').replace('#edit-', '');
                const editForm = document.getElementById('edit-' + postId);
                editForm.style.display = editForm.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Auto-save draft preview
        document.querySelectorAll('textarea[name="content"]').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const preview = this.closest('.post-card')?.querySelector('.post-preview');
                if (preview) {
                    preview.textContent = this.value.substring(0, 100) + '...';
                }
            });
        });
    </script>
</body>
</html>