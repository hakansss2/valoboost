<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = "Ana Sayfa - Profesyonel Elo Boost Hizmetleri";
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-area">
    <div class="container">
        <div class="hero-slider">
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="glass-card">
                            <img src="assets/img/games/valorant-hero.jpg" alt="Valorant Boost">
                            <div class="glass-card-content">
                                <h3>Valorant Boost</h3>
                                <p>Profesyonel oyuncularımızla hızlı ve güvenli boost hizmeti</p>
                                <a href="/login.php" class="btn btn-primary mt-4">Hemen Başla</a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="glass-card">
                            <img src="assets/img/games/lol-hero.jpg" alt="LoL Boost">
                            <div class="glass-card-content">
                                <h3>League of Legends Boost</h3>
                                <p>Hedeflediğiniz lige ulaşmanız için profesyonel destek</p>
                                <a href="/login.php" class="btn btn-primary mt-4">Hemen Başla</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </div>
</section>

<!-- Initialize Swiper -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Swiper('.swiper-container', {
        effect: 'cards',
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: 'auto',
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true
        },
        on: {
            init: function() {
                document.querySelector('.hero-slider').style.opacity = '1';
            }
        }
    });
});
</script>

<!-- Games Section -->
<section class="games-section">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Boost Hizmetlerimiz</h2>
            <p>İstediğiniz oyunda profesyonel destek alın</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="game-card">
                    <img src="assets/img/games/valorant-hero.jpg" alt="Valorant Boost">
                    <div class="game-card-content">
                        <h3>Valorant Boost</h3>
                        <p>Profesyonel Valorant oyuncularımız ile hedeflediğiniz ranka hızlı ve güvenli yükseliş</p>
                        <a href="/login.php" class="btn btn-primary">
                            Hemen Başla
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="game-card">
                    <img src="assets/img/games/lol-hero.jpg" alt="LoL Boost">
                    <div class="game-card-content">
                        <h3>League of Legends Boost</h3>
                        <p>Challenger seviye boosterlarımız ile istediğiniz lige ulaşın</p>
                        <a href="/login.php" class="btn btn-primary">
                            Hemen Başla
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Neden Biz?</h2>
            <p>Size en iyi hizmeti sunmak için çalışıyoruz</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="feature-box glass-effect">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Güvenli Hizmet</h4>
                    <p>SSL sertifikalı güvenli ödeme ve hesap koruması</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box glass-effect">
                    <i class="fas fa-bolt"></i>
                    <h4>Hızlı Teslimat</h4>
                    <p>Profesyonel ekibimiz ile hızlı boost hizmeti</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box glass-effect">
                    <i class="fas fa-headset"></i>
                    <h4>7/24 Destek</h4>
                    <p>Canlı destek ile sorularınıza anında yanıt</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box glass-effect">
                    <i class="fas fa-user-secret"></i>
                    <h4>Gizlilik</h4>
                    <p>%100 gizlilik garantisi ile güvenli boost</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-box glass-effect">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">1000+</div>
                    <div class="stat-text">Mutlu Müşteri</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box glass-effect">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number">4.9</div>
                    <div class="stat-text">Müşteri Memnuniyeti</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box glass-effect">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-number">5000+</div>
                    <div class="stat-text">Tamamlanan Sipariş</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box glass-effect">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number">2</div>
                    <div class="stat-text">Yıllık Deneyim</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Sıkça Sorulan Sorular</h2>
            <p>Merak ettiğiniz soruların cevapları</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item glass-effect">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Boost hizmeti hesabıma zarar verir mi?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Hayır, profesyonel oyuncularımız hesabınızı kendi hesapları gibi özenle kullanır. 
                                Anti-cheat korumalı sistemler ve manuel oyun garantisi veriyoruz.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item glass-effect">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Ne kadar sürede tamamlanır?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Boost işlemi seçtiğiniz pakete göre değişmekle birlikte, genellikle 1-3 gün içerisinde tamamlanır. 
                                Siparişinizin durumunu anlık olarak takip edebilirsiniz.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item glass-effect">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Ödeme yöntemleri nelerdir?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Kredi kartı, banka havalesi ve papara ile güvenli ödeme yapabilirsiniz. 
                                Tüm ödemeleriniz SSL sertifikası ile korunmaktadır.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item glass-effect">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Boost işlemi sırasında hesabımı kullanabilir miyim?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Boost işlemi sırasında hesabınızı kullanmamanızı öneriyoruz. 
                                Ancak duo boost seçeneği ile birlikte oynayarak da boost hizmeti alabilirsiniz.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <h2>Hemen Başlamaya Hazır mısınız?</h2>
                <p>Profesyonel boost hizmetimiz ile hedefinize ulaşın</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <a href="/login.php" class="btn btn-primary">Hemen Başla</a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Müşteri Görüşleri</h2>
            <p>Müşterilerimizin deneyimleri</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="testimonial-info">
                            <h4>Ahmet Y.</h4>
                            <p>Valorant Boost</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Çok profesyonel bir hizmet aldım. Hedeflediğim lige kısa sürede ulaştım. Kesinlikle tavsiye ederim!"
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="testimonial-info">
                            <h4>Mehmet K.</h4>
                            <p>LoL Boost</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Güvenilir ve hızlı hizmet. Booster ekibi çok ilgili ve profesyonel. Teşekkürler!"
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="testimonial-info">
                            <h4>Ayşe S.</h4>
                            <p>Valorant Boost</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "7/24 destek ve güvenli hizmet. İstediğim ranka ulaşmamda çok yardımcı oldular."
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Genel Cam Efekti */
.glass-effect {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    transition: all 0.3s ease;
}

.glass-effect:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
    box-shadow: 0 20px 40px -15px rgba(0, 194, 255, 0.2);
}

/* Game Cards */
.game-card.glass-effect {
    height: 100%;
    overflow: hidden;
}

.game-card.glass-effect img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.game-card.glass-effect:hover img {
    transform: scale(1.1);
}

.game-card .game-card-content {
    padding: 30px;
    background: linear-gradient(to top, rgba(9, 10, 26, 0.95), rgba(9, 10, 26, 0.5));
}

/* Feature Boxes */
.feature-box.glass-effect {
    padding: 40px 30px;
    text-align: center;
}

.feature-box i {
    font-size: 40px;
    margin-bottom: 20px;
    background: var(--gradient-1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Order Cards */
.order-card.glass-effect {
    padding: 25px;
}

.order-card .rank-info {
    margin: 15px 0;
    font-size: 18px;
    color: var(--primary-color);
}

.order-card .completion-time {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
}

/* Section Styles */
.games-section, .features-section, .latest-orders {
    padding: 100px 0;
    position: relative;
    background: var(--body-bg);
}

.games-section::before,
.features-section::before,
.latest-orders::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(0, 194, 255, 0.05) 0%, transparent 70%);
    pointer-events: none;
}

.section-title h2 {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 20px;
    background: var(--gradient-1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.section-title p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 18px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 