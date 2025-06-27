<?php
// admin/includes/upload_tinymce_image.php

// This script handles image uploads from the TinyMCE editor.
// It receives a file, saves it, and returns the URL.

// Ensure session is started for authentication if needed (optional for simple uploads)
session_start();

// You might want to add more robust authentication here,
// e.g., check if the user is logged in and authorized to upload.
if (!isset($_SESSION['user_id'])) {
    // Return an error if not authenticated
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required to upload images.']);
    exit();
}


// Define the upload directory for TinyMCE images
// This should be relative to your project root, e.g., IQ/uploads/blog/
$upload_dir = '../../uploads/blog/'; // Adjust based on your actual file structure
// Ensure this directory exists and is writable by the web server!
// Example: IQ/uploads/blog/

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Use 0755 or 0775 in production for better security
}

// Check if the file was uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Generate a unique filename to prevent collisions
    $unique_filename = uniqid('blog_img_', true) . '.' . $file_ext;
    $target_file = $upload_dir . $unique_filename;

    $uploadOk = 1;
    $response_message = '';

    // Validate file type
    // CORRECTED: Allow common image formats
// Validate file type
$allowed_extensions = ['webp']; // <--- THIS IS THE PROBLEM
if (!in_array($file_ext, $allowed_extensions)) {
    $response_message = 'Invalid file type. Only WEBP are allowed.';
    $uploadOk = 0;
}

    // Validate file size (e.g., max 5MB, previously fixed)
    $max_file_size = 5 * 1024 * 1024; // 5MB
    if ($file_size > $max_file_size) {
        $response_message = 'File is too large. Max ' . ($max_file_size / (1024 * 1024)) . 'MB.';
        $uploadOk = 0;
    }

    // Check if it's a real image
    if ($uploadOk && !getimagesize($file_tmp_name)) {
        $response_message = 'File is not a valid image.';
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $response_message]);
    } else {
        // Attempt to move the uploaded file
        if (move_uploaded_file($file_tmp_name, $target_file)) {
            // Success: Return the URL to TinyMCE
            // The URL needs to be relative to the web root (IQ/)
            $relative_url = 'uploads/blog/' . $unique_filename; // This is the public URL
            header('Content-Type: application/json');
            echo json_encode(['location' => $relative_url]);
        } else {
            // Failure
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to upload image. Server error.']);
        }
    }
    
} else {
    // No file uploaded or an upload error occurred
    header('Content-Type: application/json');
    $error_msg = 'No file uploaded or upload error: ';
    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_msg .= 'File exceeds maximum upload size (check php.ini settings).';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_msg .= 'File was only partially uploaded.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_msg .= 'No file was uploaded.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_msg .= 'Missing a temporary folder.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_msg .= 'Failed to write file to disk (check folder permissions).';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_msg .= 'A PHP extension stopped the file upload.';
            break;
        default:
            $error_msg .= 'Unknown error.';
            break;
    }
    echo json_encode(['error' => $error_msg]);
}

exit();