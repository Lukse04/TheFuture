<?php
require_once 'includes/dbh.inc.php';

// Pradedame sesiją, jei ji dar nepradėta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Patikriname, ar vartotojas yra prisijungęs
if (!isset($_SESSION['userid'])) {
    die("Klaida: Vartotojas neprisijungęs");
}

$userId = $_SESSION['userid'];

// Funkcija pridėti operaciją
function addTransaction($userId, $currency, $transactionType, $amount) {
    global $conn;

    // Įrašome operaciją į lentelę user_transactions, įskaitant valiutą
    $queryInsertTransaction = "
        INSERT INTO user_transactions (user_id, transaction_type, currency, amount, transaction_date)
        VALUES (?, ?, ?, ?, NOW())
    ";
    $stmt = $conn->prepare($queryInsertTransaction);
    $stmt->bind_param('issd', $userId, $transactionType, $currency, $amount);
    $stmt->execute();
    $stmt->close();
}

// Funkcija pinigų pervedimui pagal piniginės adresą
function transferFunds($fromUserId, $walletAddress, $currency, $amount) {
    global $conn;

    // Nustatomas pervedimo mokestis (pvz., Bitcoin valiutai)
    $transactionFee = 0.001;  // Pervedimo mokestis (1 mBTC Bitcoin)

    // Apskaičiuojama bendra suma, įskaitant mokestį
    $totalAmount = $amount + $transactionFee;

    // Patikriname, ar vartotojas turi pakankamai lėšų
    $queryBalanceCheck = "
        SELECT balance 
        FROM user_wallets 
        WHERE user_id = ? AND currency = ?
    ";
    $stmt = $conn->prepare($queryBalanceCheck);
    $stmt->bind_param('is', $fromUserId, $currency);
    $stmt->execute();
    $stmt->bind_result($fromUserBalance);
    $stmt->fetch();
    $stmt->close();

    if ($fromUserBalance < $totalAmount) {
        echo "Nepakanka lėšų pervedimui atlikti.";
        return false;
    }

    // Patikriname, ar gavėjas egzistuoja pagal piniginės adresą
    $queryCheckReceiver = "
        SELECT user_id FROM user_wallets 
        WHERE wallet_address = ? AND currency = ?
    ";
    $stmt = $conn->prepare($queryCheckReceiver);
    $stmt->bind_param('ss', $walletAddress, $currency);
    $stmt->execute();
    $stmt->bind_result($toUserId);
    $stmt->fetch();
    $stmt->close();

    if (!$toUserId) {
        echo "Piniginės adresas nerastas arba neteisinga valiuta.";
        return false;
    }

    // Nuskaityti lėšas nuo siuntėjo balanso
    $queryUpdateSenderBalance = "
        UPDATE user_wallets 
        SET balance = balance - ? 
        WHERE user_id = ? AND currency = ?
    ";
    $stmt = $conn->prepare($queryUpdateSenderBalance);
    $stmt->bind_param('dis', $totalAmount, $fromUserId, $currency);
    $stmt->execute();
    $stmt->close();

    // Pridėti lėšas gavėjo balansui
    $queryUpdateReceiverBalance = "
        UPDATE user_wallets 
        SET balance = balance + ? 
        WHERE user_id = ? AND currency = ?
    ";
    $stmt = $conn->prepare($queryUpdateReceiverBalance);
    $stmt->bind_param('dis', $amount, $toUserId, $currency);
    $stmt->execute();
    $stmt->close();

    // Įrašome operacijas į user_transactions lentelę
    addTransaction($fromUserId, $currency, 'debit', $amount);
    addTransaction($toUserId, $currency, 'credit', $amount);

    echo "Pervedimas sėkmingai įvykdytas!";
}

// Apdorojame pervedimo formos pateikimą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $walletAddress = $_POST['wallet_address'];  // Gavėjo piniginės adresas
    $currency = $_POST['currency'];
    $amount = floatval($_POST['amount']);

    // Iškviečiame pervedimo funkciją pagal piniginės adresą
    transferFunds($userId, $walletAddress, $currency, $amount);
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vartotojo operacijos</title>
</head>
<body>
    <h1>Vartotojo operacijos</h1>

    <!-- Forma pinigų pervedimui -->
    <h2>Pervesti pinigus</h2>
    <form method="POST" action="">
        <label for="wallet_address">Gavėjo piniginės adresas:</label>
        <input type="text" name="wallet_address" id="wallet_address" required><br>

        <label for="currency">Valiuta:</label>
        <select name="currency" id="currency" required>
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
    <table border="1">
        <thead>
            <tr>
                <th>Operacijos tipas</th>
                <th>Valiuta</th>
                <th>Suma</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['transaction_type'] === 'credit' ? 'Įskaitymas' : 'Atskaitymas'; ?></td>
                <td><?php echo $row['currency']; ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo $row['transaction_date']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php $stmt->close(); ?>
    <?php $conn->close(); ?>
</body>
</html>
