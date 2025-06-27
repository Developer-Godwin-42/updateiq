<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpdateIQ | Admin Login</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #2e59d9;
            --secondary-color: #858796;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            padding: 20px;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #4e73df, #3a56c8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--secondary-color);
            font-size: 0.95rem;
        }
        
        .form-control {
            height: 50px;
            padding: 0.75rem 1.25rem;
            border: 1px solid #d1d3e2;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: var(--light);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            background-color: #fff;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-login {
            height: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #4e73df 0%, #3a56c8 100%);
            border: none;
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
            background: linear-gradient(135deg, #3a56c8 0%, #4e73df 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }
        
        /* Loading spinner */
        .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.2em;
            display: none;
            margin-right: 8px;
            vertical-align: text-top;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h1>Welcome Back</h1>
            <p>Please login to access the admin panel</p>
        </div>
        
        
        <form id="loginForm" action="login_process.php" method="POST" novalidate>
            <div class="mb-4">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" class="form-control ps-3" id="username" name="username" 
                           placeholder="Enter your username" required>
                </div>
                <div class="invalid-feedback">Please enter your username</div>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between">
                    <label for="password" class="form-label">Password</label>
                    <!-- <a href="forgot-password.php" class="text-decoration-none small text-primary">Forgot Password?</a> -->
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" class="form-control ps-3" id="password" name="password" 
                           placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Please enter your password</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-login" id="loginButton">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="btn-text">Sign In</span>
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="small text-muted mb-0"> 2025 UpdateIQ. All rights reserved.</p>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var form = document.getElementById('loginForm');
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        // Show loading spinner and change button text
                        const loginBtn = document.getElementById('loginButton');
                        const spinner = loginBtn.querySelector('.spinner-border');
                        const btnText = loginBtn.querySelector('.btn-text');
                        
                        loginBtn.disabled = true;
                        spinner.style.display = 'inline-block';
                        btnText.textContent = 'Signing in...';
                    }
                    form.classList.add('was-validated');
                }, false);
            }, false);
        })();
        
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Add animation to inputs on focus
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('input-focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('input-focused');
            });
        });
    </script>
</body>
</html>