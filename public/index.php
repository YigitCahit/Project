<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Yatay Gecis Basvuru Sistemi';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="hero">
        <h1>Universiteler Arasi Yatay Gecis Basvurularini Tek Ekranda Yonetin</h1>
        <p>Ogrenci, memur, bolum baskani ve ust yonetim icin tasarlanan modul sayesinde belgeleri toplayin, surecleri hizlandirin ve tum basvurulari seffaf sekilde takip edin.</p>
        <div class="actions">
            <?php if (is_logged_in()): ?>
                <a class="btn btn-primary" href="dashboard.php">Kontrol Paneline Git</a>
            <?php else: ?>
                <a class="btn btn-primary" href="register_student.php">Ogrenci Kaydi</a>
                <a class="btn btn-secondary" href="login.php">Giris Yap</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="card-grid">
        <article class="card">
            <h3>Belgeler</h3>
            <p>Zorunlu evraklar icin yukleme kontrolleri, her rol icin guvenli erisim ve tek tikla indirme.</p>
        </article>
        <article class="card">
            <h3>Yetki Katmanlari</h3>
            <p>Ogrenci, memur, bolum baskani, yonetici ve ust yonetici rollerine gore filtrelenmis deneyim.</p>
        </article>
        <article class="card">
            <h3>Izlenebilirlik</h3>
            <p>Durum loglari, onay zinciri ve otomatik hatirlatmalar ile surec takibini kolaylastirin.</p>
        </article>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
