<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_method('GET');
if (empty($_SESSION['passenger_id'])) {
    json_response(['authenticated' => false], 401);
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
json_response([
    'authenticated' => true,
    'passenger_id' => $_SESSION['passenger_id'],
    'csrf_token' => $_SESSION['csrf_token'],
]);
