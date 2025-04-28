<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/captcha.php';

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
    'press_url' => ''
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
            // Process logo upload if provided
            $logo_url = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $temp_name = $_FILES['logo']['tmp_name'];
                $file_name = $_FILES['logo']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Only allow specific image formats
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    // Create a unique filename
                    $new_filename = uniqid('logo_') . '.' . $file_ext;
                    $upload_path = __DIR__ . '/uploads/logos/' . $new_filename;
                    
                    // Move the uploaded file
                    if (move_uploaded_file($temp_name, $upload_path)) {
                        $logo_url = 'uploads/logos/' . $new_filename;
                    } else {
                        $error = "There was an error uploading your logo. Please try again.";
                    }
                } else {
                    $error = "Invalid logo file format. Allowed formats: JPG, JPEG, PNG, GIF, WEBP.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Google Fonts - Lexend -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        /* Navigation */
        .nav-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .logo {
            color: white;
            font-size: 1.5rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #d1d1d1;
        }
        
        .admin-btn {
            background-color: transparent;
            border: 2px solid white;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            transition: all 0.3s;
        }
        
        .admin-btn:hover {
            background-color: white;
            color: #333;
        }
        
        /* Hero section */
        .hero {
            height: 40vh;
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1579154204601-01588f351e67?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 1rem;
        }
        
        .hero h1 {
            color: white;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.03em;
        }
        
        .hero p {
            color: white;
            font-size: 1.5rem;
            max-width: 700px;
            margin-bottom: 2rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        /* Content container */
        .content {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        
        /* Form styles */
        .submission-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .form-intro {
            margin-bottom: 2rem;
            font-size: 1.1rem;
            color: #555;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .form-required {
            color: #D1232A;
            margin-left: 0.2rem;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.75rem;
            font-family: "Lexend", sans-serif;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #D1232A;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .form-section-title {
            font-size: 1.3rem;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }
        
        .checkbox-group {
            margin-bottom: 1rem;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            margin-right: 0.5rem;
            width: auto;
        }
        
        .checkbox-label {
            font-weight: 500;
            cursor: pointer;
        }
        
        input[type="file"].form-input {
            padding: 0.5rem;
            line-height: 1.2;
        }
        
        .captcha-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .captcha-image {
            max-width: 180px;
            height: 60px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .captcha-input {
            max-width: 180px;
        }
        
        .submit-button {
            background-color: #D1232A;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-family: "Lexend", sans-serif;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-button:hover {
            background-color: #b01e24;
        }
        
        .field-error {
            color: #D1232A;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        
        .input-error {
            border-color: #D1232A !important;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        /* Section title */
        .section-title {
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .section-title h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-style: normal;
            letter-spacing: -0.02em;
        }
        
        .section-title p {
            color: #777;
            font-size: 1.1rem;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        /* Footer styles */
        .site-footer {
            background-color: #333;
            color: #fff;
            padding: 3rem 0;
            margin-top: 4rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-copyright p {
            margin: 0;
            color: #ccc;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
        }
        
        .footer-copyright a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
        }
        
        .footer-copyright a:visited {
            color: #ccc;
        }
        
        .footer-copyright a:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        .footer-links ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .footer-links li {
            margin-left: 2rem;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            font-family: "Lexend", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
        }
        
        .footer-links a:visited {
            color: #ccc;
        }
        
        .footer-links a:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        /* Dropdown styles */
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            cursor: pointer;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 200px;
            border-radius: 4px;
            padding: 8px 0;
        }
        
        .dropdown-menu li {
            display: block;
            margin: 0;
        }
        
        .dropdown-menu li a {
            padding: 8px 16px;
            display: block;
            color: #333;
            text-decoration: none;
        }
        
        .dropdown-menu li a:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .hero {
                height: 30vh;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
            }
            
            nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .nav-links {
                margin-top: 1rem;
                flex-direction: column;
            }
            
            .nav-links li {
                margin: 0.5rem 0;
                margin-left: 0;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                margin-top: 1.5rem;
            }
            
            .footer-links ul {
                flex-direction: column;
            }
            
            .footer-links li {
                margin: 0.5rem 0;
                margin-left: 0;
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .submission-card {
                padding: 1.2rem;
            }
            
            .submit-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-container">
        <nav>
            <a href="index.php" class="logo">Biotech Wales</a>
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
        </nav>
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
                
                <form action="submit.php" method="post" enctype="multipart/form-data">
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
                        <label for="logo" class="form-label">Company Logo</label>
                        <input type="file" class="form-input" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-hint">Recommended size: 300x300 pixels. Maximum file size: 2MB.</div>
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
        });
    </script>
</body>
</html> 