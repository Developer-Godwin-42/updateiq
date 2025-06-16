<?php
// edit_menu.php
session_start();
require_once '../includes/database.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage menus)
$user_role = $_SESSION['user_role'] ?? 'Editor';

$message = '';
$menu_item_data = null; // To store data of the menu item being edited

// --- Handle Menu Item Data Fetch (GET request) ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_menu_id = (int)$_GET['id'];

    $sql = "SELECT menu_id, menu_text, menu_link, parent_id, order_priority, is_active FROM menus WHERE menu_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $edit_menu_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $menu_item_data = $result->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger">Menu item not found.</div>';
            $menu_item_data = null; // Ensure no stale data is used
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // If ID is not provided in GET request when page is loaded initially
    $message = '<div class="alert alert-warning">No menu item ID specified for editing.</div>';
}

// --- Handle Update Menu Item Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_menu_item'])) {
    $menu_id_to_update = (int)$_POST['menu_id'];
    $new_menu_text = trim($_POST['menu_text']);
    $new_menu_link = trim($_POST['menu_link']);
    $new_parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $new_order_priority = (int)$_POST['order_priority'];
    $new_is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($new_menu_text) || empty($new_menu_link)) {
        $message = '<div class="alert alert-danger">Menu Text and Link are required.</div>';
    } else {
        // Prevent a menu item from being its own parent, or being a parent of its own child (circular reference)
        // This is a basic check. More robust checks might involve recursive lookup.
        if ($new_parent_id == $menu_id_to_update) {
            $message = '<div class="alert alert-danger">A menu item cannot be its own parent.</div>';
        } else {
            // Prepare an update statement
            $sql_update = "UPDATE menus SET menu_text = ?, menu_link = ?, parent_id = ?, order_priority = ?, is_active = ? WHERE menu_id = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("ssiiii", $new_menu_text, $new_menu_link, $new_parent_id, $new_order_priority, $new_is_active, $menu_id_to_update);

                if ($stmt_update->execute()) {
                    $message = '<div class="alert alert-success">Menu item updated successfully!</div>';
                    // Re-fetch menu item data to display updated info immediately
                    $sql_re_fetch = "SELECT menu_id, menu_text, menu_link, parent_id, order_priority, is_active FROM menus WHERE menu_id = ?";
                    $stmt_re_fetch = $conn->prepare($sql_re_fetch);
                    $stmt_re_fetch->bind_param("i", $menu_id_to_update);
                    $stmt_re_fetch->execute();
                    $result_re_fetch = $stmt_re_fetch->get_result();
                    $menu_item_data = $result_re_fetch->fetch_assoc();
                    $stmt_re_fetch->close();

                } else {
                    $message = '<div class="alert alert-danger">Error updating menu item: ' . $stmt_update->error . '</div>';
                }
                $stmt_update->close();
            } else {
                $message = '<div class="alert alert-danger">Database error preparing update statement: ' . $conn->error . '</div>';
            }
        }
    }
}

// --- Fetch ALL Menu Items for Parent Dropdown (always needed) ---
$all_menu_items = [];
// Select all menu items to populate the 'Parent Menu Item' dropdown, excluding the current item being edited
$sql_all_menus = "SELECT menu_id, menu_text, parent_id FROM menus WHERE menu_id != ? ORDER BY parent_id ASC, order_priority ASC, menu_text ASC";
// This ensures you cannot select the item itself as its parent
if ($stmt_all_menus = $conn->prepare($sql_all_menus)) {
    $stmt_all_menus->bind_param("i", $edit_menu_id); // Exclude the current item from being its own parent
    $stmt_all_menus->execute();
    $result_all_menus = $stmt_all_menus->get_result();
    while ($row = $result_all_menus->fetch_assoc()) {
        $all_menu_items[] = $row;
    }
    $stmt_all_menus->close();
} else {
    $message .= '<div class="alert alert-danger">Error fetching all menu items for parent dropdown: ' . $conn->error . '</div>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ - Edit Menu Item</title>
    <link rel="stylesheet" href="assets/css/admin.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
        <h1>Edit Menu Item</h1>
        <hr>

        <?php echo $message; ?>

        <?php if ($menu_item_data): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Editing Menu: <?php echo htmlspecialchars($menu_item_data['menu_text']); ?> (ID: <?php echo htmlspecialchars($menu_item_data['menu_id']); ?>)</h3>
            </div>
            <div class="card-body">
                <form action="edit_menu.php" method="POST">
                    <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($menu_item_data['menu_id']); ?>">

                    <div class="mb-3">
                        <label for="menu_text" class="form-label">Menu Text</label>
                        <input type="text" class="form-control" id="menu_text" name="menu_text" value="<?php echo htmlspecialchars($menu_item_data['menu_text']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="menu_link" class="form-label">Menu Link (URL)</label>
                        <input type="text" class="form-control" id="menu_link" name="menu_link" value="<?php echo htmlspecialchars($menu_item_data['menu_link']); ?>" required>
                        <small class="form-text text-muted">Example: `/about-us.php` or `https://example.com`</small>
                    </div>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Menu Item (for sub-menu)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">-- No Parent (Top Level) --</option>
                            <?php foreach ($all_menu_items as $item): ?>
                                <option value="<?php echo htmlspecialchars($item['menu_id']); ?>"
                                    <?php echo ($item['menu_id'] == $menu_item_data['parent_id']) ? 'selected' : ''; ?>>
                                    <?php
                                    // Indent for better readability in dropdown
                                    if (!empty($item['parent_id'])) {
                                        echo '&nbsp;&nbsp;&nbsp;&mdash; ';
                                    }
                                    echo htmlspecialchars($item['menu_text']);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select a parent if this is a sub-menu item.</small>
                    </div>
                    <div class="mb-3">
                        <label for="order_priority" class="form-label">Order Priority</label>
                        <input type="number" class="form-control" id="order_priority" name="order_priority" value="<?php echo htmlspecialchars($menu_item_data['order_priority']); ?>" required>
                        <small class="form-text text-muted">Lower numbers appear first (e.g., 0, 1, 2...).</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $menu_item_data['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Is Active?</label>
                    </div>
                    <button type="submit" name="update_menu_item" class="btn btn-primary">Update Menu Item</button>
                    <a href="menus.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        <?php else: ?>
            <p>Please select a menu item to edit from the <a href="menus.php">Menu Management</a> page.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>