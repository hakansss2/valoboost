<?php
require_once 'includes/header.php';

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
        $stmt = $conn->prepare("SELECT id FROM games WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu oyun adı zaten kullanılıyor.";
        }
    } catch(PDOException $e) {
        $errors[] = "Oyun kontrolü yapılırken bir hata oluştu.";
    }

    // Resim yükleme
    $image = null;
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
                $image = 'uploads/games/' . $new_name;
            } else {
                $errors[] = "Resim yüklenirken bir hata oluştu.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO games (name, slug, description, image, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $name,
                $slug,
                $description,
                $image
            ]);
            
            $_SESSION['success'] = "Oyun başarıyla eklendi.";
            header("Location: games.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Oyun eklenirken bir hata oluştu.";
            
            // Resim yüklendiyse sil
            if ($image && file_exists('../' . $image)) {
                unlink('../' . $image);
            }
        }
    } else {
        // Hata durumunda yüklenen resmi sil
        if ($image && file_exists('../' . $image)) {
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
    <h1 class="h3 mb-0 text-gray-800">Yeni Oyun</h1>
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

<!-- Oyun Ekleme Formu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="" id="addGameForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Oyun Adı</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php 
                            echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                        ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Resim</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div id="imagePreview" class="mt-2"></div>
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
    watchFormChanges('addGameForm');

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