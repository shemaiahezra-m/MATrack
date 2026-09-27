<?php
require_once __DIR__ . '/../config/app.php';

function escapeDashboardHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDashboardDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('M j, Y g:i A', $timestamp);
}

$materials = getDemoMaterials();
$borrowers = getDemoBorrowers();
$transactions = getDemoTransactions();
$lowStockThreshold = 10;
$lowStockMaterials = array_values(array_filter(
    $materials,
    static fn (array $material): bool => (int) $material['stock_quantity'] <= $lowStockThreshold
));
$activeBorrowings = count(array_filter(
    $transactions,
    static fn (array $transaction): bool => $transaction['transaction_type'] === 'BORROWED'
        && $transaction['status'] === 'ACTIVE'
));

usort($transactions, static fn (array $left, array $right): int =>
    strtotime($right['transaction_date']) <=> strtotime($left['transaction_date'])
);
$recentTransactions = array_slice($transactions, 0, 5);
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
<div class="container dashboard-container">
    <section class="intro-row dashboard-intro">
        <div>
            <p class="eyebrow">Department inventory</p>
            <h1>Dashboard</h1>
            <p class="intro-copy">A quick overview of materials, borrowers, and recent activity.</p>
        </div>
    </section>

    <div class="demo-banner"><span class="demo-dot" aria-hidden="true"></span><div><strong>Demo mode</strong><span> Dashboard figures use the same sample records as the inventory pages.</span></div></div>

    <section class="dashboard-summary" aria-label="Inventory summary">
        <article class="summary-card">
            <span class="summary-label">Total Materials</span>
            <strong class="summary-value"><?= count($materials) ?></strong>
            <span class="summary-detail">materials in the inventory</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Total Borrowers</span>
            <strong class="summary-value"><?= count($borrowers) ?></strong>
            <span class="summary-detail">registered department borrowers</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Active Borrowings</span>
            <strong class="summary-value"><?= $activeBorrowings ?></strong>
            <span class="summary-detail">borrowed transactions marked active</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Low Stock Materials</span>
            <strong class="summary-value"><?= count($lowStockMaterials) ?></strong>
            <span class="summary-detail">at or below <?= $lowStockThreshold ?> units</span>
        </article>
    </section>

    <div class="dashboard-grid">
        <section class="dashboard-panel recent-panel" aria-labelledby="recent-transactions-title">
            <div class="dashboard-panel-heading">
                <div><h2 id="recent-transactions-title">Recent Transactions</h2><p>Latest recorded inventory activity</p></div>
                <a class="panel-link" href="../transactions/index.php">View all</a>
            </div>
            <?php if ($recentTransactions === []): ?>
                <p class="dashboard-empty">There are no transactions to show yet.</p>
            <?php else: ?>
                <div class="dashboard-table-scroll">
                    <table class="dashboard-table">
                        <thead><tr><th>Transaction ID</th><th>Material</th><th>Borrower</th><th>Type</th><th>Quantity</th><th>Transaction Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <?php
                            $material = $materialsById[$transaction['material_id']] ?? null;
                            $borrower = $transaction['borrower_id'] !== null
                                ? ($borrowersById[$transaction['borrower_id']] ?? null)
                                : null;
                            ?>
                            <tr>
                                <td class="record-id"><?= escapeDashboardHtml($transaction['transaction_id']) ?></td>
                                <td><?= escapeDashboardHtml($material['material_name'] ?? 'Unknown material') ?></td>
                                <td><?= escapeDashboardHtml($borrower['borrower_name'] ?? '—') ?></td>
                                <td><span class="type-badge type-<?= strtolower(escapeDashboardHtml($transaction['transaction_type'])) ?>"><?= escapeDashboardHtml($transaction['transaction_type']) ?></span></td>
                                <td><?= (int) $transaction['quantity'] ?></td>
                                <td class="transaction-date"><?= escapeDashboardHtml(formatDashboardDate($transaction['transaction_date'])) ?></td>
                                <td><span class="status-badge status-<?= strtolower(escapeDashboardHtml($transaction['status'])) ?>"><?= escapeDashboardHtml($transaction['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="dashboard-side-panels">
            <section class="dashboard-panel low-stock-panel" aria-labelledby="low-stock-title">
                <div class="dashboard-panel-heading">
                    <div><h2 id="low-stock-title">Low Stock Materials</h2><p>Stock of <?= $lowStockThreshold ?> units or less</p></div>
                    <a class="panel-link" href="../materials/index.php">Materials</a>
                </div>
                <?php if ($lowStockMaterials === []): ?>
                    <p class="dashboard-empty">No materials are below the low-stock threshold.</p>
                <?php else: ?>
                    <ul class="low-stock-list">
                        <?php foreach ($lowStockMaterials as $material): ?>
                            <li>
                                <span class="low-stock-name"><strong><?= escapeDashboardHtml($material['material_name']) ?></strong><small><?= escapeDashboardHtml($material['material_id']) ?></small></span>
                                <span class="low-stock-quantity"><?= (int) $material['stock_quantity'] ?> <small><?= escapeDashboardHtml($material['unit']) ?></small></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="dashboard-panel quick-actions-panel" aria-labelledby="quick-actions-title">
                <div class="dashboard-panel-heading"><div><h2 id="quick-actions-title">Quick Actions</h2><p>Open a form to add a record</p></div></div>
                <div class="quick-actions-list">
                    <a class="button" href="../materials/index.php?add=1">＋ Add Material</a>
                    <a class="button" href="../borrowers/index.php?add=1">＋ Add Borrower</a>
                    <a class="button" href="../transactions/index.php?add=1">＋ Add Transaction</a>
                </div>
            </section>
        </div>
    </div>
    <footer class="page-footer">MATrack <span>·</span> Department Materials Inventory</footer>
</div>
</main>
</div>
</body>
</html>
