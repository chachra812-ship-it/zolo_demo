<?php
include 'config.php';

// Fetch homepage data
$result = $conn->query("SELECT * FROM homepage LIMIT 1");
$homepage = $result->fetch_assoc();

// Fetch photos
$photos = $conn->query("SELECT * FROM photos ORDER BY uploaded_at DESC");

// Fetch services
$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Homepage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <img src="uploads/<?php echo $homepage['logo_path']; ?>" alt="Logo" style="max-width: 200px;">
        <h1><?php echo htmlspecialchars($homepage['welcome_text']); ?></h1>
    </header>
    
    <section class="photo-gallery">
        <h2>Photo Gallery</h2>
        <div class="gallery">
            <?php while ($photo = $photos->fetch_assoc()): ?>
                <img src="uploads/<?php echo $photo['photo_path']; ?>" alt="Photo" style="max-width: 150px; margin: 10px;">
            <?php endwhile; ?>
        </div>
    </section>
    
    <section class="services">
        <h2>Our Services</h2>
        <ul>
            <?php while ($service = $services->fetch_assoc()): ?>
                <li>
                    <strong><?php echo htmlspecialchars($service['service_name']); ?>:</strong> <?php echo htmlspecialchars($service['description']); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    </section>
    
    <footer>
        <a href="admin.php">Admin Panel</a>
    </footer>

</body>
</html>