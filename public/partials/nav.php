<?php
$user = current_user();
$currentPath = basename($_SERVER['PHP_SELF']);

$navLinks = [
    ['label' => 'Ana Sayfa', 'href' => 'index.php'],
];

if ($user) {
    $navLinks[] = ['label' => 'Panel', 'href' => 'dashboard.php'];
    $navLinks[] = ['label' => 'Basvurular', 'href' => 'applications.php'];
    if ($user['role'] === ROLE_STUDENT) {
        $navLinks[] = ['label' => 'Yeni Basvuru', 'href' => 'create_application.php'];
    }
    if (user_has_role(ROLE_MANAGER, ROLE_EXECUTIVE)) {
        $navLinks[] = ['label' => 'Kullanici Yonetimi', 'href' => 'manage_users.php'];
    }
    if (user_has_role(ROLE_EXECUTIVE)) {
        $navLinks[] = ['label' => 'Ust Yonetim', 'href' => 'executive_tools.php'];
    }
} else {
    $navLinks[] = ['label' => 'Ogrenci Kaydi', 'href' => 'register_student.php'];
    $navLinks[] = ['label' => 'Personel Kaydi', 'href' => 'register_staff.php'];
    $navLinks[] = ['label' => 'Giris', 'href' => 'login.php'];
}
?>
<header class="top-bar">
    <div class="page-width top-bar__inner">
        <a class="brand-link" href="index.php">
            <span class="brand-mark">YGS</span>
            <div class="brand-text">
                <span>Yatay Gecis Sistemi</span>
                <small>Aksaray Universitesi Modulu</small>
            </div>
        </a>
        <nav class="nav-links">
            <?php foreach ($navLinks as $link): ?>
                <?php $active = $currentPath === basename($link['href']) ? 'is-active' : ''; ?>
                <a class="<?php echo $active; ?>" href="<?php echo e($link['href']); ?>"><?php echo e($link['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="user-tools">
            <?php if ($user): ?>
                <div class="user-pill">
                    <strong><?php echo e($user['full_name']); ?></strong>
                    <span><?php echo e(ROLE_LABELS[$user['role']] ?? ''); ?></span>
                </div>
                <?php if ($user['role'] === ROLE_STUDENT): ?>
                    <a class="btn btn-ghost" href="create_application.php">Yeni Basvuru</a>
                <?php else: ?>
                    <a class="btn btn-ghost" href="applications.php">Basvurular</a>
                <?php endif; ?>
                <a class="btn btn-primary" href="logout.php">Cikis</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="login.php">Giris Yap</a>
                <a class="btn btn-primary" href="register_student.php">Ogrenci Kaydi</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php include __DIR__ . '/flash.php'; ?>
