<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Login - OLX Clone';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}
$redirect = $_GET['redirect'] ?? '';
require_once __DIR__ . '/includes/header.php';
?>
<body class="bg-gray-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-6">
                <a href="<?= BASE_URL ?>" class="text-3xl font-bold text-[#002F34]">OLX</a>
                <p class="text-gray-500 mt-1">Masuk ke akun Anda</p>
            </div>
            
            <form id="login-form" method="POST" class="space-y-4">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded-lg p-2" placeholder="nama@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required class="w-full border rounded-lg p-2 pr-10" placeholder="••••••••">
                        <button type="button" id="toggle-password" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300"> <span class="ml-2 text-sm">Ingat saya</span>
                    </label>
                    <a href="<?= BASE_URL ?>forgot-password.php" class="text-sm text-[#002F34]">Lupa password?</a>
                </div>
                <button type="submit" class="w-full bg-[#002F34] text-white py-2 rounded-lg font-medium">Masuk</button>
            </form>
            
            <div id="form-error" class="mt-3 text-red-500 text-sm hidden"></div>
            
            <p class="text-center mt-6 text-gray-600">
                Belum punya akun? <a href="register.php" class="text-[#002F34] font-medium">Daftar</a>
            </p>
        </div>
    </div>
    
<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('toggle-password').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
});

document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const errorDiv = document.getElementById('form-error');
    
    try {
        const response = await fetch(BASE_URL + 'api/auth.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            window.location.href = data.redirect || BASE_URL;
        } else {
            showToast(data.message || 'Email atau password salah', 'error');
        }
    } catch (err) {
        showToast(data.message || 'Terjadi kesalahan jaringan', 'error');
    }
});
</script>
</body>
</html>