<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Pengaturan Profil - OLX Clone';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];
$user = getCurrentUser();
$provinces = $pdo->query("SELECT * FROM provinces ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['message' => 'Token keamanan tidak valid', 'type' => 'error'];
        header('Location: settings.php');
        exit;
    }
    
    $fullName = sanitizeInput($_POST['full_name']);
    $phone = sanitizeInput($_POST['phone']);
    $bio = sanitizeInput($_POST['bio']);
    $provinceId = intval($_POST['province_id']);
    $cityId = intval($_POST['city_id']);
    
    // Handle avatar upload
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $newName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../uploads/avatars/' . $newName;
        if (resizeImage($_FILES['avatar']['tmp_name'], $dest, 300, 300)) {
            $avatar = $newName;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, bio=?, province_id=?, city_id=?, avatar=? WHERE id=?");
    $stmt->execute([$fullName, $phone, $bio, $provinceId, $cityId, $avatar, $userId]);
    $_SESSION['flash'] = ['message' => 'Profil berhasil diperbarui', 'type' => 'success'];
    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <aside class="md:w-64 flex-shrink-0">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </aside>
        <div class="flex-1 max-w-2xl">
            <h1 class="text-2xl font-bold mb-4">Pengaturan Profil</h1>
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="font-bold mb-4">Informasi Pribadi</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="flex items-center mb-4">
                        <div class="w-20 h-20 rounded-full bg-gray-200 overflow-hidden mr-4">
                            <?php if ($user['avatar']): ?>
                            <img src="<?= BASE_URL ?>uploads/avatars/<?= $user['avatar'] ?>" class="w-full h-full object-cover" id="avatar-preview">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-3xl text-gray-400" id="avatar-preview">👤</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" name="avatar" accept="image/*" id="avatar-input" class="hidden">
                            <button type="button" onclick="document.getElementById('avatar-input').click()" class="border px-3 py-1 rounded text-sm">Ganti Foto</button>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor HP</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Bio</label>
                            <textarea name="bio" rows="3" class="w-full border rounded-lg p-2"><?= htmlspecialchars($user['bio']) ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Provinsi</label>
                            <select name="province_id" id="province-select" class="w-full border rounded-lg p-2">
                                <option value="">Pilih Provinsi</option>
                                <?php foreach ($provinces as $prov): ?>
                                <option value="<?= $prov['id'] ?>" <?= $user['province_id'] == $prov['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prov['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kota</label>
                            <select name="city_id" id="city-select" class="w-full border rounded-lg p-2" <?= $user['province_id'] ? '' : 'disabled' ?>>
                                <option value="">Pilih Kota</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="mt-4 bg-[#002F34] text-white px-6 py-2 rounded-lg">Simpan Perubahan</button>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="font-bold mb-4">Ganti Password</h2>
                <form id="password-form" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-sm font-medium mb-1">Password Lama</label>
                        <input type="password" name="old_password" required class="w-full border rounded-lg p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Password Baru</label>
                        <input type="password" name="new_password" required minlength="8" class="w-full border rounded-lg p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required class="w-full border rounded-lg p-2">
                    </div>
                    <button type="submit" class="bg-[#002F34] text-white px-6 py-2 rounded-lg">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Avatar preview
document.getElementById('avatar-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('avatar-preview');
            if (preview.tagName === 'IMG') preview.src = e.target.result;
            else preview.outerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" id="avatar-preview">`;
        };
        reader.readAsDataURL(file);
    }
});

// Location cascade
const provinceSelect = document.getElementById('province-select');
const citySelect = document.getElementById('city-select');

async function loadCities(provinceId) {
    citySelect.innerHTML = '<option value="">Pilih Kota</option>';
    if (!provinceId) { citySelect.disabled = true; return; }
    citySelect.disabled = false;
    try {
        const res = await fetch('<?= BASE_URL ?>api/search.php?action=cities&province_id=' + provinceId);
        const cities = await res.json();
        cities.forEach(city => {
            citySelect.innerHTML += `<option value="${city.id}" ${city.id == <?= $user['city_id'] ?? 0 ?> ? 'selected' : ''}>${city.name}</option>`;
        });
    } catch (e) {
        window.showToast('Gagal memuat daftar kota', 'error');
    }
}

provinceSelect.addEventListener('change', () => loadCities(provinceSelect.value));
<?php if ($user['province_id']): ?>
loadCities(<?= $user['province_id'] ?>);
<?php endif; ?>

// Password change
document.getElementById('password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    
    if (newPassword !== confirmPassword) {
        window.showToast('Konfirmasi password tidak cocok', 'error');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'change_password');
    
    try {
        const res = await fetch('<?= BASE_URL ?>api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast('Password berhasil diubah', 'success');
            form.reset();
        } else {
            window.showToast(data.message || 'Gagal mengubah password', 'error');
        }
    } catch (err) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>