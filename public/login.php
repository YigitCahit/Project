<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        set_flash('error', 'Lutfen email ve sifre giriniz.');
    } elseif (attempt_login($email, $password)) {
        redirect('dashboard.php');
    } else {
        set_flash('error', 'Gecersiz bilgiler.');
    }
}

$pageTitle = 'Giris Yap';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="page-intro">
        <div>
            <h1 class="page-title">Platform Girisi</h1>
            <p class="page-subtitle">Kurumsal email ve sifreniz ile oturum acin. Hesabiniz yoksa ogrenci veya personel kaydi olusturabilirsiniz.</p>
        </div>
        <div class="page-intro__actions">
            <a class="btn btn-ghost" href="register_student.php">Ogrenci Kaydi</a>
            <a class="btn btn-ghost" href="register_staff.php">Personel Kaydi</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <span>Giris</span>
            <h3>Oturum Bilgileri</h3>
        </div>
        <form method="post">
            <label>Email
                <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </label>
            <label>Sifre
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn btn-primary">Giris Yap</button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
