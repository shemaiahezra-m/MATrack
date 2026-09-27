<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: index.php?add=1');
    exit;
}

$errors = [];
$material = [
    'material_name' => '',
    'category' => '',
    'unit' => '',
    'stock_quantity' => '0',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($material as $field => $default) {
        $material[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    if ($material['material_name'] === '' || strlen($material['material_name']) > 100) {
        $errors[] = 'Material name is required and must be 100 characters or fewer.';
    }
    if ($material['unit'] === '' || strlen($material['unit']) > 20) {
        $errors[] = 'Unit is required and must be 20 characters or fewer.';
    }
    if (strlen($material['category']) > 50) {
        $errors[] = 'Category must be 50 characters or fewer.';
    }
    if (filter_var($material['stock_quantity'], FILTER_VALIDATE_INT) === false || (int) $material['stock_quantity'] < 0) {
        $errors[] = 'Stock quantity must be a whole number of zero or more.';
    }

    if ($errors === [] && !DATABASE_ENABLED) {
        $errors[] = 'Demo mode is on, so this material was not saved. The form is ready to use when the database is enabled.';
    } elseif ($errors === []) {
        try {
            $pdo = getDatabaseConnection();
            $statement = $pdo->prepare(
                'INSERT INTO materials (material_name, category, unit, stock_quantity, description)
                 VALUES (:material_name, :category, :unit, :stock_quantity, :description)'
            );
            $statement->execute([
                'material_name' => $material['material_name'],
                'category' => $material['category'] !== '' ? $material['category'] : null,
                'unit' => $material['unit'],
                'stock_quantity' => (int) $material['stock_quantity'],
                'description' => $material['description'] !== '' ? $material['description'] : null,
            ]);

            header('Location: index.php?message=' . urlencode('Material added successfully.'));
            exit;
        } catch (PDOException $exception) {
            $errors[] = 'Could not save the material. Check the database connection and try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['materials_flash'] = [
        'errors' => $errors,
        'action' => 'create',
        'material' => $material,
    ];
    header('Location: index.php');
    exit;
}
