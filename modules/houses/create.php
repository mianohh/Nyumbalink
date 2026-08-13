<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/houses/create.php'));
    }
    
    $data = [
        'house_number' => getInput('house_number'),
        'location' => getInput('location'),
        'rent_amount' => getInput('rent_amount'),
        'status' => getInput('status'),
        'description' => getInput('description')
    ];
    
    $requiredErrors = validateRequired(['house_number' => 'House Number', 'location' => 'Location', 'rent_amount' => 'Rent Amount'], $data);
    $errors = array_merge($errors, $requiredErrors);
    
    if (!empty($data['rent_amount']) && !validateAmount($data['rent_amount'])) {
        $errors['rent_amount'] = 'Please enter a valid rent amount';
    }
    
    if (empty($errors['house_number']) && !empty($data['house_number'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM houses WHERE house_number = ?");
        $stmt->execute([$data['house_number']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['house_number'] = 'House number already exists';
        }
    }
    
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors['image'] = 'Only JPEG, PNG, GIF, and WebP images are allowed';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errors['image'] = 'Image size must be less than 5MB';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'house_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadPath = UPLOADS_DIR . '/houses/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $imageName)) {
                $errors['image'] = 'Failed to upload image';
                $imageName = null;
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO houses (house_number, location, rent_amount, status, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['house_number'], $data['location'], $data['rent_amount'], $data['status'] ?: 'available', $data['description'] ?: null, $imageName]);
            setFlash('success', 'House added successfully!');
            redirect(url('modules/houses/index.php'));
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to add house: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add House';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-house-add"></i> Add New House</h2>
    <a href="<?= url('modules/houses/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $errors['general'] ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label for="house_number" class="form-label">House Number <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['house_number']) ? 'is-invalid' : '' ?>" id="house_number" name="house_number" value="<?= sanitize($data['house_number'] ?? '') ?>" placeholder="e.g. A1, B2, 101" required>
                    <?php if (isset($errors['house_number'])): ?>
                        <div class="invalid-feedback"><?= $errors['house_number'] ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="location" class="form-label">Location <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" id="location" name="location" value="<?= sanitize($data['location'] ?? '') ?>" placeholder="e.g. Nairobi, Westlands" required>
                    <?php if (isset($errors['location'])): ?>
                        <div class="invalid-feedback"><?= $errors['location'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label for="rent_amount" class="form-label">Rent Amount (KES) <span style="color:var(--danger)">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['rent_amount']) ? 'is-invalid' : '' ?>" id="rent_amount" name="rent_amount" value="<?= sanitize($data['rent_amount'] ?? '') ?>" min="0" step="0.01" required>
                    <?php if (isset($errors['rent_amount'])): ?>
                        <div class="invalid-feedback"><?= $errors['rent_amount'] ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="available" <?= ($data['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="occupied" <?= ($data['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="maintenance" <?= ($data['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom:16px;">
                <label for="image" class="form-label">House Image</label>
                <input type="file" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" id="image" name="image" accept="image/*">
                <?php if (isset($errors['image'])): ?>
                    <div class="invalid-feedback"><?= $errors['image'] ?></div>
                <?php endif; ?>
                <div class="form-text">JPEG, PNG, GIF, or WebP — Max 5MB</div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional description of the property..."><?= sanitize($data['description'] ?? '') ?></textarea>
            </div>
            
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Add House</button>
                <a href="<?= url('modules/houses/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
