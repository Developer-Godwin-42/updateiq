<?php

// Step 1: Include PHPMailer classes
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once 'includes/config.php';

// Step 2: Now include your configuration files that USE the classes
// Make sure these files are defined after PHPMailer classes are available
require_once 'includes/database.php'; // Assumes $public_conn is available here
// require_once 'includes/config.php'; // Assumes SMTP_HOST, SMTP_USERNAME, etc. are defined here

// Step 3: Now you can use the 'use' statements for the rest of the script
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


$message = ''; // Message to display to the user

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate email input
    $email = trim($_POST['email']); // Remove leading/trailing whitespace

    // Basic server-side email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // Use mysqli_real_escape_string ONLY if you are not using prepared statements.
        // With prepared statements, it's not strictly necessary for parameters,
        // as parameters are handled by the driver. However, `trim` is still good.
        $email = mysqli_real_escape_string($public_conn, $email);

        // Check if the email exists in the database
        $query = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($public_conn, $query);

        if ($stmt === false) {
            // Log database preparation error
            error_log("MySQLi Prepare Error (SELECT): " . mysqli_error($public_conn));
            // Show a generic error to the user
            $message = "An unexpected error occurred. Please try again later.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                // Email found, proceed with token generation and email sending
                $token = bin2hex(random_bytes(32)); // Generate a strong, random token
                $expiration = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour

                $user = mysqli_fetch_assoc($result);
                $user_id = $user['id'];

                // Update user record with token
                $update_query = "UPDATE users SET password_reset_token = ?, token_expiration = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($public_conn, $update_query);

                if ($update_stmt === false) {
                    // Log database update preparation error
                    error_log("MySQLi Prepare Error (UPDATE): " . mysqli_error($public_conn));
                    // Show a generic error to the user
                    $message = "An unexpected error occurred. Please try again later.";
                } else {
                    mysqli_stmt_bind_param($update_stmt, "ssi", $token, $expiration, $user_id);
                    $update_success = mysqli_stmt_execute($update_stmt);

                    if ($update_success) {  
                        // Send the password reset email
                        $mail = new PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();

                            //   // UNCOMMENT THESE TWO LINES
                            //   $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER; // Set to 2
                            //   $mail->Debugoutput = 'html'; // Display in browser
                            $mail->SMTPDebug = 2; // Enable verbose debug output (level 2)
                            $mail->Debugoutput = 'html';
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'noreplysbbs@gmail.com';
                            $mail->Password   = 'ywlo bmup lryl jobz'; // Your App Password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            // Recipients
                            $mail->setFrom('noreplysbbs@gmail.com', 'UpdateIQ');
                            $mail->addAddress($email); // <-- THE FIX: Send to the user's email

                            // Content
                            $reset_link = "http://localhost:8000/admin/reset-password.php?token=" . urlencode($token);
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request for UpdateIQ';
                            $mail->Body    = "
        Hello,<br><br>
        You recently requested to reset your password for your UpdateIQ account.
        <br><br>
        Please click the link below to reset your password. This link is valid for 1 hour.<br><br>
        <a href='{$reset_link}'>Reset Password</a><br><br>
        If you did not request this, please ignore this email.
        <br><br>
        Thanks,<br>
        The UpdateIQ Team
    ";
                            $mail->AltBody = "Hello,\n\nYou recently requested to reset your password for your UpdateIQ account.\n\nPlease copy and paste the following link into your browser to reset your password. This link is valid for 1 hour:\n\n{$reset_link}\n\nIf you did not request this, please ignore this email.\n\nThanks,\nThe UpdateIQ Team";

                            $mail->send();
                        } catch (Exception $e) {
                            // Log the error for debugging, but show a generic message to the user
                            error_log("PHPMailer Error (Forgot Password): {$mail->ErrorInfo}");
                            // The generic message set at the end of the script will handle user feedback
                        }
                    } else {
                        // Log database execution error for the update
                        error_log("MySQLi Execute Error (UPDATE): " . mysqli_error($public_conn));
                        $message = "An unexpected error occurred. Please try again later.";
                    }
                    mysqli_stmt_close($update_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    // IMPORTANT: Always show a generic message to prevent email enumeration attacks.
    // This message is shown regardless of whether the email was found or the email sending failed.
    // This prevents an attacker from knowing if an email exists in your system.
    $message = "If an account with that email address exists, we have sent instructions to reset your password.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UpdateIQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f9fafb;
            color: var(--gray-700);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            width: 100%;
            max-width: 28rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .auth-header {
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--gray-200);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: inline-block;
            text-decoration: none;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
        }

        .auth-content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        input[type="email"] {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--primary);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }

        .auth-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .illustration {
            width: 100%;
            max-width: 200px;
            margin: 0 auto 1.5rem;
            display: block;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo">UpdateIQ</a>
            <h1>Reset your password</h1>
            <p>Enter your email and we'll send you a link to reset your password.</p>
        </div>

        <div class="auth-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="post" action="forgot-password.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        placeholder="you@example.com"
                        required
                        autofocus>
                </div>

                <button type="submit" class="btn">Send Reset Link</button>

                <div class="auth-footer">
                    Remember your password?
                    <a href="login.php" class="auth-link">Sign in</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = 'Sending...';
                    }
                });
            }
        });
    </script>
</body>

</html>