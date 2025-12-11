<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if (!$fullName || !$email || !$password) {
        set_flash('error', 'Tum zorunlu alanlari doldurun.');
    } elseif ($password !== $confirm) {
        set_flash('error', 'Sifreler eslesmiyor.');
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            set_flash('error', 'Bu email ile kayit bulunuyor.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = ROLE_STUDENT;
            $status = USER_STATUS_ACTIVE;
            $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, role, status, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $fullName, $email, $phone, $role, $status, $hash);
            $stmt->execute();
            $userId = $stmt->insert_id;
            $stmt->close();

            $_SESSION['user_id'] = $userId;
            set_flash('success', 'Kaydiniz olusturuldu.');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Ogrenci Kaydi';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="page-intro">
        <div>
            <h1 class="page-title">Ogrenci hesap olusturma</h1>
            <p class="page-subtitle">Yatay gecis basvurularini yonetebilmek icin okul email adresinizle hizla hesap olusturun.</p>
        </div>
        <div class="page-intro__actions">
            <a class="btn btn-ghost" href="login.php">Zaten hesabim var</a>
        </div>
    </section>

    <div class="info-banner">Ogrenci hesaplari otomatik olarak etkinlestirilir. Personel rolune ihtiyaciniz varsa lutfen personel kayit formunu kullanin.</div>

    <section class="panel">
        <div class="panel-head">
            <span>Kayit Formu</span>
            <h3>Temel bilgiler</h3>
        </div>
        <form method="post">
            <div class="form-row">
                <label>Ad Soyad
                    <input type="text" name="full_name" required value="<?php echo e($_POST['full_name'] ?? ''); ?>">
                </label>
                <label>Email (Okul)
                    <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                </label>
            </div>
            <label>Telefon
                <input type="tel" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>">
            </label>
            <div class="form-row">
                <label>Sifre
                    <input type="password" name="password" required>
                </label>
                <label>Sifre (Tekrar)
                    <input type="password" name="password_confirmation" required>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Kaydi Tamamla</button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
