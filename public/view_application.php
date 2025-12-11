<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$appId = (int) ($_POST['application_id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!user_has_role(ROLE_EXECUTIVE)) {
        set_flash('error', 'Bu islem icin yetkiniz yok.');
        redirect('view_application.php?id=' . $appId);
    }

    $targetApplication = fetch_application($appId);
    if (!$targetApplication) {
        set_flash('error', 'Basvuru bulunamadi.');
        redirect('applications.php');
    }

    $mode = $_POST['mode'] ?? '';
    if ($mode === 'update_application') {
        $fields = [
            'current_school' => trim($_POST['current_school'] ?? ''),
            'current_faculty' => trim($_POST['current_faculty'] ?? ''),
            'current_department' => trim($_POST['current_department'] ?? ''),
            'student_number' => trim($_POST['student_number'] ?? ''),
            'current_instruction' => map_instruction_label($_POST['current_instruction'] ?? INSTRUCTION_TYPES[0]),
            'completed_semester' => (int) ($_POST['completed_semester'] ?? 0),
            'target_faculty' => trim($_POST['target_faculty'] ?? ''),
            'target_department' => trim($_POST['target_department'] ?? ''),
            'target_instruction' => map_instruction_label($_POST['target_instruction'] ?? INSTRUCTION_TYPES[0]),
            'student_first_name' => trim($_POST['student_first_name'] ?? ''),
            'student_last_name' => trim($_POST['student_last_name'] ?? ''),
            'student_address' => trim($_POST['student_address'] ?? ''),
            'student_phone' => trim($_POST['student_phone'] ?? ''),
        ];

        $required = ['current_school','current_faculty','current_department','student_number','target_faculty','target_department','student_first_name','student_last_name','student_address','student_phone'];
        $missing = array_filter($required, fn($key) => $fields[$key] === '');

        if (!empty($missing)) {
            set_flash('error', 'Lutfen zorunlu alanlari bos birakmayin.');
        } else {
            $stmt = db()->prepare(
                'UPDATE applications SET current_school = ?, current_faculty = ?, current_department = ?, student_number = ?, current_instruction = ?, completed_semester = ?, target_faculty = ?, target_department = ?, target_instruction = ?, student_first_name = ?, student_last_name = ?, student_address = ?, student_phone = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param(
                'sssssisssssssi',
                $fields['current_school'],
                $fields['current_faculty'],
                $fields['current_department'],
                $fields['student_number'],
                $fields['current_instruction'],
                $fields['completed_semester'],
                $fields['target_faculty'],
                $fields['target_department'],
                $fields['target_instruction'],
                $fields['student_first_name'],
                $fields['student_last_name'],
                $fields['student_address'],
                $fields['student_phone'],
                $appId
            );
            $stmt->execute();
            $stmt->close();
            log_status_change($appId, $targetApplication['status'], $user['id'], 'Ust yonetici basvuru verilerini guncelledi.');
            set_flash('success', 'Basvuru bilgileri guncellendi.');
        }
    } elseif ($mode === 'revert_status') {
        if ($targetApplication['status'] !== APPLICATION_STATUS_APPROVED) {
            set_flash('error', 'Sadece onaylanmis kayitlar geri alinabilir.');
        } else {
            $stmt = db()->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
            $status = APPLICATION_STATUS_PENDING_CLERK;
            $stmt->bind_param('si', $status, $appId);
            $stmt->execute();
            $stmt->close();
            log_status_change($appId, $status, $user['id'], 'Ust yonetici onayi kaldirdi.');
            set_flash('info', 'Onay kaldirildi ve basvuru memura geri donduruldu.');
        }
    }

    redirect('view_application.php?id=' . $appId);
}

$application = fetch_application($appId);

