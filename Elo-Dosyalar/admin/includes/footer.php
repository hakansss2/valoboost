    </div>
    <!-- /Main Content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script>
    // DataTables Türkçe dil desteği
    const dataTablesTurkish = {
        "emptyTable": "Tabloda veri bulunmuyor",
        "info": "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
        "infoEmpty": "Kayıt yok",
        "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
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
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        order: [[0, 'desc']],
        columnDefs: [{
            targets: 'no-sort',
            orderable: false
        }]
    });

    // SweetAlert2 varsayılan ayarları
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#1e293b',
        color: '#f8fafc'
    });

    // Sipariş onaylama fonksiyonu
    function acceptOrder(orderId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu siparişi onaylamak istediğinize emin misiniz?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#dc2626',
            confirmButtonText: 'Evet, Onayla',
            cancelButtonText: 'İptal',
            background: '#1e293b',
            color: '#f8fafc'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'accept_order.php?id=' + orderId;
            }
        });
    }

    // Booster atama fonksiyonu
    function assignBooster(orderId) {
        document.getElementById('orderIdInput').value = orderId;
        var modal = new bootstrap.Modal(document.getElementById('assignBoosterModal'));
        modal.show();
    }

    // Form submit işlemi
    document.addEventListener('DOMContentLoaded', function() {
        const assignBoosterForm = document.getElementById('assignBoosterForm');
        if (assignBoosterForm) {
            assignBoosterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const modalElement = document.getElementById('assignBoosterModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...';
                
                const formData = new FormData(this);
                
                fetch('assign_booster.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: data.message || 'Booster başarıyla atandı.',
                            showConfirmButton: false,
                            timer: 1500,
                            background: '#1e293b',
                            color: '#f8fafc'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: error.message || 'Bir hata oluştu. Lütfen tekrar deneyin.',
                        confirmButtonText: 'Tamam',
                        background: '#1e293b',
                        color: '#f8fafc'
                    });
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="mdi mdi-check me-2"></i>Ata';
                });
            });
        }
    });

    // DataTables dark theme ayarları
    $(document).ready(function() {
        $('.table').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    "initComplete": function(settings, json) {
                        // DataTables dark theme stilleri
                        $('.dataTables_wrapper').addClass('text-white');
                        $('.dataTables_length, .dataTables_filter, .dataTables_info').addClass('text-white');
                        $('.dataTables_length select').addClass('form-select form-select-sm bg-dark text-white border-secondary');
                        $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-secondary');
                        $('.page-link').addClass('bg-dark text-white border-secondary');
                        $('.page-item.disabled .page-link').addClass('bg-secondary');
                        $('.page-item.active .page-link').addClass('bg-primary border-primary');
                    }
                });
            }
        });

        // Fade-in animasyonu
        $('.fade-in').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's'
            });
        });
    });
    </script>
</body>
</html> 