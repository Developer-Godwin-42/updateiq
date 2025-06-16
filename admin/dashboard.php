<?php
// Set page title
$page_title = 'Dashboard';

// Initialize variables
$stats = [
    'posts' => 0,
    'published_posts' => 0,
    'draft_posts' => 0,
    'pending_posts' => 0,
    'categories' => 0,
    'users' => 0,
    'admin_users' => 0,
    'editor_users' => 0,
    'author_users' => 0,
    'media' => 0,
    'comments' => 0
];
$recent_posts = [];
$monthly_labels = [];
$monthly_data = [];
$user_activity = [
    'user_posts' => 0,
    'user_comments' => 0,
    'user_media' => 0
];

// Include header (assuming this file exists and handles session_start() and authentication)
require_once 'includes/header.php'; // Make sure this file properly starts session and includes navigation

// Get user role and name from session (assuming header.php sets these based on login)
$user_role = $_SESSION['user_role'] ?? 'Editor'; // Use 'user_role' as defined in login_process.php
$user_name = $_SESSION['username'] ?? 'User';   // Use 'username' as defined in login_process.php

// Get database connection
require_once '../includes/database.php'; // Adjust path if necessary (e.g., ../includes/database.php if header.php is in admin/)

try {
    // Start loading state
    echo '<div class="loading-overlay">
            <div class="typewriter">
                <div class="slide">
                    <i></i>
                </div>
                <div class="paper"></div>
                <div class="keyboard"></div>
            </div>
        </div>';

    // Total posts with status breakdown
    $posts_query = "SELECT
        COUNT(post_id) as total_posts,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_posts,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_posts
        FROM blog_posts";
    $posts_result = $conn->query($posts_query);
    if ($posts_result) { // Check if query was successful
        $posts_data = $posts_result->fetch_assoc();
        $stats['posts'] = $posts_data['total_posts'] ?? 0;
        $stats['published_posts'] = $posts_data['published_posts'] ?? 0;
        $stats['draft_posts'] = $posts_data['draft_posts'] ?? 0;
        $stats['pending_posts'] = $posts_data['archived_posts'] ?? 0;
    } else {
        throw new Exception("Error fetching post stats: " . $conn->error);
    }

    // Total categories with post counts
    $categories_query = "SELECT bc.category_id, bc.category_name, COUNT(bp.post_id) as post_count
        FROM blog_categories bc
        LEFT JOIN blog_posts bp ON bc.category_id = bp.category_id
        GROUP BY bc.category_id, bc.category_name"; // Group by category_name as well if not already primary key
    $categories_result = $conn->query($categories_query);
    $categories = [];
    $stats['categories'] = 0; // Initialize total categories
    if ($categories_result) {
        $stats['categories'] = $categories_result->num_rows;
        while ($row = $categories_result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        throw new Exception("Error fetching categories stats: " . $conn->error);
    }

    // Users by role
    $users_query = "SELECT
        COUNT(u.user_id) as total_users,
        SUM(CASE WHEN r.role_name = 'Admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN r.role_name = 'Editor' THEN 1 ELSE 0 END) as editor_users
        FROM users u JOIN roles r ON u.role_id = r.role_id"; // Join with roles table
    $users_result = $conn->query($users_query);
    if ($users_result) {
        $users_data = $users_result->fetch_assoc();
        $stats['users'] = $users_data['total_users'] ?? 0;
        $stats['admin_users'] = $users_data['admin_users'] ?? 0;
        $stats['editor_users'] = $users_data['editor_users'] ?? 0;
        $stats['author_users'] = 0; // We only defined 'Admin' and 'Editor' roles in our schema
    } else {
        throw new Exception("Error fetching user stats: " . $conn->error);
    }

    // Media library stats (gallery_images)
    // Note: Our schema doesn't have a 'file_type' in gallery_images, assuming all are images
    $media_query = "SELECT
        COUNT(image_id) as total_media
        FROM gallery_images";
    $media_result = $conn->query($media_query);
    if ($media_result) {
        $stats['media'] = $media_result->fetch_assoc()['total_media'] ?? 0;
    } else {
        throw new Exception("Error fetching media stats: " . $conn->error);
    }

    // Comments count - (Requires a 'comments' table, which we haven't built. This will cause an error.)
    // For now, I'll comment it out or set to 0. If you add comments, ensure the table exists.
    // Assuming 'comments' table exists with 'comment_id' column
    /*
    $comments_query = "SELECT COUNT(comment_id) as total_comments FROM comments";
    $comments_result = $conn->query($comments_query);
    if ($comments_result) {
        $stats['comments'] = $comments_result->fetch_assoc()['total_comments'] ?? 0;
    } else {
        throw new Exception("Error fetching comments stats: " . $conn->error);
    }
    */
    $stats['comments'] = 0; // Default to 0 if comments table is not implemented yet


    // Recent posts with author info
    $recent_posts_query = "SELECT bp.post_id, bp.title, bp.status, bp.created_at, u.username as author_name
        FROM blog_posts bp
        LEFT JOIN users u ON bp.author_user_id = u.user_id
        ORDER BY bp.created_at DESC LIMIT 5";
    $recent_posts_result = $conn->query($recent_posts_query);
    $recent_posts = [];
    if ($recent_posts_result && $recent_posts_result->num_rows > 0) {
        while ($row = $recent_posts_result->fetch_assoc()) {
            $recent_posts[] = $row;
        }
    } else {
        // No recent posts found is not an error, just an empty array
    }

    // Get monthly post counts for the last 6 months
    $monthly_posts_query = "SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(post_id) as post_count
        FROM blog_posts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";
    $monthly_posts_result = $conn->query($monthly_posts_query);
    $monthly_posts = [];
    $monthly_labels = [];
    $monthly_data = [];

    if ($monthly_posts_result) {
        while ($row = $monthly_posts_result->fetch_assoc()) {
            $monthly_posts[$row['month']] = $row['post_count'];
        }

        // Generate last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthly_labels[] = date('M Y', strtotime($month . '-01'));
            $monthly_data[] = $monthly_posts[$month] ?? 0;
        }
    } else {
        throw new Exception("Error fetching monthly post stats: " . $conn->error);
    }

    // Get current user's activity
    $user_id = $_SESSION['user_id'] ?? 0; // Use user_id from session
    $user_activity_query = "SELECT
        (SELECT COUNT(post_id) FROM blog_posts WHERE author_user_id = $user_id) as user_posts,
        (SELECT COUNT(image_id) FROM gallery_images WHERE uploaded_by_user_id = $user_id) as user_media";
    // If comments table exists and user_id is linked to comments:
    // (SELECT COUNT(comment_id) FROM comments WHERE user_id = $user_id) as user_comments,
    $user_activity_result = $conn->query($user_activity_query);
    if ($user_activity_result) {
        $user_activity = $user_activity_result->fetch_assoc();
    } else {
        throw new Exception("Error fetching user activity stats: " . $conn->error);
    }

} catch (Exception $e) {
    // Log error
    error_log('Dashboard error: ' . $e->getMessage());

    // Display user-friendly error message
    $error_message = '<div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading dashboard data. Please try again later.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>';

    // Reset stats to default values
    $stats = array_fill_keys(array_keys($stats), 0);
    $recent_posts = [];
    $monthly_labels = [];
    $monthly_data = [];
    $user_activity = array_fill_keys(array_keys($user_activity), 0);
} finally {
    // Ensure database connection is closed if it was opened
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>

<!-- Loading Overlay -->
<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 1060;
    display: flex;
    justify-content: center;
    align-items: center;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    z-index: 1060;
    display: flex;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(5px);
}

.loading-overlay .typewriter {
    position: relative;
    width: 120px;
    height: 80px;
}
</style>

<!-- Welcome Header -->
<div class="row">
    <div class="col-12">
        <div class="welcome-card bg-gradient-primary text-white p-4 rounded-3">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                <div class="mb-3 mb-md-0">
                    <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($user_name); ?>! </h2>
                    <p class="mb-0 opacity-75">Here's what's happening with your website today.</p>
                </div>
                <div class="d-flex">
                    <a href="blog.php" class="btn btn-light me-2">
                        <i class="fas fa-plus me-2"></i>
                        New Post
                    </a>
                    <a href="#" class="btn btn-outline-light">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <?php echo $error_message; ?>
<?php endif; ?>

<!-- Stats Overview -->
<div class="row g-4 mb-4">
    <?php if (isset($error_message)): ?>
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-info-circle me-2"></i>
                Some data may be incomplete due to previous errors. Please refresh the page to try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

<div class="row g-4 mb-4 mt-0">
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-2">
                    <i class="fas fa-newspaper"></i>
                </div>
                <span class="text-muted small">Posts</span>
            </div>
            <div class="d-flex align-items-end justify-content-between">
                <h3 class="mb-0"><?php echo number_format($stats['posts']); ?></h3>
                <div class="badge bg-primary bg-opacity-10 text-primary">
                    <?php echo number_format($stats['published_posts']); ?> published
                </div>
            </div>
            <div class="progress mt-2" style="height: 4px;">
                <?php 
                $total = max($stats['posts'], 1); // Avoid division by zero
                $published_pct = ($stats['published_posts'] / $total) * 100;
                $draft_pct = ($stats['draft_posts'] / $total) * 100;
                $archived_pct = ($stats['pending_posts'] / $total) * 100;
                ?>
                <div class="progress-bar bg-success" style="width: <?php echo $published_pct; ?>%" title="Published"></div>
                <div class="progress-bar bg-warning" style="width: <?php echo $archived_pct; ?>%" title="Archived"></div>
                <div class="progress-bar bg-secondary" style="width: <?php echo $draft_pct; ?>%" title="Draft"></div>
            </div>
            <div class="mt-2">
                <a href="blog.php" class="btn btn-sm btn-outline-primary w-100">Manage Posts</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-success bg-opacity-10 text-success rounded-3 p-2 me-2">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="text-muted small">Categories</span>
            </div>
            <h3 class="mb-0"><?php echo number_format($stats['categories']); ?></h3>
            <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar bg-success" style="width: 75%" title="Active"></div>
            </div>
            <div class="mt-2">
                <a href="blog.php" class="btn btn-sm btn-outline-success w-100">Manage Categories</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-info bg-opacity-10 text-info rounded-3 p-2 me-2">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-muted small">Users</span>
            </div>
            <div class="d-flex align-items-end justify-content-between">
                <h3 class="mb-0"><?php echo number_format($stats['users']); ?></h3>
                <div class="user-role-stats">
                    <span class="badge bg-primary" title="Admins"><?php echo $stats['admin_users'] ?? 0; ?></span>
                    <span class="badge bg-info" title="Editors"><?php echo $stats['editor_users'] ?? 0; ?></span>
                    <span class="badge bg-secondary" title="Authors"><?php echo $stats['author_users'] ?? 0; ?></span>
                </div>
            </div>
            <div class="mt-2">
                <a href="users.php" class="btn btn-sm btn-outline-info w-100">Manage Users</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-warning bg-opacity-10 text-warning rounded-3 p-2 me-2">
                    <i class="fas fa-images"></i>
                </div>
                <span class="text-muted small">Media</span>
            </div>
            <h3 class="mb-0"><?php echo number_format($stats['media']); ?></h3>
            <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar bg-warning" style="width: 60%" title="Storage"></div>
            </div>
            <div class="mt-2">
                <a href="gallery.php" class="btn btn-sm btn-outline-warning w-100">Manage Media</a>
            </div>
        </div>
    </div>

    <!-- <div class="col-6 col-md-4 col-lg-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-purple bg-opacity-10 text-purple rounded-3 p-2 me-2">
                    <i class="fas fa-comments"></i>
                </div>
                <span class="text-muted small">Comments</span>
            </div>
            <h3 class="mb-0"><?php echo number_format($stats['comments']); ?></h3>
            <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar bg-purple" style="width: 85%" title="Engagement"></div>
            </div>
            <div class="mt-2">
                <a href="#" class="btn btn-sm btn-outline-purple w-100">Manage Comments</a>
            </div>
        </div>
    </div> -->

    <!-- <div class="col-6 col-md-4 col-lg-4">
        <div class="stats-card h-100">
            <div class="d-flex align-items-center mb-2">
                <div class="icon-wrapper bg-dark bg-opacity-10 text-dark rounded-3 p-2 me-2">
                    <i class="fas fa-server"></i>
                </div>
                <span class="text-muted small">System Status</span>
            </div>
            <div class="d-flex align-items-center">
                <div class="system-status-indicator bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                <span class="ms-2 small">All systems operational</span>
            </div>
            <div class="mt-2">
                <a href="#" class="btn btn-sm btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#systemInfoModal">
                    <i class="fas fa-info-circle me-2"></i>
                    System Info
                </a>
            </div>
        </div>
    </div> -->
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Posts</h5>
                <a href="blog.php" class="btn btn-sm btn-outline-primary">View All</a> </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_posts)): ?>
                            <?php foreach ($recent_posts as $post): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td><?php echo htmlspecialchars($post['author_name']); ?></td> <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            if ($post['status'] === 'published') echo 'success';
                                            elseif ($post['status'] === 'draft') echo 'secondary'; // Using secondary for draft
                                            elseif ($post['status'] === 'archived') echo 'warning'; // Using warning for archived
                                            else echo 'info'; // Fallback for other statuses
                                        ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_blog_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-icon" data-bs-toggle="tooltip" title="Edit"> <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-icon text-danger delete-post" data-id="<?php echo $post['post_id']; ?>" data-bs-toggle="tooltip" title="Delete"> <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="text-muted">No posts found. <a href="blog.php">Create your first post</a></div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="blog.php" class="list-group-item list-group-item-action d-flex align-items-center"> <div class="icon-wrapper bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">New Post</h6>
                            <small class="text-muted">Create a new blog post</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="gallery.php" class="list-group-item list-group-item-action d-flex align-items-center"> <div class="icon-wrapper bg-success bg-opacity-10 text-success rounded p-2 me-3">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Upload Media</h6>
                            <small class="text-muted">Add new images or files</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center"> <div class="icon-wrapper bg-info bg-opacity-10 text-info rounded p-2 me-3">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Profile Settings</h6>
                            <small class="text-muted">Update your profile information</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Your Activity</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Posts Created
                        <span class="badge bg-primary rounded-pill"><?php echo $user_activity['user_posts'] ?? 0; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Media Uploaded
                        <span class="badge bg-success rounded-pill"><?php echo $user_activity['user_media'] ?? 0; ?></span>
                    </li>
                    <?php if (isset($user_activity['user_comments'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Comments Made
                        <span class="badge bg-info rounded-pill"><?php echo $user_activity['user_comments'] ?? 0; ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Storage</span>
                        <span>65% Used</span> </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="d-flex justify-content-between mb-1">
                        <span>PHP Version</span>
                        <span><?php echo phpversion(); ?></span>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Database</span>
                        <span>MySQL</span>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Server</span>
                        <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <canvas id="postStatusChart"
                    data-published="<?php echo $stats['published_posts']; ?>"
                    data-draft="<?php echo $stats['draft_posts']; ?>"
                    data-pending="<?php echo $stats['pending_posts']; ?>"> </canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <canvas id="monthlyPostsChart"
                    data-labels='<?php echo json_encode($monthly_labels); ?>'
                    data-data='<?php echo json_encode($monthly_data); ?>'>
                </canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <canvas id="userActivityChart"
                    data-posts="<?php echo $user_activity['user_posts'] ?? 0; ?>"
                    data-comments="<?php echo $user_activity['user_comments'] ?? 0; ?>"
                    data-media="<?php echo $user_activity['user_media'] ?? 0; ?>">
                </canvas>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/dashboard.js"></script>
<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Handle delete post
$(document).on('click', '.delete-post', function(e) {
    e.preventDefault();
    const postId = $(this).data('id');

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4361ee',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'delete_blog_post.php',
                type: 'GET',
                data: { id: postId },
                dataType: 'html',
                success: function(response) {
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    Swal.fire(
                        'Error!',
                        'An error occurred while deleting the post: ' + error,
                        'error'
                    );
                }
            });
        }
    });
});

</script>


<?php
// Include footer
require_once 'includes/footer.php';
?>
