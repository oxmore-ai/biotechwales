<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
require_admin_login();

// Get counts for dashboard
try {
    $entry_count = $pdo->query("SELECT COUNT(*) FROM entries")->fetchColumn();
    $category_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    // Get recent entries
    $stmt = $pdo->query("
        SELECT e.*, c.name as category_name 
        FROM entries e 
        LEFT JOIN categories c ON e.category_id = c.id 
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    $recent_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    $entry_count = 0;
    $category_count = 0;
    $recent_entries = [];
}

// Page meta
$title = 'Admin Dashboard | Biotech Wales';
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="entry_form.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-plus-circle"></i> Add New Entry
                        </a>
                        <a href="category_form.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-plus-circle"></i> Add New Category
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard overview -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Directory Entries</h5>
                            <p class="card-text display-4"><?php echo $entry_count; ?></p>
                            <a href="entries.php" class="btn btn-primary">Manage Entries</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Categories</h5>
                            <p class="card-text display-4"><?php echo $category_count; ?></p>
                            <a href="categories.php" class="btn btn-primary">Manage Categories</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent entries -->
            <h2 class="h4 mb-3">Recent Entries</h2>
            
            <?php if (empty($recent_entries)): ?>
                <div class="alert alert-info">No entries found. <a href="entry_form.php">Add the first entry</a>.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_entries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['location']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($entry['created_at'])); ?></td>
                                    <td>
                                        <a href="entry_form.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?type=entry&id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="entries.php" class="btn btn-outline-primary">View All Entries</a>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 