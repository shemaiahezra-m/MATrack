<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the materials page to delete a record.');
}

$rawId = $_POST['material_id'] ?? '';
$id = is_string($rawId) ? trim($rawId) : '';
if (!preg_match('/^MAT-[0-9]{3,}$/D', $id)) {
    http_response_code(400);
    exit('A valid material ID is required.');
}

try {
    if (!DATABASE_ENABLED) {
        header('Location: index.php?message=' . urlencode('Demo mode is on, so no material was deleted.'));
        exit;
    }

    $pdo = getDatabaseConnection();
    $statement = $pdo->prepare('DELETE FROM materials WHERE material_id = :id');
    $statement->execute(['id' => $id]);
    $message = $statement->rowCount() > 0 ? 'Material deleted successfully.' : 'Material was not found.';
    header('Location: index.php?message=' . urlencode($message));
    exit;
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Could not delete the material. Check the database connection and try again.');
}
