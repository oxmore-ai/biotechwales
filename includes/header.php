<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default title and meta description if not set
if (!isset($title)) {
    $title = 'Biotech Wales';
}

if (!isset($meta_description)) {
    $meta_description = 'Directory of biotechnology, agritech, and pharmaceutical companies in Wales.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black">
        <div class="container">
            <a class="navbar-brand" href="index.php">Biotech Wales</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <?php
                            try {
                                require_once 'config.php';
                                $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                                while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<li><a class="dropdown-item" href="index.php?category=' . $category['id'] . '">' . 
                                    htmlspecialchars($category['name']) . '</a></li>';
                                }
                            } catch (PDOException $e) {
                                // Silently fail if database is not yet set up
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit.php">Add to Directory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <a href="admin/index.php" class="btn btn-outline-light me-2">Admin Panel</a>
                        <a href="admin/logout.php" class="btn btn-light">Logout</a>
                    <?php else: ?>
                        <a href="admin/login.php" class="btn btn-outline-light">Admin Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <!-- Main Content --> 