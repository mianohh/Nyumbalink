<?php
require_once __DIR__ . '/../../includes/core.php';
requireAuth();
requireAdmin();

$db = getDB();
$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(url('modules/auth/register.php'));
    }

    $data = [
        'email' => getInput('email'),
        'password' => $_POST['password'] ?? '',
        'name' => getInput('name'),
        'role' => getInput('role')
    ];

    $requiredErrors = validateRequired(['email' => 'Email', 'name' => 'Full Name'], $data);
    $errors = array_merge($errors, $requiredErrors);

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if (!empty($data['role']) && !in_array($data['role'], ['admin', 'staff'])) {
        $errors['role'] = 'Invalid role selected';
    }

    if (empty($errors['email']) && !empty($data['email'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already exists';
        }
    }

    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['email'], $hashedPassword, $data['name'], $data['role'] ?: 'staff']);

            setFlash('success', 'Staff member created successfully!');
            redirect(url('modules/auth/register.php'));
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to create user: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Staff Member';
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-person-plus"></i> Add Staff Member</h2>
    <div class="page-header-actions">
        <a href="<?= url('modules/dashboard/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<div style="max-width:600px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-person-gear" style="color:var(--accent)"></i> Staff Details</span>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $errors['general'] ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrfField() ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label class="form-label">Email <span style="color:var(--danger)">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= sanitize($data['email'] ?? '') ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Password <span style="color:var(--danger)">*</span></label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" name="password" required minlength="6">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= sanitize($data['name'] ?? '') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="staff" <?= ($data['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= ($data['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <small style="color:var(--text-light);">Staff can manage tenants, houses, and payments. Admin has full access.</small>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Account</button>
                    <a href="<?= url('modules/dashboard/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
