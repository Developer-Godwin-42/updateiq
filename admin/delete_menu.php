<?php
// delete_menu.php
session_start();
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can delete menus too, but admins often have final say)
// For simplicity, we'll allow editors for now, but you can restrict this to Admin only.
$user_role = $_SESSION['user_role'] ?? 'Editor';
// If you want ONLY admins to delete menus, uncomment this:
/*
if ($user_role != 'Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Access denied. Only administrators can delete menu items.</div>';
    header("Location: menus.php"); // Redirect to menus list with error
    exit();
}
*/

$message = '';

// Check if menu ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $menu_id_to_delete = (int)$_GET['id'];

    // Check if this menu item has any children (sub-menus)
    // If it does, prevent deletion unless children are handled (e.g., re-assigned or deleted)
    $check_children_sql = "SELECT menu_id FROM menus WHERE parent_id = ?";
    if ($stmt_check_children = $conn->prepare($check_children_sql)) {
        $stmt_check_children->bind_param("i", $menu_id_to_delete);
        $stmt_check_children->execute();
        $stmt_check_children->store_result();

        if ($stmt_check_children->num_rows > 0) {
            $_SESSION['message'] = '<div class="alert alert-danger">Cannot delete this menu item because it has sub-menu items. Please delete or re-assign its children first.</div>';
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM menus WHERE menu_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $menu_id_to_delete);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $_SESSION['message'] = '<div class="alert alert-success">Menu item deleted successfully!</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-warning">Menu item not found or already deleted.</div>';
                    }
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error deleting menu item: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Database error preparing statement: ' . $conn->error . '</div>';
            }
        }
        $stmt_check_children->close();
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Database error checking for children: ' . $conn->error . '</div>';
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-warning">No menu ID specified for deletion.</div>';
}

$conn->close();
header("Location: menus.php"); // Redirect back to the menu list
exit();
?>