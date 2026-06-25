<?php
/**
 * CodeByTushu — Database Connection (Singleton PDO)
 * Returns a reusable PDO instance.
 * Usage: $pdo = db();
 */

declare(strict_types=1);

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/app.php';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        http_response_code(503);
        
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        if ($isApi) {
            die(json_encode([
                'success' => false,
                'error'   => 'Database connection unavailable. Please try again later.',
            ]));
        } else {
            die("<!DOCTYPE html><html><head><title>503 Service Unavailable</title><style>body{background:#111118;color:#fff;font-family:sans-serif;text-align:center;padding:50px;}h1{color:#ffc400;}p{color:#aaa;}</style></head><body><h1>Database Error</h1><p>The application could not connect to the database.</p><p>Please ensure the production <b>.env</b> file is created on your Hostinger server with the correct database credentials.</p></body></html>");
        }
    }

    return $pdo;
}
