<?php
    session_start();
?>

<!DOCTYPE HTML>

<html lang="lt" >
    <head>
    <?php
    $title = 'Here is the future';
    include_once 'include_once/header.php';
    ?>
    </head>
    <body>
<?php
include_once 'include_once/navbar.php';
?>  
        <section class="hero">
        <?php
               if(isset($_SESSION["username"])) {
                echo "<p>Hello " . $_SESSION["username"] . "</p>";
            }
            
            
            if(isset($_SESSION["usertype"])) {
                echo "<p> Id " . $_SESSION["usertype"] . "</p>";
            }

            if(isset($_SESSION["userid"])) {
                echo "<p> Id " . $_SESSION["userid"] . "</p>";
            }
        ?>    
            &nbsp;
        </section>
        <?php include_once 'include_once/footer.php'; ?>
    </body>
</html>

<!-- Comment -->