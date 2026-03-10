<?php
include 'db.php';
include 'functions.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    if (loginUser($conn, $username, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "❌ Invalid credentials!";
    }
}
?>

<?php include 'header.php'; ?>
<h2>Login</h2>
<?php if ($message) echo "<p class='msg'>$message</p>"; ?>
<form method="POST">
    <input type="text" name="username" placeholder="Enter Username" required>
    <input type="password" name="password" placeholder="Enter Password" required>
    <button type="submit">Login</button>
</form>
<?php include 'footer.php'; ?>
