<?php
/**
 * Database Configuration File
 * 
 * This file contains the database connection parameters and other configuration settings.
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your database username
define('DB_PASS', '');            // Change to your database password
define('DB_NAME', 'community_pulse');

// Site configuration
define('SITE_NAME', 'Community Pulse');
define('SITE_URL', 'http://localhost/community_pulse'); // Change to your domain
define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/community_pulse/uploads/');
define('EVENT_IMAGES_DIR', UPLOADS_DIR . 'events/');
define('PROFILE_IMAGES_DIR', UPLOADS_DIR . 'profiles/');
define('DEFAULT_PROFILE_IMAGE', 'default.jpg');

// Session configuration
define('SESSION_COOKIE_NAME', 'community_pulse_session');
define('SESSION_EXPIRY', 3600); // 1 hour in seconds
define('REMEMBER_ME_EXPIRY', 2592000); // 30 days in seconds

// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@example.com');
define('SMTP_PASSWORD', 'your_password');
define('MAIL_FROM', 'noreply@example.com');
define('MAIL_FROM_NAME', 'Community Pulse');

// Error reporting
ini_set('display_errors', 1); // Set to 0 in production
ini_set('display_startup_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('UTC');

// Start the session
if (!session_id()) {
    session_start();
}

/**
 * Database Connection Function
 * 
 * @return mysqli Database connection object
 */
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Global error handler
 * 
 * @param int $errno Error number
 * @param string $errstr Error message
 * @param string $errfile File where error occurred
 * @param int $errline Line number where error occurred
 * @return bool
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, $_SERVER['DOCUMENT_ROOT'] . '/community_pulse/logs/error.log');
    
    // Don't display errors in production
    if (ini_get('display_errors') == 1) {
        echo "<div style='color: red; background-color: #ffe6e6; padding: 10px; margin: 10px 0; border: 1px solid #ff8080;'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<strong>File:</strong> $errfile<br>";
        echo "<strong>Line:</strong> $errline<br>";
        echo "</div>";
    }
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set the custom error handler
set_error_handler("customErrorHandler");
