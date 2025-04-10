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
    pageLength: 25,
    order: [[0, 'desc']],
    responsive: true,
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
});

// SweetAlert2 Toast bildirimleri için varsayılan ayarlar
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
    const formElements = form.querySelectorAll('input, select, textarea');

    formElements.forEach(element => {
        element.addEventListener('change', () => {
            formChanged = true;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

// AJAX form gönderimi
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message || 'İşlem başarıyla tamamlandı.'
                });

                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message || 'Bir hata oluştu.'
                });
            }
        })
        .catch(error => {
            Toast.fire({
                icon: 'error',
                title: 'Bir hata oluştu.'
            });
            console.error('Error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
}

// Sipariş iptal etme
function cancelOrder(orderId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu siparişi iptal etmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Evet, iptal et',
        cancelButtonText: 'Vazgeç'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: 'Sipariş başarıyla iptal edildi'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || 'Sipariş iptal edilemedi'
                    });
                }
            })
            .catch(error => {
                Toast.fire({
                    icon: 'error',
                    title: 'Bir hata oluştu'
                });
                console.error('Error:', error);
            });
        }
    });
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips'i etkinleştir
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // DataTables'ı başlat
    const tables = document.querySelectorAll('.datatable');
    tables.forEach(table => {
        $(table).DataTable();
    });
}); 