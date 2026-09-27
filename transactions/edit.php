<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rawId = $_GET['id'] ?? '';
    $requestedId = is_string($rawId) ? trim($rawId) : '';
    header('Location: index.php?edit=' . urlencode($requestedId));
    exit;
}

$rawId = $_GET['id'] ?? '';
$id = is_string($rawId) ? trim($rawId) : '';
if (!isValidTransactionId($id)) {
    http_response_code(400);
    exit('A valid transaction ID is required.');
}

if (!DATABASE_ENABLED) {
    $demoTransactionIds = array_column(getDemoTransactions(), 'transaction_id');
    if (!in_array($id, $demoTransactionIds, true)) {
        http_response_code(404);
        exit('Transaction not found.');
    }
}

function postedString(string $field): string
{
    $value = $_POST[$field] ?? '';
    return is_string($value) ? trim($value) : '';
}

$transaction = [
    'material_id' => postedString('material_id'),
    'borrower_id' => postedString('borrower_id'),
    'transaction_type' => postedString('transaction_type'),
    'quantity' => postedString('quantity'),
    'transaction_date' => postedString('transaction_date'),
    'expected_return_date' => postedString('expected_return_date'),
    'status' => postedString('status'),
    'notes' => postedString('notes'),
];
$errors = validateTransactionInput($transaction);

if ($errors === [] && !DATABASE_ENABLED) {
    $materialIds = array_column(getDemoMaterials(), 'material_id');
    $borrowerIds = array_column(getDemoBorrowers(), 'borrower_id');
    if (!in_array($transaction['material_id'], $materialIds, true)) {
        $errors[] = 'Choose a material from the current materials list.';
    }
    if ($transaction['borrower_id'] !== ''
        && !in_array($transaction['borrower_id'], $borrowerIds, true)) {
        $errors[] = 'Choose a borrower from the current borrowers list.';
    }
    if ($errors === []) {
        $errors[] = 'Demo mode is on, so these changes were not saved. The form is ready when the database is enabled.';
    }
} elseif ($errors === []) {
    try {
        $pdo = getDatabaseConnection();
        $transactionCheck = $pdo->prepare('SELECT 1 FROM transactions WHERE transaction_id = :transaction_id');
        $transactionCheck->execute(['transaction_id' => $id]);
        if ($transactionCheck->fetchColumn() === false) {
            http_response_code(404);
            exit('Transaction not found.');
        }

        $materialCheck = $pdo->prepare('SELECT 1 FROM materials WHERE material_id = :material_id');
        $materialCheck->execute(['material_id' => $transaction['material_id']]);
        if ($materialCheck->fetchColumn() === false) {
            $errors[] = 'Choose a material from the current materials list.';
        }

        if ($transaction['borrower_id'] !== '') {
            $borrowerCheck = $pdo->prepare('SELECT 1 FROM borrowers WHERE borrower_id = :borrower_id');
            $borrowerCheck->execute(['borrower_id' => $transaction['borrower_id']]);
            if ($borrowerCheck->fetchColumn() === false) {
                $errors[] = 'Choose a borrower from the current borrowers list.';
            }
        }

        if ($errors === []) {
            $statement = $pdo->prepare(
                'UPDATE transactions
                 SET material_id = :material_id, borrower_id = :borrower_id,
                     transaction_type = :transaction_type, quantity = :quantity,
                     transaction_date = :transaction_date,
                     expected_return_date = :expected_return_date, status = :status, notes = :notes
                 WHERE transaction_id = :transaction_id'
            );
            $statement->execute([
                'material_id' => $transaction['material_id'],
                'borrower_id' => $transaction['borrower_id'] !== '' ? $transaction['borrower_id'] : null,
                'transaction_type' => $transaction['transaction_type'],
                'quantity' => (int) $transaction['quantity'],
                'transaction_date' => transactionDateForDatabase($transaction['transaction_date']),
                'expected_return_date' => expectedReturnDateForDatabase($transaction['expected_return_date']),
                'status' => $transaction['status'],
                'notes' => $transaction['notes'] !== '' ? $transaction['notes'] : null,
                'transaction_id' => $id,
            ]);
            header('Location: index.php?message=' . urlencode('Transaction ' . $id . ' updated successfully.'));
            exit;
        }
    } catch (PDOException $exception) {
        $errors[] = 'Could not update this transaction. Check the database connection and try again.';
    }
}

$_SESSION['transactions_flash'] = [
    'errors' => $errors,
    'action' => 'edit',
    'id' => $id,
    'transaction' => $transaction,
];
header('Location: index.php');
exit;
