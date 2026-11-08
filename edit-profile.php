<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$msg = '';
$msgType = '';

// جلب بيانات المستخدم الحالية
$query = mysqli_query($conn, "
    SELECT t.*, u.username, u.email 
    FROM travelers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = $uid
");

if (!$query) {
    die("خطأ في الاستعلام: " . mysqli_error($conn));
}

$traveler = mysqli_fetch_assoc($query);

// إذا لم يكن هناك بيانات للمسافر، نقوم بإنشائها
if (!$traveler) {
    $insert = "INSERT INTO travelers (user_id, full_name, phone, loyalty_points) VALUES ($uid, '', '', 0)";
    mysqli_query($conn, $insert);

    $query = mysqli_query($conn, "
        SELECT t.*, u.username, u.email 
        FROM travelers t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.user_id = $uid
    ");
    $traveler = mysqli_fetch_assoc($query);
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, trim($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    $errors = [];

    // التحقق من صحة البيانات الأساسية
    if (empty($full_name)) {
        $errors[] = "الاسم الكامل مطلوب";
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "رقم الجوال غير صحيح (يجب أن يحتوي على 10-15 رقم)";
    }

    // تحديث البيانات الأساسية
    if (empty($errors)) {
        $update_query = "UPDATE travelers SET full_name = '$full_name', phone = '$phone' WHERE user_id = $uid";
        if (mysqli_query($conn, $update_query)) {
            $msg = "تم تحديث البيانات الشخصية بنجاح";
            $msgType = "success";

            // إعادة جلب البيانات بعد التحديث
            $query = mysqli_query($conn, "
                SELECT t.*, u.username, u.email 
                FROM travelers t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.user_id = $uid
            ");
            $traveler = mysqli_fetch_assoc($query);
        } else {
            $msg = "حدث خطأ أثناء تحديث البيانات: " . mysqli_error($conn);
            $msgType = "danger";
        }
    }

    // معالجة تغيير كلمة المرور
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "كلمة المرور الحالية مطلوبة لتغيير كلمة المرور";
        } elseif (empty($new_password)) {
            $errors[] = "كلمة المرور الجديدة مطلوبة";
        } elseif (strlen($new_password) < 4) {
            $errors[] = "كلمة المرور الجديدة يجب أن تكون على الأقل 4 أحرف";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "كلمة المرور الجديدة وتأكيدها غير متطابقين";
        } else {
            // التحقق من كلمة المرور الحالية
            $user_check = mysqli_query($conn, "SELECT password FROM users WHERE id = $uid");
            $user_data = mysqli_fetch_assoc($user_check);

            if ($user_data && $user_data['password'] === $current_password) {
                // تحديث كلمة المرور
                $update_pass = "UPDATE users SET password = '$new_password' WHERE id = $uid";
                if (mysqli_query($conn, $update_pass)) {
                    $msg = "تم تغيير كلمة المرور بنجاح";
                    $msgType = "success";
                } else {
                    $errors[] = "حدث خطأ أثناء تغيير كلمة المرور";
                }
            } else {
                $errors[] = "كلمة المرور الحالية غير صحيحة";
            }
        }
    }

    // عرض الأخطاء إن وجدت
    if (!empty($errors) && empty($msg)) {
        $msg = implode("<br>", $errors);
        $msgType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الملف الشخصي | TravelPlanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #e6c96a;
            --gold-dark: #b89628;
            --blue-dark: #0f172a;
            --blue-darker: #020617;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --glass-bg: rgba(15, 23, 42, 0.85);
            --glass-border: rgba(212, 175, 55, 0.3);
            --success: #10b981;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--blue-darker);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(ellipse at 50% 50%, rgba(15, 23, 42, 0.95) 0%, var(--blue-darker) 100%);
        }

        .main-content {
            margin-top: 70px;
            padding: 30px 20px;
            max-width: 800px;
            margin: 70px auto 0;
        }

        .edit-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 35px;
        }

        .edit-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .edit-header i {
            font-size: 3rem;
            color: var(--gold-primary);
            margin-bottom: 15px;
        }

        .edit-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .edit-header p {
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group label i {
            margin-left: 8px;
            color: var(--gold-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold-primary);
            background: rgba(30, 41, 59, 0.8);
        }

        .form-control:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-save {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            border-radius: 12px;
            font-family: 'Tajawal', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: transparent;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-back:hover {
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--gold-primary);
            color: var(--gold-primary);
        }

        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .divider {
            margin: 30px 0;
            text-align: center;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: var(--glass-border);
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            background: var(--glass-bg);
            padding: 0 15px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .edit-card {
                padding: 25px;
            }

            .edit-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <div class="edit-card" data-aos="fade-up">
            <div class="edit-header">
                <i class="fas fa-user-edit"></i>
                <h2>تعديل الملف الشخصي</h2>
                <p>يمكنك تحديث معلوماتك الشخصية وتغيير كلمة المرور</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- المعلومات الأساسية -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i>اسم المستخدم</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($traveler['username'] ?? 'غير محدد'); ?>" disabled>
                    <small class="text-muted">لا يمكن تغيير اسم المستخدم</small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i>البريد الإلكتروني</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($traveler['email'] ?? 'غير محدد'); ?>" disabled>
                    <small class="text-muted">لا يمكن تغيير البريد الإلكتروني</small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-circle"></i>الاسم الكامل *</label>
                    <input type="text" name="full_name" class="form-control"
                        value="<?php echo htmlspecialchars($traveler['full_name'] ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i>رقم الجوال</label>
                    <input type="tel" name="phone" class="form-control"
                        value="<?php echo htmlspecialchars($traveler['phone'] ?? ''); ?>"
                        placeholder="05xxxxxxxx">
                    <small class="text-muted">أدخل رقم الجوال بدون رموز (مثال: 0501234567)</small>
                </div>

                <div class="divider">
                    <span>تغيير كلمة المرور (اختياري)</span>
                </div>

                <!-- تغيير كلمة المرور -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i>كلمة المرور الحالية</label>
                    <input type="password" name="current_password" class="form-control"
                        placeholder="أدخل كلمة المرور الحالية لتغييرها">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-key"></i>كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" class="form-control"
                        placeholder="كلمة المرور الجديدة (4 أحرف على الأقل)">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i>تأكيد كلمة المرور الجديدة</label>
                    <input type="password" name="confirm_password" class="form-control"
                        placeholder="أعد كتابة كلمة المرور الجديدة">
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>

                <a href="profile.php" class="btn-back">
                    <i class="fas fa-arrow-right me-2"></i>العودة للملف الشخصي
                </a>
            </form>
        </div>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // التحقق من تطابق كلمة المرور (جانب العميل)
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPass = document.querySelector('input[name="new_password"]').value;
            const confirmPass = document.querySelector('input[name="confirm_password"]').value;

            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('كلمة المرور الجديدة وتأكيدها غير متطابقين');
            }
        });
    </script>
</body>

</html>