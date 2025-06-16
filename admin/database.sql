-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the newly created database
USE cms_db;

-- --------------------------------------------------------
-- Table structure for `roles`
-- This table defines different user roles (e.g., Admin, Editor)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE, -- e.g., 'Admin', 'Editor'
  `description` TEXT,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT IGNORE INTO `roles` (`role_name`, `description`) VALUES
('Admin', 'Full administrative access to the CMS'),
('Editor', 'Can create, edit, and publish content (blogs, menus, gallery)');

-- --------------------------------------------------------
-- Table structure for `users`
-- Stores information about CMS users (admins, editors)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL, -- Stores hashed passwords
  `email` VARCHAR(255) UNIQUE,
  `role_id` INT(11) NOT NULL, -- Foreign key to roles table
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IMPORTANT: Insert an initial admin user.
-- Replace 'admin_username', 'admin@example.com', and 'your_strong_password'
-- You MUST hash the password using PHP's password_hash() function before inserting into a production environment.
-- For now, you can insert a plain password or use a pre-hashed one.
-- Example (plain password 'password123' - DO NOT USE IN PRODUCTION):
-- INSERT INTO `users` (`username`, `password_hash`, `email`, `role_id`) VALUES
-- ('admin', 'password123', 'admin@example.com', (SELECT role_id FROM `roles` WHERE role_name = 'Admin'));
-- We will use PHP to insert this securely later via a registration/seed script.
-- For initial testing, you can manually insert a row using a simple hash or plain text for now,
-- but we'll build the proper login process with hashing.

-- --------------------------------------------------------
-- Table structure for `menus`
-- Stores website navigation menu items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menus` (
  `menu_id` INT(11) NOT NULL AUTO_INCREMENT,
  `menu_text` VARCHAR(100) NOT NULL,
  `menu_link` VARCHAR(255) NOT NULL,
  `parent_id` INT(11) DEFAULT NULL, -- For sub-menus
  `order_priority` INT(11) DEFAULT 0, -- To control menu order
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`menu_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `menus`(`menu_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `gallery_categories`
-- Organizes gallery images
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_categories` (
  `category_id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `gallery_images`
-- Stores information about uploaded images
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_images` (
  `image_id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) DEFAULT NULL, -- Foreign key to gallery_categories
  `image_filename` VARCHAR(255) NOT NULL, -- Stored filename (e.g., 'image123.jpg')
  `image_alt_text` VARCHAR(255), -- For SEO and accessibility
  `title_tag` VARCHAR(255), -- For SEO and accessibility
  `description` TEXT,
  `uploaded_by_user_id` INT(11), -- To track who uploaded it
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_published` BOOLEAN DEFAULT TRUE, -- Can be used to hide/show images
  PRIMARY KEY (`image_id`),
  FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`category_id`) ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users`(`user_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `blog_categories`
-- Organizes blog posts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `category_id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE, -- SEO friendly URL part
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `blog_posts`
-- Stores blog post content and metadata
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `post_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE, -- SEO friendly URL part for the post
  `content` LONGTEXT, -- Use LONGTEXT for potentially large blog content
  `excerpt` TEXT, -- Short summary for listings
  `category_id` INT(11) DEFAULT NULL, -- Foreign key to blog_categories
  `author_user_id` INT(11) NOT NULL, -- Foreign key to users table (who wrote it)
  `featured_image_url` VARCHAR(255), -- URL or filename of a featured image
  `meta_title` VARCHAR(255), -- SEO: Title tag for the page
  `meta_description` VARCHAR(255), -- SEO: Meta description
  `meta_keywords` VARCHAR(255), -- SEO: Comma-separated keywords
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft', -- 'draft', 'published', 'archived'
  `published_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`category_id`) ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`author_user_id`) REFERENCES `users`(`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a default admin user (for initial testing only)
-- You'll want to remove this line and use a proper registration process later.
-- Make sure to replace 'your_admin_password_hash' with a password hashed using PHP's password_hash()
-- For example, for "password123", run `echo password_hash("password123", PASSWORD_DEFAULT);` in a PHP script.
-- Example: INSERT INTO `users` (`username`, `password_hash`, `email`, `role_id`, `is_active`) VALUES
-- ('admin', '$2y$10$YOUR_GENERATED_HASH_HERE', 'admin@example.com', (SELECT role_id FROM `roles` WHERE role_name = 'Admin'), TRUE);
-- Replace '$2y$10$YOUR_GENERATED_HASH_HERE' with an actual hash.