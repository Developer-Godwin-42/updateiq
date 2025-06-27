<?php
// edit_gallery_image.php
$page_title = 'Edit Gallery Image';
session_start();
require_once '../includes/database.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage gallery)
$user_role = $_SESSION['user_role'] ?? 'Editor';

$message = '';
$image_data = null; // To store data of the image being edited
$upload_dir = '../uploads/gallery/'; // Relative to this script

// --- Handle Image Data Fetch (GET request) ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_image_id = (int)$_GET['id'];

    $sql = "SELECT image_id, category_id, image_filename, image_alt_text, title_tag, description, is_published FROM gallery_images WHERE image_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $edit_image_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $image_data = $result->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger">Image not found.</div>';
            $image_data = null;
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $message = '<div class="alert alert-warning">No image ID specified for editing.</div>';
}

// --- Handle Update Image Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_image'])) {
    $image_id_to_update = (int)$_POST['image_id'];
    $new_category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $new_image_alt_text = trim($_POST['image_alt_text']);
    $new_image_title_tag = trim($_POST['image_title_tag']);
    $new_image_description = trim($_POST['image_description']);
    $new_is_published = isset($_POST['is_published']) ? 1 : 0;
    $current_filename = $_POST['current_filename']; // Hidden field to keep track of the current filename

    // Start building the SQL update query and parameters
    $sql_update = "UPDATE gallery_images SET category_id = ?, image_alt_text = ?, title_tag = ?, description = ?, is_published = ?";
    $params = "isssi";
    $values = [$new_category_id, $new_image_alt_text, $new_image_title_tag, $new_image_description, $new_is_published];
    $new_filename = $current_filename; // Assume filename won't change unless new file is uploaded

    // Check if a new file has been uploaded
    if (isset($_FILES['new_image_file']) && $_FILES['new_image_file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['new_image_file']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_filename = uniqid('img_', true) . '.' . $file_type;
        $target_file = $upload_dir . $unique_filename;
        $uploadOk = 1;

        // Basic file validation
        $check = getimagesize($_FILES['new_image_file']['tmp_name']);
        if ($check === false) { $message = '<div class="alert alert-danger">File is not an image.</div>'; $uploadOk = 0; }
        if (!in_array($file_type, ['jpg', 'png', 'jpeg', 'gif'])) { $message = '<div class="alert alert-danger">Sorry, only JPG, JPEG, PNG & GIF files are allowed.</div>'; $uploadOk = 0; }
        if ($_FILES['new_image_file']['size'] > 5000000) { $message = '<div class="alert alert-danger">Sorry, your file is too large (max 5MB).</div>'; $uploadOk = 0; }

        if ($uploadOk == 1) {
            // Move the new file
            if (move_uploaded_file($_FILES['new_image_file']['tmp_name'], $target_file)) {
                // Delete old file if it exists and is different from the new one
                if ($current_filename && file_exists($upload_dir . $current_filename) && $current_filename != $unique_filename) {
                    unlink($upload_dir . $current_filename);
                }
                $new_filename = $unique_filename;
                // Add filename to SQL update
                $sql_update .= ", image_filename = ?";
                $params .= "s";
                $values[] = $new_filename;
            } else {
                $message = '<div class="alert alert-danger">Error uploading new image file.</div>';
                $uploadOk = 0; // Prevent DB update if file upload failed
            }
        }
    }

    if ($uploadOk ?? 1) { // Proceed if no file upload error or no file was uploaded
        $sql_update .= " WHERE image_id = ?";
        $params .= "i";
        $values[] = $image_id_to_update;

        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param($params, ...$values); // Use splat operator for dynamic binding

            if ($stmt_update->execute()) {
                $message = '<div class="alert alert-success">Image details updated successfully!</div>';
                // Re-fetch image data to display updated info immediately
                $sql_re_fetch = "SELECT image_id, category_id, image_filename, image_alt_text, title_tag, description, is_published FROM gallery_images WHERE image_id = ?";
                $stmt_re_fetch = $conn->prepare($sql_re_fetch);
                $stmt_re_fetch->bind_param("i", $image_id_to_update);
                $stmt_re_fetch->execute();
                $result_re_fetch = $stmt_re_fetch->get_result();
                $image_data = $result_re_fetch->fetch_assoc();
                $stmt_re_fetch->close();
            } else {
                $message = '<div class="alert alert-danger">Error updating image details: ' . $stmt_update->error . '</div>';
            }
            $stmt_update->close();
        } else {
            $message = '<div class="alert alert-danger">Database error preparing update statement: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Gallery Categories for Dropdown (always needed) ---
$categories = [];
$sql_categories = "SELECT category_id, category_name FROM gallery_categories ORDER BY category_name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    $message .= '<div class="alert alert-danger">Error fetching categories: ' . $conn->error . '</div>';
}

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
 

    <div class="container mt-4">

        <?php echo $message; ?>

        <?php if ($image_data): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Editing Image: <?php echo htmlspecialchars($image_data['image_alt_text'] ?: 'No Alt Text'); ?> (ID: <?php echo htmlspecialchars($image_data['image_id']); ?>)</h3>
            </div>
            <div class="card-body">
                <form action="edit_gallery_image.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_data['image_id']); ?>">
                    <input type="hidden" name="current_filename" value="<?php echo htmlspecialchars($image_data['image_filename']); ?>">

                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Current Image Preview:</label>
                        <img src="<?php echo htmlspecialchars($upload_dir . $image_data['image_filename']); ?>" alt="Current Image" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 5px;">
                    </div>

                    <div class="mb-3">
                        <label for="new_image_file" class="form-label">Replace Image File (Optional)</label>
                        <input type="file" class="form-control" id="new_image_file" name="new_image_file" accept="image/*">
                        <small class="form-text text-muted">Upload a new image file to replace the current one.</small>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                    <?php echo ($cat['category_id'] == $image_data['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image_alt_text" class="form-label">Alt Text (for SEO & Accessibility)</label>
                        <input type="text" class="form-control" id="image_alt_text" name="image_alt_text" value="<?php echo htmlspecialchars($image_data['image_alt_text']); ?>">
                        <small class="form-text text-muted">Briefly describe the image content.</small>
                    </div>
                    <div class="mb-3">
                        <label for="image_title_tag" class="form-label">Title Tag (Tooltip on hover)</label>
                        <input type="text" class="form-control" id="image_title_tag" name="image_title_tag" value="<?php echo htmlspecialchars($image_data['title_tag']); ?>">
                        <small class="form-text text-muted">Short, descriptive title for the image.</small>
                    </div>
                    <div class="mb-3">
                        <label for="image_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="image_description" name="image_description" rows="3"><?php echo htmlspecialchars($image_data['description']); ?></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" <?php echo $image_data['is_published'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_published">Is Published?</label>
                    </div>
                    <button type="submit" name="update_image" class="btn btn-primary">Update Image</button>
                    <a href="gallery.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        <?php else: ?>
            <p>Please select an image to edit from the <a href="gallery.php">Gallery Management</a> page.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>