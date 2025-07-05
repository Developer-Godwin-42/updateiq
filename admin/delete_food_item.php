<?php
// admin/delete_food_item.php
session_start();
require_once '../includes/database.php'; // Correct path to your main database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert_message'] = 'Authentication required to delete food menu items.';
    $_SESSION['alert_type'] = 'error';
    header("Location: login.php");
    exit();
}

// User role check (Editors can delete items, but Admins might have final say)
// For now, allowing Editors. You can restrict to 'Admin' if needed.
$user_role = $_SESSION['user_role'] ?? 'Editor';
// if ($user_role != 'Admin') {
//     $_SESSION['alert_message'] = 'Access denied. Only administrators can delete food menu items.';
//     $_SESSION['alert_type'] = 'error';
//     header("Location: food_menu.php");
//     exit();
// }

$food_menu_upload_dir = '../uploads/food_menu/'; // Must match definition in food_menu.php

// Check if item ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id_to_delete = (int)$_GET['id'];

    // First, retrieve the filename from the database before deleting the record
    $sql_get_filename = "SELECT image_filename FROM food_menu_items WHERE item_id = ?";
    if ($stmt_get_filename = $conn->prepare($sql_get_filename)) {
        $stmt_get_filename->bind_param("i", $item_id_to_delete);
        $stmt_get_filename->execute();
        $result_filename = $stmt_get_filename->get_result();
        $filename_row = $result_filename->fetch_assoc();
        $stmt_get_filename->close();

        if ($filename_row) {
            $image_filename = $filename_row['image_filename'];
            $file_path = $food_menu_upload_dir . $image_filename;

            // Prepare a delete statement for the database record
            $sql_delete_db = "DELETE FROM food_menu_items WHERE item_id = ?";
            if ($stmt_delete_db = $conn->prepare($sql_delete_db)) {
                $stmt_delete_db->bind_param("i", $item_id_to_delete);

                if ($stmt_delete_db->execute()) {
                    if ($stmt_delete_db->affected_rows > 0) {
                        // Database record deleted successfully, now try to delete the file
                        if ($image_filename && file_exists($file_path)) { // Check $image_filename is not null/empty
                            if (unlink($file_path)) {
                                $_SESSION['alert_message'] = 'Food menu item and its image deleted successfully!';
                                $_SESSION['alert_type'] = 'success';
                            } else {
                                $_SESSION['alert_message'] = 'Food menu item record deleted, but failed to delete the image file from server.';
                                $_SESSION['alert_type'] = 'warning';
                            }
                        } else {
                            $_SESSION['alert_message'] = 'Food menu item record deleted, but no associated image file was found (or was already missing).';
                            $_SESSION['alert_type'] = 'info';
                        }
                    } else {
                        $_SESSION['alert_message'] = 'Food menu item not found in database or already deleted.';
                        $_SESSION['alert_type'] = 'warning';
                    }
                } else {
                    $_SESSION['alert_message'] = 'Error deleting food menu item from database: ' . $stmt_delete_db->error;
                    $_SESSION['alert_type'] = 'error';
                }
                $stmt_delete_db->close();
            } else {
                $_SESSION['alert_message'] = 'Database error preparing delete statement: ' . $conn->error;
                $_SESSION['alert_type'] = 'error';
            }
        } else {
            $_SESSION['alert_message'] = 'Food menu item not found in database (to get filename) or item has no image.';
            $_SESSION['alert_type'] = 'warning';
        }
    } else {
        $_SESSION['alert_message'] = 'Database error retrieving filename for deletion: ' . $conn->error;
        $_SESSION['alert_type'] = 'error';
    }
} else {
    $_SESSION['alert_message'] = 'No food menu item ID specified for deletion.';
    $_SESSION['alert_type'] = 'warning';
}

$conn->close();
header("Location: food_menu.php"); // Redirect back to the food menu list
exit();
?>