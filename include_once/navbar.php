<div class="menu__bar">
    <h1 class="logo">The<span>Future</span></h1>
    
    <ul class="menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="#">About</a></li>
        <li>
            <a href="#">Pages <i class="fas fa-caret-down"></i></a>
            <div class="dropdown__menu">
                <ul>
                    <li><a href="wallet.php">Wallet</a></li>
                    <li><a href="bank.php">Bank</a></li>
                    <li><a href="purchase.php">Purchase Items</a></li>
                    <li><a href="inventory.php">My Inventory</a></li>
                    <li><a href="user_transactions.php">Transactions</a></li> <!-- Nuoroda į Transactions -->
                </ul>
            </div>
        </li>
        <?php
            if (isset($_SESSION["usertype"])) {
                if ($_SESSION["usertype"] === "admin") {
                    echo '<li><a href="admin_panel.php">Admin Panel</a></li>';
                }
            }
            if (isset($_SESSION["username"])) {
                echo "<li><a href='profile.php'>Profile</a></li>";
                echo "<li><a href='../includes/logout.inc.php'>Log out</a></li>";
            } else {
                echo "<li><a href='singup.php'>Sign-up</a></li>";
                echo "<li><a href='singin.php'>Sign in</a></li>";  
            }
        ?>    
    </ul>
</div>

<style>
.menu__bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: #333;
    color: #fff;
}

.menu {
    list-style: none;
    display: flex;
}

.menu li {
    margin-right: 20px;
}

.menu a {
    color: #fff;
    text-decoration: none;
}

.dropdown__menu {
    display: none;
    position: absolute;
    background-color: #444;
}

.menu li:hover .dropdown__menu {
    display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
    .menu {
        flex-direction: column;
    }
    .dropdown__menu {
        position: static;
    }
}
</style>
