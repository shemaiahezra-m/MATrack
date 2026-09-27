<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: index.php?add=1');
    exit;
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
        $errors[] = 'Demo mode is on, so this transaction was not saved. The form is ready when the database is enabled.';
    }
} elseif ($errors === []) {
    try {
        $pdo = getDatabaseConnection();

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
            $pdo->beginTransaction();
            $pdo->exec('LOCK TABLE transactions IN SHARE ROW EXCLUSIVE MODE');
            $transactionId = generateNextFormattedId($pdo, 'transactions');
            $statement = $pdo->prepare(
                'INSERT INTO transactions
                    (transaction_id, material_id, borrower_id, transaction_type, quantity,
                     transaction_date, expected_return_date, status, notes)
                 VALUES
                    (:transaction_id, :material_id, :borrower_id, :transaction_type, :quantity,
                     :transaction_date, :expected_return_date, :status, :notes)'
            );
            $statement->execute([
                'transaction_id' => $transactionId,
                'material_id' => $transaction['material_id'],
                'borrower_id' => $transaction['borrower_id'] !== '' ? $transaction['borrower_id'] : null,
                'transaction_type' => $transaction['transaction_type'],
                'quantity' => (int) $transaction['quantity'],
                'transaction_date' => transactionDateForDatabase($transaction['transaction_date']),
                'expected_return_date' => expectedReturnDateForDatabase($transaction['expected_return_date']),
                'status' => $transaction['status'],
                'notes' => $transaction['notes'] !== '' ? $transaction['notes'] : null,
            ]);
            $pdo->commit();

            header('Location: index.php?message=' . urlencode('Transaction ' . $transactionId . ' added successfully.'));
            exit;
        }
    } catch (PDOException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = 'Could not save this transaction. Check the database connection and try again.';
    } catch (RuntimeException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = 'Could not generate a transaction ID. Check existing transaction IDs and try again.';
    }
}

$_SESSION['transactions_flash'] = [
    'errors' => $errors,
    'action' => 'create',
    'transaction' => $transaction,
];
header('Location: index.php');
exit;
