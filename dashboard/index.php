<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function escapeDashboardHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDashboardDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('M j, Y g:i A', $timestamp);
}

function getDashboardStatus(array $transaction): string
{
    return in_array($transaction['status'], ['ACTIVE', 'COMPLETED', 'OVERDUE', 'CANCELLED'], true)
        ? $transaction['status']
        : 'ACTIVE';
}

function getDashboardStatusClass(string $status): string
{
    return match ($status) {
        'ACTIVE' => 'status-active',
        'COMPLETED' => 'status-completed',
        'OVERDUE' => 'status-overdue',
        'CANCELLED' => 'status-cancelled',
        default => 'status-active',
    };
}

function getDashboardTypeClass(string $type): string
{
    return match ($type) {
        'BORROWED' => 'type-borrowed',
        'RETURNED' => 'type-returned',
        'USED' => 'type-used',
        'DISPOSED' => 'type-disposed',
        default => 'type-borrowed',
    };
}

$lowStockThreshold = 10;
$databaseError = null;
$databaseAvailable = false;

if (DATABASE_ENABLED) {
    try {
        $pdo = getDatabaseConnection();
        $materials = $pdo->query(
            'SELECT material_id, material_name, category, unit, stock_quantity, description
             FROM materials ORDER BY material_name'
        )->fetchAll();
        $borrowers = $pdo->query(
            'SELECT borrower_id, borrower_name, contact, department
             FROM borrowers ORDER BY borrower_name'
        )->fetchAll();
        $transactions = $pdo->query(
            'SELECT t.transaction_id, t.material_id, t.borrower_id,
                    t.transaction_type, t.quantity, t.transaction_date,
                    t.expected_return_date, t.return_date, t.status, t.notes,
                    m.material_name, b.borrower_name
             FROM transactions AS t
             INNER JOIN materials AS m ON m.material_id = t.material_id
             LEFT JOIN borrowers AS b ON b.borrower_id = t.borrower_id
             ORDER BY t.transaction_date DESC, t.transaction_id DESC
             LIMIT 5'
        )->fetchAll();
        $activeBorrowings = (int) $pdo->query(
            "SELECT COUNT(*) FROM transactions
             WHERE transaction_type = 'BORROWED' AND status = 'ACTIVE'"
        )->fetchColumn();
        $databaseAvailable = true;
    } catch (PDOException $exception) {
        $databaseError = $exception->getMessage();
    }
}

if (!$databaseAvailable) {
    $materials = getDemoMaterials();
    $borrowers = getDemoBorrowers();
    $transactions = getDemoTransactions();
    $activeBorrowings = count(array_filter(
        $transactions,
        static fn (array $transaction): bool => $transaction['transaction_type'] === 'BORROWED'
            && $transaction['status'] === 'ACTIVE'
    ));
}

$lowStockMaterials = array_values(array_filter(
    $materials,
    static fn (array $material): bool => (int) $material['stock_quantity'] <= $lowStockThreshold
));

