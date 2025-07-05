<?php
// admin/food_menu.php

// 1. Start session at the very top!
//    This MUST be the absolute first thing on the page, before any output,
//    and before including header.php if header.php also manages sessions.
session_start();

// 2. Define page title
$page_title = 'Food Menu Management';

// 3. Include database connection
require_once '../includes/database.php';

// IMPORTANT: No local $alert_message or $alert_type initialization here.
// These are managed by header.php from $_SESSION.

// Define the upload directory for food menu item images
$food_menu_upload_dir = '../uploads/food_menu/'; // Relative to admin/ directory

// Ensure the upload directory exists and is writable
if (!is_dir($food_menu_upload_dir)) {
    // Attempt to create, and if it fails, set an alert
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


// Check if user is logged in (early check, still before header.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User role check (Editors can manage food menu)
$user_role = $_SESSION['user_role'] ?? 'Editor';
$current_user_id = $_SESSION['user_id'];

// --- 5. ALL POST HANDLING LOGIC MUST COME HERE (before data fetching for display and header.php) ---

// --- Handle Add Food Menu Category Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['category_description']);
    $order_priority = (int)$_POST['category_order_priority'];
    $is_active = isset($_POST['category_is_active']) ? 1 : 0;

    if (empty($category_name)) {
        $_SESSION['alert_message'] = 'Category Name is required.';
        $_SESSION['alert_type'] = 'error';
    } else {
        $check_sql = "SELECT category_id FROM food_menu_categories WHERE category_name = ?";
        if ($stmt_check = $conn->prepare($check_sql)) {
            $stmt_check->bind_param("s", $category_name);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $_SESSION['alert_message'] = 'Category "' . htmlspecialchars($category_name) . '" already exists.';
                $_SESSION['alert_type'] = 'warning';
            } else {
                $sql = "INSERT INTO food_menu_categories (category_name, description, order_priority, is_active) VALUES (?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssii", $category_name, $description, $order_priority, $is_active);
                    if ($stmt->execute()) {
                        $_SESSION['alert_message'] = 'Food menu category added successfully!';
                        $_SESSION['alert_type'] = 'success';
                    } else {
                        $_SESSION['alert_message'] = 'Error adding category: ' . $stmt->error;
                        $_SESSION['alert_type'] = 'error';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['alert_message'] = 'Database error preparing category insert statement: ' . $conn->error;
                    $_SESSION['alert_type'] = 'error';
                }
            }
            $stmt_check->close();
        } else {
            $_SESSION['alert_message'] = 'Database error preparing category check statement: ' . $conn->error;
            $_SESSION['alert_type'] = 'error';
        }
    }
    header("Location: food_menu.php");
    exit();
}

