<?php
// admin/edit_food_item.php
session_start(); // Start session at the very top!

// Define the page title early for the header.php
$page_title = 'Edit Food Menu Item';

// Include database connection BEFORE any HTML output or header includes
require_once '../includes/database.php'; 

// Initialize SweetAlert2 message variables for this page
$alert_message = '';
$alert_type = '';

// Check if there are alert messages from previous redirects (e.g., from POST submission)
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    unset($_SESSION['alert_message']); // Clear the session variables after reading
    unset($_SESSION['alert_type']);
}


// --- Initial validation and redirection before any HTML output ---
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert_message'] = 'Authentication required to access this page.';
    $_SESSION['alert_type'] = 'error';
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage food menu items)
$user_role = $_SESSION['user_role'] ?? 'Editor';
// Uncomment the lines below if you want ONLY 'Admin' role to edit food items
// if ($user_role !== 'Admin') {
//     $_SESSION['alert_message'] = 'Access denied. You do not have permission to edit food menu items.';
//     $_SESSION['alert_type'] = 'error';
//     header("Location: food_menu.php");
//     exit();
// }

// Define the upload directory for food menu item images
$food_menu_upload_dir = '../uploads/food_menu/'; // Relative to admin/ directory

// Ensure the upload directory exists and is writable
if (!is_dir($food_menu_upload_dir)) {
    // Attempt to create, and if fails, set an alert
    if (!mkdir($food_menu_upload_dir, 0777, true)) { // Use 0755 or 0775 in production
        $_SESSION['alert_message'] = 'Failed to create food menu image upload directory. Check server permissions.';
        $_SESSION['alert_type'] = 'error';
    } else {
        $_SESSION['alert_message'] = 'Food menu image upload directory created.';
        $_SESSION['alert_type'] = 'info';
    }
}
// Check write permissions AFTER ensuring it exists
if (!is_writable($food_menu_upload_dir)) {
    $_SESSION['alert_message'] = 'Food menu image upload directory is not writable. Please check permissions for ' . $food_menu_upload_dir . '.';
    $_SESSION['alert_type'] = 'error';
}


// --- Handle Item Data Fetch (GET request when page is loaded) ---
// This populates $item_data or redirects if ID is missing/invalid.
// This must be before the POST handling as POST needs $item_data to re-fetch if updated.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_item_id = (int)$_GET['id'];

    $sql = "SELECT item_id, category_id, item_name, description, price, image_filename, image_alt_text, order_priority, is_active FROM food_menu_items WHERE item_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $edit_item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $item_data = $result->fetch_assoc();
        } else {
            $_SESSION['alert_message'] = 'Food menu item not found.';
            $_SESSION['alert_type'] = 'danger';
            header("Location: food_menu.php"); // Redirect to the food menu page
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['alert_message'] = 'Database error fetching item: ' . $conn->error;
        $_SESSION['alert_type'] = 'error';
        header("Location: food_menu.php"); // Redirect on DB error
        exit();
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") { // If loaded directly without ID
    $_SESSION['alert_message'] = 'No food menu item ID specified for editing. Please select an item to edit.';
    $_SESSION['alert_type'] = 'warning';
    header("Location: food_menu.php");
    exit();
}


