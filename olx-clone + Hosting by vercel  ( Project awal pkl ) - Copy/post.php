<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Pasang Iklan - OLX Clone';
redirectIfNotLoggedIn('/login.php?redirect=post.php');

$userId = $_SESSION['user_id'];

// --- MODE EDIT ---
$editMode = false;
$editListing = null;
$editImages = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$editId, $userId]);
    $editListing = $stmt->fetch();
    if ($editListing) {
        $editMode = true;
        $pageTitle = 'Edit Iklan - OLX Clone';
        $imgStmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY sort_order");
        $imgStmt->execute([$editId]);
        $editImages = $imgStmt->fetchAll();
    } else {
        header('Location: ' . BASE_URL . 'dashboard/my-listings.php');
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
$provinces = $pdo->query("SELECT * FROM provinces ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6 max-w-4xl">
    <h1 class="text-2xl font-bold mb-2"><?= $editMode ? 'Edit Iklan' : 'Pasang Iklan' ?></h1>
    <!-- Progress Bar -->
    <div class="flex items-center justify-between mb-8" id="progress-steps">
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-[#002F34] text-white flex items-center justify-center step-indicator" data-step="1">1</div>
            <span class="text-sm mt-1">Kategori</span>
        </div>
        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center step-indicator" data-step="2">2</div>
            <span class="text-sm mt-1">Detail</span>
        </div>
        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center step-indicator" data-step="3">3</div>
            <span class="text-sm mt-1">Foto</span>
        </div>
        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center step-indicator" data-step="4">4</div>
            <span class="text-sm mt-1">Lokasi</span>
        </div>
        <div class="flex-1 h-1 bg-gray-300 mx-2"></div>
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center step-indicator" data-step="5">5</div>
            <span class="text-sm mt-1">Preview</span>
        </div>
    </div>

    <form id="post-form" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6">
        <?= csrf_field(); ?>
        <?php if ($editMode): ?>
            <input type="hidden" name="listing_id" value="<?= $editListing['id'] ?>">
            <input type="hidden" name="action" value="update">
        <?php else: ?>
            <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <!-- Step 1: Category & Subcategory -->
        <div id="step-1" class="step-content">
            <h2 class="text-xl font-bold mb-4">Pilih Kategori</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($categories as $cat): ?>
                <label class="border rounded-lg p-3 text-center cursor-pointer hover:border-[#002F34] category-option">
                    <input type="radio" name="category_id" value="<?= $cat['id'] ?>" class="hidden category-radio" required
                        <?= ($editMode && $editListing['category_id'] == $cat['id']) ? 'checked' : '' ?>>
                    <div class="text-2xl mb-1"><?= htmlspecialchars($cat['icon']) ?></div>
                    <span class="text-sm"><?= htmlspecialchars($cat['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div id="subcategory-container" class="mt-4 <?= ($editMode && $editListing['category_id']) ? '' : 'hidden' ?>">
                <label class="block font-medium mb-1">Pilih Subkategori <span class="text-red-500">*</span></label>
                <select name="subcategory_id" id="subcategory-select" class="w-full border rounded-lg p-2" required>
                    <option value="">Pilih Subkategori</option>
                </select>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="next-step bg-[#002F34] text-white px-6 py-2 rounded-lg" data-next="2">Lanjut</button>
            </div>
        </div>

        <!-- Step 2: Details -->
        <div id="step-2" class="step-content hidden">
            <h2 class="text-xl font-bold mb-4">Detail Iklan</h2>
            <div class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Judul Iklan <span class="text-red-500">*</span></label>
                    <input type="text" name="title" maxlength="70" required class="w-full border rounded-lg p-2"
                           value="<?= $editMode ? htmlspecialchars($editListing['title']) : '' ?>"
                           placeholder="Contoh: iPhone 13 Pro Max 256GB">
                    <p class="text-xs text-gray-500 mt-1">Maksimal 70 karakter</p>
                </div>
                <div>
                    <label class="block font-medium mb-1">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="5" required class="w-full border rounded-lg p-2"
                              placeholder="Jelaskan kondisi, spesifikasi, dll."><?= $editMode ? htmlspecialchars($editListing['description']) : '' ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">Harga (Rp)</label>
                        <input type="number" name="price" class="w-full border rounded-lg p-2" placeholder="0"
                               value="<?= $editMode ? $editListing['price'] : '' ?>">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Tipe Harga</label>
                        <select name="price_type" class="w-full border rounded-lg p-2">
                            <option value="fixed" <?= ($editMode && $editListing['price_type'] == 'fixed') ? 'selected' : '' ?>>Tetap</option>
                            <option value="negotiable" <?= ($editMode && $editListing['price_type'] == 'negotiable') ? 'selected' : '' ?>>Nego</option>
                            <option value="free" <?= ($editMode && $editListing['price_type'] == 'free') ? 'selected' : '' ?>>Gratis</option>
                            <option value="contact" <?= ($editMode && $editListing['price_type'] == 'contact') ? 'selected' : '' ?>>Hubungi</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-medium mb-1">Kondisi</label>
                    <select name="condition_type" class="w-full border rounded-lg p-2">
                        <option value="used" <?= ($editMode && $editListing['condition_type'] == 'used') ? 'selected' : '' ?>>Bekas</option>
                        <option value="new" <?= ($editMode && $editListing['condition_type'] == 'new') ? 'selected' : '' ?>>Baru</option>
                    </select>
                </div>
                <div id="dynamic-fields"></div>
            </div>
            <div class="mt-6 flex justify-between">
                <button type="button" class="prev-step border border-gray-300 px-6 py-2 rounded-lg" data-prev="1">Kembali</button>
                <button type="button" class="next-step bg-[#002F34] text-white px-6 py-2 rounded-lg" data-next="3">Lanjut</button>
            </div>
        </div>

        <!-- Step 3: Photos -->
        <div id="step-3" class="step-content hidden">
            <h2 class="text-xl font-bold mb-4">Unggah Foto (Maks. 20)</h2>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center" id="drop-area">
                <input type="file" name="images[]" id="image-input" multiple accept="image/jpeg,image/png,image/webp" class="hidden">
                <div class="text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p>Seret & lepas foto di sini atau <button type="button" class="text-[#002F34] font-medium" onclick="document.getElementById('image-input').click()">pilih dari komputer</button></p>
                    <p class="text-xs mt-2">Format: JPG, PNG, WEBP. Maks 5MB per file.</p>
                </div>
            </div>
            <div id="image-preview" class="grid grid-cols-4 gap-2 mt-4"></div>
            <p class="text-sm text-gray-500 mt-2">Foto pertama akan menjadi foto utama.</p>
            <div class="mt-6 flex justify-between">
                <button type="button" class="prev-step border border-gray-300 px-6 py-2 rounded-lg" data-prev="2">Kembali</button>
                <button type="button" class="next-step bg-[#002F34] text-white px-6 py-2 rounded-lg" data-next="4">Lanjut</button>
            </div>
        </div>

        <!-- Step 4: Location -->
        <div id="step-4" class="step-content hidden">
            <h2 class="text-xl font-bold mb-4">Lokasi</h2>
            <div class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Provinsi</label>
                    <select name="province_id" id="province-select" class="w-full border rounded-lg p-2" required>
                        <option value="">Pilih Provinsi</option>
                        <?php foreach ($provinces as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= ($editMode && $editListing['province_id'] == $prov['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Kota/Kabupaten</label>
                    <select name="city_id" id="city-select" class="w-full border rounded-lg p-2" required <?= ($editMode && $editListing['province_id']) ? '' : 'disabled' ?>>
                        <option value="">Pilih Kota</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Alamat Lengkap</label>
                    <textarea name="address" rows="2" class="w-full border rounded-lg p-2" placeholder="Contoh: Jl. Sudirman No. 10, Kecamatan..."><?= $editMode ? htmlspecialchars($editListing['address']) : '' ?></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-between">
                <button type="button" class="prev-step border border-gray-300 px-6 py-2 rounded-lg" data-prev="3">Kembali</button>
                <button type="button" class="next-step bg-[#002F34] text-white px-6 py-2 rounded-lg" data-next="5">Lanjut</button>
            </div>
        </div>

        <!-- Step 5: Preview & Package -->
        <div id="step-5" class="step-content hidden">
            <h2 class="text-xl font-bold mb-4">Tinjau & Pilih Paket</h2>
            <div id="preview-container" class="bg-gray-50 p-4 rounded-lg mb-4"></div>
            <div>
                <label class="block font-medium mb-2">Pilih Paket Iklan</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="border rounded-lg p-3 cursor-pointer hover:border-[#002F34]">
                        <input type="radio" name="package_type" value="free" <?= ($editMode && $editListing['package_type'] == 'free') ? 'checked' : '' ?> class="mr-2"> Gratis
                        <p class="text-xs text-gray-500 mt-1">Tayang 30 hari</p>
                    </label>
                    <label class="border rounded-lg p-3 cursor-pointer hover:border-[#002F34]">
                        <input type="radio" name="package_type" value="highlight" <?= ($editMode && $editListing['package_type'] == 'highlight') ? 'checked' : '' ?> class="mr-2"> Highlight
                        <p class="text-xs text-gray-500 mt-1">Latar kuning, lebih menonjol</p>
                        <p class="text-sm font-bold">Rp 50.000</p>
                    </label>
                    <label class="border rounded-lg p-3 cursor-pointer hover:border-[#002F34]">
                        <input type="radio" name="package_type" value="top_ads" <?= ($editMode && $editListing['package_type'] == 'top_ads') ? 'checked' : '' ?> class="mr-2"> Top Ads
                        <p class="text-xs text-gray-500 mt-1">Posisi teratas</p>
                        <p class="text-sm font-bold">Rp 150.000</p>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex justify-between">
                <button type="button" class="prev-step border border-gray-300 px-6 py-2 rounded-lg" data-prev="4">Kembali</button>
                <button type="submit" id="submit-btn" class="bg-[#FFCE32] text-[#002F34] font-bold px-8 py-2 rounded-lg">
                    <?= $editMode ? 'Simpan Perubahan' : 'Pasang Iklan' ?>
                </button>
            </div>
        </div>
    </form>
</main>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const editMode = <?= $editMode ? 'true' : 'false' ?>;
const existingImages = <?= json_encode($editImages) ?>;
let imageFiles = [];
let keptImageIds = [];

// Fungsi showToast sekarang global dari toast.js, tidak perlu definisi ulang.

// Multi-step
let currentStep = 1;
const totalSteps = 5;

function showStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(`step-${step}`).classList.remove('hidden');
    document.querySelectorAll('.step-indicator').forEach((ind, idx) => {
        const stepNum = idx + 1;
        if (stepNum <= step) {
            ind.classList.remove('bg-gray-300', 'text-gray-600');
            ind.classList.add('bg-[#002F34]', 'text-white');
        } else {
            ind.classList.add('bg-gray-300', 'text-gray-600');
            ind.classList.remove('bg-[#002F34]', 'text-white');
        }
    });
    if (step === 5) generatePreview();
    currentStep = step;
    if (!editMode) saveDraft();
}

document.querySelectorAll('.next-step').forEach(btn => {
    btn.addEventListener('click', () => {
        if (validateStep(currentStep) && currentStep < totalSteps) showStep(currentStep + 1);
    });
});
document.querySelectorAll('.prev-step').forEach(btn => {
    btn.addEventListener('click', () => {
        if (currentStep > 1) showStep(currentStep - 1);
    });
});

// Category highlight
document.querySelectorAll('.category-option').forEach(label => {
    label.addEventListener('click', function() {
        document.querySelectorAll('.category-option').forEach(l => l.classList.remove('border-[#002F34]', 'bg-blue-50'));
        this.classList.add('border-[#002F34]', 'bg-blue-50');
    });
});

// ========== SUBKATEGORI ==========
const subcategoryContainer = document.getElementById('subcategory-container');
const subcategorySelect = document.getElementById('subcategory-select');
const categoryRadios = document.querySelectorAll('.category-radio');

async function loadSubcategories(categoryId, selectedSubId = null) {
    if (!categoryId) {
        subcategoryContainer.classList.add('hidden');
        subcategorySelect.innerHTML = '<option value="">Pilih Subkategori</option>';
        subcategorySelect.required = false;
        return;
    }
    try {
        const res = await fetch(BASE_URL + `api/subcategories.php?action=get_by_category&category_id=${categoryId}`);
        const subs = await res.json();
        subcategorySelect.innerHTML = '<option value="">Pilih Subkategori</option>';
        subs.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.name;
            if (selectedSubId && sub.id == selectedSubId) opt.selected = true;
            subcategorySelect.appendChild(opt);
        });
        subcategoryContainer.classList.remove('hidden');
        subcategorySelect.required = true;
    } catch (e) {
        window.showToast('Gagal memuat subkategori', 'error');
    }
}

categoryRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.category-option').forEach(l => l.classList.remove('border-[#002F34]', 'bg-blue-50'));
        this.closest('.category-option').classList.add('border-[#002F34]', 'bg-blue-50');
        loadSubcategories(this.value);
    });
});

if (editMode) {
    const checkedRadio = document.querySelector('input[name="category_id"]:checked');
    if (checkedRadio) {
        checkedRadio.closest('.category-option').classList.add('border-[#002F34]', 'bg-blue-50');
        const subcatId = <?= $editListing['subcategory_id'] ?? 'null' ?>;
        loadSubcategories(checkedRadio.value, subcatId);
    }
}

// Image handling
const dropArea = document.getElementById('drop-area');
const imageInput = document.getElementById('image-input');
const previewContainer = document.getElementById('image-preview');

dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('border-[#002F34]'); });
dropArea.addEventListener('dragleave', () => dropArea.classList.remove('border-[#002F34]'));
dropArea.addEventListener('drop', e => {
    e.preventDefault();
    dropArea.classList.remove('border-[#002F34]');
    handleFiles(e.dataTransfer.files);
});
imageInput.addEventListener('change', () => handleFiles(imageInput.files));

function handleFiles(files) {
    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;
        if (file.size > 5 * 1024 * 1024) { window.showToast('File terlalu besar: ' + file.name, 'error'); continue; }
        imageFiles.push(file);
    }
    renderPreview();
}

