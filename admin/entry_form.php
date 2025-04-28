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
$description = '';
$category_id = '';
$location = '';
$email = '';
$phone = '';
$image_url = '';
$error = '';
$success = false;

// Get categories for select dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $categories = [];
}

// If editing an existing entry, fetch its data
if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM entries WHERE id = ?");
        $stmt->execute([$id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            header('Location: entries.php');
            exit;
        }
        
        $name = $entry['name'];
        $description = $entry['description'];
        $category_id = $entry['category_id'];
        $location = $entry['location'];
        $email = $entry['email'];
        $phone = $entry['phone'];
        $image_url = $entry['image_url'];
    } catch (PDOException $e) {
        $error = "Error fetching entry: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validate required fields
    if (empty($name)) {
        $error = "Name is required.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new_image_url = upload_image($_FILES['image']);
                
                if ($new_image_url) {
                    // If upload successful, update image URL
                    $image_url = $new_image_url;
                }
            }
            
            // Prepare SQL statement based on whether adding or editing
            if ($id) {
                // Update existing entry
                $stmt = $pdo->prepare("
                    UPDATE entries 
                    SET name = ?, description = ?, category_id = ?, location = ?, 
                        email = ?, phone = ?, image_url = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $category_id ?: null, $location, $email, $phone, $image_url, $id]);
                $success = true;
            } else {
                // Add new entry
                $stmt = $pdo->prepare("
                    INSERT INTO entries (name, description, category_id, location, email, phone, image_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $category_id ?: null, $location, $email, $phone, $image_url]);
                $success = true;
                
                // Get the ID of the newly created entry
                $id = $pdo->lastInsertId();
            }
            
            // Redirect to entries page if no errors
            if ($success) {
                header('Location: entries.php?success=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Page meta
$title = ($id ? 'Edit' : 'Add') . ' Entry | Biotech Wales';
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $id ? 'Edit' : 'Add New'; ?> Entry</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="entries.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Entries
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="entry_form.php<?php echo $id ? '?id=' . $id : ''; ?>" method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label required-field">Company Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="image" class="form-label">Company Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                
                                <?php if (!empty($image_url)): ?>
                                <div class="mt-2">
                                    <p class="text-muted">Current image:</p>
                                    <img src="<?php echo htmlspecialchars('../' . $image_url); ?>" alt="Current image" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <img id="image-preview" src="#" alt="Image preview" class="img-thumbnail" style="max-height: 100px; display: none;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="entries.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><?php echo $id ? 'Update' : 'Add'; ?> Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 