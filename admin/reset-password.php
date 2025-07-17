<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php';

// Initialize variables
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';
$token_is_valid = false;

// Check if token is provided
if (empty($token)) {
    header('Location: forgot-password.php?error=no_token');
    exit();
}

// Validate token format (basic check for a 64-character hex string)
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $error = 'Invalid password reset token format.';
}

// Validate the token
$query = "SELECT id, email FROM users WHERE password_reset_token = ? AND token_expiration > NOW() LIMIT 1";
$stmt = mysqli_prepare($public_conn, $query);

if ($stmt === false) {
    error_log("MySQLi Prepare Error: " . mysqli_error($public_conn));
    $error = "An error occurred. Please try again later.";
} else {
    mysqli_stmt_bind_param($stmt, "s", $token);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        $error = "An error occurred. Please try again.";
    } else {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $token_is_valid = true;
            $user_email = $user['email'];
        } else {
            $error = "This password reset link is invalid or has expired.";
        }
    }
}

// Handle form submission for new password
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_is_valid) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $password = trim($_POST['password']);
        $password_confirm = trim($_POST['password_confirm']);
        $post_token = trim($_POST['token']);

        // Validate passwords
        if (empty($password) || empty($password_confirm)) {
            $error = "Please fill in all fields.";
        } elseif ($password !== $password_confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match("/[a-z]/", $password)) {
            $error = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match("/[0-9]/", $password)) {
            $error = "Password must contain at least one number.";
        }
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Start transaction
            mysqli_begin_transaction($public_conn);
            
            try {
                // Update the password and clear the reset token
                $update_query = "UPDATE users SET password = ?, password_reset_token = NULL, token_expiration = NULL, updated_at = NOW() WHERE password_reset_token = ?";
                $update_stmt = mysqli_prepare($public_conn, $update_query);
                
                if ($update_stmt === false) {
                    throw new Exception("Database error: " . mysqli_error($public_conn));
                }
                
                mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $post_token);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Failed to update password: " . mysqli_stmt_error($update_stmt));
                }
                
                // If we got here, everything was successful
                mysqli_commit($public_conn);
                
                // Log the password change
                error_log("Password reset successful for user: " . ($user_email ?? 'unknown'));
                
                // Clear the CSRF token
                unset($_SESSION['csrf_token']);
                
                $success = "Your password has been successfully updated! You can now log in.";
                $token_is_valid = false; // Prevent the form from showing again
                
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($public_conn);
                error_log("Password reset error: " . $e->getMessage());
                $error = "An error occurred while updating your password. Please try again.";
            }
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UpdateIQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .password-requirements {
            font-size: 0.85em;
            color: #666;
            margin: 5px 0 15px;
        }
        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            margin: 0.25rem 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
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
            <a href="login.php">Proceed to Login</a>
        <?php endif; ?>

        <?php if ($token_is_valid): ?>
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
                <div id="password-match" style="color: red; display: none;">Passwords do not match!</div>
            </div>
            
            <button type="submit" class="btn">Reset Password</button>
        </form>
        
        <script>
        // Password validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('password_confirm');
        const form = document.getElementById('resetForm');
        
        // Validate password on input
        password.addEventListener('input', function() {
            const value = this.value;
            
            // Validate length
            document.getElementById('length').classList.toggle('valid', value.length >= 8);
            document.getElementById('length').classList.toggle('invalid', value.length < 8);
            
            // Validate uppercase letters
            document.getElementById('uppercase').classList.toggle('valid', /[A-Z]/.test(value));
            document.getElementById('uppercase').classList.toggle('invalid', !/[A-Z]/.test(value));
            
            // Validate lowercase letters
            document.getElementById('lowercase').classList.toggle('valid', /[a-z]/.test(value));
            document.getElementById('lowercase').classList.toggle('invalid', !/[a-z]/.test(value));
            
            // Validate numbers
            document.getElementById('number').classList.toggle('valid', /[0-9]/.test(value));
            document.getElementById('number').classList.toggle('invalid', !/[0-9]/.test(value));
            
            // Check password match
            checkPasswordMatch();
        });
        
        // Check password confirmation
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const matchElement = document.getElementById('password-match');
            if (password.value && confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    matchElement.style.display = 'block';
                    return false;
                } else {
                    matchElement.style.display = 'none';
                    return true;
                }
            }
            return false;
        }
        
        // Form validation
        function validateForm() {
            if (!checkPasswordMatch()) {
                alert('Passwords do not match!');
                return false;
            }
            
            // Check if password meets all requirements
            const requirements = document.querySelectorAll('.password-requirements .invalid');
            if (requirements.length > 0) {
                alert('Please ensure your password meets all requirements.');
                return false;
            }
            
            return true;
        }
        </script>
        
        <style>
        .valid {
            color: #28a745;
        }
        .valid:before {
            content: '✓ ';
        }
        .invalid {
            color: #dc3545;
        }
        .invalid:before {
            content: '✗ ';
        }
        </style>
        <?php endif; ?>
    </div>
</body>
</html>