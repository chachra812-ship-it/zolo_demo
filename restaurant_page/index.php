<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taste of Italy | Authentic Italian Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="overlay"></div>

    <!-- Italian Cuisine Content Section -->
    <div class="content-section">
        <h1>🍝 Welcome to Taste of Italy</h1>
        <p>Experience the authentic flavors of Italy right here! Our chef brings decades of experience from Rome, Florence, and Naples to your table.</p>
        
        <div class="divider"></div>
        
        <h2>Our Specialties</h2>
        <p>From handmade pasta to wood-fired pizzas, every dish is crafted with love and the freshest ingredients imported directly from Italy.</p>
        
        <div class="dishes">
            <div class="dish-card">🍕 Margherita Pizza</div>
            <div class="dish-card">🍝 Fettuccine Alfredo</div>
            <div class="dish-card">🥘 Lasagna</div>
            <div class="dish-card">🍝 Spaghetti Carbonara</div>
            <div class="dish-card">🥗 Caprese Salad</div>
            <div class="dish-card">🍰 Tiramisu</div>
        </div>
        
        <div class="divider"></div>
        
        <h3>🕐 Opening Hours</h3>
        <p>
            <strong>Monday - Friday:</strong> 11:00 AM - 10:00 PM<br>
            <strong>Saturday - Sunday:</strong> 10:00 AM - 11:00 PM
        </p>
        
        <p>
            <strong>📍 Location:</strong> 123 Italian Avenue, Food City
        </p>
    </div>

    <!-- Contact Form Section -->
    <div class="form-container">
        <h2>📞 Reach Out to Us</h2>
        <p>Reserve a table or ask us anything!</p>
        
        <!-- autocomplete="off" ensures data is cleared when returning to page -->
        <form id="contactForm" autocomplete="off">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="+1 234 567 890">
            </div>

            <button type="submit" id="submitBtn">Submit Request</button>
        </form>
    </div>

    <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Stop page from reloading

        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerText;
        
        // Change button text to show processing
        btn.innerText = "Sending...";
        btn.disabled = true;

        // Gather data
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value
        };

        // Send via AJAX (Fetch API)
        fetch('submit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                
                // ✅ RESET THE FORM IMMEDIATELY AFTER SUCCESS
                document.getElementById('contactForm').reset();
                
                // Optional: Show success message briefly before redirect
                btn.innerText = "Sent!";
                
                // Redirect to Thank You page after 1 second
                setTimeout(() => {
                    window.location.href = 'thankyou.php';
                }, 1000);
                
            } else {
                alert('Error: ' + data.message);
                btn.innerText = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong!');
            btn.innerText = originalText;
            btn.disabled = false;
        });
    });
</script>

</body>
</html>