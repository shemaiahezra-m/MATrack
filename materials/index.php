<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

function escapeHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$flash = $_SESSION['materials_flash'] ?? [];
unset($_SESSION['materials_flash']);
$errors = $flash['errors'] ?? [];
$message = $flash['message'] ?? (string) ($_GET['message'] ?? '');
$formMaterial = $flash['material'] ?? [
    'material_name' => '',
    'color' => '',
    'category' => '',
    'unit' => '',
    'stock_quantity' => '0',
    'description' => '',
];
$formAction = $flash['action'] ?? '';
$formId = (string) ($flash['id'] ?? '');

$databaseError = null;
$databaseAvailable = false;
if (DATABASE_ENABLED) {
    try {
        $pdo = $pdo ?? getDatabaseConnection();
        $statement = $pdo->query(
            'SELECT material_id, material_name, color, category, unit, stock_quantity, description
             FROM materials
             ORDER BY material_name'
        );
        $materials = $statement->fetchAll();
        $databaseAvailable = true;
    } catch (PDOException $exception) {
        $databaseError = $exception->getMessage();
        $materials = getDemoMaterials();
    }
} else {
    $materials = getDemoMaterials();
}

$initialModal = isset($_GET['add']) ? 'add' : '';
$lowStockThreshold = 10;
$requestedEditId = $_GET['edit'] ?? '';
if (!is_string($requestedEditId) || !preg_match('/^MAT-[0-9]{3,}$/D', $requestedEditId)) {
    $requestedEditId = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials | MATrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-shell">
<aside class="sidebar" aria-label="Main navigation">
    <a class="brand-lockup" href="../index.php"><img class="brand-logo" src="../assets/css/MATrack-Logo.png" alt="MATrack"></a>
    <p class="nav-heading">Overview</p>
    <nav class="side-nav">
        <a class="nav-link" href="../dashboard/index.php"><span class="nav-icon" aria-hidden="true">▦</span>Dashboard</a>
    </nav>
    <p class="nav-heading">Inventory</p>
    <nav class="side-nav">
        <a class="nav-link active" href="index.php" aria-current="page"><span class="nav-icon" aria-hidden="true">▤</span>Materials<span class="active-indicator" aria-hidden="true"></span></a>
        <a class="nav-link" href="../borrowers/index.php"><span class="nav-icon" aria-hidden="true">♙</span>Borrowers</a>
        <a class="nav-link" href="../transactions/index.php"><span class="nav-icon" aria-hidden="true">↔</span>Transactions</a>
    </nav>
    <div class="sidebar-footer"><strong>MATrack</strong><span>Department inventory system</span></div>
</aside>
<main class="main-content">
<div class="container">

    <section class="intro-row">
        <div>
            <p class="eyebrow">Supplies &amp; stock</p>
            <h1>Materials</h1>
            <p class="intro-copy">Manage and monitor your department supplies.</p>
        </div>
        <button class="button" type="button" data-open-modal="add"><span aria-hidden="true">＋</span> Add Material</button>
    </section>

    <?php if (!$databaseAvailable): ?>
        <div class="demo-banner"><span class="demo-dot" aria-hidden="true"></span><div><strong>Preview mode</strong><span> Sample materials are shown. Changes are not saved until PostgreSQL is reachable.</span></div></div>
    <?php endif; ?>

    <?php if ($message !== ''): ?><p class="notice" role="status"><?= escapeHtml($message) ?></p><?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="validation-errors" role="alert"><strong>Please check the form:</strong><ul><?php foreach ($errors as $error): ?><li><?= escapeHtml($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
        <section class="error-panel"><h2>Could not connect to the database</h2><p>Check the connection settings in <code>config/database.php</code> and confirm PostgreSQL is reachable.</p><p class="technical-error"><?= escapeHtml($databaseError) ?></p></section>
    <?php endif; ?>

    <section class="inventory-card" aria-label="Materials inventory">
        <div class="inventory-toolbar">
            <div><h2>All materials</h2><p><?= count($materials) ?> item<?= count($materials) === 1 ? '' : 's' ?> in inventory</p></div>
            <label class="search-box"><span class="search-icon" aria-hidden="true">⌕</span><span class="sr-only">Search materials</span><input id="material-search" type="search" placeholder="Search materials..." autocomplete="off"></label>
        </div>
        <?php if ($materials === []): ?>
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">＋</span><h3>No materials yet</h3><p>Add your first supply to start tracking department stock.</p><button class="button" type="button" data-open-modal="add">Add Material</button></div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>ID</th><th>Material</th><th>Category</th><th>Unit</th><th>Stock</th><th>Description</th><th>Actions</th></tr></thead>
                    <tbody id="materials-body">
                    <?php foreach ($materials as $material): ?>
                        <?php $isLowStock = (int) $material['stock_quantity'] <= $lowStockThreshold; ?>
                        <tr class="material-row" data-search="<?= escapeHtml(strtolower(implode(' ', [$material['material_name'], $material['color'] ?? '', $material['category'] ?? '', $material['unit'], $material['description'] ?? '']))) ?>">
                            <td class="record-id"><?= escapeHtml($material['material_id']) ?></td>
                            <td><span class="material-initial" aria-hidden="true"><?= escapeHtml(strtoupper(substr($material['material_name'], 0, 1))) ?></span><span class="material-name"><?= escapeHtml($material['material_name']) ?></span><?php if (($material['color'] ?? '') !== ''): ?> <small class="material-variant" title="Color / Variant: <?= escapeHtml($material['color']) ?>"><?= escapeHtml($material['color']) ?></small><?php endif; ?></td>
                            <td><?= escapeHtml($material['category']) !== '' ? '<span class="category-pill">' . escapeHtml($material['category']) . '</span>' : '<span class="muted">—</span>' ?></td>
                            <td><?= escapeHtml($material['unit']) ?></td>
                            <td><span class="stock-count<?= $isLowStock ? ' is-low-stock' : '' ?>"<?= $isLowStock ? ' title="Low stock" aria-label="Low stock: ' . (int) $material['stock_quantity'] . '"' : '' ?>><?= (int) $material['stock_quantity'] ?></span></td>
                            <td class="description-cell"><?= escapeHtml($material['description']) !== '' ? escapeHtml($material['description']) : '<span class="muted">—</span>' ?></td>
                            <td class="actions">
                                <details class="action-menu">
                                    <summary aria-label="Actions for <?= escapeHtml($material['material_name']) ?>">•••</summary>
                                    <div class="action-menu-panel">
                                        <button class="menu-action" type="button" data-open-modal="edit" data-id="<?= escapeHtml($material['material_id']) ?>"
                                            data-name="<?= escapeHtml($material['material_name']) ?>" data-category="<?= escapeHtml($material['category']) ?>"
                                            data-color="<?= escapeHtml($material['color'] ?? '') ?>"
                                            data-unit="<?= escapeHtml($material['unit']) ?>" data-stock="<?= (int) $material['stock_quantity'] ?>"
                                            data-description="<?= escapeHtml($material['description']) ?>">Edit material</button>
                                        <form class="delete-form" action="delete.php" method="post" data-material-name="<?= escapeHtml($material['material_name']) ?>">
                                            <input type="hidden" name="material_id" value="<?= escapeHtml($material['material_id']) ?>">
                                            <button class="menu-action delete-menu-action" type="submit">Delete material</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="no-search-results" id="no-search-results" hidden>No materials match your search.</p>
            </div>
        <?php endif; ?>
    </section>
    <footer class="page-footer">MATrack <span>·</span> Department inventory system</footer>
</div>
</main>
</div>

<div class="drawer-shell" id="material-modal" hidden>
    <section class="drawer-panel" role="region" aria-labelledby="modal-title" aria-describedby="modal-copy" tabindex="-1">
        <button class="modal-close" type="button" data-close-modal aria-label="Close dialog">×</button>
        <p class="eyebrow">Materials inventory</p>
        <h2 id="modal-title">Add Material</h2>
        <p class="modal-copy" id="modal-copy">Add a supply to your department inventory.</p>
        <form id="material-form" method="post" action="create.php">
            <input type="hidden" name="material_id" id="material-id" value="">
            <label for="material-name">Material Name <span>*</span></label>
            <input id="material-name" name="material_name" maxlength="100" required>
            <label for="material-color">Color / Variant</label>
            <input id="material-color" name="color" maxlength="30" placeholder="e.g. Red, Blue, Black">
            <div class="form-row">
                <div><label for="material-category">Category</label><input id="material-category" name="category" maxlength="50" placeholder="e.g. Art supplies"></div>
                <div><label for="material-unit">Unit <span>*</span></label><input id="material-unit" name="unit" maxlength="20" required placeholder="e.g. pieces"></div>
            </div>
            <label for="material-stock">Stock Quantity <span>*</span></label>
            <input id="material-stock" name="stock_quantity" type="number" min="0" step="1" required value="0">
            <label for="material-description">Description</label>
            <textarea id="material-description" name="description" rows="3" placeholder="Optional details about this material"></textarea>
            <?php if (!$databaseAvailable): ?><p class="modal-demo-note">Preview mode is on. Submissions will not be saved.</p><?php endif; ?>
            <div class="form-actions"><button class="button button-secondary" type="button" data-close-modal>Cancel</button><button class="button" type="submit" id="submit-material">Add Material</button></div>
        </form>
    </section>
</div>

<script>
(() => {
    const backdrop = document.getElementById('material-modal');
    const form = document.getElementById('material-form');
    const search = document.getElementById('material-search');
    const noResults = document.getElementById('no-search-results');
    let lastTrigger = null;
    const fields = {
        id: document.getElementById('material-id'),
        name: document.getElementById('material-name'),
        color: document.getElementById('material-color'),
        category: document.getElementById('material-category'),
        unit: document.getElementById('material-unit'),
        stock: document.getElementById('material-stock'),
        description: document.getElementById('material-description'),
    };

    function openModal(mode, source = null) {
        const editing = mode === 'edit';
        const material = source ? source.dataset : {};
        lastTrigger = source?.closest('details')?.querySelector('summary')
            || source
            || document.querySelector('[data-open-modal="add"]');
        fields.id.value = editing ? (material.id || fields.id.value) : '';
        form.action = editing ? `edit.php?id=${encodeURIComponent(fields.id.value)}` : 'create.php';
        fields.name.value = editing ? (source ? material.name : fields.name.value) : '';
        fields.color.value = editing ? (source ? material.color : fields.color.value) : '';
        fields.category.value = editing ? (source ? material.category : fields.category.value) : '';
        fields.unit.value = editing ? (source ? material.unit : fields.unit.value) : '';
        fields.stock.value = editing ? (source ? material.stock : fields.stock.value) : '0';
        fields.description.value = editing ? (source ? material.description : fields.description.value) : '';
        document.getElementById('modal-title').textContent = editing ? 'Edit Material' : 'Add Material';
        document.getElementById('modal-copy').textContent = editing ? 'Update the details or current stock for this supply.' : 'Add a supply to your department inventory.';
        document.getElementById('submit-material').textContent = editing ? 'Save Changes' : 'Add Material';
        backdrop.hidden = false;
        requestAnimationFrame(() => backdrop.classList.add('is-open'));
        document.body.classList.add('drawer-open');
        fields.name.focus();
    }

    function closeModal() {
        backdrop.classList.remove('is-open');
        document.body.classList.remove('drawer-open');
        if (lastTrigger) lastTrigger.focus();
        window.setTimeout(() => {
            if (!backdrop.classList.contains('is-open')) backdrop.hidden = true;
        }, 220);
    }

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const menu = button.closest('details');
            if (menu) menu.open = false;
            openModal(button.dataset.openModal, button);
        });
    });
    document.querySelectorAll('.delete-form').forEach((deleteForm) => {
        deleteForm.addEventListener('submit', (event) => {
            if (!window.confirm(`Delete ${deleteForm.dataset.materialName} from the materials inventory?`)) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => {
        if (backdrop.hidden) return;
        if (event.key === 'Escape') closeModal();
    });

    if (search) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visibleCount = 0;
            document.querySelectorAll('.material-row').forEach((row) => {
                const matches = row.dataset.search.includes(query);
                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });
            if (noResults) noResults.hidden = visibleCount > 0;
        });
    }

    <?php if ($initialModal !== ''): ?>openModal('add');<?php endif; ?>
    <?php if ($requestedEditId): ?>
    const requestedEdit = document.querySelector('[data-open-modal="edit"][data-id="<?= escapeHtml($requestedEditId) ?>"]');
    if (requestedEdit) openModal('edit', requestedEdit);
    <?php endif; ?>
    <?php if ($errors !== []): ?>
    const restoreEdit = <?= json_encode($formAction === 'edit') ?>;
    const restoreButton = restoreEdit
        ? document.querySelector('[data-open-modal="edit"][data-id="<?= escapeHtml($formId) ?>"]')
        : null;
    openModal(restoreEdit ? 'edit' : 'add', restoreButton);
    fields.id.value = <?= json_encode($formId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || fields.id.value;
    form.action = restoreEdit ? `edit.php?id=${encodeURIComponent(fields.id.value)}` : 'create.php';
    fields.name.value = <?= json_encode($formMaterial['material_name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.color.value = <?= json_encode($formMaterial['color'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.category.value = <?= json_encode($formMaterial['category'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.unit.value = <?= json_encode($formMaterial['unit'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.stock.value = <?= json_encode($formMaterial['stock_quantity'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.description.value = <?= json_encode($formMaterial['description'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    <?php endif; ?>
})();
</script>
</body>
</html>
