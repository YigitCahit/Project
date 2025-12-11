<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

function application_count(string $status, ?string $department = null): int
{
    if ($department) {
        $stmt = db()->prepare('SELECT COUNT(*) AS total FROM applications WHERE status = ? AND target_department = ?');
        $stmt->bind_param('ss', $status, $department);
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) AS total FROM applications WHERE status = ?');
        $stmt->bind_param('s', $status);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($result['total'] ?? 0);
}

$studentApplications = [];
if ($user['role'] === ROLE_STUDENT) {
    $stmt = db()->prepare('SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $studentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$recentApplications = [];
if ($user['role'] !== ROLE_STUDENT) {
    if ($user['role'] === ROLE_CHAIR) {
        $stmt = db()->prepare('SELECT * FROM applications WHERE target_department = ? ORDER BY created_at DESC LIMIT 5');
        $stmt->bind_param('s', $user['department']);
    } else {
        $stmt = db()->prepare('SELECT * FROM applications ORDER BY created_at DESC LIMIT 5');
    }
    $stmt->execute();
    $recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$approvedByUser = 0;
$rejectedByUser = 0;
if ($user['role'] !== ROLE_STUDENT) {
    $stmt = db()->prepare("SELECT status, COUNT(*) AS total FROM application_status_logs WHERE actor_id = ? AND status IN (?, ?) GROUP BY status");
    $approved = APPLICATION_STATUS_APPROVED;
    $rejected = APPLICATION_STATUS_REJECTED;
    $stmt->bind_param('iss', $user['id'], $approved, $rejected);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($logs as $log) {
        if ($log['status'] === APPLICATION_STATUS_APPROVED) {
            $approvedByUser = (int) $log['total'];
        }
        if ($log['status'] === APPLICATION_STATUS_REJECTED) {
            $rejectedByUser = (int) $log['total'];
        }
    }
}

$firstName = explode(' ', trim($user['full_name']))[0] ?? $user['full_name'];

$pageTitle = 'Kontrol Paneli';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="page-intro">
        <div>
            <h1 class="page-title">Hos geldiniz, <?php echo e($firstName); ?></h1>
            <p class="page-subtitle">Yatay gecis basvurularinizi buradan yonetebilir, ilerlemeyi gorebilir ve ilgili belgeleri paylasabilirsiniz.</p>
        </div>
        <?php if ($user['role'] === ROLE_STUDENT): ?>
            <div class="page-intro__actions">
                <a class="btn btn-primary" href="create_application.php">Yeni Basvuru Olustur</a>
                <a class="btn btn-ghost" href="applications.php">Basvurularimi Goruntule</a>
            </div>
        <?php else: ?>
            <div class="page-intro__actions">
                <a class="btn btn-primary" href="applications.php">Bekleyen Basvurular</a>
                <a class="btn btn-ghost" href="manage_users.php">Kullanici Araclari</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($user['role'] === ROLE_STUDENT): ?>
        <?php
        $total = count($studentApplications);
        $draftCount = array_sum(array_map(fn($app) => $app['status'] === APPLICATION_STATUS_DRAFT ? 1 : 0, $studentApplications));
        $approvedCount = array_sum(array_map(fn($app) => $app['status'] === APPLICATION_STATUS_APPROVED ? 1 : 0, $studentApplications));
        $processingCount = max(0, $total - ($draftCount + $approvedCount));
        ?>
        <section class="stat-grid">
            <article class="stat-card stat-card--slate">
                <span class="stat-card__label">Toplam Basvuru</span>
                <span class="stat-card__value"><?php echo $total; ?></span>
            </article>
            <article class="stat-card stat-card--yellow">
                <span class="stat-card__label">Taslak</span>
                <span class="stat-card__value"><?php echo $draftCount; ?></span>
            </article>
            <article class="stat-card stat-card--blue">
                <span class="stat-card__label">Islemde</span>
                <span class="stat-card__value"><?php echo $processingCount; ?></span>
            </article>
            <article class="stat-card stat-card--green">
                <span class="stat-card__label">Tamamlanan</span>
                <span class="stat-card__value"><?php echo $approvedCount; ?></span>
            </article>
        </section>

        <section class="quick-actions">
            <a class="quick-card" href="create_application.php">
                <strong>Yeni Basvuru Olustur</strong>
                <span>Formu doldurun ve zorunlu belgeleri yukleyin.</span>
            </a>
            <a class="quick-card" href="applications.php">
                <strong>Basvurularimi Goruntule</strong>
                <span>Durum, log ve belgelerinizi tek listede takip edin.</span>
            </a>
            <a class="quick-card" href="applications.php#drafts">
                <strong>Taslak Basvurular</strong>
                <span>Sonradan tamamlamak uzere kayitli formlari acin.</span>
            </a>
        </section>

        <?php if (empty($studentApplications)): ?>
            <div class="info-banner">Henuz basvuru olusturmadiniz. <a href="create_application.php">Yeni basvuru olusturmak icin tiklayin.</a></div>
        <?php endif; ?>

        <section class="table-card">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.5rem 0;">
                <h2 class="card-title" style="margin:0;">Basvurularim</h2>
                <a class="btn btn-primary" href="create_application.php">Yeni Basvuru</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kaynak Bolum</th>
                        <th>Hedef Bolum</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentApplications as $app): ?>
                        <tr>
                            <td>#<?php echo e($app['id']); ?></td>
                            <td><?php echo e($app['current_department']); ?></td>
                            <td><?php echo e($app['target_department']); ?></td>
                            <td><span class="badge <?php echo e(status_badge_class($app['status'])); ?>"><?php echo e(APPLICATION_STATUS_LABELS[$app['status']] ?? $app['status']); ?></span></td>
                            <td><a class="btn btn-ghost" href="view_application.php?id=<?php echo e($app['id']); ?>">Detay</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($studentApplications)): ?>
                        <tr><td colspan="5">Henuz kayit yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php else: ?>
        <?php
        $pendingCount = $user['role'] === ROLE_CHAIR
            ? application_count(APPLICATION_STATUS_APPROVED, $user['department'])
            : application_count(APPLICATION_STATUS_PENDING_CLERK);
        $inReviewCount = application_count(APPLICATION_STATUS_PENDING_CLERK);
        $approvedTotal = application_count(APPLICATION_STATUS_APPROVED);
        ?>
        <section class="stat-grid">
            <article class="stat-card stat-card--yellow">
                <span class="stat-card__label">Bekleyen Basvurular</span>
                <span class="stat-card__value"><?php echo $pendingCount; ?></span>
            </article>
            <article class="stat-card stat-card--blue">
                <span class="stat-card__label">Incelemede</span>
                <span class="stat-card__value"><?php echo $inReviewCount; ?></span>
            </article>
            <article class="stat-card stat-card--green">
                <span class="stat-card__label">Onayladigim</span>
                <span class="stat-card__value"><?php echo $approvedByUser; ?></span>
            </article>
            <article class="stat-card stat-card--red">
                <span class="stat-card__label">Iade Ettigim</span>
                <span class="stat-card__value"><?php echo $rejectedByUser; ?></span>
            </article>
        </section>

        <section class="card">
            <h2 class="card-title">Operasyon Ozeti</h2>
            <p class="card-description">Guncel talepleri incelemek icin bekleyen listeyi kontrol edin, gerekli durumlarda not ekleyin ve kararlarinizi loglayin.</p>
            <div style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
                <a class="btn btn-primary" href="applications.php">Bekleyenleri Incele</a>
                <?php if (user_has_role(ROLE_EXECUTIVE)): ?>
                    <a class="btn btn-ghost" href="executive_tools.php">Ust Yonetim Araclari</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="table-card">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.5rem 0;">
                <h2 class="card-title" style="margin:0;">Son Gelen Basvurular</h2>
                <a class="btn btn-ghost" href="applications.php">Tumunu Gor</a>
            </div>
            <?php if (empty($recentApplications)): ?>
                <div class="empty-state" style="margin:1rem;">Henuz basvuru bulunmuyor.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ogrenci</th>
                            <th>Hedef Bolum</th>
                            <th>Durum</th>
                            <th>Islem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentApplications as $row): ?>
                            <tr>
                                <td>#<?php echo e($row['id']); ?></td>
                                <td><?php echo e($row['student_first_name'] . ' ' . $row['student_last_name']); ?></td>
                                <td><?php echo e($row['target_department']); ?></td>
                                <td><span class="badge <?php echo e(status_badge_class($row['status'])); ?>"><?php echo e(APPLICATION_STATUS_LABELS[$row['status']] ?? $row['status']); ?></span></td>
                                <td><a class="btn btn-ghost" href="view_application.php?id=<?php echo e($row['id']); ?>">Detay</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
