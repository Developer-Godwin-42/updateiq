<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data from session - CORRECTED TO USE 'username' AND 'user_role'
$user_name = $_SESSION['username'] ?? 'User'; // Use 'username' from session
$user_role = $_SESSION['user_role'] ?? 'Editor'; // Use 'user_role' from session
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.css"/>
    
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
                
                <?php if ($user_role == 'Admin'): // Check for 'Admin' string, not 'admin' ?>
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
                
                <li class="nav-item" id="nav-media">
                    <a href="gallery.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' || basename($_SERVER['PHP_SELF']) == 'edit_gallery_image.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        <span>Media</span>
                    </a>
                </li>
                
                <li class="nav-item" id="nav-menus">
                    <a href="menus.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'menus.php' || basename($_SERVER['PHP_SELF']) == 'edit_menu.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bars"></i>
                        <span>Menus</span>
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
                <h4 class="mb-0 ml-3">
                    <?php
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
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </nav>
        
        <div class="container-fluid py-4 pt-0">