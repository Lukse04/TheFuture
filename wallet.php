<?php

function generateWalletAddress($currency) {
    switch ($currency) {
        case 'Bitcoin':
            $prefix = (rand(0, 1) == 0) ? '1' : '3';
            return $prefix . bin2hex(random_bytes(20)); // Approx 34 characters
        case 'Ethereum':
            return '0x' . bin2hex(random_bytes(20)); // 42 characters in total
        case 'Tether':
            $prefix = (rand(0, 1) == 0) ? '1' : '3';
            return $prefix . bin2hex(random_bytes(20));
        case 'Dogecoin':
            return 'D' . bin2hex(random_bytes(20)); // Approx 34 characters
        case 'Monero':
            $prefix = (rand(0, 1) == 0) ? '4' : '8';
            return $prefix . bin2hex(random_bytes(47)); // Approx 95 characters
        default:
            return null;
    }
}

require_once 'includes/dbh.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    die("Error: User not logged in");
}

$userId = $_SESSION['userid'];

try {
    // Fetch and display user's wallet addresses and balances for all currencies
    $queryBalances = "SELECT currency, balance, wallet_address FROM user_wallets WHERE user_id = ?";
    $stmt = $conn->prepare($queryBalances);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $resultBalances = $stmt->get_result();

    echo "Wallet Balances and Addresses:<br>";
    while ($rowBalance = $resultBalances->fetch_assoc()) {
        echo $rowBalance['currency'] . ": " . $rowBalance['balance'] . " - Address: " . $rowBalance['wallet_address'] . "<br>";
    }

    if ($resultBalances->num_rows == 0) {
        // Insert default balances and wallet addresses for the user in all currencies
        $currencies = ['Bitcoin', 'Ethereum', 'Tether', 'Dogecoin', 'Monero'];
        $insertBalanceQuery = "INSERT INTO user_wallets (user_id, currency, balance, wallet_address) VALUES (?, ?, ?, ?)";
        
        foreach ($currencies as $currency) {
            $zeroBalance = '0.000000000';
            $walletAddress = generateWalletAddress($currency);
            $stmt = $conn->prepare($insertBalanceQuery);
            $stmt->bind_param('isss', $userId, $currency, $zeroBalance, $walletAddress);
            $stmt->execute();
        }
        echo "<br>Initial balances and wallet addresses for all currencies have been created.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$stmt->close();
$conn->close();

?>
