<?php


// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header("Location: app/main.php");
} else {
    // Redirect to login page
    header("Location: views/login.php");
}

// Exit to prevent any additional code execution
exit();
?>
