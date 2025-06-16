<?php
// edit_user.php
session_start();
require_once '../includes/database.php';
require_once '../includes/header.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is an Admin
$user_role = $_SESSION['user_role'] ?? 'Editor';
if ($user_role != 'Admin') {
    header("Location: dashboard.php"); // Or show an "Access Denied" message
    exit();
}

$message = '';
$user_data = null; // To store data of the user being edited

// --- Handle User Data Fetch (GET request) ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_user_id = (int)$_GET['id'];

    $sql = "SELECT u.user_id, u.username, u.email, u.role_id, r.role_name, u.is_active FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $edit_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger">User not found.</div>';
            $user_data = null; // Ensure no stale data is used
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // If ID is not provided in GET request when page is loaded initially
    $message = '<div class="alert alert-warning">No user ID specified for editing.</div>';
}

// --- Handle Update User Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_role_id = (int)$_POST['role_id'];
    $new_password = trim($_POST['password']); // Optional: for changing password
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($new_username) || empty($new_email) || empty($new_role_id)) {
        $message = '<div class="alert alert-danger">Username, Email, and Role are required.</div>';
    } else {
        // Build the SQL query dynamically based on whether password is being updated
        $sql_update = "UPDATE users SET username = ?, email = ?, role_id = ?, is_active = ?";
        $params = "ssii";
        $values = [$new_username, $new_email, $new_role_id, $is_active];

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash = ?";
            $params .= "s";
            $values[] = $hashed_password;
        }

        $sql_update .= " WHERE user_id = ?";
        $params .= "i";
        $values[] = $user_id_to_update;

        // Check for duplicate username/email (excluding the current user being edited)
        $check_sql = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("ssi", $new_username, $new_email, $user_id_to_update);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-danger">Username or Email already exists for another user.</div>';
            } else {
                if ($stmt_update = $conn->prepare($sql_update)) {
                    // Use call_user_func_array for binding parameters dynamically
                    $stmt_update->bind_param($params, ...$values);

                    if ($stmt_update->execute()) {
                        $message = '<div class="alert alert-success">User updated successfully!</div>';
                        // Re-fetch user data to display updated info immediately
                        $sql_re_fetch = "SELECT u.user_id, u.username, u.email, u.role_id, r.role_name, u.is_active FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
                        $stmt_re_fetch = $conn->prepare($sql_re_fetch);
                        $stmt_re_fetch->bind_param("i", $user_id_to_update);
                        $stmt_re_fetch->execute();
                        $result_re_fetch = $stmt_re_fetch->get_result();
                        $user_data = $result_re_fetch->fetch_assoc();
                        $stmt_re_fetch->close();

                    } else {
                        $message = '<div class="alert alert-danger">Error updating user: ' . $stmt_update->error . '</div>';
                    }
                    $stmt_update->close();
                } else {
                    $message = '<div class="alert alert-danger">Database error preparing update statement: ' . $conn->error . '</div>';
                }
            }
            $stmt_check->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing check statement: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Roles for Dropdown (always needed) ---
$roles = [];
$sql_roles = "SELECT role_id, role_name FROM roles ORDER BY role_name ASC";
$result_roles = $conn->query($sql_roles);
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ - Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <!-- <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">CMS Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="users.php">User Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menus.php">Menu Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php">Gallery Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog Management</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav> -->

    <div class="container mt-4">
        <h1>Edit User</h1>
        <hr>

        <?php echo $message; ?>

        <?php if ($user_data): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Editing User: <?php echo htmlspecialchars($user_data['username']); ?> (ID: <?php echo htmlspecialchars($user_data['user_id']); ?>)</h3>
            </div>
            <div class="card-body">
                <form action="edit_user.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['user_id']); ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Enter a new password only if you want to change it.</small>
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>"
                                    <?php echo ($role['role_id'] == $user_data['role_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $user_data['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Is Active?</label>
                    </div>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        <?php else: ?>
            <p>Please select a user to edit from the <a href="users.php">User Management</a> page.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>