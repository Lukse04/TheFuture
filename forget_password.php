
<?php
    if (isset($_GET['reset'])) {
        if ($_GET['reset'] == "success") {
            echo "<p style='color: green;'>Check your email for a password reset link!</p>";
        } elseif ($_GET['reset'] == "failed") {
            echo "<p style='color: red;'>Failed to send the reset link. Please try again.</p>";
        }
    }
?>

<form action="includes/reset_password.inc.php" method="post">
    <label for="email">Enter your email to reset password:</label><br>
    <input type="email" id="email" id="email" name="email" required><br><br>
    <button type="submit">Submit</button>
</form>
