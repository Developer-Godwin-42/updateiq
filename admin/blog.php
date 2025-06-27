<?php
// blog.php
$page_title = 'Blog Management';
session_start();
require_once '../includes/database.php';
require_once 'includes/header.php'; // Assuming this header includes basic session and authentication

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage blogs)
$user_role = $_SESSION['user_role'] ?? 'Editor';
$current_user_id = $_SESSION['user_id'];

$message = ''; // To store success or error messages

// Define the upload directory for blog featured images
$blog_upload_dir = '../uploads/blog/'; // Create this folder later if it doesn't exist

// Ensure the upload directory exists and is writable
if (!is_dir($blog_upload_dir)) {
    mkdir($blog_upload_dir, 0777, true); // Create directory with full permissions (adjust for production)
}
if (!is_writable($blog_upload_dir)) {
    $message = '<div class="alert alert-danger">Blog image upload directory is not writable. Please check permissions for ' . $blog_upload_dir . '</div>';
}


// --- Handle Add Category Form Submission (Existing code, no changes needed here) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_blog_category'])) {
    // ... (Your existing code for adding categories) ...
    $category_name = trim($_POST['category_name']);
    $slug = trim($_POST['slug']); // For SEO-friendly URLs
    $description = trim($_POST['category_description']);

    if (empty($category_name) || empty($slug)) {
        $message = '<div class="alert alert-danger">Category Name and Slug are required.</div>';
    } else {
        $check_sql = "SELECT category_id FROM blog_categories WHERE category_name = ? OR slug = ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("ss", $category_name, $slug);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-warning">Category Name or Slug already exists.</div>';
            } else {
                $sql = "INSERT INTO blog_categories (category_name, slug, description) VALUES (?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("sss", $category_name, $slug, $description);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Blog category added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error adding blog category: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Database error preparing category insert statement: ' . $conn->error . '</div>';
                }
            }
            $stmt_check->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing category check statement: ' . $conn->error . '</div>';
        }
    }
}


// --- Handle Add Blog Post Form Submission (MODIFIED for featured image) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_blog_post'])) {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content'];
    $excerpt = trim($_POST['excerpt']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $meta_keywords = trim($_POST['meta_keywords']);
    $status = $_POST['status'];
    $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;

    $featured_image_filename = null; // Initialize to null

    // Handle featured image upload
    if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['featured_image_file']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_filename = uniqid('blog_img_', true) . '.' . $file_type;
        $target_file = $blog_upload_dir . $unique_filename;
        $uploadOk = 1;

        // Basic file validation (similar to gallery)
        $check = getimagesize($_FILES['featured_image_file']['tmp_name']);
        if ($check === false) { $message = '<div class="alert alert-danger">Featured file is not an image.</div>'; $uploadOk = 0; }
        if (!in_array($file_type, ['webp'])) { $message = '<div class="alert alert-danger">Sorry, only WEBP files are allowed for featured image.</div>'; $uploadOk = 0; }
        if ($_FILES['featured_image_file']['size'] > 1024) { $message = '<div class="alert alert-danger">Sorry, your featured image file is too large (max 1MB).</div>'; $uploadOk = 0; }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES['featured_image_file']['tmp_name'], $target_file)) {
                $featured_image_filename = $unique_filename; // Set filename if upload successful
            } else {
                $message = '<div class="alert alert-danger">Sorry, there was an error uploading your featured image.</div>';
                // Don't halt, but the filename will remain null
            }
        }
    }


    // Basic validation for post content
    if (empty($title) || empty($slug) || empty($content)) {
        $message = '<div class="alert alert-danger">Title, Slug, and Content are required for a blog post.</div>';
    } else {
        // Check for duplicate slug
        $check_sql = "SELECT post_id FROM blog_posts WHERE slug = ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("s", $slug);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-warning">Blog post with this slug already exists. Please choose a different one.</div>';
            } else {
                // Modified SQL to include featured_image_url
                $sql = "INSERT INTO blog_posts (title, slug, content, excerpt, category_id, author_user_id, featured_image_url, meta_title, meta_description, meta_keywords, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    // Added 's' for featured_image_url parameter
                    $stmt->bind_param("sssssissssss", $title, $slug, $content, $excerpt, $category_id, $current_user_id, $featured_image_filename, $meta_title, $meta_description, $meta_keywords, $status, $published_at);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Blog post "' . htmlspecialchars($title) . '" saved successfully as ' . htmlspecialchars($status) . '!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error saving blog post: ' . $stmt->error . '</div>';
                        // If DB insert fails, delete the uploaded file
                        if ($featured_image_filename && file_exists($blog_upload_dir . $featured_image_filename)) {
                            unlink($blog_upload_dir . $featured_image_filename);
                        }
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Database error preparing post insert statement: ' . $conn->error . '</div>';
                }
            }
            $stmt_check->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing post check statement: ' . $conn->error . '</div>';
        }
    }
}


