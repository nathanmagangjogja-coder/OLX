<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Manajemen User';
redirectIfNotAdmin('/login.php');

// Handle actions (ubah role / hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('Invalid CSRF');
    
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId && $userId != $_SESSION['user_id']) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $_SESSION['flash'] = ['message' => 'User berhasil dihapus', 'type' => 'success'];
        } elseif ($action === 'toggle_role') {
            $currentRole = $pdo->query("SELECT role FROM users WHERE id = $userId")->fetchColumn();
            $newRole = $currentRole === 'admin' ? 'user' : 'admin';
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);
            $_SESSION['flash'] = ['message' => 'Role user diperbarui', 'type' => 'success'];
        }
    }
    header('Location: users.php');
    exit;
}

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

$sql = "SELECT id, username, email, full_name, role, created_at FROM users";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$paramIndex = 1;
foreach ($params as $p) $stmt->bindValue($paramIndex++, $p);
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Count total
$countSql = "SELECT COUNT(*) FROM users";
if ($where) $countSql .= " WHERE " . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
foreach ($params as $i => $p) $countStmt->bindValue($i+1, $p);
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

require_once __DIR__ . '/layout.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Manajemen User</h2>
    <p class="text-gray-600">Kelola semua pengguna terdaftar di platform.</p>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Search Bar -->
<form method="GET" class="mb-6 flex gap-2">
    <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari username, email, atau nama..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002F34] focus:border-transparent">
    </div>
    <button type="submit" class="bg-[#002F34] text-white px-5 py-2 rounded-lg hover:bg-[#001a1e] transition font-medium">Cari</button>
    <?php if ($search): ?>
        <a href="users.php" class="border border-gray-300 px-5 py-2 rounded-lg hover:bg-gray-50 transition">Reset</a>
    <?php endif; ?>
</form>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b">
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Username</th>
                    <th class="px-6 py-4 text-left">Nama</th>
                    <th class="px-6 py-4 text-left">Email</th>
                    <th class="px-6 py-4 text-left">Role</th>
                    <th class="px-6 py-4 text-left">Bergabung</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-mono text-gray-500">#<?= $u['id'] ?></td>
                    <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($u['full_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                            <?= $u['role'] === 'admin' ? 'Administrator' : 'User' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" onsubmit="return confirm('Ubah role user ini?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <?= $u['role'] === 'admin' ? 'Jadikan User' : 'Jadikan Admin' ?>
                                </button>
                            </form>
                            <span class="text-gray-300">|</span>
                            <form method="POST" onsubmit="return confirm('Hapus user ini?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                            </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm italic">Akun sendiri</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <p class="text-lg font-medium">Tidak ada user ditemukan</p>
                        <p class="text-sm">Coba ubah kata kunci pencarian</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="mt-6">
        <?= generatePagination($total, $page, $limit) ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>