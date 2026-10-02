<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/db.php';
require_method('POST');

$data = request_data();
$passengerId = trim((string)($data['passenger_id'] ?? ''));
$password = (string)($data['password'] ?? '');
if ($passengerId === '' || $password === '' || strlen($passengerId) > 64 || strlen($password) > 128) {
    json_response(['error' => 'Enter a valid passenger ID and password.'], 422);
}

try {
    $query = db()->prepare('SELECT passenger_id, password_hash FROM passengers WHERE passenger_id = :passenger_id AND is_active = 1 LIMIT 1');
    $query->execute(['passenger_id' => $passengerId]);
    $passenger = $query->fetch();
} catch (PDOException $error) {
    error_log('Passenger lookup failed: ' . $error->getMessage());
    json_response(['error' => 'Sign-in is temporarily unavailable. Please try again later.'], 503);
}
if (!$passenger || !password_verify($password, $passenger['password_hash'])) {
    json_response(['error' => 'Passenger ID or password is incorrect.'], 401);
}

session_regenerate_id(true);
$_SESSION['passenger_id'] = $passenger['passenger_id'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
json_response(['ok' => true, 'passenger_id' => $passenger['passenger_id'], 'csrf_token' => $_SESSION['csrf_token']]);
