<?php
/**
 * Database Update Script
 * 
 * This script applies database schema updates to ensure
 * all tables and columns exist correctly.
 */

// Include the database connection
require_once 'config.php';

// Display errors during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to execute SQL file
function executeSqlFile($mysqli, $sqlFile) {
    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        die("Error reading SQL file: $sqlFile");
    }

    // Split the SQL file into individual statements
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    
    try {
        foreach ($queries as $query) {
            $query = trim($query);
            
            if (empty($query)) {
                continue;
            }
            
            if (!$mysqli->query($query)) {
                throw new Exception("SQL Error: " . $mysqli->error . " in query: $query");
            }
        }
        
        echo "SQL file executed successfully: $sqlFile<br>";
        return true;
    } catch (Exception $e) {
        die("Error executing SQL: " . $e->getMessage());
    }
}

// Display header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Update Script</h1>
        <p>Running database update scripts...</p>
        <pre>';

try {
    // Check database connection
    if (!$mysqli) {
        throw new Exception("Database connection failed. Please check your config.php file.");
    }
    
    echo "Database connection successful.\n\n";
    
    // Execute the main schema file if tables don't exist
    $result = $mysqli->query("SHOW TABLES LIKE 'entries'");
    $tableExists = ($result && $result->num_rows > 0);
    
    if (!$tableExists) {
        echo "Tables not found. Creating database structure...\n";
        executeSqlFile($mysqli, 'sql/schema.sql');
        echo "Database structure created successfully.\n";
    } else {
        echo "Tables already exist. Skipping initial schema creation.\n";
    }
    
    // Execute the update script
    echo "\nApplying schema updates...\n";
    executeSqlFile($mysqli, 'sql/update_entries_add_status.sql');
    
    // Success message
    echo "\n<span class='success'>Database update completed successfully!</span>";
    
} catch (Exception $e) {
    echo "<span class='error'>Error: " . $e->getMessage() . "</span>";
}

// Display footer
echo '</pre>
        <a href="index.php" class="back-link">Return to Homepage</a>
    </div>
</body>
</html>'; 