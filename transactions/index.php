<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

function escapeHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatTransactionDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('M j, Y g:i A', $timestamp);
}

$flash = $_SESSION['transactions_flash'] ?? [];
unset($_SESSION['transactions_flash']);
$errors = $flash['errors'] ?? [];
$message = $flash['message'] ?? (string) ($_GET['message'] ?? '');
$formTransaction = $flash['transaction'] ?? [
    'material_id' => '',
    'borrower_id' => '',
    'transaction_type' => 'BORROWED',
    'quantity' => '1',
    'transaction_date' => date('Y-m-d\TH:i'),
    'expected_return_date' => '',
    'return_date' => '',
    'status' => 'ACTIVE',
    'notes' => '',
];
$formAction = $flash['action'] ?? '';
$formId = (string) ($flash['id'] ?? '');
$transactionTypes = ['BORROWED', 'RETURNED', 'USED', 'DISPOSED'];
$transactionStatuses = ['ACTIVE', 'COMPLETED', 'OVERDUE', 'CANCELLED'];
$databaseError = null;
$databaseAvailable = false;

if (DATABASE_ENABLED) {
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->query(
            'SELECT t.transaction_id, t.material_id, m.material_name, m.color AS material_color,
                    t.borrower_id, b.borrower_name, t.transaction_type,
                    t.quantity, t.transaction_date, t.expected_return_date, t.return_date,
                    t.status, t.notes
             FROM transactions AS t
             INNER JOIN materials AS m ON m.material_id = t.material_id
             LEFT JOIN borrowers AS b ON b.borrower_id = t.borrower_id
             ORDER BY t.transaction_date DESC, t.transaction_id DESC'
        );
        $transactions = $statement->fetchAll();
        $materials = $pdo->query(
            'SELECT material_id, material_name, color, unit, stock_quantity FROM materials ORDER BY material_name'
        )->fetchAll();
        $borrowers = $pdo->query(
            'SELECT borrower_id, borrower_name, department FROM borrowers ORDER BY borrower_name'
        )->fetchAll();
        $databaseAvailable = true;
    } catch (PDOException $exception) {
        $databaseError = $exception->getMessage();
    }
}

if (!$databaseAvailable) {
    $materials = getDemoMaterials();
    $borrowers = getDemoBorrowers();
    $materialsById = array_column($materials, null, 'material_id');
    $borrowersById = array_column($borrowers, null, 'borrower_id');
    $transactions = [];

    foreach (getDemoTransactions() as $transaction) {
        $material = $materialsById[$transaction['material_id']] ?? null;
        $borrower = $transaction['borrower_id'] !== null
            ? ($borrowersById[$transaction['borrower_id']] ?? null)
            : null;
        if ($material === null) {
            continue;
        }

        $transactions[] = array_merge($transaction, [
            'material_name' => $material['material_name'],
            'material_color' => $material['color'] ?? null,
            'borrower_name' => $borrower['borrower_name'] ?? null,
        ]);
    }
}

$activeBorrowingCount = count(array_filter(
    $transactions,
    static fn (array $transaction): bool => $transaction['transaction_type'] === 'BORROWED'
        && $transaction['status'] === 'ACTIVE'
));
$returnedTransactionCount = count(array_filter(
    $transactions,
    static fn (array $transaction): bool => $transaction['transaction_type'] === 'RETURNED'
        || $transaction['status'] === 'RETURNED'
));
$overdueTransactionCount = count(array_filter(
    $transactions,
    static fn (array $transaction): bool => $transaction['status'] === 'OVERDUE'
));

