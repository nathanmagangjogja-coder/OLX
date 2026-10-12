<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Detail Iklan - OLX Clone';
$pageDescription = '';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch listing dengan tambahan rating penjual
$stmt = $pdo->prepare("
    SELECT l.*, u.full_name as seller_name, u.avatar as seller_avatar, u.phone as seller_phone,
           u.last_seen, u.created_at as seller_since, u.id as seller_id,
           c.name as city_name, p.name as province_name,
           cat.name as category_name,
           (SELECT AVG(rating) FROM reviews WHERE reviewed_id = u.id) as seller_rating,
           (SELECT COUNT(*) FROM reviews WHERE reviewed_id = u.id) as seller_review_count
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN cities c ON l.city_id = c.id
    LEFT JOIN provinces p ON l.province_id = p.id
    LEFT JOIN categories cat ON l.category_id = cat.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    header('Location: ' . BASE_URL);
    exit;
}

// Increment view count
$pdo->prepare("UPDATE listings SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

// Get images
$imgStmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY sort_order");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

// Check if favorited
$isFavorited = false;
if (isLoggedIn()) {
    $favStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
    $favStmt->execute([$_SESSION['user_id'], $id]);
    $isFavorited = $favStmt->rowCount() > 0;
}

// Similar listings
$similarStmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name
    FROM listings l
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE l.category_id = ? AND l.id != ? AND l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 8
");
$similarStmt->execute([$listing['category_id'], $id]);
$similarListings = $similarStmt->fetchAll();

$pageTitle = $listing['title'] . ' - OLX Clone';
$pageDescription = substr(strip_tags($listing['description']), 0, 160);
$ogImage = !empty($images) ? BASE_URL . 'uploads/listings/' . $images[0]['filename'] : '';
$canonical = BASE_URL . 'detail.php?id=' . $id;

// Schema markup
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $listing['title'],
    'description' => $listing['description'],
    'image' => $ogImage,
    'offers' => [
        '@type' => 'Offer',
        'price' => $listing['price'],
        'priceCurrency' => 'IDR',
        'availability' => 'https://schema.org/InStock'
    ]
];
$schemaMarkup = json_encode($schema, JSON_UNESCAPED_SLASHES);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Build map query dari alamat yang tersedia
$mapQuery = urlencode(trim(
    ($listing['address'] ?? '') . ', ' . 
    ($listing['city_name'] ?? '') . ', ' . 
    ($listing['province_name'] ?? '')
));
?>

