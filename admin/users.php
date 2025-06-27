<?php
// users.php
$page_title = 'User Management';
session_start();
require_once '../includes/database.php'; // Include the database connection
require_once 'includes/header.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Check if the logged-in user is an Admin
$user_role = $_SESSION['user_role'] ?? 'Editor'; // Default to editor if not set
if ($user_role != 'Admin') {
    // If not an admin, redirect to dashboard or show an error
    header("Location: dashboard.php");
    exit();
}

$message = ''; // To store success or error messages

// --- Handle Add User Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_email = trim($_POST['email']);
    $new_role_id = (int)$_POST['role_id']; // Cast to integer for security

    // Basic validation
    if (empty($new_username) || empty($new_password) || empty($new_email) || empty($new_role_id)) {
        $message = '<div class="alert alert-danger">All fields are required to add a user.</div>';
    } else {
        // Hash the password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("ss", $new_username, $new_email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-danger">Username or Email already exists. Please choose another.</div>';
            } else {
                // Prepare an insert statement
                $sql = "INSERT INTO users (username, password_hash, email, role_id) VALUES (?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("sssi", $new_username, $hashed_password, $new_email, $new_role_id);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">User added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error adding user: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Database error preparing statement: ' . $conn->error . '</div>';
                }
            }
            $stmt_check->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing check statement: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Users and Roles for Display ---
$users = [];
$sql_users = "SELECT u.user_id, u.username, u.email, r.role_name, u.is_active FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id DESC";
$result_users = $conn->query($sql_users);
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $message = '<div class="alert alert-danger">Error fetching users: ' . $conn->error . '</div>';
}

$roles = [];
$sql_roles = "SELECT role_id, role_name FROM roles ORDER BY role_name ASC";
$result_roles = $conn->query($sql_roles);
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
} else {
    $message = '<div class="alert alert-danger">Error fetching roles: ' . $conn->error . '</div>';
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- <link rel="stylesheet" href="../assets/css/global.css"> -->

</head>
<body>
    

    <div class="container mt-4">
    
        <?php echo $message; // Display success/error messages ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New User</h3>
            </div>
            <div class="card-body">
                <form action="users.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">Select a role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Existing Users</h3>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p>No users found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                        <td>
                                            <?php echo $user['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?>
                                        </td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-info me-1">Edit</a>
                                            <a href="delete_user.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>