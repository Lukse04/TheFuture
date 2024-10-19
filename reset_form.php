
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

if (isset($_GET['update'])) {
    if ($_GET['update'] == "success") {
        echo "<p style='color: green;'>Your password has been successfully reset!</p>";
    } elseif ($_GET['update'] == "failed") {
        echo "<p style='color: red;'>Failed to reset the password. Please try again.</p>";
    }
}
?>
