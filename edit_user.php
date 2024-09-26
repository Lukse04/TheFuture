<?php
// edit_user.php

// Įtraukite funkcijų failą
require_once 'includes/edit_user.inc.php';

// Patikrinkite, ar vartotojo ID perduotas per GET parametrus
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']); // Konvertuojame į sveiką skaičių, kad išvengtume saugumo problemų

    // Gaukite vartotojo duomenis
    $user = getUserDetails($conn, $userId);

    if ($user) {
        // Gaukite vartotojo pinigines
        $wallets = getUserWallets($conn, $userId);
    } else {
        die("Klaida: Vartotojas nerastas.");
    }
} else {
    die("Klaida: Vartotojo ID nenurodytas.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $titel = 'Redaguoti vartotoją';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/edit_user.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>

    <section class="edit-user-form">
        <h1>Redaguoti vartotoją</h1>
        <form method="post" action="includes/update_user.inc.php">
            <!-- CSRF tokenas -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="usersId" value="<?php echo htmlspecialchars($user['usersId']); ?>">

            <label>Vartotojo vardas:</label>
            <input type="text" name="usersName" value="<?php echo htmlspecialchars($user['usersName']); ?>" required>

            <label>El. paštas:</label>
            <input type="email" name="usersEmail" value="<?php echo htmlspecialchars($user['usersEmail']); ?>" required>

            <label>Vartotojo tipas:</label>
            <select name="user_type" required>
                <option value="user" <?php if ($user['user_type'] === 'user') echo 'selected'; ?>>Vartotojas</option>
                <option value="admin" <?php if ($user['user_type'] === 'admin') echo 'selected'; ?>>Administratorius</option>
            </select>

            <h3>Vartotojo piniginės:</h3>
            <?php foreach ($wallets as $index => $wallet): ?>
                <div class="wallet">
                    <input type="hidden" name="wallet_address[]" value="<?php echo htmlspecialchars($wallet['wallet_address']); ?>">

                    <label>Valiuta:</label>
                    <input type="text" name="currency[]" value="<?php echo htmlspecialchars($wallet['currency']); ?>" readonly>

                    <label>Balansas:</label>
                    <input type="number" step="0.01" name="balance[]" value="<?php echo htmlspecialchars($wallet['balance']); ?>" required>
                </div>
            <?php endforeach; ?>

            <input type="submit" value="Atnaujinti">
        </form>
    </section>
</body>
</html>
