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

$pageTitle = 'Kontrol Paneli';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="card-grid">
        <article class="card">
            <h3>Rol</h3>
            <p><?php echo e(ROLE_LABELS[$user['role']] ?? ''); ?></p>
        </article>
        <article class="card">
            <h3>Bekleyen Basvurular</h3>
            <p>
                <?php
                if ($user['role'] === ROLE_STUDENT) {
                    echo count($studentApplications);
                } elseif ($user['role'] === ROLE_CHAIR) {
                    echo application_count(APPLICATION_STATUS_PENDING_CHAIR, $user['department']);
                } elseif ($user['role'] === ROLE_CLERK) {
                    echo application_count(APPLICATION_STATUS_PENDING_CLERK);
                } elseif ($user['role'] === ROLE_MANAGER) {
                    echo application_count(APPLICATION_STATUS_PENDING_MANAGER);
                } else {
                    echo application_count(APPLICATION_STATUS_PENDING_EXECUTIVE);
                }
                ?>
            </p>
        </article>
        <article class="card">
            <h3>Durum Loglari</h3>
            <p>Onay akisi her adimda arivlenir ve indirilebilir.</p>
        </article>
    </section>

    <?php if ($user['role'] === ROLE_STUDENT): ?>
        <section class="card" style="margin-top: 2rem;">
            <div class="actions" style="justify-content: space-between;">
                <h2>Basvurularim</h2>
                <a class="btn btn-primary" href="create_application.php">Yeni Basvuru</a>
            </div>
            <div class="table-wrapper" style="margin-top: 1rem;">
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
                                <td><a href="view_application.php?id=<?php echo e($app['id']); ?>">Detay</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($studentApplications)): ?>
                            <tr><td colspan="5">Henuz basvuru yok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <section class="card" style="margin-top: 2rem;">
            <h2>Operasyon Ozeti</h2>
            <p>Rollerinize gore basvurulari <a href="applications.php">listeden</a> yonetin.</p>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
