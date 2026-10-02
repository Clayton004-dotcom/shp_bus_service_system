<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_method('POST');
if (empty($_SESSION['passenger_id'])) {
    json_response(['ok' => true]);
}
require_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Strict',
    ]);
}
session_destroy();
json_response(['ok' => true]);
