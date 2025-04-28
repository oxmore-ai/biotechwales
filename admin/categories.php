<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
require_admin_login();

// Get categories
try {
    // Get all categories
    $stmt = $pdo->query("SELECT c.*, COUNT(e.id) as entry_count 
                         FROM categories c 
                         LEFT JOIN entries e ON c.id = e.category_id 
                         GROUP BY c.id 
                         ORDER BY c.name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    $categories = [];
}

// Page meta
$title = 'Manage Categories | Biotech Wales';
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Categories</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="category_form.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Category
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Category successfully <?php echo isset($_GET['added']) ? 'added' : 'updated'; ?>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (empty($categories)): ?>
                <div class="alert alert-info">No categories found. <a href="category_form.php">Add a new category</a>.</div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Entries</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <?php if ($category['entry_count'] > 0): ?>
                                                    <a href="../index.php?category=<?php echo $category['id']; ?>" target="_blank">
                                                        <?php echo $category['entry_count']; ?> entries
                                                    </a>
                                                <?php else: ?>
                                                    0 entries
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="category_form.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?type=category&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 