// --- Fetch Blog Categories for Display and Dropdown (Existing code) ---
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

// --- Fetch Blog Posts for Display (Existing code, but also fetches featured_image_url now) ---
$blog_posts = [];
$sql_blog_posts = "SELECT bp.post_id, bp.title, bp.slug, bc.category_name, u.username, bp.status, bp.published_at, bp.created_at, bp.featured_image_url FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id LEFT JOIN users u ON bp.author_user_id = u.user_id ORDER BY bp.created_at DESC";
$result_blog_posts = $conn->query($sql_blog_posts);
if ($result_blog_posts) {
    while ($row = $result_blog_posts->fetch_assoc()) {
        $blog_posts[] = $row;
    }
} else {
    $message .= '<div class="alert alert-danger">Error fetching blog posts: ' . $conn->error . '</div>';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/r8pyi1q5m5kxvmsr9sk2rl5g7edwsekb9kkqvakytdlrbzwx/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- <script>
        tinymce.init({
            selector: '#post_content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bulllist indent outdent | emoticons charmap | removeformat',
            height: 400,
            // Configure TinyMCE to handle image uploads via a custom handler (optional, more advanced)
            // images_upload_url: 'your_image_upload_handler.php', // This would be a separate script
            // automatic_uploads: true,
            // file_picker_types: 'image',
            // file_picker_callback: function (cb, value, meta) {
            //     var input = document.createElement('input');
            //     input.setAttribute('type', 'file');
            //     input.setAttribute('accept', 'image/*');
            //     input.onchange = function () {
            //         var file = this.files[0];
            //         var reader = new FileReader();
            //         reader.onload = function () {
            //             var id = 'blobid' + (new Date()).getTime();
            //             var blobCache = tinymce.activeEditor.editorUpload.blobCache;
            //             var base64 = reader.result.split(',')[1];
            //             var blobInfo = blobCache.create(id, file, base64);
            //             blobCache.add(blobInfo);
            //             cb(blobInfo.blobUri(), { title: file.name });
            //         };
            //         reader.readAsDataURL(file);
            //     };
            //     input.click();
            // }
        });
    </script> -->
    <!-- <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>  -->
    <script>
    tinymce.init({
        selector: '#post_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bulllist indent outdent | emoticons charmap | removeformat',
        height: 400,

        // --- NEW TINYMCE CONFIG FOR IMAGE UPLOADS ---
        images_upload_url: 'includes/upload_tinymce_image.php', // This is the URL to your upload handler
        automatic_uploads: true, // Automatically upload image when selected
        file_picker_types: 'image', // Show image picker in dialog
        
        // This callback is for the file picker button (the folder icon in the dialog)
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            input.onchange = function () {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        },

        // --- Configuration for Lazy Loading and Title Tag in Editor ---
        // This part mostly tells TinyMCE what HTML attributes to allow/expect.
        // Lazy loading is primarily handled on the frontend rendering.
        extended_valid_elements: 'img[class|src|alt|title|width|height|loading]', // Allow 'loading' attribute
        custom_elements: 'img', // Treat img tags correctly
        // This is where TinyMCE can be instructed to *add* attributes to inserted images
        // This often requires more advanced customization or a plugin, but the `loading` attribute
        // can be added manually by the user or through a custom TinyMCE button/hook.
        // For now, by allowing 'loading' in extended_valid_elements, TinyMCE won't strip it if you add it.
        // You'd typically add a function on the frontend to automatically add loading="lazy" if not present.
    });
</script>
</head>
<body>
    <?php require_once 'includes/header.php'; // Include the header with sidebar/nav ?>

    <div class="container mt-4">
        <?php echo $message; // Display success/error messages ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Blog Category</h3>
            </div>
            <div class="card-body">
                <form action="blog.php" method="POST">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Category Slug (for URL)</label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g., web-development" required>
                        <small class="form-text text-muted">A URL-friendly version of the name (lowercase, no spaces, use hyphens).</small>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="2"></textarea>
                    </div>
                    <button type="submit" name="add_blog_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Create New Blog Post</h3>
            </div>
            <div class="card-body">
                <form action="blog.php" method="POST" enctype="multipart/form-data"> <div class="mb-3">
                        <label for="title" class="form-label">Post Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Post Slug (for URL)</label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g., my-first-blog-post" required>
                        <small class="form-text text-muted">A URL-friendly version of the title (lowercase, no spaces, use hyphens).</small>
                    </div>

                    <div class="mb-3">
                        <label for="featured_image_file" class="form-label">Featured Image (Banner)</label>
                        <input type="file" class="form-control" id="featured_image_file" name="featured_image_file" accept="image/*">
                        <small class="form-text text-muted">Upload an image to be used as the post's banner/featured image.</small>
                    </div>

                    <div class="mb-3">
                        <label for="post_content" class="form-label">Content</label>
                        <textarea class="form-control" id="post_content" name="content"></textarea>
                        <small class="form-text text-muted">Write your blog post here using the rich text editor.</small>
                    </div>
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Excerpt (Short Summary)</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="2"></textarea>
                        <small class="form-text text-muted">A brief summary of the post, often used in listings.</small>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($blog_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>">
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
                                <input type="text" class="form-control" id="meta_title" name="meta_title" maxlength="60">
                                <small class="form-text text-muted">Appears in browser tab and search results (max 60 characters recommended).</small>
                            </div>
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="160"></textarea>
                                <small class="form-text text-muted">Short summary for search results (max 160 characters recommended).</small>
                            </div>
                            <div class="mb-3">
                                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords">
                                <small class="form-text text-muted">Comma-separated keywords (less important for modern SEO).</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <button type="submit" name="add_blog_post" class="btn btn-primary">Save Post</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Existing Blog Posts</h3>
            </div>
            <div class="card-body">
                <?php if (empty($blog_posts)): ?>
                    <p>No blog posts found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th> <th>Title</th>
                                    <th>Slug</th>
                                    <th>Category</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Published On</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blog_posts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                                        <td>
                                            <?php if ($post['featured_image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($blog_upload_dir . $post['featured_image_url']); ?>" alt="Featured Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                                        <td><?php echo htmlspecialchars($post['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($post['category_name'] ?: 'Uncategorized'); ?></td>
                                        <td><?php echo htmlspecialchars($post['username'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            if ($post['status'] == 'published') {
                                                echo '<span class="badge bg-success">Published</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Draft</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['published_at'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($post['created_at']); ?></td>
                                        <td>
                                            <a href="edit_blog_post.php?id=<?php echo htmlspecialchars($post['post_id']); ?>" class="btn btn-sm btn-info me-1">Edit</a>
                                            <a href="delete_blog_post.php?id=<?php echo htmlspecialchars($post['post_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this blog post?');">Delete</a>
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

    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>