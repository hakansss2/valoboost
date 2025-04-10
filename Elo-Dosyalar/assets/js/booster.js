// DataTables Türkçe dil desteği
const dataTablesTurkish = {
    "emptyTable": "Tabloda herhangi bir veri mevcut değil",
    "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
    "infoEmpty": "Kayıt yok",
    "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
    "infoThousands": ".",
    "lengthMenu": "Sayfada _MENU_ kayıt göster",
    "loadingRecords": "Yükleniyor...",
    "processing": "İşleniyor...",
    "search": "Ara:",
    "zeroRecords": "Eşleşen kayıt bulunamadı",
    "paginate": {
        "first": "İlk",
        "last": "Son",
        "next": "Sonraki",
        "previous": "Önceki"
    }
};

// DataTables varsayılan ayarları
$.extend(true, $.fn.dataTable.defaults, {
    language: dataTablesTurkish,
    responsive: true,
    pageLength: 25,
    order: [[0, 'desc']]
});

// SweetAlert2 varsayılan ayarları
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
});

// Form değişikliklerini izle
function watchFormChanges(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    let formChanged = false;
    const originalData = new FormData(form);

    function checkChanges() {
        const currentData = new FormData(form);
        formChanged = false;

        for (let pair of originalData.entries()) {
            if (currentData.get(pair[0]) !== pair[1]) {
                formChanged = true;
                break;
            }
        }

        return formChanged;
    }

    form.addEventListener('change', () => {
        formChanged = checkChanges();
    });

    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

// AJAX form gönderimi
function submitFormAjax(formId, successCallback = null) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;

        // Submit butonunu devre dışı bırak ve loading göster
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...';

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });

                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(error => {
            Toast.fire({
                icon: 'error',
                title: 'Bir hata oluştu!'
            });
            console.error('Error:', error);
        })
        .finally(() => {
            // Submit butonunu tekrar aktif et
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });
}

// Sipariş ilerleme güncelleme
function updateOrderProgress(orderId, progress) {
    fetch('update_progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&progress=${progress}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Toast.fire({
                icon: 'success',
                title: 'İlerleme güncellendi'
            });
        } else {
            Toast.fire({
                icon: 'error',
                title: data.message
            });
        }
    })
    .catch(error => {
        Toast.fire({
            icon: 'error',
            title: 'Bir hata oluştu!'
        });
        console.error('Error:', error);
    });
}

// Sayfa yüklendiğinde çalışacak kodlar
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips'i aktifleştir
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
}); 