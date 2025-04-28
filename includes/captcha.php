<?php
/**
 * CAPTCHA Generation and Validation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CAPTCHA image and store the code in the session
 * Returns the path to the generated image
 */
function generate_captcha() {
    // Create a new CAPTCHA code
    $captcha_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    
    // Store CAPTCHA code in session
    $_SESSION['captcha_code'] = $captcha_code;
    $_SESSION['captcha_time'] = time();
    
    // Also store in database for additional security
    if (isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("
                INSERT INTO captcha_attempts (session_id, captcha_code)
                VALUES (?, ?)
            ");
            $stmt->execute([session_id(), $captcha_code]);
        } catch (PDOException $e) {
            // Table might not exist yet - silently fail
        }
    }
    
    // Create image
    $image_width = 180;
    $image_height = 60;
    $image = imagecreatetruecolor($image_width, $image_height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 248, 249, 250); // Light background
    $text_color = imagecolorallocate($image, 209, 35, 42); // Red color
    $noise_color = imagecolorallocate($image, 100, 120, 180); // Bluish
    
    // Fill background
    imagefilledrectangle(
        $image, 
        0, 
        0, 
        (int)$image_width, 
        (int)$image_height, 
        $bg_color
    );
    
    // Add random noise
    for ($i = 0; $i < 100; $i++) {
        imagesetpixel(
            $image, 
            (int)rand(0, $image_width), 
            (int)rand(0, $image_height), 
            $noise_color
        );
    }
    
    // Use the system font for CAPTCHA text - this is always available
    $font_size = 5; // Best built-in font size
    
    // We'll write each character separately with slight position variations
    $spacing = (int)($image_width / (strlen($captcha_code) + 2)); // Space between characters
    $y_base = (int)($image_height / 2); // Base Y position
    
    for ($i = 0; $i < strlen($captcha_code); $i++) {
        $char = $captcha_code[$i];
        $x = (int)($spacing * ($i + 1));
        $y = $y_base + rand(-5, 5); // Random Y variation
        
        // Draw the character
        imagechar($image, $font_size, $x, $y, $char, $text_color);
    }
    
    // Add distortion - wavy lines
    for ($i = 0; $i < 5; $i++) {
        imageline(
            $image, 
            0, 
            (int)rand(0, $image_height), 
            (int)$image_width, 
            (int)rand(0, $image_height), 
            $noise_color
        );
    }
    
    // Create temporary file
    $captcha_path = __DIR__ . '/../temp/captcha_' . session_id() . '.png';
    
    // Ensure temp directory exists
    if (!file_exists(__DIR__ . '/../temp')) {
        mkdir(__DIR__ . '/../temp', 0755, true);
    }
    
    // Save image to file
    imagepng($image, $captcha_path);
    imagedestroy($image);
    
    // Return relative path for HTML
    return 'temp/captcha_' . session_id() . '.png?' . time();
}

/**
 * Validate the CAPTCHA input
 * 
 * @param string $input User input to validate
 * @return bool True if valid, false otherwise
 */
function validate_captcha($input) {
    // Check if captcha code exists in session
    if (!isset($_SESSION['captcha_code']) || !isset($_SESSION['captcha_time'])) {
        return false;
    }
    
    // Check if captcha has expired (10 minute limit)
    if (time() - $_SESSION['captcha_time'] > 600) {
        unset($_SESSION['captcha_code']);
        unset($_SESSION['captcha_time']);
        return false;
    }
    
    // Compare code (case insensitive)
    $valid = (strtoupper($input) === $_SESSION['captcha_code']);
    
    // Clear the captcha after validation attempt
    unset($_SESSION['captcha_code']);
    unset($_SESSION['captcha_time']);
    
    // Delete the captcha image file
    $captcha_path = __DIR__ . '/../temp/captcha_' . session_id() . '.png';
    if (file_exists($captcha_path)) {
        unlink($captcha_path);
    }
    
    return $valid;
} 