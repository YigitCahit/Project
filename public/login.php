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
    <section class="card form-card">
        <h2>Yatay Gecis Platformu Girisi</h2>
        <p>Kurumsal email adresiniz ve sifreniz ile oturum acin.</p>
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
