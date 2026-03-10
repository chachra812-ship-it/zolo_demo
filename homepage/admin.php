<?php
include 'config.php';

// Fetch current data (no POST handling here—AJAX takes care of submissions)
$result = $conn->query("SELECT * FROM homepage LIMIT 1");
$homepage = $result->fetch_assoc();
$photos = $conn->query("SELECT * FROM photos ORDER BY uploaded_at DESC");
$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
</head>
<body>
    <h1>Admin Panel</h1>
    
    <!-- Response div for AJAX messages (success/error) -->
    <div id="response"></div>
    
    <h2>Update Homepage</h2>
    <form id="updateHomepageForm" method="post" enctype="multipart/form-data">
        <label>Logo: <input type="file" name="logo"></label><br>
        <label>Welcome Text: <textarea name="welcome_text"><?php echo htmlspecialchars($homepage['welcome_text']); ?></textarea></label><br>
        <button type="submit" name="update_homepage">Update</button>
    </form>
    
    <h2>Upload Photo</h2>
    <form id="uploadPhotoForm" method="post" enctype="multipart/form-data">
        <label>Photo: <input type="file" name="photo"></label><br>
        <button type="submit" name="upload_photo">Upload</button>
    </form>
    
    <h2>Manage Photos</h2>
    <?php while ($photo = $photos->fetch_assoc()): ?>
        <div>
            <img src="uploads/<?php echo $photo['photo_path']; ?>" alt="Photo" style="max-width: 100px;">
            <form id="deletePhotoForm-<?php echo $photo['id']; ?>" method="post" style="display:inline;">
                <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                <button type="submit" name="delete_photo">Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
    
    <h2>Manage Services</h2>
    <form id="addServiceForm" method="post">
        <label>Service Name: <input type="text" name="service_name" required></label><br>
        <label>Description: <textarea name="service_description" required></textarea></label><br>
        <button type="submit" name="add_service">Add Service</button>
    </form>
    
    <h3>Existing Services</h3>
    <?php while ($service = $services->fetch_assoc()): ?>
        <div>
            <strong><?php echo htmlspecialchars($service['service_name']); ?>:</strong> <?php echo htmlspecialchars($service['description']); ?>
            <form id="deleteServiceForm-<?php echo $service['id']; ?>" method="post" style="display:inline;">
                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                <button type="submit" name="delete_service">Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
    
    <a href="index.php">Back to Homepage</a>
    
    <!-- JavaScript for AJAX functionality -->
    <script>
        // Function to handle AJAX responses
        function handleResponse(result, formElement) {
            const responseDiv = document.getElementById('response');
            if (result.success) {
                responseDiv.innerHTML = '<p style="color: green;">' + result.success + '</p>';
                // Optional: Reload the page or specific sections after success (uncomment below for full reload)
                // location.reload();
            } else {
                responseDiv.innerHTML = '<p style="color: red;">' + result.error + '</p>';
            }
            // Re-enable the submit button
            const submitBtn = formElement.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = false;
        }

        // Update Homepage Form (with file upload)
        document.getElementById('updateHomepageForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_homepage');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => handleResponse(result, this))
            .catch(error => {
                document.getElementById('response').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                submitBtn.disabled = false;
            });
        });

        // Upload Photo Form (with file upload)
        document.getElementById('uploadPhotoForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'upload_photo');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => handleResponse(result, this))
            .catch(error => {
                document.getElementById('response').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                submitBtn.disabled = false;
            });
        });

        // Add Service Form (no files, use JSON)
        document.getElementById('addServiceForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const data = {
                action: 'add_service',
                service_name: formData.get('service_name'),
                service_description: formData.get('service_description')
            };
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => handleResponse(result, this))
            .catch(error => {
                document.getElementById('response').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                submitBtn.disabled = false;
            });
        });

        // Delete Photo Forms (dynamically generated, use event delegation)
        document.addEventListener('submit', function(event) {
            if (event.target.matches('[id^="deletePhotoForm-"]')) {
                event.preventDefault();
                const formData = new FormData(event.target);
                formData.append('action', 'delete_photo');
                
                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => handleResponse(result, event.target))
                .catch(error => {
                    document.getElementById('response').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                });
            }
        });

        // Delete Service Forms (similar to delete photo)
        document.addEventListener('submit', function(event) {
            if (event.target.matches('[id^="deleteServiceForm-"]')) {
                event.preventDefault();
                const formData = new FormData(event.target);
                formData.append('action', 'delete_service');
                
                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => handleResponse(result, event.target))
                .catch(error => {
                    document.getElementById('response').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                });
            }
        });
    </script>
</body>
</html>