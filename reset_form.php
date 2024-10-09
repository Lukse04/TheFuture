
<?php
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    echo '<form action="includes/update_password.inc.php" method="post">
        <input type="hidden" name="token" value="' . $token . '">
        <label for="password">New Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Reset Password</button>
    </form>';
}
?>
