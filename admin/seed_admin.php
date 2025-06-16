<?php
// seed_admin.php
// Run this file ONCE to create your initial admin user.
// After successful execution, DELETE this file or move it to a very secure location.

require_once '../includes/database.php'; // Include your database connection

$username = 'admin'; // Choose your desired admin username
$password = 'password123'; // Choose a STRONG password for your admin!
$email = 'admin@yourdomain.com'; // Your admin email

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Get the role_id for 'Admin'
$sql_role = "SELECT role_id FROM roles WHERE role_name = 'Admin'";
$result_role = $conn->query($sql_role);
if ($result_role && $result_role->num_rows > 0) {
    $row_role = $result_role->fetch_assoc();
    $admin_role_id = $row_role['role_id'];

    // Check if admin user already exists to prevent duplicates
    $sql_check = "SELECT user_id FROM users WHERE username = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows == 0) {
            // Prepare an insert statement for the admin user
            $sql_insert = "INSERT INTO users (username, password_hash, email, role_id, is_active) VALUES (?, ?, ?, ?, TRUE)";

            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $stmt_insert->bind_param("sssi", $username, $hashed_password, $email, $admin_role_id);

                if ($stmt_insert->execute()) {
                    echo "Admin user '$username' created successfully with password: '$password'<br>";
                    echo "NOTE: For security, delete this 'seed_admin.php' file immediately after creating the user.";
                } else {
                    echo "Error: Could not create admin user. " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                echo "Error preparing insert statement: " . $conn->error;
            }
        } else {
            echo "Admin user '$username' already exists.<br>";
            echo "NOTE: For security, delete this 'seed_admin.php' file.";
        }
        $stmt_check->close();
    } else {
        echo "Error preparing check statement: " . $conn->error;
    }

} else {
    echo "Error: 'Admin' role not found in the 'roles' table. Please ensure you ran the database SQL first.";
}

$conn->close();
?>