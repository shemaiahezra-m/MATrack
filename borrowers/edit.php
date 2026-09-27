<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rawId = $_GET['id'] ?? '';
    $requestedId = is_string($rawId) ? trim($rawId) : '';
    header('Location: index.php?edit=' . urlencode($requestedId));
    exit;
}

$rawId = $_GET['id'] ?? '';
$id = is_string($rawId) ? trim($rawId) : '';
if (!preg_match('/^BOR-[0-9]{3,}$/D', $id)) {
    http_response_code(400);
    exit('A valid borrower ID is required.');
}

$borrower = [
    'borrower_name' => trim((string) ($_POST['borrower_name'] ?? '')),
    'contact' => trim((string) ($_POST['contact'] ?? '')),
    'department' => trim((string) ($_POST['department'] ?? '')),
];
$errors = [];

if ($borrower['borrower_name'] === '' || strlen($borrower['borrower_name']) > 100) {
    $errors[] = 'Borrower name is required and must be 100 characters or fewer.';
}
if (strlen($borrower['contact']) > 50) {
    $errors[] = 'Contact must be 50 characters or fewer.';
}
if (strlen($borrower['department']) > 100) {
    $errors[] = 'Department must be 100 characters or fewer.';
}

if ($errors === [] && !DATABASE_ENABLED) {
    $errors[] = 'Demo mode is on, so these changes were not saved. The form is ready when the database is enabled.';
} elseif ($errors === []) {
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare(
            'UPDATE borrowers
             SET borrower_name = :borrower_name, contact = :contact, department = :department
             WHERE borrower_id = :borrower_id'
        );
        $statement->execute([
            'borrower_name' => $borrower['borrower_name'],
            'contact' => $borrower['contact'] !== '' ? $borrower['contact'] : null,
            'department' => $borrower['department'] !== '' ? $borrower['department'] : null,
            'borrower_id' => $id,
        ]);

        header('Location: index.php?message=' . urlencode('Borrower updated successfully.'));
        exit;
    } catch (PDOException $exception) {
        $errors[] = 'Could not update this borrower. Check the database connection and try again.';
    }
}

$_SESSION['borrowers_flash'] = [
    'errors' => $errors,
    'action' => 'edit',
    'id' => $id,
    'borrower' => $borrower,
];
header('Location: index.php');
exit;
