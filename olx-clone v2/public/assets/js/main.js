// Main JavaScript Module
document.addEventListener('DOMContentLoaded', function() {
    // Navbar shadow on scroll
    const navbar = document.getElementById('main-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }
    
    // Initialize all components
    initHeroCarousel();
    initCategoryScroll();
    initFavoriteToggle();
    initPhoneReveal();
    initSearchAutocomplete();
    initRecentlyViewed();
    initToastSystem();
    initConfirmDialogs();
});

// Toast Notification System
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-lg shadow-lg text-white flex items-center justify-between transition transform translate-x-full ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    toast.innerHTML = `
        <span>${message}</span>
        <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(toast);
    
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Hero Carousel
function initHeroCarousel() {
    const carousel = document.querySelector('[data-carousel]');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('[data-slide]');
    const dots = carousel.querySelectorAll('[data-dot]');
    const prevBtn = carousel.querySelector('[data-prev]');
    const nextBtn = carousel.querySelector('[data-next]');
    let currentSlide = 0;
    let interval;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
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
        interval = setInterval(nextSlide, 4000);
    }
    
    function stopAutoPlay() {
        clearInterval(interval);
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
}

// Category Horizontal Scroll
function initCategoryScroll() {
    const container = document.querySelector('.category-scroll');
    if (!container) return;
    
    let isDown = false;
    let startX, scrollLeft;
    
    container.addEventListener('mousedown', (e) => {
        isDown = true;
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });
    
    container.addEventListener('mouseleave', () => isDown = false);
    container.addEventListener('mouseup', () => isDown = false);
    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
    });
}

// Favorite Toggle (Optimistic UI)
function initFavoriteToggle() {
    document.querySelectorAll('[data-favorite-toggle]').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const listingId = this.dataset.listingId;
            const icon = this.querySelector('svg');
            const wasFavorited = icon.classList.contains('text-red-500');
            
            // Optimistic update
            icon.classList.toggle('text-red-500');
            icon.classList.toggle('fill-current');
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle');
                formData.append('listing_id', listingId);
                
                const response = await fetch(BASE_URL + 'api/favorites.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (!data.success) {
                    // Revert
                    icon.classList.toggle('text-red-500');
                    icon.classList.toggle('fill-current');
                    showToast(data.message || 'Gagal memperbarui favorit', 'error');
                } else {
                    showToast(wasFavorited ? 'Dihapus dari favorit' : 'Ditambahkan ke favorit', 'success');
                }
            } catch (error) {
                // Revert
                icon.classList.toggle('text-red-500');
                icon.classList.toggle('fill-current');
                showToast('Terjadi kesalahan', 'error');
            }
        });
    });
}

// Phone Reveal with tracking
function initPhoneReveal() {
    document.querySelectorAll('[data-phone-reveal]').forEach(btn => {
        btn.addEventListener('click', async function() {
            const phoneSpan = this.parentElement.querySelector('[data-phone-number]');
            const listingId = this.dataset.listingId;
            const fullPhone = this.dataset.fullPhone;
            
            if (phoneSpan) {
                phoneSpan.textContent = fullPhone;
                this.classList.add('hidden');
                
                // Track click
                if (listingId) {
                    fetch(BASE_URL + `api/listings.php?action=track_phone&id=${listingId}`);
                }
            }
        });
    });
}

// Search Autocomplete with Debounce
function initSearchAutocomplete() {
    const input = document.getElementById('search-input');
    const dropdown = document.getElementById('search-autocomplete');
    if (!input || !dropdown) return;
    
    let debounceTimer;
    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            dropdown.classList.add('hidden');
            return;
        }
        
        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(BASE_URL + `api/search.php?action=autocomplete&q=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                if (data.length > 0) {
                    dropdown.innerHTML = data.map(item => `
                        <a href="${BASE_URL}search.php?q=${encodeURIComponent(item.title)}" class="block px-4 py-2 hover:bg-gray-100">
                            ${item.title}
                        </a>
                    `).join('');
                    dropdown.classList.remove('hidden');
                } else {
                    dropdown.classList.add('hidden');
                }
            } catch (error) {
                console.error('Autocomplete error:', error);
            }
        }, 300);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// Recently Viewed (localStorage)
function initRecentlyViewed() {
    const container = document.getElementById('recently-viewed');
    if (!container) return;
    
    const viewed = JSON.parse(localStorage.getItem('recently_viewed') || '[]');
    if (viewed.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">Belum ada produk yang dilihat</p>';
        return;
    }
    
    // Fetch listings data
    fetch(BASE_URL + `api/listings.php?action=get_recent&ids=${viewed.join(',')}`)
        .then(res => res.json())
        .then(listings => {
            container.innerHTML = listings.map(listing => createProductCard(listing)).join('');
        });
}

function addToRecentlyViewed(listingId) {
    let viewed = JSON.parse(localStorage.getItem('recently_viewed') || '[]');
    viewed = viewed.filter(id => id != listingId);
    viewed.unshift(listingId);
    viewed = viewed.slice(0, 20);
    localStorage.setItem('recently_viewed', JSON.stringify(viewed));
}

// Confirm Dialog
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const message = el.dataset.confirm || 'Apakah Anda yakin?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Product Card HTML Generator
function createProductCard(listing) {
    return `
        <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition">
            <a href="${BASE_URL}detail.php?id=${listing.id}">
                <div class="aspect-square bg-gray-200 relative">
                    ${listing.primary_image ? 
                        `<img src="${BASE_URL}uploads/listings/${listing.primary_image}" alt="${listing.title}" class="w-full h-full object-cover" loading="lazy">` :
                        `<div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>`
                    }
                    ${listing.condition_type === 'new' ? 
                        '<span class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded">Baru</span>' : ''}
                </div>
                <div class="p-3">
                    <h3 class="font-medium text-gray-800 line-clamp-2 mb-1">${listing.title}</h3>
                    <p class="text-[#002F34] font-bold text-lg">${formatRupiah(listing.price)}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-gray-500 text-sm">${listing.city_name || ''}</span>
                        <span class="text-gray-400 text-xs">${timeAgo(listing.created_at)}</span>
                    </div>
                </div>
            </a>
            <div class="px-3 pb-3 flex justify-end">
                <button data-favorite-toggle data-listing-id="${listing.id}" class="text-gray-400 hover:text-red-500">
                    <svg class="w-5 h-5 ${listing.is_favorited ? 'text-red-500 fill-current' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

// Global functions
window.showToast = showToast;
window.BASE_URL = 'http://localhost/olx-clone/';