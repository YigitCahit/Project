<?php
require_once __DIR__ . '/../config.php';

$metrics = [
    'total' => 0,
    'approved' => 0,
    'departments' => 0,
];

$db = db();
$result = $db->query('SELECT COUNT(*) AS total FROM applications');
$metrics['total'] = (int) ($result->fetch_assoc()['total'] ?? 0);

$result = $db->query("SELECT COUNT(*) AS total FROM applications WHERE status = 'approved'");
$metrics['approved'] = (int) ($result->fetch_assoc()['total'] ?? 0);

$result = $db->query('SELECT COUNT(DISTINCT target_department) AS total FROM applications');
$metrics['departments'] = (int) ($result->fetch_assoc()['total'] ?? 0);

$pageTitle = 'Yatay Gecis Basvuru Sistemi';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="page-intro">
        <div>
            <p class="info-banner" style="margin-bottom:1rem;">Yatay gecis surecinin her adimi tek panelde izlenebilir, belgeler guvenle saklanir.</p>
            <h1 class="page-title">Universiteler arasi yatay gecis icin modern basvuru deneyimi</h1>
            <p class="page-subtitle">Memur, ogrenci, bolum baskani ve ust yonetim rolleri icin ozel akislara sahip bu platform ile belgeleri toplayin, ilerlemeyi takip edin ve tum isteklere seffaflik kazandirin.</p>
        </div>
        <div class="page-intro__actions">
            <?php if (is_logged_in()): ?>
                <a class="btn btn-primary" href="dashboard.php">Kontrol Paneli</a>
                <a class="btn btn-ghost" href="applications.php">Basvurular</a>
            <?php else: ?>
                <a class="btn btn-primary" href="register_student.php">Ogrenci Kaydi</a>
                <a class="btn btn-secondary" href="login.php">Personel Girisi</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="stat-grid">
        <article class="stat-card stat-card--blue">
            <span class="stat-card__label">Toplam Basvuru</span>
            <span class="stat-card__value"><?php echo number_format($metrics['total']); ?></span>
        </article>
        <article class="stat-card stat-card--green">
            <span class="stat-card__label">Onaylanan Talepler</span>
            <span class="stat-card__value"><?php echo number_format($metrics['approved']); ?></span>
        </article>
        <article class="stat-card stat-card--yellow">
            <span class="stat-card__label">Hedef Bolum</span>
            <span class="stat-card__value"><?php echo number_format($metrics['departments']); ?></span>
        </article>
    </section>

    <section class="card-grid">
        <article class="card">
            <h3 class="card-title">Rol Bazli Yetkiler</h3>
            <p class="card-description">Her rol sadece ihtiyaci olan bilgileri gorur. Memur, bolum baskani veya ust yoneticiye gore filtrelenmis ekranlar ile hata payi azalir.</p>
        </article>
        <article class="card">
            <h3 class="card-title">Belgeler icin guvenli kutuphane</h3>
            <p class="card-description">PDF ve gorsel belgeler otomatik olarak adlandirilir, izinsiz erisim engellenir ve tek tikla indirilebilir.</p>
        </article>
        <article class="card">
            <h3 class="card-title">Durum Izleme</h3>
            <p class="card-description">Onay zinciri boyunca her degisim loglanir, gerekirse HTML rapor olarak indirilebilir.</p>
        </article>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
