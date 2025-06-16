        </div><!-- End of .container-fluid -->
    </main><!-- End of .main-content -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>
    
    <!-- Page-specific JS -->
    <?php if (isset($page_js)): ?>
        <script src="assets/js/<?php echo $page_js; ?>"></script>
    <?php endif; ?>
    
    <script>
        // Toggle sidebar on mobile
        $('.toggle-sidebar').on('click', function() {
            $('.sidebar').toggleClass('active');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() < 992) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.toggle-sidebar').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeTo(500, 0).slideUp(500, function(){
                $(this).remove(); 
            });
        }, 5000);
        
        // Preloader
        $(window).on('load', function() {
            $('.preloader').fadeOut('slow');
        });
    </script>
</body>
</html>
