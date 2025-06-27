<?php
// IQ/blog.php
// Require the main database connection
require_once 'includes/database.php'; // Corrected path based on your folder structure

$posts = [];
$categories = [];

// Define the blog image upload directory (relative to the script's location in IQ/ root)
$blog_upload_dir = 'uploads/blog/'; // CORRECTED PATH: No '..' needed if 'uploads' is in the same directory as 'blog.php'

// --- Fetch Blog Categories and their Published Post Counts ---
$sql_categories = "SELECT
                        bc.category_id,
                        bc.category_name,
                        bc.slug,
                        COUNT(bp.post_id) AS post_count
                    FROM
                        blog_categories bc
                    LEFT JOIN
                        blog_posts bp ON bc.category_id = bp.category_id AND bp.status = 'published'
                    GROUP BY
                        bc.category_id, bc.category_name, bc.slug
                    ORDER BY
                        bc.category_name ASC";

$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    error_log("Error fetching public blog categories: " . $conn->error);
}


// --- Determine Active Category (if any) ---
$active_category_slug = $_GET['category'] ?? null;


// --- Select only PUBLISHED posts (and filter by category if selected) ---
// Ensure featured_image_url is selected here
$sql_posts = "SELECT
                bp.post_id, bp.title, bp.slug, bp.excerpt, bp.published_at,
                bc.category_name, u.username, bp.featured_image_url -- Make sure this is included
            FROM
                blog_posts bp
            LEFT JOIN
                blog_categories bc ON bp.category_id = bc.category_id
            LEFT JOIN
                users u ON bp.author_user_id = u.user_id
            WHERE
                bp.status = 'published'";

if ($active_category_slug) {
    // If a category is selected, add a filter
    $sql_posts .= " AND bc.slug = ?";
}

$sql_posts .= " ORDER BY bp.published_at DESC";

if ($stmt_posts = $conn->prepare($sql_posts)) {
    if ($active_category_slug) {
        $stmt_posts->bind_param("s", $active_category_slug);
    }
    $stmt_posts->execute();
    $result_posts = $stmt_posts->get_result();

    if ($result_posts) {
        while ($row = $result_posts->fetch_assoc()) {
            $posts[] = $row;
        }
    } else {
        error_log("Error fetching public blog posts: " . $conn->error);
    }
    $stmt_posts->close();
} else {
    error_log("Error preparing public blog posts statement: " . $conn->error);
}


