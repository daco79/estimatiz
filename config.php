<?php
/**
 * === config.php (version unifiée Estimatiz) ===
 * Connexion MySQL et paramètres partagés par toutes les API.
 *
 * Fichiers privés optionnels :
 * - config.local.php     : configuration XAMPP/local
 * - config.o2switch.php  : configuration production o2switch
 */

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = PHP_SAPI === 'cli'
    || $host === ''
    || strpos($host, 'localhost') !== false
    || strpos($host, '127.0.0.1') !== false;

$localConfig    = __DIR__ . '/config.local.php';
$o2switchConfig = __DIR__ . '/config.o2switch.php';

if ($isLocalHost && is_file($localConfig)) {
    require_once $localConfig;
} elseif (is_file($o2switchConfig)) {
    require_once $o2switchConfig;
} elseif (is_file($localConfig)) {
    require_once $localConfig;
}

defined('DB_HOST')      || define('DB_HOST', getenv('ESTIMATIZ_DB_HOST') ?: 'localhost');
defined('DB_NAME')      || define('DB_NAME', getenv('ESTIMATIZ_DB_NAME') ?: 'csv_db 6');
defined('DB_USER')      || define('DB_USER', getenv('ESTIMATIZ_DB_USER') ?: 'root');
defined('DB_PASSWORD')  || define('DB_PASSWORD', getenv('ESTIMATIZ_DB_PASSWORD') ?: '');
defined('DB_CHARSET')   || define('DB_CHARSET', getenv('ESTIMATIZ_DB_CHARSET') ?: 'utf8mb4');
defined('DB_TABLE_DVF') || define('DB_TABLE_DVF', getenv('ESTIMATIZ_DB_TABLE_DVF') ?: 'data_paris_I');

// Alias conservé pour les anciens scripts éventuels.
defined('DB_PASS') || define('DB_PASS', DB_PASSWORD);

function sql_identifier(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function db_table_dvf(): string {
    return sql_identifier(DB_TABLE_DVF);
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode([
            'ok'    => false,
            'error' => 'Erreur de connexion à la base.',
        ], JSON_UNESCAPED_UNICODE));
    }
}
