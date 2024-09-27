<?php
// includes/user_transactions.inc.php

require_once 'dbh.inc.php';

// Funkcija pridėti operaciją
function addTransaction($conn, $userId, $currency, $transactionType, $amount) {
    // Įrašome operaciją į lentelę user_transactions, įskaitant valiutą
    $queryInsertTransaction = "
        INSERT INTO user_transactions (user_id, transaction_type, currency, amount, transaction_date)
        VALUES (?, ?, ?, ?, NOW())
    ";
    $stmt = $conn->prepare($queryInsertTransaction);
    if (!$stmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $stmt->bind_param('issd', $userId, $transactionType, $currency, $amount);
    if (!$stmt->execute()) {
        die("Klaida vykdant užklausą: " . $stmt->error);
    }
    $stmt->close();
}

// Funkcija pinigų pervedimui pagal piniginės adresą
function transferFunds($conn, $fromUserId, $walletAddress, $currency, $amount) {
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
    if (!$stmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $stmt->bind_param('is', $fromUserId, $currency);
    $stmt->execute();
    $stmt->bind_result($fromUserBalance);
    $stmt->fetch();
    $stmt->close();

    if ($fromUserBalance === null) {
        // Vartotojas neturi tokios valiutos balanso
        return ["status" => "error", "message" => "Jūs neturite šios valiutos balanso."];
    }

    if ($fromUserBalance < $totalAmount) {
        return ["status" => "error", "message" => "Nepakanka lėšų pervedimui atlikti."];
    }

    // Patikriname, ar gavėjas egzistuoja pagal piniginės adresą
    $queryCheckReceiver = "
        SELECT user_id FROM user_wallets 
        WHERE wallet_address = ? AND currency = ?
    ";
    $stmt = $conn->prepare($queryCheckReceiver);
    if (!$stmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $stmt->bind_param('ss', $walletAddress, $currency);
    $stmt->execute();
    $stmt->bind_result($toUserId);
    $stmt->fetch();
    $stmt->close();

    if (!$toUserId) {
        return ["status" => "error", "message" => "Piniginės adresas nerastas arba neteisinga valiuta."];
    }

    if ($toUserId == $fromUserId) {
        return ["status" => "error", "message" => "Negalite pervesti lėšų sau."];
    }

    // Pradedame transakciją
    $conn->autocommit(false);

    try {
        // Nuskaityti lėšas nuo siuntėjo balanso
        $queryUpdateSenderBalance = "
            UPDATE user_wallets 
            SET balance = balance - ? 
            WHERE user_id = ? AND currency = ?
        ";
        $stmt = $conn->prepare($queryUpdateSenderBalance);
        if (!$stmt) {
            throw new Exception("Klaida ruošiant užklausą: " . $conn->error);
        }
        $stmt->bind_param('dis', $totalAmount, $fromUserId, $currency);
        if (!$stmt->execute()) {
            throw new Exception("Klaida vykdant užklausą: " . $stmt->error);
        }
        $stmt->close();

        // Pridėti lėšas gavėjo balansui
        $queryUpdateReceiverBalance = "
            UPDATE user_wallets 
            SET balance = balance + ? 
            WHERE user_id = ? AND currency = ?
        ";
        $stmt = $conn->prepare($queryUpdateReceiverBalance);
        if (!$stmt) {
            throw new Exception("Klaida ruošiant užklausą: " . $conn->error);
        }
        $stmt->bind_param('dis', $amount, $toUserId, $currency);
        if (!$stmt->execute()) {
            throw new Exception("Klaida vykdant užklausą: " . $stmt->error);
        }
        $stmt->close();

        // Įrašome operacijas į user_transactions lentelę
        addTransaction($conn, $fromUserId, $currency, 'debit', $amount);
        addTransaction($conn, $toUserId, $currency, 'credit', $amount);

        // Patvirtiname transakciją
        $conn->commit();
        $conn->autocommit(true);

        return ["status" => "success", "message" => "Pervedimas sėkmingai įvykdytas!"];
    } catch (Exception $e) {
        // Atšaukiame transakciją
        $conn->rollback();
        $conn->autocommit(true);
        return ["status" => "error", "message" => $e->getMessage()];
    }
}
