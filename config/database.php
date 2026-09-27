<?php
/**
 * Shared PostgreSQL connection settings.
 * Replace DB_HOST with your partner's Windows laptop IPv4 address. Both
 * computers must be on a network that can reach PostgreSQL on port 5432.
 */
const DB_HOST = '192.168.1.100';
const DB_PORT = '5432';
const DB_NAME = 'matrack';
const DB_USER = 'postgres';
const DB_PASSWORD = 'change_this_password';

function getDatabaseConnection(): PDO
{
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;

    return new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
