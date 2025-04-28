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

// Fetch distinct locations for the filter dropdown
$stmt = $pdo->query("SELECT DISTINCT location FROM entries WHERE location IS NOT NULL AND location != '' ORDER BY location");
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
$title = "Biotech Wales";
$meta_description = "Directory of biotechnology companies and organizations in Wales.";
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
            height: 80vh;
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1579154204601-01588f351e67?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 1rem;
        }
        
        .hero h1 {
            color: white;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.03em;
        }
        
        .hero p {
            color: white;
            font-size: 1.5rem;
            max-width: 700px;
            margin-bottom: 2rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        /* Search section */
        .search-section {
            padding: 3rem 5%;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: -4rem;
            position: relative;
            z-index: 5;
        }
        
        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
        }
        
        .search-item {
            display: flex;
            flex-direction: column;
        }
        
        .search-label {
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        .search-input, .search-select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        .search-button {
            display: block;
            width: 100%;
            background-color: #D1232A;
            color: white;
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: background-color 0.3s;
            letter-spacing: 0.03em;
        }
        
        .search-button:hover {
            background-color: #b01e24;
        }
        
        /* Content container */
        .content {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        
        /* Section title */
        .section-title {
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .section-title h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.02em;
        }
        
        .section-title p {
            color: #777;
            font-size: 1.1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        /* Entry listings */
        .entries-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .entry-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .entry-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .entry-card h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.01em;
        }
        
        .entry-category {
            color: #D1232A;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .entry-description {
            color: #666;
            flex-grow: 1;
            margin-bottom: 1.5rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        .entry-link {
            display: inline-block;
            color: #D1232A;
            text-decoration: none;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            padding: 0.5rem 1rem;
            border: 2px solid #D1232A;
            border-radius: 4px;
            transition: all 0.3s;
            align-self: flex-start;
            letter-spacing: 0.02em;
        }
        
        .entry-link:hover {
            background-color: #D1232A;
            color: white;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .no-results p {
            font-size: 1.2rem;
            color: #666;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
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
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .hero h1 {
                font-size: 3rem;
            }
            
            .entries-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                height: 70vh;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
            }
            
            .search-grid {
                grid-template-columns: 1fr;
            }
            
            .search-section {
                margin-top: -2rem;
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
            
            .entries-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .entry-card h3 {
                font-size: 1.3rem;
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
            
            .hero p {
                font-size: 1rem;
            }
            
            .entry-card {
                padding: 1.2rem;
            }
            
            .entry-link {
                width: 100%;
                text-align: center;
            }
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
                <div class="search-item">
                    <label for="location" class="search-label">Location</label>
                    <select id="location" name="location" class="search-select">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo is_array($loc) ? '' : htmlspecialchars($loc); ?>" <?php echo ($location == $loc) ? 'selected' : ''; ?>>
                            <?php echo is_array($loc) ? '' : htmlspecialchars($loc); ?>
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
        // Basic JavaScript for interactivity if needed
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any interactive behaviors here
        });
    </script>
</body>
</html> 