<?php
declare(strict_types=1);

// Serve this project over HTTPS in production. Secure cookies are enabled by default;
// set APP_COOKIE_SECURE=0 only for local HTTP development.
$cookieSetting = getenv('APP_COOKIE_SECURE');
$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
$requestHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
$isLocalHttp = !$isHttps
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && in_array(strtolower((string)$requestHost), ['localhost', '127.0.0.1', '::1'], true);
$secureCookie = $cookieSetting === '0' ? false : ($cookieSetting === '1' || !$isLocalHttp);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        json_response(['error' => 'Method not allowed.'], 405);
    }
}

function request_data(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) {
        json_response(['error' => 'Invalid request data.'], 400);
    }
    return $data;
}

function require_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !$provided || !hash_equals($expected, $provided)) {
        json_response(['error' => 'Your session has expired. Refresh and try again.'], 403);
    }
}
