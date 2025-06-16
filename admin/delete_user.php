<?php
// delete_user.php
session_start();
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is an Admin
$user_role = $_SESSION['user_role'] ?? 'Editor';
if ($user_role != 'Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Access denied. Only administrators can delete users.</div>';
    header("Location: dashboard.php"); // Redirect to dashboard or users list with error
    exit();
}

$message = '';

// Check if user ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id_to_delete = (int)$_GET['id'];

    // Prevent an admin from deleting themselves (optional but recommended)
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['message'] = '<div class="alert alert-warning">You cannot delete your own account.</div>';
        header("Location: users.php");
        exit();
    }

    // Prepare a delete statement
    $sql = "DELETE FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id_to_delete);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = '<div class="alert alert-success">User deleted successfully!</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-warning">User not found or already deleted.</div>';
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Error deleting user: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Database error preparing statement: ' . $conn->error . '</div>';
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-warning">No user ID specified for deletion.</div>';
}

$conn->close();
header("Location: users.php"); // Redirect back to the user list
exit();
?>