<?php

  require_once 'includes/auth.inc.php';

  $csrfToken = generate_csrf_token();

?>

<!DOCTYPE HTML>
<html lang="lt">
    <head>
      <?php
      $title = 'Čia yra ateitis';
      include_once 'include_once/header.php';
      ?>
      <meta charset="UTF-8">
      <!-- Pridėkite šriftą Poppins -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins&display=swap">
      <link rel="stylesheet" type="text/css" href="css/singin.css">
    </head>
    <body>
      <?php
      include_once 'include_once/navbar.php';
      ?>  

      <div class="main-content">
        <section class="hero">
            <form action="includes/singin.inc.php" method="post">    
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div class="wrapper">
                  <h1>Prisijungti</h1>
                  <div class="input-box">
                    <input type="text" name="uid" required="required" placeholder="Vartotojo vardas">
                    <i></i>
                  </div>
                  <div class="input-box">
                    <input type="password" name="pwd" required="required" placeholder="Slaptažodis">
                    <i></i>
                  </div>
                  <div class="remember-forgot">
                    <label><input type="checkbox" name="remember"> Prisiminti mane</label>
                    <a href="forget_password.php">Pamiršote slaptažodį?</a>
                  </div>
                  <button type="submit" name="submit" class="btn">Prisijungti</button>
                  <div class="register-link">
                    <p>Neturite paskyros? <a href="singup.php">Užsiregistruokite</a></p>
                  </div>
                </div>
            </form>
            <?php       
                if (isset($_GET["error"])) {
                    switch ($_GET["error"]) {
                        case "emptyinput":
                            echo "<p>Užpildykite visus laukus</p>";
                            break;
                        case "wrongsingin":
                            echo "<p>Neteisingi prisijungimo duomenys!</p>";
                            break;
                    }
                }
            ?>
        </section>
      </div>
      <?php include_once 'include_once/footer.php'; ?>
    </body>
</html>
