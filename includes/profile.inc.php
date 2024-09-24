
<?php

session_start();

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("CSRF token validation failed");
}

require_once 'dbh.inc.php';

if (isset($_POST['update_profile'])) {
    $userId = $_SESSION['userid'];
    $newUsername = $_POST['new_username'];
    $newEmail = $_POST['new_email'];
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];

    // Validate email domain is "gmail.com"
    if (strpos($newEmail, 'gmail.com') !== false) {
        // Prepared statement for checking if the new username exists
        $stmt = $conn->prepare("SELECT usersName FROM users WHERE usersName = ? AND usersId != ?");
        $stmt->bind_param('si', $newUsername, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Error: Username already exists for another user.";
        } else {
            // Retrieve the current hashed password
            $stmt = $conn->prepare("SELECT userspwd FROM users WHERE usersId = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $currentHashedPassword = $row['userspwd'];

                // Check if the old password is correct
                if (password_verify($oldPassword, $currentHashedPassword)) {
                    // Hash the new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update the user details
                    $stmt = $conn->prepare("UPDATE users SET usersName = ?, usersEmail = ?, userspwd = ? WHERE usersId = ?");
                    $stmt->bind_param('sssi', $newUsername, $newEmail, $hashedPassword, $userId);
                    
                    if ($stmt->execute()) {
                        // Update session username
                        $_SESSION['username'] = $newUsername;
                        echo "Profile updated successfully";
                    } else {
                        echo "Error updating profile: " . $conn->error;
                    }
                } else {
                    echo "Error: Old password is incorrect.";
                }
            } else {
                echo "Error: User not found.";
            }
        }
    } else {
        echo "Error: Please enter a valid Gmail address.";
    }
}

$conn->close();

?>
