<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
require_admin_login();

// Get parameters from URL
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate parameters
if (!$id || !in_array($type, ['entry', 'category'])) {
    // Invalid parameters
    header('Location: index.php');
    exit;
}

// Process deletion
try {
    if ($type === 'entry') {
        // Get the entry to find its image if any
        $stmt = $pdo->prepare("SELECT image_url FROM entries WHERE id = ?");
        $stmt->execute([$id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the entry
        $stmt = $pdo->prepare("DELETE FROM entries WHERE id = ?");
        $stmt->execute([$id]);
        
        // If the entry had an image, delete it from the filesystem
        if ($entry && !empty($entry['image_url'])) {
            $image_path = __DIR__ . '/../' . $entry['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Redirect back to entries page
        header('Location: entries.php?deleted=1');
        exit;
    } else if ($type === 'category') {
        // First check if category has entries
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE category_id = ?");
        $stmt->execute([$id]);
        $entry_count = $stmt->fetchColumn();
        
        if ($entry_count > 0) {
            // Category has entries, don't delete
            header('Location: categories.php?error=has_entries&id=' . $id);
            exit;
        }
        
        // Delete the category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirect back to categories page
        header('Location: categories.php?deleted=1');
        exit;
    }
} catch (PDOException $e) {
    // Database error
    $_SESSION['error'] = "Error deleting " . $type . ": " . $e->getMessage();
    header('Location: ' . ($type === 'entry' ? 'entries.php' : 'categories.php'));
    exit;
} 