// --- Handle Add Food Menu Item Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu_item'])) {
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['item_description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $image_alt_text = trim($_POST['image_alt_text']);
    $order_priority = (int)$_POST['item_order_priority'];
    $is_active = isset($_POST['item_is_active']) ? 1 : 0;

    $image_filename_for_db = null; // This will hold the final filename to insert into DB (or null)
    $file_tmp_path = null; // Holds the path to the temporary uploaded file
    $unique_filename = null; // Will hold the unique name for saving
    $target_file = null;     // Will hold the full target path for moving
    
    $file_upload_success_flag = true; // Overall flag for file upload process in this POST block

    // Check if a file was selected/uploaded for 'item_image_file'
    if (isset($_FILES['item_image_file']) && $_FILES['item_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_info = $_FILES['item_image_file'];
        
        // Handle general PHP upload errors (e.g., file too big for php.ini settings)
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $file_upload_success_flag = false;
            $error_code = $file_info['error'];
            $temp_alert_message = 'An unknown file upload error occurred (code ' . $error_code . ').'; // Default
            
            switch ($error_code) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE: $temp_alert_message = 'The uploaded file exceeds the maximum file size allowed by the server (check php.ini).'; break;
                case UPLOAD_ERR_PARTIAL: $temp_alert_message = 'The file was only partially uploaded.'; break;
                case UPLOAD_ERR_NO_TMP_DIR: $temp_alert_message = 'Missing a temporary folder for uploads.'; break;
                case UPLOAD_ERR_CANT_WRITE: $temp_alert_message = 'Failed to write file to disk. Check permissions.'; break;
                case UPLOAD_ERR_EXTENSION: $temp_alert_message = 'A PHP extension stopped the file upload.'; break;
            }
            $_SESSION['alert_message'] = $temp_alert_message;
            $_SESSION['alert_type'] = 'error';
            header("Location: food_menu.php");
            exit(); // Exit immediately if a general PHP upload error occurred
        } else { // UPLOAD_ERR_OK: File was successfully uploaded to temp directory, now validate content
            $file_tmp_path = $file_info['tmp_name'];
            $file_original_name = $file_info['name'];
            $file_size = $file_info['size'];
            $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

            // Basic validation: check if it's a real image
            if (!getimagesize($file_tmp_path)) {
                $file_upload_success_flag = false;
                $temp_alert_message = 'Uploaded file is not a valid image.';
            }

            // Validate file type - ONLY WEBP
            $allowed_extensions = ['webp'];
            if ($file_upload_success_flag && !in_array($file_ext, $allowed_extensions)) {
                $file_upload_success_flag = false;
                $temp_alert_message = 'Invalid image type. Only WEBP files are allowed.';
            }

            // Validate file size - MAX 1MB
            $max_file_size = 1048576; // 1 MB in bytes
            if ($file_upload_success_flag && $file_size > $max_file_size) {
                $file_upload_success_flag = false;
                $temp_alert_message = 'Image file is too large. Max 1MB.';
            }

            // If any file-specific validation failed, set session alert and redirect immediately
            if (!$file_upload_success_flag) {
                $_SESSION['alert_message'] = $temp_alert_message;
                $_SESSION['alert_type'] = 'error';
                header("Location: food_menu.php");
                exit();
            } else {
                // File is valid and ready. Assign to variables that will be used for final move.
                $unique_filename = uniqid('food_img_', true) . '.' . $file_ext;
                $target_file = $food_menu_upload_dir . $unique_filename;
                
                $file_to_move_tmp_path = $file_tmp_path; // Staging these for final move
                $file_to_move_target_path = $target_file;
                $image_filename_for_db = $unique_filename; // Set filename for DB if move is successful
            }
        }
    }
    // End of file upload validation and early exit if errors.
    // If we reach here, either no file was selected, or a valid file is ready to be moved later.

    // Basic validation for menu item text fields
    if (empty($item_name) || empty($price) || empty($category_id)) {
        $_SESSION['alert_message'] = 'Item Name, Price, and Category are required.';
        $_SESSION['alert_type'] = 'error';
    } else {
        // If a valid file was staged for upload, attempt to move it here
        if ($file_upload_success_flag && isset($file_to_move_tmp_path) && isset($file_to_move_target_path)) {
            if (!move_uploaded_file($file_to_move_tmp_path, $file_to_move_target_path)) {
                $_SESSION['alert_message'] = 'Error moving uploaded menu item image to final destination. Check folder permissions.';
                $_SESSION['alert_type'] = 'error';
                header("Location: food_menu.php"); exit(); // Redirect if move fails
            }
        }
        
        // Perform database insert
        $sql = "INSERT INTO food_menu_items (category_id, item_name, description, price, image_filename, image_alt_text, order_priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issdssii", $category_id, $item_name, $description, $price, $image_filename_for_db, $image_alt_text, $order_priority, $is_active);
            if ($stmt->execute()) {
                $_SESSION['alert_message'] = 'Food menu item added successfully!';
                $_SESSION['alert_type'] = 'success';
            } else {
                $_SESSION['alert_message'] = 'Error adding menu item to database: ' . $stmt->error;
                $_SESSION['alert_type'] = 'error';
                // Clean up uploaded file if DB insert fails
                if ($image_filename_for_db && file_exists($food_menu_upload_dir . $image_filename_for_db)) {
                    unlink($food_menu_upload_dir . $image_filename_for_db);
                }
            }
            $stmt->close();
        } else {
            $_SESSION['alert_message'] = 'Database error preparing item insert statement: ' . $conn->error;
            $_SESSION['alert_type'] = 'error';
        }
    }
    header("Location: food_menu.php"); // Final redirect after all processing
    exit();
}

// --- 6. ALL DATA FETCHING FOR DISPLAYING THE PAGE ---
// (No changes to these data fetching blocks)

// --- Fetch Food Menu Categories for Display and Dropdown ---
$food_menu_categories = [];
$sql_food_categories = "SELECT category_id, category_name, description, order_priority, is_active FROM food_menu_categories ORDER BY order_priority ASC, category_name ASC";
$result_food_categories = $conn->query($sql_food_categories);
if ($result_food_categories) {
    while ($row = $result_food_categories->fetch_assoc()) {
        $food_menu_categories[] = $row;
    }
} else {
    error_log('Error fetching food menu categories: ' . $conn->error);
}

