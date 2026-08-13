<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$paymentId = (int)($_GET['id'] ?? 0);

if (!$paymentId) {
    setFlash('error', 'Invalid payment ID.');
    redirect(url('modules/payments/index.php'));
}

$stmt = $db->prepare("
    SELECT p.*, t.name as tenant_name, t.contact as tenant_contact, t.id_number,
           h.house_number, h.location, h.rent_amount
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    LEFT JOIN houses h ON t.house_id = h.house_id 
    WHERE p.payment_id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Payment not found.');
    redirect(url('modules/payments/index.php'));
}

$pageTitle = 'View Payment';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-receipt"></i> Payment Details</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/payments/receipt.php?id=' . $paymentId) ?>" class="btn btn-success" target="_blank"><i class="bi bi-printer"></i> Print</a>
        <a href="<?= url('modules/payments/receipt.php?id=' . $paymentId . '&download=1') ?>" class="btn btn-primary"><i class="bi bi-download"></i> Download PDF</a>
        <a href="<?= url('modules/payments/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span><i class="bi bi-cash-stack" style="color:var(--accent)"></i> Payment Information</span>
            </div>
            <div class="card-body">
                <div class="detail-row"><div class="detail-label">Receipt Number</div><div class="detail-value"><code><?= sanitize($payment['receipt_number']) ?></code></div></div>
                <div class="detail-row"><div class="detail-label">Amount Paid</div><div class="detail-value" style="color:var(--success);font-weight:700;font-size:1.2em;"><?= formatCurrency($payment['amount_paid']) ?></div></div>
                <div class="detail-row"><div class="detail-label">Payment Date</div><div class="detail-value"><?= formatDate($payment['payment_date']) ?></div></div>
                <div class="detail-row"><div class="detail-label">Notes</div><div class="detail-value"><?= sanitize($payment['notes'] ?? 'None') ?></div></div>
                <div class="detail-row"><div class="detail-label">Recorded</div><div class="detail-value"><?= formatDateTime($payment['created_at']) ?></div></div>
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span><i class="bi bi-person" style="color:var(--accent)"></i> Tenant</span>
            </div>
            <div class="card-body">
                <div class="detail-row"><div class="detail-label">Name</div><div class="detail-value"><?= sanitize($payment['tenant_name']) ?></div></div>
                <div class="detail-row"><div class="detail-label">Contact</div><div class="detail-value"><?= sanitize($payment['tenant_contact']) ?></div></div>
                <div class="detail-row"><div class="detail-label">ID Number</div><div class="detail-value"><code><?= sanitize($payment['id_number']) ?></code></div></div>
                <a href="<?= url('modules/tenants/view.php?id=' . $payment['tenant_id']) ?>" class="btn btn-primary btn-sm" style="width:100%;margin-top:10px;"><i class="bi bi-person"></i> View Profile</a>
            </div>
        </div>

        <?php if ($payment['house_number']): ?>
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-house" style="color:var(--accent)"></i> House</span>
            </div>
            <div class="card-body">
                <div class="detail-row"><div class="detail-label">House #</div><div class="detail-value"><strong><?= sanitize($payment['house_number']) ?></strong></div></div>
                <div class="detail-row"><div class="detail-label">Location</div><div class="detail-value"><?= sanitize($payment['location']) ?></div></div>
                <div class="detail-row"><div class="detail-label">Rent</div><div class="detail-value" style="color:var(--success);"><?= formatCurrency($payment['rent_amount']) ?></div></div>
                <a href="<?= url('modules/houses/view.php?id=' . $payment['house_id']) ?>" class="btn btn-outline-primary btn-sm" style="width:100%;margin-top:10px;"><i class="bi bi-house"></i> View House</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
