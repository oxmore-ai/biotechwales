<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/captcha.php';
require_once 'includes/header.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$success = false;
$error = false;
$captcha_error = false;

// Store form data for repopulation after errors
$form_data = [
    'name' => '',
    'description' => '',
    'category_id' => '',
    'location' => '',
    'email' => '',
    'phone' => '',
    'website_url' => '',
    'linkedin_url' => '',
    'twitter_url' => '',
    'facebook_url' => '',
    'has_careers_page' => 0,
    'careers_url' => '',
    'has_press_page' => 0,
    'press_url' => '',
    'logo_url' => ''
];

// Define honeypot field for spam protection
$honeypot_field = 'website';

// Fetch categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data['name'] = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['description'] = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['category_id'] = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $form_data['location'] = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $form_data['phone'] = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['website_url'] = filter_input(INPUT_POST, 'website_url', FILTER_SANITIZE_URL);
    $form_data['linkedin_url'] = filter_input(INPUT_POST, 'linkedin_url', FILTER_SANITIZE_URL);
    $form_data['twitter_url'] = filter_input(INPUT_POST, 'twitter_url', FILTER_SANITIZE_URL);
    $form_data['facebook_url'] = filter_input(INPUT_POST, 'facebook_url', FILTER_SANITIZE_URL);
    $form_data['has_careers_page'] = isset($_POST['has_careers_page']) ? 1 : 0;
    $form_data['careers_url'] = filter_input(INPUT_POST, 'careers_url', FILTER_SANITIZE_URL);
    $form_data['has_press_page'] = isset($_POST['has_press_page']) ? 1 : 0;
    $form_data['press_url'] = filter_input(INPUT_POST, 'press_url', FILTER_SANITIZE_URL);
    $form_data['logo_url'] = filter_input(INPUT_POST, 'logo_url', FILTER_SANITIZE_URL);
    
    // Check honeypot field (should be empty)
    if (!empty($_POST[$honeypot_field])) {
        // This is likely a bot submission
        // We'll pretend the form was successfully submitted
        $success = true;
    } else {
        // Validate CAPTCHA
        if (!isset($_POST['captcha']) || !validate_captcha($_POST['captcha'])) {
            $captcha_error = "The verification code you entered is incorrect. Please try again.";
            // Generate new CAPTCHA for retry
            $captcha_path = generate_captcha();
        } else {
            // Process logo URL if provided
            $logo_url = null;
            if (!empty($_POST['logo_url'])) {
                $logo_url = filter_input(INPUT_POST, 'logo_url', FILTER_SANITIZE_URL);
                if (!filter_var($logo_url, FILTER_VALIDATE_URL)) {
                    $error = "Please enter a valid URL for your logo.";
                }
            }
            
            if (!$error) {
                // Validate inputs
                if (empty($form_data['name']) || empty($form_data['description']) || empty($form_data['category_id'])) {
                    $error = "Please fill out all required fields (name, description, category).";
                } elseif (!empty($form_data['email']) && !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address.";
                } elseif (
                    (!empty($form_data['website_url']) && !filter_var($form_data['website_url'], FILTER_VALIDATE_URL)) || 
                    (!empty($form_data['linkedin_url']) && !filter_var($form_data['linkedin_url'], FILTER_VALIDATE_URL)) || 
                    (!empty($form_data['twitter_url']) && !filter_var($form_data['twitter_url'], FILTER_VALIDATE_URL)) || 
                    (!empty($form_data['facebook_url']) && !filter_var($form_data['facebook_url'], FILTER_VALIDATE_URL)) || 
                    (!empty($form_data['careers_url']) && !filter_var($form_data['careers_url'], FILTER_VALIDATE_URL)) || 
                    (!empty($form_data['press_url']) && !filter_var($form_data['press_url'], FILTER_VALIDATE_URL))
                ) {
                    $error = "Please enter valid URLs for website and social media fields.";
                } else {
                    // Save to database as a draft
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO entries (
                                name, description, category_id, location, email, phone, 
                                website_url, logo_url, linkedin_url, twitter_url, facebook_url, 
                                has_careers_page, careers_url, has_press_page, press_url, status
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?, 'draft'
                            )
                        ");
                        $stmt->execute([
                            $form_data['name'], 
                            $form_data['description'], 
                            $form_data['category_id'], 
                            $form_data['location'], 
                            $form_data['email'], 
                            $form_data['phone'],
                            $form_data['website_url'],
                            $logo_url,
                            $form_data['linkedin_url'],
                            $form_data['twitter_url'],
                            $form_data['facebook_url'],
                            $form_data['has_careers_page'],
                            $form_data['careers_url'],
                            $form_data['has_press_page'],
                            $form_data['press_url']
                        ]);
                        
                        // Get category name for notification
                        $category_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                        $category_stmt->execute([$form_data['category_id']]);
                        $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Prepare submission data for notification
                        $submission_data = array_merge($form_data, [
                            'category_name' => $category['name']
                        ]);
                        
                        // Send notification email
                        send_submission_notification($submission_data);
                        
                        // Success!
                        $success = true;
                    } catch (PDOException $e) {
                        $error = "We encountered an issue saving your submission. Please try again later.";
                        error_log($e->getMessage());
                    }
                }
            }
        }
    }
    
    // Generate new CAPTCHA if there was an error but not already generated for CAPTCHA error
    if ($error && !$captcha_error) {
        $captcha_path = generate_captcha();
    }
} else {
    // Generate CAPTCHA for fresh page load
    $captcha_path = generate_captcha();
}

