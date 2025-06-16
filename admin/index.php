<?php
// Start session management
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
} else {
    // If logged in, redirect to the dashboard
    header("Location: dashboard.php");
    exit();
}
?>