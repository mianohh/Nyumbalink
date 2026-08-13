<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$page = (int)($_GET['page'] ?? 1);
$search = getInput('search');
$dateFrom = getInput('date_from');
$dateTo = getInput('date_to');

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (p.receipt_number LIKE ? OR t.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom) {
    $where .= " AND p.payment_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND p.payment_date <= ?";
    $params[] = $dateTo;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM payments p JOIN tenants t ON p.tenant_id = t.tenant_id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / PER_PAGE));
$page = min(max(1, $page), $totalPages);
$offset = ($page - 1) * PER_PAGE;

$stmt = $db->prepare("
    SELECT p.*, t.name as tenant_name, t.contact as tenant_contact, h.house_number 
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    LEFT JOIN houses h ON t.house_id = h.house_id 
    $where 
    ORDER BY p.payment_date DESC, p.created_at DESC 
    LIMIT $offset, " . PER_PAGE
);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pageTitle = 'Payments';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-cash-stack"></i> Payments</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/payments/create.php') ?>" class="btn btn-primary"><i class="bi bi-cash"></i> Record Payment</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;align-items:end;">
            <div>
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Receipt or tenant name..." value="<?= sanitize($search) ?>">
            </div>
            <div>
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="date_from" value="<?= sanitize($dateFrom) ?>">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="date_to" value="<?= sanitize($dateTo) ?>">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                <a href="<?= url('modules/payments/index.php') ?>" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span><i class="bi bi-list-ul" style="color:var(--accent)"></i> Payment Records</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-cash-stack"></i>
                <h4>No payments found</h4>
                <p>No payments match your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Tenant</th>
                            <th>House</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><code><?= sanitize($payment['receipt_number']) ?></code></td>
                                <td>
                                    <a href="<?= url('modules/tenants/view.php?id=' . $payment['tenant_id']) ?>" style="color:var(--accent);text-decoration:none;"><?= sanitize($payment['tenant_name']) ?></a>
                                </td>
                                <td><?= sanitize($payment['house_number'] ?? 'N/A') ?></td>
                                <td style="color:var(--success);font-weight:600;"><?= formatCurrency($payment['amount_paid']) ?></td>
                                <td><?= formatDate($payment['payment_date']) ?></td>
                                <td style="display:flex;gap:4px;">
                                    <a href="<?= url('modules/payments/view.php?id=' . $payment['payment_id']) ?>" class="btn btn-icon btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="<?= url('modules/payments/receipt.php?id=' . $payment['payment_id']) ?>" class="btn btn-icon btn-sm btn-outline-success" title="Print" target="_blank"><i class="bi bi-printer"></i></a>
                                    <a href="<?= url('modules/payments/receipt.php?id=' . $payment['payment_id'] . '&download=1') ?>" class="btn btn-icon btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div style="padding:16px;display:flex;justify-content:center;">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
