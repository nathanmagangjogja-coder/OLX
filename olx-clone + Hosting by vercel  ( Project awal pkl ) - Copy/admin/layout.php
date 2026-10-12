<?php
// admin/layout.php
if (!isset($pageTitle)) $pageTitle = 'Admin Panel';
$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - OLX Admin</title>
    <!-- Tailwind + Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
    <!-- Chart.js (opsional untuk chart) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { background: linear-gradient(180deg, #002F34 0%, #001a1e 100%); }
        .card-stat { transition: all 0.2s ease; }
        .card-stat:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar w-72 text-white flex-shrink-0 flex flex-col">
            <div class="p-6">
                <h1 class="text-2xl font-bold tracking-tight flex items-center">
                    <span class="bg-white/20 p-2 rounded-lg mr-3">📦</span>
                    OLX Admin
                </h1>
                <p class="text-white/60 text-sm mt-1">Panel Manajemen</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <a href="<?= BASE_URL ?>admin/index.php" class="flex items-center px-4 py-3 rounded-lg transition <?= $currentPage == 'index.php' ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="<?= BASE_URL ?>admin/users.php" class="flex items-center px-4 py-3 rounded-lg transition <?= $currentPage == 'users.php' ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Manajemen User
                </a>
                <a href="<?= BASE_URL ?>admin/listings.php" class="flex items-center px-4 py-3 rounded-lg transition <?= $currentPage == 'listings.php' ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Manajemen Iklan
                </a>
                <a href="<?= BASE_URL ?>admin/reports.php" class="flex items-center px-4 py-3 rounded-lg transition <?= $currentPage == 'reports.php' ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Laporan
                    <?php
                    // Hitung laporan pending untuk badge
                    $pendingCount = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
                    if ($pendingCount > 0):
                    ?>
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                
                <hr class="my-4 border-white/20">
                
                <a href="<?= BASE_URL ?>api/auth.php?action=logout" class="flex items-center px-4 py-3 rounded-lg text-white/80 hover:bg-white/10 transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </a>
                <a href="<?= BASE_URL ?>" class="flex items-center px-4 py-3 rounded-lg text-white/60 hover:bg-white/10 transition text-sm">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Situs
                </a>
            </nav>
            
            <div class="p-4 border-t border-white/20 text-white/60 text-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-bold">
                        <?= strtoupper(substr($adminUser['full_name'] ?? $adminUser['username'], 0, 1)) ?>
                    </div>
                    <div class="ml-2">
                        <p class="font-medium text-white"><?= htmlspecialchars($adminUser['full_name'] ?? $adminUser['username']) ?></p>
                        <p class="text-xs">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-10">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-gray-800"><?= $pageTitle ?></h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500"><?= date('l, d M Y') ?></span>
                    </div>
                </div>
            </header>
            
            <div class="p-6">
                <!-- Toast Container -->
                <div id="toast-container" class="toast-container"></div>
                <script src="<?= BASE_URL ?>assets/js/toast.js"></script>