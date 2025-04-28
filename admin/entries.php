<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
require_admin_login();

// Get entries with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get filter parameters
$category_id = isset($_GET['category']) ? filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) : null;
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) : '';

// Build the query based on filters
$query = "SELECT e.*, c.name as category_name 
          FROM entries e 
          LEFT JOIN categories c ON e.category_id = c.id 
          WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM entries e WHERE 1=1";
$params = [];
$count_params = [];

if ($category_id) {
    $query .= " AND e.category_id = ?";
    $count_query .= " AND e.category_id = ?";
    $params[] = $category_id;
    $count_params[] = $category_id;
}

if (!empty($search)) {
    $query .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $count_query .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

$query .= " ORDER BY e.name LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    // Fetch entries
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch total entries count for pagination
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_entries = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_entries / $limit);
    
    // Fetch categories for the filter dropdown
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    $entries = [];
    $total_pages = 0;
    $categories = [];
}

// Page meta
$title = 'Manage Entries | Biotech Wales';
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Entries</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="entry_form.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Entry
                    </a>
                </div>
            </div>
            
            <!-- Search and filter form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="entries.php" method="get" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Entries</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Filter by Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Entries table -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (empty($entries)): ?>
                <div class="alert alert-info">No entries found. <a href="entry_form.php">Add a new entry</a>.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['location']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($entry['created_at'])); ?></td>
                                    <td>
                                        <a href="../entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="entry_form.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?type=entry&id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_id; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_id; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_id; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 