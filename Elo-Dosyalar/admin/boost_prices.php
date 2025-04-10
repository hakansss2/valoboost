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
                <h2>Boost Fiyatları <?php echo $selected_game_name ? '- ' . htmlspecialchars($selected_game_name) : ''; ?></h2>
                <a href="ranks.php?game_id=<?php echo $selected_game_id; ?>" class="btn btn-primary">
                    <i class="fas fa-list"></i> Rankları Yönet
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
                <form action="save_prices.php" method="POST">
                    <input type="hidden" name="game_id" value="<?php echo $selected_game_id; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Mevcut Rank</th>
                                    <?php foreach ($ranks as $target): ?>
                                        <th class="text-center">
                                            <?php if ($target['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($target['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($target['name']); ?>"
                                                     class="img-fluid" style="max-height: 30px;"><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($target['name']); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranks as $current): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <?php if ($current['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($current['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($current['name']); ?>"
                                                     class="img-fluid me-2" style="max-height: 30px;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($current['name']); ?>
                                        </td>
                                        <?php foreach ($ranks as $target): ?>
                                            <td class="text-center">
                                                <?php if ($target['value'] > $current['value']): ?>
                                                    <?php 
                                                        $stmt = $conn->prepare("
                                                            SELECT price 
                                                            FROM boost_prices 
                                                            WHERE game_id = ? 
                                                            AND current_rank_id = ? 
                                                            AND target_rank_id = ?
                                                        ");
                                                        $stmt->execute([$selected_game_id, $current['id'], $target['id']]);
                                                        $price = $stmt->fetchColumn();
                                                    ?>
                                                    <input type="number" 
                                                           name="prices[<?php echo $current['id'] . '_' . $target['id']; ?>]" 
                                                           class="form-control form-control-sm text-center"
                                                           value="<?php echo $price ?: ''; ?>"
                                                           step="0.01" 
                                                           min="0">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Tüm Fiyatları Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <p>Bu oyun için henüz rank eklenmemiş.</p>
            <a href="ranks.php?game_id=<?php echo $selected_game_id; ?>" class="btn btn-primary mt-2">
                <i class="fas fa-plus"></i> Rank Ekle
            </a>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Ek Seçenek Çarpanları</h5>
        </div>
        <div class="card-body">
            <form action="save_multipliers.php" method="POST">
                <input type="hidden" name="game_id" value="<?php echo $selected_game_id; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Öncelikli Sipariş Çarpanı</label>
                            <?php
                            $stmt = $conn->prepare("SELECT priority_multiplier FROM boost_prices WHERE game_id = ? LIMIT 1");
                            $stmt->execute([$selected_game_id]);
                            $priority_multiplier = $stmt->fetchColumn() ?: 1.20;
                            ?>
                            <div class="input-group">
                                <input type="number" class="form-control" name="priority_multiplier" 
                                       value="<?php echo $priority_multiplier; ?>" step="0.01" min="1">
                                <span class="input-group-text">x</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Yayın İsteği Çarpanı</label>
                            <?php
                            $stmt = $conn->prepare("SELECT streaming_multiplier FROM boost_prices WHERE game_id = ? LIMIT 1");
                            $stmt->execute([$selected_game_id]);
                            $streaming_multiplier = $stmt->fetchColumn() ?: 1.10;
                            ?>
                            <div class="input-group">
                                <input type="number" class="form-control" name="streaming_multiplier" 
                                       value="<?php echo $streaming_multiplier; ?>" step="0.01" min="1">
                                <span class="input-group-text">x</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Çarpanları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>