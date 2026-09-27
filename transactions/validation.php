<?php

function isValidTransactionId(string $id): bool
{
    return preg_match('/^TRX-[0-9]{3,}$/D', $id) === 1;
}

function isValidOptionalDate(string $value, string $format): bool
{
    if ($value === '') {
        return true;
    }

    $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
    $errors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && $date->format($format) === $value
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function validateTransactionInput(array $transaction): array
{
    $errors = [];
    $transactionTypes = ['BORROWED', 'RETURNED', 'USED', 'DISPOSED'];
    $transactionStatuses = ['ACTIVE', 'COMPLETED', 'OVERDUE', 'CANCELLED'];

    if (!preg_match('/^MAT-[0-9]{3,}$/D', $transaction['material_id'])) {
        $errors[] = 'Choose a valid material.';
    }
    if (!in_array($transaction['transaction_type'], $transactionTypes, true)) {
        $errors[] = 'Choose a valid transaction type.';
    }
    if ($transaction['borrower_id'] !== ''
        && !preg_match('/^BOR-[0-9]{3,}$/D', $transaction['borrower_id'])) {
        $errors[] = 'Choose a valid borrower.';
    }
    if (filter_var($transaction['quantity'], FILTER_VALIDATE_INT) === false
        || (int) $transaction['quantity'] < 1) {
        $errors[] = 'Quantity must be a positive whole number.';
    }

    $transactionDate = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $transaction['transaction_date']);
    $transactionDateErrors = DateTimeImmutable::getLastErrors();
    if ($transactionDate === false
        || $transactionDate->format('Y-m-d\\TH:i') !== $transaction['transaction_date']
        || ($transactionDateErrors !== false
            && ($transactionDateErrors['warning_count'] > 0 || $transactionDateErrors['error_count'] > 0))) {
        $errors[] = 'Enter a valid transaction date and time.';
    }

    if (!isValidOptionalDate($transaction['expected_return_date'], 'Y-m-d')) {
        $errors[] = 'Enter a valid expected return date.';
    }
    if ($transaction['transaction_type'] !== 'BORROWED' && $transaction['expected_return_date'] !== '') {
        $errors[] = 'Expected return date is only used for borrowed transactions.';
    }
    if (!isValidOptionalDate($transaction['return_date'], 'Y-m-d\\TH:i')) {
        $errors[] = 'Enter a valid return date and time.';
    }
    if ($transaction['transaction_type'] !== 'RETURNED' && $transaction['return_date'] !== '') {
        $errors[] = 'Return date is only used for returned transactions.';
    }
    if (!in_array($transaction['status'], $transactionStatuses, true)) {
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

function returnDateForDatabase(string $date): ?string
{
    return $date !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $date)->format('Y-m-d H:i:s')
        : null;
}
