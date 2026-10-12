<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

$success = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email tidak valid.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($user = $stmt->fetch()) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
                    ->execute([$email, $token, $expires]);
                
                $resetLink = BASE_URL . 'reset-password.php?token=' . $token;
                $success = true;
            } else {
                $success = true; // Jangan bocorkan info email
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - OLX Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F2F4F5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <div class="text-center mb-6">
            <a href="<?= BASE_URL ?>" class="text-3xl font-bold text-[#002F34]">OLX</a>
            <p class="text-gray-500 mt-1">Reset password Anda</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-4">
                <p class="font-medium">Link reset password telah dikirim!</p>
                <p class="text-sm mt-2">Karena ini lingkungan lokal, link reset adalah:</p>
                <div class="bg-white p-3 rounded border mt-2 break-all">
                    <code><?= htmlspecialchars($resetLink ?? '') ?></code>
                </div>
                <p class="text-xs text-gray-500 mt-3">Klik link di atas untuk melanjutkan reset password.</p>
            </div>
            <a href="<?= BASE_URL ?>login.php" class="block text-center text-[#002F34] font-medium">← Kembali ke Login</a>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <?= csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded-lg p-2" placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>">
                </div>
                <button type="submit" class="w-full bg-[#002F34] text-white py-2 rounded-lg font-medium hover:bg-[#001f24] transition">Kirim Link Reset</button>
            </form>
            
            <p class="text-center mt-4">
                <a href="<?= BASE_URL ?>login.php" class="text-[#002F34] text-sm">← Kembali ke Login</a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
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