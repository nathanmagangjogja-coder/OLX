<?php
$pageTitle = 'OLX Clone - Jual Beli Mudah di Indonesia';
$pageDescription = 'Situs jual beli online terpercaya. Temukan berbagai produk bekas berkualitas dengan harga terbaik.';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order LIMIT 12")->fetchAll();

// Fetch popular listings (by view count)
$popularStmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name,
           u.full_name as seller_name,
           u.avatar as seller_avatar,
           u.id as user_id,
           cat.name as category_name,
           cat.id as category_id
    FROM listings l
    LEFT JOIN cities c ON l.city_id = c.id
    JOIN users u ON l.user_id = u.id
    LEFT JOIN categories cat ON l.category_id = cat.id
    WHERE l.status = 'active' AND l.expires_at > NOW()
    ORDER BY l.view_count DESC
    LIMIT 6
");
$popularStmt->execute();
$popularListings = $popularStmt->fetchAll();

// Fetch recent listings
$recentStmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name,
           cat.name as category_name,
           cat.id as category_id
    FROM listings l
    LEFT JOIN cities c ON l.city_id = c.id
    LEFT JOIN categories cat ON l.category_id = cat.id
    WHERE l.status = 'active' AND l.expires_at > NOW()
    ORDER BY l.created_at DESC
    LIMIT 12
");
$recentStmt->execute();
$recentListings = $recentStmt->fetchAll();

/**
 * Fungsi untuk mendapatkan URL gambar placeholder berdasarkan kategori
 */
function getPlaceholderImage($category_id, $category_name, $seed = null) {
    $seed = $seed ?? rand(1, 1000);
    $categoryImages = [
        2 => '1074/800/400',   // Mobil
        4 => '133/800/400',    // Motor
        6 => '160/800/400',    // Handphone & Gadget
        7 => '100/800/400',    // Hobi & Olahraga
        3 => '164/800/400',    // Properti
        'default' => '200/800/400'
    ];
    
    if (isset($categoryImages[$category_id])) {
        return 'https://picsum.photos/id/' . $categoryImages[$category_id];
    }
    
    $name = strtolower($category_name ?? '');
    if (strpos($name, 'mobil') !== false) return 'https://picsum.photos/id/1074/800/400';
    if (strpos($name, 'motor') !== false) return 'https://picsum.photos/id/133/800/400';
    if (strpos($name, 'handphone') !== false || strpos($name, 'gadget') !== false) return 'https://picsum.photos/id/160/800/400';
    if (strpos($name, 'elektronik') !== false) return 'https://picsum.photos/id/119/800/400';
    if (strpos($name, 'properti') !== false) return 'https://picsum.photos/id/164/800/400';
    
    return 'https://picsum.photos/seed/' . $seed . '/800/400';
}
?>

