<?php
// Database Configuration - Using SQLite for demo
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../database/maileresttv.db');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'maileresttv');

// Site Configuration
define('SITE_NAME', 'MailerestTV');
define('SITE_URL', 'http://localhost');
define('LOGO_PATH', '/assets/images/logo.png');

// API Keys (if needed for YouTube/Twitch)
define('YOUTUBE_API_KEY', '');
define('TWITCH_CLIENT_ID', '');

// Player Configuration
define('DEFAULT_PLAYER_WIDTH', '100%');
define('DEFAULT_PLAYER_HEIGHT', '500px');

// Admin Configuration
define('ADMIN_USERNAME', 'Juan');
define('ADMIN_PASSWORD_HASH', password_hash('admin123456', PASSWORD_DEFAULT));

// Error Reporting (disable in production)
define('DEBUG_MODE', true);

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
