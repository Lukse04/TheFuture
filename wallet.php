<?php
// wallet.php

require_once 'includes/wallet.inc.php';

require_once 'includes/auth.inc.php';

check_auth();

$userId = get_user_id();

try {
    // Gauname vartotojo piniginių duomenis
    $wallets = fetchUserWallets($conn, $userId);

    if (empty($wallets)) {
        // Jei piniginių nėra, sukuriame pradines pinigines ir gauname duomenis dar kartą
        createInitialWallets($conn, $userId);
        $wallets = fetchUserWallets($conn, $userId);
        $initialCreated = true;
    }

} catch (Exception $e) {
    echo "Klaida: " . $e->getMessage();
}

// Uždarome duomenų bazės ryšį
$conn->close();
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $title = 'Mano piniginės';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/wallet.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>
    <div class="container">
        <h1>Jūsų piniginės</h1>
        <?php
        if (isset($initialCreated) && $initialCreated) {
            echo "<p>Pradinės piniginės ir adresai visoms valiutoms buvo sukurti.</p>";
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>Valiuta</th>
                    <th>Balansas</th>
                    <th>Piniginės adresas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wallets as $wallet): ?>
                <tr>
                    <td><?php echo htmlspecialchars($wallet['currency']); ?></td>
                    <td><?php echo htmlspecialchars($wallet['balance']); ?></td>
                    <td><?php echo htmlspecialchars($wallet['wallet_address']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
