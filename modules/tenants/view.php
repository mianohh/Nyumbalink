<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$tenantId = (int)($_GET['id'] ?? 0);

if (!$tenantId) {
    setFlash('error', 'Invalid tenant ID.');
    redirect(url('modules/tenants/index.php'));
}

// Get tenant details
$stmt = $db->prepare("
    SELECT t.*, h.house_number, h.location, h.rent_amount, h.status as house_status
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.house_id 
    WHERE t.tenant_id = ?
");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/tenants/index.php'));
}

// Get payment history
$stmt = $db->prepare("SELECT * FROM payments WHERE tenant_id = ? ORDER BY payment_date DESC LIMIT 10");
$stmt->execute([$tenantId]);
$payments = $stmt->fetchAll();

// Get rental agreements
$stmt = $db->prepare("SELECT * FROM rental_agreements WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$tenantId]);
$agreements = $stmt->fetchAll();

// Calculate balance
function getTenantBalance($db, $tenantId, $rentAmount) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount_paid), 0) 
        FROM payments 
        WHERE tenant_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(payment_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$tenantId]);
    $paid = $stmt->fetchColumn();
    return $rentAmount - $paid;
}

$balance = $tenant['rent_amount'] ? getTenantBalance($db, $tenantId, $tenant['rent_amount']) : 0;

$pageTitle = 'View Tenant';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-person-fill"></i> <?= sanitize($tenant['name']) ?></h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/tenants/edit.php?id=' . $tenantId) ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="<?= url('modules/tenants/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <!-- Tenant Details -->
    <div class="card">
        <div class="card-header"><span><i class="bi bi-person-lines-fill" style="color:var(--primary)"></i> Tenant Details</span></div>
        <div class="card-body">
            <div class="detail-row">
                <div class="detail-label">Tenant ID</div>
                <div class="detail-value"><?= $tenant['tenant_id'] ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Contact</div>
                <div class="detail-value"><?= sanitize($tenant['contact']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">ID Number</div>
                <div class="detail-value"><code><?= sanitize($tenant['id_number']) ?></code></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">House</div>
                <div class="detail-value">
                    <?php if ($tenant['house_number']): ?>
                        <span class="badge badge-success"><?= sanitize($tenant['house_number']) ?></span>
                        <?= sanitize($tenant['location']) ?>
                    <?php else: ?>
                        <span class="badge badge-warning">Unassigned</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Monthly Rent</div>
                <div class="detail-value" style="color:var(--success);font-weight:700;font-size:1.1rem;">
                    <?= $tenant['rent_amount'] ? formatCurrency($tenant['rent_amount']) : 'N/A' ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Current Balance</div>
                <div class="detail-value" style="font-weight:700;font-size:1.1rem;<?= $balance > 0 ? 'color:var(--danger)' : 'color:var(--success)' ?>">
                    <?= $tenant['rent_amount'] ? formatCurrency($balance) : 'N/A' ?>
                </div>
            </div>
            <div class="detail-row" style="border-bottom:none;">
                <div class="detail-label">Registered</div>
                <div class="detail-value" style="color:var(--text-muted)"><?= formatDateTime($tenant['created_at']) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header"><span><i class="bi bi-lightning" style="color:var(--warning)"></i> Quick Actions</span></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="<?= url('modules/payments/create.php?tenant_id=' . $tenantId) ?>" class="btn btn-success" style="width:100%;justify-content:center;">
                    <i class="bi bi-cash-stack"></i> Record Payment
                </a>
                <a href="<?= url('modules/payments/history.php?tenant_id=' . $tenantId) ?>" class="btn btn-info" style="width:100%;justify-content:center;">
                    <i class="bi bi-clock-history"></i> Payment History
                </a>
                <a href="<?= url('modules/tenants/agreements.php?tenant_id=' . $tenantId) ?>" class="btn btn-outline-primary" style="width:100%;justify-content:center;">
                    <i class="bi bi-file-earmark-text"></i> Rental Agreements
                </a>
                <?php if ($tenant['house_number']): ?>
                    <a href="<?= url('modules/houses/view.php?id=' . $tenant['house_id']) ?>" class="btn btn-outline-primary" style="width:100%;justify-content:center;">
                        <i class="bi bi-house-door"></i> View House Details
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clock-history" style="color:var(--info)"></i> Recent Payments</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-wallet2"></i>
                <h4>No payments recorded</h4>
                <p>Payment history for this tenant will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><code><?= sanitize($payment['receipt_number']) ?></code></td>
                                <td style="color:var(--success);font-weight:700;"><?= formatCurrency($payment['amount_paid']) ?></td>
                                <td style="color:var(--text-muted)"><?= formatDate($payment['payment_date']) ?></td>
                                <td><?= sanitize($payment['notes'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
