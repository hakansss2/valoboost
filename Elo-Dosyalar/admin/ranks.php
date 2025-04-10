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
    
    if (empty($games)) {
        $_SESSION['message'] = 'Önce oyun eklemelisiniz.';
        $_SESSION['message_type'] = 'warning';
    }
} catch(PDOException $e) {
    $_SESSION['message'] = 'Oyunlar yüklenirken hata: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $games = [];
}

// Seçili oyun
$selected_game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : ($games[0]['id'] ?? 0);

// Seçili oyunun rankları
$ranks = [];
if ($selected_game_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM ranks WHERE game_id = ? ORDER BY value");
        $stmt->execute([$selected_game_id]);
        $ranks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sorgu sonucunu kontrol edelim
        if (empty($ranks)) {
            $_SESSION['message'] = 'Bu oyun için henüz rank eklenmemiş.';
            $_SESSION['message_type'] = 'info';
        }
    } catch(PDOException $e) {
        $_SESSION['message'] = 'Ranklar yüklenirken hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

// Seçili oyunun adını al
$selected_game_name = '';
foreach ($games as $game) {
    if ($game['id'] == $selected_game_id) {
        $selected_game_name = $game['name'];
        break;
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Ranklar <?php echo $selected_game_name ? '- ' . htmlspecialchars($selected_game_name) : ''; ?></h2>
                <a href="add_rank.php?game_id=<?php echo $selected_game_id; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Rank Ekle
                </a>
            </div>
        </div>
    </div>

    <!-- Oyun Seçim Butonları -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <?php foreach ($games as $game): ?>
                            <a href="?game_id=<?php echo $game['id']; ?>" 
                               class="btn <?php echo $game['id'] == $selected_game_id ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo htmlspecialchars($game['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <?php if (!empty($ranks)): ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 80px">Resim</th>
                                <th>Rank Adı</th>
                                <th>Sıralama Değeri</th>
                                <th style="width: 100px">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="rank-list">
                            <?php foreach ($ranks as $rank): ?>
                                <tr>
                                    <td>
                                        <?php if ($rank['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($rank['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($rank['name']); ?>"
                                                 class="img-fluid" style="max-height: 40px;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($rank['name']); ?></td>
                                    <td><?php echo $rank['value']; ?></td>
                                    <td>
                                        <a href="edit_rank.php?id=<?php echo $rank['id']; ?>&game_id=<?php echo $selected_game_id; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger delete-rank"
                                                data-id="<?php echo $rank['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($rank['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <p>Bu oyun için henüz rank eklenmemiş.</p>
            <a href="add_rank.php?game_id=<?php echo $selected_game_id; ?>" class="btn btn-primary mt-2">
                <i class="fas fa-plus"></i> Rank Ekle
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Rank silme işlemi
    $('.delete-rank').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        if (confirm(name + ' rankını silmek istediğinize emin misiniz?')) {
            window.location.href = 'delete_rank.php?id=' + id + '&game_id=<?php echo $selected_game_id; ?>';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>