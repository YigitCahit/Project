<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['application_id'])) {
    $appId = (int) $_POST['application_id'];
    $action = $_POST['action'];
    $note = trim($_POST['note'] ?? '');
    $application = fetch_application($appId);

    if (!$application) {
        set_flash('error', 'Basvuru bulunamadi.');
    } else {
        if ($action === 'approve') {
            $nextStatus = next_status_after_approval($user['role']);
            if ($nextStatus && can_transition_status($user, $application, $nextStatus)) {
                $stmt = db()->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
                $stmt->bind_param('si', $nextStatus, $appId);
                $stmt->execute();
                $stmt->close();
                log_status_change($appId, $nextStatus, $user['id'], $note ?: 'Onaylandi');
                set_flash('success', 'Basvuru durumu guncellendi.');
            } else {
                set_flash('error', 'Bu duruma gecis yetkiniz yok.');
            }
        } elseif ($action === 'reject') {
            if (can_transition_status($user, $application, APPLICATION_STATUS_REJECTED) || user_has_role(ROLE_EXECUTIVE)) {
                $stmt = db()->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
                $status = APPLICATION_STATUS_REJECTED;
                $stmt->bind_param('si', $status, $appId);
                $stmt->execute();
                $stmt->close();
                log_status_change($appId, $status, $user['id'], $note ?: 'Reddedildi');
                set_flash('warning', 'Basvuru reddedildi.');
            } else {
                set_flash('error', 'Reddetme yetkiniz yok.');
            }
        }
    }

    redirect('applications.php');
}

$query = available_applications_query($user);
$stmt = db()->prepare($query);

if ($user['role'] === ROLE_STUDENT) {
    $stmt->bind_param('i', $user['id']);
} elseif ($user['role'] === ROLE_CHAIR) {
    $department = $user['department'] ?? '';
    $stmt->bind_param('s', $department);
}

$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Basvurular';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="table-wrapper">
        <div class="actions" style="justify-content: space-between;">
            <h2>Basvurular</h2>
            <?php if ($user['role'] === ROLE_STUDENT): ?>
                <a class="btn btn-primary" href="create_application.php">Yeni Basvuru</a>
            <?php endif; ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ogrenci</th>
                    <th>Kaynak Bolum</th>
                    <th>Hedef Bolum</th>
                    <th>Durum</th>
                    <th>Belgeler</th>
                    <?php if (user_has_role(ROLE_CLERK, ROLE_CHAIR, ROLE_MANAGER, ROLE_EXECUTIVE)): ?>
                        <th>Islemler</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td>#<?php echo e($application['id']); ?></td>
                        <td><?php echo e($application['student_first_name'] . ' ' . $application['student_last_name']); ?></td>
                        <td><?php echo e($application['current_department']); ?></td>
                        <td><?php echo e($application['target_department']); ?></td>
                        <td><span class="badge <?php echo e(status_badge_class($application['status'])); ?>"><?php echo e(APPLICATION_STATUS_LABELS[$application['status']] ?? $application['status']); ?></span></td>
                        <td><a href="view_application.php?id=<?php echo e($application['id']); ?>">Detay</a></td>
                        <?php if (user_has_role(ROLE_CLERK, ROLE_CHAIR, ROLE_MANAGER, ROLE_EXECUTIVE)): ?>
                            <td>
                                <?php if ($application['status'] !== APPLICATION_STATUS_APPROVED && $application['status'] !== APPLICATION_STATUS_REJECTED): ?>
                                    <form method="post" class="actions-inline">
                                        <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                                        <input type="text" name="note" placeholder="Not" style="max-width: 140px;">
                                        <button class="btn btn-secondary" name="action" value="approve">Onay</button>
                                        <button class="btn" style="background:#fff;color:var(--danger);border:1px solid var(--danger);" name="action" value="reject">Red</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($applications)): ?>
                    <tr><td colspan="7">Gosterilecek kayit yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
