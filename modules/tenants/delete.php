<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$tenantId = (int)($_GET['id'] ?? 0);

if (!$tenantId) {
    setFlash('error', 'Invalid tenant ID.');
    redirect(url('modules/tenants/index.php'));
}

// Get tenant
$stmt = $db->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/tenants/index.php'));
}

// Check if tenant has payments
$stmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$paymentCount = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/tenants/delete.php?id=' . $tenantId));
    }
    
    try {
        // Free up house if assigned
        if ($tenant['house_id']) {
            $db->prepare("UPDATE houses SET status = 'available' WHERE house_id = ?")->execute([$tenant['house_id']]);
        }
        
        // Delete tenant (cascade will delete related records)
        $stmt = $db->prepare("DELETE FROM tenants WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        setFlash('success', 'Tenant deleted successfully!');
        redirect(url('modules/tenants/index.php'));
    } catch (PDOException $e) {
        setFlash('error', 'Failed to delete tenant: ' . $e->getMessage());
        redirect(url('modules/tenants/delete.php?id=' . $tenantId));
    }
}

$pageTitle = 'Delete Tenant';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-trash3" style="color:var(--danger)"></i> Delete Tenant</h2>
</div>

<div style="max-width:500px;">
    <div class="card" style="border-top:3px solid var(--danger);">
        <div class="card-body" style="text-align:center;padding:32px;">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--danger-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="bi bi-exclamation-triangle" style="font-size:1.5rem;color:var(--danger)"></i>
            </div>
            <h3 style="margin-bottom:8px;">Delete Tenant?</h3>
            <p style="color:var(--text-muted);margin-bottom:4px;"><?= sanitize($tenant['name']) ?> — <?= sanitize($tenant['id_number']) ?></p>
            <?php if ($paymentCount > 0): ?>
                <p style="color:var(--danger);font-weight:600;margin-bottom:16px;">
                    <i class="bi bi-info-circle"></i> This tenant has <?= $paymentCount ?> payment record(s). Payments will be retained for historical purposes.
                </p>
            <?php else: ?>
                <p style="color:var(--text-muted);margin-bottom:16px;">This action cannot be undone. All associated rental agreements will also be deleted.</p>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <div style="display:flex;gap:8px;justify-content:center;">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Yes, Delete</button>
                    <a href="<?= url('modules/tenants/view.php?id=' . $tenantId) ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
