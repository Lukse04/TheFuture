<header>
    <h1 class="logo">The<span>Future</span></h1>
    
    <ul class="nav__links">
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
                echo "<li><a href='includes/logout.inc.php' class='cta'>Atsijungti</a></li>";
            } else {
                echo "<li><a href='singup.php'>Registruotis</a></li>";
                echo "<li><a href='singin.php' class='cta'>Prisijungti</a></li>";  
            }
        ?>    
    </ul>
    <a class="menu" onclick="openNav()">
        <i class="fas fa-bars"></i>
    </a>
</header>

<!-- Mobilioji navigacija -->
<div id="mobile_menu" class="overlay">
    <a href="javascript:void(0)" class="close" onclick="closeNav()">&times;</a>
    <div class="overlay__content">
        <a href="index.php">Pradžia</a>
        <a href="#">Apie</a>
        <a href="#">Puslapiai</a>
        <!-- Papildomos nuorodos -->
        <?php
            if (isset($_SESSION["usertype"])) {
                if ($_SESSION["usertype"] === "admin") {
                    echo '<a href="admin_panel.php">Administratoriaus pultas</a>';
                }
            }
            if (isset($_SESSION["username"])) {
                echo "<a href='profile.php'>Profilis</a>";
                echo "<a href='includes/logout.inc.php' class='cta'>Atsijungti</a>";
            } else {
                echo "<a href='singup.php'>Registruotis</a>";
                echo "<a href='singin.php' class='cta'>Prisijungti</a>";  
            }
        ?> 
    </div>
</div>

<!-- Įtraukiame CSS failą -->
<link rel="stylesheet" href="css/navbar.css">

<!-- JavaScript mobiliajai navigacijai -->
<script>
function openNav() {
    document.getElementById("mobile_menu").classList.add("overlay--active");
}

function closeNav() {
    document.getElementById("mobile_menu").classList.remove("overlay--active");
}
</script>
