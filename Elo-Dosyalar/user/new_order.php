<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı kontrolü
if (!isUser()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_game = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;

// Kullanıcı bakiyesini getir
try {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_balance = $stmt->fetchColumn();
} catch(PDOException $e) {
    $user_balance = 0;
}

// Aktif oyunları getir
try {
    $stmt = $conn->prepare("SELECT * FROM games WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $games = $stmt->fetchAll();
} catch(PDOException $e) {
    $_SESSION['message'] = 'Oyunlar yüklenirken bir hata oluştu.';
    $_SESSION['message_type'] = 'danger';
    $games = [];
}

// Seçili oyunun ranklarını getir
$ranks = [];
if ($selected_game) {
    try {
        $stmt = $conn->prepare("SELECT * FROM ranks WHERE game_id = ? ORDER BY value");
        $stmt->execute([$selected_game]);
        $ranks = $stmt->fetchAll();
    } catch(PDOException $e) {
        $ranks = [];
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 dark-theme">
    <div class="row">
        <div class="col-md-8">
            <!-- Oyun Seçimi -->
            <div class="card glass-effect mb-4">
                <div class="card-header bg-dark-gradient">
                    <h5 class="mb-0 text-white">Boost Hizmetleri</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-dark align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px">Logo</th>
                                    <th>Oyun</th>
                                    <th>Açıklama</th>
                                    <th style="width: 120px">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($games as $game): ?>
                                    <tr>
                                        <td>
                                            <?php if ($game['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($game['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($game['name']); ?>" 
                                                     class="img-fluid" style="max-height: 50px;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($game['name']); ?></td>
                                        <td><?php echo htmlspecialchars($game['description'] ?? ''); ?></td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    Oyunu Seç
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rank Seçimi -->
            <?php if ($selected_game && !empty($ranks)): ?>
                <div class="card glass-effect">
                    <div class="card-header bg-dark-gradient">
                        <h5 class="mb-0 text-white">Rank Seçimi</h5>
                    </div>
                    <div class="card-body">
                        <form id="boost-form" action="process_order.php" method="POST">
                            <input type="hidden" name="game_id" value="<?php echo $selected_game; ?>">
                            
                            <!-- Mevcut Rank -->
                            <div class="mb-4">
                                <h5 class="text-white">Mevcut Rankınız</h5>
                                <div class="current-rank-display text-center mb-3" style="display: none;">
                                    <img src="" alt="Seçili Rank" class="img-fluid" style="max-height: 150px;">
                                    <h4 class="mt-2 rank-name text-white"></h4>
                                </div>
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#currentRankModal">
                                    Mevcut Rankınızı Seçin
                                </button>
                                <input type="hidden" name="current_rank_id" id="current_rank_id" required>
                            </div>
                            
                            <!-- Hedef Rank -->
                            <div id="targetRankSection" class="mb-4" style="display: none;">
                                <h5 class="text-white">Hedef Rankınız</h5>
                                <div class="target-rank-display text-center mb-3" style="display: none;">
                                    <img src="" alt="Hedef Rank" class="img-fluid" style="max-height: 150px;">
                                    <h4 class="mt-2 rank-name text-white"></h4>
                                </div>
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#targetRankModal">
                                    Hedef Rankınızı Seçin
                                </button>
                                <input type="hidden" name="target_rank_id" id="target_rank_id" required>
                            </div>

                            <!-- Sipariş Notları -->
                            <div class="mb-4">
                                <h5 class="text-white">Sipariş Notları</h5>
                                <textarea name="notes" class="form-control glass-effect" rows="3" 
                                          placeholder="Varsa eklemek istediğiniz notları yazabilirsiniz..."></textarea>
                            </div>

                            <div id="priceContainer" class="text-center glass-effect p-4" style="display: none;">
                                <h4 class="text-white mb-3">Toplam Tutar: <span id="totalPrice" class="text-glow">0,00 ₺</span></h4>
                                <button type="submit" class="btn btn-primary">Siparişi Onayla</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Rank Seçim Modalları -->
                <div class="modal fade" id="currentRankModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content glass-effect">
                            <div class="modal-header bg-dark-gradient">
                                <h5 class="modal-title text-white">Mevcut Rankınızı Seçin</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <?php foreach ($ranks as $rank): ?>
                                        <div class="col-4 col-md-3">
                                            <div class="rank-item text-center" 
                                                 data-rank-id="<?php echo $rank['id']; ?>"
                                                 data-rank-name="<?php echo htmlspecialchars($rank['name']); ?>"
                                                 data-rank-image="<?php echo htmlspecialchars($rank['image']); ?>"
                                                 data-rank-value="<?php echo $rank['value']; ?>"
                                                 onclick="selectCurrentRank(this)">
                                                <?php if ($rank['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($rank['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($rank['name']); ?>"
                                                         class="img-fluid mb-2">
                                                <?php endif; ?>
                                                <div class="text-white"><?php echo htmlspecialchars($rank['name']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="targetRankModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content glass-effect">
                            <div class="modal-header bg-dark-gradient">
                                <h5 class="modal-title text-white">Hedef Rankınızı Seçin</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <?php foreach ($ranks as $rank): ?>
                                        <div class="col-4 col-md-3">
                                            <div class="rank-item text-center" 
                                                 data-rank-id="<?php echo $rank['id']; ?>"
                                                 data-rank-name="<?php echo htmlspecialchars($rank['name']); ?>"
                                                 data-rank-image="<?php echo htmlspecialchars($rank['image']); ?>"
                                                 data-rank-value="<?php echo $rank['value']; ?>"
                                                 onclick="selectTargetRank(this)">
                                                <?php if ($rank['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($rank['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($rank['name']); ?>"
                                                         class="img-fluid mb-2">
                                                <?php endif; ?>
                                                <div class="text-white"><?php echo htmlspecialchars($rank['name']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card glass-effect">
                <div class="card-header bg-dark-gradient">
                    <h5 class="mb-0 text-white">Bakiyeniz</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-3 text-glow"><?php echo number_format($user_balance, 2, ',', '.'); ?> ₺</h3>
                    <a href="add_balance.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Bakiye Yükle
                    </a>
                </div>
            </div>
            
            <div class="card glass-effect mt-4">
                <div class="card-header bg-dark-gradient">
                    <h5 class="mb-0 text-white">Sipariş Adımları</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0 text-white">
                        <li class="mb-2">İstediğiniz oyunu seçin</li>
                        <li class="mb-2">Mevcut rankınızı belirtin</li>
                        <li class="mb-2">Hedef rankınızı seçin</li>
                        <li>Siparişinizi onaylayın</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --dark-bg: #1a1a1a;
    --card-bg: rgba(255, 255, 255, 0.1);
    --text-color: #ffffff;
    --border-color: rgba(255, 255, 255, 0.1);
    --hover-color: rgba(255, 255, 255, 0.2);
    --glow-color: #4a90e2;
}

body {
    background-color: var(--dark-bg);
    color: var(--text-color);
}

.glass-effect {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.bg-dark-gradient {
    background: linear-gradient(45deg, #2c3e50, #3498db);
}

.card {
    border: none;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.rank-item {
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.rank-item:hover {
    background: var(--hover-color);
    transform: scale(1.05);
}

.rank-item.selected {
    background: var(--glow-color);
    box-shadow: 0 0 15px var(--glow-color);
}

.text-glow {
    color: var(--glow-color);
    text-shadow: 0 0 10px var(--glow-color);
}

.form-control {
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: var(--glow-color);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
}

.btn-primary {
    background: linear-gradient(45deg, #4a90e2, #357abd);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #357abd, #4a90e2);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
}

.modal-content {
    background-color: var(--dark-bg);
}

.modal-header {
    border-bottom: 1px solid var(--border-color);
}

.modal-footer {
    border-top: 1px solid var(--border-color);
}

::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--dark-bg);
}

::-webkit-scrollbar-thumb {
    background: var(--glow-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #357abd;
}
</style>

<script>
function selectCurrentRank(element) {
    const rankId = element.dataset.rankId;
    const rankName = element.dataset.rankName;
    const rankImage = element.dataset.rankImage;
    
    // Input'u güncelle
    document.getElementById('current_rank_id').value = rankId;
    
    // Görsel güncelleme
    const display = document.querySelector('.current-rank-display');
    display.querySelector('img').src = '../' + rankImage;
    display.querySelector('.rank-name').textContent = rankName;
    display.style.display = 'block';
    
    // Hedef rank seçimini göster
    document.getElementById('targetRankSection').style.display = 'block';
    
    // Modalı kapat
    $('#currentRankModal').modal('hide');
    
    updatePrice();
}

function selectTargetRank(element) {
    const rankId = element.dataset.rankId;
    const rankName = element.dataset.rankName;
    const rankImage = element.dataset.rankImage;
    const rankValue = element.dataset.rankValue;
    
    // Mevcut rank kontrolü
    const currentRankValue = document.querySelector(`[data-rank-id="${document.getElementById('current_rank_id').value}"]`).dataset.rankValue;
    if (parseInt(rankValue) <= parseInt(currentRankValue)) {
        alert('Hedef rank, mevcut ranktan yüksek olmalıdır.');
        return;
    }
    
    // Input'u güncelle
    document.getElementById('target_rank_id').value = rankId;
    
    // Görsel güncelleme
    const display = document.querySelector('.target-rank-display');
    display.querySelector('img').src = '../' + rankImage;
    display.querySelector('.rank-name').textContent = rankName;
    display.style.display = 'block';
    
    // Modalı kapat
    $('#targetRankModal').modal('hide');
    
    // Fiyat container'ı göster
    document.getElementById('priceContainer').style.display = 'block';
    
    updatePrice();
}

function updatePrice() {
    const currentRankId = document.getElementById('current_rank_id').value;
    const targetRankId = document.getElementById('target_rank_id').value;
    
    if (!currentRankId || !targetRankId) return;
    
    const gameId = document.querySelector('[name="game_id"]').value;
    
    // AJAX isteği
    $.get('../ajax/get_price.php', {
        game_id: gameId,
        current_rank_id: currentRankId,
        target_rank_id: targetRankId
    }, function(response) {
        if (response.success) {
            document.getElementById('totalPrice').textContent = response.price;
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>