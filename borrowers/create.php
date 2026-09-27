<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: index.php?add=1');
    exit;
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
    $errors[] = 'Demo mode is on, so this borrower was not saved. The form is ready when the database is enabled.';
} elseif ($errors === []) {
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare(
            'INSERT INTO borrowers (borrower_name, contact, department)
             VALUES (:borrower_name, :contact, :department)'
        );
        $statement->execute([
            'borrower_name' => $borrower['borrower_name'],
            'contact' => $borrower['contact'] !== '' ? $borrower['contact'] : null,
            'department' => $borrower['department'] !== '' ? $borrower['department'] : null,
        ]);

        header('Location: index.php?message=' . urlencode('Borrower added successfully.'));
        exit;
    } catch (PDOException $exception) {
        $errors[] = 'Could not save this borrower. Check the database connection and try again.';
    }
}

$_SESSION['borrowers_flash'] = [
    'errors' => $errors,
    'action' => 'create',
    'borrower' => $borrower,
];
header('Location: index.php');
exit;
