<?php
// public/post.php
require_once 'includes/database.php'; // Adjust path

$post = null;
$message = '';

// Check if slug is provided in the URL
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $post_slug = trim($_GET['slug']);

    // Select only PUBLISHED posts with the given slug
    $sql = "SELECT bp.post_id, bp.title, bp.slug, bp.content, bp.excerpt, bp.published_at, bc.category_name, u.username, bp.meta_title, bp.meta_description, bp.meta_keywords FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id LEFT JOIN users u ON bp.author_user_id = u.user_id WHERE bp.slug = ? AND bp.status = 'published'";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $post_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $post = $result->fetch_assoc();
        } else {
            // Post not found or not published
            $message = '<div class="alert alert-warning">The blog post you are looking for does not exist or is not yet published.</div>';
        }
        $stmt->close();
    } else {
        error_log("Error preparing SQL statement for single post: " . $conn->error);
        $message = '<div class="alert alert-danger">An error occurred while fetching the post.</div>';
    }
} else {
    $message = '<div class="alert alert-warning">No blog post specified.</div>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($post): ?>
        <title><?php echo htmlspecialchars($post['meta_title'] ?: $post['title'] . ' | Our Blog'); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?: $post['excerpt']); ?>">
        <meta name="keywords" content="<?php echo htmlspecialchars($post['meta_keywords'] ?: ''); ?>">
    <?php else: ?>
        <title>Blog Post Not Found | Our Blog</title>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .blog-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 15px auto;
            border-radius: 8px;
        }
        .blog-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .blog-content table, .blog-content th, .blog-content td {
            border: 1px solid #ddd;
        }
        .blog-content th, .blog-content td {
            padding: 8px;
            text-align: left;
        }
        .blog-content pre, .blog-content code {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .blog-content blockquote {
            border-left: 5px solid #007bff;
            padding-left: 15px;
            margin: 20px 0;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <?php if ($post): ?>
            <h1 class="mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
            <p class="text-muted small">
                Published on <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                by <?php echo htmlspecialchars($post['username'] ?: 'Unknown Author'); ?>
                <?php if ($post['category_name']): ?>
                    in <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                <?php endif; ?>
            </p>
            <hr>
            <div class="blog-content">
                <?php echo $post['content']; // Output HTML content directly from TinyMCE ?>
            </div>
            <hr>
            <p class="text-center mt-4">
                <a href="blog.php" class="btn btn-secondary">&larr; Back to Blog List</a>
            </p>
        <?php else: ?>
            <?php echo $message; ?>
            <p class="text-center mt-4">
                <a href="blog.php" class="btn btn-secondary">&larr; Back to Blog List</a>
            </p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>