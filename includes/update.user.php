<?php

include 'dbh.inc.php';



// In update_user.php
$userId = $_POST['usersId'];
$newUsername = $_POST['usersName'];
$newEmail = $_POST['usersEmail'];
$newUserType = $_POST['user_type'];
$newFullName = $_POST['unique_key'];
$newAge = $_POST['user_money'];






// Update users table
$queryUsers = "UPDATE users SET usersName=?, usersEmail=?, user_type=? WHERE usersId=?";
$stmtUsers = mysqli_prepare($conn, $queryUsers);
mysqli_stmt_bind_param($stmtUsers, "sssi", $newUsername, $newEmail, $newUserType, $userId);
mysqli_stmt_execute($stmtUsers);
mysqli_stmt_close($stmtUsers);

// Update user_keys table
$queryDetails = "UPDATE user_keys SET unique_key=?, user_money=? WHERE user_id=?";
$stmtDetails = mysqli_prepare($conn, $queryDetails);
mysqli_stmt_bind_param($stmtDetails, "ssi", $newFullName, $newAge, $userId);
mysqli_stmt_execute($stmtDetails);
mysqli_stmt_close($stmtDetails);












/*





// Validate and sanitize input as needed

$query = "UPDATE users SET usersName='$newUsername', usersEmail='$newEmail', user_type='$newUserType' WHERE usersId=$userId";
mysqli_query($conn, $query);









// Assuming you have a database connection established

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission

    $userId = $_POST['usersId'];
    $newUsername = $_POST['usersName'];
    $newEmail = $_POST['userEmail'];
    $newUserType = $_POST['user_type'];

    // Validate and sanitize input as needed

    $query = "UPDATE users SET usersName='$newUsername', usersEmail='$newEmail', user_type='$newUserType' WHERE usersId=$userId";

    if (mysqli_query($conn, $query)) {
        echo "User updated successfully!";
    } else {
        echo "Error updating user: " . mysqli_error($conn);
    }
} else {
    // If someone tries to access this page directly without a POST request
    echo "Invalid request";
}








// Assuming you have a database connection established

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission

    $userId = $_POST['usersId'];
    $newUsername = $_POST['usersName'];
    $newEmail = $_POST['usersEmail'];
    $newUserType = $_POST['user_type'];

    // Validate and sanitize input as needed

    // Using prepared statements to prevent SQL injection
    $query = "UPDATE users SET usersName=?, usersEmail=?, user_type=? WHERE usersId=?";
    
    // Prepare the statement
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, "sssi", $newUsername, $newEmail, $newUserType, $userId);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            echo "User updated successfully!";
        } else {
            echo "Error updating user: " . mysqli_stmt_error($stmt);
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($conn);
    }
} else {
    // If someone tries to access this page directly without a POST request
    echo "Invalid request";
}


*/


?>