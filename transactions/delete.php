<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the Transactions page to delete a record.');
}

$rawId = $_POST['transaction_id'] ?? '';
$id = is_string($rawId) ? trim($rawId) : '';
if (!isValidTransactionId($id)) {
    http_response_code(400);
    exit('A valid transaction ID is required.');
}

if (!DATABASE_ENABLED) {
    header('Location: index.php?message=' . urlencode('Demo mode is on, so no transaction was deleted.'));
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $statement = $pdo->prepare('DELETE FROM transactions WHERE transaction_id = :transaction_id');
    $statement->execute(['transaction_id' => $id]);
    $message = $statement->rowCount() > 0 ? 'Transaction deleted successfully.' : 'Transaction was not found.';
    header('Location: index.php?message=' . urlencode($message));
    exit;
} catch (PDOException $exception) {
    header('Location: index.php?message=' . urlencode('Could not delete this transaction. Check the database connection and try again.'));
    exit;
}
