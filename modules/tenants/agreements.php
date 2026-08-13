<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$tenantId = (int)($_GET['tenant_id'] ?? 0);
$action = getInput('action', 'list');

// Get tenant info if tenant_id provided
$tenant = null;
if ($tenantId) {
    $stmt = $db->prepare("
        SELECT t.*, h.house_number, h.location, h.rent_amount 
        FROM tenants t 
        LEFT JOIN houses h ON t.house_id = h.house_id 
        WHERE t.tenant_id = ?
    ");
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch();
}

// Handle new agreement creation
$errors = [];
$data = [];

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/tenants/agreements.php?tenant_id=' . $tenantId));
    }
    
    $data = [
        'tenant_id' => $tenantId,
        'house_id' => getInput('house_id'),
        'start_date' => getInput('start_date'),
        'end_date' => getInput('end_date'),
        'duration_months' => getInput('duration_months'),
        'monthly_rent' => getInput('monthly_rent')
    ];
    
    // Validation
    $requiredErrors = validateRequired([
        'house_id' => 'House',
        'start_date' => 'Start Date',
        'duration_months' => 'Duration',
        'monthly_rent' => 'Monthly Rent'
    ], $data);
    $errors = array_merge($errors, $requiredErrors);
    
    if (!empty($data['start_date']) && !validateDate($data['start_date'])) {
        $errors['start_date'] = 'Invalid date format';
    }
    
    if (!empty($data['duration_months']) && (!is_numeric($data['duration_months']) || $data['duration_months'] < 1)) {
        $errors['duration_months'] = 'Duration must be at least 1 month';
    }
    
    if (!empty($data['monthly_rent']) && !validateAmount($data['monthly_rent'])) {
        $errors['monthly_rent'] = 'Invalid rent amount';
    }
    
    if (empty($errors)) {
        try {
            // Calculate end date if not provided
            $endDate = $data['end_date'] ?: date('Y-m-d', strtotime($data['start_date'] . ' + ' . $data['duration_months'] . ' months'));
            
            $stmt = $db->prepare("INSERT INTO rental_agreements (tenant_id, house_id, start_date, end_date, duration_months, monthly_rent) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['tenant_id'],
                $data['house_id'],
                $data['start_date'],
                $endDate,
                $data['duration_months'],
                $data['monthly_rent']
            ]);
            
            setFlash('success', 'Rental agreement created successfully!');
            redirect(url('modules/tenants/agreements.php?tenant_id=' . $tenantId));
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to create agreement: ' . $e->getMessage();
        }
    }
}

// Get available houses
$houses = $db->query("SELECT house_id, house_number, location, rent_amount FROM houses WHERE status = 'available' ORDER BY house_number")->fetchAll();

