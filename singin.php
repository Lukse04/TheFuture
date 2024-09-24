
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
      <link rel="stylesheet" type="text/css" href="css/singin.css">
    </head>
    <body>
<?php
include_once 'include_once/navbar.php';
?>  
        <section class="hero">
            <form action="includes/singin.inc.php" method="post">    
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="box">
                  <div class="from">
                    <h2>Sign In</h2>
                    <div class="inputBox">
                      <input type="text" name="uid" required="required">
                      <span>Username</span>
                      <i></i>
                    </div>
                    <div class="inputBox">
                      <input type="password" name="pwd" required="required">
                      <span>Password</span>
                      <i></i>
                    </div>
                    <div class="links">
                      <a href="#">Forgot Password</a>
                      <a href="singup.php">Sign-up</a>
                    </div>
                    <input type="submit" name="submit" value="Sign In">
                  </div>
                </div>
            </form>
            <?php       
                if (isset($_GET["error"])) {
                    switch ($_GET["error"]) {
                        case "emptyinput":
                            echo "<p>Fill in all Fields</p>";
                            break;
                        case "wrongsingin":
                            echo "<p>Incorrect login credentials!</p>";
                            break;
                    }
                }
            ?>
        </section>
    </body>
</html>
