 <!-- Footer -->
 <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="text-white mb-4">UpdateIQ Solutions</h5>
                    <p>Empowering businesses with intelligent solutions and innovative technology to drive growth and success in the digital age.</p>
                    <div class="social-links mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="footer-links">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a href="#home">Home</a></li>
                            <li><a href="#about">About Us</a></li>
                            <li><a href="#how-it-works">How It Works</a></li>
                            <li><a href="blog.php">Blog</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="footer-links">
                        <h5>Services</h5>
                        <ul>
                            <li><a href="#">Web Development</a></li>
                            <li><a href="#">Mobile Apps</a></li>
                            <li><a href="#">UI/UX Design</a></li>
                            <li><a href="#">Digital Marketing</a></li>
                            <li><a href="#">Cloud Solutions</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="footer-links">
                        <h5>Contact Us</h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> 123 Business St, City</li>
                            <li><i class="fas fa-phone me-2"></i> +1 234 567 8900</li>
                            <li><i class="fas fa-envelope me-2"></i> info@iqsolutions.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p class="mb-0">&copy; 2025 UpdateIQ Solutions. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add active class to nav links on scroll
        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY;
            
            document.querySelectorAll('section').forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${sectionId}` || 
                           (sectionId === 'home' && link.getAttribute('href') === 'index.php')) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>