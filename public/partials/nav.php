<?php $user = current_user(); ?>
<header class="app-header">
    <div class="brand">
        <span class="brand-mark">Y</span>
        <div>
            <p class="brand-title">Yatay Gecis Basvuru</p>
            <p class="brand-subtitle">Universite Ici Transfer Modulu</p>
        </div>
    </div>
    <nav class="primary-nav">
        <a href="index.php">Ana Sayfa</a>
        <?php if ($user): ?>
            <a href="dashboard.php">Panel</a>
            <a href="applications.php">Basvurular</a>
            <?php if ($user['role'] === ROLE_STUDENT): ?>
                <a href="create_application.php" class="cta-link">Yeni Basvuru</a>
            <?php endif; ?>
            <?php if (user_has_role(ROLE_MANAGER, ROLE_EXECUTIVE)): ?>
                <a href="manage_users.php">Kullanici Yonetimi</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php">Giris</a>
            <a href="register_student.php">Ogrenci Kaydi</a>
            <a href="register_staff.php">Personel Kaydi</a>
        <?php endif; ?>
    </nav>
    <div class="header-user">
        <?php if ($user): ?>
            <div class="user-meta">
                <span class="user-name"><?php echo e($user['full_name']); ?></span>
                <span class="user-role"><?php echo e(ROLE_LABELS[$user['role']] ?? ''); ?></span>
            </div>
            <a class="ghost-btn" href="logout.php">Cikis</a>
        <?php else: ?>
            <a class="ghost-btn" href="login.php">Giris Yap</a>
        <?php endif; ?>
    </div>
</header>
<?php include __DIR__ . '/flash.php'; ?>
