<?php
// public/post.php
require_once 'includes/database.php'; // Adjust path

$post = null;
$message = '';

// Define the blog image upload directory (must match admin/blog.php)
$blog_upload_dir = '../uploads/blog/';

$categories = []; // Initialize categories array (from previous blog.php update)

// --- Fetch Blog Categories and their Published Post Counts (from previous blog.php update) ---
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


// --- Determine Active Category for sidebar (from previous blog.php update) ---
$active_category_slug = $_GET['category'] ?? null;


// --- Select a single PUBLISHED post with the given slug (MODIFIED to fetch featured_image_url) ---
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $post_slug = trim($_GET['slug']);

    $sql = "SELECT bp.post_id, bp.title, bp.slug, bp.content, bp.excerpt, bp.published_at,
                   bc.category_name, u.username, bp.meta_title, bp.meta_description, bp.meta_keywords,
                   bp.featured_image_url -- ADDED this column to the SELECT statement
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
            LEFT JOIN users u ON bp.author_user_id = u.user_id
            WHERE bp.slug = ? AND bp.status = 'published'";

    if ($stmt = $conn->prepare($sql)) { // Use $public_conn for public database connection
        $stmt->bind_param("s", $post_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $post = $result->fetch_assoc();
        } else {
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
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Blog Banner Styles (for post page as well) */
        .blog-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php
                if ($post && $post['featured_image_url']) {
                    echo htmlspecialchars($blog_upload_dir . $post['featured_image_url']);
                } else {
                    echo 'https://via.placeholder.com/1500x400/007bff/ffffff?text=Blog+Post+Banner'; 
                }
            ?>') no-repeat center center;
            background-size: cover;
            padding: 80px 0;
            color: white;
            text-align: center;
            position: relative;
            z-index: 1; 
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
        
        /* Styles for blog content (from TinyMCE) */
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

        .blog-content table,
        .blog-content th,
        .blog-content td {
            border: 1px solid #ddd;
        }

        .blog-content th,
        .blog-content td {
            padding: 8px;
            text-align: left;
        }

        .blog-content pre,
        .blog-content code {
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

        /* Sidebar styles (copied from blog.php) */
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
    
    <?php include 'includes/header.php'; // Public header if available ?>

    <div class="blog-banner" style="<?php echo ($post && $post['featured_image_url']) ? 'background-image: url(' . htmlspecialchars($blog_upload_dir . $post['featured_image_url']) . ');' : ''; ?>">
        <h1 class="text-center text-white"><?php echo htmlspecialchars($post['title'] ?? 'Blog Post'); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($post['title'] ?? 'Post'); ?></li>
            </ol>
        </nav>
    </div>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($post): ?>
                    <h1 class="mb-3 d-none"><?php echo htmlspecialchars($post['title']); ?></h1> <p class="text-muted small">
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
            <div class="col-lg-4 blog-sidebar">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; // Public footer ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const blogContent = document.querySelector('.blog-content');
        if (blogContent) {
            const images = blogContent.querySelectorAll('img');
            images.forEach(img => {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
                // Optional: Ensure alt and title are present from editor (or set generic fallback)
                if (!img.hasAttribute('alt')) {
                    img.setAttribute('alt', 'Blog image');
                }
                if (!img.hasAttribute('title')) {
                    img.setAttribute('title', img.getAttribute('alt'));
                }
            });
        }
    });
</script>
</body>
</html>