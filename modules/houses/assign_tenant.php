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

// Verify house exists and is available
$stmt = $db->prepare("SELECT * FROM houses WHERE house_id = ?");
$stmt->execute([$houseId]);
$house = $stmt->fetch();

if (!$house) {
    setFlash('error', 'House not found.');
    redirect(url('modules/houses/index.php'));
}

if ($house['status'] !== 'available') {
    setFlash('error', 'This house is not available for assignment.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
}

// Verify tenant exists and is not assigned
$stmt = $db->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
}

if ($tenant['house_id']) {
    setFlash('error', 'This tenant is already assigned to a house.');
    redirect(url('modules/houses/view.php?id=' . $houseId));
}

try {
    // Assign tenant to house
    $stmt = $db->prepare("UPDATE tenants SET house_id = ? WHERE tenant_id = ?");
    $stmt->execute([$houseId, $tenantId]);
    
    // Update house status to occupied
    $stmt = $db->prepare("UPDATE houses SET status = 'occupied' WHERE house_id = ?");
    $stmt->execute([$houseId]);
    
    setFlash('success', 'Tenant ' . $tenant['name'] . ' has been assigned to house ' . $house['house_number'] . ' successfully!');
    redirect(url('modules/houses/view.php?id=' . $houseId));
} catch (PDOException $e) {
    setFlash('error', 'Failed to assign tenant: ' . $e->getMessage());
    redirect(url('modules/houses/view.php?id=' . $houseId));
}