<main class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4">
        <a href="<?= BASE_URL ?>" class="text-gray-500 hover:text-[#002F34]">Home</a> &gt;
        <a href="search.php?category=<?= $listing['category_id'] ?>" class="text-gray-500 hover:text-[#002F34]"><?= htmlspecialchars($listing['category_name']) ?></a> &gt;
        <span class="text-gray-700"><?= htmlspecialchars($listing['title']) ?></span>
    </nav>
    
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Left Column: Gallery & Details -->
        <div class="flex-1">
            <!-- Image Gallery -->
            <div class="bg-white rounded-lg p-4 mb-4">
                <div class="relative">
                    <div id="main-image" class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                        <?php if (!empty($images)): ?>
                        <img src="<?= BASE_URL ?>uploads/listings/<?= $images[0]['filename'] ?>" alt="<?= htmlspecialchars($listing['title']) ?>" class="w-full h-full object-contain" id="gallery-main">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <button id="gallery-prev" class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button id="gallery-next" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="flex mt-2 space-x-2 overflow-x-auto pb-2" id="thumbnail-container">
                    <?php foreach ($images as $index => $img): ?>
                    <img src="<?= BASE_URL ?>uploads/listings/<?= $img['filename'] ?>" 
                         data-index="<?= $index ?>"
                         class="w-16 h-16 object-cover rounded cursor-pointer border-2 <?= $index === 0 ? 'border-[#002F34]' : 'border-transparent' ?>"
                         onclick="setMainImage(<?= $index ?>)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Details -->
            <div class="bg-white rounded-lg p-4 mb-4">
                <div class="flex items-start justify-between mb-2">
                    <h1 class="text-xl font-bold"><?= htmlspecialchars($listing['title']) ?></h1>
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl font-bold text-[#002F34]"><?= formatRupiah($listing['price']) ?></span>
                        <?php if ($listing['price_type'] === 'negotiable'): ?>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Nego</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-sm text-gray-500 mb-4">
                    <span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> <?= htmlspecialchars($listing['city_name'] . ', ' . $listing['province_name']) ?></span>
                    <span>👁️ <?= $listing['view_count'] ?> dilihat</span>
                    <span class="bg-<?= $listing['condition_type'] === 'new' ? 'green' : 'gray' ?>-100 text-<?= $listing['condition_type'] === 'new' ? 'green' : 'gray' ?>-800 px-2 py-1 rounded text-xs"><?= $listing['condition_type'] === 'new' ? 'Baru' : 'Bekas' ?></span>
                </div>
                
                <!-- Description -->
                <div class="border-t pt-4">
                    <h2 class="font-bold mb-2">Deskripsi</h2>
                    <div id="description" class="prose max-w-none">
                        <p class="line-clamp-3" id="desc-preview"><?= nl2br(htmlspecialchars($listing['description'])) ?></p>
                        <button id="read-more" class="text-[#002F34] font-medium mt-2">Baca selengkapnya</button>
                        <div id="desc-full" class="hidden"><?= nl2br(htmlspecialchars($listing['description'])) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Seller Info & Map (Mobile) -->
            <div class="lg:hidden space-y-4">
                <!-- Seller Card Mobile -->
                <div class="bg-white rounded-lg p-4">
                    <?php
                    $sellerId = $listing['seller_id'];
                    $activeStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'active'");
                    $activeStmt->execute([$sellerId]);
                    $activeListings = $activeStmt->fetchColumn();
                    $sellerRating = round($listing['seller_rating'] ?? 0, 1);
                    $reviewCount = $listing['seller_review_count'] ?? 0;
                    ?>
                    <div class="flex items-center space-x-3 mb-4">
                        <?php if (!empty($listing['seller_avatar'])): ?>
                            <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($listing['seller_avatar']) ?>" 
                                 alt="<?= htmlspecialchars($listing['seller_name']) ?>" 
                                 class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-[#002F34] text-white flex items-center justify-center text-xl font-bold">
                                <?= strtoupper(substr($listing['seller_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-bold">
                                <a href="<?= BASE_URL ?>profile.php?id=<?= $sellerId ?>" class="hover:text-[#002F34]">
                                    <?= htmlspecialchars($listing['seller_name']) ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500">Aktif <?= timeAgoIndonesia($listing['last_seen']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-3">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <?= $sellerRating ?> (<?= $reviewCount ?>)
                        </span>
                        <span>•</span>
                        <span><?= $activeListings ?> iklan aktif</span>
                    </div>
                    <a href="<?= BASE_URL ?>profile.php?id=<?= $sellerId ?>" class="text-[#002F34] text-sm font-medium">Lihat semua iklan &rarr;</a>
                </div>
                
                <!-- Map Mobile -->
                <div class="bg-white rounded-lg p-4">
                    <h3 class="font-bold mb-2">Lokasi</h3>
                    <div class="bg-gray-200 rounded-lg overflow-hidden" style="height: 180px;">
                        <iframe width="100%" height="180" style="border:0" loading="lazy" allowfullscreen 
                            src="https://www.google.com/maps?q=<?= $mapQuery ?>&output=embed"></iframe>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 truncate">📍 <?= htmlspecialchars($listing['address'] ?? $listing['city_name']) ?></p>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $mapQuery ?>" target="_blank" class="text-[#002F34] text-sm font-medium mt-1 inline-block">Buka di Google Maps →</a>
                </div>
            </div>
            
            <!-- Similar Listings -->
            <?php if (!empty($similarListings)): ?>
            <div class="bg-white rounded-lg p-4 mt-4">
                <h2 class="font-bold mb-4">Iklan Serupa</h2>
                <div class="product-grid grid gap-4">
                    <?php foreach ($similarListings as $sim): ?>
                    <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm">
                        <a href="detail.php?id=<?= $sim['id'] ?>">
                            <div class="aspect-square bg-gray-200">
                                <?php if ($sim['primary_image']): ?>
                                    <img src="<?= BASE_URL ?>uploads/listings/<?= $sim['primary_image'] ?>" class="w-full h-full object-cover" loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3">
                                <h3 class="font-medium line-clamp-2"><?= htmlspecialchars($sim['title']) ?></h3>
                                <p class="text-[#002F34] font-bold"><?= formatRupiah($sim['price']) ?></p>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Sidebar: Seller & Actions (Desktop) -->
        <div class="hidden lg:block w-80 flex-shrink-0">
            <div class="sticky top-24 space-y-4">
                <!-- Seller Card Desktop -->
                <div class="bg-white rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-4">
                        <?php if (!empty($listing['seller_avatar'])): ?>
                            <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($listing['seller_avatar']) ?>" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-[#002F34] text-white flex items-center justify-center text-xl font-bold">
                                <?= strtoupper(substr($listing['seller_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-bold"><a href="<?= BASE_URL ?>profile.php?id=<?= $sellerId ?>" class="hover:text-[#002F34]"><?= htmlspecialchars($listing['seller_name']) ?></a></h3>
                            <p class="text-sm text-gray-500">Aktif <?= timeAgoIndonesia($listing['last_seen']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-3">
                        <span class="flex items-center"><svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg> <?= $sellerRating ?> (<?= $reviewCount ?>)</span>
                        <span>•</span>
                        <span><?= $activeListings ?> iklan aktif</span>
                    </div>
                    <a href="<?= BASE_URL ?>profile.php?id=<?= $sellerId ?>" class="text-[#002F34] text-sm font-medium">Lihat semua iklan &rarr;</a>
                </div>
                
                <!-- Action Buttons -->
                <div class="bg-white rounded-lg p-4">
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $listing['seller_phone']) ?>?text=Saya%20tertarik%20dengan%20iklan%20<?= urlencode($listing['title']) ?>" target="_blank" class="block w-full bg-green-500 text-white text-center py-3 rounded-lg font-bold mb-2 hover:bg-green-600">Chat via WhatsApp</a>
                    <button data-phone-reveal data-listing-id="<?= $id ?>" data-full-phone="<?= htmlspecialchars($listing['seller_phone']) ?>" class="w-full border border-[#002F34] text-[#002F34] py-3 rounded-lg font-bold mb-2 hover:bg-gray-50"><span data-phone-number><?= maskPhone($listing['seller_phone']) ?></span> (Klik untuk lihat)</button>
                    <button data-favorite-toggle data-listing-id="<?= $id ?>" class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-bold hover:bg-gray-50 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2 <?= $isFavorited ? 'text-red-500 fill-current' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <?= $isFavorited ? 'Hapus dari Favorit' : 'Tambahkan ke Favorit'; ?>
                    </button>
                    <!-- Tombol Laporkan (Desktop) -->
                    <button onclick="openReportModal()" class="w-full border border-red-300 text-red-600 py-2 rounded-lg font-medium hover:bg-red-50 flex items-center justify-center mt-2">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Laporkan Iklan
                    </button>
                </div>
                
                <!-- Map Desktop -->
                <div class="bg-white rounded-lg p-4">
                    <h3 class="font-bold mb-2">Lokasi</h3>
                    <div class="bg-gray-200 rounded-lg overflow-hidden" style="height: 200px;">
                        <iframe width="100%" height="200" style="border:0" loading="lazy" allowfullscreen 
                            src="https://www.google.com/maps?q=<?= $mapQuery ?>&output=embed"></iframe>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 truncate">📍 <?= htmlspecialchars($listing['address'] ?? $listing['city_name']) ?></p>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $mapQuery ?>" target="_blank" class="text-[#002F34] text-sm font-medium hover:underline mt-1 inline-block">Buka di Google Maps →</a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Mobile Action Bar -->
<div class="lg:hidden fixed bottom-16 left-0 right-0 bg-white border-t p-3 flex space-x-2 z-30">
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $listing['seller_phone']) ?>" target="_blank" class="flex-1 bg-green-500 text-white py-2 rounded-lg font-bold text-center">WhatsApp</a>
    <button data-phone-reveal data-full-phone="<?= htmlspecialchars($listing['seller_phone']) ?>" class="flex-1 border border-[#002F34] text-[#002F34] py-2 rounded-lg font-bold">Telepon</button>
    <button data-favorite-toggle data-listing-id="<?= $id ?>" class="px-4 border border-gray-300 rounded-lg">
        <svg class="w-6 h-6 <?= $isFavorited ? 'text-red-500 fill-current' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </button>
    <!-- Tombol Laporkan (Mobile) -->
    <button onclick="openReportModal()" class="px-4 border border-red-300 text-red-600 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </button>
</div>

<!-- Modal Laporan -->
<div id="report-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">Laporkan Iklan</h2>
        <form id="report-form">
            <?= csrf_field() ?>
            <input type="hidden" name="listing_id" value="<?= $id ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Alasan Pelaporan <span class="text-red-500">*</span></label>
                <select name="reason" required class="w-full border rounded-lg p-2">
                    <option value="">Pilih alasan</option>
                    <option value="spam">Spam / Iklan tidak relevan</option>
                    <option value="fraud">Penipuan</option>
                    <option value="prohibited">Barang terlarang</option>
                    <option value="duplicate">Iklan duplikat</option>
                    <option value="offensive">Konten ofensif</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" class="w-full border rounded-lg p-2" placeholder="Jelaskan lebih detail..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeReportModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Gallery functions
const images = <?= json_encode(array_column($images, 'filename')) ?>;
let currentImageIndex = 0;
const mainImg = document.getElementById('gallery-main');

function setMainImage(index) {
    currentImageIndex = index;
    mainImg.src = '<?= BASE_URL ?>uploads/listings/' + images[index];
    document.querySelectorAll('#thumbnail-container img').forEach((thumb, i) => {
        thumb.classList.toggle('border-[#002F34]', i === index);
        thumb.classList.toggle('border-transparent', i !== index);
    });
}
document.getElementById('gallery-prev')?.addEventListener('click', () => {
    currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
    setMainImage(currentImageIndex);
});
document.getElementById('gallery-next')?.addEventListener('click', () => {
    currentImageIndex = (currentImageIndex + 1) % images.length;
    setMainImage(currentImageIndex);
});

// Read more
document.getElementById('read-more')?.addEventListener('click', function() {
    document.getElementById('desc-preview').classList.add('hidden');
    document.getElementById('desc-full').classList.remove('hidden');
    this.classList.add('hidden');
});

// Phone reveal
document.querySelectorAll('[data-phone-reveal]').forEach(btn => {
    btn.addEventListener('click', function() {
        const phoneSpan = this.querySelector('[data-phone-number]');
        const fullPhone = this.dataset.fullPhone;
        if (phoneSpan && fullPhone) {
            phoneSpan.textContent = fullPhone;
            this.classList.add('pointer-events-none', 'opacity-50');
            fetch('<?= BASE_URL ?>api/listings.php?action=track_phone&id=' + this.dataset.listingId).catch(e=>{});
        }
    });
});

// Favorite toggle
document.querySelectorAll('[data-favorite-toggle]').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const icon = this.querySelector('svg');
        icon.classList.toggle('text-red-500');
        icon.classList.toggle('fill-current');
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('listing_id', this.dataset.listingId);
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
        try {
            const res = await fetch('<?= BASE_URL ?>api/favorites.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Gagal');
            window.showToast(data.action === 'added' ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit', 'success');
        } catch (err) {
            icon.classList.toggle('text-red-500');
            icon.classList.toggle('fill-current');
            window.showToast('Gagal memperbarui favorit', 'error');
        }
    });
});

// ========== MODAL LAPORAN ==========
function openReportModal() {
    <?php if (!isLoggedIn()): ?>
        window.showToast('Silakan login untuk melaporkan iklan', 'warning');
        window.location.href = '<?= BASE_URL ?>login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>';
        return;
    <?php endif; ?>
    document.getElementById('report-modal').classList.remove('hidden');
}

function closeReportModal() {
    document.getElementById('report-modal').classList.add('hidden');
}

document.getElementById('report-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReportModal();
});

document.getElementById('report-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('<?= BASE_URL ?>api/reports.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast(data.message, 'success');
            closeReportModal();
            this.reset();
        } else {
            window.showToast(data.message || 'Gagal mengirim laporan', 'error');
        }
    } catch (err) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
});

fetch('<?= BASE_URL ?>api/listings.php?action=increment_view&id=<?= $id ?>');
if (typeof addToRecentlyViewed === 'function') addToRecentlyViewed(<?= $id ?>);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>