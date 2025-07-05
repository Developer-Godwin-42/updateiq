<?php
require_once 'includes/database.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';
$token_is_valid = false;

if (!empty($token)) {
    // Validate the token
    $query = "SELECT id FROM users WHERE password_reset_token = ? AND token_expiration > NOW() LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $token_is_valid = true;
    } else {
        $error = "This password reset link is invalid or has expired.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle form submission for new password
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_is_valid) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $post_token = $_POST['token']; // Get token from hidden input

    if ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the password and clear the reset token
        $update_query = "UPDATE users SET password = ?, password_reset_token = NULL, token_expiration = NULL WHERE password_reset_token = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $post_token);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Your password has been successfully updated! You can now log in.";
            $token_is_valid = false; // Prevent the form from showing again
        } else {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="login.php">Proceed to Login</a>
        <?php endif; ?>

        <?php if ($token_is_valid): ?>
        <form method="post" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm New Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>