$initialModal = isset($_GET['add']) ? 'add' : '';
$requestedEditId = $_GET['edit'] ?? '';
if (!is_string($requestedEditId) || !preg_match('/^TRX-[0-9]{3,}$/D', $requestedEditId)) {
    $requestedEditId = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | MATrack</title>
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
        <a class="nav-link" href="../materials/index.php"><span class="nav-icon" aria-hidden="true">▤</span>Materials</a>
        <a class="nav-link" href="../borrowers/index.php"><span class="nav-icon" aria-hidden="true">♙</span>Borrowers</a>
        <a class="nav-link active" href="index.php" aria-current="page"><span class="nav-icon" aria-hidden="true">↔</span>Transactions<span class="active-indicator" aria-hidden="true"></span></a>
    </nav>
    <div class="sidebar-footer"><strong>MATrack</strong><span>Department inventory system</span></div>
</aside>
<main class="main-content">
<div class="container">
    <section class="intro-row">
        <div>
            <p class="eyebrow">Inventory activity</p>
            <h1>Transactions</h1>
            <p class="intro-copy">Record borrowed, returned, used, and disposed department supplies.</p>
        </div>
        <button class="button" type="button" data-open-drawer="add"><span aria-hidden="true">＋</span> Add Transaction</button>
    </section>

    <?php if (!$databaseAvailable): ?>
        <div class="demo-banner"><span class="demo-dot" aria-hidden="true"></span><div><strong>Preview mode</strong><span> Sample transactions are shown. Changes are not saved until PostgreSQL is reachable.</span></div></div>
    <?php endif; ?>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= escapeHtml($message) ?></p><?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="validation-errors" role="alert"><strong>Please check the form:</strong><ul><?php foreach ($errors as $error): ?><li><?= escapeHtml($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
        <section class="error-panel"><h2>Could not connect to the database</h2><p>Check the connection settings in <code>config/database.php</code> and confirm PostgreSQL is reachable.</p><p class="technical-error"><?= escapeHtml($databaseError) ?></p></section>
    <?php endif; ?>

    <section class="dashboard-summary transaction-summary" aria-label="Transaction summary">
        <article class="summary-card"><span class="summary-label">Total Transactions</span><strong class="summary-value"><?= count($transactions) ?></strong><span class="summary-detail">records in transaction history</span></article>
        <article class="summary-card"><span class="summary-label">Active Borrowings</span><strong class="summary-value"><?= $activeBorrowingCount ?></strong><span class="summary-detail">borrowed records marked active</span></article>
        <article class="summary-card"><span class="summary-label">Returned Transactions</span><strong class="summary-value"><?= $returnedTransactionCount ?></strong><span class="summary-detail">records marked as returned</span></article>
        <article class="summary-card"><span class="summary-label">Overdue Transactions</span><strong class="summary-value"><?= $overdueTransactionCount ?></strong><span class="summary-detail">records that need attention</span></article>
    </section>

    <section class="inventory-card" aria-label="Transactions">
        <div class="inventory-toolbar">
            <div><h2>All transactions</h2><p><?= count($transactions) ?> transaction<?= count($transactions) === 1 ? '' : 's' ?> recorded</p></div>
            <label class="search-box"><span class="search-icon" aria-hidden="true">⌕</span><span class="sr-only">Search transactions</span><input id="transaction-search" type="search" placeholder="Search transactions..." autocomplete="off"></label>
        </div>
        <?php if ($transactions === []): ?>
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">＋</span><h3>No transactions yet</h3><p>Record a material movement to get started.</p><button class="button" type="button" data-open-drawer="add">Add Transaction</button></div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                        <thead><tr><th>Transaction ID</th><th>Material</th><th>Borrower</th><th>Type</th><th>Quantity</th><th>Transaction Date</th><th>Expected Return Date</th><th>Return Date</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php $dateInput = date('Y-m-d\TH:i', strtotime((string) $transaction['transaction_date'])); ?>
                        <tr class="transaction-row" data-search="<?= escapeHtml(strtolower(implode(' ', [
                            $transaction['transaction_id'],
                            $transaction['material_id'],
                            $transaction['material_name'],
                            $transaction['material_color'] ?? '',
                            $transaction['borrower_id'] ?? '',
                            $transaction['borrower_name'] ?? '',
                                            $transaction['transaction_type'],
                                            $transaction['status'],
                                            $transaction['notes'] ?? '',
                        ]))) ?>">
                            <td class="record-id"><?= escapeHtml($transaction['transaction_id']) ?></td>
                            <td class="related-record"><strong><?= escapeHtml($transaction['material_name']) ?></strong><span><?= escapeHtml($transaction['material_id']) ?></span><?php if (!empty($transaction['material_color'])): ?><small class="transaction-material-color">Color / Variant: <?= escapeHtml($transaction['material_color']) ?></small><?php endif; ?></td>
                            <td class="related-record">
                                <?php if ($transaction['borrower_id'] !== null): ?>
                                    <strong><?= escapeHtml($transaction['borrower_name']) ?></strong><span><?= escapeHtml($transaction['borrower_id']) ?></span>
                                <?php else: ?><span class="muted">—</span><?php endif; ?>
                            </td>
                            <td><span class="type-badge type-<?= strtolower(escapeHtml($transaction['transaction_type'])) ?>"><?= escapeHtml($transaction['transaction_type']) ?></span></td>
                            <td><span class="stock-count"><?= (int) $transaction['quantity'] ?></span></td>
                            <td class="transaction-date"><?= escapeHtml(formatTransactionDate((string) $transaction['transaction_date'])) ?></td>
                            <td class="transaction-date"><?= $transaction['expected_return_date'] !== null ? escapeHtml(date('M j, Y', strtotime((string) $transaction['expected_return_date']))) : '<span class="muted">—</span>' ?></td>
                            <td class="transaction-date"><?= $transaction['return_date'] !== null ? escapeHtml(formatTransactionDate((string) $transaction['return_date'])) : '<span class="muted">—</span>' ?></td>
                            <td><span class="status-badge status-<?= strtolower(escapeHtml($transaction['status'])) ?>"><?= escapeHtml($transaction['status']) ?></span></td>
                            <td class="description-cell"><?= $transaction['notes'] !== null && $transaction['notes'] !== '' ? escapeHtml($transaction['notes']) : '<span class="muted">—</span>' ?></td>
                            <td class="actions">
                                <details class="action-menu">
                                    <summary aria-label="Actions for <?= escapeHtml($transaction['transaction_id']) ?>">•••</summary>
                                    <div class="action-menu-panel">
                                        <button class="menu-action" type="button" data-open-drawer="edit"
                                            data-id="<?= escapeHtml($transaction['transaction_id']) ?>"
                                            data-material-id="<?= escapeHtml($transaction['material_id']) ?>"
                                            data-borrower-id="<?= escapeHtml($transaction['borrower_id'] ?? '') ?>"
                                            data-type="<?= escapeHtml($transaction['transaction_type']) ?>"
                                            data-quantity="<?= (int) $transaction['quantity'] ?>"
                                            data-date="<?= escapeHtml($dateInput) ?>"
                                            data-expected-return-date="<?= escapeHtml($transaction['expected_return_date'] ?? '') ?>"
                                            data-return-date="<?= escapeHtml($transaction['return_date'] !== null ? date('Y-m-d\\TH:i', strtotime((string) $transaction['return_date'])) : '') ?>"
                                            data-status="<?= escapeHtml($transaction['status']) ?>"
                                            data-notes="<?= escapeHtml($transaction['notes']) ?>">Edit transaction</button>
                                        <form class="delete-form" action="delete.php" method="post" data-transaction-id="<?= escapeHtml($transaction['transaction_id']) ?>">
                                            <input type="hidden" name="transaction_id" value="<?= escapeHtml($transaction['transaction_id']) ?>">
                                            <button class="menu-action delete-menu-action" type="submit">Delete transaction</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="no-search-results" id="no-search-results" hidden>No transactions match your search.</p>
            </div>
        <?php endif; ?>
    </section>
    <footer class="page-footer">MATrack <span>·</span> Department inventory system</footer>