// --- Handle Update Food Menu Item Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_menu_item'])) {
    $item_id_to_update = (int)$_POST['item_id'];
    $new_item_name = trim($_POST['item_name']);
    $new_description = trim($_POST['item_description']);
    $new_price = (float)$_POST['price'];
    $new_category_id = (int)$_POST['category_id'];
    $new_image_alt_text = trim($_POST['image_alt_text']);
    $new_order_priority = (int)$_POST['item_order_priority'];
    $new_is_active = isset($_POST['item_is_active']) ? 1 : 0;
    $current_image_filename = $_POST['current_image_filename']; // Hidden field from form

    // Start building the SQL update query dynamically
    $sql_update = "UPDATE food_menu_items SET category_id = ?, item_name = ?, description = ?, price = ?, image_alt_text = ?, order_priority = ?, is_active = ?";
    $params_types = "issdsii"; // Initial types: int, string, string, double, string, int, int
    $params_values = [$new_category_id, $new_item_name, $new_description, $new_price, $new_image_alt_text, $new_order_priority, $new_is_active];
    $new_image_filename = $current_image_filename; // Assume no change unless new file uploaded

    $uploadOk = 1; // Flag for new image upload status
    $temp_response_message = ''; // For specific upload validation errors

    // Handle new image upload
    if (isset($_FILES['new_item_image_file']) && $_FILES['new_item_image_file']['error'] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['new_item_image_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['new_item_image_file']['size']; // Correctly defined

        $unique_filename = uniqid('food_img_', true) . '.' . $file_ext;
        $target_file = $food_menu_upload_dir . $unique_filename;

        // Basic file validation
        $check = getimagesize($_FILES['new_item_image_file']['tmp_name']);
        if ($check === false) { 
            $temp_response_message = 'Uploaded file is not a valid image.'; 
            $uploadOk = 0; 
        }

        // Validate file type - ONLY WEBP
        $allowed_extensions = ['webp']; 
        if ($uploadOk == 1 && !in_array($file_ext, $allowed_extensions)) {
            $temp_response_message = 'Invalid image type. Only WEBP files are allowed.';
            $uploadOk = 0;
        }

        // Validate file size - MAX 1MB
        $max_file_size = 1048576; // 1 MB in bytes
        if ($uploadOk == 1 && $file_size > $max_file_size) {
            $temp_response_message = 'Image file is too large. Max 1MB.';
            $uploadOk = 0;
        }

        if ($uploadOk == 0) { // If image upload validation failed
            $_SESSION['alert_message'] = $temp_response_message;
            $_SESSION['alert_type'] = 'error';
            header("Location: edit_food_item.php?id=" . $item_id_to_update); // Redirect on error
            exit();
        } else { // If image validation passed, attempt to move file
            if (move_uploaded_file($_FILES['new_item_image_file']['tmp_name'], $target_file)) {
                $new_image_filename = $unique_filename;
                // Add filename to SQL update
                $sql_update .= ", image_filename = ?";
                $params_types .= "s";
                $params_values[] = $new_image_filename;

                // Delete old image file if it exists and is different
                if ($current_image_filename && file_exists($food_menu_upload_dir . $current_image_filename) && $current_image_filename != $new_image_filename) {
                    unlink($food_menu_upload_dir . $current_image_filename);
                }
            } else {
                $_SESSION['alert_message'] = 'Error uploading new item image. Check folder permissions.';
                $_SESSION['alert_type'] = 'error';
                header("Location: edit_food_item.php?id=" . $item_id_to_update); // Redirect on file move error
                exit();
            }
        }
    }
    // Handle 'Remove Image' checkbox
    else if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        $sql_update .= ", image_filename = ?, image_alt_text = ?";
        $params_types .= "ss";
        $params_values[] = null;
        $params_values[] = null;

        if ($current_image_filename && file_exists($food_menu_upload_dir . $current_image_filename)) {
            unlink($food_menu_upload_dir . $current_image_filename);
            $new_image_filename = null; // Important for updating $item_data if needed
            $_SESSION['alert_message'] = 'Image removed successfully.';
            $_SESSION['alert_type'] = 'info';
        } else {
            $_SESSION['alert_message'] = 'No image found to remove or file already missing.';
            $_SESSION['alert_type'] = 'warning';
        }
    }


    // Proceed with database update (only if no critical upload errors caused early exit)
    $sql_update .= " WHERE item_id = ?";
    $params_types .= "i";
    $params_values[] = $item_id_to_update;

    if ($stmt_update = $conn->prepare($sql_update)) {
        // FIX: Using the splat operator (...) for bind_param (PHP 5.6+). This replaces makeValuesReferenced
        $stmt_update->bind_param($params_types, ...$params_values);

        if ($stmt_update->execute()) {
            $_SESSION['alert_message'] = 'Food menu item updated successfully!';
            $_SESSION['alert_type'] = 'success';
            // Re-fetch item data for display on the same page
            // This is done to ensure the form reflects the very latest data, e.g., if image_filename changed
            $sql_re_fetch = "SELECT item_id, category_id, item_name, description, price, image_filename, image_alt_text, order_priority, is_active FROM food_menu_items WHERE item_id = ?";
            $stmt_re_fetch = $conn->prepare($sql_re_fetch);
            $stmt_re_fetch->bind_param("i", $item_id_to_update);
            $stmt_re_fetch->execute();
            $result_re_fetch = $stmt_re_fetch->get_result();
            $item_data = $result_re_fetch->fetch_assoc();
            $stmt_re_fetch->close();

        } else {
            $_SESSION['alert_message'] = 'Error updating menu item: ' . $stmt_update->error;
            $_SESSION['alert_type'] = 'error';
        }
        $stmt_update->close();
    } else {
        $_SESSION['alert_message'] = 'Database error preparing update statement: ' . $conn->error;
        $_SESSION['alert_type'] = 'error';
    }
    // Redirect after POST to prevent re-submission and display SweetAlert2
    header("Location: edit_food_item.php?id=" . $item_id_to_update);
    exit();
}


