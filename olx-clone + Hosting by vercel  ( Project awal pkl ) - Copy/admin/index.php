<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Dashboard';
redirectIfNotAdmin('/login.php');

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalListings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$activeListings = $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn();
$pendingReports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

$recentUsers = $pdo->query("SELECT id, username, email, full_name, created_at, role FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentListings = $pdo->query("
    SELECT l.id, l.title, l.price, l.status, l.created_at, u.username as seller
    FROM listings l JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

require_once __DIR__ . '/layout.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Selamat datang, <?= htmlspecialchars($adminUser['full_name'] ?? $adminUser['username']) ?>!</h2>
    <p class="text-gray-600">Pantau aktivitas platform hari ini.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-stat bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total User</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($totalUsers) ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>
    </div>
    
    <div class="card-stat bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Iklan</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($totalListings) ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>
    </div>
    
    <div class="card-stat bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Iklan Aktif</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($activeListings) ?></p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
    
    <div class="card-stat bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Laporan Pending</p>
                <p class="text-3xl font-bold text-red-600"><?= number_format($pendingReports) ?></p>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- Recent Users & Listings -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">User Terbaru</h3>
            <a href="users.php" class="text-sm text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Username</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-6 py-3 text-left">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($recentUsers as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 text-xs rounded-full <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Iklan Terbaru</h3>
            <a href="listings.php" class="text-sm text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Judul</th>
                        <th class="px-6 py-3 text-left">Penjual</th>
                        <th class="px-6 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($recentListings as $l): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium"><?= htmlspecialchars($l['title']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($l['seller']) ?></td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 text-xs rounded-full <?= $l['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($l['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>