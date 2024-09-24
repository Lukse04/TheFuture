
<?php
    session_start();
    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<!DOCTYPE HTML>
<html lang="lt" >
    <head>
      <?php
      $titel = 'Here is the future';
      include_once 'include_once/header.php';
      ?>
      <link rel="stylesheet" type="text/css" href="css/singup.css">
    </head>
    <body>
    <?php
    include_once 'include_once/navbar.php';
    ?>  
        <section class="hero">
            <div class="container">
                <div class="heading">Sign Up</div>
                <form action="includes/singup.inc.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="card-details">
                        <div class="card-box">
                            <span class="details">Username</span>
                            <input type="text" name="username" placeholder="Username">
                        </div>
                    </div>
                    <div class="card-details">
                        <div class="card-box">
                            <span class="details">Email</span>
                            <input type="email" name="email" placeholder="Email">
                        </div>
                    </div>
                    <div class="card-details">
                        <div class="card-box">
                            <span class="details">Password</span>
                            <input type="password" name="pwd" placeholder="Password">
                        </div>
                    </div>
                    <div class="button">
                      <input type="submit" name="submit" value="Sign Up">
                    </div>
                </form>
            </div>
            <?php
                if (isset($_GET["error"])) {
                    switch ($_GET["error"]) {
                        case "emptyinput":
                            echo "<p>Fill in all Fields</p>";
                            break;
                        case "invalidUserName":
                            echo "<p>Choose a proper UserName</p>";
                            break;
                        case "invalidEmail":
                            echo "<p>Choose a valid Email</p>";
                            break;
                        case "stmtfailed":
                            echo "<p>Something went wrong. Try again later.</p>";
                            break;
                        case "none":
                            echo "<p>Sign up successful!</p>";
                            break;
                    }
                }
            ?>
        </section>
    </body>
</html>
