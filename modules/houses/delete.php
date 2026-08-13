<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$houseId = (int)($_GET['id'] ?? 0);
if (!$houseId) { setFlash('error', 'Invalid house ID.'); redirect(url('modules/houses/index.php')); }

$stmt = $db->prepare("SELECT * FROM houses WHERE house_id = ?");
$stmt->execute([$houseId]);
$house = $stmt->fetch();
if (!$house) { setFlash('error', 'House not found.'); redirect(url('modules/houses/index.php')); }

$stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE house_id = ?");
$stmt->execute([$houseId]);
$tenantCount = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/houses/delete.php?id=' . $houseId));
    }
    
    try {
        if ($tenantCount > 0) {
            $db->prepare("UPDATE tenants SET house_id = NULL WHERE house_id = ?")->execute([$houseId]);
        }
        $db->prepare("DELETE FROM houses WHERE house_id = ?")->execute([$houseId]);
        setFlash('success', 'House deleted successfully!');
        redirect(url('modules/houses/index.php'));
    } catch (PDOException $e) {
        setFlash('error', 'Failed to delete house.');
        redirect(url('modules/houses/index.php'));
    }
}

$pageTitle = 'Delete House';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-trash3" style="color:var(--danger)"></i> Delete House</h2>
</div>

<div style="max-width:500px;">
    <div class="card" style="border-top:3px solid var(--danger);">
        <div class="card-body" style="text-align:center;padding:32px;">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--danger-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="bi bi-exclamation-triangle" style="font-size:1.5rem;color:var(--danger)"></i>
            </div>
            <h3 style="margin-bottom:8px;">Delete House #<?= sanitize($house['house_number']) ?>?</h3>
            <p style="color:var(--text-muted);margin-bottom:4px;"><?= sanitize($house['location']) ?> — <?= formatCurrency($house['rent_amount']) ?>/mo</p>
            <?php if ($tenantCount > 0): ?>
                <p style="color:var(--danger);font-weight:600;margin-bottom:16px;">
                    <i class="bi bi-info-circle"></i> This house has <?= $tenantCount ?> tenant(s) who will be unassigned.
                </p>
            <?php else: ?>
                <p style="color:var(--text-muted);margin-bottom:16px;">This action cannot be undone.</p>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <div style="display:flex;gap:8px;justify-content:center;">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Yes, Delete</button>
                    <a href="<?= url('modules/houses/view.php?id=' . $houseId) ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
