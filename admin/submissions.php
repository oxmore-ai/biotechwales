<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Process approval or deletion actions
$message = '';
$error = '';

if (isset($_POST['action']) && isset($_POST['entry_id'])) {
    $entry_id = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);
    
    if (!$entry_id) {
        $error = "Invalid entry ID.";
    } else {
        if ($_POST['action'] === 'approve') {
            // Approve the submission
            $stmt = $pdo->prepare("UPDATE entries SET status = 'published' WHERE id = ?");
            if ($stmt->execute([$entry_id])) {
                $message = "Entry approved and published successfully.";
            } else {
                $error = "Failed to approve the entry.";
            }
        } elseif ($_POST['action'] === 'delete') {
            // Delete the submission
            $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ?");
            if ($stmt->execute([$entry_id])) {
                $message = "Entry deleted successfully.";
            } else {
                $error = "Failed to delete the entry.";
            }
        }
    }
}

// Fetch draft submissions
$stmt = $pdo->prepare("
    SELECT e.*, c.name as category_name 
    FROM entries e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.status = 'draft'
    ORDER BY e.created_at DESC
");
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count of pending submissions
$count = count($submissions);

// Page title
$title = "Manage Submissions | Biotech Wales Admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        .pending-badge {
            background-color: #ffc107;
            color: #212529;
        }
        .preview-card {
            border-left: 4px solid #ffc107;
        }
        .action-buttons .btn {
            min-width: 100px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Biotech Wales Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="submissions.php">
                            Submissions
                            <?php if ($count > 0): ?>
                                <span class="badge rounded-pill bg-warning text-dark"><?php echo $count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">View Site</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <h1 class="mb-4">Pending Submissions</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <p>There are no pending submissions to review at this time.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($submissions as $submission): ?>
                    <div class="col-md-12 mb-4">
                        <div class="card preview-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge pending-badge">Pending Review</span>
                                    <span class="ms-2 text-muted">Submitted: <?php echo date('F j, Y g:i a', strtotime($submission['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h2 class="card-title"><?php echo htmlspecialchars($submission['name']); ?></h2>
                                
                                <div class="mb-3">
                                    <strong>Category:</strong> <?php echo htmlspecialchars($submission['category_name'] ?? 'Uncategorized'); ?>
                                </div>
                                
                                <?php if (!empty($submission['location'])): ?>
                                <div class="mb-3">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($submission['location']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($submission['email'])): ?>
                                <div class="mb-3">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($submission['phone'])): ?>
                                <div class="mb-3">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($submission['phone']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <strong>Description</strong>
                                    </div>
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($submission['description'])); ?>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <form method="post" class="d-inline-block">
                                        <input type="hidden" name="entry_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve and publish this entry?')">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                    </form>
                                    
                                    <form method="post" class="d-inline-block ms-2">
                                        <input type="hidden" name="entry_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this entry? This action cannot be undone.')">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 