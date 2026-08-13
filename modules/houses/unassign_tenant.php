<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(url('modules/houses/index.php'));
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    redirect(url('modules/houses/index.php'));
}

$houseId = (int)($_POST['house_id'] ?? 0);
$tenantId = (int)($_POST['tenant_id'] ?? 0);

if (!$houseId || !$tenantId) {
    setFlash('error', 'Invalid house or tenant ID.');
    redirect(url('modules/houses/index.php'));
}

// Verify house exists
$stmt = $db->prepare("SELECT * FROM houses WHERE house_id = ?");
$stmt->execute([$houseId]);
$house = $stmt->fetch();

if (!$house) {
    setFlash('error', 'House not found.');
    redirect(url('modules/houses/index.php'));
}

// Verify tenant exists and is assigned to this house
$stmt = $db->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
}

if ($tenant['house_id'] != $houseId) {
    setFlash('error', 'This tenant is not assigned to this house.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
}

try {
    // Unassign tenant from house
    $stmt = $db->prepare("UPDATE tenants SET house_id = NULL WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    
    // Update house status to available
    $stmt = $db->prepare("UPDATE houses SET status = 'available' WHERE house_id = ?");
    $stmt->execute([$houseId]);
    
    setFlash('success', 'Tenant ' . $tenant['name'] . ' has been unassigned from house ' . $house['house_number'] . '.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
} catch (PDOException $e) {
    setFlash('error', 'Failed to unassign tenant: ' . $e->getMessage());
    redirect(url('modules/houses/view.php?id=' . $houseId));
}
