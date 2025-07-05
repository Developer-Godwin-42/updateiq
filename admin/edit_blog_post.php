<?php
// edit_blog_post.php
$page_title = 'Edit Blog Post';
session_start();
require_once '../includes/database.php';
require_once 'includes/header.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage blogs)
$user_role = $_SESSION['user_role'] ?? 'Editor';

$message = '';
$post_data = null; // To store data of the post being edited

// --- Handle Blog Post Data Fetch (GET request) ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_post_id = (int)$_GET['id'];

    $sql = "SELECT post_id, title, slug, content, excerpt, category_id, meta_title, meta_description, meta_keywords, status, published_at FROM blog_posts WHERE post_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $edit_post_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $post_data = $result->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger">Blog post not found.</div>';
            $post_data = null;
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $message = '<div class="alert alert-warning">No blog post ID specified for editing.</div>';
}

// --- Handle Update Blog Post Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_blog_post'])) {
    $post_id_to_update = (int)$_POST['post_id'];
    $new_title = trim($_POST['title']);
    $new_slug = trim($_POST['slug']);
    $new_content = $_POST['content']; // HTML content from TinyMCE
    $new_excerpt = trim($_POST['excerpt']);
    $new_category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $new_meta_title = trim($_POST['meta_title']);
    $new_meta_description = trim($_POST['meta_description']);
    $new_meta_keywords = trim($_POST['meta_keywords']);
    $new_status = $_POST['status'];

    // Get current post data to determine if 'published_at' needs updating
    $current_status_sql = "SELECT status, published_at FROM blog_posts WHERE post_id = ?";
    $current_status_stmt = $conn->prepare($current_status_sql);
    $current_status_stmt->bind_param("i", $post_id_to_update);
    $current_status_stmt->execute();
    $current_status_result = $current_status_stmt->get_result();
    $current_post_status = $current_status_result->fetch_assoc();
    $current_status_stmt->close();

    $new_published_at = $current_post_status['published_at']; // Keep current published_at by default

    // If status changes from 'draft' to 'published', set published_at now
    if ($current_post_status['status'] == 'draft' && $new_status == 'published') {
        $new_published_at = date('Y-m-d H:i:s');
    }
    // If status changes from 'published' to 'draft' or 'archived', clear published_at
    else if ($current_post_status['status'] == 'published' && ($new_status == 'draft' || $new_status == 'archived')) {
        $new_published_at = null;
    }


    // Basic validation
    if (empty($new_title) || empty($new_slug) || empty($new_content)) {
        $message = '<div class="alert alert-danger">Title, Slug, and Content are required for a blog post.</div>';
    } else {
        // Check for duplicate slug (excluding the current post being edited)
        $check_sql = "SELECT post_id FROM blog_posts WHERE slug = ? AND post_id != ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("si", $new_slug, $post_id_to_update);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-warning">Blog post with this slug already exists. Please choose a different one.</div>';
            } else {
                // Prepare an update statement
                $sql_update = "UPDATE blog_posts SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?, published_at = ? WHERE post_id = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("ssssisssssi", $new_title, $new_slug, $new_content, $new_excerpt, $new_category_id, $new_meta_title, $new_meta_description, $new_meta_keywords, $new_status, $new_published_at, $post_id_to_update);

                    if ($stmt_update->execute()) {
                        $message = '<div class="alert alert-success">Blog post "' . htmlspecialchars($new_title) . '" updated successfully!</div>';
                        // Re-fetch post data to display updated info immediately
                        $sql_re_fetch = "SELECT post_id, title, slug, content, excerpt, category_id, meta_title, meta_description, meta_keywords, status, published_at FROM blog_posts WHERE post_id = ?";
                        $stmt_re_fetch = $conn->prepare($sql_re_fetch);
                        $stmt_re_fetch->bind_param("i", $post_id_to_update);
                        $stmt_re_fetch->execute();
                        $result_re_fetch = $stmt_re_fetch->get_result();
                        $post_data = $result_re_fetch->fetch_assoc();
                        $stmt_re_fetch->close();

                    } else {
                        $message = '<div class="alert alert-danger">Error updating blog post: ' . $stmt_update->error . '</div>';
                    }
                    $stmt_update->close();
                } else {
                    $message = '<div class="alert alert-danger">Database error preparing update statement: ' . $conn->error . '</div>';
                }
            }
            $stmt_check->close();
        }
    }
}

