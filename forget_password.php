
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

<style>
    /* Form Styling */
    form {
        background-color: #f9f9f9;
        padding: 20px;
        margin: 20px 0;
        border-radius: 10px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }

    input[type="email"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        display: inline-block;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    button[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }

    button[type="submit"]:hover {
        background-color: #45a049;
    }

    p {
        margin-top: 20px;
    }
</style>
