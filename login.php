<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepared Statements للأمان
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ? AND role = 'provider'");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($row['status'] === 'blocked') {
            $error = "🔒 هذا الحساب موقوف، يرجى التواصل مع الإدارة";
        } else {
            // جلب بيانات المزود الإضافية
            $provStmt = $conn->prepare("SELECT id, business_name, business_type, is_verified FROM providers WHERE user_id = ?");
            $provStmt->bind_param("i", $row['id']);
            $provStmt->execute();
            $provResult = $provStmt->get_result();

            if ($provResult->num_rows > 0) {
                $provider = $provResult->fetch_assoc();
                $_SESSION['provider_id'] = $provider['id'];
                $_SESSION['business_name'] = $provider['business_name'];
                $_SESSION['business_type'] = $provider['business_type'];
                $_SESSION['is_verified'] = $provider['is_verified'];
            }
            $provStmt->close();

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = 'provider';
            $_SESSION['last_login'] = time();

            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "❌ البريد الإلكتروني أو كلمة المرور غير صحيحة";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏪 دخول مزود الخدمة | TravelPlanner</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        /* 🎨 ألوانك المخصصة */
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-pink: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-green: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f5e6a3 100%);
            --dark: #1a1a2e;
            --light: #f8f9fa;

            /* متغيرات خاصة بمزودي الخدمة */
            --provider-primary: #11998e;
            --provider-secondary: #38ef7d;
            --provider-gradient: var(--gradient-green);
            --glass-bg: rgba(26, 26, 46, 0.92);
            --glass-border: rgba(17, 153, 142, 0.5);
            --shadow-provider: 0 15px 50px rgba(17, 153, 142, 0.4);
            --shadow-deep: 0 25px 80px rgba(0, 0, 0, 0.5);
            --text-primary: #ffffff;
            --text-secondary: rgba(248, 249, 250, 0.75);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background:
                var(--provider-gradient),
                url("image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 20% 80%, rgba(56, 239, 125, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(17, 153, 142, 0.15) 0%, transparent 50%);
            animation: plasmaMove 25s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes plasmaMove {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            25% {
                transform: translate(3%, 2%) rotate(1deg);
            }

            50% {
                transform: translate(0, 4%) rotate(0deg);
            }

            75% {
                transform: translate(-2%, 3%) rotate(-1deg);
            }
        }

        /* الجسيمات العائمة */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: var(--provider-primary);
            border-radius: 50%;
            opacity: 0.75;
            animation: float 18s infinite;
            box-shadow: 0 0 12px var(--provider-secondary);
        }

        .particle:nth-child(odd) {
            background: var(--provider-secondary);
            animation-duration: 22s;
        }

        .particle:nth-child(3n) {
            background: #667eea;
            width: 4px;
            height: 4px;
            animation-duration: 28s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.75;
            }

            90% {
                opacity: 0.75;
            }

            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* بطاقة الدخول */
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 50px 45px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow-provider), var(--shadow-deep);
            position: relative;
            z-index: 10;
            animation: slideUp 0.9s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--provider-gradient);
            animation: plasmaLine 3s linear infinite;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes plasmaLine {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }

        /* الشعار */
        .brand-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .brand-header::after {
            content: '';
            display: block;
            width: 70px;
            height: 3px;
            background: var(--provider-gradient);
            margin: 22px auto 0;
            border-radius: 3px;
        }

        .brand-icon {
            width: 85px;
            height: 85px;
            background: var(--provider-gradient);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            box-shadow: 0 15px 40px rgba(17, 153, 142, 0.55);
            animation: pulse 2.5s infinite;
            position: relative;
        }

        .brand-icon::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: var(--provider-gradient);
            border-radius: 28px;
            z-index: -1;
            animation: plasmaGlow 2.5s ease-in-out infinite;
            opacity: 0.5;
            filter: blur(8px);
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        @keyframes plasmaGlow {

            0%,
            100% {
                opacity: 0.4;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.03);
            }
        }

        .brand-icon i {
            font-size: 2.2rem;
            color: var(--light);
        }

        .brand-header h2 {
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.7rem;
            margin-bottom: 6px;
        }

        .brand-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .provider-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(17, 153, 142, 0.25);
            border: 1px solid var(--provider-primary);
            color: var(--light);
            padding: 7px 18px;
            border-radius: 50px;
            font-size: 0.87rem;
            font-weight: 600;
            margin-top: 14px;
        }

        .provider-badge i {
            color: #38ef7d;
        }

        .provider-badge.verified {
            border-color: #38ef7d;
            background: rgba(56, 239, 125, 0.2);
        }

        /* الحقول */
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.97rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--provider-primary);
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 26px;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--provider-secondary);
            font-size: 1.1rem;
            z-index: 3;
            transition: 0.35s;
        }

        .form-control {
            background: rgba(26, 26, 46, 0.7);
            border: 2px solid rgba(17, 153, 142, 0.5);
            border-radius: 18px;
            padding: 17px 52px 17px 22px;
            color: var(--text-primary);
            font-size: 1.02rem;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.4s ease;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 1;
        }

        .form-control:focus {
            background: rgba(26, 26, 46, 0.9);
            border-color: var(--provider-secondary);
            box-shadow: 0 0 0 5px rgba(17, 153, 142, 0.25), 0 15px 50px rgba(0, 0, 0, 0.4);
            outline: none;
            color: white;
        }

        .form-control:focus+i.input-icon,
        .input-wrapper:focus-within i.input-icon {
            color: var(--light);
            transform: translateY(-50%) scale(1.15);
        }

        .password-toggle {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 5px;
            z-index: 4;
            transition: 0.3s;
        }

        .password-toggle:hover {
            color: var(--provider-primary);
        }

        .input-wrapper.has-toggle .form-control {
            padding-left: 52px;
        }

        /* الزر */
        .btn-provider {
            background: var(--provider-gradient);
            color: var(--light);
            border: none;
            border-radius: 18px;
            padding: 17px 26px;
            font-size: 1.12rem;
            font-weight: 700;
            font-family: 'Tajawal', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: all 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            margin-top: 12px;
            box-shadow: 0 12px 35px rgba(17, 153, 142, 0.5);
        }

        .btn-provider::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: 0.6s;
        }

        .btn-provider:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 60px rgba(17, 153, 142, 0.65);
        }

        .btn-provider:hover::before {
            left: 100%;
        }

        .btn-provider:active {
            transform: translateY(-1px);
        }

        .btn-provider i {
            margin-left: 9px;
            transition: 0.35s;
        }

        .btn-provider:hover i {
            transform: translateX(-4px);
        }

        /* التنبيهات */
        .alert-custom {
            background: rgba(245, 87, 108, 0.18);
            border: 1px solid rgba(245, 87, 108, 0.5);
            border-right: 4px solid #f5576c;
            color: #ffd6e0;
            border-radius: 16px;
            padding: 15px 22px;
            margin-bottom: 28px;
            font-size: 0.97rem;
            display: flex;
            align-items: center;
            gap: 11px;
            animation: shake 0.5s ease-in-out;
        }

        .alert-custom.success {
            background: rgba(17, 153, 142, 0.18);
            border-color: rgba(56, 239, 125, 0.5);
            border-right-color: #38ef7d;
            color: #d4f8e8;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-6px);
            }

            40%,
            80% {
                transform: translateX(6px);
            }
        }

        /* الروابط */
        .footer-links {
            text-align: center;
            margin-top: 32px;
            padding-top: 28px;
            border-top: 1px solid rgba(17, 153, 142, 0.35);
        }

        .footer-links a {
            color: var(--provider-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.97rem;
            transition: 0.35s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .footer-links a:hover {
            color: var(--provider-secondary);
            transform: translateX(-4px);
        }

        /* ميزات المزود */
        .provider-features {
            display: flex;
            justify-content: center;
            gap: 28px;
            margin-top: 38px;
            padding-top: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
            color: var(--text-secondary);
            font-size: 0.82rem;
        }

        .feature-item i {
            font-size: 1.4rem;
            background: var(--provider-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* التحميل */
        .loading {
            display: none;
            width: 22px;
            height: 22px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--light);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 12px;
            vertical-align: middle;
        }

        .btn-provider.loading .loading {
            display: inline-block;
        }

        .btn-provider.loading span {
            opacity: 0.85;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 42px 32px;
                margin: 10px;
            }

            .brand-icon {
                width: 75px;
                height: 75px;
            }

            .brand-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- الجسيمات -->
    <div class="particles">
        <?php for ($i = 0; $i < 25; $i++): ?>
            <div class="particle" style="left: <?php echo rand(0, 100); ?>%; animation-delay: <?php echo rand(0, 18); ?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="login-card">
        <!-- الشعار -->
        <div class="brand-header">
            <div class="brand-icon">
                <i class="fas fa-store"></i>
            </div>
            <h2>لوحة مزود الخدمة</h2>
            <p>TravelPlanner Partner Portal</p>
            <div class="provider-badge">
                <i class="fas fa-check-circle"></i>
                شريك معتمد
            </div>
        </div>

        <!-- الرسائل -->
        <?php if (isset($error)): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert-custom success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- النموذج -->
        <form method="POST" id="loginForm" onsubmit="return handleLogin(event)">
            <!-- البريد -->
            <div class="input-wrapper">
                <label class="form-label">
                    <i class="fas fa-envelope"></i>
                    البريد الإلكتروني للشركة
                </label>
                <input type="email" name="email" class="form-control"
                    required placeholder="business@travelplanner.com"
                    pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                    autocomplete="email">
                <i class="fas fa-at input-icon"></i>
            </div>

            <!-- كلمة المرور -->
            <div class="input-wrapper has-toggle">
                <label class="form-label">
                    <i class="fas fa-lock"></i>
                    كلمة المرور
                </label>
                <input type="password" name="password" id="password" class="form-control"
                    required placeholder="••••••••••••" minlength="6"
                    autocomplete="current-password">
                <i class="fas fa-key input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>

            <!-- خيارات -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <label class="form-check mb-0">
                    <input type="checkbox" class="form-check-input" name="remember"
                        style="background: rgba(26,26,46,0.7); border-color: rgba(17,153,142,0.5);">
                    <span class="form-check-label me-2" style="color: var(--text-secondary); font-size: 0.92rem;">تذكرني</span>
                </label>
                <a href="forgot-password.php" class="text-decoration-none"
                    style="color: var(--provider-primary); font-size: 0.92rem; font-weight: 500;">
                    <i class="fas fa-key ms-1"></i>نسيت كلمة المرور؟
                </a>
            </div>

            <!-- زر الدخول -->
            <button type="submit" class="btn-provider" id="loginBtn">
                <span>دخول إلى لوحة الأعمال</span>
                <i class="fas fa-arrow-left"></i>
                <span class="loading"></span>
            </button>
        </form>

        <!-- الميزات -->
        <div class="provider-features">
            <div class="feature-item">
                <i class="fas fa-chart-line"></i>
                <span>إحصائيات فورية</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-calendar-check"></i>
                <span>إدارة الحجوزات</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-wallet"></i>
                <span>متابعة الأرباح</span>
            </div>
        </div>

        <!-- العودة -->
        <div class="footer-links">
            <a href="../index.php">
                <i class="fas fa-home"></i>
                العودة للصفحة الرئيسية
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function handleLogin(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            setTimeout(() => {}, 100);
            return true;
        }

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                const btn = document.getElementById('loginBtn');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.025)';
                this.parentElement.style.transition = 'transform 0.35s ease';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>