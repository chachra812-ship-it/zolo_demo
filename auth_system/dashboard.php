<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];
?>

<?php include 'header.php'; ?>
<h2>Welcome, <?php echo $user['username']; ?>!</h2>
<p>Your role is: <?php echo $user['role']; ?></p>
<?php include 'footer.php'; ?>
