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
        // Never expose credentials or full error in production
        if (APP_DEBUG) {
            throw $e;
        }
        http_response_code(503);
        die(json_encode([
            'success' => false,
            'error'   => 'Database connection unavailable. Please try again later.',
        ]));
    }

    return $pdo;
}
