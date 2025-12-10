<?php
function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if ($user['status'] !== USER_STATUS_ACTIVE && $user['role'] !== ROLE_STUDENT) {
        set_flash('error', 'Hesabiniz etkin degil.');
        return false;
    }

    $_SESSION['user_id'] = $user['id'];
    return true;
}

function logout(): void
{
    session_destroy();
    header('Location: login.php');
    exit;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Devam etmek icin giris yapmalisiniz.');
        redirect('login.php');
    }

    $user = current_user();
    if ($user && $user['status'] === USER_STATUS_DISABLED) {
        set_flash('error', 'Hesabiniz deaktiv durumda.');
        logout();
    }
}

function require_roles(string ...$roles): void
{
    require_login();
    if (!user_has_role(...$roles)) {
        set_flash('error', 'Bu alana erisim yetkiniz yok.');
        redirect('dashboard.php');
    }
}
