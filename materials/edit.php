<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    header('Location: index.php?edit=' . (int) $requestedId);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('A valid material ID is required.');
}

$errors = [];
if (DATABASE_ENABLED) {
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->prepare('SELECT * FROM materials WHERE material_id = :id');
        $statement->execute(['id' => $id]);
        $material = $statement->fetch();
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Could not connect to the database. Check config/database.php.');
    }
} else {
    $material = null;
    foreach (getDemoMaterials() as $demoMaterial) {
        if ((int) $demoMaterial['material_id'] === $id) {
            $material = $demoMaterial;
            break;
        }
    }
}

if (!$material) {
    http_response_code(404);
    exit('Material not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material = [
        'material_id' => $id,
        'material_name' => trim((string) ($_POST['material_name'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'unit' => trim((string) ($_POST['unit'] ?? '')),
        'stock_quantity' => trim((string) ($_POST['stock_quantity'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

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
        $errors[] = 'Demo mode is on, so these changes were not saved. The form is ready to use when the database is enabled.';
    } elseif ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'UPDATE materials
                 SET material_name = :material_name, category = :category, unit = :unit,
                     stock_quantity = :stock_quantity, description = :description
                 WHERE material_id = :id'
            );
            $statement->execute([
                'material_name' => $material['material_name'],
                'category' => $material['category'] !== '' ? $material['category'] : null,
                'unit' => $material['unit'],
                'stock_quantity' => (int) $material['stock_quantity'],
                'description' => $material['description'] !== '' ? $material['description'] : null,
                'id' => $id,
            ]);
            header('Location: index.php?message=' . urlencode('Material updated successfully.'));
            exit;
        } catch (PDOException $exception) {
            $errors[] = 'Could not update the material. Check the database connection and try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['materials_flash'] = [
        'errors' => $errors,
        'action' => 'edit',
        'id' => $id,
        'material' => $material,
    ];
    header('Location: index.php');
    exit;
}
