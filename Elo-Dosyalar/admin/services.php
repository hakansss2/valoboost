<?php
require_once 'includes/header.php';

// Hizmet silme
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    
    if ($service_id) {
        try {
            // Önce hizmete ait siparişleri kontrol et
            $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE service_id = ?");
            $stmt->execute([$service_id]);
            $orderCount = $stmt->fetchColumn();
            
            if ($orderCount > 0) {
                $_SESSION['error'] = "Bu hizmete ait siparişler olduğu için silinemez.";
            } else {
                // Hizmeti sil
                $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                
                $_SESSION['success'] = "Hizmet başarıyla silindi.";
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Hizmet silinirken bir hata oluştu.";
        }
    } else {
        $_SESSION['error'] = "Geçersiz hizmet ID'si.";
    }
    
    header("Location: services.php");
    exit;
}

// Hizmetleri getir
try {
    $stmt = $conn->prepare("
        SELECT s.*, g.name as game_name, COUNT(o.id) as order_count
        FROM services s
        LEFT JOIN games g ON s.game_id = g.id
        LEFT JOIN orders o ON s.id = o.service_id
        GROUP BY s.id
        ORDER BY g.name, s.name
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Hizmetler getirilirken bir hata oluştu.";
    $services = [];
}
?>

<!-- Başlık -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Hizmetler</h1>
    <a href="add_service.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Yeni Hizmet
    </a>
</div>

<!-- Başarı Mesajı -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<!-- Hata Mesajı -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<!-- Hizmetler Tablosu -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="servicesTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Oyun</th>
                        <th>Ad</th>
                        <th>Açıklama</th>
                        <th>Fiyat</th>
                        <th>Sipariş Sayısı</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo $service['id']; ?></td>
                            <td><?php echo htmlspecialchars($service['game_name']); ?></td>
                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                            <td>
                                <?php 
                                $description = htmlspecialchars($service['description']);
                                echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                ?>
                            </td>
                            <td><?php echo number_format($service['price'], 2); ?> ₺</td>
                            <td><?php echo $service['order_count']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $service['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo $service['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="edit_service.php?id=<?php echo $service['id']; ?>">
                                                <i class="fas fa-edit fa-fw"></i> Düzenle
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="confirmDelete(<?php echo $service['id']; ?>, <?php echo $service['order_count']; ?>)">
                                                <i class="fas fa-trash fa-fw"></i> Sil
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Silme Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="service_id" id="deleteServiceId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTables
    $('#servicesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        },
        order: [[1, 'asc'], [2, 'asc']] // Önce oyun sonra hizmet adına göre sırala
    });
});

// Silme Onayı
function confirmDelete(serviceId, orderCount) {
    if (orderCount > 0) {
        Swal.fire({
            title: 'Silinemez!',
            text: 'Bu hizmete ait siparişler olduğu için silinemez.',
            icon: 'error',
            confirmButtonText: 'Tamam'
        });
        return;
    }

    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu hizmet kalıcı olarak silinecek!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteServiceId').value = serviceId;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 