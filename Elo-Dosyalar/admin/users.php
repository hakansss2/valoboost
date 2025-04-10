<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Yönetici kontrolü
if (!isAdmin()) {
    redirect('../login.php');
}

// Düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $balance = $_POST['balance'];

    // Kullanıcı bilgilerini güncelle (bakiye dahil)
    $sql = "UPDATE users SET username = '$username', email = '$email', role = '$role', status = '$status', balance = $balance WHERE id = $id";
    $conn->query($sql);
    
    header('Location: users.php');
    exit;
}

// Kullanıcıları getir
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as total_orders,
               SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id, u.username, u.email, u.role, u.status, u.created_at, u.balance
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Kullanıcılar alınırken bir hata oluştu: " . $e->getMessage();
    $users = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="mdi mdi-account-group me-2"></i>Kullanıcı Yönetimi
                        </h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="mdi mdi-plus me-2"></i>Yeni Kullanıcı
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı Adı</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>Durum</th>
                                    <th>Bakiye</th>
                                    <th>Toplam Sipariş</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random" 
                                                     class="rounded-circle me-2" width="32" height="32">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php
                                            $role_badges = [
                                                'admin' => 'danger',
                                                'user' => 'primary',
                                                'booster' => 'success'
                                            ];
                                            $role_names = [
                                                'admin' => 'Yönetici',
                                                'user' => 'Kullanıcı',
                                                'booster' => 'Booster'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $role_badges[$user['role']]; ?>">
                                                <?php echo $role_names[$user['role']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-success fw-bold">
                                                <?php echo number_format($user['balance'] ?? 0, 2, ',', '.'); ?> ₺
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo $user['total_orders']; ?></span>
                                                <?php if ($user['total_orders'] > 0): ?>
                                                    <small class="text-success">
                                                        (<?php echo $user['completed_orders']; ?> tamamlandı)
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kullanıcı Ekleme Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-account-plus me-2"></i>Yeni Kullanıcı Ekle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">E-posta</label>
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Şifre</label>
                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Rol</label>
                        <select name="role" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Rol seçin...</option>
                            <option value="user">Kullanıcı</option>
                            <option value="booster">Booster</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-2"></i>Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kullanıcı Düzenleme Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-account-edit me-2"></i>Kullanıcı Düzenle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label text-white">Kullanıcı Adı</label>
                        <input type="text" name="username" id="editUsername" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">E-posta</label>
                        <input type="email" name="email" id="editEmail" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Bakiye</label>
                        <div class="input-group">
                            <input type="number" name="balance" id="editBalance" class="form-control bg-dark text-white border-secondary" step="0.01" required>
                            <span class="input-group-text bg-dark text-white border-secondary">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Rol</label>
                        <select name="role" id="editRole" class="form-select bg-dark text-white border-secondary" required>
                            <option value="user">Kullanıcı</option>
                            <option value="booster">Booster</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Durum</label>
                        <select name="status" id="editStatus" class="form-select bg-dark text-white border-secondary" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// DataTables başlat
$(document).ready(function() {
    $('#usersTable').DataTable({
        order: [[0, 'desc']]
    });

    // Kullanıcı ekleme formu
    const addUserForm = document.getElementById('addUserForm');
    const addUserModal = document.getElementById('addUserModal');
    const bsAddUserModal = bootstrap.Modal.getInstance(addUserModal);

    addUserForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ekleniyor...';
        
        fetch('add_user.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Modalı kapat
                if (bsAddUserModal) bsAddUserModal.hide();
                else (new bootstrap.Modal(addUserModal)).hide();
                
                // Formu temizle
                this.reset();
                
                // Başarı mesajını göster
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Kullanıcı başarıyla eklendi',
                    showConfirmButton: false,
                    timer: 1500,
                    background: '#1e293b',
                    color: '#fff'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: data.message,
                    confirmButtonText: 'Tamam',
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Bir hata oluştu, lütfen tekrar deneyin.',
                confirmButtonText: 'Tamam',
                background: '#1e293b',
                color: '#fff'
            });
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="mdi mdi-check me-2"></i>Ekle';
        });
    });

    // Kullanıcı düzenleme formu
    const editUserForm = document.getElementById('editUserForm');
    const editUserModal = document.getElementById('editUserModal');
    const bsEditUserModal = bootstrap.Modal.getInstance(editUserModal);

    editUserForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        fetch('edit_user.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Bir hata oluştu');
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    });
});

// Kullanıcı düzenleme
function editUser(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editStatus').value = user.status;
    document.getElementById('editBalance').value = user.balance;
    
    const editUserModal = document.getElementById('editUserModal');
    const bsEditUserModal = bootstrap.Modal.getInstance(editUserModal);
    
    if (bsEditUserModal) bsEditUserModal.show();
    else (new bootstrap.Modal(editUserModal)).show();
}

// Kullanıcı silme
function deleteUser(id, username) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: `"${username}" kullanıcısını silmek istediğinize emin misiniz?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal',
        background: '#1e293b',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_user.php?id=${id}`;
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 