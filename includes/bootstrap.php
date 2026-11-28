<?php
declare(strict_types=1);

/**
 * Point d'entrée commun : à inclure en première ligne de chaque page.
 *   require __DIR__ . '/../includes/bootstrap.php';
 */
$configFile = dirname(__DIR__) . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config/config.php manquant : copier config/config.example.php.');
}
require $configFile;
require dirname(__DIR__) . '/config/database.php';

if (APP_ENV === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

foreach (['functions', 'csrf', 'mail', 'auth', 'upload', 'notifications', 'candidatures', 'stages', 'matching'] as $inc) {
    require __DIR__ . "/$inc.php";
}