// --- Fetch Blog Categories for Dropdown (always needed) ---
$blog_categories = [];
$sql_blog_categories = "SELECT category_id, category_name FROM blog_categories ORDER BY category_name ASC";
$result_blog_categories = $conn->query($sql_blog_categories);
if ($result_blog_categories) {
    while ($row = $result_blog_categories->fetch_assoc()) {
        $blog_categories[] = $row;
    }
} else {
    $message .= '<div class="alert alert-danger">Error fetching blog categories: ' . $conn->error . '</div>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ - <?php echo isset($page_title) ? ' - ' . htmlspecialchars($page_title) : ''; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.tiny.cloud/1/r8pyi1q5m5kxvmsr9sk2rl5g7edwsekb9kkqvakytdlrbzwx/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#post_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bulllist indent outdent | emoticons charmap | removeformat',
        height: 400,
        images_upload_url: 'includes/upload_tinymce_image.php',
        automatic_uploads: true,
        file_picker_types: 'image',
        
        // MODIFIED: file_picker_callback to set title (which becomes alt/title by TinyMCE's default behavior)
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            input.onchange = function () {
                var file = this.files[0]; // Get the selected file
                var reader = new FileReader();
                reader.onload = function () {
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    
                    // --- THIS IS THE CRUCIAL LINE ---
                    // 'file.name' gives you the original filename (e.g., "my_image.jpg")
                    // TinyMCE's 'title' property in the callback often populates both
                    // the title attribute and the alt text field in the dialog.
                    cb(blobInfo.blobUri(), { title: file.name }); 
                };
                reader.readAsDataURL(file);
            };
            input.click();
        },
        
        // These settings ensure the fields are visible in the TinyMCE image dialog
        image_title: true,     // Enables the 'Title' field
        image_alt_field: true  // Enables the 'Alternative description' (Alt text) field
    
    });
</script>
</head>
<body>


    <div class="container mt-4">

        <?php echo $message; ?>

        <?php if ($post_data): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Editing Post: <?php echo htmlspecialchars($post_data['title']); ?> (ID: <?php echo htmlspecialchars($post_data['post_id']); ?>)</h3>
            </div>
            <div class="card-body">
                <form action="edit_blog_post.php" method="POST">
                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_data['post_id']); ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Post Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post_data['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Post Slug (for URL)</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($post_data['slug']); ?>" required>
                        <small class="form-text text-muted">A URL-friendly version of the title (lowercase, no spaces, use hyphens).</small>
                    </div>
                    <div class="mb-3">
                        <label for="post_content" class="form-label">Content</label>
                        <textarea class="form-control" id="post_content" name="content"><?php echo htmlspecialchars($post_data['content']); ?></textarea>
                        <small class="form-text text-muted">Edit your blog post here using the rich text editor.</small>
                    </div>
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Excerpt (Short Summary)</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="2"><?php echo htmlspecialchars($post_data['excerpt']); ?></textarea>
                        <small class="form-text text-muted">A brief summary of the post, often used in listings.</small>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($blog_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                    <?php echo ($cat['category_id'] == $post_data['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>SEO Optimization</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Title</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" maxlength="60" value="<?php echo htmlspecialchars($post_data['meta_title']); ?>">
                                <small class="form-text text-muted">Appears in browser tab and search results (max 60 characters recommended).</small>
                            </div>
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="160"><?php echo htmlspecialchars($post_data['meta_description']); ?></textarea>
                                <small class="form-text text-muted">Short summary for search results (max 160 characters recommended).</small>
                            </div>
                            <div class="mb-3">
                                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($post_data['meta_keywords']); ?>">
                                <small class="form-text text-muted">Comma-separated keywords (less important for modern SEO).</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" <?php echo ($post_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($post_data['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo ($post_data['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <button type="submit" name="update_blog_post" class="btn btn-primary">Update Post</button>
                    <a href="blog.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        <?php else: ?>
            <p>Please select a blog post to edit from the <a href="blog.php">Blog Management</a> page.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>