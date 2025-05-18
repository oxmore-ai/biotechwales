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
$meta_description = "Get in touch with the Biotech Wales team for any questions or support.";

// Set canonical URL
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/contact.php';

require_once 'includes/header.php';
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('open');
        });
    });
    </script>
</body>
</html> 