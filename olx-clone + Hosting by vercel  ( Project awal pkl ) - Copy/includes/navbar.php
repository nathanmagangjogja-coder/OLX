<?php
if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';
if (!function_exists('isLoggedIn')) require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();

// Get provinces for location dropdown
$provinces = $pdo->query("SELECT id, name FROM provinces ORDER BY name")->fetchAll();
?>
<header class="sticky top-0 z-40 bg-white shadow-sm transition-shadow" id="main-navbar">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16 md:h-20">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>" class="flex-shrink-0">
                <span class="text-2xl font-bold text-[#002F34]">OLX</span>
                <span class="text-xs text-gray-500 block -mt-1">Clone</span>
            </a>
            
            <!-- Location Selector (Desktop) -->
            <div class="hidden md:flex items-center ml-4 border rounded-lg px-3 py-2 bg-gray-50">
                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <select id="location-selector" class="bg-transparent text-sm focus:outline-none">
                    <option value="">Seluruh Indonesia</option>
                    <?php foreach ($provinces as $prov): ?>
                    <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Search Bar -->
            <div class="flex-1 max-w-2xl mx-4">
                <form action="<?php echo BASE_URL; ?>search.php" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Cari barang bekas..." 
                           class="w-full px-4 py-2 pl-10 pr-20 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#002F34]"
                           id="search-input">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-[#002F34] text-white px-3 py-1 rounded text-sm">Cari</button>
                </form>
                <!-- Search autocomplete dropdown (hidden by default) -->
                <div id="search-autocomplete" class="absolute mt-1 w-full bg-white border rounded-lg shadow-lg hidden z-50 max-h-96 overflow-y-auto"></div>
            </div>
            
            <!-- Right Actions -->
            <div class="flex items-center space-x-3">
                <?php if (isLoggedIn()): ?>
                    <!-- Panel Admin Button (Hanya untuk Admin) -->
                    <?php if (isAdmin()): ?>
                        <a href="<?= BASE_URL ?>admin/" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center transition shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Panel Admin
                        </a>
                    <?php endif; ?>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-gray-700 hover:text-[#002F34]">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?php echo BASE_URL . 'uploads/avatars/' . $currentUser['avatar']; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-[#002F34] text-white flex items-center justify-center">
                                    <?php echo strtoupper(substr($currentUser['full_name'] ?? $currentUser['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="hidden lg:inline"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-lg hidden group-hover:block">
                            <a href="<?php echo BASE_URL; ?>dashboard/" class="block px-4 py-2 hover:bg-gray-100">Dashboard</a>
                            <a href="<?php echo BASE_URL; ?>dashboard/my-listings.php" class="block px-4 py-2 hover:bg-gray-100">Iklan Saya</a>
                            <a href="<?php echo BASE_URL; ?>dashboard/favorites.php" class="block px-4 py-2 hover:bg-gray-100">Favorit</a>
                            <a href="<?php echo BASE_URL; ?>dashboard/messages.php" class="block px-4 py-2 hover:bg-gray-100">Pesan</a>
                            <a href="<?php echo BASE_URL; ?>dashboard/settings.php" class="block px-4 py-2 hover:bg-gray-100">Pengaturan</a>
                            <hr class="my-1">
                            <a href="<?php echo BASE_URL; ?>api/auth.php?action=logout" class="block px-4 py-2 hover:bg-gray-100 text-red-600">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="hidden md:inline-block text-gray-700 hover:text-[#002F34] font-medium">Login</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="hidden md:inline-block text-gray-700 hover:text-[#002F34] font-medium">Daftar</a>
                <?php endif; ?>
                
                <a href="<?php echo isLoggedIn() ? BASE_URL . 'post.php' : BASE_URL . 'login.php?redirect=post.php'; ?>" 
                   class="bg-[#FFCE32] hover:bg-yellow-400 text-[#002F34] font-bold px-4 py-2 rounded-full flex items-center space-x-1 shadow-md transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span class="hidden md:inline">Jual</span>
                </a>
            </div>
        </div>
    </div>
</header>