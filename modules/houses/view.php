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

$stmt = $db->prepare("SELECT * FROM tenants WHERE house_id = ?");
$stmt->execute([$houseId]);
$tenant = $stmt->fetch();

$stmt = $db->prepare("
    SELECT p.*, t.name as tenant_name 
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    WHERE t.house_id = ? 
    ORDER BY p.payment_date DESC 
    LIMIT 10
");
$stmt->execute([$houseId]);
$payments = $stmt->fetchAll();

$pageTitle = 'House Details';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-house-door-fill"></i> House <?= sanitize($house['house_number']) ?></h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/houses/edit.php?id=' . $houseId) ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= url('modules/houses/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <!-- House Details -->
    <div class="card">
        <div class="card-header"><span><i class="bi bi-info-circle" style="color:var(--accent)"></i> Property Details</span></div>
        <div class="card-body">
            <?php if ($house['image']): ?>
                <div style="margin-bottom:16px;">
                    <img src="<?= url('uploads/houses/' . $house['image']) ?>" alt="House" style="width:100%;height:200px;object-fit:cover;border-radius:8px;">
                </div>
            <?php else: ?>
                <div style="margin-bottom:16px;text-align:center;padding:32px;background:var(--surface);border-radius:8px;">
                    <i class="bi bi-house-door" style="font-size:2.5rem;color:var(--text-muted);opacity:0.5;"></i>
                    <p style="margin-top:8px;color:var(--text-muted);font-size:0.85rem;">No image uploaded</p>
                </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <div class="detail-label">House Number</div>
                <div class="detail-value"><?= sanitize($house['house_number']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Location</div>
                <div class="detail-value"><?= sanitize($house['location']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Rent Amount</div>
                <div class="detail-value" style="color:var(--success);font-weight:700;font-size:1.1rem;"><?= formatCurrency($house['rent_amount']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php
                    $badgeClass = match($house['status']) {
                        'available' => 'badge-success',
                        'occupied' => 'badge-primary',
                        'maintenance' => 'badge-warning',
                        default => 'badge-secondary'
                    };
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($house['status']) ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Description</div>
                <div class="detail-value"><?= nl2br(sanitize($house['description'] ?? 'No description')) ?></div>
            </div>
            <div class="detail-row" style="border-bottom:none;">
                <div class="detail-label">Registered</div>
                <div class="detail-value" style="color:var(--text-muted)"><?= formatDateTime($house['created_at']) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Tenant & Actions -->
    <div class="card">
        <div class="card-header"><span><i class="bi bi-person" style="color:var(--success)"></i> Current Tenant</span></div>
        <div class="card-body">
            <?php if ($tenant): ?>
                <div class="detail-row">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><strong><?= sanitize($tenant['name']) ?></strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Contact</div>
                    <div class="detail-value"><?= sanitize($tenant['contact']) ?></div>
                </div>
                <div class="detail-row" style="border-bottom:none;">
                    <div class="detail-label">ID Number</div>
                    <div class="detail-value"><code><?= sanitize($tenant['id_number']) ?></code></div>
                </div>
                
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:20px;">
                    <a href="<?= url('modules/tenants/view.php?id=' . $tenant['tenant_id']) ?>" class="btn btn-info" style="width:100%;justify-content:center;">
                        <i class="bi bi-person-lines-fill"></i> View Tenant
                    </a>
                    <a href="<?= url('modules/payments/create.php?tenant_id=' . $tenant['tenant_id']) ?>" class="btn btn-success" style="width:100%;justify-content:center;">
                        <i class="bi bi-cash-stack"></i> Record Payment
                    </a>
                    <form method="POST" action="<?= url('modules/houses/unassign_tenant.php') ?>" onsubmit="return confirm('Unassign this tenant? The house will become available.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="house_id" value="<?= $houseId ?>">
                        <input type="hidden" name="tenant_id" value="<?= $tenant['tenant_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" style="width:100%;justify-content:center;">
                            <i class="bi bi-person-dash"></i> Unassign Tenant
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:24px;">
                    <i class="bi bi-person-x"></i>
                    <h4>No tenant assigned</h4>
                    <p>This house is currently vacant.</p>
                    
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
                        <a href="<?= url('modules/tenants/create.php?house_id=' . $houseId) ?>" class="btn btn-primary" style="width:100%;justify-content:center;">
                            <i class="bi bi-person-plus"></i> Add New Tenant
                        </a>
                        <?php
                        $availableTenants = $db->query("SELECT tenant_id, name, id_number FROM tenants WHERE house_id IS NULL ORDER BY name")->fetchAll();
                        if (!empty($availableTenants)): ?>
                            <form method="POST" action="<?= url('modules/houses/assign_tenant.php') ?>" style="margin-top:8px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="house_id" value="<?= $houseId ?>">
                                <div class="input-group">
                                    <select class="form-select" name="tenant_id" required>
                                        <option value="">-- Select Tenant --</option>
                                        <?php foreach ($availableTenants as $t): ?>
                                            <option value="<?= $t['tenant_id'] ?>"><?= sanitize($t['name']) ?> (<?= sanitize($t['id_number']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-success"><i class="bi bi-person-check"></i> Assign</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clock-history" style="color:var(--info)"></i> Payment History</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-wallet2"></i>
                <h4>No payments recorded</h4>
                <p>Payment history for this house will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Tenant</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><code><?= sanitize($payment['receipt_number']) ?></code></td>
                                <td><strong><?= sanitize($payment['tenant_name']) ?></strong></td>
                                <td style="color:var(--success);font-weight:700;"><?= formatCurrency($payment['amount_paid']) ?></td>
                                <td style="color:var(--text-muted)"><?= formatDate($payment['payment_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
