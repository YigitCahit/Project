<?php
require_once __DIR__ . '/../config.php';
require_roles(ROLE_EXECUTIVE);

$user = current_user();

if (!function_exists('purge_upload_files')) {
    function purge_upload_files(): void
    {
        if (!is_dir(UPLOAD_PATH)) {
            return;
        }

        $items = scandir(UPLOAD_PATH);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }

            $path = rtrim(UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                // best-effort recursive removal for stray folders
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isDir()) {
                        @rmdir($fileInfo->getPathname());
                    } else {
                        @unlink($fileInfo->getPathname());
                    }
                }
                @rmdir($path);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = strtoupper(trim($_POST['confirm'] ?? ''));
    if ($confirm !== 'TEMIZLE') {
        set_flash('error', 'Islemi baslatmak icin TEMIZLE yazmalisiniz.');
    } else {
        $db = db();
        $db->begin_transaction();
        try {
            if (!$db->query('SET FOREIGN_KEY_CHECKS = 0')) {
                throw new RuntimeException($db->error);
            }
            if (!$db->query('TRUNCATE TABLE application_documents')) {
                throw new RuntimeException($db->error);
            }
            if (!$db->query('TRUNCATE TABLE application_status_logs')) {
                throw new RuntimeException($db->error);
            }
            if (!$db->query('TRUNCATE TABLE applications')) {
                throw new RuntimeException($db->error);
            }
            $stmt = $db->prepare('DELETE FROM users WHERE id <> ?');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();
            if (!$db->query('SET FOREIGN_KEY_CHECKS = 1')) {
                throw new RuntimeException($db->error);
            }
            $db->commit();
            purge_upload_files();
            set_flash('success', 'Tum basvuru verileri ve kullanici hesaplari (sizin disinizda) temizlendi.');
        } catch (Throwable $e) {
            $db->rollback();
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
            set_flash('error', 'Islem tamamlanamadi: ' . $e->getMessage());
        }
    }

    redirect('executive_tools.php');
}

$pageTitle = 'Ust Yonetim Araclari';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<main>
    <section class="card form-card">
        <h2>Veri Tabanini Sifirla</h2>
        <p>
            Bu islem tum basvurulari, yuklenen belgeleri, durum loglarini ve sizin disinizdaki tum hesaplari siler.
            Geri alinamaz.
        </p>
        <ul>
            <li>Belgeler <code>public/uploads</code> icinden kaldirilir.</li>
            <li>Basvurular sifirlanir ve bolum baskanlarina ait listeler bosalir.</li>
            <li>Tum kullanici hesaplari silinir, yalnizca mevcut oturumunuz korunur.</li>
        </ul>
        <form method="post" style="margin-top: 1rem;">
            <label>Onaylamak icin TEMIZLE yaziniz
                <input type="text" name="confirm" placeholder="TEMIZLE" required>
            </label>
            <button class="btn btn-danger" type="submit" style="margin-top: 1rem;">Veriyi Temizle</button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
