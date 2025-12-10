<?php
require_once __DIR__ . '/../config.php';
require_roles(ROLE_MANAGER, ROLE_EXECUTIVE);

$currentUser = current_user();
$canAssignExecutive = user_has_role(ROLE_EXECUTIVE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['mode'] ?? 'update';

    if ($action === 'create' && $canAssignExecutive) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? ROLE_STUDENT;
        $status = $_POST['status'] ?? USER_STATUS_PENDING;
        $password = $_POST['password'] ?? '';

        if (!$fullName || !$email || !$password) {
            set_flash('error', 'Ad, email ve sifre zorunludur.');
        } elseif (!in_array($status, [USER_STATUS_PENDING, USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
            set_flash('error', 'Gecersiz durum.');
        } elseif (!array_key_exists($role, ROLE_LABELS)) {
            set_flash('error', 'Gecersiz rol secimi.');
        } else {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($exists) {
                set_flash('error', 'Bu email zaten kayitli.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, department, role, status, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssss', $fullName, $email, $phone, $department, $role, $status, $hash);
                $stmt->execute();
                $stmt->close();
                set_flash('success', 'Yeni kullanici olusturuldu.');
            }
        }
    } elseif ($action === 'delete_user' && $canAssignExecutive && isset($_POST['user_id'])) {
        $targetId = (int) $_POST['user_id'];
        if ($targetId === ($currentUser['id'] ?? null)) {
            set_flash('error', 'Kendi hesabinizi silemezsiniz.');
        } else {
            $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            $stmt->close();
            set_flash('warning', 'Kullanici silindi.');
        }
    } elseif (isset($_POST['user_id'])) {
        $targetId = (int) $_POST['user_id'];
        $status = $_POST['status'] ?? USER_STATUS_PENDING;
        $role = $_POST['role'] ?? ROLE_STUDENT;
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');

        $roleWhitelist = $canAssignExecutive
            ? array_keys(ROLE_LABELS)
            : [ROLE_STUDENT, ROLE_CLERK, ROLE_CHAIR, ROLE_MANAGER];

        if (!in_array($role, $roleWhitelist, true)) {
            set_flash('error', 'Gecersiz rol secimi.');
        } elseif (!in_array($status, [USER_STATUS_PENDING, USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
            set_flash('error', 'Gecersiz durum.');
        } elseif ($canAssignExecutive && (!$fullName || !$email)) {
            set_flash('error', 'Ad ve email zorunludur.');
        } else {
            if ($canAssignExecutive) {
                $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                $stmt->bind_param('si', $email, $targetId);
                $stmt->execute();
                $emailConflict = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($emailConflict) {
                    set_flash('error', 'Bu email baska bir hesaba ait.');
                    redirect('manage_users.php');
                }

                $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, department = ?, role = ?, status = ? WHERE id = ?');
                $stmt->bind_param('sssssi', $fullName, $email, $department, $role, $status, $targetId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
                $stmt->bind_param('ssi', $role, $status, $targetId);
                $stmt->execute();
                $stmt->close();
            }

            set_flash('success', 'Kullanici guncellendi.');
        }
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
    <?php if ($canAssignExecutive): ?>
        <section class="card form-card">
            <h2>Yeni Kullanici Ekle</h2>
            <form method="post">
                <input type="hidden" name="mode" value="create">
                <div class="form-row">
                    <label>Ad Soyad
                        <input type="text" name="full_name" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Telefon
                        <input type="tel" name="phone">
                    </label>
                    <label>Birim / Bolum
                        <input type="text" name="department">
                    </label>
                </div>
                <div class="form-row">
                    <label>Rol
                        <select name="role" required>
                            <?php foreach (ROLE_LABELS as $key => $label): ?>
                                <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Durum
                        <select name="status" required>
                            <option value="pending">Beklemede</option>
                            <option value="active" selected>Aktif</option>
                            <option value="disabled">Pasif</option>
                        </select>
                    </label>
                </div>
                <div class="form-row">
                    <label>Sifre
                        <input type="password" name="password" required>
                    </label>
                </div>
                <button class="btn btn-primary" type="submit">Kullanici Olustur</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="table-wrapper">
        <h2>Kullanici Hesaplari</h2>
        <table>
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th>Birim</th>
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
                        <td><?php echo e($row['department'] ?: '—'); ?></td>
                        <td><?php echo e(ROLE_LABELS[$row['role']] ?? $row['role']); ?></td>
                        <td><?php echo e($row['status']); ?></td>
                        <td>
                            <form method="post" class="form-inline">
                                <input type="hidden" name="mode" value="update_user">
                                <input type="hidden" name="user_id" value="<?php echo e($row['id']); ?>">
                                <?php if ($canAssignExecutive): ?>
                                    <input type="text" name="full_name" value="<?php echo e($row['full_name']); ?>" placeholder="Ad Soyad" required>
                                    <input type="email" name="email" value="<?php echo e($row['email']); ?>" placeholder="Email" required>
                                    <input type="text" name="department" value="<?php echo e($row['department']); ?>" placeholder="Birim">
                                <?php else: ?>
                                    <input type="hidden" name="full_name" value="<?php echo e($row['full_name']); ?>">
                                    <input type="hidden" name="email" value="<?php echo e($row['email']); ?>">
                                    <input type="hidden" name="department" value="<?php echo e($row['department']); ?>">
                                <?php endif; ?>
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
                            <?php if ($canAssignExecutive && $row['id'] !== ($currentUser['id'] ?? null)): ?>
                                <form method="post" onsubmit="return confirm('Kullanici kalici olarak silinecek. Devam edilsin mi?');" style="margin-top:0.5rem;">
                                    <input type="hidden" name="mode" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo e($row['id']); ?>">
                                    <button class="btn btn-danger" type="submit">Sil</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
