<?php
// test_db.php
require_once '../includes/database.php';

echo "<h2>Database Connection Test</h2>";

// Test database connection
if ($conn->connect_error) {
    die("<p style='color:red;'>Connection failed: " . $conn->connect_error . "</p>");
} else {
    echo "<p style='color:green;'>✓ Database connection successful!</p>";
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ Users table exists</p>";
    
    // Show users table structure
    echo "<h3>Users Table Structure:</h3>";
    $columns = $conn->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show existing users
    echo "<h3>Existing Users:</h3>";
    $users = $conn->query("SELECT id, username, email, role_id, is_active FROM users");
    if ($users->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role ID</th><th>Active</th></tr>";
        while($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['role_id'] . "</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in the database.</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Users table does not exist!</p>";
}

// Check roles table
$result = $conn->query("SHOW TABLES LIKE 'roles'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ Roles table exists</p>";
    
    // Show existing roles
    echo "<h3>Existing Roles:</h3>";
    $roles = $conn->query("SELECT * FROM roles");
    if ($roles->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Role ID</th><th>Role Name</th><th>Description</th></tr>";
        while($role = $roles->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $role['role_id'] . "</td>";
            echo "<td>" . htmlspecialchars($role['role_name']) . "</td>";
            echo "<td>" . htmlspecialchars($role['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No roles found in the database.</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Roles table does not exist!</p>";
}

$conn->close();
?>
