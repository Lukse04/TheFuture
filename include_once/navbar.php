<div class="menu__bar">
    <h1 class="logo">The<span>Future</span></h1>
    
    <ul class="menu">
        <li><a href="index.php">Pradžia</a></li>
        <li><a href="#">Apie</a></li>
        <li>
            <a href="#">Puslapiai <i class="fas fa-caret-down"></i></a>
            <div class="dropdown__menu">
                <ul>
                    <li><a href="wallet.php">Piniginė</a></li>
                    <li><a href="bank.php">Bankas</a></li>
                    <li><a href="purchase.php">Pirkti daiktus</a></li>
                    <li><a href="inventory.php">Mano inventorius</a></li>
                    <li><a href="user_transactions.php">Operacijos</a></li>
                </ul>
            </div>
        </li>
        <?php
            if (isset($_SESSION["usertype"])) {
                if ($_SESSION["usertype"] === "admin") {
                    echo '<li><a href="admin_panel.php">Administratoriaus pultas</a></li>';
                }
            }
            if (isset($_SESSION["username"])) {
                echo "<li><a href='profile.php'>Profilis</a></li>";
                echo "<li><a href='includes/logout.inc.php'>Atsijungti</a></li>";
            } else {
                echo "<li><a href='singup.php'>Registruotis</a></li>";
                echo "<li><a href='singin.php'>Prisijungti</a></li>";  
            }
        ?>    
    </ul>
</div>

<!-- Įtraukiame CSS failą -->
<link rel="stylesheet" href="css/navbar.css">
