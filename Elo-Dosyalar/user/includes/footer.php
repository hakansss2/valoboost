    <!-- Footer -->
    <footer class="footer mt-auto py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="footer-content text-center text-lg-start">
                        <span class="text-glow">&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_title'); ?></span>
                        <span class="text-muted mx-2">|</span>
                        <span class="text-muted">Tüm Hakları Saklıdır</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer-links text-center text-lg-end mt-3 mt-lg-0">
                        <a href="about.php" class="footer-link">Hakkımızda</a>
                        <a href="terms.php" class="footer-link">Kullanım Şartları</a>
                        <a href="privacy.php" class="footer-link">Gizlilik Politikası</a>
                        <a href="contact.php" class="footer-link">İletişim</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- TechUI Theme JS -->
    <script src="../theme/assets/app-3e0c4d1e.js"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/user.js"></script>

    <style>
    /* Footer Styles */
    .footer {
        background: rgba(26, 27, 58, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.8);
    }

    .text-glow {
        color: var(--neon-blue);
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
    }

    .footer-link {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        margin: 0 15px;
        transition: all 0.3s ease;
        position: relative;
    }

    .footer-link:hover {
        color: var(--neon-blue);
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
    }

    .footer-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -4px;
        left: 0;
        background: linear-gradient(to right, var(--neon-purple), var(--neon-blue));
        transition: width 0.3s ease;
    }

    .footer-link:hover::after {
        width: 100%;
    }

    @media (max-width: 991.98px) {
        .footer-link {
            margin: 0 10px;
        }
    }
    </style>
</body>
</html>