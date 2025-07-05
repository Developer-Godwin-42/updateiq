<?php
// admin/delete_food_category.php
session_start();
require_once '../includes/database.php'; // Correct path to your main database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert_message'] = 'Authentication required to delete food menu categories.';
    $_SESSION['alert_type'] = 'error';
    header("Location: login.php");
    exit();
}

// User role check (Editors can delete categories, but Admins might have final say)
// For now, allowing Editors. You can restrict to 'Admin' if needed.
$user_role = $_SESSION['user_role'] ?? 'Editor';
// if ($user_role != 'Admin') {
//     $_SESSION['alert_message'] = 'Access denied. Only administrators can delete food menu categories.';
//     $_SESSION['alert_type'] = 'error';
//     header("Location: food_menu.php");
//     exit();
// }

// Check if category ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $category_id_to_delete = (int)$_GET['id'];

    // Check for associated food menu items before attempting to delete category
    $check_items_sql = "SELECT COUNT(item_id) AS item_count FROM food_menu_items WHERE category_id = ?";
    if ($stmt_check_items = $conn->prepare($check_items_sql)) {
        $stmt_check_items->bind_param("i", $category_id_to_delete);
        $stmt_check_items->execute();
        $result_item_count = $stmt_check_items->get_result();
        $item_count_row = $result_item_count->fetch_assoc();
        $stmt_check_items->close();

        if ($item_count_row['item_count'] > 0) {
            $_SESSION['alert_message'] = 'Cannot delete this category because it has ' . $item_count_row['item_count'] . ' associated food menu item(s). Please delete or re-assign items first.';
            $_SESSION['alert_type'] = 'warning';
        } else {
            // Prepare a delete statement for the category
            $sql_delete_db = "DELETE FROM food_menu_categories WHERE category_id = ?";
            if ($stmt_delete_db = $conn->prepare($sql_delete_db)) {
                $stmt_delete_db->bind_param("i", $category_id_to_delete);

                if ($stmt_delete_db->execute()) {
                    if ($stmt_delete_db->affected_rows > 0) {
                        $_SESSION['alert_message'] = 'Food menu category deleted successfully!';
                        $_SESSION['alert_type'] = 'success';
                    } else {
                        $_SESSION['alert_message'] = 'Food menu category not found in database or already deleted.';
                        $_SESSION['alert_type'] = 'warning';
                    }
                } else {
                    $_SESSION['alert_message'] = 'Error deleting food menu category from database: ' . $stmt_delete_db->error;
                    $_SESSION['alert_type'] = 'error';
                }
                $stmt_delete_db->close();
            } else {
                $_SESSION['alert_message'] = 'Database error preparing delete statement: ' . $conn->error;
                $_SESSION['alert_type'] = 'error';
            }
        }
    } else {
        $_SESSION['alert_message'] = 'Database error checking for associated items: ' . $conn->error;
        $_SESSION['alert_type'] = 'error';
    }
} else {
    $_SESSION['alert_message'] = 'No food menu category ID specified for deletion.';
    $_SESSION['alert_type'] = 'warning';
}

$conn->close();
header("Location: food_menu.php"); // Redirect back to the food menu list
exit();
?>