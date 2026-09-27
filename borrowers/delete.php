<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the Borrowers page to delete a record.');
}

$id = filter_input(INPUT_POST, 'borrower_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('A valid borrower ID is required.');
}

if (!DATABASE_ENABLED) {
    header('Location: index.php?message=' . urlencode('Demo mode is on, so no borrower was deleted.'));
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $statement = $pdo->prepare('DELETE FROM borrowers WHERE borrower_id = :borrower_id');
    $statement->execute(['borrower_id' => $id]);
    $message = $statement->rowCount() > 0 ? 'Borrower deleted successfully.' : 'Borrower was not found.';
    header('Location: index.php?message=' . urlencode($message));
    exit;
} catch (PDOException $exception) {
    header('Location: index.php?message=' . urlencode('Could not delete this borrower. They may be referenced by a transaction.'));
    exit;
}
