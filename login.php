<?php
session_start();
include '../config/db.php';

// إذا كان مسجلاً بالفعل
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'traveler') {
    header("Location: index.php");
    exit();
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepared Statements للأمان
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'traveler'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) { // ⚠️ للإنتاج: password_verify()
            if ($user['status'] === 'active') {
                // جلب بيانات المسافر الإضافية
                $travelerStmt = $conn->prepare("SELECT id, full_name, phone, loyalty_points FROM travelers WHERE user_id = ?");
                $travelerStmt->bind_param("i", $user['id']);
                $travelerStmt->execute();
                $travelerResult = $travelerStmt->get_result();

                if ($travelerResult->num_rows > 0) {
                    $traveler = $travelerResult->fetch_assoc();
                    $_SESSION['traveler_id'] = $traveler['id'];
                    $_SESSION['full_name'] = $traveler['full_name'];
                    $_SESSION['loyalty_points'] = $traveler['loyalty_points'];
                }
                $travelerStmt->close();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                header("Location: index.php");
                exit();
            } else {
                $msg = "❌ حسابك محظور. يرجى التواصل مع الدعم.";
                $msgType = "danger";
            }
        } else {
            $msg = "❌ كلمة المرور غير صحيحة";
            $msgType = "danger";
        }
    } else {
        $msg = "❌ البريد الإلكتروني غير مسجل";
        $msgType = "danger";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✈️ تسجيل الدخول | TravelPlanner</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

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

            /* متغيرات خاصة بالمسافرين */
            --traveler-primary: #f093fb;
            --traveler-secondary: #f5576c;
            --traveler-gradient: var(--gradient-pink);
            --glass-bg: rgba(26, 26, 46, 0.92);
            --glass-border: rgba(245, 87, 108, 0.4);
            --shadow-traveler: 0 15px 50px rgba(245, 87, 108, 0.35);
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
                var(--traveler-gradient),
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
                radial-gradient(circle at 20% 80%, rgba(245, 87, 108, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(240, 147, 251, 0.15) 0%, transparent 50%);
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
            background: var(--traveler-primary);
            border-radius: 50%;
            opacity: 0.75;
            animation: float 18s infinite;
            box-shadow: 0 0 12px var(--traveler-secondary);
        }

        .particle:nth-child(odd) {
            background: var(--traveler-secondary);
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
        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 50px 45px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow-traveler), var(--shadow-deep);
            position: relative;
            z-index: 10;
            animation: slideUp 0.9s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--traveler-gradient);
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
        .auth-logo {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .auth-logo::after {
            content: '';
            display: block;
            width: 70px;
            height: 3px;
            background: var(--traveler-gradient);
            margin: 22px auto 0;
            border-radius: 3px;
        }

        .brand-icon {
            width: 85px;
            height: 85px;
            background: var(--traveler-gradient);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            box-shadow: 0 15px 40px rgba(245, 87, 108, 0.55);
            animation: pulse 2.5s infinite;
            position: relative;
        }

        .brand-icon::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: var(--traveler-gradient);
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

        .auth-logo h1 {
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.7rem;
            margin-bottom: 6px;
        }

        .auth-logo p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .traveler-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(245, 87, 108, 0.2);
            border: 1px solid var(--traveler-secondary);
            color: var(--light);
            padding: 7px 18px;
            border-radius: 50px;
            font-size: 0.87rem;
            font-weight: 600;
            margin-top: 14px;
        }

        .traveler-badge i {
            color: #f093fb;
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
            color: var(--traveler-primary);
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
            color: var(--traveler-secondary);
            font-size: 1.1rem;
            z-index: 3;
            transition: 0.35s;
        }

        .form-control {
            background: rgba(26, 26, 46, 0.7);
            border: 2px solid rgba(245, 87, 108, 0.4);
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
            border-color: var(--traveler-secondary);
            box-shadow: 0 0 0 5px rgba(245, 87, 108, 0.25), 0 15px 50px rgba(0, 0, 0, 0.4);
            outline: none;
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
            color: var(--traveler-primary);
        }

        .input-wrapper.has-toggle .form-control {
            padding-left: 52px;
        }

        /* الزر */
        .btn-traveler {
            background: var(--traveler-gradient);
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
            box-shadow: 0 12px 35px rgba(245, 87, 108, 0.5);
        }

        .btn-traveler::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: 0.6s;
        }

        .btn-traveler:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 60px rgba(245, 87, 108, 0.65);
        }

        .btn-traveler:hover::before {
            left: 100%;
        }

        .btn-traveler:active {
            transform: translateY(-1px);
        }

        .btn-traveler i {
            margin-left: 9px;
            transition: 0.35s;
        }

        .btn-traveler:hover i {
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

        /* الخيارات */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.92rem;
            cursor: pointer;
        }

        .remember-me input {
            accent-color: var(--traveler-secondary);
            width: 16px;
            height: 16px;
        }

        .forgot-link {
            color: var(--traveler-primary);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: 0.35s;
        }

        .forgot-link:hover {
            color: var(--traveler-secondary);
            text-decoration: underline;
        }

        /* الفاصل */
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-secondary);
            font-size: 0.87rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(245, 87, 108, 0.3);
        }

        .divider span {
            padding: 0 15px;
        }

        /* الدخول الاجتماعي */
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 25px;
        }

        .btn-social {
            padding: 13px;
            border-radius: 16px;
            border: 1px solid rgba(245, 87, 108, 0.3);
            background: rgba(26, 26, 46, 0.6);
            color: var(--text-primary);
            cursor: pointer;
            transition: 0.35s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Tajawal', sans-serif;
            font-size: 0.92rem;
            text-decoration: none;
        }

        .btn-social:hover {
            border-color: var(--traveler-secondary);
            background: rgba(245, 87, 108, 0.15);
            transform: translateY(-2px);
        }

        .btn-social i {
            font-size: 1.1rem;
        }

        /* التذييل */
        .auth-footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid rgba(245, 87, 108, 0.3);
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .auth-footer a {
            color: var(--traveler-primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.35s;
        }

        .auth-footer a:hover {
            color: var(--traveler-secondary);
            text-decoration: underline;
        }

        .provider-link {
            display: block;
            margin-top: 12px;
            font-size: 0.87rem;
            color: var(--text-secondary);
        }

        .provider-link a {
            color: var(--primary);
            text-decoration: none;
        }

        .provider-link a:hover {
            color: var(--secondary);
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

        .btn-traveler.loading .loading {
            display: inline-block;
        }

        .btn-traveler.loading span {
            opacity: 0.85;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 576px) {
            .auth-card {
                padding: 42px 32px;
                margin: 10px;
            }

            .brand-icon {
                width: 75px;
                height: 75px;
            }

            .auth-logo h1 {
                font-size: 1.5rem;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- الجسيمات -->
    <div class="particles" id="particles"></div>

    <div class="auth-card" data-aos="zoom-in">
        <!-- الشعار -->
        <div class="auth-logo">
            <div class="brand-icon">
                <i class="fas fa-plane-departure"></i>
            </div>
            <h1>TravelPlanner</h1>
            <p>سجل دخولك وابدأ رحلتك</p>
            <div class="traveler-badge">
                <i class="fas fa-gem"></i>
                مسافر مميز
            </div>
        </div>

        <!-- الرسائل -->
        <?php if ($msg): ?>
            <div class="alert-custom <?php echo $msgType === 'success' ? 'success' : ''; ?>">
                <i class="fas fa-<?php echo $msgType === 'danger' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- النموذج -->
        <form method="POST" id="loginForm" onsubmit="return handleLogin(event)">
            <!-- البريد -->
            <div class="input-wrapper">
                <label class="form-label">
                    <i class="fas fa-envelope"></i>
                    البريد الإلكتروني
                </label>
                <input type="email" name="email" class="form-control"
                    required placeholder="traveler@email.com"
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

            <!-- الخيارات -->
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> تذكرني
                </label>
                <a href="forgot-password.php" class="forgot-link">
                    <i class="fas fa-key ms-1"></i>نسيت كلمة المرور؟
                </a>
            </div>

            <!-- زر الدخول -->
            <button type="submit" class="btn-traveler" id="loginBtn">
                <span>تسجيل الدخول والبدء</span>
                <i class="fas fa-arrow-left"></i>
                <span class="loading"></span>
            </button>
        </form>



        <!-- التذييل -->
        <div class="auth-footer">
            ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
            <span class="provider-link">
                <a href="../index.php">
                    <i class="fas fa-close me-1"></i>العودة للرئيسية
                </a>
            </span>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // إنشاء الجسيمات
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 25; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 20) + 's';
                container.appendChild(particle);
            }
        }
        createParticles();

        // إظهار/إخفاء كلمة المرور
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

        // تأثير التحميل
        function handleLogin(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            setTimeout(() => {}, 100);
            return true;
        }

        // تأثيرات الحقول
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