// --- Fetch Food Menu Categories for Dropdown (always needed for the form) ---
$food_menu_categories = [];
// This SELECT statement needs to fetch all category columns that are used in the table (description, order_priority, is_active)
// for consistency, even if not directly displayed in the dropdown itself.
$sql_food_categories = "SELECT category_id, category_name, description, order_priority, is_active FROM food_menu_categories ORDER BY order_priority ASC, category_name ASC";
$result_food_categories = $conn->query($sql_food_categories);
if ($result_food_categories) {
    while ($row = $result_food_categories->fetch_assoc()) {
        $food_menu_categories[] = $row;
    }
} else {
    // Log this error, but don't redirect as it might be a secondary issue on page display
    error_log('Error fetching food menu categories for dropdown: ' . $conn->error);
}

// Close database connection after all database operations are done
$conn->close();

// NOW, include the header.php AFTER all PHP logic that might send headers (like redirects).
require_once 'includes/header.php';
?>

<div class="container mt-4">

    <?php // SweetAlert2 display block (This needs to be in the HTML body where scripts can run) ?>
    <?php if ($alert_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo ucfirst($alert_type); ?>!',
                text: '<?php echo addslashes($alert_message); ?>', // Use addslashes to properly escape quotes in JS string
                showConfirmButton: false,
                timer: 3000 // Automatically close after 3 seconds
            });
        });
    </script>
    <?php endif; ?>

    <?php if ($item_data): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3>Editing Item: <?php echo htmlspecialchars($item_data['item_name']); ?> (ID: <?php echo htmlspecialchars($item_data['item_id']); ?>)</h3>
        </div>
        <div class="card-body">
            <form action="edit_food_item.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_data['item_id']); ?>">
                <input type="hidden" name="current_image_filename" value="<?php echo htmlspecialchars($item_data['image_filename'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="item_name" class="form-label">Item Name</label>
                    <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item_data['item_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="item_description" class="form-label">Description</label>
                    <textarea class="form-control" id="item_description" name="item_description" rows="3"><?php echo htmlspecialchars($item_data['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($item_data['price']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($food_menu_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                <?php echo ($cat['category_id'] == $item_data['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="new_item_image_file" class="form-label">Item Image</label>
                    <?php if ($item_data['image_filename']): ?>
                        <div class="mb-2">
                            <img src="<?php echo htmlspecialchars($food_menu_upload_dir . $item_data['image_filename']); ?>" alt="Current Image" class="current-image-preview">
                            <p class="small text-muted mt-1">Current: <?php echo htmlspecialchars($item_data['image_filename']); ?></p>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label class="form-check-label" for="remove_image">Remove current image</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="new_item_image_file" name="new_item_image_file" accept="image/*">
                    <small class="form-text text-muted">Upload a new image to replace the current one.</small>
                </div>
                <div class="mb-3">
                    <label for="image_alt_text" class="form-label">Image Alt Text (for SEO & Accessibility)</label>
                    <input type="text" class="form-control" id="image_alt_text" name="image_alt_text" value="<?php echo htmlspecialchars($item_data['image_alt_text'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="item_order_priority" class="form-label">Order Priority</label>
                    <input type="number" class="form-control" id="item_order_priority" name="item_order_priority" value="<?php echo htmlspecialchars($item_data['order_priority']); ?>" required>
                    <small class="form-text text-muted">Lower numbers appear first within category.</small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="item_is_active" name="item_is_active" <?php echo $item_data['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="item_is_active">Is Active?</label>
                </div>
                <button type="submit" name="update_menu_item" class="btn btn-primary">Update Menu Item</button>
                <a href="food_menu.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php else: ?>
        <p>Please select a food menu item to edit from the <a href="food_menu.php">Food Menu Management</a> page.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>