function renderPreview() {
    previewContainer.innerHTML = '';
    if (editMode && keptImageIds !== undefined) {
        existingImages.forEach(img => {
            if (keptImageIds.includes(img.id)) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square border rounded overflow-hidden group';
                div.innerHTML = `
                    <img src="${BASE_URL}uploads/listings/${img.filename}" class="w-full h-full object-cover">
                    <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100" onclick="removeExistingImage(${img.id})">&times;</button>
                    ${img.is_primary == 1 ? '<span class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-2 py-1 rounded">Utama</span>' : ''}
                `;
                previewContainer.appendChild(div);
            }
        });
    }
    imageFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'relative aspect-square border rounded overflow-hidden group';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100" onclick="removeNewImage(${index})">&times;</button>
                ${(previewContainer.children.length === 0 && !hasExistingPrimary()) ? '<span class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-2 py-1 rounded">Utama</span>' : ''}
            `;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function hasExistingPrimary() {
    return existingImages.some(img => img.is_primary == 1 && keptImageIds.includes(img.id));
}

window.removeNewImage = (index) => { imageFiles.splice(index, 1); renderPreview(); };
window.removeExistingImage = (id) => { keptImageIds = keptImageIds.filter(i => i !== id); renderPreview(); };

if (editMode) {
    keptImageIds = existingImages.map(img => img.id);
    renderPreview();
}

// Location cascade
const provinceSelect = document.getElementById('province-select');
const citySelect = document.getElementById('city-select');

async function loadCities(provinceId, selectedCityId = null) {
    citySelect.innerHTML = '<option value="">Pilih Kota</option>';
    if (!provinceId) { citySelect.disabled = true; return; }
    citySelect.disabled = false;
    const res = await fetch(BASE_URL + `api/search.php?action=cities&province_id=${provinceId}`);
    const cities = await res.json();
    cities.forEach(city => {
        const opt = document.createElement('option');
        opt.value = city.id;
        opt.textContent = city.name;
        if (selectedCityId && city.id == selectedCityId) opt.selected = true;
        citySelect.appendChild(opt);
    });
}

provinceSelect.addEventListener('change', () => loadCities(provinceSelect.value));

if (editMode) {
    const provinceId = <?= $editListing['province_id'] ?? 'null' ?>;
    const cityId = <?= $editListing['city_id'] ?? 'null' ?>;
    if (provinceId) loadCities(provinceId, cityId);
}

// Validation
function validateStep(step) {
    if (step === 1) {
        if (!document.querySelector('input[name="category_id"]:checked')) { window.showToast('Pilih kategori', 'error'); return false; }
        if (!subcategorySelect.value) { window.showToast('Pilih subkategori', 'error'); return false; }
    }
    if (step === 2) {
        const title = document.querySelector('input[name="title"]').value.trim();
        const desc = document.querySelector('textarea[name="description"]').value.trim();
        if (!title) { window.showToast('Judul harus diisi', 'error'); return false; }
        if (!desc) { window.showToast('Deskripsi harus diisi', 'error'); return false; }
    }
    if (step === 4) {
        if (!provinceSelect.value || !citySelect.value) { window.showToast('Pilih lokasi lengkap', 'error'); return false; }
    }
    return true;
}

function generatePreview() {
    const title = document.querySelector('input[name="title"]').value;
    const price = document.querySelector('input[name="price"]').value;
    const condition = document.querySelector('select[name="condition_type"]').value;
    const desc = document.querySelector('textarea[name="description"]').value;
    document.getElementById('preview-container').innerHTML = `
        <div class="flex">
            <div class="ml-4">
                <h3 class="font-bold">${title}</h3>
                <p class="text-[#002F34] font-bold">Rp ${Number(price).toLocaleString('id')}</p>
                <p class="text-sm">${condition === 'new' ? 'Baru' : 'Bekas'}</p>
                <p class="text-sm text-gray-600 line-clamp-2">${desc}</p>
            </div>
        </div>
    `;
}

function saveDraft() {
    if (editMode) return;
    const formData = new FormData(document.getElementById('post-form'));
    const draft = {};
    for (let [key, val] of formData.entries()) {
        if (key !== 'csrf_token' && key !== 'images[]') draft[key] = val;
    }
    draft.currentStep = currentStep;
    localStorage.setItem('post_draft', JSON.stringify(draft));
}

function loadDraft() {
    if (editMode) return;
    const draft = JSON.parse(localStorage.getItem('post_draft') || '{}');
    if (!draft.currentStep) return;
    if (confirm('Ada draft tersimpan. Lanjutkan?')) {
        for (let key in draft) {
            if (key === 'currentStep') continue;
            const field = document.querySelector(`[name="${key}"]`);
            if (field) field.value = draft[key];
        }
        if (draft.category_id) {
            const radio = document.querySelector(`input[name="category_id"][value="${draft.category_id}"]`);
            if (radio) {
                radio.checked = true;
                radio.closest('.category-option').classList.add('border-[#002F34]', 'bg-blue-50');
                loadSubcategories(draft.category_id, draft.subcategory_id);
            }
        }
        showStep(parseInt(draft.currentStep));
    } else {
        localStorage.removeItem('post_draft');
    }
}
if (!editMode) loadDraft();

// Submit
document.getElementById('post-form').addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateStep(5)) return;
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Memproses...';
    const formData = new FormData(e.target);
    if (editMode) formData.append('kept_images', JSON.stringify(keptImageIds));
    imageFiles.forEach((f, i) => formData.append(`images[${i}]`, f));
    try {
        const res = await fetch(BASE_URL + 'api/listings.php', { method: 'POST', body: formData });
        const raw = await res.text();
        let data;
        try { data = JSON.parse(raw); } catch { window.showToast('Server error: ' + raw.substring(0,100), 'error'); return; }
        if (data.success) {
            if (!editMode) localStorage.removeItem('post_draft');
            window.showToast(editMode ? 'Iklan berhasil diperbarui!' : 'Iklan berhasil dipasang!', 'success');
            window.location.href = BASE_URL + 'detail.php?id=' + (data.listing_id || <?= $editMode ? $editListing['id'] : 0 ?>);
        } else {
            window.showToast(data.message || 'Gagal', 'error');
        }
    } catch (err) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '<?= $editMode ? 'Simpan Perubahan' : 'Pasang Iklan' ?>';
    }
});
</script>
<?php require_once 'includes/footer.php'; ?>