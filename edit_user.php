
<?php

require_once 'includes/dbh.inc.php';

if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    
    // Use prepared statements to securely fetch the user details
    $query = "SELECT users.usersId, users.usersName, users.usersEmail, users.user_type, user_keys.unique_key, user_keys.user_money
              FROM users
              LEFT JOIN user_keys ON users.usersId = user_keys.user_id
              WHERE users.usersId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Display a form with user details
        echo "<form method='post' action='includes/update_user.php'>
            <input type='hidden' name='usersId' value='{$user['usersId']}'>
            Username: <input type='text' name='usersName' value='{$user['usersName']}'>
            Email: <input type='email' name='usersEmail' value='{$user['usersEmail']}'>
            User Type: <input type='text' name='user_type' value='{$user['user_type']}'>
            Unique Key: <input type='text' name='unique_key' value='{$user['unique_key']}'>
            User Money: <input type='text' name='user_money' value='{$user['user_money']}'>
            <input type='submit' value='Update'>
        </form>";
    } else {
        echo "Error: User not found.";
    }
}

$conn->close();

?>