<main id="main-content" class="container mx-auto px-4 py-6">
    <!-- Hero Carousel dengan 4 Slide (Gambar LOKAL) -->
    <div class="relative rounded-xl overflow-hidden mb-8" data-carousel>
        <div class="relative h-48 md:h-80 bg-gray-200">
            <?php
            // 4 Slide dengan gambar lokal
            $slides = [
                [
                    'image' => BASE_URL . 'assets/images/promo-mobil.jpg',
                    'title' => 'Mobil Bekas',
                    'subtitle' => 'Dapatkan harga terbaik untuk mobil impian Anda'
                ],
                [
                    'image' => BASE_URL . 'assets/images/promo-elektronik.jpg',
                    'title' => 'Gadget & Elektronik',
                    'subtitle' => 'iPhone, Samsung, Laptop hingga 30%'
                ],
                [
                    'image' => BASE_URL . 'assets/images/promo-properti.jpg',
                    'title' => 'Rumah & Apartemen',
                    'subtitle' => 'Cari hunian nyaman dengan harga bersahabat'
                ],
                [
                    'image' => BASE_URL . 'assets/images/promo-motor.jpg',
                    'title' => 'Motor Bekas',
                    'subtitle' => 'Motor idaman dengan harga terjangkau'
                ],
            ];
            foreach ($slides as $index => $slide): ?>
            <div data-slide class="absolute inset-0 transition-opacity duration-500 <?= $index === 0 ? '' : 'hidden'; ?>">
                <img src="<?= $slide['image']; ?>" alt="<?= $slide['title']; ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center">
                    <div class="text-white p-8 max-w-lg">
                        <h2 class="text-3xl md:text-4xl font-bold mb-2"><?= $slide['title']; ?></h2>
                        <p class="text-lg md:text-xl opacity-90"><?= $slide['subtitle']; ?></p>
                        <a href="search.php" class="inline-block mt-4 bg-[#FFCE32] text-[#002F34] font-bold px-6 py-2 rounded-full">Lihat Produk</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button data-prev class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2 hover:bg-white z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button data-next class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 rounded-full p-2 hover:bg-white z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-10">
                <?php foreach ($slides as $index => $slide): ?>
                <button data-dot class="w-2 h-2 rounded-full bg-white/60 transition-all duration-300 <?= $index === 0 ? '!bg-white !w-6' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Categories -->
    <section class="mb-8">
        <h2 class="text-xl font-bold mb-4">Kategori</h2>
        <div class="category-scroll flex space-x-4 overflow-x-auto pb-4">
            <a href="search.php" class="category-item flex-shrink-0 w-20 text-center">
                <div class="w-16 h-16 mx-auto bg-[#FFCE32] rounded-full flex items-center justify-center text-2xl">🔍</div>
                <span class="text-sm mt-1 block">Lihat Semua</span>
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="search.php?category=<?= $cat['id']; ?>" class="category-item flex-shrink-0 w-20 text-center hover:scale-105 transition">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center text-2xl">
                    <?= htmlspecialchars($cat['icon']); ?>
                </div>
                <span class="text-xs mt-1 block line-clamp-2"><?= htmlspecialchars($cat['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Popular Listings (Spotlight) -->
    <?php if (!empty($popularListings)): ?>
    <section class="mb-8">
        <h2 class="text-xl font-bold mb-4">Produk Terpopuler</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($popularListings as $listing): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center p-3 border-b">
                    <?php if ($listing['seller_avatar']): ?>
                        <img src="<?= BASE_URL . 'uploads/avatars/' . $listing['seller_avatar']; ?>" class="w-8 h-8 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-sm font-bold">
                            <?= strtoupper(substr($listing['seller_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="ml-2 flex-1">
                        <p class="font-medium text-sm"><?= htmlspecialchars($listing['seller_name']); ?></p>
                        <p class="text-xs text-gray-500"><?= $listing['seller_type'] ?? 'Personal'; ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>profile.php?id=<?= $listing['user_id'] ?>" class="text-[#002F34] text-sm font-medium">View Profile</a>
                </div>
                <a href="detail.php?id=<?= $listing['id']; ?>">
                    <div class="aspect-video bg-gray-200">
                        <?php if ($listing['primary_image']): ?>
                            <img src="<?= BASE_URL . 'uploads/listings/' . $listing['primary_image']; ?>" 
                                 alt="<?= htmlspecialchars($listing['title']); ?>" 
                                 class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                            <img src="<?= getPlaceholderImage($listing['category_id'], $listing['category_name'], $listing['id']); ?>" 
                                 alt="Placeholder <?= $listing['category_name']; ?>" 
                                 class="w-full h-full object-cover" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <h3 class="font-medium line-clamp-1"><?= htmlspecialchars($listing['title']); ?></h3>
                        <p class="text-[#002F34] font-bold"><?= formatRupiah($listing['price']); ?></p>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($listing['city_name'] ?? 'Indonesia'); ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Recent Listings -->
    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Iklan Terbaru</h2>
            <a href="search.php" class="text-[#002F34] text-sm">Lihat Semua &rarr;</a>
        </div>
        <div class="product-grid grid gap-4">
            <?php foreach ($recentListings as $listing): ?>
            <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm">
                <a href="detail.php?id=<?= $listing['id']; ?>">
                    <div class="aspect-square bg-gray-200">
                        <?php if ($listing['primary_image']): ?>
                            <img src="<?= BASE_URL . 'uploads/listings/' . $listing['primary_image']; ?>" 
                                 alt="<?= htmlspecialchars($listing['title']); ?>" 
                                 class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                            <img src="<?= getPlaceholderImage($listing['category_id'], $listing['category_name'], $listing['id']); ?>" 
                                 alt="Placeholder <?= $listing['category_name']; ?>" 
                                 class="w-full h-full object-cover" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <h3 class="font-medium text-gray-800 line-clamp-2 mb-1"><?= htmlspecialchars($listing['title']); ?></h3>
                        <p class="text-[#002F34] font-bold text-lg"><?= formatRupiah($listing['price']); ?></p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($listing['city_name'] ?? 'Indonesia'); ?></span>
                            <span class="text-gray-400 text-xs"><?= timeAgoIndonesia($listing['created_at']); ?></span>
                        </div>
                    </div>
                </a>
                <div class="px-3 pb-3 flex justify-end">
                    <button data-favorite-toggle data-listing-id="<?= $listing['id']; ?>" class="text-gray-400 hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($recentListings) >= 12): ?>
        <div class="text-center mt-6">
            <button id="load-more" class="bg-white border border-[#002F34] text-[#002F34] px-6 py-2 rounded-full hover:bg-gray-50">Muat Lebih Banyak</button>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Recently Viewed (from localStorage) -->
    <section class="mb-8">
        <h2 class="text-xl font-bold mb-4">Terakhir Dilihat</h2>
        <div id="recently-viewed" class="product-grid grid gap-4">
            <!-- Populated by JS -->
        </div>
    </section>
</main>

<script>
// Load more functionality
let page = 2;
const loadMoreBtn = document.getElementById('load-more');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', async function() {
        this.textContent = 'Memuat...';
        try {
            const response = await fetch(BASE_URL + `api/listings.php?action=get_all&page=${page}`);
            const listings = await response.json();
            
            if (listings.length > 0) {
                const container = document.querySelector('.product-grid');
                listings.forEach(listing => {
                    if (typeof createProductCard === 'function') {
                        container.insertAdjacentHTML('beforeend', createProductCard(listing));
                    } else {
                        const card = document.createElement('div');
                        card.className = 'product-card bg-white rounded-lg overflow-hidden shadow-sm';
                        card.innerHTML = `
                            <a href="detail.php?id=${listing.id}">
                                <div class="aspect-square bg-gray-200">
                                    <img src="${listing.primary_image ? BASE_URL + 'uploads/listings/' + listing.primary_image : 'https://picsum.photos/seed/'+listing.id+'/200/200'}" 
                                         class="w-full h-full object-cover" loading="lazy">
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium line-clamp-2">${listing.title}</h3>
                                    <p class="text-[#002F34] font-bold text-lg">${formatRupiah(listing.price)}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-gray-500 text-sm">${listing.city_name || 'Indonesia'}</span>
                                        <span class="text-gray-400 text-xs">${timeAgo(listing.created_at)}</span>
                                    </div>
                                </div>
                            </a>
                        `;
                        container.appendChild(card);
                    }
                });
                page++;
                this.textContent = 'Muat Lebih Banyak';
                if (listings.length < 20) this.remove();
            } else {
                this.remove();
            }
        } catch (error) {
            this.textContent = 'Coba Lagi';
        }
    });
}

// Helper (fallback)
function formatRupiah(price) {
    return 'Rp ' + Number(price).toLocaleString('id');
}
function timeAgo(datetime) {
    return 'Baru saja';
}

// ========== INISIALISASI CAROUSEL ==========
(function() {
    const carousel = document.querySelector('[data-carousel]');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('[data-slide]');
    const dots = carousel.querySelectorAll('[data-dot]');
    const prevBtn = carousel.querySelector('[data-prev]');
    const nextBtn = carousel.querySelector('[data-next]');
    
    if (!slides.length) return;
    
    let currentSlide = 0;
    let interval;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
        });
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('!bg-white', '!w-6');
                dot.classList.remove('bg-white/60');
            } else {
                dot.classList.remove('!bg-white', '!w-6');
                dot.classList.add('bg-white/60');
            }
        });
        currentSlide = index;
    }
    
    function nextSlide() {
        showSlide((currentSlide + 1) % slides.length);
    }
    
    function prevSlide() {
        showSlide((currentSlide - 1 + slides.length) % slides.length);
    }
    
    function startAutoPlay() {
        stopAutoPlay();
        interval = setInterval(nextSlide, 4000);
    }
    
    function stopAutoPlay() {
        if (interval) clearInterval(interval);
    }
    
    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoPlay(); startAutoPlay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoPlay(); startAutoPlay(); });
    
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => { showSlide(i); stopAutoPlay(); startAutoPlay(); });
    });
    
    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);
    
    showSlide(0);
    startAutoPlay();
})();
</script>

<?php require_once 'includes/footer.php'; ?>