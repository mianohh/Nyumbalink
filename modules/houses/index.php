<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$page = (int)($_GET['page'] ?? 1);
$search = getInput('search');
$status = getInput('status');

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (house_number LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status && in_array($status, ['available', 'occupied', 'maintenance'])) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM houses $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / PER_PAGE));
$page = min(max(1, $page), $totalPages);
$offset = ($page - 1) * PER_PAGE;

$stmt = $db->prepare("SELECT * FROM houses $where ORDER BY house_number ASC LIMIT $offset, " . PER_PAGE);
$stmt->execute($params);
$houses = $stmt->fetchAll();

$pageTitle = 'Houses';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-house-door-fill"></i> Houses</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/houses/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add House
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div class="input-group" style="max-width:320px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search houses..." value="<?= sanitize($search) ?>">
            </div>
            <div class="filter-pills">
                <a href="?search=<?= urlencode($search) ?>" class="filter-pill <?= !$status ? 'active' : '' ?>">All</a>
                <a href="?search=<?= urlencode($search) ?>&status=available" class="filter-pill <?= $status === 'available' ? 'active' : '' ?>">Available</a>
                <a href="?search=<?= urlencode($search) ?>&status=occupied" class="filter-pill <?= $status === 'occupied' ? 'active' : '' ?>">Occupied</a>
                <a href="?search=<?= urlencode($search) ?>&status=maintenance" class="filter-pill <?= $status === 'maintenance' ? 'active' : '' ?>">Maintenance</a>
            </div>
            <?php if ($search || $status): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Houses Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($houses)): ?>
            <div class="empty-state">
                <i class="bi bi-house-door"></i>
                <h4>No houses found</h4>
                <p><?= $search ? 'Try a different search term.' : 'Get started by adding your first house.' ?></p>
                <?php if (!$search): ?>
                    <a href="<?= url('modules/houses/create.php') ?>" class="btn btn-primary" style="margin-top:12px">
                        <i class="bi bi-plus-lg"></i> Add House
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>House</th>
                            <th>Location</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th style="width:120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($houses as $house): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?php if ($house['image']): ?>
                                            <img src="<?= url('uploads/houses/' . $house['image']) ?>" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:40px;height:40px;border-radius:6px;background:var(--surface);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="bi bi-house-door" style="color:var(--text-muted);font-size:0.9rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:600;"><?= sanitize($house['house_number']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">ID: <?= $house['house_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= sanitize($house['location']) ?></td>
                                <td><span style="font-weight:700;color:var(--success)"><?= formatCurrency($house['rent_amount']) ?></span></td>
                                <td>
                                    <?php
                                    $badgeClass = match($house['status']) {
                                        'available' => 'badge-success',
                                        'occupied' => 'badge-primary',
                                        'maintenance' => 'badge-warning',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($house['status']) ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?= url('modules/houses/view.php?id=' . $house['house_id']) ?>" class="btn btn-icon btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="<?= url('modules/houses/edit.php?id=' . $house['house_id']) ?>" class="btn btn-icon btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="<?= url('modules/houses/delete.php?id=' . $house['house_id']) ?>" class="btn btn-icon btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash3"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div style="padding:16px 20px;display:flex;justify-content:center;">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
