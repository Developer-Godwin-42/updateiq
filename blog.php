<?php
// public/blog.php
require_once 'includes/database.php'; // Adjust path based on your structure

$posts = [];
// Select only PUBLISHED posts
$sql_posts = "SELECT bp.post_id, bp.title, bp.slug, bp.excerpt, bp.published_at, bc.category_name, u.username FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id LEFT JOIN users u ON bp.author_user_id = u.user_id WHERE bp.status = 'published' ORDER BY bp.published_at DESC";
$result_posts = $conn->query($sql_posts);

if ($result_posts) {
    while ($row = $result_posts->fetch_assoc()) {
        $posts[] = $row;
    }
} else {
    // In a public facing site, you might log the error instead of displaying it to the user
    error_log("Error fetching public blog posts: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .blog-post-card {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .blog-post-card h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .blog-post-card h2 a {
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
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Latest Blog Posts</h1>
        <hr>

        <?php if (empty($posts)): ?>
            <div class="alert alert-info">No published blog posts found yet. Check back soon!</div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="blog-post-card">
                    <h2><a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h2>
                    <p class="blog-post-meta">
                        Published on <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                        by <?php echo htmlspecialchars($post['username'] ?: 'Unknown Author'); ?>
                        <?php if ($post['category_name']): ?>
                            in <span class="badge bg-info"><?php echo htmlspecialchars($post['category_name']); ?></span>
                        <?php endif; ?>
                    </p>
                    <div class="blog-post-excerpt">
                        <?php
                        // Display excerpt, or a trimmed version of content if no excerpt
                        echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 200) . '...');
                        ?>
                    </div>
                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-primary">Read More</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>