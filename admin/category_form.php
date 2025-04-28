<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
require_admin_login();

// Initialize variables
$id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
$name = '';
$error = '';
$success = false;

// If editing an existing category, fetch its data
if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            header('Location: categories.php');
            exit;
        }
        
        $name = $category['name'];
    } catch (PDOException $e) {
        $error = "Error fetching category: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validate required fields
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        try {
            // Check if the category name already exists (except for current category when editing)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id ?: 0]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $error = "A category with this name already exists.";
            } else {
                // Prepare SQL statement based on whether adding or editing
                if ($id) {
                    // Update existing category
                    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    $success = true;
                } else {
                    // Add new category
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $success = true;
                }
                
                // Redirect to categories page if no errors
                if ($success) {
                    header('Location: categories.php?success=1' . ($id ? '' : '&added=1'));
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Page meta
$title = ($id ? 'Edit' : 'Add') . ' Category | Biotech Wales';
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $id ? 'Edit' : 'Add New'; ?> Category</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Categories
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="category_form.php<?php echo $id ? '?id=' . $id : ''; ?>" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label required-field">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <div class="form-text">Enter a descriptive name for the category (e.g., Biotechnology, Agritech, Pharmaceuticals).</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="categories.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><?php echo $id ? 'Update' : 'Add'; ?> Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 