<?php
require_once 'includes/header.php';

// ID kontrolü
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) {
    $_SESSION['error'] = "Geçersiz oyun ID'si.";
    header("Location: games.php");
    exit;
}

// Oyun bilgilerini getir
try {
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        $_SESSION['error'] = "Oyun bulunamadı.";
        header("Location: games.php");
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Oyun bilgileri getirilirken bir hata oluştu.";
    header("Location: games.php");
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? clean($_POST['name']) : '';
    $description = isset($_POST['description']) ? clean($_POST['description']) : '';
    
    $errors = [];

    // Validasyon
    if (empty($name)) {
        $errors[] = "Oyun adı gereklidir.";
    }

    // Slug oluştur
    $slug = createSlug($name);

    // Slug benzersiz olmalı
    try {
        $stmt = $conn->prepare("SELECT id FROM games WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $game_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu oyun adı zaten kullanılıyor.";
        }
    } catch(PDOException $e) {
        $errors[] = "Oyun kontrolü yapılırken bir hata oluştu.";
    }

    // Resim yükleme
    $image = $game['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed)) {
            $errors[] = "Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.";
        } else {
            $upload_dir = '../uploads/games/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_name = uniqid() . '.' . $file_ext;
            $destination = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Eski resmi sil
                if ($game['image'] && file_exists('../' . $game['image'])) {
                    unlink('../' . $game['image']);
                }
                $image = 'uploads/games/' . $new_name;
            } else {
                $errors[] = "Resim yüklenirken bir hata oluştu.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE games 
                SET name = ?, slug = ?, description = ?, image = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $slug,
                $description,
                $image,
                $game_id
            ]);
            
            $_SESSION['success'] = "Oyun başarıyla güncellendi.";
            header("Location: games.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Oyun güncellenirken bir hata oluştu.";
            
            // Yeni resim yüklendiyse sil
            if ($image !== $game['image'] && file_exists('../' . $image)) {
                unlink('../' . $image);
            }
        }
    } else {
        // Hata durumunda yeni yüklenen resmi sil
        if ($image !== $game['image'] && file_exists('../' . $image)) {
            unlink('../' . $image);
        }
    }
}

/**
 * Slug oluşturma fonksiyonu
 */
function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $str);
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}
?>

<!-- Başlık -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Oyun Düzenle</h1>
</div>

<!-- Hata Mesajları -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Oyun Düzenleme Formu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="" id="editGameForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Oyun Adı</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($game['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php 
                            echo htmlspecialchars($game['description']); 
                        ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Resim</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div id="imagePreview" class="mt-2">
                            <?php if ($game['image']): ?>
                                <img src="<?php echo '../' . htmlspecialchars($game['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($game['name']); ?>"
                                     class="img-thumbnail" style="max-height: 200px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
                <a href="games.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Geri
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form değişikliklerini izle
    watchFormChanges('editGameForm');

    // Resim önizleme
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-thumbnail', 'mt-2');
                img.style.maxHeight = '200px';
                preview.appendChild(img);
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 