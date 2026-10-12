<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Cari Iklan - OLX Clone';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$q = $_GET['q'] ?? '';
$category = intval($_GET['category'] ?? 0);
$subcategory = intval($_GET['subcategory'] ?? 0); // <-- TAMBAHAN
$province = intval($_GET['province'] ?? 0);
$city = intval($_GET['city'] ?? 0);
$minPrice = intval($_GET['min_price'] ?? 0);
$maxPrice = intval($_GET['max_price'] ?? 0);
$condition = $_GET['condition'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));

// Fetch categories for filter
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
$provinces = $pdo->query("SELECT * FROM provinces ORDER BY name")->fetchAll();

// Build query
$where = ["l.status = 'active'", "l.expires_at > NOW()"];
$params = [];

if (!empty($q)) {
    $where[] = "MATCH(l.title, l.description) AGAINST(? IN BOOLEAN MODE)";
    $params[] = $q;
}
if ($category > 0) {
    $where[] = "l.category_id = ?";
    $params[] = $category;
}
if ($subcategory > 0) { // <-- TAMBAHAN
    $where[] = "l.subcategory_id = ?";
    $params[] = $subcategory;
}
if ($province > 0) {
    $where[] = "l.province_id = ?";
    $params[] = $province;
}
if ($city > 0) {
    $where[] = "l.city_id = ?";
    $params[] = $city;
}
if ($minPrice > 0) {
    $where[] = "l.price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[] = "l.price <= ?";
    $params[] = $maxPrice;
}
if ($condition) {
    $where[] = "l.condition_type = ?";
    $params[] = $condition;
}

$orderBy = "l.created_at DESC";
if ($sort === 'price_low') $orderBy = "l.price ASC";
elseif ($sort === 'price_high') $orderBy = "l.price DESC";

$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT l.*, 
               (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
               c.name as city_name
        FROM listings l
        LEFT JOIN cities c ON l.city_id = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param);
}
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$listings = $stmt->fetchAll();

