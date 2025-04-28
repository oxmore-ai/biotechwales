<?php
// Count pending submissions for badge display
try {
    $pending_count = $pdo->query("SELECT COUNT(*) FROM entries WHERE status = 'draft'")->fetchColumn();
} catch (PDOException $e) {
    $pending_count = 0;
}

// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'entries.php') ? 'active' : ''; ?>" href="entries.php">
                    <i class="bi bi-card-list me-1"></i>
                    Entries
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'submissions.php') ? 'active' : ''; ?>" href="submissions.php">
                    <i class="bi bi-inbox me-1"></i>
                    Submissions
                    <?php if ($pending_count > 0): ?>
                        <span class="badge rounded-pill bg-warning text-dark ms-1"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                    <i class="bi bi-tag me-1"></i>
                    Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i>
                    View Site
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav> 