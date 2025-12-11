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
            if (can_transition_status($user, $application, APPLICATION_STATUS_APPROVED)) {
                $stmt = db()->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
                $newStatus = APPLICATION_STATUS_APPROVED;
                $stmt->bind_param('si', $newStatus, $appId);
                $stmt->execute();
                $stmt->close();
                log_status_change($appId, $newStatus, $user['id'], $note ?: 'Onaylandi');
                set_flash('success', 'Basvuru onaylandi ve bolum baskanina bildirildi.');
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
        } elseif ($action === 'reset' && user_has_role(ROLE_EXECUTIVE)) {
            if ($application['status'] === APPLICATION_STATUS_APPROVED) {
                $stmt = db()->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
                $status = APPLICATION_STATUS_PENDING_CLERK;
                $stmt->bind_param('si', $status, $appId);
                $stmt->execute();
                $stmt->close();
                log_status_change($appId, $status, $user['id'], $note ?: 'Ust yonetici onayi kaldirdi');
                set_flash('info', 'Onay kaldirildi ve basvuru yeniden memur sirasina alindi.');
            } else {
                set_flash('error', 'Sadece onaylanmis basvurular geri alinabilir.');
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

$allowedStatuses = ['all', APPLICATION_STATUS_PENDING_CLERK, APPLICATION_STATUS_APPROVED, APPLICATION_STATUS_REJECTED, APPLICATION_STATUS_DRAFT];
$selectedStatus = $_GET['status'] ?? 'all';
if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

if ($selectedStatus !== 'all') {
    $applications = array_values(array_filter($applications, fn($app) => $app['status'] === $selectedStatus));
}

$summaryTotal = count($applications);
$summaryApproved = array_sum(array_map(fn($app) => $app['status'] === APPLICATION_STATUS_APPROVED ? 1 : 0, $applications));
$summaryDraft = array_sum(array_map(fn($app) => $app['status'] === APPLICATION_STATUS_DRAFT ? 1 : 0, $applications));
$summaryProcessing = max(0, $summaryTotal - ($summaryApproved + $summaryDraft));
?>
<main>
    <section class="page-intro">
        <div>
            <h1 class="page-title">Basvurular</h1>
            <p class="page-subtitle">Yaptiginiz tum basvurulari goruntuleyin, durum guncellemelerini izleyin ve gerekli belgeleri tek yerden kontrol edin.</p>
        </div>
        <?php if ($user['role'] === ROLE_STUDENT): ?>
            <div class="page-intro__actions">
                <a class="btn btn-primary" href="create_application.php">Yeni Basvuru</a>
            </div>
        <?php endif; ?>
    </section>

    <form class="filter-bar" method="get">
        <div class="filter-group">
            <label>Durum</label>
            <select name="status">
                <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>Tumu</option>
                <option value="<?php echo APPLICATION_STATUS_PENDING_CLERK; ?>" <?php echo $selectedStatus === APPLICATION_STATUS_PENDING_CLERK ? 'selected' : ''; ?>>Memur Onayi Bekleyen</option>
                <option value="<?php echo APPLICATION_STATUS_APPROVED; ?>" <?php echo $selectedStatus === APPLICATION_STATUS_APPROVED ? 'selected' : ''; ?>>Onaylanan</option>
                <option value="<?php echo APPLICATION_STATUS_REJECTED; ?>" <?php echo $selectedStatus === APPLICATION_STATUS_REJECTED ? 'selected' : ''; ?>>Reddedilen</option>
                <option value="<?php echo APPLICATION_STATUS_DRAFT; ?>" <?php echo $selectedStatus === APPLICATION_STATUS_DRAFT ? 'selected' : ''; ?>>Taslak</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Yil</label>
            <select name="year" disabled title="Yakinda">
                <option>Tumu</option>
                <option>2025</option>
                <option>2024</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Donem</label>
            <select name="term" disabled title="Yakinda">
                <option>Tumu</option>
                <option>Bahar</option>
                <option>Guz</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Filtrele</button>
            <a class="btn btn-ghost" href="applications.php">Sifirla</a>
        </div>
    </form>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <strong>Henuz basvurunuz bulunmamaktadir.</strong>
            <?php if ($user['role'] === ROLE_STUDENT): ?>
                <p><a class="btn btn-primary" href="create_application.php">Hemen Basvuru Yap</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <section class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ogrenci</th>
                        <th>Kaynak Bolum</th>
                        <th>Hedef Bolum</th>
                        <th>Durum</th>
                        <th>Belgeler</th>
                        <?php if (user_has_role(ROLE_CLERK, ROLE_MANAGER, ROLE_EXECUTIVE)): ?>
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
                            <td><a class="btn btn-ghost" href="view_application.php?id=<?php echo e($application['id']); ?>">Detay</a></td>
                            <?php if (user_has_role(ROLE_CLERK, ROLE_MANAGER, ROLE_EXECUTIVE)): ?>
                                <td>
                                    <?php if ($application['status'] === APPLICATION_STATUS_PENDING_CLERK): ?>
                                        <form method="post" class="actions-inline">
                                            <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                                            <input type="text" name="note" placeholder="Opsiyonel not">
                                            <button class="btn btn-primary" name="action" value="approve">Onayla</button>
                                            <button class="btn btn-danger" name="action" value="reject">Reddet</button>
                                        </form>
                                    <?php elseif (user_has_role(ROLE_EXECUTIVE) && $application['status'] === APPLICATION_STATUS_APPROVED): ?>
                                        <form method="post" class="actions-inline">
                                            <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                                            <input type="text" name="note" placeholder="Sebep">
                                            <button class="btn btn-secondary" name="action" value="reset">Onayi Kaldir</button>
                                        </form>
                                    <?php else: ?>
                                        <span> - </span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>

    <section class="stat-grid">
        <article class="stat-card stat-card--slate">
            <span class="stat-card__label">Toplam</span>
            <span class="stat-card__value"><?php echo $summaryTotal; ?></span>
        </article>
        <article class="stat-card stat-card--blue">
            <span class="stat-card__label">Islemde</span>
            <span class="stat-card__value"><?php echo $summaryProcessing; ?></span>
        </article>
        <article class="stat-card stat-card--green">
            <span class="stat-card__label">Onaylanan</span>
            <span class="stat-card__value"><?php echo $summaryApproved; ?></span>
        </article>
        <article class="stat-card stat-card--yellow">
            <span class="stat-card__label">Taslak</span>
            <span class="stat-card__value"><?php echo $summaryDraft; ?></span>
        </article>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
