<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Daftar halaman yang valid
$pages = [
    // OLX Indonesia
    'about' => ['title' => 'Tentang Kami', 'icon' => '🏢'],
    'karir' => ['title' => 'Karir', 'icon' => '💼'],
    'blog' => ['title' => 'Blog', 'icon' => '📝'],
    'syarat' => ['title' => 'Syarat Penggunaan', 'icon' => '📜'],
    'privasi' => ['title' => 'Kebijakan Privasi', 'icon' => '🔒'],
    // OLX
    'bantuan' => ['title' => 'Pusat Bantuan', 'icon' => '🆘'],
    'kontak' => ['title' => 'Kontak Kami', 'icon' => '📞'],
    'pasang-iklan' => ['title' => 'Pasang Iklan', 'icon' => '📢'],
    'aturan' => ['title' => 'Aturan Iklan', 'icon' => '⚖️'],
    // Pencarian Populer & Kategori Populer
    'populer' => ['title' => 'Pencarian Populer', 'icon' => '🔥'],
    'kategori-populer' => ['title' => 'Kategori Populer', 'icon' => '📂'],
    // Top Bar
    'promo' => ['title' => 'Promo', 'icon' => '🎁'],
    'olx-news' => ['title' => 'OLX News', 'icon' => '📰'],
    'faq' => ['title' => 'FAQ', 'icon' => '❓'],
];

