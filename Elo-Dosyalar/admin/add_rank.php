<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Yönetici kontrolü
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Oyunları getir
try {
    $stmt = $conn->query("SELECT * FROM games ORDER BY name");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['message'] = 'Oyunlar yüklenirken hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $games = [];
}

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = (int)$_POST['game_id'];
    $name = trim($_POST['name']);
    $value = (int)$_POST['value'];
    
    // Veri kontrolü
    if (!$game_id || empty($name) || $value < 1) {
        $_SESSION['message'] = 'Lütfen tüm alanları doldurun.';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            // Resim yükleme
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/ranks/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid('rank_') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/ranks/' . $file_name;
                }
            }
            
            // Rankı ekle
            $stmt = $conn->prepare("
                INSERT INTO ranks (game_id, name, value, image) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$game_id, $name, $value, $image_path]);
            
            $_SESSION['message'] = 'Rank başarıyla eklendi.';
            $_SESSION['message_type'] = 'success';
            header('Location: ranks.php?game_id=' . $game_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Rank eklenirken hata: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }
}

// Seçili oyun ID'si
$selected_game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
?>

<?php require_once 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Yeni Rank Ekle</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['message'];
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Oyun</label>
                            <select class="form-select" name="game_id" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($games as $game): ?>
                                    <option value="<?php echo $game['id']; ?>"
                                            <?php echo $game['id'] == $selected_game_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($game['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rank Adı</label>
                            <input type="text" class="form-control" name="name" required
                                   placeholder="Örn: Bronz 1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sıralama Değeri</label>
                            <input type="number" class="form-control" name="value" required
                                   placeholder="Düşük değer alt rank, yüksek değer üst rank" min="1">
                            <small class="text-muted">
                                Örnek: Bronz 1 = 1, Bronz 2 = 2, Gümüş 1 = 11, vb.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rank Resmi</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">
                                PNG veya JPG formatında rank resmi yükleyebilirsiniz.
                            </small>
                        </div>

                        <div class="text-end">
                            <a href="ranks.php<?php echo $selected_game_id ? '?game_id=' . $selected_game_id : ''; ?>" 
                               class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>