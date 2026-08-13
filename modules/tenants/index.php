<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();

$db = getDB();
$page = (int)($_GET['page'] ?? 1);
$search = getInput('search');

$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (t.name LIKE ? OR t.id_number LIKE ? OR t.contact LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM tenants t LEFT JOIN houses h ON t.house_id = h.house_id $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / PER_PAGE));
$page = min(max(1, $page), $totalPages);
$offset = ($page - 1) * PER_PAGE;

$stmt = $db->prepare("
    SELECT t.*, h.house_number
    FROM tenants t
    LEFT JOIN houses h ON t.house_id = h.house_id
    $where
    ORDER BY t.tenant_id DESC
    LIMIT $offset, " . PER_PAGE
);
$stmt->execute($params);
$tenants = $stmt->fetchAll();

$pageTitle = 'Tenants';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-people-fill"></i> Tenants</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/tenants/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add Tenant
        </a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div class="input-group" style="max-width:400px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search by name, ID number, or contact..." value="<?= sanitize($search) ?>">
            </div>
            <?php if ($search): ?>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($tenants)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h4>No tenants found</h4>
                <p><?= $search ? 'Try a different search term.' : 'Get started by adding your first tenant.' ?></p>
                <?php if (!$search): ?>
                    <a href="<?= url('modules/tenants/create.php') ?>" class="btn btn-primary" style="margin-top:12px">
                        <i class="bi bi-person-plus"></i> Add Tenant
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Contact</th>
                            <th>ID Number</th>
                            <th>House</th>
                            <th style="width:140px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:40px;height:40px;border-radius:6px;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="bi bi-person" style="color:var(--primary);font-size:0.9rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;"><?= sanitize($tenant['name']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">ID: <?= $tenant['tenant_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= sanitize($tenant['contact']) ?></td>
                                <td><code><?= sanitize($tenant['id_number']) ?></code></td>
                                <td>
                                    <?php if ($tenant['house_number']): ?>
                                        <span class="badge badge-success"><?= sanitize($tenant['house_number']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <a href="<?= url('modules/tenants/view.php?id=' . $tenant['tenant_id']) ?>" class="btn btn-icon btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="<?= url('modules/tenants/edit.php?id=' . $tenant['tenant_id']) ?>" class="btn btn-icon btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="<?= url('modules/tenants/delete.php?id=' . $tenant['tenant_id']) ?>" class="btn btn-icon btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash3"></i></a>
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
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
