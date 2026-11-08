<?php
session_start();
include '../config/db.php';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    // التحقق من المدخلات
    $errors = [];

    if (strlen($username) < 3) $errors[] = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صالح";
    if (strlen($password) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    if ($password !== $confirm_password) $errors[] = "كلمات المرور غير متطابقة";
    if (strlen($full_name) < 2) $errors[] = "الاسم الكامل مطلوب";

    // التحقق من التكرار
    if (empty($errors)) {
        $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkUser->bind_param("ss", $username, $email);
        $checkUser->execute();

        if ($checkUser->get_result()->num_rows > 0) {
            $errors[] = "اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً";
        }
        $checkUser->close();
    }

    // إنشاء الحساب
    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // إنشاء المستخدم
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'traveler')");
            $stmt->bind_param("sss", $username, $email, $password); // ⚠️ للإنتاج: password_hash()
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();

            // إنشاء بيانات المسافر
            $travelerStmt = $conn->prepare("INSERT INTO travelers (user_id, full_name, phone) VALUES (?, ?, ?)");
            $travelerStmt->bind_param("iss", $user_id, $full_name, $phone);
            $travelerStmt->execute();
            $travelerStmt->close();

            $conn->commit();

            $msg = "✅ تم إنشاء حسابك بنجاح! يمكنك تسجيل الدخول الآن";
            $msgType = "success";

            // إعادة التوجيه بعد 3 ثوانٍ
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "حدث خطأ أثناء إنشاء الحساب، يرجى المحاولة لاحقاً";
        }
    }

    if (!empty($errors)) {
        $msg = "❌ " . implode(" | ", $errors);
        $msgType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 إنشاء حساب | TravelPlanner</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* 🎨 نفس ألوانك */
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-pink: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-green: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f5e6a3 100%);
            --dark: #1a1a2e;
            --light: #f8f9fa;

            --traveler-primary: #f093fb;
            --traveler-secondary: #f5576c;
            --traveler-gradient: var(--gradient-pink);
            --glass-bg: rgba(26, 26, 46, 0.92);
            --glass-border: rgba(245, 87, 108, 0.4);
            --shadow-traveler: 0 15px 50px rgba(245, 87, 108, 0.35);
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
            background: var(--traveler-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 80%, rgba(245, 87, 108, 0.2) 0%, transparent 50%);
            animation: plasmaMove 25s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes plasmaMove {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(3%, 3%);
            }
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: var(--traveler-primary);
            border-radius: 50%;
            opacity: 0.7;
            animation: float 18s infinite;
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
                opacity: 0.7;
            }

            90% {
                opacity: 0.7;
            }

            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 45px 40px;
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow-traveler);
            position: relative;
            z-index: 10;
            animation: slideUp 0.85s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--traveler-gradient);
            border-radius: 30px 30px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(45px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-icon {
            width: 75px;
            height: 75px;
            background: var(--traveler-gradient);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 12px 35px rgba(245, 87, 108, 0.45);
            animation: pulse 2.5s infinite;
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

        .brand-icon i {
            font-size: 1.9rem;
            color: var(--light);
        }

        .auth-logo h1 {
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 6px;
        }

        .auth-logo p {
            color: var(--text-secondary);
            font-size: 0.97rem;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .form-label i {
            color: var(--traveler-primary);
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--traveler-secondary);
            z-index: 3;
        }

        .form-control {
            background: rgba(26, 26, 46, 0.7);
            border: 2px solid rgba(245, 87, 108, 0.4);
            border-radius: 16px;
            padding: 15px 45px 15px 20px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Tajawal', sans-serif;
            transition: 0.35s;
        }

        .form-control:focus {
            background: rgba(26, 26, 46, 0.9);
            border-color: var(--traveler-secondary);
            box-shadow: 0 0 0 4px rgba(245, 87, 108, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .password-toggle {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 5px;
            z-index: 4;
        }

        .password-toggle:hover {
            color: var(--traveler-primary);
        }

        .input-wrapper.has-toggle .form-control {
            padding-left: 48px;
        }

        /* قوة كلمة المرور */
        .password-strength {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: 0.4s;
            border-radius: 2px;
        }

        .strength-weak {
            background: #f5576c;
            width: 33%;
        }

        .strength-medium {
            background: #f59e0b;
            width: 66%;
        }

        .strength-strong {
            background: #38ef7d;
            width: 100%;
        }

        .strength-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* الزر */
        .btn-register {
            background: var(--traveler-gradient);
            color: var(--light);
            border: none;
            border-radius: 16px;
            padding: 15px 24px;
            font-size: 1.08rem;
            font-weight: 700;
            font-family: 'Tajawal', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: 0.45s;
            margin-top: 10px;
            box-shadow: 0 12px 35px rgba(245, 87, 108, 0.45);
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px rgba(245, 87, 108, 0.6);
        }

        .btn-register i {
            margin-left: 8px;
        }

        /* التنبيهات */
        .alert-custom {
            background: rgba(245, 87, 108, 0.18);
            border: 1px solid rgba(245, 87, 108, 0.5);
            border-right: 4px solid #f5576c;
            color: #ffd6e0;
            border-radius: 14px;
            padding: 13px 20px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-custom.success {
            background: rgba(17, 153, 142, 0.18);
            border-color: rgba(56, 239, 125, 0.5);
            border-right-color: #38ef7d;
            color: #d4f8e8;
        }

        /* التذييل */
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 22px;
            border-top: 1px solid rgba(245, 87, 108, 0.3);
            color: var(--text-secondary);
            font-size: 0.93rem;
        }

        .auth-footer a {
            color: var(--traveler-primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.35s;
        }

        .auth-footer a:hover {
            color: var(--traveler-secondary);
        }

        /* شبكة الحقول */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 576px) {
            .auth-card {
                padding: 38px 28px;
                margin: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="particles" id="particles"></div>

    <div class="auth-card" data-aos="zoom-in">
        <div class="auth-logo">
            <div class="brand-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>إنشاء حساب جديد</h1>
            <p>انضم إلى عائلة TravelPlanner</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert-custom <?php echo $msgType === 'success' ? 'success' : ''; ?>">
                <i class="fas fa-<?php echo $msgType === 'danger' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <!-- الاسم الكامل واسم المستخدم -->
            <div class="form-row">
                <div class="input-wrapper">
                    <label class="form-label"><i class="fas fa-user"></i> الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control" required
                        placeholder="أحمد محمد" pattern="[\u0600-\u06FFa-zA-Z\s]{2,50}">
                    <i class="fas fa-id-card input-icon"></i>
                </div>
                <div class="input-wrapper">
                    <label class="form-label"><i class="fas fa-at"></i> اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" required
                        placeholder="ahmed123" pattern="[a-zA-Z0-9_]{3,20}">
                    <i class="fas fa-user-tag input-icon"></i>
                </div>
            </div>

            <!-- البريد والهاتف -->
            <div class="form-row">
                <div class="input-wrapper">
                    <label class="form-label"><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" required
                        placeholder="email@example.com">
                    <i class="fas fa-at input-icon"></i>
                </div>
                <div class="input-wrapper">
                    <label class="form-label"><i class="fas fa-phone"></i> رقم الهاتف</label>
                    <input type="tel" name="phone" class="form-control" required
                        placeholder="+963XXXXXXXXX" pattern="[\+0-9\s\-]{8,20}">
                    <i class="fas fa-mobile-alt input-icon"></i>
                </div>
            </div>

            <!-- كلمة المرور -->
            <div class="input-wrapper has-toggle">
                <label class="form-label"><i class="fas fa-lock"></i> كلمة المرور</label>
                <input type="password" name="password" id="password" class="form-control" required
                    placeholder="••••••••••••" minlength="6" oninput="checkStrength(this.value)">
                <i class="fas fa-key input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                    <i class="fas fa-eye" id="toggleIcon1"></i>
                </button>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small class="strength-text" id="strengthText"></small>
            </div>

            <!-- تأكيد كلمة المرور -->
            <div class="input-wrapper has-toggle">
                <label class="form-label"><i class="fas fa-lock"></i> تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required
                    placeholder="••••••••••••" oninput="checkMatch()">
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'toggleIcon2')">
                    <i class="fas fa-eye" id="toggleIcon2"></i>
                </button>
                <small class="strength-text" id="matchText"></small>
            </div>

            <!-- الشروط -->
            <div style="margin: 20px 0;">
                <label class="form-check" style="color: var(--text-secondary); font-size: 0.9rem; display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="terms" required style="accent-color: var(--traveler-secondary); margin-top: 3px; width: 16px; height: 16px;">
                    <span>أوافق على <a href="#" style="color: var(--traveler-primary); text-decoration: none;">شروط الاستخدام</a> و <a href="#" style="color: var(--traveler-primary); text-decoration: none;">سياسة الخصوصية</a></span>
                </label>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> إنشاء حسابي والبدء
            </button>
        </form>

        <div class="auth-footer">
            لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // الجسيمات
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 25; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                container.appendChild(particle);
            }
        }
        createParticles();

        // إظهار/إخفاء كلمة المرور
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // قوة كلمة المرور
        function checkStrength(password) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password) && /[0-9]/.test(password)) strength++;

            bar.className = 'password-strength-bar';
            if (strength <= 1) {
                bar.classList.add('strength-weak');
                text.textContent = 'ضعيفة 🔴';
                text.style.color = '#f5576c';
            } else if (strength === 2) {
                bar.classList.add('strength-medium');
                text.textContent = 'متوسطة 🟡';
                text.style.color = '#f59e0b';
            } else {
                bar.classList.add('strength-strong');
                text.textContent = 'قوية 🟢';
                text.style.color = '#38ef7d';
            }
        }

        // تطابق كلمات المرور
        function checkMatch() {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            const text = document.getElementById('matchText');

            if (confirm.length > 0) {
                if (pass === confirm) {
                    text.textContent = '✓ متطابقتان';
                    text.style.color = '#38ef7d';
                } else {
                    text.textContent = '✗ غير متطابقتين';
                    text.style.color = '#f5576c';
                }
            } else {
                text.textContent = '';
            }
        }

        // تأثيرات الحقول
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>