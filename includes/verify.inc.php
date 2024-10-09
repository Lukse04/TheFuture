
<?php
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    require_once 'includes/dbh.inc.php';

    // Update the user as verified
    $sql = "UPDATE users SET is_verified = 1 WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);

    if ($stmt->execute()) {
        echo "Your email has been successfully verified!";
    } else {
        echo "Verification failed. Invalid or expired token.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "No token provided.";
}
?>
