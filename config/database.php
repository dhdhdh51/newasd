<?php
/**
 * School ERP - PDO Database Connection
 */

if (!defined('DB_HOST')) {
    die('Direct access not allowed.');
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the real error; show a friendly message
    error_log('[School ERP DB Error] ' . $e->getMessage());
    die('
    <!DOCTYPE html>
    <html>
    <head><title>Database Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container mt-5">
      <div class="alert alert-danger">
        <h4>Database Connection Failed</h4>
        <p>Could not connect to the database. Please check your configuration in <code>config/config.php</code>.</p>
        <small class="text-muted">Error logged. Contact administrator.</small>
      </div>
    </div>
    </body></html>
    ');
}
