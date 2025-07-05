<?php
// includes/header.php

// Removed the session_start() check here. It should ALWAYS be done once at the very top of the calling page (e.g., food_menu.php)
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data from session - CORRECTED TO USE 'username' AND 'user_role'
$user_name = $_SESSION['username'] ?? 'User'; // Use 'username' from session
$user_role = $_SESSION['user_role'] ?? 'Editor'; // Use 'user_role' from session


// Check for and display session-based alert messages
// This block should remain here, as it generates the HTML/JS for the toast
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'] ?? 'info'; // Default to info if type not set
    unset($_SESSION['alert_message']); // Clear the message after displaying
    unset($_SESSION['alert_type']);   // Clear the type after displaying
?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);" id="liveToast"  class="toast align-items-center text-white bg-<?php echo $alert_type; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" >
                    <?php echo htmlspecialchars($alert_message); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastLiveExample = document.getElementById('liveToast');
            if (toastLiveExample) {
                var toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }
        });
    </script>
<?php
}
// END of toast logic block
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UpdateIQ <?php echo isset($page_title) ? ' - ' . htmlspecialchars($page_title) : ''; ?></title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.js.iife.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.css" />
    <script src="assets/js/arrowLeftCircle.json"></script>

    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2 class="text-white">UpdateIQ </h2>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav sudo-element flex-column">
                <li class="nav-item" id="nav-dashboard">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' || basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if ($user_role == 'Admin'): ?>
                    <li class="nav-item" id="nav-users">
                        <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item" id="nav-posts">
                    <a href="blog.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' || basename($_SERVER['PHP_SELF']) == 'edit_blog_post.php' ? 'active' : ''; ?>">
                        <i class="fas fa-newspaper"></i>
                        <span>Posts</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="gallery.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' || basename($_SERVER['PHP_SELF']) == 'edit_gallery_image.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        <span>Media</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="food_menu.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'food_menu.php' || basename($_SERVER['PHP_SELF']) == 'edit_food_item.php' || basename($_SERVER['PHP_SELF']) == 'edit_food_category.php' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> <span>Food Menu</span>
                    </a>
                </li>

                <li class="nav-item" id="nav-view-site">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Site</span>
                    </a>
                </li>

                <li class="nav-item" id="nav-logout">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <nav class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-dark d-md-none toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <?php if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php' && basename($_SERVER['PHP_SELF']) !== 'index.php' && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
                    <button class="btn btn-link text-dark ms-2" onclick="window.history.back();" title="Go to previous page">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left-circle">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 8 8 12 12 16"></polyline>
                            <line x1="16" y1="12" x2="8" y2="12"></line>
                        </svg>
                    </button>
                <?php endif; ?>
                <h4 class="mb-0 ms-3"> <?php
                                            // This uses the $page_title variable set in individual pages
                                            echo htmlspecialchars($page_title);
                                            ?>
                </h4>
            </div>

            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid py-4 pt-0">