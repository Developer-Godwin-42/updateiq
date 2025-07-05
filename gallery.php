<?php
// IQ/gallery.php
require_once 'includes/database.php'; // Correct path to your database connection

$images_by_category = [];
$categories = []; // To store all categories for display

// Define the public path to gallery images (relative to IQ/ root)
$gallery_upload_dir = 'uploads/gallery/';

// --- Fetch all categories ---
$sql_categories = "SELECT category_id, category_name, description FROM gallery_categories ORDER BY category_name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
        $images_by_category[$row['category_id']] = [
            'name' => $row['category_name'],
            'description' => $row['description'],
            'images' => [] // Initialize empty array for images in this category
        ];
    }
} else {
    error_log("Error fetching gallery categories: " . $conn->error);
}

// Add a default "Uncategorized" entry if there are images without a category
$images_by_category[0] = [
    'name' => 'Uncategorized',
    'description' => 'Images that are not assigned to a specific category.',
    'images' => []
];

// --- Fetch all PUBLISHED gallery images ---
// Filter by is_published = TRUE
$sql_images = "SELECT gi.image_id, gi.category_id, gi.image_filename, gi.image_alt_text, gi.title_tag, gi.description, gi.uploaded_at, u.username
               FROM gallery_images gi
               LEFT JOIN users u ON gi.uploaded_by_user_id = u.user_id
               WHERE gi.is_published = TRUE
               ORDER BY gi.category_id ASC, gi.uploaded_at DESC";

$result_images = $conn->query($sql_images);
if ($result_images) {
    while ($row = $result_images->fetch_assoc()) {
        $category_id = $row['category_id'] ?? 0; // Default to 0 for uncategorized
        // Check if category exists in our fetched categories, otherwise use Uncategorized (0)
        if (!isset($images_by_category[$category_id])) {
            $category_id = 0;
        }
        $images_by_category[$category_id]['images'][] = $row;
    }
} else {
    error_log("Error fetching gallery images: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Photo Gallery - UpdateIQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="gallery-banner">
        <h1 class="text-center text-white">Our Photo Gallery</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gallery</li>
            </ol>
        </nav>
    </div>

    <div class="container my-5">
        <?php if (empty($images_by_category) || (count($images_by_category) == 1 && empty($images_by_category[0]['images']))): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i> No published images found in the gallery yet. Please check back later!
            </div>
        <?php else: ?>
            <?php foreach ($images_by_category as $cat_id => $category_data): ?>
                <?php if (!empty($category_data['images'])): // Only show categories that have images ?>
                    <section class="gallery-category-section">
                        <h2><?php echo htmlspecialchars($category_data['name']); ?></h2>
                        <?php if (!empty($category_data['description'])): ?>
                            <p class="lead"><?php echo htmlspecialchars($category_data['description']); ?></p>
                        <?php endif; ?>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php foreach ($category_data['images'] as $image): ?>
                                <div class="col">
                                    <div class="card gallery-image-card">
                                        <img src="<?php echo htmlspecialchars($gallery_upload_dir . $image['image_filename']); ?>"
                                             class="card-img-top"
                                             alt="<?php echo htmlspecialchars($image['image_alt_text'] ?: 'Gallery Image'); ?>"
                                             title="<?php echo htmlspecialchars($image['title_tag'] ?: $image['image_alt_text']); ?>"
                                             loading="lazy">
                                        <!-- <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($image['image_alt_text'] ?: 'No Alt Text'); ?></h5>
                                            <?php
                                            if (!empty($image['description'])): ?>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($image['description'], 0, 100)); ?><?php echo (strlen($image['description']) > 100) ? '...' : ''; ?></p>
                                            <?php endif; ?>
                                            <small class="text-muted mt-auto">Uploaded by: <?php echo htmlspecialchars($image['username'] ?: 'N/A'); ?></small>
                                        </div> -->
                                        
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>