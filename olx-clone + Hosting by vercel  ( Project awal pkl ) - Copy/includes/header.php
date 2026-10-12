<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo $pageTitle ?? 'OLX Clone - Jual Beli Mudah'; ?></title>
    <meta name="description" content="<?php echo $pageDescription ?? 'OLX Clone, situs jual beli online mudah dan terpercaya di Indonesia.'; ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $pageTitle ?? 'OLX Clone'; ?>">
    <meta property="og:description" content="<?php echo $pageDescription ?? 'Jual Beli Mudah di OLX Clone'; ?>">
    <meta property="og:image" content="<?php echo $ogImage ?? BASE_URL . 'assets/images/og-image.jpg'; ?>">
    <meta property="og:url" content="<?php echo $ogUrl ?? BASE_URL; ?>">
    <meta property="og:type" content="website">
    
    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $canonical ?? BASE_URL; ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F2F4F5; }
    </style>
    <!-- Schema -->
    <?php if (isset($schemaMarkup)): ?>
    <script type="application/ld+json"><?php echo $schemaMarkup; ?></script>
    <?php endif; ?>
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-[#002F34] text-white px-4 py-2 rounded z-50">Lewati ke konten utama</a>
    
    <!-- Top Bar (hidden on mobile) -->
    <div class="bg-[#002F34] text-white text-sm py-2 hidden md:block">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="#" class="flex items-center space-x-1 hover:text-[#FFCE32]">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 14l-4-4 1.41-1.41L8 11.17l6.59-6.59L16 6l-8 8z"/></svg>
                    <span>Download OLX App</span>
                </a>
            </div>
            <div class="flex items-center space-x-6">
                <a href="<?= BASE_URL ?>page.php?page=promo" class="hover:text-[#FFCE32]">Promo</a>
                <a href="<?= BASE_URL ?>page.php?page=olx-news" class="hover:text-[#FFCE32]">OLX News</a>
                <a href="<?= BASE_URL ?>page.php?page=bantuan" class="hover:text-[#FFCE32]">Pusat Bantuan</a>
                <a href="<?= BASE_URL ?>page.php?page=faq" class="hover:text-[#FFCE32]">FAQ</a>
            </div>
        </div>
    </div>