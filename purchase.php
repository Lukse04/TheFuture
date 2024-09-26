<?php
// purchase.php

require_once 'includes/dbh.inc.php';
require_once 'includes/chest_purchase.inc.php'; // Chest purchase functions
require_once 'includes/xp.inc.php'; // XP functions
require_once 'includes/user_functions.inc.php'; // User functions

// Pradedame sesiją, jei ji dar nepradėta
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userid'])) {
    header("Location: singin.php");
    exit();
}

$userId = $_SESSION['userid'];

// Gauname vartotojo lygio ir XP informaciją
$userInfo = getUserLevelInfo($conn, $userId);
$currentXP = $userInfo['currentXP'];
$currentLevel = $userInfo['currentLevel'];
$xpForNextLevel = $userInfo['xpForNextLevel'];
$xpPercentage = $userInfo['xpPercentage'];

$xpGained = 0; // XP, gautas prieš pirkimą
$levelUp = false; // Tikriname, ar vartotojas pakėlė lygį

// Funkcija, kuri grąžina parduodamus daiktus
function getItemsForSale() {
    return [
        ['name' => 'Basic Chest', 'type' => 'chest', 'level' => 1, 'price' => 100],
        ['name' => 'Advanced Chest', 'type' => 'chest', 'level' => 2, 'price' => 200],
        ['name' => 'Epic Chest', 'type' => 'chest', 'level' => 3, 'price' => 500],
    ];
}

$itemsForSale = getItemsForSale();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF apsauga
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Klaida: neteisingas CSRF žetonas.");
    }

    $itemIndex = intval($_POST['itemIndex']);
    $itemsForSale = getItemsForSale();

    if (isset($itemsForSale[$itemIndex])) {
        $item = $itemsForSale[$itemIndex];

        // Perkame skrynią
        $purchaseResult = buyChest($conn, $userId, $item['level'], $item['price']);

        if ($purchaseResult['success']) {
            $xpGained = $purchaseResult['xpGained'];
            $levelUp = $purchaseResult['levelUp'];

            // Atnaujiname vartotojo lygio ir XP informaciją
            $userInfo = updateUserLevelInfo($conn, $userId);
            $currentXP = $userInfo['currentXP'];
            $currentLevel = $userInfo['currentLevel'];
            $xpForNextLevel = $userInfo['xpForNextLevel'];
            $xpPercentage = $userInfo['xpPercentage'];

            $successMessage = "Sėkmingai nusipirkote {$item['name']}!";
        } else {
            $errorMessage = $purchaseResult['error'];
        }
    } else {
        $errorMessage = "Neteisingai pasirinktas daiktas.";
    }
}

// Sukuriame naują CSRF žetoną, jei jo nėra
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $titel = 'Pirkti daiktus';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/purchase.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>
    <h2>Pirkti daiktus</h2>

    <!-- Lygio baras -->
    <div class="level-bar-container">
        <div class="level-bar-fill" id="levelBar"></div>
        <div class="level-info">Lygis <?= htmlspecialchars($currentLevel) ?> - <?= round($xpPercentage) ?>%</div>
    </div>

    <?php if (isset($successMessage)): ?>
        <!-- Sėkmingo pirkimo pranešimas su pridėtu XP -->
        <div class="success-message">
            <?php echo htmlspecialchars($successMessage); ?><br>
            Jūs gavote <?= htmlspecialchars($xpGained) ?> XP!
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <!-- Klaidos pranešimas -->
        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Fejerverkų animacija -->
    <?php if (isset($levelUp) && $levelUp): ?>
        <div class="firework" id="firework1"></div>
    <?php endif; ?>

    <form method="POST" action="purchase.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
        <table>
            <tr>
                <th>Daikto pavadinimas</th>
                <th>Tipas</th>
                <th>Lygis</th>
                <th>Kaina</th>
                <th>Veiksmas</th>
            </tr>
            <?php foreach ($itemsForSale as $index => $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($item['type'])) ?></td>
                    <td><?= htmlspecialchars($item['level']) ?></td>
                    <td><?= htmlspecialchars($item['price']) ?> €</td>
                    <td>
                        <button type="submit" name="itemIndex" value="<?= $index ?>">Pirkti</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>

    <script>
        // Pildome lygio barą pagal procentus
        document.getElementById('levelBar').style.width = "<?= $xpPercentage ?>%";

        // Rodome fejerverkus, jei vartotojas pakėlė lygį
        <?php if (isset($levelUp) && $levelUp): ?>
            const firework = document.getElementById("firework1");
            firework.style.display = "block";
            setTimeout(() => {
                firework.style.display = "none";
            }, 2000); // Fejerverkai dings po 2 sekundžių
        <?php endif; ?>
    </script>
</body>
</html>
