
<?php
if (isset($_POST['token']) && isset($_POST['password'])) {
    require_once 'dbh.inc.php';

    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $sql = "UPDATE users SET userspwd = ?, reset_token = NULL WHERE reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $hashed_password, $token);
    
    if ($stmt->execute()) {
        header("Location: ../reset_form.php?update=success");
    } else {
        header("Location: ../reset_form.php?update=failed");
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../reset_form.php?update=failed");
}
?>