$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Blog - UpdateIQ </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Blog Banner Styles (for blog listing page) */
        .blog-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://placehold.co/1500x400/007bff/ffffff?text=Our+Blog+Updates') no-repeat center center;
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            color: white;
            text-align: center;
            position: relative;
            z-index: 1;
            margin-bottom: 50px;
        }
        .blog-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }
        .blog-banner h1 {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .blog-banner .breadcrumb {
            --bs-breadcrumb-divider-color: rgba(255, 255, 255, 0.75);
        }
        .blog-banner .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
        }
        .blog-banner .breadcrumb-item.active {
            color: white;
            font-weight: bold;
        }

        /* Blog Post Cards */
        .blog-post-card {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            /* Removed padding from here as it's in card-body */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex; /* Make card a flex container */
            flex-direction: column; /* Stack content vertically */
            overflow: hidden; /* Hide overflowing parts like sharp image corners */
        }

        .blog-post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Style for the featured image within the card */
        .blog-post-card .card-img-top-custom { /* Added custom class for specific styling */
            width: 100%;
            height: 200px; /* Fixed height for uniform thumbnails */
            object-fit: cover; /* Cover the area, cropping if necessary */
            border-top-left-radius: calc(8px - 1px); /* Match card border radius */
            border-top-right-radius: calc(8px - 1px); /* Match card border radius */
            margin-bottom: 0; /* Remove default margin-bottom from img-fluid */
        }
        
        .blog-post-card .card-body {
            padding: 20px; /* Restore padding to card-body */
            display: flex;
            flex-direction: column;
            flex-grow: 1; /* Allow card body to grow and push footer down */
        }

        .blog-post-card h3 a {
            text-decoration: none;
            color: #007bff;
        }

        .blog-post-meta {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .blog-post-excerpt {
            margin-bottom: 20px;
            flex-grow: 1; /* Allow excerpt to take available space */
        }

        /* Sidebar Styles (retained from your code) */
        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 20px;
            transition: all 0.3s ease;
            z-index: 10;
            margin-bottom: 30px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .blog-sidebar {
            position: sticky;
            top: 120px;
            transition: all 0.3s ease;
            z-index: 10;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .categories-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            overflow-x: hidden;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) rgba(0, 0, 0, 0.1);
            padding-right: 5px;
        }

        .categories-list li {
            margin-bottom: 12px;
        }

        .categories-list a:hover,
        .categories-list a.active {
            background: var(--primary-color, #007bff);
            color: white;
            transform: translateX(5px);
        }

        .categories-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            color: #666;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(79, 193, 164, 0.03);
        }

        .category-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .categories-list a:hover i,
        .categories-list a.active i {
            color: white;
        }

        .categories-list a:hover .post-count,
        .categories-list a.active .post-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .post-count {
            background: rgba(79, 193, 164, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            color: var(--primary-color, #007bff);
            font-weight: 600;
            transition: all 0.3s ease;
        }
    </style>

</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="blog-banner">
        <h1 class="text-center text-white">Our Blog Updates</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blog</li>
            </ol>
        </nav>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-3 blog-sidebar">
                <div class="card sidebar">
                    <div class="card-header bg-white pb-0">
                         <h2 class="h5 mb-0">Categories</h2>
                    </div>
                    <div class="card-body pt-3">
                        <ul class="categories-list">
                            <li>
                                <a href="blog.php" class="<?php echo ($active_category_slug === null) ? 'active' : ''; ?>">
                                    <div class="category-info">
                                        <i class="fas fa-layer-group"></i>
                                        <span>All Categories</span>
                                    </div>
                                    <span class="post-count"><?php echo array_sum(array_column($categories, 'post_count')); ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="blog.php?category=<?php echo htmlspecialchars($category['slug']); ?>"
                                       class="<?php echo ($active_category_slug == $category['slug']) ? 'active' : ''; ?>">
                                        <div class="category-info">
                                            <i class="fas fa-folder"></i>
                                            <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        </div>
                                        <span class="post-count"><?php echo htmlspecialchars($category['post_count']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <h2 class="mb-4">Latest Blog Posts <?php echo $active_category_slug ? 'in "' . htmlspecialchars($active_category_slug) . '"' : ''; ?></h2>
                <hr>
                <?php if (empty($posts)): ?>
                    <div class="alert alert-info">No published blog posts found yet<?php echo $active_category_slug ? ' in this category' : ''; ?>. Check back soon!</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-lg-6 col-md-6 mb-4">
                                <div class="blog-post-card h-100">
                                    <?php if ($post['featured_image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($blog_upload_dir . $post['featured_image_url']); ?>"
                                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                                             class="card-img-top-custom"> <?php else: ?>
                                        <img src="https://placehold.co/600x400/e9ecef/555?text=No+Image" 
                                             alt="No image available" 
                                             class="card-img-top-custom">
                                    <?php endif; ?>

                                    <div class="card-body d-flex flex-column">
                                        <h3 class="h5 mb-3 card-title">
                                            <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="blog-post-meta small text-muted mb-3">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                                            <span class="mx-2">•</span>
                                            <i class="far fa-user me-1"></i>
                                            <?php echo htmlspecialchars($post['username'] ?: 'Unknown Author'); ?>
                                            <?php if ($post['category_name']): ?>
                                                <span class="mx-2">•</span>
                                                <a href="blog.php?category=<?php echo htmlspecialchars($post['slug']); ?>" class="text-decoration-none text-muted">
                                                    <i class="far fa-folder me-1"></i>
                                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </p>
                                        <div class="blog-post-excerpt card-text flex-grow-1 mb-3">
                                            <?php
                                            echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 100) . '...');
                                            ?>
                                        </div>
                                        <div class="mt-auto">
                                            <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-primary btn-sm">
                                                Read More <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; // Public footer ?>
</body>

</html>