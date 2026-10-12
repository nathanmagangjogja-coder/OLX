<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Reset Password - OLX Clone';
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

// Validasi token
if (empty($token)) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'Link reset password tidak valid atau sudah kadaluarsa.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            // Update password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->execute([$hashed, $reset['email']]);
            
            // Hapus token
            $del = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $del->execute([$token]);
            
            $success = true;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <div class="text-center mb-6">
            <a href="<?= BASE_URL ?>" class="text-3xl font-bold text-[#002F34]">OLX</a>
            <p class="text-gray-500 mt-1">Buat password baru</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-4">
                <p>Password berhasil diubah!</p>
            </div>
            <a href="<?= BASE_URL ?>login.php" class="block w-full bg-[#002F34] text-white text-center py-2 rounded-lg">Login Sekarang</a>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <?= csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium mb-1">Password Baru</label>
                    <input type="password" name="password" required minlength="8" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required class="w-full border rounded-lg p-2">
                </div>
                <button type="submit" class="w-full bg-[#002F34] text-white py-2 rounded-lg font-medium">Reset Password</button>
            </form>
            <?php if ($error !== 'Link reset password tidak valid atau sudah kadaluarsa.'): ?>
                <p class="text-center mt-4">
                    <a href="<?= BASE_URL ?>login.php" class="text-[#002F34] text-sm">← Kembali ke Login</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Toast Container & JS -->
    <div id="toast-container" class="toast-container"></div>
    <script src="<?= BASE_URL ?>assets/js/toast.js"></script>
    <?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.showToast(<?= json_encode($error) ?>, 'error');
        });
    </script>
    <?php endif; ?>
</body>
</html>