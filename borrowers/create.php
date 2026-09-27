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
        $pdo->beginTransaction();
        $pdo->exec('LOCK TABLE borrowers IN SHARE ROW EXCLUSIVE MODE');
        $borrowerId = generateNextFormattedId($pdo, 'borrowers');
        $statement = $pdo->prepare(
            'INSERT INTO borrowers (borrower_id, borrower_name, contact, department)
             VALUES (:borrower_id, :borrower_name, :contact, :department)'
        );
        $statement->execute([
            'borrower_id' => $borrowerId,
            'borrower_name' => $borrower['borrower_name'],
            'contact' => $borrower['contact'] !== '' ? $borrower['contact'] : null,
            'department' => $borrower['department'] !== '' ? $borrower['department'] : null,
        ]);
        $pdo->commit();

        header('Location: index.php?message=' . urlencode('Borrower ' . $borrowerId . ' added successfully.'));
        exit;
    } catch (PDOException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = 'Could not save this borrower. Check the database connection and try again.';
    } catch (RuntimeException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = 'Could not generate a borrower ID. Check the existing borrower IDs and try again.';
    }
}

$_SESSION['borrowers_flash'] = [
    'errors' => $errors,
    'action' => 'create',
    'borrower' => $borrower,
];
header('Location: index.php');
exit;
