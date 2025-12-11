<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = $_POST['role'] ?? ROLE_CLERK;
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if (!$fullName || !$email || !$department || !$password) {
        set_flash('error', 'Tum zorunlu alanlari doldurun.');
    } elseif (!in_array($role, [ROLE_CLERK, ROLE_CHAIR], true)) {
        set_flash('error', 'Gecersiz rol secimi.');
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
            $status = USER_STATUS_PENDING;
            $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, department, role, status, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssss', $fullName, $email, $phone, $department, $role, $status, $hash);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Personel kaydi alindi. Yonetici onayindan sonra giris yapabilirsiniz.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Personel Kaydi';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="page-intro">
        <div>
            <h1 class="page-title">Personel hesabi talep edin</h1>
            <p class="page-subtitle">Memur veya bolum baskani rollerine erisim icin kurumsal email adresinizle kayit talebi olusturun. Kayitlar yonetim onayi sonrasinda etkinlesir.</p>
        </div>
        <div class="page-intro__actions">
            <a class="btn btn-ghost" href="login.php">Giris sayfasina don</a>
        </div>
    </section>

    <div class="info-banner">Personel hesaplari otomatik olarak bekleme durumuna alinir ve yonetici tarafindan aktiflestirilir.</div>

    <section class="panel">
        <div class="panel-head">
            <span>Basvuru Formu</span>
            <h3>Kimlik ve rol bilgileri</h3>
        </div>
        <form method="post">
            <div class="form-row">
                <label>Ad Soyad
                    <input type="text" name="full_name" required value="<?php echo e($_POST['full_name'] ?? ''); ?>">
                </label>
                <label>Kurumsal Email
                    <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                </label>
            </div>
            <div class="form-row">
                <label>Telefon
                    <input type="tel" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>">
                </label>
                <label>Birim / Bolum
                    <input type="text" name="department" required value="<?php echo e($_POST['department'] ?? ''); ?>">
                </label>
            </div>
            <label>Rol
                <select name="role" required>
                    <option value="<?php echo ROLE_CLERK; ?>" <?php echo (($_POST['role'] ?? '') === ROLE_CLERK) ? 'selected' : ''; ?>>Memur (Basvuru onay)</option>
                    <option value="<?php echo ROLE_CHAIR; ?>" <?php echo (($_POST['role'] ?? '') === ROLE_CHAIR) ? 'selected' : ''; ?>>Bolum Baskani</option>
                </select>
            </label>
            <div class="form-row">
                <label>Sifre
                    <input type="password" name="password" required>
                </label>
                <label>Sifre (Tekrar)
                    <input type="password" name="password_confirmation" required>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Kaydi Gonder</button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
