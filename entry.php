<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get entry ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    // Invalid ID, redirect to homepage
    header('Location: index.php');
    exit;
}

// Fetch the entry
$stmt = $pdo->prepare("
    SELECT e.*, c.name as category_name 
    FROM entries e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.id = ?
");
$stmt->execute([$id]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    // Entry not found, redirect to homepage
    header('Location: index.php');
    exit;
}

// Fetch categories for the dropdown menu
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page metadata
$title = htmlspecialchars($entry['name']) . " | Biotech Wales";
$meta_description = htmlspecialchars(truncate_text($entry['description'], 160));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Google Fonts - Lexend -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        /* Navigation */
        .nav-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .logo {
            color: white;
            font-size: 1.5rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #d1d1d1;
        }
        
        .admin-btn {
            background-color: transparent;
            border: 2px solid white;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            transition: all 0.3s;
        }
        
        .admin-btn:hover {
            background-color: white;
            color: #333;
        }
        
        /* Hero section */
        .hero {
            height: 30vh;
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1579154204601-01588f351e67?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 1rem;
            margin-bottom: 2rem;
        }
        
        .hero h1 {
            color: white;
            font-size: 3rem;
            margin-bottom: 1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.03em;
        }
        
        /* Content container */
        .content {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        
        /* Breadcrumb styles */
        .breadcrumb {
            display: flex;
            list-style: none;
            margin-bottom: 2rem;
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 4px;
        }
        
        .breadcrumb-item {
            margin-right: 0.5rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            padding-right: 0.5rem;
            color: #6c757d;
        }
        
        .breadcrumb-item a {
            color: #D1232A;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        /* Entry detail styles */
        .entry-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .entry-main {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }
        
        .entry-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .entry-location {
            display: flex;
            align-items: center;
            color: #666;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .entry-location i {
            margin-right: 0.5rem;
        }
        
        .entry-category {
            display: inline-block;
            background-color: #D1232A;
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .entry-content {
            padding: 1.5rem 0;
            border-top: 1px solid #eee;
        }
        
        .entry-content h5 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #333;
        }
        
        /* Sidebar styles */
        .entry-sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .sidebar-image {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .sidebar-content {
            padding: 1.5rem;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            font-weight: 600;
            color: #333;
        }
        
        .contact-item {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .contact-item i {
            margin-right: 0.75rem;
            color: #D1232A;
            font-size: 1.2rem;
        }
        
        .contact-item a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .contact-item a:hover {
            color: #D1232A;
        }
        
        .update-date {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        /* Social media icons */
        .social-links {
            margin-top: 1.5rem;
        }
        
        .social-icons {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f5f5;
            color: #333;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        .social-icon i {
            font-size: 0;
        }
        
        .social-icon.linkedin {
            background-color: #0A66C2;
            color: white;
        }
        
        .social-icon.linkedin::before {
            content: "in";
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .social-icon.twitter {
            background-color: #1DA1F2;
            color: white;
        }
        
        .social-icon.twitter::before {
            content: "X";
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .social-icon.facebook {
            background-color: #1877F2;
            color: white;
        }
        
        .social-icon.facebook::before {
            content: "f";
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        /* Special page links */
        .special-pages {
            margin-top: 1.5rem;
        }
        
        .special-page-links {
            margin-top: 0.75rem;
        }
        
        .special-page-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .special-page-item i {
            margin-right: 0.75rem;
            color: #D1232A;
            font-size: 1.2rem;
        }
        
        .special-page-item a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .special-page-item a:hover {
            color: #D1232A;
        }
        
        /* Footer styles */
        .site-footer {
            background-color: #333;
            color: #fff;
            padding: 3rem 0;
            margin-top: 4rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-copyright p {
            margin: 0;
            color: #ccc;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        .footer-copyright a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
        }
        
        .footer-copyright a:visited {
            color: #ccc;
        }
        
        .footer-copyright a:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        .footer-links ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .footer-links li {
            margin-left: 2rem;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
        }
        
        .footer-links a:visited {
            color: #ccc;
        }
        
        .footer-links a:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        /* Dropdown styles */
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            cursor: pointer;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 200px;
            border-radius: 4px;
            padding: 8px 0;
        }
        
        .dropdown-menu li {
            display: block;
            margin: 0;
        }
        
        .dropdown-menu li a {
            padding: 8px 16px;
            display: block;
            color: #333;
            text-decoration: none;
        }
        
        .dropdown-menu li a:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .entry-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                height: 25vh;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .nav-links {
                margin-top: 1rem;
                flex-direction: column;
            }
            
            .nav-links li {
                margin: 0.5rem 0;
                margin-left: 0;
            }
            
            .entry-title {
                font-size: 2rem;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                margin-top: 1.5rem;
            }
            
            .footer-links ul {
                flex-direction: column;
            }
            
            .footer-links li {
                margin: 0.5rem 0;
                margin-left: 0;
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .breadcrumb {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-container">
        <nav>
            <a href="index.php" class="logo">Biotech Wales</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">Categories</a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $category): ?>
                            <li><a href="index.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="submit.php">Add to Directory</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </div>
    
    <!-- Hero Section -->
    <section class="hero">
        <h1><?php echo htmlspecialchars($entry['name']); ?></h1>
    </section>
    
    <div class="content">
        <!-- Breadcrumb -->
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <?php if (!empty($entry['category_name'])): ?>
            <li class="breadcrumb-item">
                <a href="index.php?category=<?php echo $entry['category_id']; ?>">
                    <?php echo htmlspecialchars($entry['category_name']); ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($entry['name']); ?></li>
        </ul>
        
        <div class="entry-container">
            <!-- Main content -->
            <div class="entry-main">
                <h1 class="entry-title"><?php echo htmlspecialchars($entry['name']); ?></h1>
                
                <?php if (!empty($entry['location'])): ?>
                <div class="entry-location">
                    <i>📍</i> <?php echo htmlspecialchars($entry['location']); ?>
                </div>
                <?php endif; ?>
                
                <div class="entry-category">
                    <?php echo htmlspecialchars($entry['category_name'] ?? 'Uncategorized'); ?>
                </div>
                
                <div class="entry-content">
                    <h5>About</h5>
                    <div>
                        <?php echo nl2br(htmlspecialchars($entry['description'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="entry-sidebar">
                <?php if (!empty($entry['logo_url'])): ?>
                <img src="<?php echo htmlspecialchars($entry['logo_url']); ?>" class="sidebar-image" alt="<?php echo htmlspecialchars($entry['name']); ?> logo">
                <?php elseif (!empty($entry['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($entry['image_url']); ?>" class="sidebar-image" alt="<?php echo htmlspecialchars($entry['name']); ?>">
                <?php endif; ?>
                
                <div class="sidebar-content">
                    <h5 class="sidebar-title">Contact Information</h5>
                    
                    <?php if (!empty($entry['website_url'])): ?>
                    <div class="contact-item">
                        <i>🌐</i>
                        <a href="<?php echo htmlspecialchars($entry['website_url']); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $entry['website_url'])); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($entry['email'])): ?>
                    <div class="contact-item">
                        <i>✉️</i>
                        <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>">
                            <?php echo htmlspecialchars($entry['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($entry['phone'])): ?>
                    <div class="contact-item">
                        <i>📞</i>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $entry['phone'])); ?>">
                            <?php echo htmlspecialchars($entry['phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check for social media links
                    $has_social = !empty($entry['linkedin_url']) || !empty($entry['twitter_url']) || !empty($entry['facebook_url']);
                    if ($has_social): 
                    ?>
                    <div class="social-links">
                        <h5 class="sidebar-title">Social Media</h5>
                        <div class="social-icons">
                            <?php if (!empty($entry['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($entry['linkedin_url']); ?>" target="_blank" rel="noopener" class="social-icon linkedin" title="LinkedIn">
                                <i>LinkedIn</i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($entry['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($entry['twitter_url']); ?>" target="_blank" rel="noopener" class="social-icon twitter" title="Twitter/X">
                                <i>Twitter</i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($entry['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($entry['facebook_url']); ?>" target="_blank" rel="noopener" class="social-icon facebook" title="Facebook">
                                <i>Facebook</i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check for careers or press pages
                    $has_special_pages = (!empty($entry['has_careers_page']) && $entry['has_careers_page'] == 1) || 
                                        (!empty($entry['has_press_page']) && $entry['has_press_page'] == 1);
                    if ($has_special_pages): 
                    ?>
                    <div class="special-pages">
                        <h5 class="sidebar-title">Company Pages</h5>
                        <div class="special-page-links">
                            <?php if (!empty($entry['has_careers_page']) && $entry['has_careers_page'] == 1): ?>
                            <div class="special-page-item">
                                <i>👥</i>
                                <a href="<?php echo !empty($entry['careers_url']) ? htmlspecialchars($entry['careers_url']) : htmlspecialchars($entry['website_url']) . '/careers'; ?>" target="_blank" rel="noopener">
                                    Careers
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($entry['has_press_page']) && $entry['has_press_page'] == 1): ?>
                            <div class="special-page-item">
                                <i>📰</i>
                                <a href="<?php echo !empty($entry['press_url']) ? htmlspecialchars($entry['press_url']) : htmlspecialchars($entry['website_url']) . '/press'; ?>" target="_blank" rel="noopener">
                                    Press Releases
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <p class="update-date">
                        Last updated: <?php echo date('F j, Y', strtotime($entry['updated_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Biotech Wales. Built with 🤖 by <a href="https://oxmore.com">Oxmore</a>.</p>
                </div>
                <div class="footer-links">
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="submit.php">Add to Directory</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 