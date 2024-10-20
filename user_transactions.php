<?php
// user_transactions.php

require_once 'includes/dbh.inc.php';
require_once 'includes/user_transactions.inc.php';

// Pradedame sesiją, jei ji dar nepradėta
require_once 'includes/auth.inc.php';

check_auth();

$userId = get_user_id();

$csrfToken = generate_csrf_token();

// Kintamieji pranešimams
$successMessage = "";
$errorMessage = "";

// Apdorojame pervedimo formos pateikimą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF apsauga
    if (!isset($_POST['csrf_token']) || !check_csrf_token($_POST['csrf_token'])) {
        $errorMessage = "Neteisingas CSRF žymeklis.";
    } else {
        // Gauname ir išvalome įvestus duomenis
        $walletAddress = trim($_POST['wallet_address']);
        $currency = $_POST['currency'];
        $amount = $_POST['amount'];

        // Įvesties validacija
        $errors = [];
        if (empty($walletAddress)) {
            $errors[] = "Gavėjo piniginės adresas negali būti tuščias.";
        }
        if (empty($currency)) {
            $errors[] = "Pasirinkite valiutą.";
        }
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors[] = "Įveskite teisingą sumą.";
        }

        if (empty($errors)) {
            // Iškviečiame pervedimo funkciją pagal piniginės adresą
            $transferResult = transferFunds($conn, $userId, $walletAddress, $currency, $amount);
            if ($transferResult['status'] === 'success') {
                $successMessage = $transferResult['message'];
            } else {
                $errorMessage = $transferResult['message'];
            }
        } else {
            $errorMessage = implode("<br>", $errors);
        }
    }
}

// Paimame vartotojo operacijų istoriją
$queryTransactions = "
    SELECT transaction_type, currency, amount, transaction_date 
    FROM user_transactions 
    WHERE user_id = ? 
    ORDER BY transaction_date DESC
";

$stmt = $conn->prepare($queryTransactions);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $title = 'Vartotojo operacijos';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/user_transactions.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>

    <div class="container">
        <h1>Vartotojo operacijos</h1>

        <?php if (!empty($successMessage)): ?>
            <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <!-- Forma pinigų pervedimui -->
        <h2>Pervesti pinigus</h2>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <label for="wallet_address">Gavėjo piniginės adresas:</label>
            <input type="text" name="wallet_address" id="wallet_address" required><br>

            <label for="currency">Valiuta:</label>
            <select name="currency" id="currency" required>
                <option value="">-- Pasirinkite valiutą --</option>
                <option value="Bitcoin">Bitcoin</option>
                <option value="Ethereum">Ethereum</option>
                <option value="Tether">Tether</option>
                <option value="Dogecoin">Dogecoin</option>
                <option value="Monero">Monero</option>
            </select><br>

            <label for="amount">Suma:</label>
            <input type="number" step="0.000000001" name="amount" id="amount" required><br>

            <button type="submit">Pervesti</button>
        </form>

        <!-- Operacijų istorija -->
        <h2>Operacijų istorija</h2>
        <table>
            <thead>
                <tr>
                    <th>Operacijos tipas</th>
                    <th>Valiuta</th>
                    <th>Suma</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['transaction_type'] === 'credit' ? 'Įskaitymas' : 'Nurašymas'); ?></td>
                        <td><?php echo htmlspecialchars($row['currency']); ?></td>
                        <td><?php echo htmlspecialchars($row['amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nėra operacijų.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $stmt->close(); ?>
    <?php $conn->close(); ?>
</body>
</html>
