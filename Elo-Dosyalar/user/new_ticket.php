<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Kullanıcı girişi kontrolü
if (!isUser()) {
    redirect('../login.php');
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4 techui-content dark-theme">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white">Yeni Destek Talebi</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-purple-light">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="support.php" class="text-purple-light">Destek</a></li>
                            <li class="breadcrumb-item active text-muted">Yeni Talep</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Destek Talebi Formu -->
        <div class="col-lg-8">
            <div class="card glass-effect" style="border-radius: 20px;">
                <div class="card-header bg-dark-gradient border-0">
                    <h5 class="mb-0 text-white">Destek Talebi Oluştur</h5>
                </div>
                <div class="card-body">
                    <form action="process_ticket.php" method="POST">
                        <div class="mb-4">
                            <label for="subject" class="form-label text-white">Konu</label>
                            <input type="text" class="form-control glass-effect text-white" id="subject" name="subject" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label text-white">Mesajınız</label>
                            <textarea class="form-control glass-effect text-white" id="message" name="message" rows="6" required></textarea>
                        </div>
                        <div class="text-end">
                            <a href="support.php" class="btn btn-light me-2">İptal</a>
                            <button type="submit" class="btn btn-glow btn-primary">Gönder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ Sidebar -->
        <div class="col-lg-4">
            <!-- Hızlı Bilgiler -->
            <div class="row g-3">
                <div class="col-lg-12 col-md-4">
                    <div class="card glass-effect hover-effect" style="border-radius: 15px;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-glow rounded-circle me-3">
                                    <i class="fas fa-headset text-white"></i>
                                </div>
                                <div>
                                    <h6 class="text-white mb-1">7/24 Destek</h6>
                                    <p class="text-muted small mb-0">Ortalama yanıt: 15 dk</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 col-md-4">
                    <div class="card glass-effect hover-effect" style="border-radius: 15px;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-glow rounded-circle me-3">
                                    <i class="fab fa-discord text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-white mb-1">Discord Destek</h6>
                                    <p class="text-muted small mb-0">Anlık destek için</p>
                                </div>
                                <a href="#" class="btn btn-sm btn-primary">Katıl</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 col-md-4">
                    <div class="card glass-effect" style="border-radius: 15px;">
                        <div class="card-body p-3">
                            <h6 class="text-white mb-3">Sık Sorulan Sorular</h6>
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item glass-effect border-0 rounded mb-2 py-2 px-3">
                                    <small class="text-white">Destek yanıt süreleri</small>
                                </a>
                                <a href="#" class="list-group-item glass-effect border-0 rounded mb-2 py-2 px-3">
                                    <small class="text-white">Sipariş sorunları</small>
                                </a>
                                <a href="#" class="list-group-item glass-effect border-0 rounded py-2 px-3">
                                    <small class="text-white">Ödeme sorunları</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Theme */
.dark-theme {
    background-color: #0a0b1e;
    color: #fff;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Form Controls */
.form-control {
    border: none;
    padding: 0.8rem 1rem;
}

.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
}

/* Buttons */
.btn-glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.4);
}

/* Background Gradients */
.bg-dark-gradient {
    background: linear-gradient(135deg, #1a1b3a 0%, #0a0b1e 100%);
}

/* Hover Effects */
.hover-effect {
    transition: all 0.3s ease;
}

.hover-effect:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(106, 17, 203, 0.2);
}

/* Avatar Sizes */
.avatar-sm {
    height: 2rem;
    width: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* List Group */
.list-group-item {
    background: transparent;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    transform: translateX(5px);
}

/* Glow Effects */
.bg-glow {
    background: rgba(106, 17, 203, 0.3);
}
</style>

<?php require_once 'includes/footer.php'; ?> 