<?php
/**
 * School ERP - Main Configuration
 * PHP 8.1+
 */

// Error reporting (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
error_reporting(0);
ini_set('display_errors', 0);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// Database Configuration (edit for cPanel)
// -------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // cPanel: cpaneluser_dbuser
define('DB_PASS', '');              // Your DB password
define('DB_NAME', 'school_erp');    // cPanel: cpaneluser_school_erp
define('DB_CHARSET', 'utf8mb4');

// -------------------------------------------------------
// Application Paths
// -------------------------------------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Auto-detect base URL - adjust SITE_BASE_PATH if in a subfolder
// e.g., if installed at /school-erp/, set to '/school-erp'
define('SITE_BASE_PATH', '');  // Leave empty if at domain root
define('SITE_URL', $protocol . '://' . $host . SITE_BASE_PATH);

// Physical paths
define('ROOT_PATH',    dirname(__DIR__) . '/');
define('CONFIG_PATH',  ROOT_PATH . 'config/');
define('INCLUDES_PATH',ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('ASSETS_URL',   SITE_URL . '/assets');

// Upload URLs
define('UPLOADS_URL',  SITE_URL . '/uploads');

// -------------------------------------------------------
// Security
// -------------------------------------------------------
define('CSRF_TOKEN_LENGTH', 32);
define('OTP_EXPIRY_MINUTES', 10);
define('SESSION_TIMEOUT',    1800); // 30 minutes

// -------------------------------------------------------
// Application
// -------------------------------------------------------
define('APP_NAME',        'School ERP System');
define('APP_VERSION',     '1.0.0');
define('ACADEMIC_YEAR',   '2025-2026');
define('PASS_PERCENTAGE', 33);

// -------------------------------------------------------
// Upload settings
// -------------------------------------------------------
define('MAX_FILE_SIZE',   5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGES',  ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_DOCS',    ['pdf','doc','docx','jpg','jpeg','png']);

// -------------------------------------------------------
// Timezone
// -------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');

// Include database connection
require_once CONFIG_PATH . 'database.php';

// Include core functions
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'notifications.php';

// Load dynamic settings from DB (after DB connection established)
function get_setting(string $key, string $default = ''): string
{
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            $cache[$key] = $row ? (string)$row['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    return $cache[$key];
}
