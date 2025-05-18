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

// Set canonical URL
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/entry.php?id=' . $id;

require_once 'includes/header.php';
?>

<body>
    <!-- HEADER BAR -->
    <div class="header-bar">
        <div class="header-content">
            <a href="index.php" class="logo">Biotech Wales</a>
            <nav class="main-nav">
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
                <button class="hamburger" aria-label="Open menu">&#9776;</button>
            </nav>
        </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('open');
        });
    });
    </script>
</body>
</html> 