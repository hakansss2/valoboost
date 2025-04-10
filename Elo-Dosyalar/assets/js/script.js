// DOM yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Rank seçimi
    const rankItems = document.querySelectorAll('.rank-item');
    rankItems.forEach(item => {
        item.addEventListener('click', function() {
            const type = this.dataset.type; // current veya target
            const rankId = this.dataset.rankId;
            const gameId = this.dataset.gameId;
            
            // Diğer rank seçimlerini kaldır
            document.querySelectorAll(`.rank-item[data-type="${type}"]`).forEach(el => {
                el.classList.remove('selected');
            });
            
            // Bu rankı seç
            this.classList.add('selected');
            
            // Hidden input'ları güncelle
            document.getElementById(`${type}_rank`).value = rankId;
            
            // Fiyatı güncelle
            updatePrice();
        });
    });
    
    // Extra seçenekler
    const extraOptions = document.querySelectorAll('.extra-option');
    extraOptions.forEach(option => {
        option.addEventListener('change', function() {
            updatePrice();
        });
    });
    
    // Agent/Şampiyon seçimi
    const agentItems = document.querySelectorAll('.agent-item');
    agentItems.forEach(item => {
        item.addEventListener('click', function() {
            const agentId = this.dataset.agentId;
            
            // Diğer seçimleri kaldır
            document.querySelectorAll('.agent-item').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Bu ajanı seç
            this.classList.add('selected');
            
            // Hidden input'u güncelle
            document.getElementById('selected_agent').value = agentId;
        });
    });
});

// Fiyat güncelleme fonksiyonu
function updatePrice() {
    const currentRank = document.getElementById('current_rank').value;
    const targetRank = document.getElementById('target_rank').value;
    const gameId = document.getElementById('game_id').value;
    
    // Extra seçenekleri topla
    const extras = [];
    document.querySelectorAll('.extra-option:checked').forEach(option => {
        extras.push(option.value);
    });
    
    // AJAX isteği gönder
    fetch('calculate_price.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            current_rank: currentRank,
            target_rank: targetRank,
            game_id: gameId,
            extras: extras
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fiyatı güncelle
            document.getElementById('total_price').textContent = data.price;
            document.getElementById('price_input').value = data.price;
            
            // İndirim varsa göster
            if (data.discount) {
                document.getElementById('discount_container').style.display = 'block';
                document.getElementById('original_price').textContent = data.original_price;
                document.getElementById('discount_amount').textContent = data.discount;
            } else {
                document.getElementById('discount_container').style.display = 'none';
            }
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Fiyat hesaplama hatası:', error);
        showNotification('error', 'Fiyat hesaplanırken bir hata oluştu.');
    });
}

// Bildirim gösterme fonksiyonu
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // 3 saniye sonra bildirim kaybolsun
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Form gönderimi
document.getElementById('boost-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const currentRank = document.getElementById('current_rank').value;
    const targetRank = document.getElementById('target_rank').value;
    
    if (!currentRank || !targetRank) {
        showNotification('error', 'Lütfen mevcut ve hedef rankı seçin.');
        return;
    }
    
    if (parseInt(currentRank) >= parseInt(targetRank)) {
        showNotification('error', 'Hedef rank, mevcut ranktan yüksek olmalıdır.');
        return;
    }
    
    // Formu gönder
    this.submit();
});

// Oyun değiştirme
document.querySelectorAll('.game-button')?.forEach(button => {
    button.addEventListener('click', function() {
        const gameId = this.dataset.gameId;
        window.location.href = `boost.php?game_id=${gameId}`;
    });
});

// Admin paneli grafikleri
function initializeCharts() {
    if (document.getElementById('ordersChart')) {
        const ctx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Siparişler',
                    data: chartData.orders,
                    borderColor: '#007bff',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    if (document.getElementById('gamesChart')) {
        const ctx = document.getElementById('gamesChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartData.gameLabels,
                datasets: [{
                    data: chartData.gameCounts,
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

// DataTables inicializasyonu
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
            }
        });
    }
}); 