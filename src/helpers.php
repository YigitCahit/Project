<?php
function db(): mysqli
{
    global $mysqli;
    return $mysqli;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cached = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $cached;
}

function is_logged_in(): bool
{
    return (bool) current_user();
}

function user_has_role(string ...$roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    return in_array($user['role'], $roles, true);
}

function require_post(): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirect('login.php');
    }
}

function ensure_upload_path(): void
{
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }
}

function save_application_documents(int $applicationId, array $files): void
{
    ensure_upload_path();

    foreach (DOCUMENT_REQUIREMENTS as $key => $label) {
        if (empty($files[$key]) || ($files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $file = $files[$key];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            set_flash('error', $label . ' yuklenirken hata olustu.');
            continue;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = $key . '_' . time() . '_' . bin2hex(random_bytes(4));
        $target = rtrim(UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName . ($extension ? '.' . $extension : '');

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            set_flash('error', $label . ' kaydedilemedi.');
            continue;
        }

        $stmt = db()->prepare('INSERT INTO application_documents (application_id, doc_type, file_path, original_name) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $applicationId, $key, $target, $file['name']);
        $stmt->execute();
        $stmt->close();
    }
}

function log_status_change(int $applicationId, string $status, ?int $actorId, string $note = ''): void
{
    $stmt = db()->prepare('INSERT INTO application_status_logs (application_id, status, actor_id, note) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isis', $applicationId, $status, $actorId, $note);
    $stmt->execute();
    $stmt->close();
}

function fetch_application(int $applicationId): ?array
{
    $stmt = db()->prepare('SELECT a.*, u.full_name, u.email FROM applications a JOIN users u ON a.user_id = u.id WHERE a.id = ? LIMIT 1');
    $stmt->bind_param('i', $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $application;
}

function fetch_documents(int $applicationId): array
{
    $stmt = db()->prepare('SELECT * FROM application_documents WHERE application_id = ?');
    $stmt->bind_param('i', $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $documents = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $documents;
}

function status_badge_class(string $status): string
{
    return match ($status) {
        APPLICATION_STATUS_APPROVED => 'badge-success',
        APPLICATION_STATUS_REJECTED => 'badge-danger',
        APPLICATION_STATUS_PENDING_CLERK, APPLICATION_STATUS_PENDING_CHAIR, APPLICATION_STATUS_PENDING_MANAGER, APPLICATION_STATUS_PENDING_EXECUTIVE => 'badge-warning',
        default => 'badge-muted',
    };
}

function available_applications_query(array $user): string
{
    switch ($user['role']) {
        case ROLE_STUDENT:
            return 'SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC';
        case ROLE_CLERK:
        case ROLE_MANAGER:
        case ROLE_EXECUTIVE:
            return 'SELECT * FROM applications ORDER BY created_at DESC';
        case ROLE_CHAIR:
            return 'SELECT * FROM applications WHERE target_department = ? ORDER BY created_at DESC';
        default:
            return 'SELECT * FROM applications WHERE 1 = 0';
    }
}

function next_status_after_approval(string $role): ?string
{
    return match ($role) {
        ROLE_CLERK => APPLICATION_STATUS_PENDING_CHAIR,
        ROLE_CHAIR => APPLICATION_STATUS_PENDING_MANAGER,
        ROLE_MANAGER => APPLICATION_STATUS_PENDING_EXECUTIVE,
        ROLE_EXECUTIVE => APPLICATION_STATUS_APPROVED,
        default => null,
    };
}

function can_transition_status(array $user, array $application, string $newStatus): bool
{
    $role = $user['role'];
    $current = $application['status'];

    $allowed = [
        ROLE_CLERK => [
            APPLICATION_STATUS_PENDING_CLERK => [APPLICATION_STATUS_PENDING_CHAIR, APPLICATION_STATUS_REJECTED],
        ],
        ROLE_CHAIR => [
            APPLICATION_STATUS_PENDING_CHAIR => [APPLICATION_STATUS_PENDING_MANAGER, APPLICATION_STATUS_REJECTED],
        ],
        ROLE_MANAGER => [
            APPLICATION_STATUS_PENDING_MANAGER => [APPLICATION_STATUS_PENDING_EXECUTIVE, APPLICATION_STATUS_APPROVED, APPLICATION_STATUS_REJECTED],
        ],
        ROLE_EXECUTIVE => [
            APPLICATION_STATUS_PENDING_EXECUTIVE => [APPLICATION_STATUS_APPROVED, APPLICATION_STATUS_REJECTED],
        ],
    ];

    return isset($allowed[$role][$current]) && in_array($newStatus, $allowed[$role][$current], true);
}

function map_instruction_label(string $value): string
{
    return in_array($value, INSTRUCTION_TYPES, true) ? $value : INSTRUCTION_TYPES[0];
}
