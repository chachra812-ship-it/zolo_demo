<?php
include 'db.php';
include 'functions.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email    = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);

    if (registerUser($conn, $username, $email, $password)) {
        $message = "✅ Registration successful! <a href='login.php'>Login here</a>";
    } else {
        $message = "❌ Error: Could not register.";
    }
}
?>

<?php include 'header.php'; ?>
<h2>Create Account</h2>
<?php if ($message) echo "<p class='msg'>$message</p>"; ?>
<form method="POST">
    <input type="text" name="username" placeholder="Enter Username" required>
    <input type="email" name="email" placeholder="Enter Email" required>
    <input type="password" name="password" placeholder="Enter Password" required>
    <button type="submit">Register</button>
</form>
<?php include 'footer.php'; ?>
