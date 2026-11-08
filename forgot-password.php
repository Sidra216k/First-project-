<?php
session_start();
include '../config/db.php';
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['step']) && $_POST['step'] === 'verify') {
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        if (empty($email) || empty($phone)) {
            $message = "⚠️ يرجى إدخال البريد الإلكتروني ورقم الهاتف";
            $messageType = 'error';
        } else {
            // ✅ التحقق من users مباشرة مع role = provider
            $stmt = $conn->prepare("SELECT id, username, status FROM users WHERE email = ? AND phone = ? AND role = 'provider'");
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if ($user['status'] === 'blocked') {
                    $message = "🔒 حساب المزود موقوف، يرجى التواصل مع الإدارة";
                    $messageType = 'error';
                } else {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_token'] = bin2hex(random_bytes(32));
                    $_SESSION['reset_time'] = time();
                    $_SESSION['reset_role'] = 'provider';
                    $message = "✅ تم التحقق بنجاح! يمكنك الآن تعيين كلمة مرور جديدة";
                    $messageType = 'success';
                }
            } else {
                $message = "❌ البيانات غير مطابقة، يرجى التحقق من البريد ورقم الهاتف";
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === 'update') {
        if (!isset($_SESSION['reset_token']) || (time() - $_SESSION['reset_time']) > 900) {
            $message = "⏰ انتهت صلاحية الجلسة، يرجى إعادة المحاولة";
            $messageType = 'error';
        } else {
            $newPassword = trim($_POST['new_password']);
            $confirmPassword = trim($_POST['confirm_password']);
            $token = $_POST['token'] ?? '';
            if ($token !== $_SESSION['reset_token']) {
                $message = "🔐 طلب غير صالح";
                $messageType = 'error';
            } elseif (empty($newPassword) || empty($confirmPassword)) {
                $message = "⚠️ أدخل كلمة المرور وتأكيدها";
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = "❌ كلمات المرور غير متطابقة";
                $messageType = 'error';
            } elseif (strlen($newPassword) < 6) {
                $message = "⚠️ الحد الأدنى 6 أحرف";
                $messageType = 'error';
            } else {
                $userId = $_SESSION['reset_user_id'];
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newPassword, $userId);
                if ($updateStmt->execute()) {
                    unset($_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_token'], $_SESSION['reset_time'], $_SESSION['reset_role']);
                    $message = "🎉 تم التغيير بنجاح! توجيه خلال 3 ثوانٍ...";
                    $messageType = 'success';
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
                } else {
                    $message = "❌ خطأ في التحديث";
                    $messageType = 'error';
                }
                $updateStmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 استعادة كلمة المرور | مزود الخدمة</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        :root {
            --gradient-green: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --provider-primary: #11998e;
            --provider-secondary: #38ef7d;
            --glass-bg: rgba(26, 26, 46, 0.92);
            --glass-border: rgba(17, 153, 142, 0.5);
            --shadow-provider: 0 15px 50px rgba(17, 153, 142, 0.4);
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
            background: var(--gradient-green);
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
            background: radial-gradient(circle at 20% 80%, rgba(56, 239, 125, 0.2) 0%, transparent 50%);
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

        .reset-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 48px 42px;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-provider);
            position: relative;
            z-index: 10;
            animation: slideUp 0.85s ease-out;
        }

        .reset-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-green);
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

        .brand-header {
            text-align: center;
            margin-bottom: 38px;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-green);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 12px 35px rgba(17, 153, 142, 0.5);
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
            font-size: 2rem;
            color: var(--light);
        }

        .brand-header h2 {
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .brand-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 32px;
        }

        .step-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(17, 153, 142, 0.4);
            transition: 0.35s;
        }

        .step-dot.active {
            background: var(--provider-primary);
            box-shadow: 0 0 0 5px rgba(17, 153, 142, 0.3);
        }

        .step-dot.completed {
            background: #38ef7d;
            box-shadow: 0 0 0 5px rgba(56, 239, 125, 0.3);
        }

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
            margin-bottom: 24px;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--provider-secondary);
            z-index: 3;
        }

        .form-control {
            background: rgba(26, 26, 46, 0.7);
            border: 2px solid rgba(17, 153, 142, 0.5);
            border-radius: 18px;
            padding: 16px 48px 16px 22px;
            color: var(--text-primary);
            font-size: 1.02rem;
            font-family: 'Tajawal', sans-serif;
            transition: 0.4s;
        }

        .form-control:focus {
            background: rgba(26, 26, 46, 0.9);
            border-color: var(--provider-secondary);
            box-shadow: 0 0 0 5px rgba(17, 153, 142, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .btn-reset {
            background: var(--gradient-green);
            color: var(--light);
            border: none;
            border-radius: 18px;
            padding: 16px 26px;
            font-size: 1.08rem;
            font-weight: 700;
            font-family: 'Tajawal', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: 0.45s;
            margin-top: 12px;
            box-shadow: 0 12px 35px rgba(17, 153, 142, 0.5);
        }

        .btn-reset:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 60px rgba(17, 153, 142, 0.65);
        }

        .btn-reset i {
            margin-left: 9px;
        }

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
        }

        .alert-custom.success {
            background: rgba(17, 153, 142, 0.18);
            border-color: rgba(56, 239, 125, 0.5);
            border-right-color: #38ef7d;
            color: #d4f8e8;
        }

        .back-link {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(17, 153, 142, 0.4);
        }

        .back-link a {
            color: var(--provider-primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: 0.35s;
        }

        .back-link a:hover {
            color: var(--provider-secondary);
            transform: translateX(-4px);
        }

        .password-strength {
            height: 5px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: 0.4s;
            border-radius: 3px;
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

        .step-section {
            display: none;
        }

        .step-section.active {
            display: block;
            animation: fadeIn 0.45s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(25px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 576px) {
            .reset-card {
                padding: 38px 28px;
                margin: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="reset-card">
        <div class="brand-header">
            <div class="brand-icon"><i class="fas fa-key"></i></div>
            <h2>استعادة كلمة المرور</h2>
            <p>Provider Account Recovery</p>
        </div>
        <div class="step-indicator">
            <div class="step-dot <?php echo (!isset($_SESSION['reset_token'])) ? 'active' : 'completed'; ?>"></div>
            <div class="step-dot <?php echo (isset($_SESSION['reset_token'])) ? 'active' : ''; ?>"></div>
        </div>
        <?php if ($message): ?><div class="alert-custom <?php echo $messageType; ?>"><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i><span><?php echo htmlspecialchars($message); ?></span></div><?php endif; ?>
        <div class="step-section <?php echo (!isset($_SESSION['reset_token'])) ? 'active' : ''; ?>" id="step1">
            <form method="POST"><input type="hidden" name="step" value="verify">
                <div class="input-wrapper"><label class="form-label"><i class="fas fa-envelope"></i> البريد الإلكتروني للشركة</label><input type="email" name="email" class="form-control" required placeholder="business@travelplanner.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"><i class="fas fa-at input-icon"></i></div>
                <div class="input-wrapper"><label class="form-label"><i class="fas fa-phone"></i> رقم هاتف الشركة المسجل</label><input type="tel" name="phone" class="form-control" required placeholder="+963XXXXXXXXX" pattern="[\+0-9\s\-]{8,20}" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"><i class="fas fa-mobile-alt input-icon"></i></div>
                <button type="submit" class="btn-reset"><i class="fas fa-search"></i> التحقق من بيانات الشركة</button>
            </form>
        </div>
        <div class="step-section <?php echo (isset($_SESSION['reset_token'])) ? 'active' : ''; ?>" id="step2">
            <?php if (isset($_SESSION['reset_token'])): ?>
                <form method="POST"><input type="hidden" name="step" value="update"><input type="hidden" name="token" value="<?php echo $_SESSION['reset_token']; ?>">
                    <div class="alert-custom success mb-4"><i class="fas fa-building-check"></i><span>مرحباً <?php echo htmlspecialchars($_SESSION['reset_email'] ?? 'شريك'); ?>، عيّن كلمة مرور جديدة</span></div>
                    <div class="input-wrapper"><label class="form-label"><i class="fas fa-lock"></i> كلمة المرور الجديدة</label><input type="password" name="new_password" id="newPassword" class="form-control" required placeholder="••••••••••••" minlength="6" oninput="checkStrength(this.value)"><i class="fas fa-key input-icon"></i>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div><small id="strengthText" style="font-size: 0.82rem; color: var(--text-secondary);"></small>
                    </div>
                    <div class="input-wrapper"><label class="form-label"><i class="fas fa-lock"></i> تأكيد كلمة المرور</label><input type="password" name="confirm_password" class="form-control" required placeholder="••••••••••••" oninput="checkMatch()"><i class="fas fa-lock input-icon"></i><small id="matchText" style="font-size: 0.82rem; color: var(--text-secondary);"></small></div>
                    <button type="submit" class="btn-reset"><i class="fas fa-save"></i> حفظ كلمة المرور الجديدة</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="back-link"><a href="login.php"><i class="fas fa-arrow-right"></i> العودة لتسجيل الدخول</a></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkStrength(password) {
            const bar = document.getElementById('strengthBar'),
                text = document.getElementById('strengthText');
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

        function checkMatch() {
            const newPass = document.getElementById('newPassword').value,
                confirmPass = document.querySelector('input[name="confirm_password"]').value,
                matchText = document.getElementById('matchText');
            if (confirmPass.length > 0) {
                if (newPass === confirmPass) {
                    matchText.textContent = '✓ متطابقتان';
                    matchText.style.color = '#38ef7d';
                } else {
                    matchText.textContent = '✗ غير متطابقتين';
                    matchText.style.color = '#f5576c';
                }
            } else {
                matchText.textContent = '';
            }
        }
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