$pageKey = $_GET['page'] ?? 'about';
if (!isset($pages[$pageKey])) {
    $pageKey = 'about';
}
$pageData = $pages[$pageKey];
$pageTitle = $pageData['title'] . ' - OLX Clone';
$pageDescription = $pageData['title'] . ' OLX Clone Indonesia.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="bg-white rounded-lg shadow-sm p-6 md:p-8">
        <div class="flex items-center mb-6">
            <span class="text-4xl mr-3"><?= $pageData['icon'] ?></span>
            <h1 class="text-2xl md:text-3xl font-bold text-[#002F34]"><?= $pageData['title'] ?></h1>
        </div>
        
        <div class="prose prose-lg max-w-none">
            <?php
            // Konten berdasarkan halaman
            switch ($pageKey) {
                case 'about':
                    ?>
                    <p class="lead">OLX Clone adalah platform jual beli online terpercaya di Indonesia yang menghubungkan jutaan pembeli dan penjual setiap harinya.</p>
                    <h2>Visi Kami</h2>
                    <p>Menjadi marketplace nomor satu di Indonesia yang memberdayakan masyarakat untuk menjual dan membeli barang bekas berkualitas dengan mudah, aman, dan nyaman.</p>
                    <h2>Misi Kami</h2>
                    <ul>
                        <li>Menyediakan platform yang mudah digunakan untuk semua kalangan.</li>
                        <li>Menjamin keamanan transaksi dengan sistem pembayaran terproteksi.</li>
                        <li>Mendorong ekonomi sirkular dengan memperpanjang umur pakai barang.</li>
                    </ul>
                    <h2>Sejarah Singkat</h2>
                    <p>Didirikan pada tahun 2026, OLX Clone hadir sebagai solusi bagi masyarakat Indonesia yang ingin menjual barang bekas mereka dengan cepat dan mendapatkan barang berkualitas dengan harga terjangkau.</p>
                    <?php
                    break;
                case 'karir':
                    ?>
                    <p>Bergabunglah dengan tim kami dan jadilah bagian dari perusahaan teknologi yang berkembang pesat di Indonesia.</p>
                    <h2>Mengapa Bekerja di OLX Clone?</h2>
                    <ul>
                        <li>Lingkungan kerja yang dinamis dan inklusif</li>
                        <li>Kesempatan untuk berkembang bersama perusahaan</li>
                        <li>Fasilitas dan benefit kompetitif</li>
                    </ul>
                    <h2>Lowongan Tersedia</h2>
                    <p>Saat ini belum ada lowongan yang tersedia. Silakan cek kembali nanti.</p>
                    <?php
                    break;
                case 'blog':
                    ?>
                    <h2>Artikel Terbaru</h2>
                    <div class="space-y-4">
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg">Tips Menjual Mobil Bekas dengan Harga Tinggi</h3>
                            <p class="text-gray-600">Pelajari cara mempersiapkan mobil Anda sebelum dijual agar mendapatkan penawaran terbaik...</p>
                        </div>
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg">5 Gadget Bekas yang Masih Worth It di 2026</h3>
                            <p class="text-gray-600">iPhone, Samsung, atau laptop bekas mana yang masih layak dibeli? Simak ulasannya...</p>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Panduan Aman Bertransaksi di Marketplace</h3>
                            <p class="text-gray-600">Jangan sampai tertipu! Ikuti tips aman bertransaksi online ala OLX Clone...</p>
                        </div>
                    </div>
                    <?php
                    break;
                case 'syarat':
                    ?>
                    <h2>Syarat Penggunaan</h2>
                    <p>Terakhir diperbarui: 1 Januari 2026</p>
                    <p>Dengan mengakses atau menggunakan layanan OLX Clone, Anda setuju untuk terikat oleh Syarat Penggunaan ini.</p>
                    <h3>1. Akun Pengguna</h3>
                    <p>Anda bertanggung jawab untuk menjaga kerahasiaan akun dan password Anda.</p>
                    <h3>2. Konten Iklan</h3>
                    <p>Iklan yang dipasang tidak boleh mengandung unsur SARA, pornografi, atau melanggar hukum.</p>
                    <h3>3. Transaksi</h3>
                    <p>OLX Clone tidak bertanggung jawab atas transaksi yang dilakukan di luar platform.</p>
                    <?php
                    break;
                case 'privasi':
                    ?>
                    <h2>Kebijakan Privasi</h2>
                    <p>Terakhir diperbarui: 1 Januari 2026</p>
                    <p>OLX Clone menghargai privasi Anda. Kebijakan ini menjelaskan bagaimana kami mengumpulkan dan menggunakan data pribadi Anda.</p>
                    <h3>Informasi yang Kami Kumpulkan</h3>
                    <ul>
                        <li>Informasi akun: nama, email, nomor telepon</li>
                        <li>Informasi iklan: judul, deskripsi, gambar, harga</li>
                        <li>Data penggunaan: halaman yang dikunjungi, waktu akses</li>
                    </ul>
                    <h3>Penggunaan Informasi</h3>
                    <p>Kami menggunakan informasi untuk menyediakan layanan, meningkatkan pengalaman pengguna, dan keperluan keamanan.</p>
                    <?php
                    break;
                case 'bantuan':
                    ?>
                    <h2>Pusat Bantuan</h2>
                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                        <div class="border rounded-lg p-4">
                            <h3 class="font-bold">📞 Call Center</h3>
                            <p class="text-lg">0800-123-4567</p>
                            <p class="text-sm text-gray-500">24 Jam / 7 Hari</p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <h3 class="font-bold">✉️ Email Support</h3>
                            <p>support@olxclone.id</p>
                            <p class="text-sm text-gray-500">Balasan maksimal 1x24 jam</p>
                        </div>
                    </div>
                    <h3 class="mt-6">FAQ Singkat</h3>
                    <div class="space-y-3">
                        <details class="border-b pb-2">
                            <summary class="font-medium cursor-pointer">Bagaimana cara memasang iklan?</summary>
                            <p class="mt-2 text-gray-600">Klik tombol "Jual" di pojok kanan atas, lalu ikuti 5 langkah mudah: pilih kategori, isi detail, unggah foto, tentukan lokasi, dan pilih paket.</p>
                        </details>
                        <details class="border-b pb-2">
                            <summary class="font-medium cursor-pointer">Apakah gratis memasang iklan?</summary>
                            <p class="mt-2 text-gray-600">Ya, Anda dapat memasang iklan secara gratis dengan paket Free yang tayang selama 30 hari. Tersedia juga paket Highlight dan Top Ads untuk meningkatkan visibilitas.</p>
                        </details>
                    </div>
                    <?php
                    break;
                case 'kontak':
                    ?>
                    <h2>Hubungi Kami</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-bold mb-2">📍 Kantor Pusat</h3>
                            <p>Jl. Jenderal Sudirman No. 52-53<br>Jakarta Selatan, DKI Jakarta 12190</p>
                            <h3 class="font-bold mt-4 mb-2">📞 Telepon</h3>
                            <p>(021) 1234-5678</p>
                            <h3 class="font-bold mt-4 mb-2">✉️ Email</h3>
                            <p>info@olxclone.id</p>
                        </div>
                        <div>
                            <h3 class="font-bold mb-2">Kirim Pesan</h3>
                            <form class="space-y-3">
                                <input type="text" placeholder="Nama" class="w-full border rounded p-2">
                                <input type="email" placeholder="Email" class="w-full border rounded p-2">
                                <textarea placeholder="Pesan" rows="3" class="w-full border rounded p-2"></textarea>
                                <button type="button" class="bg-[#002F34] text-white px-4 py-2 rounded">Kirim</button>
                            </form>
                        </div>
                    </div>
                    <?php
                    break;
                case 'pasang-iklan':
                    ?>
                    <h2>Pasang Iklan di OLX Clone</h2>
                    <p>Jangkau jutaan pembeli potensial dengan memasang iklan di OLX Clone. Prosesnya mudah dan cepat!</p>
                    <h3>Langkah-langkah:</h3>
                    <ol class="list-decimal ml-5 space-y-2">
                        <li>Pilih kategori yang sesuai dengan barang Anda</li>
                        <li>Isi judul dan deskripsi yang menarik</li>
                        <li>Unggah foto berkualitas (maksimal 20 foto)</li>
                        <li>Tentukan lokasi dan harga</li>
                        <li>Pilih paket iklan (Gratis / Highlight / Top Ads)</li>
                    </ol>
                    <div class="mt-6">
                        <a href="<?= BASE_URL ?>post.php" class="inline-block bg-[#FFCE32] text-[#002F34] font-bold px-6 py-3 rounded-full">Mulai Pasang Iklan</a>
                    </div>
                    <?php
                    break;
                case 'aturan':
                    ?>
                    <h2>Aturan Iklan</h2>
                    <p>Untuk menjaga kualitas dan keamanan platform, semua iklan harus mematuhi aturan berikut:</p>
                    <h3>Barang yang Dilarang</h3>
                    <ul>
                        <li>Senjata api, bahan peledak</li>
                        <li>Narkoba dan obat-obatan terlarang</li>
                        <li>Barang bajakan atau palsu</li>
                        <li>Hewan yang dilindungi</li>
                    </ul>
                    <h3>Konten Iklan</h3>
                    <ul>
                        <li>Judul harus sesuai dengan barang yang dijual</li>
                        <li>Deskripsi tidak boleh mengandung kata-kata kasar atau SARA</li>
                        <li>Foto harus asli, tidak boleh mengambil dari internet</li>
                    </ul>
                    <?php
                    break;
                case 'populer':
                    ?>
                    <h2>Pencarian Populer</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <a href="<?= BASE_URL ?>search.php?q=iPhone" class="border rounded-lg p-3 hover:bg-gray-50">📱 iPhone</a>
                        <a href="<?= BASE_URL ?>search.php?q=Rumah" class="border rounded-lg p-3 hover:bg-gray-50">🏠 Rumah</a>
                        <a href="<?= BASE_URL ?>search.php?q=Mobil Bekas" class="border rounded-lg p-3 hover:bg-gray-50">🚗 Mobil Bekas</a>
                        <a href="<?= BASE_URL ?>search.php?q=Vario" class="border rounded-lg p-3 hover:bg-gray-50">🏍️ Vario</a>
                        <a href="<?= BASE_URL ?>search.php?q=Kost" class="border rounded-lg p-3 hover:bg-gray-50">🏢 Kost</a>
                        <a href="<?= BASE_URL ?>search.php?q=Laptop" class="border rounded-lg p-3 hover:bg-gray-50">💻 Laptop</a>
                    </div>
                    <?php
                    break;
                case 'kategori-populer':
                    ?>
                    <h2>Kategori Populer</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <a href="<?= BASE_URL ?>search.php?category=2" class="border rounded-lg p-3 hover:bg-gray-50">🚗 Mobil</a>
                        <a href="<?= BASE_URL ?>search.php?category=4" class="border rounded-lg p-3 hover:bg-gray-50">🏍️ Motor</a>
                        <a href="<?= BASE_URL ?>search.php?category=3" class="border rounded-lg p-3 hover:bg-gray-50">🏠 Properti</a>
                        <a href="<?= BASE_URL ?>search.php?category=6" class="border rounded-lg p-3 hover:bg-gray-50">📱 Handphone & Gadget</a>
                        <a href="<?= BASE_URL ?>search.php?category=8" class="border rounded-lg p-3 hover:bg-gray-50">🛋️ Rumah Tangga</a>
                        <a href="<?= BASE_URL ?>search.php?category=7" class="border rounded-lg p-3 hover:bg-gray-50">⚽ Hobi & Olahraga</a>
                    </div>
                    <?php
                    break;
                case 'promo':
                    ?>
                    <h2>Promo Spesial OLX Clone</h2>
                    <p>Manfaatkan berbagai promo menarik untuk meningkatkan penjualan Anda!</p>
                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div class="border rounded-lg p-4 bg-yellow-50">
                            <h3 class="font-bold text-lg mb-2">🎉 Diskon 50% Paket Highlight</h3>
                            <p class="text-gray-600 mb-2">Pasang iklan Highlight sekarang dan dapatkan diskon 50%! Berlaku hingga 31 Desember 2026.</p>
                            <a href="<?= BASE_URL ?>post.php" class="text-[#002F34] font-medium">Pasang Iklan →</a>
                        </div>
                        <div class="border rounded-lg p-4 bg-blue-50">
                            <h3 class="font-bold text-lg mb-2">🚀 Gratis Top Ads untuk 10 Iklan Pertama</h3>
                            <p class="text-gray-600 mb-2">Pengguna baru yang memasang 10 iklan pertama akan mendapatkan gratis paket Top Ads selama 7 hari.</p>
                            <a href="<?= BASE_URL ?>register.php" class="text-[#002F34] font-medium">Daftar Sekarang →</a>
                        </div>
                        <div class="border rounded-lg p-4 bg-green-50">
                            <h3 class="font-bold text-lg mb-2">📱 Promo Pengguna Aplikasi</h3>
                            <p class="text-gray-600 mb-2">Download aplikasi OLX Clone dan dapatkan voucher Rp 50.000 untuk pemasangan iklan pertama.</p>
                            <a href="#" class="text-[#002F34] font-medium">Download App →</a>
                        </div>
                        <div class="border rounded-lg p-4 bg-purple-50">
                            <h3 class="font-bold text-lg mb-2">🏷️ Promo Kategori Properti</h3>
                            <p class="text-gray-600 mb-2">Khusus iklan kategori Properti, dapatkan gratis paket Highlight selama 30 hari.</p>
                            <a href="<?= BASE_URL ?>post.php" class="text-[#002F34] font-medium">Pasang Iklan Properti →</a>
                        </div>
                    </div>
                    <?php
                    break;
                case 'olx-news':
                    ?>
                    <h2>OLX News</h2>
                    <p class="mb-4">Berita terbaru seputar OLX Clone dan tips jual beli online.</p>
                    <div class="space-y-6">
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg">📰 Tips Menjual Mobil Bekas dengan Cepat</h3>
                            <p class="text-gray-500 text-sm mb-2">12 April 2026</p>
                            <p class="text-gray-600">Pelajari cara mempersiapkan mobil Anda, mengambil foto yang menarik, dan menulis deskripsi yang menjual agar mobil bekas Anda laku dalam hitungan hari.</p>
                            <a href="#" class="text-[#002F34] text-sm font-medium mt-2 inline-block">Baca selengkapnya →</a>
                        </div>
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg">🔒 Keamanan Transaksi di OLX Clone</h3>
                            <p class="text-gray-500 text-sm mb-2">5 April 2026</p>
                            <p class="text-gray-600">OLX Clone terus meningkatkan fitur keamanan. Kini hadir fitur verifikasi nomor telepon dan sistem rating penjual untuk transaksi yang lebih aman.</p>
                            <a href="#" class="text-[#002F34] text-sm font-medium mt-2 inline-block">Baca selengkapnya →</a>
                        </div>
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg">📊 Laporan Tren Pasar 2026</h3>
                            <p class="text-gray-500 text-sm mb-2">28 Maret 2026</p>
                            <p class="text-gray-600">Kategori Mobil dan Properti mengalami kenaikan minat sebesar 25% dibandingkan tahun lalu. Simak laporan lengkapnya di sini.</p>
                            <a href="#" class="text-[#002F34] text-sm font-medium mt-2 inline-block">Baca selengkapnya →</a>
                        </div>
                    </div>
                    <?php
                    break;
                case 'faq':
                    ?>
                    <h2>Pertanyaan yang Sering Diajukan (FAQ)</h2>
                    <div class="space-y-4 mt-6">
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Bagaimana cara memasang iklan?</summary>
                            <p class="mt-2 text-gray-600">Klik tombol "Jual" di pojok kanan atas, pilih kategori, isi detail barang, unggah foto, tentukan lokasi, lalu pilih paket iklan. Iklan Anda akan tayang setelah disimpan.</p>
                        </details>
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Apakah memasang iklan di OLX Clone gratis?</summary>
                            <p class="mt-2 text-gray-600">Ya, Anda dapat memasang iklan secara gratis dengan paket Free yang tayang selama 30 hari. Kami juga menyediakan paket Highlight dan Top Ads untuk meningkatkan visibilitas iklan Anda.</p>
                        </details>
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Bagaimana cara mengedit atau menghapus iklan?</summary>
                            <p class="mt-2 text-gray-600">Login ke akun Anda, buka Dashboard > Iklan Saya. Di sana Anda dapat mengedit, menandai terjual, atau menghapus iklan yang sudah dipasang.</p>
                        </details>
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Bagaimana jika saya lupa password?</summary>
                            <p class="mt-2 text-gray-600">Klik "Lupa password?" di halaman login, masukkan email terdaftar Anda, dan kami akan mengirimkan link untuk reset password.</p>
                        </details>
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Apakah OLX Clone menyediakan fitur chat?</summary>
                            <p class="mt-2 text-gray-600">Ya, setiap iklan memiliki tombol "Chat via WhatsApp" yang terhubung langsung ke nomor penjual. Fitur chat internal akan segera hadir.</p>
                        </details>
                        <details class="border rounded-lg p-4">
                            <summary class="font-bold cursor-pointer">Bagaimana cara melaporkan iklan yang mencurigakan?</summary>
                            <p class="mt-2 text-gray-600">Di halaman detail iklan, terdapat tombol "Laporkan Iklan". Klik dan pilih alasan pelaporan. Tim kami akan meninjau laporan Anda.</p>
                        </details>
                    </div>
                    <?php
                    break;
                default:
                    echo "<p>Halaman sedang dalam pengembangan.</p>";
            }
            ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>