<?php
// Display all PHP errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check GD information
if (function_exists('gd_info')) {
    echo "<h2>GD Information</h2>";
    echo "<pre>";
    print_r(gd_info());
    echo "</pre>";
} else {
    echo "<p>GD is not installed</p>";
}

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . "</p>";

// Check loaded extensions
echo "<h2>Loaded Extensions</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

// Create a simple image without text
echo "<h2>Test Image (no text)</h2>";
$im = imagecreatetruecolor(200, 50);
$white = imagecolorallocate($im, 255, 255, 255);
$red = imagecolorallocate($im, 255, 0, 0);
imagefilledrectangle($im, 0, 0, 199, 49, $white);
imageline($im, 0, 0, 199, 49, $red);
imageline($im, 0, 49, 199, 0, $red);

// Output the image as base64
ob_start();
imagepng($im);
$image_data = ob_get_clean();
imagedestroy($im);

echo '<img src="data:image/png;base64,' . base64_encode($image_data) . '" alt="Test Image">';

// Test system font
echo "<h2>Test System Font</h2>";
$im = imagecreatetruecolor(200, 50);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 199, 49, $white);
imagestring($im, 5, 50, 15, "Test String", $black);

// Output the system font image as base64
ob_start();
imagepng($im);
$image_data = ob_get_clean();
imagedestroy($im);

echo '<img src="data:image/png;base64,' . base64_encode($image_data) . '" alt="System Font Test Image">';
?> 