if (!$databaseAvailable) {
    usort($transactions, static fn (array $left, array $right): int =>
        strtotime($right['transaction_date']) <=> strtotime($left['transaction_date'])
    );
}
$recentTransactions = $transactions;
$materialsById = array_column($materials, null, 'material_id');
$borrowersById = array_column($borrowers, null, 'borrower_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MATrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-page { padding-top: 34px; }
        .dashboard-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
        .dashboard-header .eyebrow { margin-bottom: 5px; }
        .dashboard-header h1 { margin-bottom: 5px; }
        .dashboard-header-tools { display: grid; flex: 0 0 auto; justify-items: end; gap: 8px; }
        .dashboard-demo-badge { padding: 5px 9px; border: 1px solid var(--border); border-radius: 4px; background: var(--white); color: var(--muted); font-size: .72rem; font-weight: 600; }
        .dashboard-action-list { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
        .dashboard-action { min-height: 37px; padding: 7px 12px; font-size: .76rem; white-space: nowrap; }
        .dashboard-statistics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 17px; }
        .dashboard-stat { min-width: 0; padding: 13px 15px; border: 1px solid var(--border); border-radius: 5px; background: var(--white); }
        .dashboard-stat-label { display: block; margin-bottom: 4px; color: var(--muted); font-size: .72rem; font-weight: 600; }
        .dashboard-stat-value { display: block; color: var(--navy); font-size: 1.65rem; font-weight: 700; letter-spacing: -.04em; line-height: 1.15; }
        .dashboard-stat-note { display: block; margin-top: 4px; color: var(--muted); font-size: .68rem; }
        .dashboard-main-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(260px, .8fr); align-items: start; gap: 15px; }
        .dashboard-section { min-width: 0; overflow: hidden; border: 1px solid var(--border); border-radius: 5px; background: var(--white); }
        .dashboard-section-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 14px; border-bottom: 1px solid var(--border); }
        .dashboard-section-header h2 { margin: 0; color: var(--navy); font-size: .84rem; font-weight: 680; }
        .dashboard-section-header p { margin: 2px 0 0; color: var(--muted); font-size: .69rem; }
        .dashboard-view-all { flex: 0 0 auto; color: var(--navy); font-size: .72rem; font-weight: 650; text-decoration: none; }
        .dashboard-view-all:hover { color: var(--pink-dark); }
        .dashboard-activity-scroll { max-width: 100%; overflow-x: auto; }
        .dashboard-activity-table { min-width: 700px; }
        .dashboard-activity-table th { padding: 8px 9px; font-size: .59rem; }
        .dashboard-activity-table td { padding: 9px; font-size: .7rem; white-space: nowrap; }
        .dashboard-activity-table .record-id { font-size: .68rem; }
        .dashboard-activity-table .transaction-date { min-width: 125px; font-size: .69rem; }
        .dashboard-activity-table .type-badge, .dashboard-activity-table .status-badge { padding: 2px 6px; font-size: .59rem; }
        .dashboard-activity-table .type-disposed { border-color: var(--border); background: #f3f4f6; color: #515a66; }
        .dashboard-status-active { border-color: var(--badge-active-border); background: var(--badge-active-background); color: var(--badge-active-text); }
        .dashboard-status-completed { border-color: var(--badge-returned-border); background: var(--badge-returned-background); color: var(--badge-returned-text); }
        .dashboard-status-overdue { border-color: #f0d9de; background: var(--soft-pink); color: #934b5a; }
        .dashboard-status-cancelled { border-color: var(--border); background: #f3f4f6; color: var(--muted); }
        .dashboard-inventory-list { margin: 0; padding: 0 14px; list-style: none; }
        .dashboard-inventory-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 8px; border-bottom: 1px solid #f0f1f2; border-radius: 3px; }
        .dashboard-inventory-item:last-child { border-bottom: 0; }
        .dashboard-inventory-item.is-low-stock { background: #fffafb; }
        .dashboard-material-name { display: grid; min-width: 0; gap: 2px; }
        .dashboard-material-name strong { overflow: hidden; color: var(--navy); font-size: .77rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .dashboard-material-name small { color: var(--muted); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .64rem; }
        .dashboard-stock-value { flex: 0 0 auto; color: var(--navy); font-size: .77rem; font-weight: 680; text-align: right; }
        .dashboard-stock-value small { color: var(--muted); font-size: .66rem; font-weight: 500; }
        .dashboard-inventory-item.is-low-stock .dashboard-stock-value { color: var(--pink-dark); }
        .dashboard-empty { margin: 0; padding: 18px 14px; color: var(--muted); font-size: .75rem; }
        .dashboard-page > .page-footer { padding-top: 14px; }

        @media (max-width: 900px) {
            .dashboard-main-grid { grid-template-columns: minmax(0, 1fr); }
            .dashboard-header { align-items: flex-start; flex-direction: column; gap: 12px; }
            .dashboard-header-tools { width: 100%; justify-items: start; }
            .dashboard-action-list { justify-content: flex-start; }
        }
        @media (max-width: 800px) {
            .dashboard-statistics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .dashboard-page { padding-top: 24px; }
            .dashboard-header { align-items: flex-start; flex-direction: column; gap: 12px; }
            .dashboard-header-tools { width: 100%; justify-items: start; }
            .dashboard-action-list { justify-content: flex-start; }
            .dashboard-statistics { gap: 8px; }
            .dashboard-stat { padding: 11px 12px; }
            .dashboard-stat-value { font-size: 1.45rem; }
            .dashboard-main-grid { gap: 12px; }
            .dashboard-activity-table { min-width: 660px; }
        }
    </style>
</head>
<body>
<div class="app-shell">
<aside class="sidebar" aria-label="Main navigation">
    <a class="brand-lockup" href="../index.php"><img class="brand-logo" src="../assets/css/MATrack-Logo.png" alt="MATrack"></a>
    <p class="nav-heading">Overview</p>
    <nav class="side-nav">
        <a class="nav-link active" href="index.php" aria-current="page"><span class="nav-icon" aria-hidden="true">▦</span>Dashboard<span class="active-indicator" aria-hidden="true"></span></a>
    </nav>
    <p class="nav-heading">Inventory</p>
    <nav class="side-nav">
        <a class="nav-link" href="../materials/index.php"><span class="nav-icon" aria-hidden="true">▤</span>Materials</a>
        <a class="nav-link" href="../borrowers/index.php"><span class="nav-icon" aria-hidden="true">♙</span>Borrowers</a>
        <a class="nav-link" href="../transactions/index.php"><span class="nav-icon" aria-hidden="true">↔</span>Transactions</a>
    </nav>
    <div class="sidebar-footer"><strong>MATrack</strong><span>Department inventory system</span></div>
</aside>
<main class="main-content">
<div class="container dashboard-container dashboard-page">
    <section class="dashboard-header">
        <div>
            <p class="eyebrow">DEPARTMENT INVENTORY</p>
            <h1>Dashboard</h1>
            <p class="intro-copy">A quick overview of materials, borrowers, and recent activity.</p>
        </div>
        <div class="dashboard-header-tools">
            <nav class="dashboard-action-list" aria-label="Quick actions">
                <a class="button dashboard-action" href="../materials/index.php?add=1"><span aria-hidden="true">＋</span> Add Material</a>
                <a class="button dashboard-action" href="../borrowers/index.php?add=1"><span aria-hidden="true">＋</span> Add Borrower</a>
                <a class="button dashboard-action" href="../transactions/index.php?add=1"><span aria-hidden="true">＋</span> Add Transaction</a>
            </nav>
            <span class="dashboard-demo-badge"><?= $databaseAvailable ? 'PostgreSQL mode' : 'Preview mode · Shared sample data' ?></span>
        </div>
    </section>

    <?php if ($databaseError !== null): ?>
        <section class="error-panel"><h2>Could not connect to the database</h2><p>Check the PostgreSQL connection settings in <code>config/database.php</code> and confirm the database is reachable.</p><p class="technical-error"><?= escapeDashboardHtml($databaseError) ?></p></section>
    <?php endif; ?>

    <section class="dashboard-statistics" aria-label="Inventory summary">
        <article class="dashboard-stat">
            <span class="dashboard-stat-label">Total Materials</span>
            <strong class="dashboard-stat-value"><?= count($materials) ?></strong>
            <span class="dashboard-stat-note">material records</span>
        </article>
        <article class="dashboard-stat">
            <span class="dashboard-stat-label">Total Borrowers</span>
            <strong class="dashboard-stat-value"><?= count($borrowers) ?></strong>
            <span class="dashboard-stat-note">registered borrowers</span>
        </article>
        <article class="dashboard-stat">
            <span class="dashboard-stat-label">Active Borrowings</span>
            <strong class="dashboard-stat-value"><?= $activeBorrowings ?></strong>
            <span class="dashboard-stat-note">borrowed and active</span>
        </article>
        <article class="dashboard-stat">
            <span class="dashboard-stat-label">Low Stock Materials</span>
            <strong class="dashboard-stat-value"><?= count($lowStockMaterials) ?></strong>
            <span class="dashboard-stat-note">at or below <?= $lowStockThreshold ?> units</span>
        </article>
    </section>

    <div class="dashboard-main-grid">
        <section class="dashboard-section" aria-labelledby="recent-transactions-title">
            <div class="dashboard-section-header">
                <div><h2 id="recent-transactions-title">Recent Activity</h2><p>Latest inventory transactions</p></div>
                <a class="dashboard-view-all" href="../transactions/index.php">View all <span aria-hidden="true">→</span></a>
            </div>
            <?php if ($recentTransactions === []): ?>
                <p class="dashboard-empty">There are no transactions to show yet.</p>
            <?php else: ?>
                <div class="dashboard-activity-scroll">
                    <table class="dashboard-activity-table">
                        <thead><tr><th>Transaction ID</th><th>Material</th><th>Borrower</th><th>Type</th><th>Quantity</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <?php
                            $material = $materialsById[$transaction['material_id']] ?? null;
                            $borrower = $transaction['borrower_id'] !== null
                                ? ($borrowersById[$transaction['borrower_id']] ?? null)
                                : null;
                            $displayStatus = getDashboardStatus($transaction);
                            ?>
                            <tr>
                                <td class="record-id"><?= escapeDashboardHtml($transaction['transaction_id']) ?></td>
                                <td><?= escapeDashboardHtml($material['material_name'] ?? 'Unknown material') ?></td>
                                <td><?= escapeDashboardHtml($borrower['borrower_name'] ?? '—') ?></td>
                                <td><span class="type-badge <?= escapeDashboardHtml(getDashboardTypeClass($transaction['transaction_type'])) ?>"><?= escapeDashboardHtml($transaction['transaction_type']) ?></span></td>
                                <td><?= (int) $transaction['quantity'] ?></td>
                                <td class="transaction-date"><?= escapeDashboardHtml(formatDashboardDate($transaction['transaction_date'])) ?></td>
                                <td><span class="status-badge dashboard-<?= escapeDashboardHtml(getDashboardStatusClass($displayStatus)) ?>"><?= escapeDashboardHtml($displayStatus) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section" aria-labelledby="inventory-status-title">
            <div class="dashboard-section-header">
                <div><h2 id="inventory-status-title">Inventory Status</h2><p>Current materials and available stock</p></div>
                <a class="dashboard-view-all" href="../materials/index.php">Materials <span aria-hidden="true">→</span></a>
            </div>
            <ul class="dashboard-inventory-list">
                <?php foreach ($materials as $material): ?>
                    <?php $isLowStock = (int) $material['stock_quantity'] <= $lowStockThreshold; ?>
                    <li class="dashboard-inventory-item<?= $isLowStock ? ' is-low-stock' : '' ?>">
                        <span class="dashboard-material-name"><strong><?= escapeDashboardHtml($material['material_name']) ?></strong><small><?= escapeDashboardHtml($material['material_id']) ?></small></span>
                        <span class="dashboard-stock-value"><?= (int) $material['stock_quantity'] ?> <small><?= escapeDashboardHtml($material['unit']) ?></small></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <footer class="page-footer">MATrack <span>·</span> Department inventory system</footer>
</div>
</main>
</div>
</body>
</html>