// --- Fetch Food Menu Items for Display ---
$food_menu_items = [];
$sql_food_items = "SELECT fmi.*, fmc.category_name FROM food_menu_items fmi JOIN food_menu_categories fmc ON fmi.category_id = fmc.category_id ORDER BY fmc.order_priority ASC, fmi.order_priority ASC, fmi.item_name ASC";
$result_food_items = $conn->query($sql_food_items);
if ($result_food_items) {
    while ($row = $result_food_items->fetch_assoc()) {
        $food_menu_items[] = $row;
    }
} else {
    error_log('Error fetching food menu items: ' . $conn->error);
}

// Close database connection
$conn->close();

// 8. IMPORTANT: Include the header.php ONLY ONCE, right before the <body> tag opens.
//    All PHP logic that might set session alerts or send headers MUST be above this.
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
    <style>
        /* Basic styling for menu images in table */
        .menu-item-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
       
    </style>
</head>
<body>
<?php require_once 'includes/header.php'; ?>
    <div class="container mt-4">        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Menu Category</h3>
            </div>
            <div class="card-body">
                <form action="food_menu.php" method="POST">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_order_priority" class="form-label">Order Priority</label>
                        <input type="number" class="form-control" id="category_order_priority" name="category_order_priority" value="0" required>
                        <small class="form-text text-muted">Lower numbers appear first.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="category_is_active" name="category_is_active" checked>
                        <label class="form-check-label" for="category_is_active">Is Active?</label>
                    </div>
                    <button type="submit" name="add_menu_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Menu Item</h3>
            </div>
            <div class="card-body">
                <form action="food_menu.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="item_description" class="form-label">Description</label>
                        <textarea class="form-control" id="item_description" name="item_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($food_menu_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="item_image_file" class="form-label">Item Image</label>
                        <input type="file" class="form-control" id="item_image_file" name="item_image_file" accept="image/*">
                        <small class="form-text text-muted">Upload an image for this menu item.</small>
                    </div>
                    <div class="mb-3">
                        <label for="image_alt_text" class="form-label">Image Alt Text (for SEO & Accessibility)</label>
                        <input type="text" class="form-control" id="image_alt_text" name="image_alt_text">
                    </div>
                    <div class="mb-3">
                        <label for="item_order_priority" class="form-label">Order Priority</label>
                        <input type="number" class="form-control" id="item_order_priority" name="item_order_priority" value="0" required>
                        <small class="form-text text-muted">Lower numbers appear first within category.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="item_is_active" name="item_is_active" checked>
                        <label class="form-check-label" for="item_is_active">Is Active?</label>
                    </div>
                    <button type="submit" name="add_menu_item" class="btn btn-primary">Add Menu Item</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Existing Menu Categories</h3>
            </div>
            <div class="card-body">
                <?php if (empty($food_menu_categories)): ?>
                    <p>No food menu categories found.</p>
                <?php else: ?>
                    <div class="table-responsive" >
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($food_menu_categories as $cat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cat['category_id']); ?></td>
                                        <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($cat['description'], 0, 50)); ?><?php echo (strlen($cat['description']) > 50) ? '...' : ''; ?></td>
                                        <td><?php echo htmlspecialchars($cat['order_priority']); ?></td>
                                        <td><?php echo $cat['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                        <td>
                                            <a href="edit_food_category.php?id=<?php echo htmlspecialchars($cat['category_id']); ?>" class="btn btn-sm btn-info me-1">Edit</a>
                                            <a href="delete_food_category.php?id=<?php echo htmlspecialchars($cat['category_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category and all its items?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Existing Menu Items</h3>
            </div>
            <div class="card-body">
                <?php if (empty($food_menu_items)): ?>
                    <p>No food menu items found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Order</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($food_menu_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                                        <td>
                                            <?php if ($item['image_filename']): ?>
                                                <img src="<?php echo htmlspecialchars($food_menu_upload_dir . $item['image_filename']); ?>" alt="<?php echo htmlspecialchars($item['image_alt_text'] ?: $item['item_name']); ?>" class="menu-item-thumbnail">
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_priority']); ?></td>
                                        <td><?php echo $item['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                        <td>
                                            <a href="edit_food_item.php?id=<?php echo htmlspecialchars($item['item_id']); ?>" class="btn btn-sm btn-info me-1">Edit</a>
                                            <a href="delete_food_item.php?id=<?php echo htmlspecialchars($item['item_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this menu item?');">Delete</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>