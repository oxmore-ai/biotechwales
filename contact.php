<?php
require_once 'config.php';
require_once 'includes/functions.php';

$success = false;
$error = false;

// Define honey pot field for spam protection
$honeypot_field = 'website';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check honeypot field (should be empty)
    if (!empty($_POST[$honeypot_field])) {
        // This is likely a bot submission
        // We'll pretend the form was successfully submitted
        $success = true;
    } else {
        // Get form data
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please fill out all fields correctly.";
        } else {
            // Save to database (optional)
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contact_messages (name, email, message, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $email, $message]);
                
                // Send email notification
                $to = 'biotech@oxmore.com';
                $subject = 'New Contact Form Submission - Biotech Wales';
                $email_message = "Name: $name\r\n";
                $email_message .= "Email: $email\r\n\r\n";
                $email_message .= "Message:\r\n$message";
                
                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($to, $subject, $email_message, $headers);
                
                $success = true;
            } catch (PDOException $e) {
                // Table might not exist yet
                $error = "We encountered an issue. Please try again later.";
            }
        }
    }
}

// Fetch categories for the dropdown menu
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page meta
$title = "Contact Us | Biotech Wales";
$meta_description = "Contact the Biotech Wales team with your questions or feedback.";
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
            background-color: #000;
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
        
        /* Contact form styles */
        .contact-card {
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
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            font-family: "Lexend", sans-serif;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #D1232A;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
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
            
            .contact-card {
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
        <h1>Contact Us</h1>
        <p>Get in touch with the Biotech Wales team</p>
    </section>
    
    <!-- Content Container -->
    <div class="content">
        <!-- Section Title -->
        <div class="section-title">
            <h2>Send Us a Message</h2>
            <p>Have a question about the directory? Want to suggest a company to add?</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert-success">
                <p>Thank you for your message! We'll get back to you as soon as possible.</p>
            </div>
        <?php elseif ($error): ?>
            <div class="alert-danger">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div class="contact-card">
                <p class="form-intro">
                    Fill out the form below and we'll get back to you as soon as possible.
                </p>
                
                <form action="contact.php" method="post">
                    <div class="form-group">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-input" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-textarea" id="message" name="message" required></textarea>
                    </div>
                    
                    <!-- Honeypot field (hidden from users but visible to bots) -->
                    <div style="display: none;">
                        <input type="text" name="<?php echo $honeypot_field; ?>" value="">
                    </div>
                    
                    <button type="submit" class="submit-button">SEND MESSAGE</button>
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
</body>
</html> 