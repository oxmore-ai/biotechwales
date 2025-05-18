<?php
/**
 * Utility functions for the directory website
 */

/**
 * Truncate text to a specified length and append ellipsis
 *
 * @param string $text The text to truncate
 * @param int $length Maximum length of the returned string
 * @return string Truncated text
 */
function truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . '...';
}

/**
 * Generate a clean URL slug from a string
 *
 * @param string $string Input string
 * @return string Cleaned slug for URLs
 */
function create_slug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Check if a user is logged in as admin
 *
 * @return bool True if logged in, false otherwise
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require admin login to access a page
 * Redirects to login page if not logged in
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Generate a CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Upload and process an image file
 * 
 * @param array $file The $_FILES array element
 * @return string|bool The path to the uploaded file or false on failure
 */
function upload_image($file) {
    // Define allowed file types and max size
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Generate a unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $extension;
    $upload_dir = __DIR__ . '/../uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $destination = $upload_dir . $new_filename;
    
    // Move the file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $new_filename;
    }
    
    return false;
}

/**
 * Get a list of all distinct locations from entries
 * 
 * @param PDO $pdo Database connection
 * @return array List of locations
 */
function get_all_locations($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT location FROM entries WHERE location IS NOT NULL AND location != '' ORDER BY location");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Send email notification for new submissions
 * 
 * @param array $submission_data The submission data
 * @return bool True if email was sent successfully, false otherwise
 */
function send_submission_notification($submission_data) {
    $to = 'biotech@oxmore.com'; // Admin email address
    $subject = 'New Directory Submission - Biotech Wales';
    
    // Build email message
    $message = "A new submission has been added to the Biotech Wales directory:\n\n";
    $message .= "Organization: " . $submission_data['name'] . "\n";
    $message .= "Category: " . $submission_data['category_name'] . "\n";
    
    if (!empty($submission_data['location'])) {
        $message .= "Location: " . $submission_data['location'] . "\n";
    }
    
    if (!empty($submission_data['email'])) {
        $message .= "Email: " . $submission_data['email'] . "\n";
    }
    
    if (!empty($submission_data['phone'])) {
        $message .= "Phone: " . $submission_data['phone'] . "\n";
    }
    
    if (!empty($submission_data['website_url'])) {
        $message .= "Website: " . $submission_data['website_url'] . "\n";
    }
    
    $message .= "\nDescription:\n" . $submission_data['description'] . "\n\n";
    $message .= "View submission in admin panel: https://" . $_SERVER['HTTP_HOST'] . "/admin/submissions.php";
    
    // Set headers
    $headers = "From: Biotech Wales <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    return mail($to, $subject, $message, $headers);
} 