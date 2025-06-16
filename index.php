<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ - Intelligent Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">UpdateIQ</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a class="btn btn-primary" href="admin/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Banner Section -->
    <section id="home" class="banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="lead">Welcome to</span>
                    <h1 class="display-4">UpdateIQ Solutions</h1>
                    <p class="lead">Empowering your business with intelligent solutions and innovative technology to drive growth and success in the digital age.</p>
                    <a href="#about" class="btn btn-light btn-lg me-3">Learn More</a>
                    <a href="#how-it-works" class="btn btn-outline-light btn-lg">How It Works</a>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="assets/images/banner.png" alt="IQ Solutions" class="img-fluid rounded-3">
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="section-title">
                <h2>About Us</h2>
                <p>We are a team of passionate professionals dedicated to delivering exceptional solutions that drive real business value.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>Innovative Solutions</h4>
                        <p>We leverage cutting-edge technology to create innovative solutions that solve complex business challenges.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Expert Team</h4>
                        <p>Our team consists of experienced professionals who are experts in their respective fields.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Client-Centric</h4>
                        <p>We prioritize our clients' needs and work closely with them to achieve their business objectives.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Our simple 3-step process to get started with our services</p>
            </div>
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Discovery & Planning</h3>
                        <p>We start by understanding your business needs, goals, and challenges to create a tailored solution that meets your requirements.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Development & Implementation</h3>
                        <p>Our team works diligently to develop and implement the solution, ensuring quality and efficiency at every step.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Launch & Support</h3>
                        <p>We deploy the solution and provide ongoing support to ensure its success and your satisfaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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