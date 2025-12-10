<?php
require_once __DIR__ . '/../config.php';
require_roles(ROLE_STUDENT);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'current_school' => trim($_POST['current_school'] ?? ''),
        'current_faculty' => trim($_POST['current_faculty'] ?? ''),
        'current_department' => trim($_POST['current_department'] ?? ''),
        'student_number' => trim($_POST['student_number'] ?? ''),
        'current_instruction' => trim($_POST['current_instruction'] ?? INSTRUCTION_TYPES[0]),
        'completed_semester' => (int) ($_POST['completed_semester'] ?? 0),
        'target_faculty' => trim($_POST['target_faculty'] ?? ''),
        'target_department' => trim($_POST['target_department'] ?? ''),
        'target_instruction' => trim($_POST['target_instruction'] ?? INSTRUCTION_TYPES[0]),
        'student_first_name' => trim($_POST['student_first_name'] ?? ''),
        'student_last_name' => trim($_POST['student_last_name'] ?? ''),
        'student_address' => trim($_POST['student_address'] ?? ''),
        'student_phone' => trim($_POST['student_phone'] ?? ''),
    ];

    if (in_array('', [$fields['current_school'], $fields['current_faculty'], $fields['current_department'], $fields['student_number'], $fields['target_faculty'], $fields['target_department'], $fields['student_first_name'], $fields['student_last_name'], $fields['student_address'], $fields['student_phone']], true)) {
        set_flash('error', 'Tum zorunlu alanlari doldurun.');
    } else {
        $fields['current_instruction'] = map_instruction_label($fields['current_instruction']);
        $fields['target_instruction'] = map_instruction_label($fields['target_instruction']);
        $status = APPLICATION_STATUS_PENDING_CLERK;
        $stmt = db()->prepare('INSERT INTO applications (user_id, current_school, current_faculty, current_department, student_number, current_instruction, completed_semester, target_faculty, target_department, target_instruction, student_first_name, student_last_name, student_address, student_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'issssssisssssss',
            $user['id'],
            $fields['current_school'],
            $fields['current_faculty'],
            $fields['current_department'],
            $fields['student_number'],
            $fields['current_instruction'],
            $fields['completed_semester'],
            $fields['target_faculty'],
            $fields['target_department'],
            $fields['target_instruction'],
            $fields['student_first_name'],
            $fields['student_last_name'],
            $fields['student_address'],
            $fields['student_phone'],
            $status
        );
        $stmt->execute();
        $appId = $stmt->insert_id;
        $stmt->close();

        save_application_documents($appId, $_FILES);
        log_status_change($appId, $status, $user['id'], 'Basvuru olusturuldu');

        set_flash('success', 'Basvurunuz iletildi.');
        redirect('view_application.php?id=' . $appId);
    }
}

$pageTitle = 'Yeni Basvuru';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="card form-card">
        <h2>Yatay Gecis Basvurusu</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <label>Kayitli Olunan Universite / Birim
                    <input type="text" name="current_school" required>
                </label>
                <label>Fakulte / Yuksekokul
                    <input type="text" name="current_faculty" required>
                </label>
            </div>
            <div class="form-row">
                <label>Bolum
                    <input type="text" name="current_department" required>
                </label>
                <label>Ogrenci No
                    <input type="text" name="student_number" required>
                </label>
            </div>
            <div class="form-row">
                <label>Ogretim Sekli
                    <select name="current_instruction">
                        <?php foreach (INSTRUCTION_TYPES as $type): ?>
                            <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tamamlanan Yariyil
                    <input type="number" name="completed_semester" min="0" max="12" required>
                </label>
            </div>
            <hr>
            <div class="form-row">
                <label>Hedef Fakulte / Yuksekokul
                    <input type="text" name="target_faculty" required>
                </label>
                <label>Hedef Bolum
                    <input type="text" name="target_department" required>
                </label>
            </div>
            <label>Hedef Ogretim Sekli
                <select name="target_instruction">
                    <?php foreach (INSTRUCTION_TYPES as $type): ?>
                        <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <hr>
            <div class="form-row">
                <label>Ad
                    <input type="text" name="student_first_name" required>
                </label>
                <label>Soyad
                    <input type="text" name="student_last_name" required>
                </label>
            </div>
            <label>Adres
                <textarea name="student_address" required></textarea>
            </label>
            <label>Iletisim Telefonu
                <input type="tel" name="student_phone" required>
            </label>
            <hr>
            <h3>Belgeler</h3>
            <p>Zorunlu belgelerinizi PDF veya JPG olarak yukleyin.</p>
            <?php foreach (DOCUMENT_REQUIREMENTS as $key => $label): ?>
                <label><?php echo e($label); ?>
                    <input type="file" name="<?php echo e($key); ?>" accept=".pdf,.jpg,.jpeg,.png">
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Basvuruyu Gonder</button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
