<?php
require_once __DIR__ . '/../config.php';
require_login();

$appId = (int) ($_GET['id'] ?? 0);
$application = fetch_application($appId);

if (!$application) {
    set_flash('error', 'Basvuru bulunamadi.');
    redirect('applications.php');
}

$user = current_user();
$allowed = false;

if ($user['role'] === ROLE_STUDENT && $application['user_id'] === $user['id']) {
    $allowed = true;
} elseif (in_array($user['role'], [ROLE_CLERK, ROLE_MANAGER, ROLE_EXECUTIVE], true)) {
    $allowed = true;
} elseif (
    $user['role'] === ROLE_CHAIR
    && $user['department'] === $application['target_department']
    && $application['status'] === APPLICATION_STATUS_APPROVED
) {
    $allowed = true;
}

if (!$allowed) {
    set_flash('error', 'Bu kayda erisim yetkiniz yok.');
    redirect('applications.php');
}

$documents = fetch_documents($appId);

$stmt = db()->prepare('SELECT l.*, u.full_name FROM application_status_logs l LEFT JOIN users u ON l.actor_id = u.id WHERE l.application_id = ? ORDER BY l.created_at');
$stmt->bind_param('i', $appId);
$stmt->execute();
$statusLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Basvuru #' . $appId;
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h2>Basvuru #<?php echo e($application['id']); ?></h2>
                <p>Ogrenci: <?php echo e($application['student_first_name'] . ' ' . $application['student_last_name']); ?></p>
            </div>
            <div>
                <span class="badge <?php echo e(status_badge_class($application['status'])); ?>"><?php echo e(APPLICATION_STATUS_LABELS[$application['status']] ?? $application['status']); ?></span>
                <a class="btn btn-primary" href="download_application.php?id=<?php echo e($application['id']); ?>">Basvuru Belgesi</a>
            </div>
        </div>
        <div class="form-row">
            <div>
                <h3>Kaynak Bilgileri</h3>
                <p><?php echo e($application['current_school']); ?></p>
                <p><?php echo e($application['current_faculty']); ?> · <?php echo e($application['current_department']); ?></p>
                <p>Ogretim: <?php echo e($application['current_instruction']); ?> · <?php echo e($application['completed_semester']); ?>. yariyil</p>
            </div>
            <div>
                <h3>Hedef Bilgileri</h3>
                <p><?php echo e($application['target_faculty']); ?></p>
                <p><?php echo e($application['target_department']); ?></p>
                <p>Ogretim: <?php echo e($application['target_instruction']); ?></p>
            </div>
        </div>
        <div class="form-row">
            <div>
                <h3>Iletisim</h3>
                <p><?php echo e($application['student_address']); ?></p>
                <p>Tel: <?php echo e($application['student_phone']); ?></p>
            </div>
            <div>
                <h3>Ogrenci No</h3>
                <p><?php echo e($application['student_number']); ?></p>
            </div>
        </div>
    </section>

    <section class="card" style="margin-top: 2rem;">
        <h3>Belgeler</h3>
        <ul>
            <?php foreach ($documents as $document): ?>
                <li>
                    <?php echo e(DOCUMENT_REQUIREMENTS[$document['doc_type']] ?? $document['doc_type']); ?> -
                    <a href="download_document.php?id=<?php echo e($document['id']); ?>">Indir</a>
                </li>
            <?php endforeach; ?>
            <?php if (empty($documents)): ?>
                <li>Yuklenmis belge yok.</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="card" style="margin-top: 2rem;">
        <h3>Durum Guncellemeleri</h3>
        <ul>
            <?php foreach ($statusLogs as $log): ?>
                <li>
                    <strong><?php echo e(APPLICATION_STATUS_LABELS[$log['status']] ?? $log['status']); ?></strong> ·
                    <?php echo e($log['full_name'] ?? 'Sistem'); ?> ·
                    <?php echo e($log['created_at']); ?>
                    <?php if ($log['note']): ?>
                        <br><small><?php echo e($log['note']); ?></small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (empty($statusLogs)): ?>
                <li>Kayit yok.</li>
            <?php endif; ?>
        </ul>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
