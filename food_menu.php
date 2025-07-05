<?php
// IQ/food_menu.php
require_once 'includes/database.php'; // Correct path to your database connection

$menu_categories_with_items = []; // To store categories and their active items

// Define the public path to food menu images (relative to IQ/ root)
$food_menu_upload_dir = 'uploads/food_menu/';

// --- Fetch all active food menu categories ---
$sql_fetch_categories = "SELECT category_id, category_name, description FROM food_menu_categories WHERE is_active = TRUE ORDER BY order_priority ASC, category_name ASC";
$result_categories = $conn->query($sql_fetch_categories);

if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $menu_categories_with_items[$row['category_id']] = [
            'name' => $row['category_name'],
            'description' => $row['description'],
            'items' => [] // Initialize array for items in this category
        ];
    }
} else {
    error_log("Error fetching food menu categories for public view: " . $conn->error);
}

// --- Fetch all active food menu items ---
$sql_fetch_items = "SELECT fmi.item_id, fmi.category_id, fmi.item_name, fmi.description, fmi.price, fmi.image_filename, fmi.image_alt_text
                    FROM food_menu_items fmi
                    WHERE fmi.is_active = TRUE
                    ORDER BY fmi.order_priority ASC, fmi.item_name ASC";
$result_items = $conn->query($sql_fetch_items);

if ($result_items) {
    while ($row = $result_items->fetch_assoc()) {
        $category_id = $row['category_id'];
        // Only add item if its category is active and was fetched
        if (isset($menu_categories_with_items[$category_id])) {
            $menu_categories_with_items[$category_id]['items'][] = $row;
        }
    }
} else {
    error_log("Error fetching food menu items for public view: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Food Menu - UpdateIQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Food Menu Banner */
        .food-menu-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://placehold.co/1500x400/dc3545/ffffff?text=Delicious+Menu') no-repeat center center; /* Red placeholder */
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            color: white;
            text-align: center;
            position: relative;
            z-index: 1;
            margin-bottom: 50px;
        }
        .food-menu-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }
        .food-menu-banner h1 {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .food-menu-banner .breadcrumb {
            --bs-breadcrumb-divider-color: rgba(255, 255, 255, 0.75);
        }
        .food-menu-banner .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
        }
        .food-menu-banner .breadcrumb-item.active {
            color: white;
            font-weight: bold;
        }

        /* Menu Section & Item Styles */
        .menu-category-section {
            margin-bottom: 60px;
            padding: 20px 0;
            border-bottom: 1px solid #eee; /* Separator between categories */
        }
        .menu-category-section:last-child {
            border-bottom: none; /* No border for the last section */
            margin-bottom: 0;
        }
        .menu-category-section h2 {
            font-size: 2.2rem;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            color: #333;
        }
        .menu-category-section h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: #dc3545; /* Bootstrap danger color for food menu */
            border-radius: 2px;
        }
        .menu-category-section p.lead {
            text-align: center;
            margin-bottom: 40px;
            color: #555;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #eee;
        }
        .menu-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .menu-item-image {
            flex-shrink: 0; /* Prevent image from shrinking */
            width: 120px; /* Fixed width for menu item images */
            height: 120px; /* Fixed height for menu item images */
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .menu-item-image:hover {
            transform: scale(1.05);
        }
        .menu-item-details {
            flex-grow: 1;
        }
        .menu-item-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 5px;
        }
        .menu-item-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0;
        }
        .menu-item-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #dc3545; /* Match banner/section underline color */
        }
        .menu-item-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .menu-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .menu-item-image {
                width: 100%;
                max-width: 200px; /* Limit max width on small screens */
                height: 150px;
                margin-bottom: 15px;
            }
            .menu-item-header {
                flex-direction: column;
                align-items: center;
            }
            .menu-item-name, .menu-item-price {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="food-menu-banner">
        <h1 class="text-center text-white">Our Delicious Menu</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Food Menu</li>
            </ol>
        </nav>
    </div>

    <div class="container my-5">
        <?php
        $has_active_items = false;
        foreach ($menu_categories_with_items as $cat_data) {
            if (!empty($cat_data['items'])) {
                $has_active_items = true;
                break;
            }
        }

        if (!$has_active_items): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i> Our menu is currently being updated. Please check back soon!
            </div>
        <?php else: ?>
            <?php foreach ($menu_categories_with_items as $cat_id => $category_data): ?>
                <?php if (!empty($category_data['items'])): // Only display category if it has active items ?>
                    <section class="menu-category-section">
                        <h2><?php echo htmlspecialchars($category_data['name']); ?></h2>
                        <?php if (!empty($category_data['description'])): ?>
                            <p class="lead"><?php echo htmlspecialchars($category_data['description']); ?></p>
                        <?php endif; ?>

                        <div class="row">
                            <?php foreach ($category_data['items'] as $item): ?>
                                <div class="col-lg-6 col-md-6 col-sm-12"> 
                                    <div class="menu-item mb-4">
                                        <?php if ($item['image_filename']): ?>
                                            <img src="<?php echo htmlspecialchars($food_menu_upload_dir . $item['image_filename']); ?>"
                                                 alt="<?php echo htmlspecialchars($item['image_alt_text'] ?: $item['item_name']); ?>"
                                                 class="menu-item-image"
                                                 loading="lazy">
                                        <?php endif; ?>
                                        <div class="menu-item-details">
                                            <div class="menu-item-header">
                                                <h3 class="menu-item-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                                <span class="menu-item-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <p class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
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