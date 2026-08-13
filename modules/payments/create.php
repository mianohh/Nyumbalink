<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$errors = [];
$data = [
    'tenant_id' => getInput('tenant_id'),
    'amount_paid' => '',
    'payment_date' => date('Y-m-d'),
    'notes' => ''
];

$selectedTenant = null;
if ($data['tenant_id']) {
    $stmt = $db->prepare("SELECT t.*, h.house_number, h.rent_amount FROM tenants t LEFT JOIN houses h ON t.house_id = h.house_id WHERE t.tenant_id = ?");
    $stmt->execute([$data['tenant_id']]);
    $selectedTenant = $stmt->fetch();
}

$tenants = $db->query("SELECT t.tenant_id, t.name, t.id_number, h.house_number, h.rent_amount FROM tenants t LEFT JOIN houses h ON t.house_id = h.house_id ORDER BY t.name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/payments/create.php'));
    }

    $data = [
        'tenant_id' => getInput('tenant_id'),
        'amount_paid' => getInput('amount_paid'),
        'payment_date' => getInput('payment_date'),
        'notes' => getInput('notes')
    ];

    $requiredErrors = validateRequired(['tenant_id' => 'Tenant', 'amount_paid' => 'Amount', 'payment_date' => 'Date'], $data);
    $errors = array_merge($errors, $requiredErrors);

    if (!empty($data['amount_paid']) && !validateAmount($data['amount_paid'])) {
        $errors['amount_paid'] = 'Please enter a valid amount';
    }

    if (!empty($data['payment_date']) && !validateDate($data['payment_date'])) {
        $errors['payment_date'] = 'Please enter a valid date';
    }

    if (!empty($data['tenant_id'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE tenant_id = ?");
        $stmt->execute([$data['tenant_id']]);
        if ($stmt->fetchColumn() == 0) {
            $errors['tenant_id'] = 'Selected tenant not found';
        }
    }

    if (empty($errors)) {
        try {
            $receiptNumber = generateReceiptNumber();
            $stmt = $db->prepare("INSERT INTO payments (tenant_id, amount_paid, payment_date, receipt_number, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['tenant_id'], $data['amount_paid'], $data['payment_date'], $receiptNumber, $data['notes'] ?: null]);

            setFlash('success', 'Payment recorded successfully! Receipt: ' . $receiptNumber);
            redirect(url('modules/payments/index.php'));
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to record payment: ' . $e->getMessage();
        }
    } else {
        if ($data['tenant_id']) {
            $stmt = $db->prepare("SELECT t.*, h.house_number, h.rent_amount FROM tenants t LEFT JOIN houses h ON t.house_id = h.house_id WHERE t.tenant_id = ?");
            $stmt->execute([$data['tenant_id']]);
            $selectedTenant = $stmt->fetch();
        }
    }
}

$pageTitle = 'Record Payment';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-cash"></i> Record New Payment</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/payments/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span><i class="bi bi-credit-card" style="color:var(--accent)"></i> Payment Details</span>
    </div>
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label class="form-label">Select Tenant <span style="color:var(--danger)">*</span></label>
                    <select class="form-select <?= isset($errors['tenant_id']) ? 'is-invalid' : '' ?>" name="tenant_id" required onchange="this.form.submit()">
                        <option value="">-- Select Tenant --</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= $tenant['tenant_id'] ?>" <?= $data['tenant_id'] == $tenant['tenant_id'] ? 'selected' : '' ?>>
                                <?= sanitize($tenant['name']) ?> (<?= sanitize($tenant['id_number']) ?>) - <?= sanitize($tenant['house_number'] ?? 'Unassigned') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tenant_id'])): ?>
                        <div class="invalid-feedback"><?= $errors['tenant_id'] ?></div>
                    <?php endif; ?>
                </div>
                <div></div>
            </div>

            <?php if ($selectedTenant): ?>
            <div class="alert alert-info" style="margin-bottom:20px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <strong><i class="bi bi-person"></i> Tenant:</strong> <?= sanitize($selectedTenant['name']) ?><br>
                        <strong><i class="bi bi-house"></i> House:</strong> <?= sanitize($selectedTenant['house_number'] ?? 'Unassigned') ?>
                    </div>
                    <div>
                        <strong><i class="bi bi-cash"></i> Monthly Rent:</strong> <?= $selectedTenant['rent_amount'] ? formatCurrency($selectedTenant['rent_amount']) : 'N/A' ?><br>
                        <strong><i class="bi bi-wallet2"></i> Balance:</strong>
                        <?php
                        $balance = 0;
                        if ($selectedTenant['rent_amount']) {
                            $stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE tenant_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
                            $stmt->execute([$data['tenant_id']]);
                            $paid = $stmt->fetchColumn();
                            $balance = $selectedTenant['rent_amount'] - $paid;
                        }
                        ?>
                        <span style="color:<?= $balance > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:600;"><?= $selectedTenant['rent_amount'] ? formatCurrency($balance) : 'N/A' ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label class="form-label">Amount Paid (KES) <span style="color:var(--danger)">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['amount_paid']) ? 'is-invalid' : '' ?>" name="amount_paid" value="<?= sanitize($data['amount_paid']) ?>" min="0" step="0.01" required>
                    <?php if (isset($errors['amount_paid'])): ?>
                        <div class="invalid-feedback"><?= $errors['amount_paid'] ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="form-label">Payment Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['payment_date']) ? 'is-invalid' : '' ?>" name="payment_date" value="<?= sanitize($data['payment_date']) ?>" required>
                    <?php if (isset($errors['payment_date'])): ?>
                        <div class="invalid-feedback"><?= $errors['payment_date'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"><?= sanitize($data['notes']) ?></textarea>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Record Payment</button>
                <a href="<?= url('modules/payments/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
