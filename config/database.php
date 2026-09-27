<?php

/**
 * Shared PostgreSQL connection settings.
 * Replace DB_HOST with your partner's Windows laptop IPv4 address.
 * Both computers must be on a network that can reach PostgreSQL on port 5432.
 */

const DB_HOST = '127.0.0.1';
const DB_PORT = '5432';
const DB_NAME = 'matrack';
const DB_USER = 'postgres';
const DB_PASSWORD = '';

function getDatabaseConnection(): PDO
{
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;

    return new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3,
    ]);
}

/**
 * Return the next string primary key for one of MATrack's entity tables.
 * The table and column names come only from this allowlist; values are IDs
 * such as MAT-001, BOR-001, or TRX-001 rather than integer keys.
 */
function generateNextFormattedId(PDO $pdo, string $entity): string
{
    $entities = [
        'materials' => ['table' => 'materials', 'column' => 'material_id', 'prefix' => 'MAT'],
        'borrowers' => ['table' => 'borrowers', 'column' => 'borrower_id', 'prefix' => 'BOR'],
        'transactions' => ['table' => 'transactions', 'column' => 'transaction_id', 'prefix' => 'TRX'],
    ];

    if (!isset($entities[$entity])) {
        throw new InvalidArgumentException('Unknown MATrack ID type.');
    }

    $definition = $entities[$entity];
    $table = $definition['table'];
    $column = $definition['column'];
    $prefix = $definition['prefix'];
    $sql = "SELECT {$column} FROM {$table}
            WHERE {$column} LIKE :id_pattern
            ORDER BY LENGTH({$column}) DESC, {$column} DESC
            LIMIT 1";
    $statement = $pdo->prepare($sql);
    $statement->execute(['id_pattern' => $prefix . '-%']);
    $lastId = $statement->fetchColumn();

    $digits = $lastId === false ? '0' : substr((string) $lastId, strlen($prefix) + 1);
    if (!preg_match('/^[0-9]+$/D', $digits)) {
        throw new RuntimeException('The latest database ID does not match the expected format.');
    }

    $number = str_split($digits);
    $carry = true;
    for ($position = count($number) - 1; $position >= 0 && $carry; $position--) {
        if ($number[$position] === '9') {
            $number[$position] = '0';
        } else {
            $number[$position] = chr(ord($number[$position]) + 1);
            $carry = false;
        }
    }
    if ($carry) {
        array_unshift($number, '1');
    }

    $nextDigits = ltrim(implode('', $number), '0');
    if ($nextDigits === '') {
        $nextDigits = '0';
    }

    if (strlen($prefix . '-' . $nextDigits) > 20) {
        throw new RuntimeException('The next formatted ID is longer than the database ID column allows.');
    }

    return $prefix . '-' . str_pad($nextDigits, 3, '0', STR_PAD_LEFT);
}
