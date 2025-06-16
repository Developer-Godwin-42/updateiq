<?php
// menus.php
$page_title = 'Menu Management';
session_start();
require_once '../includes/database.php';
require_once 'includes/header.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage menus too, so no specific admin check needed here)
$user_role = $_SESSION['user_role'] ?? 'Editor';

$message = ''; // To store success or error messages

// --- Handle Add Menu Item Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu_item'])) {
    $menu_text = trim($_POST['menu_text']);
    $menu_link = trim($_POST['menu_link']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $order_priority = (int)$_POST['order_priority'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($menu_text) || empty($menu_link)) {
        $message = '<div class="alert alert-danger">Menu Text and Link are required.</div>';
    } else {
        // Prepare an insert statement
        $sql = "INSERT INTO menus (menu_text, menu_link, parent_id, order_priority, is_active) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssiii", $menu_text, $menu_link, $parent_id, $order_priority, $is_active);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Menu item added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error adding menu item: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing statement: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Menu Items for Display (Hierarchical Order) ---
$menu_items = [];
// Select all menu items and their parent's text for display
$sql_menus = "SELECT m1.menu_id, m1.menu_text, m1.menu_link, m1.parent_id, m2.menu_text AS parent_text, m1.order_priority, m1.is_active FROM menus m1 LEFT JOIN menus m2 ON m1.parent_id = m2.menu_id ORDER BY m1.parent_id ASC, m1.order_priority ASC, m1.menu_text ASC";
$result_menus = $conn->query($sql_menus);
if ($result_menus) {
    while ($row = $result_menus->fetch_assoc()) {
        $menu_items[] = $row;
    }
} else {
    $message = '<div class="alert alert-danger">Error fetching menu items: ' . $conn->error . '</div>';
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
                    <?php if ($user_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">User Management</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="menus.php">Menu Management</a>
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

        <?php echo $message; // Display success/error messages ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Menu Item</h3>
            </div>
            <div class="card-body">
                <form action="menus.php" method="POST">
                    <div class="mb-3">
                        <label for="menu_text" class="form-label">Menu Text</label>
                        <input type="text" class="form-control" id="menu_text" name="menu_text" required>
                    </div>
                    <div class="mb-3">
                        <label for="menu_link" class="form-label">Menu Link (URL)</label>
                        <input type="text" class="form-control" id="menu_link" name="menu_link" placeholder="/about-us.php" required>
                        <small class="form-text text-muted">Example: `/about-us.php` or `https://example.com`</small>
                    </div>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Menu Item (for sub-menu)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">-- No Parent (Top Level) --</option>
                            <?php foreach ($menu_items as $item): ?>
                                <?php if (empty($item['parent_id'])): // Only allow top-level items as parents initially ?>
                                    <option value="<?php echo htmlspecialchars($item['menu_id']); ?>">
                                        <?php echo htmlspecialchars($item['menu_text']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select a parent if this is a sub-menu item.</small>
                    </div>
                    <div class="mb-3">
                        <label for="order_priority" class="form-label">Order Priority</label>
                        <input type="number" class="form-control" id="order_priority" name="order_priority" value="0" required>
                        <small class="form-text text-muted">Lower numbers appear first (e.g., 0, 1, 2...).</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Is Active?</label>
                    </div>
                    <button type="submit" name="add_menu_item" class="btn btn-primary">Add Menu Item</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Existing Menu Items</h3>
            </div>
            <div class="card-body">
                <?php if (empty($menu_items)): ?>
                    <p>No menu items found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Menu Text</th>
                                    <th>Link</th>
                                    <th>Parent</th>
                                    <th>Order</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['menu_id']); ?></td>
                                        <td>
                                            <?php
                                            // Indent sub-menus for better readability
                                            if (!empty($item['parent_id'])) {
                                                echo '&mdash; '; // Em dash for sub-items
                                            }
                                            echo htmlspecialchars($item['menu_text']);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['menu_link']); ?></td>
                                        <td><?php echo htmlspecialchars($item['parent_text'] ?? 'None'); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_priority']); ?></td>
                                        <td>
                                            <?php echo $item['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?>
                                        </td>
                                        <td>
                                            <a href="edit_menu.php?id=<?php echo htmlspecialchars($item['menu_id']); ?>" class="btn btn-sm btn-info me-1">Edit</a>
                                            <a href="delete_menu.php?id=<?php echo htmlspecialchars($item['menu_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this menu item?');">Delete</a>
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