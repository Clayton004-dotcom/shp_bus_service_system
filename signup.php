<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    json_response(['csrf_token' => $_SESSION['csrf_token']]);
}
require_method('POST');
require_csrf();

$data = request_data();
$passengerId = $data['passenger_id'] ?? null;
$password = $data['password'] ?? null;
$confirmPassword = $data['confirm_password'] ?? null;
if (!is_string($passengerId) || !is_string($password) || !is_string($confirmPassword)) {
    json_response(['error' => 'Enter a valid passenger ID and password.'], 422);
}

$passengerId = trim($passengerId);
if ($passengerId === '' || strlen($passengerId) > 64) {
    json_response(['error' => 'Use a passenger ID of up to 64 characters.'], 422);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    json_response(['error' => 'Your password must be between 8 and 72 bytes.'], 422);
}
if (!hash_equals($password, $confirmPassword)) {
    json_response(['error' => 'The passwords do not match.'], 422);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    error_log('Passenger password hashing failed.');
    json_response(['error' => 'Account creation is temporarily unavailable. Please try again later.'], 503);
}

try {
    $insert = db()->prepare('INSERT INTO passengers (passenger_id, password_hash) VALUES (:passenger_id, :password_hash)');
    $insert->execute(['passenger_id' => $passengerId, 'password_hash' => $passwordHash]);
} catch (PDOException $error) {
    if ($error->getCode() === '23000' && (int)($error->errorInfo[1] ?? 0) === 1062) {
        json_response(['error' => 'That passenger ID is already in use. Choose another one.'], 409);
    }
    error_log('Passenger registration failed: ' . $error->getMessage());
    json_response(['error' => 'Account creation is temporarily unavailable. Please try again later.'], 503);
}

session_regenerate_id(true);
$_SESSION['passenger_id'] = $passengerId;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
json_response(['ok' => true, 'csrf_token' => $_SESSION['csrf_token']]);
