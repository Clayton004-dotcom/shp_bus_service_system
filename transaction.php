<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/db.php';
require_method('POST');
if (empty($_SESSION['passenger_id'])) {
    json_response(['error' => 'Please sign in to record a fare.'], 401);
}
require_csrf();
$data = request_data();
$pcardId = trim((string)($data['pcard_id'] ?? ''));
$busId = trim((string)($data['bus_id'] ?? ''));
$fareAmount = filter_var($data['fare_amount'] ?? null, FILTER_VALIDATE_FLOAT);
if ($pcardId === '' || $busId === '' || strlen($pcardId) > 64 || strlen($busId) > 64) {
    json_response(['error' => 'Enter valid payment card and bus IDs.'], 422);
}
if ($fareAmount === false || $fareAmount < 0.01 || $fareAmount > 10000) {
    json_response(['error' => 'Fare must be between K0.01 and K10,000.00 PGK.'], 422);
}
try {
    $statement = db()->prepare('INSERT INTO fare_transactions (passenger_id, pcard_id, bus_id, fare_amount) VALUES (:passenger_id, :pcard_id, :bus_id, :fare_amount)');
    $statement->execute([
        'passenger_id' => $_SESSION['passenger_id'],
        'pcard_id' => $pcardId,
        'bus_id' => $busId,
        'fare_amount' => number_format((float)$fareAmount, 2, '.', ''),
    ]);
    $id = (int)db()->lastInsertId();
    $lookup = db()->prepare('SELECT transaction_id, pcard_id, bus_id, fare_amount, timestamp FROM fare_transactions WHERE transaction_id = :id');
    $lookup->execute(['id' => $id]);
    $transaction = $lookup->fetch();
    if (!$transaction) {
        throw new RuntimeException('Inserted transaction could not be retrieved.');
    }
} catch (PDOException $error) {
    error_log('Fare transaction save failed: ' . $error->getMessage());
    json_response(['error' => 'The fare could not be saved. Please try again later.'], 503);
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    json_response(['error' => 'The fare was submitted, but its receipt could not be loaded. Contact the administrator.'], 500);
}
json_response([
    'ok' => true,
    'transaction_id' => (int)$transaction['transaction_id'],
    'pcard_id' => $transaction['pcard_id'],
    'bus_id' => $transaction['bus_id'],
    'fare_amount' => $transaction['fare_amount'],
    'timestamp' => $transaction['timestamp'],
], 201);
