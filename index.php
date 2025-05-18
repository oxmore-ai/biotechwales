<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) : null;
$location = isset($_GET['location']) ? filter_input(INPUT_GET, 'location', FILTER_SANITIZE_SPECIAL_CHARS) : null;
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) : null;

// Fetch categories for the filter dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the query based on filters
$query = "SELECT e.*, c.name as category_name 
          FROM entries e 
          LEFT JOIN categories c ON e.category_id = c.id 
          WHERE 1=1 AND e.status = 'published'";
$params = [];

if ($category_id) {
    $query .= " AND e.category_id = ?";
    $params[] = $category_id;
}

if ($location) {
    $query .= " AND e.location = ?";
    $params[] = $location;
}

if ($search) {
    $query .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY e.name";

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$title = "Biotech Wales - Directory of biotech companies in Wales.";
$meta_description = "Discover and learn about all the great biotech, agtech, medtech, and pharma companies in Wales. Check out their social or current job openings.";

// Set canonical URL
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php';
if ($category_id || $location || $search) {
    $canonical_url .= '?' . http_build_query(array_filter([
        'category' => $category_id,
        'location' => $location,
        'search' => $search
    ]));
}

require_once 'includes/header.php';
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
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-NECVJC64ZR"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-NECVJC64ZR');
    </script>
</head>
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
        <h1>Biotech Wales</h1>
        <p>Discover innovative biotechnology, agritech, and pharmaceutical companies across Wales</p>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <form action="index.php" method="get">
            <div class="search-grid">
                <div class="search-item">
                    <label for="search" class="search-label">Search</label>
                    <input type="text" id="search" name="search" placeholder="Company name or keywords" class="search-input" value="<?php echo is_array($search) ? '' : htmlspecialchars($search ?? ''); ?>">
                        </div>
                <div class="search-item">
                    <label for="category" class="search-label">Category</label>
                    <select id="category" name="category" class="search-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                </div>
            </div>
            <button type="submit" class="search-button">SEARCH DIRECTORY</button>
        </form>
    </section>

    <!-- Content Container -->
    <div class="content">
        <!-- Section Title -->
        <div class="section-title">
            <h2>Directory Listings</h2>
            <p>Explore biotech companies and research organizations across Wales</p>
        </div>

        <!-- Directory Entries -->
        <?php if (empty($entries)): ?>
            <div class="no-results">
                <p>No entries found matching your criteria. Please try different filters.</p>
            </div>
        <?php else: ?>
            <div class="entries-grid">
            <?php foreach ($entries as $entry): ?>
                    <!-- Entry content will go here -->
                    <div class="entry-card">
                        <h3><?php echo htmlspecialchars($entry['name']); ?></h3>
                        <p class="entry-category"><?php echo htmlspecialchars($entry['category_name'] ?? 'Uncategorized'); ?> | <?php echo htmlspecialchars($entry['location']); ?></p>
                        <p class="entry-description"><?php echo htmlspecialchars(truncate_text($entry['description'], 150)); ?></p>
                        <a href="entry.php?id=<?php echo $entry['id']; ?>" class="entry-link">View Details</a>
                    </div>
                <?php endforeach; ?>
                </div>
        <?php endif; ?>
    </div>

    <!-- About Section -->
    <div class="content">
        <div class="section-title">
            <h2>About Biotech Wales</h2>
        </div>
        <div class="entry-main">
            <p>Biotech Wales serves as the premier directory for Wales' thriving life sciences sector, encompassing biotechnology, agricultural technology, medical technology, and pharmaceutical companies. Our mission is to showcase the remarkable innovation and research taking place across Wales, connecting businesses, researchers, and the public with the wealth of expertise in our region.</p>
            <p>Wales has emerged as a significant player in the life sciences sector, with an estimated 12,000 professionals working across more than 300 companies and research organizations. The sector contributes over £2 billion annually to the Welsh economy, demonstrating its vital role in the nation's growth and development. From cutting-edge pharmaceutical research in Cardiff to agricultural technology innovations in rural Wales, our directory captures the full spectrum of this dynamic industry.</p>
            <p>The Welsh life sciences sector is particularly strong in several key areas: advanced therapies and regenerative medicine, medical diagnostics, agricultural biotechnology, and pharmaceutical manufacturing. Companies range from multinational corporations to innovative startups, all contributing to Wales' reputation as a hub for life sciences innovation. The sector benefits from strong academic partnerships with leading universities, including Cardiff University, Swansea University, and Bangor University, creating a fertile environment for research and development.</p>
            <p>Our directory aims to make these companies and their work more accessible to potential collaborators, investors, and the wider public. Whether you're looking for a specific service, seeking partnership opportunities, or simply wanting to learn more about Wales' life sciences sector, Biotech Wales provides a comprehensive resource to connect you with the right organizations and expertise.</p>
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