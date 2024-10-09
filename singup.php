<?php
    session_start();
    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<!DOCTYPE HTML>
<html lang="lt">
    <head>
        <?php
        $title = 'User Registration - The Future';
        include_once 'include_once/header.php';
        ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/navbar.css">
        <link rel="stylesheet" type="text/css" href="css/singup.css">
    </head>
    <body>
        <?php include_once 'include_once/navbar.php'; ?>

        <section class="signup-section">
    <div class="signup-container">
        <div class="heading">Sign Up</div>

        <!-- Error messages section -->
        <?php if (isset($_GET["error"])): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px; text-align: center;">
            <?php
                switch ($_GET["error"]) {
                    case "emptyinput":
                        echo "Fill in all fields!";
                        break;
                    case "invalidUserName":
                        echo "Choose a proper username!";
                        break;
                    case "invalidEmail":
                        echo "Choose a valid email!";
                        break;
                    case "stmtfailed":
                        echo "Something went wrong. Try again later.";
                        break;
                    case "passwordmismatch":
                        echo "Passwords do not match!";
                        break;
                    case "usernametaken":
                        echo "Username is already taken!";
                        break;
                    case "none":
                        echo "Sign up successful!";
                        break;
                }
            ?>
        </div>
        <?php endif; ?>

        <!-- Signup form -->
        <form action="includes/singup.inc.php" method="post" class="signup-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="pwd" placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                <small id="password-match-message"></small>
            </div>

            <div class="form-group">
                <button type="submit" name="signup_submit">Sign Up</button>
            </div>
        </form>
    </div>
</section>


<script>
    // Automatically hide error message after 5 seconds
    const errorMessage = document.querySelector('div[style*="background-color"]');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000); // Hide after 5 seconds
    }

    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const message = document.getElementById('password-match-message');

    function checkPasswords() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;

        if (password.length >= 3) {
            passwordField.classList.add('valid');
        } else {
            passwordField.classList.remove('valid');
        }

        if (password === confirmPassword && confirmPassword.length > 0) {
            confirmPasswordField.classList.add('valid');
            confirmPasswordField.classList.remove('invalid');
            message.textContent = 'Passwords match!';
            message.style.color = 'green';
        } else if (confirmPassword.length > 0) {
            confirmPasswordField.classList.add('invalid');
            confirmPasswordField.classList.remove('valid');
            message.textContent = 'Passwords do not match!';
            message.style.color = 'red';
        } else {
            confirmPasswordField.classList.remove('invalid', 'valid');
            message.textContent = '';
        }
    }

    passwordField.addEventListener('input', checkPasswords);
    confirmPasswordField.addEventListener('input', checkPasswords);
</script>


        <?php include_once 'include_once/footer.php'; ?>
    </body>
</html>
