<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php'; // Make sure this provides $conn (your database connection)

// Initialize variables
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';
$token_is_valid = false;
$user_id_for_reset = null;

error_log("--- DEBUG START: reset-password.php Load (Timestamp: " . date('Y-m-d H:i:s') . ") ---");
error_log("Request Method: " . $_SERVER["REQUEST_METHOD"]);
error_log("Token from URL (raw): " . (isset($_GET['token']) ? $_GET['token'] : 'NOT SET'));
error_log("Token from URL (trimmed): " . $token);


// --- GET Request / Initial Token Validation ---
if (empty($token)) {
    error_log("DEBUG: Token is empty. Redirecting to forgot-password.php.");
    header('Location: forgot-password.php?error=no_token_provided');
    exit();
} elseif (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $error = 'The password reset link is invalid. Please request a new one.';
    error_log("DEBUG: Token format invalid: " . $token);
}

// Only proceed to database validation if the token format is correct
if (empty($error)) {
    // Query the password_resets table to validate the token and its expiration
    // We join with the users table to ensure the user still exists and is active
    $query = "SELECT pr.user_id, u.email
              FROM password_resets pr
              JOIN users u ON pr.user_id = u.id
              WHERE pr.token = ? AND pr.expires_at > NOW() AND u.is_active = TRUE
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt === false) {
        error_log("DEBUG: MySQLi Prepare Error for token validation: " . mysqli_error($conn));
        $error = "An internal error occurred. Please try again later. (Code: PREP_FAIL)";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $token);
        error_log("DEBUG: Executing token validation query with token: " . $token);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("DEBUG: MySQLi Execute Error for token validation: " . mysqli_stmt_error($stmt));
            $error = "An error occurred while validating the link. Please try again. (Code: EXEC_FAIL)";
        } else {
            $result = mysqli_stmt_get_result($stmt);
            $num_rows = mysqli_num_rows($result);
            error_log("DEBUG: Token validation query returned " . $num_rows . " rows.");

            if ($num_rows > 0) {
                $user_data = mysqli_fetch_assoc($result);
                $token_is_valid = true;
                $user_id_for_reset = $user_data['user_id'];
                error_log("DEBUG: Token is VALID. User ID: " . $user_id_for_reset . ", Email: " . $user_data['email']);
            } else {
                $error = "This password reset link is invalid or has expired.";
                error_log("DEBUG: Token is INVALID or EXPIRED. No matching rows found.");
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    error_log("DEBUG: Skipping database token validation due to format error: " . $error);
}

// --- POST Request / Password Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_is_valid && empty($error)) {
    error_log("DEBUG: Handling POST request for password update.");
    $post_token = trim($_POST['token']);

    // Re-validate the token before updating password
    $revalidate_query = "SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at > NOW() LIMIT 1";
    $revalidate_stmt = mysqli_prepare($conn, $revalidate_query);

    if ($revalidate_stmt === false) {
        error_log("DEBUG: MySQLi Prepare Error for POST revalidation: " . mysqli_error($conn));
        $error = "An internal error occurred. Please try again. (Code: REPREP_FAIL)";
    } else {
        mysqli_stmt_bind_param($revalidate_stmt, "s", $post_token);
        error_log("DEBUG: Executing POST revalidation query with token: " . $post_token);
        mysqli_stmt_execute($revalidate_stmt);
        $revalidate_result = mysqli_stmt_get_result($revalidate_stmt);

        if (mysqli_num_rows($revalidate_result) == 0) {
            $error = "This password reset link is invalid or has expired. Please request a new one.";
            $token_is_valid = false;
            error_log("DEBUG: POST revalidation failed. Token invalid or expired.");
        } else {
            $user_revalidated_data = mysqli_fetch_assoc($revalidate_result);
            $user_id_for_reset = $user_revalidated_data['user_id'];
            error_log("DEBUG: POST revalidation SUCCESS. User ID: " . $user_id_for_reset);
        }
        mysqli_stmt_close($revalidate_stmt);
    }

    if (empty($error)) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Invalid request. Please try again. (Code: CSRF_FAIL)";
            error_log("DEBUG: CSRF token mismatch.");
        } else {
            $password = trim($_POST['password']);
            $password_confirm = trim($_POST['password_confirm']);

            if (empty($password) || empty($password_confirm)) {
                $error = "Please fill in all password fields.";
                error_log("DEBUG: Password fields empty.");
            } elseif ($password !== $password_confirm) {
                $error = "Passwords do not match.";
                error_log("DEBUG: Passwords do not match.");
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long.";
                error_log("DEBUG: Password too short.");
            } elseif (!preg_match("/[A-Z]/", $password)) {
                $error = "Password must contain at least one uppercase letter.";
                error_log("DEBUG: Password missing uppercase.");
            } elseif (!preg_match("/[a-z]/", $password)) {
                $error = "Password must contain at least one lowercase letter.";
                error_log("DEBUG: Password missing lowercase.");
            } elseif (!preg_match("/[0-9]/", $password)) {
                $error = "Password must contain at least one number.";
                error_log("DEBUG: Password missing number.");
            }

            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_begin_transaction($conn);

                try {
                    $update_password_query = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
                    $update_password_stmt = mysqli_prepare($conn, $update_password_query);
                    if ($update_password_stmt === false) {
                        throw new Exception("Database error updating password (prep): " . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($update_password_stmt, "si", $hashed_password, $user_id_for_reset);
                    if (!mysqli_stmt_execute($update_password_stmt)) {
                        throw new Exception("Failed to update password (exec): " . mysqli_stmt_error($update_password_stmt));
                    }
                    mysqli_stmt_close($update_password_stmt);

                    $invalidate_token_query = "DELETE FROM password_resets WHERE token = ?";
                    $invalidate_token_stmt = mysqli_prepare($conn, $invalidate_token_query);
                    if ($invalidate_token_stmt === false) {
                        throw new Exception("Database error invalidating token (prep): " . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($invalidate_token_stmt, "s", $post_token);
                    if (!mysqli_stmt_execute($invalidate_token_stmt)) {
                        throw new Exception("Failed to invalidate token (exec): " . mysqli_stmt_error($invalidate_token_stmt));
                    }
                    mysqli_stmt_close($invalidate_token_stmt);

                    mysqli_commit($conn);
                    error_log("DEBUG: Password reset transaction COMMIT for user ID: " . $user_id_for_reset);
                    unset($_SESSION['csrf_token']);
                    $success = "Your password has been successfully updated! You can now log in.";
                    $token_is_valid = false;
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    error_log("DEBUG: Password reset transaction ROLLBACK for user ID {$user_id_for_reset}: " . $e->getMessage());
                    $error = "An error occurred while updating your password. Please try again. (Code: TRANS_FAIL)";
                }
            }
        }
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("DEBUG: New CSRF token generated: " . $_SESSION['csrf_token']);
} else {
    error_log("DEBUG: Existing CSRF token: " . $_SESSION['csrf_token']);
}
error_log("--- DEBUG END: reset-password.php Load ---");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UpdateIQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Your existing CSS styles */
        .password-requirements { font-size: 0.85em; color: #666; margin: 5px 0 15px; }
        .password-requirements ul { margin: 5px 0; padding-left: 20px; }
        .form-group { margin-bottom: 1rem; }
        .form-control { width: 100%; padding: 0.5rem; margin: 0.25rem 0; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .valid { color: #28a745; }
        .valid:before { content: '✓ '; }
        .invalid { color: #dc3545; }
        .invalid:before { content: '✗ '; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { margin-bottom: 1.5rem; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <p><a href="login.php">Proceed to Login</a></p>
        <?php endif; ?>

        <?php
        if ($token_is_valid && empty($success)):
        ?>
        <form method="post" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" id="resetForm" onsubmit="return validateForm()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" class="form-control" required
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                        title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 or more characters">
                <div class="password-requirements">
                    <p>Password must contain:</p>
                    <ul>
                        <li id="length" class="invalid">At least 8 characters</li>
                        <li id="uppercase" class="invalid">At least one uppercase letter</li>
                        <li id="lowercase" class="invalid">At least one lowercase letter</li>
                        <li id="number" class="invalid">At least one number</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm New Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                <div id="password-match" style="color: red; display: none; margin-top: 5px;">Passwords do not match!</div>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirm');
        const lengthRequirement = document.getElementById('length');
        const uppercaseRequirement = document.getElementById('uppercase');
        const lowercaseRequirement = document.getElementById('lowercase');
        const numberRequirement = document.getElementById('number');
        const passwordMatchElement = document.getElementById('password-match');

        function updatePasswordRequirements() {
            const value = passwordInput.value;

            lengthRequirement.classList.toggle('valid', value.length >= 8);
            lengthRequirement.classList.toggle('invalid', value.length < 8);

            uppercaseRequirement.classList.toggle('valid', /[A-Z]/.test(value));
            uppercaseRequirement.classList.toggle('invalid', !/[A-Z]/.test(value));

            lowercaseRequirement.classList.toggle('valid', /[a-z]/.test(value));
            lowercaseRequirement.classList.toggle('invalid', !/[a-z]/.test(value));

            numberRequirement.classList.toggle('valid', /[0-9]/.test(value));
            numberRequirement.classList.toggle('invalid', !/[0-9]/.test(value));

            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            if (passwordInput.value && confirmPasswordInput.value) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    passwordMatchElement.style.display = 'block';
                    return false;
                } else {
                    passwordMatchElement.style.display = 'none';
                    return true;
                }
            } else if (passwordInput.value || confirmPasswordInput.value) {
                return false;
            }
            passwordMatchElement.style.display = 'none';
            return true;
        }

        passwordInput.addEventListener('input', updatePasswordRequirements);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);

        function validateForm() {
            if (!checkPasswordMatch()) {
                alert('Passwords do not match!');
                return false;
            }

            const requirements = document.querySelectorAll('.password-requirements .invalid');
            if (requirements.length > 0) {
                alert('Please ensure your password meets all requirements.');
                return false;
            }

            return true;
        }
        </script>
        <?php endif; ?>
    </div>
</body>
</html>