if (!$application) {
    set_flash('error', 'Basvuru bulunamadi.');
    redirect('applications.php');
}

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
    <section class="page-intro">
        <div>
            <p class="page-subtitle">Ogrenci: <?php echo e($application['student_first_name'] . ' ' . $application['student_last_name']); ?> - Ogrenci No: <?php echo e($application['student_number']); ?></p>
            <h1 class="page-title">Basvuru #<?php echo e($application['id']); ?></h1>
        </div>
        <div class="page-intro__actions">
            <span class="badge <?php echo e(status_badge_class($application['status'])); ?>" style="justify-content:center;"><?php echo e(APPLICATION_STATUS_LABELS[$application['status']] ?? $application['status']); ?></span>
            <a class="btn btn-primary" href="download_application.php?id=<?php echo e($application['id']); ?>">Basvuru Belgesi</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <span>Program Ozetleri</span>
            <h3>Kaynak ve hedef bilgileri</h3>
        </div>
        <div class="form-row">
            <div>
                <h4>Kaynak Program</h4>
                <p><?php echo e($application['current_school']); ?></p>
                <p><?php echo e($application['current_faculty']); ?> - <?php echo e($application['current_department']); ?></p>
                <p>Ogretim: <?php echo e($application['current_instruction']); ?> - <?php echo e($application['completed_semester']); ?>. yariyil</p>
            </div>
            <div>
                <h4>Hedef Program</h4>
                <p><?php echo e($application['target_faculty']); ?></p>
                <p><?php echo e($application['target_department']); ?></p>
                <p>Ogretim: <?php echo e($application['target_instruction']); ?></p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head panel-head--mint">
            <span>Iletisim</span>
            <h3>Adres ve telefon</h3>
        </div>
        <div class="form-row">
            <div>
                <h4>Adres</h4>
                <p><?php echo e($application['student_address']); ?></p>
            </div>
            <div>
                <h4>Telefon</h4>
                <p><?php echo e($application['student_phone']); ?></p>
            </div>
        </div>
    </section>

    <section class="card">
        <h3 class="card-title">Yuklenen Belgeler</h3>
        <?php if (empty($documents)): ?>
            <div class="empty-state">Yuklenmis belge bulunmamaktadir.</div>
        <?php else: ?>
            <ul class="doc-list">
                <?php foreach ($documents as $document): ?>
                    <li>
                        <strong><?php echo e(DOCUMENT_REQUIREMENTS[$document['doc_type']] ?? $document['doc_type']); ?></strong>
                        <div style="margin-top:0.35rem;">
                            <a class="btn btn-ghost" href="download_document.php?id=<?php echo e($document['id']); ?>">Indir</a>
                            <small style="margin-left:0.5rem;color:var(--slate-500);">Kayit: <?php echo e($document['created_at'] ?? ''); ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3 class="card-title">Durum Guncellemeleri</h3>
        <?php if (empty($statusLogs)): ?>
            <div class="empty-state">Kayit bulunmuyor.</div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($statusLogs as $log): ?>
                    <li>
                        <strong><?php echo e(APPLICATION_STATUS_LABELS[$log['status']] ?? $log['status']); ?></strong>
                        <small> - <?php echo e($log['full_name'] ?? 'Sistem'); ?> - <?php echo e($log['created_at']); ?></small>
                        <?php if ($log['note']): ?>
                            <div style="margin-top:0.25rem;color:var(--slate-500);"><?php echo e($log['note']); ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if (user_has_role(ROLE_EXECUTIVE)): ?>
        <section class="panel">
            <div class="panel-head panel-head--gold">
                <span>Ust Yonetim</span>
                <h3>Basvuruya mudahale edin</h3>
            </div>
            <form method="post">
                <input type="hidden" name="mode" value="update_application">
                <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                <div class="form-row">
                    <label>Kayitli Universite
                        <input type="text" name="current_school" value="<?php echo e($application['current_school']); ?>" required>
                    </label>
                    <label>Fakulte / Yuksekokul
                        <input type="text" name="current_faculty" value="<?php echo e($application['current_faculty']); ?>" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Bolum
                        <input type="text" name="current_department" value="<?php echo e($application['current_department']); ?>" required>
                    </label>
                    <label>Ogrenci No
                        <input type="text" name="student_number" value="<?php echo e($application['student_number']); ?>" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Ogretim
                        <select name="current_instruction">
                            <?php foreach (INSTRUCTION_TYPES as $type): ?>
                                <option value="<?php echo e($type); ?>" <?php echo $application['current_instruction'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Tamamlanan Yariyil
                        <input type="number" name="completed_semester" min="0" max="12" value="<?php echo e($application['completed_semester']); ?>" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Hedef Fakulte
                        <input type="text" name="target_faculty" value="<?php echo e($application['target_faculty']); ?>" required>
                    </label>
                    <label>Hedef Bolum
                        <input type="text" name="target_department" value="<?php echo e($application['target_department']); ?>" required>
                    </label>
                </div>
                <label>Hedef Ogretim
                    <select name="target_instruction">
                        <?php foreach (INSTRUCTION_TYPES as $type): ?>
                            <option value="<?php echo e($type); ?>" <?php echo $application['target_instruction'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-row">
                    <label>Ad
                        <input type="text" name="student_first_name" value="<?php echo e($application['student_first_name']); ?>" required>
                    </label>
                    <label>Soyad
                        <input type="text" name="student_last_name" value="<?php echo e($application['student_last_name']); ?>" required>
                    </label>
                </div>
                <label>Adres
                    <textarea name="student_address" required><?php echo e($application['student_address']); ?></textarea>
                </label>
                <label>Telefon
                    <input type="tel" name="student_phone" value="<?php echo e($application['student_phone']); ?>" required>
                </label>
                <button class="btn btn-primary" type="submit">Bilgileri Guncelle</button>
            </form>

            <?php if ($application['status'] === APPLICATION_STATUS_APPROVED): ?>
                <form method="post" style="margin-top: 1rem;">
                    <input type="hidden" name="mode" value="revert_status">
                    <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                    <button class="btn btn-secondary" type="submit">Onayi Kaldir ve Memura Dondur</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