// Page meta
$title = "Submit a Listing | Biotech Wales";
$meta_description = "Submit your biotech company or organization to the Biotech Wales directory.";

// Set canonical URL
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/submit.php';

?>

<body>
    <!-- HEADER BAR -->
    <div class="header-bar">
        <div class="header-content">
            <a href="index.php" class="logo">Biotech Wales</a>
            <nav class="main-nav">
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Categories</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                                <li><a href="index.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li><a href="submit.php">Add to Directory</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <button class="hamburger" aria-label="Open menu">&#9776;</button>
            </nav>
        </div>
    </div>
    
    <!-- Hero Section -->
    <section class="hero">
        <h1>Submit a Listing</h1>
        <p>Add your organization to the Biotech Wales directory</p>
    </section>
    
    <!-- Content Container -->
    <div class="content">
        <!-- Section Title -->
        <div class="section-title">
            <h2>Submit Your Organization</h2>
            <p>Please provide details about your biotech company or organization for consideration in our directory</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert-success">
                <p>Thank you for your submission! Your listing has been submitted for review by our team. Once approved, it will appear in the directory.</p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($captcha_error): ?>
                <div class="alert-danger">
                    <p><?php echo htmlspecialchars($captcha_error); ?></p>
                    <p><strong>Note:</strong> Your form data has been preserved. Please enter the new verification code below and submit again.</p>
                </div>
            <?php endif; ?>
            
            <div class="submission-card">
                <p class="form-intro">
                    Submit your organization for inclusion in the Biotech Wales directory. 
                    All submissions will be reviewed by our team before publishing.
                    Fields marked with <span class="form-required">*</span> are required.
                </p>
                
                <form action="submit.php" method="post">
                    <div class="form-group">
                        <label for="name" class="form-label">Organization Name <span class="form-required">*</span></label>
                        <input type="text" class="form-input" id="name" name="name" required value="<?php echo htmlspecialchars($form_data['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category <span class="form-required">*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($form_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description <span class="form-required">*</span></label>
                        <textarea class="form-textarea" id="description" name="description" required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-input" id="location" name="location" value="<?php echo htmlspecialchars($form_data['location']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-input" id="phone" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website_url" class="form-label">Website URL</label>
                        <input type="url" class="form-input" id="website_url" name="website_url" value="<?php echo htmlspecialchars($form_data['website_url']); ?>" placeholder="https://example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="logo_url" class="form-label">Company Logo URL</label>
                        <input type="url" class="form-input" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($form_data['logo_url'] ?? ''); ?>" placeholder="https://example.com/logo.png">
                        <div class="form-hint">Enter the URL of your company logo. Recommended size: 300x300 pixels.</div>
                        <div class="logo-preview-container" style="margin-top: 10px; display: none;">
                            <p class="text-muted">Logo Preview:</p>
                            <img id="logo-preview" src="" alt="Logo preview" style="max-height: 100px; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <h4 class="form-section-title">Social Media</h4>
                    
                    <div class="form-group">
                        <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                        <input type="url" class="form-input" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($form_data['linkedin_url']); ?>" placeholder="https://linkedin.com/company/your-company">
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_url" class="form-label">Twitter/X URL</label>
                        <input type="url" class="form-input" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($form_data['twitter_url']); ?>" placeholder="https://twitter.com/your-company">
                    </div>
                    
                    <div class="form-group">
                        <label for="facebook_url" class="form-label">Facebook URL</label>
                        <input type="url" class="form-input" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($form_data['facebook_url']); ?>" placeholder="https://facebook.com/your-company">
                    </div>
                    
                    <h4 class="form-section-title">Additional Information</h4>
                    
                    <div class="form-group checkbox-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="has_careers_page" name="has_careers_page" value="1" <?php echo ($form_data['has_careers_page'] == 1) ? 'checked' : ''; ?>>
                            <label for="has_careers_page" class="checkbox-label">Company has a careers/jobs page</label>
                        </div>
                    </div>
                    
                    <div class="form-group careers-url-group" id="careers-url-group" <?php echo ($form_data['has_careers_page'] != 1) ? 'style="display:none;"' : ''; ?>>
                        <label for="careers_url" class="form-label">Careers Page URL</label>
                        <input type="url" class="form-input" id="careers_url" name="careers_url" value="<?php echo htmlspecialchars($form_data['careers_url']); ?>" placeholder="https://example.com/careers">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="has_press_page" name="has_press_page" value="1" <?php echo ($form_data['has_press_page'] == 1) ? 'checked' : ''; ?>>
                            <label for="has_press_page" class="checkbox-label">Company has a press releases page</label>
                        </div>
                    </div>
                    
                    <div class="form-group press-url-group" id="press-url-group" <?php echo ($form_data['has_press_page'] != 1) ? 'style="display:none;"' : ''; ?>>
                        <label for="press_url" class="form-label">Press Page URL</label>
                        <input type="url" class="form-input" id="press_url" name="press_url" value="<?php echo htmlspecialchars($form_data['press_url']); ?>" placeholder="https://example.com/press">
                    </div>
                    
                    <!-- CAPTCHA -->
                    <div class="captcha-container">
                        <label for="captcha" class="form-label">Verification Code <span class="form-required">*</span></label>
                        <img src="<?php echo htmlspecialchars($captcha_path); ?>" alt="CAPTCHA" class="captcha-image">
                        <input type="text" class="form-input captcha-input" id="captcha" name="captcha" required placeholder="Enter the code shown above">
                    </div>
                    
                    <!-- Honeypot field (hidden from users but visible to bots) -->
                    <div style="display: none;">
                        <input type="text" name="<?php echo $honeypot_field; ?>" value="">
                    </div>
                    
                    <button type="submit" class="submit-button">SUBMIT FOR REVIEW</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Biotech Wales. Built with 🤖 by <a href="https://oxmore.com">Oxmore</a>.</p>
                </div>
                <div class="footer-links">
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="submit.php">Add to Directory</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Client-side form validation
            const form = document.querySelector('form');
            const captchaInput = document.getElementById('captcha');
            
            // Display field error if CAPTCHA error exists
            const captchaError = <?php echo $captcha_error ? 'true' : 'false'; ?>;
            if (captchaError && captchaInput) {
                captchaInput.classList.add('input-error');
                if (!captchaInput.nextElementSibling || !captchaInput.nextElementSibling.classList.contains('field-error')) {
                    addErrorMessage(captchaInput, '<?php echo $captcha_error ? addslashes($captcha_error) : ""; ?>');
                }
            }
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Clear existing error messages
                    const existingErrors = form.querySelectorAll('.field-error');
                    existingErrors.forEach(error => error.remove());
                    
                    // Remove error styling
                    const errorFields = form.querySelectorAll('.input-error');
                    errorFields.forEach(field => field.classList.remove('input-error'));
                    
                    // Validate required fields
                    const requiredFields = form.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            addErrorMessage(field, 'This field is required');
                            field.classList.add('input-error');
                        }
                    });
                    
                    // Validate email format if provided
                    const emailField = document.getElementById('email');
                    if (emailField && emailField.value.trim() && !isValidEmail(emailField.value)) {
                        isValid = false;
                        addErrorMessage(emailField, 'Please enter a valid email address');
                        emailField.classList.add('input-error');
                    }
                    
                    // Validate logo URL format if provided
                    const logoUrlField = document.getElementById('logo_url');
                    if (logoUrlField && logoUrlField.value.trim()) {
                        try {
                            new URL(logoUrlField.value);
                        } catch (e) {
                            isValid = false;
                            addErrorMessage(logoUrlField, 'Please enter a valid URL for your logo');
                            logoUrlField.classList.add('input-error');
                        }
                    }
                    
                    // If validation failed, prevent form submission
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
            
            // Helper function to add error message below a field
            function addErrorMessage(field, message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = message;
                
                // Insert after the field
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
                
                // Add event listener to clear error on input
                field.addEventListener('input', function() {
                    const errorMsg = field.parentNode.querySelector('.field-error');
                    if (errorMsg) {
                        errorMsg.remove();
                        field.classList.remove('input-error');
                    }
                }, { once: true });
            }
            
            // Validate email format
            function isValidEmail(email) {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            }
            
            // Toggle fields visibility based on checkbox state
            const careersCheckbox = document.getElementById('has_careers_page');
            const careersUrlGroup = document.getElementById('careers-url-group');
            const pressCheckbox = document.getElementById('has_press_page');
            const pressUrlGroup = document.getElementById('press-url-group');
            
            if (careersCheckbox && careersUrlGroup) {
                careersCheckbox.addEventListener('change', function() {
                    careersUrlGroup.style.display = this.checked ? 'block' : 'none';
                });
            }
            
            if (pressCheckbox && pressUrlGroup) {
                pressCheckbox.addEventListener('change', function() {
                    pressUrlGroup.style.display = this.checked ? 'block' : 'none';
                });
            }

            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');
            hamburger.addEventListener('click', function() {
                navLinks.classList.toggle('open');
            });

            // Logo preview functionality
            const logoUrlField = document.getElementById('logo_url');
            const logoPreview = document.getElementById('logo-preview');
            const logoPreviewContainer = document.querySelector('.logo-preview-container');

            if (logoUrlField && logoPreview && logoPreviewContainer) {
                logoUrlField.addEventListener('input', function() {
                    const url = this.value.trim();
                    if (url) {
                        try {
                            new URL(url); // Validate URL format
                            logoPreview.src = url;
                            logoPreviewContainer.style.display = 'block';
                            
                            // Handle image load errors
                            logoPreview.onerror = function() {
                                logoPreviewContainer.style.display = 'none';
                                addErrorMessage(logoUrlField, 'Could not load image from this URL');
                                logoUrlField.classList.add('input-error');
                            };
                            
                            // Clear error if image loads successfully
                            logoPreview.onload = function() {
                                const errorMsg = logoUrlField.parentNode.querySelector('.field-error');
                                if (errorMsg) {
                                    errorMsg.remove();
                                    logoUrlField.classList.remove('input-error');
                                }
                            };
                        } catch (e) {
                            logoPreviewContainer.style.display = 'none';
                        }
                    } else {
                        logoPreviewContainer.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html> 