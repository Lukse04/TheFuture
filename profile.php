
<?php
    session_start();
    
    include 'includes/dbh.inc.php';
    
    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $user_id = $_SESSION["userid"];
    
    $employee_id = mysqli_real_escape_string($conn, $user_id);
    $query = "SELECT * FROM `users` WHERE usersId ='$employee_id'";
    $query_run = mysqli_query($conn, $query);
    $fetch_profile = mysqli_fetch_array($query_run);
?> 

<!DOCTYPE HTML>
<html lang="lt" >
    <head>
        <?php
        $titel = 'Here is the future';
        include_once 'include_once/header.php';
        ?>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"/>
        <link rel="stylesheet" type="text/css" href="css/profile.css">
    </head>
    <body>
        <?php include_once 'include_once/navbar.php'; ?>
        
        <section class="hero">
            <div class="container2">
                <div id="logo-profile">
                    <h1 class="logo-profile">Profile</h1>
                    <div class="CAT">
                        <h1>get $10</h1>
                    </div>
                </div>
                <div class="leftbox">
                    <nav>
                        <a href="#" class="active">
                            <i class="fa fa-user"></i>
                        </a>
                    </nav>
                </div>
                <div class="rightbox">
                    <form action="includes/profile.inc.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="profile">
                            <h1>Personal Info</h1>
                            <h2>UserName</h2>
                            <p><input type="text" id="username" name="new_username" placeholder="UserName" value="<?= $fetch_profile['usersName'];?>"></p>
                            <h2>Email</h2>
                            <p><input type="email" id="email" name="new_email" placeholder="Email" value="<?= $fetch_profile['usersEmail'];?>"></p>
                            <h2>Old Password</h2>
                            <p><input type="password" id="old_password" name="old_password" placeholder="Old Password"></p>
                            <h2>New Password</h2>
                            <p><input type="password" id="new_password" name="new_password" placeholder="New Password"></p>
                        </div>
                        <div class="button">
                            <input type="submit" name="update_profile" value="Update Profile">
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </body>
</html>
