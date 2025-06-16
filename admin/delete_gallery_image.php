<?php
// delete_gallery_image.php
session_start();
require_once 'includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can delete gallery images)
$user_role = $_SESSION['user_role'] ?? 'Editor';
// If you want ONLY admins to delete images, uncomment this:
/*
if ($user_role != 'Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Access denied. Only administrators can delete gallery images.</div>';
    header("Location: gallery.php"); // Redirect to gallery list with error
    exit();
}
*/

$message = '';
$upload_dir = '../uploads/gallery/'; // Must match the directory in gallery.php and edit_gallery_image.php

// Check if image ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $image_id_to_delete = (int)$_GET['id'];

    // First, retrieve the filename from the database before deleting the record
    $sql_get_filename = "SELECT image_filename FROM gallery_images WHERE image_id = ?";
    if ($stmt_get_filename = $conn->prepare($sql_get_filename)) {
        $stmt_get_filename->bind_param("i", $image_id_to_delete);
        $stmt_get_filename->execute();
        $result_filename = $stmt_get_filename->get_result();
        $filename_row = $result_filename->fetch_assoc();
        $stmt_get_filename->close();

        if ($filename_row) {
            $image_filename = $filename_row['image_filename'];
            $file_path = $upload_dir . $image_filename;

            // Prepare a delete statement for the database record
            $sql_delete_db = "DELETE FROM gallery_images WHERE image_id = ?";
            if ($stmt_delete_db = $conn->prepare($sql_delete_db)) {
                $stmt_delete_db->bind_param("i", $image_id_to_delete);

                if ($stmt_delete_db->execute()) {
                    if ($stmt_delete_db->affected_rows > 0) {
                        // Database record deleted successfully, now try to delete the file
                        if (file_exists($file_path)) {
                            if (unlink($file_path)) {
                                $_SESSION['message'] = '<div class="alert alert-success">Image and file deleted successfully!</div>';
                            } else {
                                $_SESSION['message'] = '<div class="alert alert-warning">Image record deleted, but failed to delete the file from server.</div>';
                            }
                        } else {
                            $_SESSION['message'] = '<div class="alert alert-warning">Image record deleted, but file was not found on server.</div>';
                        }
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-warning">Image not found in database or already deleted.</div>';
                    }
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error deleting image from database: ' . $stmt_delete_db->error . '</div>';
                }
                $stmt_delete_db->close();
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Database error preparing delete statement: ' . $conn->error . '</div>';
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-warning">Image not found in database.</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Database error retrieving filename: ' . $conn->error . '</div>';
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-warning">No image ID specified for deletion.</div>';
}

$conn->close();
header("Location: gallery.php"); // Redirect back to the gallery list
exit();
?>