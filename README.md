# Biotech Wales

A lightweight directory website for biotechnology companies in Wales. Built with PHP and MySQL, designed for easy deployment on standard Apache web servers.

## Features

- Browse and search directory of biotech companies in Wales
- Filter entries by name, category, or location
- Admin panel for managing entries and categories
- Secure login with bcrypt password hashing
- Responsive design using Bootstrap 5
- Image upload capability for company logos
- SEO friendly with meta tags and clean URLs
- MySQLi database connection with PDO compatibility layer

## Requirements

- PHP 8.x
- MySQL 8.x
- Apache 2.4 with mod_rewrite enabled
- MAMP (or similar) for local development

## Local Development Setup

### Using MAMP

1. Install [MAMP](https://www.mamp.info/) on your Mac
2. Clone this repository to your MAMP `htdocs` folder:
   ```
   git clone https://github.com/yourusername/biotechwales.git
   ```
3. Start MAMP and ensure Apache and MySQL are running
4. Create a new database called `directory_db` using phpMyAdmin (accessible at http://localhost/phpMyAdmin)
5. Import the database schema from `sql/schema.sql`
6. Update the database credentials in `config.php` if needed (default MAMP credentials are used)
7. Access the site at `http://localhost/biotechwales`
8. To test the database connection, visit `http://localhost/biotechwales/db_test.php`

### Database Connection

The application uses MySQLi for database connections with a PDO compatibility layer that allows all the existing code to work without modifications. If you need to change database credentials, edit the following variables in `config.php`:

```php
$db_host = 'localhost';     // Your database host
$db_user = 'root';          // Your database username
$db_password = 'root';      // Your database password
$db_db = 'directory_db';    // Your database name
```

### Admin Login

- Default admin credentials:
  - Username: `admin`
  - Password: `password123`
- **IMPORTANT**: Change the default password once deployed to production

## Production Deployment

### Step 1: Server Preparation

1. Ensure your server meets the minimum requirements:
   - PHP 8.0 or higher
   - MySQL 8.0 or higher
   - Apache 2.4 with mod_rewrite enabled
   - PHP extensions: mysqli, PDO, GD (for image processing)

2. Set up a dedicated database user:
   ```sql
   CREATE USER 'biotechdb'@'localhost' IDENTIFIED BY 'secure_password_here';
   CREATE DATABASE directory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON directory_db.* TO 'biotechdb'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Step 2: File Transfer

1. Use SFTP (not regular FTP) to securely transfer files to your production server.
2. Place all files in your web root directory or a subdirectory depending on your setup:
   - If using a dedicated domain: `/var/www/biotechwales.com/`
   - If using a subdirectory: `/var/www/yourdomain.com/biotechwales/`

### Step 3: Database Setup

1. Import the schema SQL file:
   ```bash
   mysql -u biotechdb -p directory_db < /path/to/schema.sql
   ```
   
2. Update `config.php` with your production database credentials:
   ```php
   $db_host = 'localhost'; 
   $db_user = 'biotechdb';  
   $db_password = 'secure_password_here';
   $db_db = 'directory_db';
   $db_port = 3306;  // Default MySQL port
   ```

### Step 4: File Permissions

1. Set correct file permissions to enhance security:

   ```bash
   # Make sure web server user (e.g., www-data) owns the files
   chown -R www-data:www-data /var/www/biotechwales
   
   # Set directories to 755 (rwxr-xr-x)
   find /var/www/biotechwales -type d -exec chmod 755 {} \;
   
   # Set files to 644 (rw-r--r--)
   find /var/www/biotechwales -type f -exec chmod 644 {} \;
   
   # Make uploads directory writable
   chmod -R 775 /var/www/biotechwales/uploads
   
   # Make config.php read-only
   chmod 400 /var/www/biotechwales/config.php
   ```

2. Create `.htaccess` files in sensitive directories to prevent direct access:

   In the `uploads` directory:
   ```
   # Only allow image files to be viewed directly
   <FilesMatch "(?i)\.(php|php\d+|phtml|pl|py|jsp|asp|htm|shtml|sh|cgi)$">
       ForceType text/plain
       Require all denied
   </FilesMatch>
   ```

### Step 5: Admin User Setup

1. Access the admin login page at `https://yourdomain.com/admin/`
2. Log in with the default credentials:
   - Username: `admin`
   - Password: `password123`

3. Immediately change the admin password by going to Profile settings.

4. Alternatively, use the provided CLI tool to reset the admin password directly in the database:
   ```bash
   cd /var/www/biotechwales
   php admin/reset_password.php admin new_secure_password
   ```

5. For added security, create a new admin user with a non-default username:
   ```sql
   INSERT INTO admins (username, password_hash) 
   VALUES ('your_secure_username', '$2y$10$hashed_password_here');
   ```

### Step 6: Email Configuration

1. Update the contact form email in `contact.php` to ensure messages are sent to the correct address:
   ```php
   $to = 'biotech@oxmore.com';  // Confirm this is correct
   ```

2. If using a custom SMTP server instead of PHP's mail() function, update your email configuration.

### Step 7: Security Hardening

1. Enable HTTPS:
   - Purchase or obtain a free SSL certificate (Let's Encrypt)
   - Configure your server to force HTTPS
   - Redirect all HTTP traffic to HTTPS

2. Set up a firewall and configure server security:
   - Enable a web application firewall (WAF)
   - Configure regular security updates
   - Implement rate limiting to prevent brute force attacks

3. Add security headers to your `.htaccess` file:
   ```
   # Security headers
   Header set X-XSS-Protection "1; mode=block"
   Header set X-Content-Type-Options "nosniff"
   Header set X-Frame-Options "SAMEORIGIN"
   Header set Referrer-Policy "strict-origin-when-cross-origin"
   Header set Content-Security-Policy "default-src 'self' https: data:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:;"
   ```

### Step 8: Backup Strategy

1. Set up automated database backups:
   ```bash
   mysqldump -u biotechdb -p directory_db > /backup/directory_db_$(date +\%Y\%m\%d).sql
   ```

2. Configure a cron job for regular backups:
   ```
   0 2 * * * /path/to/backup_script.sh > /dev/null 2>&1
   ```

3. Ensure file backups include:
   - All PHP files
   - The `uploads` directory
   - Configuration files

### Step 9: Monitoring and Maintenance

1. Set up monitoring for server health and uptime
2. Configure error logging:
   ```php
   // In config.php
   ini_set('error_reporting', E_ALL);
   ini_set('display_errors', 0);
   ini_set('log_errors', 1);
   ini_set('error_log', '/var/log/php/biotechwales_error.log');
   ```

3. Plan regular security audits and updates

## Directory Structure

- `/` - Main files
- `/admin/` - Admin panel files
- `/assets/` - CSS, JavaScript, and image files
- `/includes/` - Shared PHP files and functions
- `/sql/` - Database schema
- `/uploads/` - User uploaded images

## Troubleshooting Production Issues

1. **Database Connection Issues**:
   - Verify host, username, password and database name
   - Confirm MySQL server is running
   - Check if the database user has proper permissions

2. **File Permission Problems**:
   - Check web server error logs
   - Verify user/group ownership of files
   - Ensure upload directory is writable

3. **Email Sending Failures**:
   - Confirm PHP mail() function is enabled on the server
   - Check spam configurations
   - Verify email sending capabilities from the server

4. **500 Server Errors**:
   - Check Apache/Nginx error logs
   - Verify PHP syntax in recently modified files
   - Look for file permission issues

## Security Considerations

- All user inputs are sanitized and validated
- Passwords are hashed using bcrypt
- Admin sessions expire after inactivity
- Protection against SQL injection and XSS attacks

## License

This project is licensed under the MIT License - see the LICENSE file for details. 