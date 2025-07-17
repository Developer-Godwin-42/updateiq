<?php
// FINAL WORKING PHP CODE

// Use statements should be at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include necessary files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once 'includes/config.php';
require_once 'includes/database.php';

$message = '';
$message_type = 'info'; // To control alert color

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'danger';
    } else {
        $stmt = $public_conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // For security, we show a generic message whether the email is found or not.
        $message = "If an account exists for this email, a reset link has been sent.";
        $message_type = 'success';

        if ($result->num_rows > 0) {
            // Email was found, so we proceed to generate a token and send the email.
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $token = bin2hex(random_bytes(32));
            $expiration = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour

            $update_stmt = $public_conn->prepare("UPDATE users SET password_reset_token = ?, token_expiration = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $token, $expiration, $user_id);

            if ($update_stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port       = SMTP_PORT;

                    // Recipients
                    $mail->setFrom(SMTP_USERNAME, 'UpdateIQ');
                    $mail->addAddress($email);

                    // Email Content
                    $reset_link = "http://localhost:8000/admin/reset-password.php?token=" . urlencode($token);
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request for UpdateIQ';
                    $mail->Body    = "Hello,<br><br>Click the link below to reset your password. This link is valid for 1 hour.<br><br><a href='{$reset_link}'>Reset Password</a><br><br>If you did not request this, please ignore this email.";

                    $mail->send();
                } catch (Exception $e) {
                    // Silently log the error for the admin, but don't show the user.
                    error_log("PHPMailer Error: {$mail->ErrorInfo}");
                }
            }
        }
    }
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

        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* ADD THESE NEW STYLES */
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
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
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
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