</div>
</main>
</div>

<div class="drawer-shell" id="transaction-drawer" hidden>
    <section class="drawer-panel" role="region" aria-labelledby="drawer-title" aria-describedby="drawer-description" tabindex="-1">
        <button class="modal-close" type="button" data-close-drawer aria-label="Close drawer">×</button>
        <p class="eyebrow">Inventory activity</p>
        <h2 id="drawer-title">Add Transaction</h2>
        <p class="modal-copy" id="drawer-description">Record a material movement in the inventory.</p>
        <form id="transaction-form" method="post" action="create.php">
            <input id="transaction-id" type="hidden" value="">
            <div class="drawer-id-row" id="transaction-id-row" hidden><span>Transaction ID</span><strong id="transaction-id-display"></strong></div>
            <section class="transaction-picker" aria-labelledby="borrower-picker-title">
                <h3 id="borrower-picker-title">Select Borrower</h3>
                <input class="transaction-picker-search" type="search" data-filter-cards="borrower" placeholder="Search borrower name or ID" aria-label="Search borrowers">
                <select id="transaction-borrower" name="borrower_id" class="transaction-native-select" aria-hidden="true" tabindex="-1">
                    <option value="">No borrower</option>
                    <?php foreach ($borrowers as $borrower): ?>
                        <option value="<?= escapeHtml($borrower['borrower_id']) ?>"><?= escapeHtml($borrower['borrower_name']) ?> (<?= escapeHtml($borrower['borrower_id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="transaction-choice-grid" data-choice-grid="borrower">
                    <button class="transaction-choice" type="button" data-choice="borrower" data-value="" data-search="no borrower optional"><strong>No borrower</strong><span>Optional</span></button>
                    <?php foreach ($borrowers as $borrower): ?>
                        <button class="transaction-choice" type="button" data-choice="borrower" data-value="<?= escapeHtml($borrower['borrower_id']) ?>" data-search="<?= escapeHtml(strtolower($borrower['borrower_name'] . ' ' . $borrower['borrower_id'] . ' ' . ($borrower['department'] ?? ''))) ?>"><strong><?= escapeHtml($borrower['borrower_name']) ?></strong><span><?= escapeHtml($borrower['borrower_id']) ?><?= !empty($borrower['department']) ? ' · ' . escapeHtml($borrower['department']) : '' ?></span></button>
                    <?php endforeach; ?>
                </div>
                <p class="transaction-filter-empty" data-filter-empty="borrower" hidden>No borrowers match your search.</p>
                <p class="field-hint">Required for borrowing and return transactions.</p>
            </section>

            <section class="transaction-picker" aria-labelledby="material-picker-title">
                <h3 id="material-picker-title">Select Material <span>*</span></h3>
                <input class="transaction-picker-search" type="search" data-filter-cards="material" placeholder="Search materials by name or ID" aria-label="Search materials">
                <select id="transaction-material" name="material_id" class="transaction-native-select" aria-hidden="true" tabindex="-1">
                    <option value="">Choose a material</option>
                    <?php foreach ($materials as $material): ?>
                        <option value="<?= escapeHtml($material['material_id']) ?>"><?= escapeHtml($material['material_name']) ?> (<?= escapeHtml($material['material_id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="transaction-choice-grid" data-choice-grid="material">
                    <?php foreach ($materials as $material): ?>
                        <button class="transaction-choice" type="button" data-choice="material" data-value="<?= escapeHtml($material['material_id']) ?>" data-search="<?= escapeHtml(strtolower($material['material_name'] . ' ' . $material['material_id'] . ' ' . ($material['color'] ?? ''))) ?>"><strong><?= escapeHtml($material['material_name']) ?></strong><span><?= escapeHtml($material['material_id']) ?><?= isset($material['stock_quantity']) ? ' · ' . (int) $material['stock_quantity'] . ' ' . escapeHtml($material['unit'] ?? 'units') . ' available' : '' ?></span><?php if (!empty($material['color'])): ?><span class="transaction-variant">Color / Variant: <?= escapeHtml($material['color']) ?></span><?php endif; ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="transaction-filter-empty" data-filter-empty="material" hidden>No materials match your search.</p>
            </section>

            <label for="transaction-type">Transaction Type <span>*</span></label>
            <select id="transaction-type" name="transaction_type" required>
                <?php foreach ($transactionTypes as $type): ?>
                    <option value="<?= escapeHtml($type) ?>"><?= escapeHtml($type) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="transaction-quantity">Quantity <span>*</span></label>
            <input id="transaction-quantity" name="quantity" type="number" min="1" step="1" value="1" required>

            <label for="transaction-date">Transaction Date <span>*</span></label>
            <input id="transaction-date" name="transaction_date" type="datetime-local" value="<?= escapeHtml(date('Y-m-d\TH:i')) ?>" required>

            <label id="expected-return-date-label" for="expected-return-date">Expected Return Date</label>
            <input id="expected-return-date" name="expected_return_date" type="date">

            <label id="return-date-label" for="return-date">Return Date</label>
            <input id="return-date" name="return_date" type="datetime-local">

            <label for="transaction-status">Status <span>*</span></label>
            <select id="transaction-status" name="status" required>
                <?php foreach ($transactionStatuses as $status): ?>
                    <option value="<?= escapeHtml($status) ?>"><?= escapeHtml($status) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="transaction-notes">Notes</label>
            <textarea id="transaction-notes" name="notes" rows="3" placeholder="Optional details"></textarea>
            <?php if (!$databaseAvailable): ?><p class="modal-demo-note">Preview mode is on. Submissions are not saved.</p><?php endif; ?>
            <div class="form-actions"><button class="button button-secondary" type="button" data-close-drawer>Cancel</button><button class="button" type="submit" id="save-transaction">Save Transaction</button></div>
        </form>
    </section>
</div>

<script>
(() => {
    const backdrop = document.getElementById('transaction-drawer');
    const form = document.getElementById('transaction-form');
    const search = document.getElementById('transaction-search');
    const noResults = document.getElementById('no-search-results');
    const material = document.getElementById('transaction-material');
    const borrower = document.getElementById('transaction-borrower');
    const type = document.getElementById('transaction-type');
    const expectedReturnDate = document.getElementById('expected-return-date');
    const expectedReturnDateLabel = document.getElementById('expected-return-date-label');
    const returnDateLabel = document.getElementById('return-date-label');
    const expectedReturnRequiredMarker = document.getElementById('expected-return-required-marker');
    const status = document.getElementById('transaction-status');
    let lastTrigger = null;
    const fields = {
        id: document.getElementById('transaction-id'),
        material: material,
        borrower: borrower,
        type: type,
        quantity: document.getElementById('transaction-quantity'),
        date: document.getElementById('transaction-date'),
        expectedReturnDate: expectedReturnDate,
        returnDate: document.getElementById('return-date'),
        status: status,
        notes: document.getElementById('transaction-notes'),
    };

    function updateConditionalFields(applyDefaults = false) {
        borrower.required = false;
        const usesExpectedReturnDate = type.value === 'BORROWED';
        const usesReturnDate = type.value === 'RETURNED';
        expectedReturnDate.hidden = !usesExpectedReturnDate;
        expectedReturnDateLabel.hidden = !usesExpectedReturnDate;
        fields.returnDate.hidden = !usesReturnDate;
        returnDateLabel.hidden = !usesReturnDate;
        if (type.value !== 'BORROWED') expectedReturnDate.value = '';
        if (type.value !== 'RETURNED') fields.returnDate.value = '';
        if (applyDefaults) {
            status.value = type.value === 'BORROWED' ? 'ACTIVE' : 'COMPLETED';
        }
    }

    function updateChoiceCards(choiceType) {
        const select = choiceType === 'material' ? fields.material : fields.borrower;
        document.querySelectorAll(`[data-choice="${choiceType}"]`).forEach((card) => {
            const selected = card.dataset.value === select.value;
            card.classList.toggle('is-selected', selected);
            card.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-choice]').forEach((card) => {
        card.addEventListener('click', () => {
            const select = card.dataset.choice === 'material' ? fields.material : fields.borrower;
            select.value = card.dataset.value;
            updateChoiceCards(card.dataset.choice);
        });
    });

    document.querySelectorAll('[data-filter-cards]').forEach((input) => {
        input.addEventListener('input', () => {
            const group = input.dataset.filterCards;
            const query = input.value.trim().toLowerCase();
            let visibleCount = 0;
            document.querySelectorAll(`[data-choice="${group}"]`).forEach((card) => {
                const visible = card.dataset.search.includes(query);
                card.hidden = !visible;
                if (visible) visibleCount += 1;
            });
            const emptyMessage = document.querySelector(`[data-filter-empty="${group}"]`);
            if (emptyMessage) emptyMessage.hidden = visibleCount > 0;
        });
    });

    function openDrawer(mode, source = null) {
        const editing = mode === 'edit';
        const transaction = source ? source.dataset : {};
        lastTrigger = source?.closest('details')?.querySelector('summary')
            || source
            || document.querySelector('[data-open-drawer="add"]');
        fields.id.value = editing ? (transaction.id || fields.id.value) : '';
        form.action = editing ? `edit.php?id=${encodeURIComponent(fields.id.value)}` : 'create.php';
        fields.material.value = editing ? (source ? transaction.materialId : fields.material.value) : '';
        fields.borrower.value = editing ? (source ? transaction.borrowerId : fields.borrower.value) : '';
        fields.type.value = editing ? (source ? transaction.type : fields.type.value) : 'BORROWED';
        fields.quantity.value = editing ? (source ? transaction.quantity : fields.quantity.value) : '1';
        fields.date.value = editing ? (source ? transaction.date : fields.date.value) : <?= json_encode(date('Y-m-d\TH:i')) ?>;
        fields.expectedReturnDate.value = editing ? (source ? transaction.expectedReturnDate : fields.expectedReturnDate.value) : '';
        fields.returnDate.value = editing ? (source ? transaction.returnDate : fields.returnDate.value) : '';
        fields.status.value = editing ? (source ? transaction.status : fields.status.value) : 'ACTIVE';
        fields.notes.value = editing ? (source ? transaction.notes : fields.notes.value) : '';
        updateConditionalFields();
        updateChoiceCards('material');
        updateChoiceCards('borrower');
        document.getElementById('transaction-id-row').hidden = !editing;
        document.getElementById('transaction-id-display').textContent = editing ? fields.id.value : '';
        document.getElementById('drawer-title').textContent = editing ? 'Edit Transaction' : 'Add Transaction';
        document.getElementById('drawer-description').textContent = editing ? 'Update the details for this inventory record.' : 'Record a material movement in the inventory.';
        document.getElementById('save-transaction').textContent = editing ? 'Save Changes' : 'Save Transaction';
        backdrop.hidden = false;
        requestAnimationFrame(() => backdrop.classList.add('is-open'));
        document.body.classList.add('drawer-open');
        fields.material.focus();
    }

    function closeDrawer() {
        backdrop.classList.remove('is-open');
        document.body.classList.remove('drawer-open');
        if (lastTrigger) lastTrigger.focus();
        window.setTimeout(() => {
            if (!backdrop.classList.contains('is-open')) backdrop.hidden = true;
        }, 220);
    }

    type.addEventListener('change', () => updateConditionalFields(true));
    form.addEventListener('submit', (event) => {
        if (fields.material.value === '') {
            event.preventDefault();
            document.querySelector('[data-choice="material"]')?.focus();
        }
    });
    document.querySelectorAll('[data-open-drawer]').forEach((button) => {
        button.addEventListener('click', () => {
            const menu = button.closest('details');
            if (menu) menu.open = false;
            openDrawer(button.dataset.openDrawer, button);
        });
    });
    document.querySelectorAll('.delete-form').forEach((deleteForm) => {
        deleteForm.addEventListener('submit', (event) => {
            if (!window.confirm(`Delete transaction ${deleteForm.dataset.transactionId}?`)) {
                event.preventDefault();
            }
        });
    });
    document.querySelectorAll('[data-close-drawer]').forEach((button) => button.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (event) => {
        if (backdrop.hidden) return;
        if (event.key === 'Escape') closeDrawer();
    });

    if (search) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visibleCount = 0;
            document.querySelectorAll('.transaction-row').forEach((row) => {
                const matches = row.dataset.search.includes(query);
                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });
            if (noResults) noResults.hidden = visibleCount > 0;
        });
    }

    <?php if ($initialModal !== ''): ?>openDrawer('add');<?php endif; ?>
    <?php if ($requestedEditId !== ''): ?>
    const requestedEdit = document.querySelector('[data-open-drawer="edit"][data-id="<?= escapeHtml($requestedEditId) ?>"]');
    if (requestedEdit) openDrawer('edit', requestedEdit);
    <?php endif; ?>
    <?php if ($errors !== []): ?>
    const restoreEdit = <?= json_encode($formAction === 'edit') ?>;
    const restoreButton = restoreEdit
        ? document.querySelector('[data-open-drawer="edit"][data-id="<?= escapeHtml($formId) ?>"]')
        : null;
    openDrawer(restoreEdit ? 'edit' : 'add', restoreButton);
    fields.id.value = <?= json_encode($formId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> || fields.id.value;
    form.action = restoreEdit ? `edit.php?id=${encodeURIComponent(fields.id.value)}` : 'create.php';
    fields.material.value = <?= json_encode($formTransaction['material_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.borrower.value = <?= json_encode($formTransaction['borrower_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.type.value = <?= json_encode($formTransaction['transaction_type'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.quantity.value = <?= json_encode($formTransaction['quantity'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.date.value = <?= json_encode($formTransaction['transaction_date'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.expectedReturnDate.value = <?= json_encode($formTransaction['expected_return_date'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.returnDate.value = <?= json_encode($formTransaction['return_date'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.status.value = <?= json_encode($formTransaction['status'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.notes.value = <?= json_encode($formTransaction['notes'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    document.getElementById('transaction-id-row').hidden = !restoreEdit;
    document.getElementById('transaction-id-display').textContent = restoreEdit ? fields.id.value : '';
    updateConditionalFields();
    updateChoiceCards('material');
    updateChoiceCards('borrower');
    <?php endif; ?>
})();
</script>
</body>
</html>
