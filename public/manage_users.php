<?php
require_once __DIR__ . '/../config.php';
require_roles(ROLE_MANAGER, ROLE_EXECUTIVE);

$canAssignExecutive = user_has_role(ROLE_EXECUTIVE);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $targetId = (int) $_POST['user_id'];
    $status = $_POST['status'] ?? USER_STATUS_PENDING;
    $role = $_POST['role'] ?? ROLE_STUDENT;

    $roleWhitelist = $canAssignExecutive
        ? array_keys(ROLE_LABELS)
        : [ROLE_STUDENT, ROLE_CLERK, ROLE_CHAIR, ROLE_MANAGER];

    if (!in_array($role, $roleWhitelist, true)) {
        set_flash('error', 'Gecersiz rol secimi.');
    } elseif (!in_array($status, [USER_STATUS_PENDING, USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
        set_flash('error', 'Gecersiz durum.');
    } else {
        $stmt = db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
        $stmt->bind_param('ssi', $role, $status, $targetId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Kullanici guncellendi.');
    }

    redirect('manage_users.php');
}

$result = db()->query('SELECT * FROM users ORDER BY created_at DESC');
$users = $result->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Kullanici Yonetimi';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="table-wrapper">
        <h2>Kullanici Hesaplari</h2>
        <table>
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th>Islem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?php echo e($row['full_name']); ?></td>
                        <td><?php echo e($row['email']); ?></td>
                        <td><?php echo e(ROLE_LABELS[$row['role']] ?? $row['role']); ?></td>
                        <td><?php echo e($row['status']); ?></td>
                        <td>
                            <form method="post" class="form-inline">
                                <input type="hidden" name="user_id" value="<?php echo e($row['id']); ?>">
                                <select name="role">
                                    <?php foreach (ROLE_LABELS as $key => $label): ?>
                                        <?php if (!$canAssignExecutive && $key === ROLE_EXECUTIVE) { continue; } ?>
                                        <option value="<?php echo e($key); ?>" <?php echo $row['role'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status">
                                    <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                                    <option value="active" <?php echo $row['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="disabled" <?php echo $row['status'] === 'disabled' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                                <button class="btn btn-secondary" type="submit">Kaydet</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