// Count total
$countSql = "SELECT COUNT(*) FROM listings l WHERE " . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
foreach ($params as $i => $param) {
    $countStmt->bindValue($i+1, $param);
}
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Filters Sidebar (Desktop) -->
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <div class="bg-white p-4 rounded-lg shadow-sm sticky top-24">
                <h3 class="font-bold mb-4">Filter</h3>
                <form method="GET" id="filter-form">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                    
                    <!-- Category -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Kategori</label>
                        <select name="category" id="category-select" class="w-full border rounded-lg p-2">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Subcategory (TAMBAHAN) -->
                    <div class="mb-4" id="subcategory-wrapper" style="<?php echo $category ? '' : 'display: none;'; ?>">
                        <label class="block text-sm font-medium mb-2">Subkategori</label>
                        <select name="subcategory" id="subcategory-select" class="w-full border rounded-lg p-2">
                            <option value="">Semua Subkategori</option>
                            <?php
                            if ($category) {
                                $subStmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order");
                                $subStmt->execute([$category]);
                                while ($sub = $subStmt->fetch()) {
                                    $selected = ($subcategory == $sub['id']) ? 'selected' : '';
                                    echo "<option value='{$sub['id']}' $selected>" . htmlspecialchars($sub['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Location -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Provinsi</label>
                        <select name="province" id="province-select" class="w-full border rounded-lg p-2">
                            <option value="">Semua Provinsi</option>
                            <?php foreach ($provinces as $prov): ?>
                            <option value="<?php echo $prov['id']; ?>" <?php echo $province == $prov['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Kota</label>
                        <select name="city" id="city-select" class="w-full border rounded-lg p-2">
                            <option value="">Semua Kota</option>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Harga</label>
                        <div class="flex space-x-2">
                            <input type="number" name="min_price" placeholder="Min" value="<?php echo $minPrice ?: ''; ?>" class="w-1/2 border rounded-lg p-2">
                            <input type="number" name="max_price" placeholder="Max" value="<?php echo $maxPrice ?: ''; ?>" class="w-1/2 border rounded-lg p-2">
                        </div>
                    </div>
                    
                    <!-- Condition -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Kondisi</label>
                        <select name="condition" class="w-full border rounded-lg p-2">
                            <option value="">Semua</option>
                            <option value="new" <?php echo $condition === 'new' ? 'selected' : ''; ?>>Baru</option>
                            <option value="used" <?php echo $condition === 'used' ? 'selected' : ''; ?>>Bekas</option>
                        </select>
                    </div>
                    
                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Urutkan</label>
                        <select name="sort" class="w-full border rounded-lg p-2">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Harga Terendah</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-[#002F34] text-white py-2 rounded-lg">Terapkan Filter</button>
                    <a href="search.php" class="block text-center mt-2 text-sm text-gray-500">Reset Filter</a>
                </form>
            </div>
        </aside>
        
        <!-- Results -->
        <div class="flex-1">
            <!-- Active Filters -->
            <div class="flex flex-wrap gap-2 mb-4">
                <?php if ($q): ?>
                <span class="bg-gray-200 px-3 py-1 rounded-full text-sm flex items-center">
                    Pencarian: <?php echo htmlspecialchars($q); ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['q' => ''])); ?>" class="ml-2">&times;</a>
                </span>
                <?php endif; ?>
                <!-- Add other active filter chips similarly -->
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <p class="text-gray-600"><?php echo number_format($total, 0, ',', '.'); ?> iklan ditemukan</p>
                <div class="flex items-center space-x-2">
                    <button id="grid-view" class="p-2 rounded <?php echo ($_COOKIE['view_mode'] ?? 'grid') === 'grid' ? 'bg-gray-200' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button id="list-view" class="p-2 rounded <?php echo ($_COOKIE['view_mode'] ?? 'grid') === 'list' ? 'bg-gray-200' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
            
            <?php if (empty($listings)): ?>
            <div class="bg-white rounded-lg p-8 text-center">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-bold mb-2">Tidak ada iklan ditemukan</h3>
                <p class="text-gray-600 mb-4">Coba ubah filter atau kata kunci pencarian</p>
                <a href="post.php" class="inline-block bg-[#FFCE32] text-[#002F34] font-bold px-6 py-2 rounded-full">Pasang Iklan</a>
            </div>
            <?php else: ?>
            <div class="product-grid grid gap-4" id="results-container">
                <?php foreach ($listings as $listing): ?>
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm">
                    <a href="detail.php?id=<?php echo $listing['id']; ?>">
                        <div class="aspect-square bg-gray-200">
                            <?php if ($listing['primary_image']): ?>
                                <img src="<?php echo BASE_URL . 'uploads/listings/' . $listing['primary_image']; ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-gray-800 line-clamp-2 mb-1"><?php echo htmlspecialchars($listing['title']); ?></h3>
                            <p class="text-[#002F34] font-bold text-lg"><?php echo formatRupiah($listing['price']); ?></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-gray-500 text-sm"><?php echo htmlspecialchars($listing['city_name'] ?? ''); ?></span>
                                <span class="text-gray-400 text-xs"><?php echo timeAgoIndonesia($listing['created_at']); ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="px-3 pb-3 flex justify-end">
                        <button data-favorite-toggle data-listing-id="<?php echo $listing['id']; ?>" class="text-gray-400 hover:text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-8">
                <?php 
                $paginationParams = $_GET;
                echo generatePagination($total, $page, $limit);
                ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Mobile Filter Modal (Bottom Sheet) -->
<div id="filter-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden">
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl p-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Filter</h3>
            <button id="close-filter" class="text-gray-500">&times;</button>
        </div>
        <!-- Same filter form as desktop, with id="mobile-filter-form" -->
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// Location cascade
document.getElementById('province-select')?.addEventListener('change', async function() {
    const provinceId = this.value;
    const citySelect = document.getElementById('city-select');
    citySelect.innerHTML = '<option value="">Semua Kota</option>';
    
    if (provinceId) {
        const response = await fetch(BASE_URL + `api/search.php?action=cities&province_id=${provinceId}`);
        const cities = await response.json();
        cities.forEach(city => {
            citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
        });
    }
});

// Trigger if province preselected
<?php if ($province > 0): ?>
document.getElementById('province-select')?.dispatchEvent(new Event('change'));
setTimeout(() => {
    document.getElementById('city-select').value = '<?php echo $city; ?>';
}, 500);
<?php endif; ?>

// ========== SUBKATEGORI ==========
const categorySelect = document.getElementById('category-select');
const subcategoryWrapper = document.getElementById('subcategory-wrapper');
const subcategorySelect = document.getElementById('subcategory-select');

if (categorySelect) {
    categorySelect.addEventListener('change', async function() {
        const categoryId = this.value;
        if (!categoryId) {
            subcategoryWrapper.style.display = 'none';
            subcategorySelect.innerHTML = '<option value="">Semua Subkategori</option>';
            return;
        }
        try {
            const res = await fetch(BASE_URL + 'api/subcategories.php?action=get_by_category&category_id=' + categoryId);
            const subs = await res.json();
            subcategorySelect.innerHTML = '<option value="">Semua Subkategori</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                subcategorySelect.appendChild(opt);
            });
            subcategoryWrapper.style.display = 'block';
        } catch (e) {
            console.error('Gagal memuat subkategori', e);
        }
    });
    
    // Jika kategori sudah terpilih saat load (misal dari URL), muat subkategorinya
    <?php if ($category > 0): ?>
    (async function() {
        try {
            const res = await fetch(BASE_URL + 'api/subcategories.php?action=get_by_category&category_id=<?= $category ?>');
            const subs = await res.json();
            subcategorySelect.innerHTML = '<option value="">Semua Subkategori</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                if (sub.id == <?= $subcategory ?>) opt.selected = true;
                subcategorySelect.appendChild(opt);
            });
            subcategoryWrapper.style.display = 'block';
        } catch (e) {}
    })();
    <?php endif; ?>
}
</script>

<?php require_once 'includes/footer.php'; ?>