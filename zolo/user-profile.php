<!-- user-profile.php -->
<div class="user-profile">
    Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | 
    Plan: <strong><?php echo ucfirst($userPlan); ?></strong>
    <a href="logout.php" style="color: #ff6b6b; margin-left: 10px;">Logout</a>
</div>

<style>
.user-profile { background: #333; color: white; padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
</style>