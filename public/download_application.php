<?php
require_once __DIR__ . '/../config.php';
require_login();

$appId = (int) ($_GET['id'] ?? 0);
$application = fetch_application($appId);

if (!$application) {
    set_flash('error', 'Basvuru bulunamadi.');
    redirect('applications.php');
}

$user = current_user();
$allowed = false;

if ($user['role'] === ROLE_STUDENT && $application['user_id'] === $user['id']) {
    $allowed = true;
} elseif (in_array($user['role'], [ROLE_CLERK, ROLE_CHAIR, ROLE_MANAGER, ROLE_EXECUTIVE], true)) {
    if (
        $user['role'] === ROLE_CHAIR
        && (
            $user['department'] !== $application['target_department']
            || $application['status'] !== APPLICATION_STATUS_APPROVED
        )
    ) {
        $allowed = false;
    } else {
        $allowed = true;
    }
}

if (!$allowed) {
    set_flash('error', 'Erisim engellendi.');
    redirect('applications.php');
}

header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="basvuru_' . $application['id'] . '.html"');

echo '<html><head><meta charset="UTF-8"><title>Basvuru</title>';
echo '<style>body{font-family:Arial,sans-serif;padding:2rem;}h1{color:#111;}section{margin-bottom:1.2rem;}table{width:100%;border-collapse:collapse;}td{padding:6px 4px;border-bottom:1px solid #ccc;}</style>';
echo '</head><body>';
echo '<h1>Yatay Gecis Basvurusu #' . $application['id'] . '</h1>';
echo '<section><h2>Ogrenci Bilgileri</h2><table>';
echo '<tr><td>Ad Soyad</td><td>' . e($application['student_first_name'] . ' ' . $application['student_last_name']) . '</td></tr>';
echo '<tr><td>Ogrenci No</td><td>' . e($application['student_number']) . '</td></tr>';
echo '<tr><td>Adres</td><td>' . e($application['student_address']) . '</td></tr>';
echo '<tr><td>Telefon</td><td>' . e($application['student_phone']) . '</td></tr>';
echo '</table></section>';
echo '<section><h2>Kayitli Olunan Program</h2><table>';
echo '<tr><td>Universite</td><td>' . e($application['current_school']) . '</td></tr>';
echo '<tr><td>Fakulte</td><td>' . e($application['current_faculty']) . '</td></tr>';
echo '<tr><td>Bolum</td><td>' . e($application['current_department']) . '</td></tr>';
echo '<tr><td>Ogretim</td><td>' . e($application['current_instruction']) . '</td></tr>';
echo '<tr><td>Tamamlanan Yariyil</td><td>' . e($application['completed_semester']) . '</td></tr>';
echo '</table></section>';
echo '<section><h2>Hedef Program</h2><table>';
echo '<tr><td>Fakulte</td><td>' . e($application['target_faculty']) . '</td></tr>';
echo '<tr><td>Bolum</td><td>' . e($application['target_department']) . '</td></tr>';
echo '<tr><td>Ogretim</td><td>' . e($application['target_instruction']) . '</td></tr>';
echo '</table></section>';
echo '<p>Durum: ' . e(APPLICATION_STATUS_LABELS[$application['status']] ?? $application['status']) . '</p>';
echo '</body></html>';
