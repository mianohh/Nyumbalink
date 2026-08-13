<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$tenantId = (int)($_GET['id'] ?? 0);

if (!$tenantId) {
    setFlash('error', 'Invalid tenant ID.');
    redirect(url('modules/tenants/index.php'));
}

// Get current tenant data
$stmt = $db->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    setFlash('error', 'Tenant not found.');
    redirect(url('modules/tenants/index.php'));
}

$errors = [];
$data = $tenant;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/tenants/edit.php?id=' . $tenantId));
    }
    
    $data = [
        'name' => getInput('name'),
        'contact' => getInput('contact'),
        'id_number' => getInput('id_number'),
        'house_id' => getInput('house_id') ?: null
    ];
    
    // Validation
    $requiredErrors = validateRequired(['name' => 'Name', 'contact' => 'Contact', 'id_number' => 'ID Number'], $data);
    $errors = array_merge($errors, $requiredErrors);
    
    if (!empty($data['contact']) && !validatePhone($data['contact'])) {
        $errors['contact'] = 'Invalid phone number format';
    }
    
    if (!empty($data['id_number']) && !validateIDNumber($data['id_number'])) {
        $errors['id_number'] = 'ID number must be 6-15 alphanumeric characters';
    }
    
    // Check unique ID number (excluding current tenant)
    if (empty($errors['id_number']) && !empty($data['id_number'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE id_number = ? AND tenant_id != ?");
        $stmt->execute([$data['id_number'], $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            $errors['id_number'] = 'ID number already exists';
        }
    }
    
    // If no errors, update
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE tenants SET name = ?, contact = ?, id_number = ?, house_id = ? WHERE tenant_id = ?");
            $stmt->execute([$data['name'], $data['contact'], $data['id_number'], $data['house_id'], $tenantId]);
            
            // Update house statuses
            if ($data['house_id'] != $tenant['house_id']) {
                // Mark old house as available
                if ($tenant['house_id']) {
                    $db->prepare("UPDATE houses SET status = 'available' WHERE house_id = ?")->execute([$tenant['house_id']]);
                }
                // Mark new house as occupied
                if ($data['house_id']) {
                    $db->prepare("UPDATE houses SET status = 'occupied' WHERE house_id = ?")->execute([$data['house_id']]);
                }
            }
            
            setFlash('success', 'Tenant updated successfully!');
            redirect(url('modules/tenants/view.php?id=' . $tenantId));
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to update tenant: ' . $e->getMessage();
        }
    }
}

// Get available houses (including current house)
$houses = $db->query("SELECT house_id, house_number, location, rent_amount FROM houses WHERE status = 'available' OR house_id = " . $tenant['house_id'] . " ORDER BY house_number")->fetchAll();

$pageTitle = 'Edit Tenant';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-pencil-square"></i> Edit Tenant: <?= sanitize($tenant['name']) ?></h2>
    <a href="<?= url('modules/tenants/view.php?id=' . $tenantId) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $errors['general'] ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label for="name" class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= sanitize($data['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= $errors['name'] ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="id_number" class="form-label">ID Number <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['id_number']) ? 'is-invalid' : '' ?>" id="id_number" name="id_number" value="<?= sanitize($data['id_number']) ?>" required>
                    <?php if (isset($errors['id_number'])): ?>
                        <div class="invalid-feedback"><?= $errors['id_number'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label for="contact" class="form-label">Contact (Phone) <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['contact']) ? 'is-invalid' : '' ?>" id="contact" name="contact" value="<?= sanitize($data['contact']) ?>" required>
                    <?php if (isset($errors['contact'])): ?>
                        <div class="invalid-feedback"><?= $errors['contact'] ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="house_id" class="form-label">House Assignment</label>
                    <select class="form-select" id="house_id" name="house_id">
                        <option value="">-- No House --</option>
                        <?php foreach ($houses as $house): ?>
                            <option value="<?= $house['house_id'] ?>" <?= $data['house_id'] == $house['house_id'] ? 'selected' : '' ?>>
                                <?= sanitize($house['house_number']) ?> - <?= sanitize($house['location']) ?> (<?= formatCurrency($house['rent_amount']) ?>/month)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Tenant</button>
                <a href="<?= url('modules/tenants/view.php?id=' . $tenantId) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
