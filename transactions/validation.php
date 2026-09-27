<?php

function isValidTransactionId(string $id): bool
{
    return preg_match('/^TRX-[0-9]{3,}$/D', $id) === 1;
}

function validateTransactionInput(array $transaction): array
{
    $errors = [];

    if (!preg_match('/^MAT-[0-9]{3,}$/D', $transaction['material_id'])) {
        $errors[] = 'Choose a valid material.';
    }
    if (!in_array($transaction['transaction_type'], ['ADDED', 'BORROWED', 'RETURNED', 'USED'], true)) {
        $errors[] = 'Choose a valid transaction type.';
    }
    if (in_array($transaction['transaction_type'], ['BORROWED', 'RETURNED'], true)
        && $transaction['borrower_id'] === '') {
        $errors[] = 'Choose a borrower for borrowed and returned transactions.';
    }
    if ($transaction['transaction_type'] === 'BORROWED' && $transaction['expected_return_date'] === '') {
        $errors[] = 'Enter an expected return date for borrowed transactions.';
    }
    if (in_array($transaction['transaction_type'], ['ADDED', 'USED'], true)
        && $transaction['expected_return_date'] !== '') {
        $errors[] = 'Expected return date must be blank for added and used transactions.';
    }
    if ($transaction['borrower_id'] !== ''
        && !preg_match('/^BOR-[0-9]{3,}$/D', $transaction['borrower_id'])) {
        $errors[] = 'Choose a valid borrower.';
    }
    if (filter_var($transaction['quantity'], FILTER_VALIDATE_INT) === false
        || (int) $transaction['quantity'] < 1) {
        $errors[] = 'Quantity must be a positive whole number.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $transaction['transaction_date']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if ($date === false
        || $date->format('Y-m-d\\TH:i') !== $transaction['transaction_date']
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
        $errors[] = 'Enter a valid transaction date and time.';
    }

    if ($transaction['expected_return_date'] !== '') {
        $expectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $transaction['expected_return_date']);
        $expectedDateErrors = DateTimeImmutable::getLastErrors();
        if ($expectedDate === false
            || $expectedDate->format('Y-m-d') !== $transaction['expected_return_date']
            || ($expectedDateErrors !== false && ($expectedDateErrors['warning_count'] > 0 || $expectedDateErrors['error_count'] > 0))) {
            $errors[] = 'Enter a valid expected return date.';
        }
    }
    if (!in_array($transaction['status'], ['ACTIVE', 'RETURNED', 'OVERDUE', 'CANCELLED'], true)) {
        $errors[] = 'Choose a valid transaction status.';
    }

    return $errors;
}

function transactionDateForDatabase(string $date): string
{
    return DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $date)->format('Y-m-d H:i:s');
}

function expectedReturnDateForDatabase(string $date): ?string
{
    return $date !== '' ? $date : null;
}
