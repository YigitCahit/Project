<?php
require_once __DIR__ . '/../config.php';
require_login();

db();
$docId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT d.*, a.user_id, a.target_department, a.status FROM application_documents d JOIN applications a ON d.application_id = a.id WHERE d.id = ? LIMIT 1');
$stmt->bind_param('i', $docId);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    set_flash('error', 'Belge bulunamadi.');
    redirect('applications.php');
}

$user = current_user();
$allowed = false;

if ($user['role'] === ROLE_STUDENT && $document['user_id'] == $user['id']) {
    $allowed = true;
} elseif (in_array($user['role'], [ROLE_CLERK, ROLE_MANAGER, ROLE_EXECUTIVE], true)) {
    $allowed = true;
} elseif (
    $user['role'] === ROLE_CHAIR
    && $user['department'] === $document['target_department']
    && $document['status'] === APPLICATION_STATUS_APPROVED
) {
    $allowed = true;
}

if (!$allowed) {
    set_flash('error', 'Bu belgeye erisim yetkiniz yok.');
    redirect('applications.php');
}

$filePath = $document['file_path'];
if (!is_file($filePath)) {
    set_flash('error', 'Dosya bulunamiyor.');
    redirect('applications.php');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($document['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
