<?php
// create_admin.php
require_once '../includes/database.php';

// Check if admin user already exists
$check_sql = "SELECT id FROM users WHERE username = 'admin' LIMIT 1";
$result = $conn->query($check_sql);

if ($result && $result->num_rows > 0) {
    die("Admin user already exists. No changes were made.\n");
}

// Create admin user with hashed password
$username = 'admin';
$password = 'Admin@123'; // Change this to a strong password in production
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@example.com';

// First, make sure the admin role exists
$conn->query("INSERT IGNORE INTO roles (role_name, description) VALUES ('Admin', 'Full administrative access')");

// Get the admin role ID
$role_result = $conn->query("SELECT role_id FROM roles WHERE role_name = 'Admin' LIMIT 1");
$role_row = $role_result->fetch_assoc();
$role_id = $role_row['role_id'];

// Insert admin user
$sql = "INSERT INTO users (username, password_hash, email, role_id, is_active) 
        VALUES (?, ?, ?, ?, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $username, $hashed_password, $email, $role_id);

if ($stmt->execute()) {
    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: Admin@123\n";
    echo "\nIMPORTANT: Change this password immediately after first login!\n";
} else {
    echo "Error creating admin user: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
