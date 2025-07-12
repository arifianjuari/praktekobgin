<?php
// login_form.php: tampilan form login, dipanggil dari LoginController
$error = $error ?? ($_SESSION['error'] ?? '');
$success = $success ?? ($_SESSION['success'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Praktekobgin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #198754;
            --secondary-color: #0d6efd;
            --accent-color: #f8f9fa;
            --border-radius: 12px;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container { 
            max-width: 420px; 
            width: 100%;
            margin: 0 auto; 
            padding: 40px; 
            background: #fff; 
            border-radius: var(--border-radius); 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }
        
        .header-container { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        
        .header-container h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .form-group { 
            margin-bottom: 20px; 
            position: relative;
        }
        
        .form-control { 
            width: 100%; 
            padding: 12px 15px; 
            border-radius: 8px; 
            border: 2px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            animation: shake 0.5s;
        }
        
        .form-control.is-valid {
            border-color: var(--primary-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .btn-login { 
            width: 100%; 
            padding: 12px; 
            border-radius: 8px; 
            background: var(--primary-color); 
            color: #fff; 
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover:not(:disabled) {
            background: #146c43;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
        
        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        
        .btn-login .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Ripple effect */
        .btn-login::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:active::after {
            width: 300px;
            height: 300px;
        }
        
        .alert { 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger { 
            border-left: 4px solid #dc3545; 
            background: #f8d7da; 
            color: #842029; 
        }
        
        .alert-success { 
            border-left: 4px solid #198754; 
            background: #d1e7dd; 
            color: #0f5132; 
        }
        
        .form-check {
            margin-bottom: 20px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .links-container {
            margin-top: 20px;
            text-align: center;
        }
        
        .links-container a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .links-container a:hover {
            color: #0a58ca;
            text-decoration: underline;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="header-container">
                <h2>Login</h2>
                <p class="text-muted">Silakan masuk ke akun Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="post" id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="username" class="form-label fw-medium">Username</label>
                    <input type="text" name="username" id="username" class="form-control" 
                           placeholder="Masukkan username" required autofocus>
                    <div class="invalid-feedback">
                        Username tidak boleh kosong
                    </div>
                </div>
                
                <div class="form-group position-relative">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Masukkan password" required>
                    <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                    <div class="invalid-feedback">
                        Password tidak boleh kosong
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="remember_me" id="remember_me" class="form-check-input">
                    <label for="remember_me" class="form-check-label">Ingat saya</label>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                
                <button type="submit" class="btn-login" id="loginButton">
                    <span class="button-text">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </span>
                    <span class="spinner-border spinner-border-sm text-light d-none" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                </button>
            </form>
            
            <div class="links-container">
                <a href="forgot_password.php">Lupa password?</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Form validation and submission
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const buttonText = loginButton.querySelector('.button-text');
        const spinner = loginButton.querySelector('.spinner-border');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        // Real-time validation
        function validateInput(input) {
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
                return false;
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                return true;
            }
        }

        // Validate on blur
        usernameInput.addEventListener('blur', function() {
            if (this.value) validateInput(this);
        });

        passwordInput.addEventListener('blur', function() {
            if (this.value) validateInput(this);
        });

        // Remove validation classes on input
        usernameInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
            if (this.value && this.classList.contains('is-valid')) {
                this.classList.remove('is-valid');
            }
        });

        passwordInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
            if (this.value && this.classList.contains('is-valid')) {
                this.classList.remove('is-valid');
            }
        });

        // Form submission
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate all inputs
            const isUsernameValid = validateInput(usernameInput);
            const isPasswordValid = validateInput(passwordInput);

            if (!isUsernameValid || !isPasswordValid) {
                // Shake animation for button
                loginButton.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    loginButton.style.animation = '';
                }, 500);
                
                // Focus on first invalid input
                if (!isUsernameValid) {
                    usernameInput.focus();
                } else if (!isPasswordValid) {
                    passwordInput.focus();
                }
                return;
            }

            // Disable button and show loading
            loginButton.disabled = true;
            buttonText.classList.add('d-none');
            spinner.classList.remove('d-none');

            // Submit form
            this.submit();
        });

        // Enter key support for better UX
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (document.activeElement === usernameInput || document.activeElement === passwordInput)) {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });

        // Auto-focus username field on load
        window.addEventListener('load', function() {
            usernameInput.focus();
        });
    </script>
</body>
</html>
