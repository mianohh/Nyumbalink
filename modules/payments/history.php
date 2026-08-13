<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$tenantId = (int)($_GET['tenant_id'] ?? 0);

if (!$tenantId) {
    setFlash('error', 'Invalid tenant ID.');
    redirect(url('modules/payments/index.php'));
}

$stmt = $db->prepare("SELECT t.*, h.house_number, h.rent_amount FROM tenants t LEFT JOIN houses h ON t.house_id = h.house_id WHERE t.tenant_id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/payments/index.php'));
}

$stmt = $db->prepare("SELECT p.* FROM payments p WHERE p.tenant_id = ? ORDER BY p.payment_date DESC");
$stmt->execute([$tenantId]);
$payments = $stmt->fetchAll();

$totalPaid = array_sum(array_column($payments, 'amount_paid'));
$currentMonthPaid = 0;
$currentMonth = date('Y-m');
foreach ($payments as $p) {
    if (substr($p['payment_date'], 0, 7) === $currentMonth) {
        $currentMonthPaid += $p['amount_paid'];
    }
}

$pageTitle = 'Payment History';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-clock-history"></i> Payment History: <?= sanitize($tenant['name']) ?></h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/payments/create.php?tenant_id=' . $tenantId) ?>" class="btn btn-primary"><i class="bi bi-cash"></i> Record Payment</a>
        <a href="<?= url('modules/tenants/view.php?id=' . $tenantId) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Tenant</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-house"></i></div>
        <div class="stat-info">
            <div class="stat-label">Monthly Rent</div>
            <div class="stat-value"><?= $tenant['rent_amount'] ? formatCurrency($tenant['rent_amount']) : 'N/A' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash"></i></div>
        <div class="stat-info">
            <div class="stat-label">This Month Paid</div>
            <div class="stat-value"><?= formatCurrency($currentMonthPaid) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Paid (All Time)</div>
            <div class="stat-value"><?= formatCurrency($totalPaid) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span><i class="bi bi-list-ul" style="color:var(--accent)"></i> Payment Records</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No payments yet</h4>
                <p>No payments have been recorded for this tenant.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Receipt #</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $payment): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><code><?= sanitize($payment['receipt_number']) ?></code></td>
                                <td style="color:var(--success);font-weight:600;"><?= formatCurrency($payment['amount_paid']) ?></td>
                                <td><?= formatDate($payment['payment_date']) ?></td>
                                <td><?= sanitize($payment['notes'] ?? '') ?></td>
                                <td style="display:flex;gap:4px;">
                                    <a href="<?= url('modules/payments/view.php?id=' . $payment['payment_id']) ?>" class="btn btn-icon btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <a href="<?= url('modules/payments/receipt.php?id=' . $payment['payment_id']) ?>" class="btn btn-icon btn-sm btn-outline-success" target="_blank"><i class="bi bi-printer"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
