<?php
// login_process.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Configure error logging
ini_set('log_errors', 1);
$logFile = __DIR__ . '/../logs/login_debug.log';
ini_set('error_log', $logFile);

// Clear previous log file for this request
file_put_contents($logFile, "=== Login Process Started ===\n", FILE_APPEND);

function log_debug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Start the session with secure parameters
log_debug("Starting session...");
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true,
    'cookie_samesite' => 'Lax',  // Changed to Lax for better compatibility
    'cookie_lifetime' => 86400,  // 24 hours
    'gc_maxlifetime' => 86400    // 24 hours
]);

log_debug("Session ID: " . session_id());
log_debug("Session status: " . session_status());

// !!! CRITICAL !!!
// Make sure this path is correct. This is the most likely cause of the white screen.
// It assumes `database.php` is in an 'includes' folder one level above the 'admin' folder.
require_once '../includes/database.php';

// Check if the form was submitted
log_debug("Request method: " . $_SERVER['REQUEST_METHOD']);
log_debug("POST data: " . print_r($_POST, true));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if database connection was successful
    if ($conn->connect_error) {
        $_SESSION['login_error'] = "Database connection failed. Please contact support.";
        header("Location: login.php");
        exit();
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: login.php");
        exit();
    }

    // Prepare a select statement
    $sql = "SELECT id, username, password_hash, role_id FROM users WHERE username = ?";

    // Log the login attempt for debugging
    error_log("Login attempt for username: " . $username);

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("s", $param_username);
        $param_username = $username;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            $stmt->store_result();
            log_debug("User query executed. Found rows: " . $stmt->num_rows);

            // Check if username exists
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id, $username, $hashed_password, $role_id);
                if ($stmt->fetch()) {
                    // Verify password
                    log_debug("Password verification attempt for user: $username");
                if (password_verify($password, $hashed_password)) {
                    log_debug("Password verified successfully");
                        // Password is correct, start a new session
                        session_regenerate_id(); // Security measure
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        
                        // Fetch role name from roles table
                        $role_sql = "SELECT role_name FROM roles WHERE role_id = ?";
                        if ($role_stmt = $conn->prepare($role_sql)) {
                            $role_stmt->bind_param("i", $role_id);
                            $role_stmt->execute();
                            $role_stmt->bind_result($role_name);
                            $role_stmt->fetch();
                            $role_stmt->close();
                            $_SESSION['user_role'] = $role_name;
                        }

                        // Set session variables
                        $_SESSION['last_activity'] = time();
                        
                        log_debug("Session variables set - User ID: " . $_SESSION['user_id'] . ", Username: " . $_SESSION['username']);
                        log_debug("Session data: " . print_r($_SESSION, true));
                        
                        // Debug headers
                        log_debug("Redirecting to dashboard.php");
                        
                        // Clear output buffer to prevent any output before header
                        if (ob_get_level()) ob_end_clean();
                        
                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Password is not valid
                        $_SESSION['login_error'] = "Invalid username or password.";
                    }
                }
            } else {
                // Username doesn't exist
                $_SESSION['login_error'] = "Invalid username or password.";
            }
        } else {
            $_SESSION['login_error'] = "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
    $conn->close();

    // Redirect back to login page with error
    header("Location: login.php");
    exit();
} else {
    // If not a POST request, redirect back to login page
    header("Location: login.php");
    exit();
}
?>