// Get agreements for this tenant
$agreements = [];
if ($tenantId) {
    $stmt = $db->prepare("
        SELECT ra.*, h.house_number, h.location 
        FROM rental_agreements ra 
        JOIN houses h ON ra.house_id = h.house_id 
        WHERE ra.tenant_id = ? 
        ORDER BY ra.created_at DESC
    ");
    $stmt->execute([$tenantId]);
    $agreements = $stmt->fetchAll();
}

$pageTitle = 'Rental Agreements';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-file-earmark-text"></i> Rental Agreements</h2>
    <div class="page-header-actions">
        <?php if ($tenant): ?>
            <a href="<?= url('modules/tenants/agreements.php?action=create&tenant_id=' . $tenantId) ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Agreement
            </a>
        <?php endif; ?>
        <a href="<?= url('modules/tenants/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (!$tenant): ?>
    <!-- Select Tenant -->
    <div style="max-width:500px;margin:0 auto;">
        <div class="card">
            <div class="card-header"><span><i class="bi bi-person" style="color:var(--primary)"></i> Select Tenant</span></div>
            <div class="card-body">
                <p style="color:var(--text-muted);margin-bottom:16px;">Select a tenant to view or create rental agreements.</p>
                <form method="GET">
                    <div style="margin-bottom:16px;">
                        <label for="tenant_id" class="form-label">Tenant</label>
                        <select class="form-select" id="tenant_id" name="tenant_id" required>
                            <option value="">-- Select Tenant --</option>
                            <?php
                            $stmt = $db->query("SELECT tenant_id, name, id_number FROM tenants ORDER BY name");
                            $allTenants = $stmt->fetchAll();
                            foreach ($allTenants as $t): ?>
                                <option value="<?= $t['tenant_id'] ?>"><?= sanitize($t['name']) ?> (<?= sanitize($t['id_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View Agreements</button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($action === 'create'): ?>
    <!-- Create New Agreement -->
    <div style="max-width:700px;margin:0 auto;">
        <div class="card">
            <div class="card-header"><span><i class="bi bi-plus-circle" style="color:var(--success)"></i> New Rental Agreement for <?= sanitize($tenant['name']) ?></span></div>
            <div class="card-body">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $errors['general'] ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div style="margin-bottom:16px;">
                        <label for="house_id" class="form-label">House <span style="color:var(--danger)">*</span></label>
                        <select class="form-select <?= isset($errors['house_id']) ? 'is-invalid' : '' ?>" id="house_id" name="house_id" required>
                            <option value="">-- Select House --</option>
                            <?php foreach ($houses as $house): ?>
                                <option value="<?= $house['house_id'] ?>" <?= ($data['house_id'] ?? '') == $house['house_id'] ? 'selected' : '' ?>>
                                    <?= sanitize($house['house_number']) ?> - <?= sanitize($house['location']) ?> (<?= formatCurrency($house['rent_amount']) ?>/month)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['house_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['house_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label for="start_date" class="form-label">Start Date <span style="color:var(--danger)">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>" id="start_date" name="start_date" value="<?= sanitize($data['start_date'] ?? date('Y-m-d')) ?>" required>
                            <?php if (isset($errors['start_date'])): ?>
                                <div class="invalid-feedback"><?= $errors['start_date'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="end_date" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= sanitize($data['end_date'] ?? '') ?>">
                            <div class="form-text">If not set, calculated from start date + duration</div>
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label for="duration_months" class="form-label">Duration (Months) <span style="color:var(--danger)">*</span></label>
                            <input type="number" class="form-control <?= isset($errors['duration_months']) ? 'is-invalid' : '' ?>" id="duration_months" name="duration_months" min="1" value="<?= sanitize($data['duration_months'] ?? '12') ?>" required>
                            <?php if (isset($errors['duration_months'])): ?>
                                <div class="invalid-feedback"><?= $errors['duration_months'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="monthly_rent" class="form-label">Monthly Rent (KES) <span style="color:var(--danger)">*</span></label>
                            <input type="number" class="form-control <?= isset($errors['monthly_rent']) ? 'is-invalid' : '' ?>" id="monthly_rent" name="monthly_rent" min="0" step="0.01" value="<?= sanitize($data['monthly_rent'] ?? $tenant['rent_amount'] ?? '') ?>" required>
                            <?php if (isset($errors['monthly_rent'])): ?>
                                <div class="invalid-feedback"><?= $errors['monthly_rent'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Agreement</button>
                        <a href="<?= url('modules/tenants/agreements.php?tenant_id=' . $tenantId) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- List Agreements -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px;">
        <div class="card" style="border-top:3px solid var(--info);">
            <div class="card-body">
                <div style="color:var(--text-muted);font-size:0.8rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Tenant</div>
                <div style="font-size:1.1rem;font-weight:700;"><?= sanitize($tenant['name']) ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;">ID: <?= sanitize($tenant['id_number']) ?></div>
            </div>
        </div>
        <div class="card" style="border-top:3px solid var(--primary);">
            <div class="card-body">
                <div style="color:var(--text-muted);font-size:0.8rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">House</div>
                <div style="font-size:1.1rem;font-weight:700;"><?= $tenant['house_number'] ? sanitize($tenant['house_number']) . ' - ' . sanitize($tenant['location']) : 'Unassigned' ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;">Current Rent: <?= $tenant['rent_amount'] ? formatCurrency($tenant['rent_amount']) : 'N/A' ?></div>
            </div>
        </div>
        <div class="card" style="border-top:3px solid var(--success);">
            <div class="card-body">
                <div style="color:var(--text-muted);font-size:0.8rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Total Agreements</div>
                <div style="font-size:1.1rem;font-weight:700;"><?= count($agreements) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-file-earmark-text" style="color:var(--primary)"></i> Agreement History</span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($agreements)): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark"></i>
                    <h4>No rental agreements</h4>
                    <p>No rental agreements found for this tenant.</p>
                    <a href="<?= url('modules/tenants/agreements.php?action=create&tenant_id=' . $tenantId) ?>" class="btn btn-primary" style="margin-top:12px">
                        <i class="bi bi-plus-lg"></i> Create First Agreement
                    </a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>House</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Monthly Rent</th>
                                <th>Status</th>
                                <th style="width:60px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agreements as $agreement): ?>
                                <tr>
                                    <td><?= $agreement['agreement_id'] ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?= sanitize($agreement['house_number']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-muted)"><?= sanitize($agreement['location']) ?></div>
                                    </td>
                                    <td style="color:var(--text-muted)"><?= formatDate($agreement['start_date']) ?></td>
                                    <td style="color:var(--text-muted)"><?= $agreement['end_date'] ? formatDate($agreement['end_date']) : 'Ongoing' ?></td>
                                    <td><?= $agreement['duration_months'] ?> months</td>
                                    <td style="color:var(--success);font-weight:700;"><?= formatCurrency($agreement['monthly_rent']) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($agreement['status']) {
                                            'active' => 'badge-success',
                                            'expired' => 'badge-warning',
                                            'terminated' => 'badge-danger',
                                            default => 'badge-warning'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($agreement['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= url('modules/houses/view.php?id=' . $agreement['house_id']) ?>" class="btn btn-icon btn-sm btn-outline-primary" title="View House"><i class="bi bi-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
