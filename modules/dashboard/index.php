<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();

// Get dashboard stats
$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM tenants");
$stats['total_tenants'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM houses");
$stats['total_houses'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM houses WHERE status = 'available'");
$stats['available_houses'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM houses WHERE status = 'occupied'");
$stats['occupied_houses'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
$stats['month_revenue'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments");
$stats['total_revenue'] = $stmt->fetchColumn();

// Recent payments
$stmt = $db->query("
    SELECT p.*, t.name as tenant_name, h.house_number 
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    LEFT JOIN houses h ON t.house_id = h.house_id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$recent_payments = $stmt->fetchAll();

// Chart data: monthly payments (last 6 months)
$monthly_payments = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $month_label = date('M', strtotime("-{$i} months"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthly_payments[] = ['label' => $month_label, 'total' => (float)$stmt->fetchColumn()];
}

// Chart data: houses by status
$stmt = $db->query("SELECT status, COUNT(*) as count FROM houses GROUP BY status");
$house_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Dashboard';
include __DIR__ . '/../../includes/header.php';
?>

<!-- Stat Cards -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Tenants</div>
            <div class="stat-value"><?= $stats['total_tenants'] ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-house-door-fill"></i></div>
        <div class="stat-info">
            <div class="stat-label">Available Houses</div>
            <div class="stat-value"><?= $stats['available_houses'] ?> <span style="font-size:0.8rem;font-weight:500;color:var(--text-muted)">/ <?= $stats['total_houses'] ?></span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <div class="stat-label">This Month</div>
            <div class="stat-value"><?= formatCurrency($stats['month_revenue']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($stats['total_revenue']) ?></div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span><i class="bi bi-lightning-charge-fill" style="color:var(--warning)"></i> Quick Actions</span>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="<?= url('modules/tenants/create.php') ?>" class="quick-action">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add Tenant</span>
            </a>
            <a href="<?= url('modules/houses/create.php') ?>" class="quick-action">
                <i class="bi bi-house-add-fill"></i>
                <span>Add House</span>
            </a>
            <a href="<?= url('modules/payments/create.php') ?>" class="quick-action">
                <i class="bi bi-cash-stack"></i>
                <span>Record Payment</span>
            </a>
            <a href="<?= url('modules/reports/index.php') ?>" class="quick-action">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>View Reports</span>
            </a>
            <a href="<?= url('modules/tenants/agreements.php') ?>" class="quick-action">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Agreements</span>
            </a>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:24px;">
    <!-- Monthly Payments Chart -->
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-bar-chart-line" style="color:var(--accent)"></i> Monthly Payments</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="paymentsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- House Status Chart -->
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-pie-chart" style="color:var(--success)"></i> House Status</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="houseStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clock-history" style="color:var(--info)"></i> Recent Payments</span>
        <a href="<?= url('modules/payments/index.php') ?>" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($recent_payments)): ?>
            <div class="empty-state">
                <i class="bi bi-wallet2"></i>
                <h4>No payments yet</h4>
                <p>Payment records will appear here once tenants start paying.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Tenant</th>
                            <th>House</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td><code><?= sanitize($payment['receipt_number']) ?></code></td>
                                <td><strong><?= sanitize($payment['tenant_name']) ?></strong></td>
                                <td><?= sanitize($payment['house_number'] ?? 'N/A') ?></td>
                                <td><span style="color:var(--success);font-weight:700;"><?= formatCurrency($payment['amount_paid']) ?></span></td>
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

<script>
new Chart(document.getElementById('paymentsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthly_payments, 'label')) ?>,
        datasets: [{
            label: 'Payments (KES)',
            data: <?= json_encode(array_column($monthly_payments, 'total')) ?>,
            backgroundColor: 'rgba(14, 165, 233, 0.8)',
            borderColor: '#0ea5e9',
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('houseStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Available', 'Occupied', 'Maintenance'],
        datasets: [{
            data: [
                <?= $house_status['available'] ?? 0 ?>,
                <?= $house_status['occupied'] ?? 0 ?>,
                <?= $house_status['maintenance'] ?? 0 ?>
            ],
            backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b'],
            borderWidth: 0,
            spacing: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } }
        }
    }
});
</script>
