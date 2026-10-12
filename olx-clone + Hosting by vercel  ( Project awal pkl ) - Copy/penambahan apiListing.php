case 'create':
    // Pastikan user sudah login
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
        break;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Ambil dan validasi input
    $category_id = intval($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $price_type = $_POST['price_type'] ?? 'fixed';
    $condition_type = $_POST['condition_type'] ?? 'used';
    $province_id = intval($_POST['province_id'] ?? 0);
    $city_id = intval($_POST['city_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $package_type = $_POST['package_type'] ?? 'free';
    
    // Validasi
    $errors = [];
    if ($category_id <= 0) $errors[] = 'Kategori harus dipilih';
    if (empty($title)) $errors[] = 'Judul harus diisi';
    if (strlen($title) > 70) $errors[] = 'Judul maksimal 70 karakter';
    if (empty($description)) $errors[] = 'Deskripsi harus diisi';
    if (!in_array($price_type, ['fixed', 'negotiable', 'free', 'contact'])) $errors[] = 'Tipe harga tidak valid';
    if (!in_array($condition_type, ['new', 'used'])) $errors[] = 'Kondisi tidak valid';
    if ($province_id <= 0 || $city_id <= 0) $errors[] = 'Lokasi harus dipilih';
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        break;
    }
    
    // Generate slug unik
    $baseSlug = generateSlug($title);
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT id FROM listings WHERE slug = ?");
        $checkStmt->execute([$slug]);
        if (!$checkStmt->fetch()) break;
        $slug = $baseSlug . '-' . $counter++;
    }
    
    // Set expires_at (60 hari dari sekarang)
    $expires_at = date('Y-m-d H:i:s', strtotime('+60 days'));
    
    // Insert listing
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO listings (
                user_id, category_id, title, slug, description, price, price_type,
                condition_type, province_id, city_id, address, status, package_type,
                published_at, expires_at, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), ?, NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            $userId, $category_id, $title, $slug, $description, $price, $price_type,
            $condition_type, $province_id, $city_id, $address, $package_type, $expires_at
        ]);
        
        $listingId = $pdo->lastInsertId();
        
        // Handle image uploads
        $uploadedImages = [];
        if (!empty($_FILES['images']['tmp_name']) && is_array($_FILES['images']['tmp_name'])) {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $sortOrder = 1;
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                
                $fileType = mime_content_type($tmpName);
                if (!in_array($fileType, $allowedTypes)) continue;
                
                if ($_FILES['images']['size'][$key] > MAX_FILE_SIZE) continue;
                
                $extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $newFilename = uniqid() . '_' . time() . '.' . $extension;
                $destination = UPLOAD_DIR . $newFilename;
                
                if (resizeImage($tmpName, $destination, 1200, 1200, 85)) {
                    $isPrimary = (count($uploadedImages) === 0) ? 1 : 0;
                    $imgStmt = $pdo->prepare("
                        INSERT INTO listing_images (listing_id, filename, is_primary, sort_order)
                        VALUES (?, ?, ?, ?)
                    ");
                    $imgStmt->execute([$listingId, $newFilename, $isPrimary, $sortOrder]);
                    $uploadedImages[] = $newFilename;
                    $sortOrder++;
                }
            }
        }
        
        // Update total_listings user
        $updUser = $pdo->prepare("UPDATE users SET total_listings = total_listings + 1 WHERE id = ?");
        $updUser->execute([$userId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'listing_id' => $listingId,
            'slug' => $slug,
            'message' => 'Iklan berhasil dipasang!'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan iklan: ' . $e->getMessage()]);
    }
    break;