<?php
// login_process.php

// Start the session
session_start();

// Include the database connection file
require_once '../includes/database.php'; // Adjust path if your structure is different

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: login.php");
        exit();
    }

    // Prepare a select statement
    $sql = "SELECT user_id, username, password_hash, role_id FROM users WHERE username = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("s", $param_username);
        $param_username = $username;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if username exists, if yes then verify password
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($user_id, $username, $hashed_password, $role_id);
                if ($stmt->fetch()) {
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
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

                        // Redirect to dashboard page
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Password is not valid
                        $_SESSION['login_error'] = "Invalid username or password.";
                        header("Location: login.php");
                        exit();
                    }
                }
            } else {
                // Username doesn't exist
                $_SESSION['login_error'] = "Invalid username or password.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Oops! Something went wrong. Please try again later.";
            header("Location: login.php");
            exit();
        }

        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
} else {
    // If not a POST request, redirect back to login page
    header("Location: login.php");
    exit();
}
?>