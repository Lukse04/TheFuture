<?php
require_once 'includes/bank.inc.php';
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $title = 'Banko informacija';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/bank.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>
    <section class="bank-info">
        <h1>Banko informacija</h1>
        <p>Vartotojo ID: <?php echo htmlspecialchars($userId); ?></p>
        <p>Kortelės numeris: <?php echo htmlspecialchars($cardInfo['card_number']); ?></p>
        <p>Sąskaitos balansas: <?php echo htmlspecialchars($cardInfo['account_balance']); ?> €</p>
    </section>
</body>
</html>
