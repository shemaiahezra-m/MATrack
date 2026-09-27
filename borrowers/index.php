<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

function escapeHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$flash = $_SESSION['borrowers_flash'] ?? [];
unset($_SESSION['borrowers_flash']);
$errors = $flash['errors'] ?? [];
$message = $flash['message'] ?? (string) ($_GET['message'] ?? '');
$formBorrower = $flash['borrower'] ?? [
    'borrower_name' => '',
    'contact' => '',
    'department' => '',
];
$formAction = $flash['action'] ?? '';
$formId = (string) ($flash['id'] ?? '');

$databaseError = null;
if (DATABASE_ENABLED) {
    try {
        $pdo = getDatabaseConnection();
        $statement = $pdo->query(
            'SELECT borrower_id, borrower_name, contact, department
             FROM borrowers
             ORDER BY borrower_name'
        );
        $borrowers = $statement->fetchAll();
    } catch (PDOException $exception) {
        $databaseError = $exception->getMessage();
        $borrowers = [];
    }
} else {
    $borrowers = getDemoBorrowers();
}

$initialModal = isset($_GET['add']) ? 'add' : '';
$requestedEditId = $_GET['edit'] ?? '';
if (!is_string($requestedEditId) || !preg_match('/^BOR-[0-9]{3,}$/D', $requestedEditId)) {
    $requestedEditId = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowers | MATrack</title>
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
        <a class="nav-link active" href="index.php" aria-current="page"><span class="nav-icon" aria-hidden="true">♙</span>Borrowers<span class="active-indicator" aria-hidden="true"></span></a>
        <a class="nav-link" href="../transactions/index.php"><span class="nav-icon" aria-hidden="true">↔</span>Transactions</a>
    </nav>
    <div class="sidebar-footer"><strong>MATrack</strong><span>Department inventory system</span></div>
</aside>
<main class="main-content">
<div class="container">
    <section class="intro-row">
        <div>
            <p class="eyebrow">People &amp; departments</p>
            <h1>Borrowers</h1>
            <p class="intro-copy">Manage the people and departments that borrow materials.</p>
        </div>
        <button class="button" type="button" data-open-modal="add"><span aria-hidden="true">＋</span> Add Borrower</button>
    </section>

    <?php if (!DATABASE_ENABLED): ?>
        <div class="demo-banner"><span class="demo-dot" aria-hidden="true"></span><div><strong>Preview mode</strong><span> Sample borrowers are shown. Changes are not saved until the database is connected.</span></div></div>
    <?php endif; ?>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= escapeHtml($message) ?></p><?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="validation-errors" role="alert"><strong>Please check the form:</strong><ul><?php foreach ($errors as $error): ?><li><?= escapeHtml($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
        <section class="error-panel"><h2>Could not connect to the database</h2><p>Check the connection settings in <code>config/database.php</code> and confirm PostgreSQL is reachable.</p><p class="technical-error"><?= escapeHtml($databaseError) ?></p></section>
    <?php endif; ?>

    <section class="inventory-card" aria-label="Borrowers">
        <div class="inventory-toolbar">
            <div><h2>All borrowers</h2><p><?= count($borrowers) ?> borrower<?= count($borrowers) === 1 ? '' : 's' ?> registered</p></div>
            <label class="search-box"><span class="search-icon" aria-hidden="true">⌕</span><span class="sr-only">Search borrowers</span><input id="borrower-search" type="search" placeholder="Search borrowers..." autocomplete="off"></label>
        </div>
        <?php if ($borrowers === []): ?>
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">＋</span><h3>No borrowers yet</h3><p>Add a borrower to start keeping track of department materials.</p><button class="button" type="button" data-open-modal="add">Add Borrower</button></div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>ID</th><th>Borrower</th><th>Contact</th><th>Department</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($borrowers as $borrower): ?>
                        <tr class="borrower-row" data-search="<?= escapeHtml(strtolower(implode(' ', [$borrower['borrower_name'], $borrower['contact'] ?? '', $borrower['department'] ?? '']))) ?>">
                            <td class="record-id"><?= escapeHtml($borrower['borrower_id']) ?></td>
                            <td><span class="material-initial" aria-hidden="true"><?= escapeHtml(strtoupper(substr($borrower['borrower_name'], 0, 1))) ?></span><span class="material-name"><?= escapeHtml($borrower['borrower_name']) ?></span></td>
                            <td><?= escapeHtml($borrower['contact']) !== '' ? escapeHtml($borrower['contact']) : '<span class="muted">—</span>' ?></td>
                            <td><?= escapeHtml($borrower['department']) !== '' ? '<span class="category-pill">' . escapeHtml($borrower['department']) . '</span>' : '<span class="muted">—</span>' ?></td>
                            <td class="actions">
                                <details class="action-menu">
                                    <summary aria-label="Actions for <?= escapeHtml($borrower['borrower_name']) ?>">•••</summary>
                                    <div class="action-menu-panel">
                                        <button class="menu-action" type="button" data-open-modal="edit" data-id="<?= escapeHtml($borrower['borrower_id']) ?>"
                                            data-name="<?= escapeHtml($borrower['borrower_name']) ?>" data-contact="<?= escapeHtml($borrower['contact']) ?>"
                                            data-department="<?= escapeHtml($borrower['department']) ?>">Edit borrower</button>
                                        <form class="delete-form" action="delete.php" method="post" data-borrower-name="<?= escapeHtml($borrower['borrower_name']) ?>">
                                            <input type="hidden" name="borrower_id" value="<?= escapeHtml($borrower['borrower_id']) ?>">
                                            <button class="menu-action delete-menu-action" type="submit">Delete borrower</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="no-search-results" id="no-search-results" hidden>No borrowers match your search.</p>
            </div>
        <?php endif; ?>
    </section>
    <footer class="page-footer">MATrack <span>·</span> Department Materials Inventory</footer>
</div>
</main>
</div>

<div class="drawer-shell" id="borrower-modal" hidden>
    <section class="drawer-panel" role="region" aria-labelledby="modal-title" aria-describedby="modal-copy" tabindex="-1">
        <button class="modal-close" type="button" data-close-modal aria-label="Close dialog">×</button>
        <p class="eyebrow">Borrower records</p>
        <h2 id="modal-title">Add Borrower</h2>
        <p class="modal-copy" id="modal-copy">Add a person to the department borrower list.</p>
        <form id="borrower-form" method="post" action="create.php">
            <input type="hidden" name="borrower_id" id="borrower-id" value="">
            <label for="borrower-name">Borrower Name <span>*</span></label>
            <input id="borrower-name" name="borrower_name" maxlength="100" required>
            <label for="borrower-contact">Contact</label>
            <input id="borrower-contact" name="contact" maxlength="50" placeholder="Phone number or email">
            <label for="borrower-department">Department</label>
            <input id="borrower-department" name="department" maxlength="100" placeholder="e.g. Design Committee">
            <?php if (!DATABASE_ENABLED): ?><p class="modal-demo-note">Preview mode is on. Submissions will not be saved.</p><?php endif; ?>
            <div class="form-actions"><button class="button button-secondary" type="button" data-close-modal>Cancel</button><button class="button" type="submit" id="submit-borrower">Add Borrower</button></div>
        </form>
    </section>
</div>

<script>
(() => {
    const backdrop = document.getElementById('borrower-modal');
    const form = document.getElementById('borrower-form');
    const search = document.getElementById('borrower-search');
    const noResults = document.getElementById('no-search-results');
    let lastTrigger = null;
    const fields = {
        id: document.getElementById('borrower-id'),
        name: document.getElementById('borrower-name'),
        contact: document.getElementById('borrower-contact'),
        department: document.getElementById('borrower-department'),
    };

    function openModal(mode, source = null) {
        const editing = mode === 'edit';
        const borrower = source ? source.dataset : {};
        lastTrigger = source?.closest('details')?.querySelector('summary')
            || source
            || document.querySelector('[data-open-modal="add"]');
        fields.id.value = editing ? (borrower.id || fields.id.value) : '';
        form.action = editing ? `edit.php?id=${encodeURIComponent(fields.id.value)}` : 'create.php';
        fields.name.value = editing ? (source ? borrower.name : fields.name.value) : '';
        fields.contact.value = editing ? (source ? borrower.contact : fields.contact.value) : '';
        fields.department.value = editing ? (source ? borrower.department : fields.department.value) : '';
        document.getElementById('modal-title').textContent = editing ? 'Edit Borrower' : 'Add Borrower';
        document.getElementById('modal-copy').textContent = editing ? 'Update this borrower’s contact or department details.' : 'Add a person to the department borrower list.';
        document.getElementById('submit-borrower').textContent = editing ? 'Save Changes' : 'Add Borrower';
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
            if (!window.confirm(`Delete ${deleteForm.dataset.borrowerName} from the borrower list?`)) {
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
            document.querySelectorAll('.borrower-row').forEach((row) => {
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
    fields.name.value = <?= json_encode($formBorrower['borrower_name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.contact.value = <?= json_encode($formBorrower['contact'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    fields.department.value = <?= json_encode($formBorrower['department'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    <?php endif; ?>
})();
</script>
</body>
</html>
