<?php
require_once 'includes/dbh.inc.php';
require_once 'chest_purchase.php'; // Įtraukiame funkcijas, susijusias su chest pirkimu

// Patikriname, ar sesija jau aktyvi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userid'])) {
    header("Location: singin.php");
    exit();
}

$userId = $_SESSION['userid'];

// Paimame vartotojo XP ir lygio informaciją iš duomenų bazės
$stmt = $conn->prepare("SELECT xp, level FROM users WHERE usersId = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$currentXP = $user['xp'];
$currentLevel = $user['level'];
$xpForNextLevel = $currentLevel * 1000; // Pvz., 1000 XP per lygį

// Procentai, kiek XP užpildyta
$xpPercentage = ($currentXP / $xpForNextLevel) * 100;

$xpGained = 0; // Nustatome pridėtus XP (prieš pirkimą)
$levelUp = false; // Tikriname, ar vartotojas pakėlė lygį

$itemsForSale = [
    ['name' => 'Basic Chest', 'type' => 'chest', 'level' => 1, 'price' => 100],
    ['name' => 'Advanced Chest', 'type' => 'chest', 'level' => 2, 'price' => 200],
    ['name' => 'Epic Chest', 'type' => 'chest', 'level' => 3, 'price' => 500],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemIndex = $_POST['itemIndex'];
    $item = $itemsForSale[$itemIndex];

    // Patikriname, ar vartotojas turi pakankamai pinigų kortelėje
    if (checkCardBalance($userId) >= $item['price']) {
        // Nuskaičiuojame pinigus už pirkimą
        deductCardBalance($userId, $item['price']);

        // Pridedame daiktą į inventorių
        addToInventory($userId, $item['type'], $item['level']);

        // Jei vartotojas perka skrynią, pridedame XP ir tikriname lygį
        if ($item['type'] === 'chest') {
            addXpAndCheckLevel($userId); 
            $xpGained = XP_PER_CHEST; // Pridedame XP vertę

            // Patikriname, ar vartotojas pakėlė lygį
            $stmt = $conn->prepare("SELECT xp, level FROM users WHERE usersId = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $updatedUser = $result->fetch_assoc();

            if ($updatedUser['level'] > $currentLevel) {
                $levelUp = true; // Nustatome, kad įvyko lygio pakilimas
            }
        }

        echo "You have successfully purchased {$item['name']}!";
    } else {
        echo "Not enough money to purchase {$item['name']}.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Items</title>
    <style>
        /* Stilius lygio barui */
        .level-bar-container {
            width: 100%;
            height: 40px;
            background-color: #e0e0e0;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin: 20px 0;
        }

        .level-bar-fill {
            height: 100%;
            width: 0;
            background-color: #4caf50;
            transition: width 1s ease-in-out;
            border-radius: 20px;
        }

        .level-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            color: #fff;
        }

        /* Animacija sėkmingam pirkimui */
        #successMessage {
            display: none;
            font-size: 20px;
            color: green;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #dff0d8;
            padding: 20px;
            border-radius: 10px;
            animation: fadeInOut 3s forwards;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }

        /* Fejerverkų animacija */
        .firework {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
            animation: explode 2s ease-out forwards;
            display: none;
        }

        @keyframes explode {
            0% {
                width: 10px;
                height: 10px;
                opacity: 1;
            }
            50% {
                width: 50px;
                height: 50px;
                opacity: 0.5;
            }
            100% {
                width: 100px;
                height: 100px;
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <h2>Purchase Items</h2>

    <!-- Lygio baras -->
    <div class="level-bar-container">
        <div class="level-bar-fill" id="levelBar"></div>
        <div class="level-info">Level <?= $currentLevel ?> - <?= round($xpPercentage) ?>%</div>
    </div>

    <!-- Sėkmingo pirkimo pranešimas su pridėtu XP -->
    <div id="successMessage">
        You have successfully purchased a chest!<br>
        You gained <?= $xpGained ?> XP!
    </div>

    <!-- Fejerverkų animacija -->
    <div class="firework" id="firework1"></div>

    <form method="POST" action="purchase.php">
        <table border="1">
            <tr>
                <th>Item Name</th>
                <th>Type</th>
                <th>Level</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
            <?php foreach ($itemsForSale as $index => $item): ?>
                <tr>
                    <td><?= $item['name'] ?></td>
                    <td><?= ucfirst($item['type']) ?></td>
                    <td><?= $item['level'] ?></td>
                    <td><?= $item['price'] ?> coins</td>
                    <td>
                        <button type="submit" name="itemIndex" value="<?= $index ?>">Buy</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>

    <script>
        // Pildome lygio barą pagal procentus
        document.getElementById('levelBar').style.width = "<?= $xpPercentage ?>%";

        // Rodome sėkmingo pirkimo pranešimą, jei buvo pridėti XP
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $xpGained > 0): ?>
            document.getElementById("successMessage").style.display = "block";
        <?php endif; ?>

        // Rodome fejerverkus, jei vartotojas pakėlė lygį
        <?php if ($levelUp): ?>
            const firework = document.getElementById("firework1");
            firework.style.display = "block";
            setTimeout(() => {
                firework.style.display = "none";
            }, 2000); // Fejerverkai dings po 2 sekundžių
        <?php endif; ?>
    </script>
</body>
</html>
