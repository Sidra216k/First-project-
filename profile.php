<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// تصحيح: التحقق من وجود بيانات المسافر
$traveler_query = mysqli_query($conn, "
    SELECT t.*, u.username, u.email, u.status, u.created_at 
    FROM travelers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = $uid
");

if (!$traveler_query) {
    die("خطأ في الاستعلام: " . mysqli_error($conn));
}

$traveler = mysqli_fetch_assoc($traveler_query);

// إذا لم يكن هناك بيانات للمسافر، نقوم بإنشائها
if (!$traveler) {
    // إنشاء سجل جديد في travelers
    $insert_traveler = "INSERT INTO travelers (user_id, full_name, phone, loyalty_points) VALUES ($uid, '', '', 0)";
    if (!mysqli_query($conn, $insert_traveler)) {
        die("خطأ في إنشاء بيانات المسافر: " . mysqli_error($conn));
    }

    // إعادة جلب البيانات
    $traveler_query = mysqli_query($conn, "
        SELECT t.*, u.username, u.email, u.status, u.created_at 
        FROM travelers t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.user_id = $uid
    ");
    $traveler = mysqli_fetch_assoc($traveler_query);
}

// إحصائيات - التحقق من وجود id للمسافر
$traveler_id = isset($traveler['id']) ? $traveler['id'] : 0;
$stats_query = mysqli_query($conn, "
    SELECT COUNT(*) as bookings, COALESCE(SUM(total_price), 0) as spent 
    FROM bookings 
    WHERE traveler_id = $traveler_id
");

if (!$stats_query) {
    $stats = ['bookings' => 0, 'spent' => 0];
} else {
    $stats = mysqli_fetch_assoc($stats_query);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ملفي الشخصي | TravelPlanner</title>
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
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --warning: #f59e0b;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--blue-darker);
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
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
            max-width: 1000px;
            margin: 70px auto 0;
        }

        .profile-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--blue-dark);
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: var(--text-secondary);
            margin-bottom: 15px;
        }

        .loyalty-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(245, 158, 11, 0.15);
            padding: 8px 16px;
            border-radius: 50px;
            color: var(--warning);
            font-weight: 600;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--gold-primary);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--glass-border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
        }

        .info-value {
            font-weight: 600;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 12px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold-primary);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .btn-edit {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            border-radius: 12px;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            color: var(--blue-dark);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <div class="profile-header" data-aos="fade-down">
            <div class="profile-avatar">
                <?php
                // تصحيح: التحقق من وجود full_name
                $full_name = isset($traveler['full_name']) ? $traveler['full_name'] : '';
                echo !empty($full_name) ? mb_substr($full_name, 0, 1) : 'م';
                ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($traveler['full_name'] ?? 'غير محدد'); ?></h1>
                <p><?php echo htmlspecialchars($traveler['email'] ?? 'غير محدد'); ?></p>
                <div class="loyalty-badge">
                    <i class="fas fa-coins"></i>
                    <?php echo number_format($traveler['loyalty_points'] ?? 0); ?> نقطة ولاء
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <div class="profile-card" data-aos="fade-up">
                <h3 class="card-title"><i class="fas fa-user"></i> المعلومات الشخصية</h3>
                <div class="info-row">
                    <span class="info-label">اسم المستخدم</span>
                    <span class="info-value"><?php echo htmlspecialchars($traveler['username'] ?? 'غير محدد'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني</span>
                    <span class="info-value"><?php echo htmlspecialchars($traveler['email'] ?? 'غير محدد'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الهاتف</span>
                    <span class="info-value"><?php echo htmlspecialchars($traveler['phone'] ?? 'غير محدد'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ التسجيل</span>
                    <span class="info-value">
                        <?php
                        if (isset($traveler['created_at']) && !empty($traveler['created_at'])) {
                            echo date('Y-m-d', strtotime($traveler['created_at']));
                        } else {
                            echo 'غير محدد';
                        }
                        ?>
                    </span>
                </div>
                <a href="edit-profile.php" class="btn-edit" style="margin-top:15px;">
                    <i class="fas fa-edit"></i> تعديل البيانات
                </a>
            </div>

            <div class="profile-card" data-aos="fade-up" data-aos-delay="100">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> إحصائياتي</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['bookings'] ?? 0; ?></div>
                        <div class="stat-label">حجوزات</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats['spent'] ?? 0, 0); ?> $</div>
                        <div class="stat-label">إجمالي الإنفاق</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($traveler['loyalty_points'] ?? 0, 0); ?></div>
                        <div class="stat-label">نقاط الولاء</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            // تصحيح: التحقق من وجود status
                            $status = isset($traveler['status']) ? $traveler['status'] : 'active';
                            echo $status === 'active' ? 'نشط' : 'غير نشط';
                            ?>
                        </div>
                        <div class="stat-label">الحالة</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
    </script>
</body>

</html>