<?php
// delete_blog_post.php
session_start();
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can delete blog posts)
$user_role = $_SESSION['user_role'] ?? 'Editor';
// If you want ONLY admins to delete blog posts, uncomment this:
if ($user_role != 'Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Access denied. Only administrators can delete blog posts.</div>';
    header("Location: blog.php"); // Redirect to blog list with error
    exit();
}


$message = '';

// Check if post ID is provided via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $post_id_to_delete = (int)$_GET['id'];

    // Prepare a delete statement
    $sql = "DELETE FROM blog_posts WHERE post_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $post_id_to_delete);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = '<div class="alert alert-success">Blog post deleted successfully!</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-warning">Blog post not found or already deleted.</div>';
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Error deleting blog post: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Database error preparing statement: ' . $conn->error . '</div>';
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-warning">No blog post ID specified for deletion.</div>';
}

$conn->close();
header("Location: blog.php"); // Redirect back to the blog list
exit();
?>