<?php
session_start();
include '../config/db.php';

// 🔐 التحقق من الصلاحية
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// جلب معرف مقدم الخدمة باستخدام Prepared Statement
$stmt = $conn->prepare("SELECT id FROM providers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
$pid = $provider['id'] ?? 0;
$stmt->close();

// 🔔 جلب الحجوزات الجديدة (إشعارات)
$sql = "SELECT b.*, t.full_name as traveler_name,
               CASE 
                   WHEN h.id IS NOT NULL THEN h.name 
                   WHEN r.id IS NOT NULL THEN r.name 
                   WHEN a.id IS NOT NULL THEN a.name 
               END as service_name,
               b.service_type
        FROM bookings b
        JOIN travelers t ON b.traveler_id = t.id
        LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
        LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
        LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
        WHERE b.service_id IN (
            SELECT id FROM hotels WHERE provider_id=?
            UNION SELECT id FROM restaurants WHERE provider_id=?
            UNION SELECT id FROM activities WHERE provider_id=?
        ) AND b.status = 'pending'
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $pid, $pid, $pid);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات | TravelPlanner</title>

    <!-- الخطوط والمكتبات -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Tajawal', 'Segoe UI', sans-serif;
        }

        .main-content {
            padding: 30px;
            transition: margin-right 0.3s;
        }

        /* بطاقات الإحصائيات */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .stat-icon.primary {
            background: rgba(17, 153, 142, 0.1);
            color: #11998e;
        }

        .stat-icon.warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .stat-icon.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .stat-icon.danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .stat-icon.info {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
        }

        .stat-label {
            color: #888;
            font-size: 0.9rem;
        }

        /* بطاقات الحجوزات */
        .booking-item {
            background: white;
            border-radius: 15px;
            padding: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
            border-right: 4px solid transparent;
        }

        .booking-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .booking-item.status-pending {
            border-right-color: #ffc107;
        }

        .booking-item.status-confirmed {
            border-right-color: #28a745;
        }

        .booking-item.status-cancelled {
            border-right-color: #dc3545;
        }

        .status-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        /* الرسوم البيانية */
        .chart-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        /* تنبؤات AI */
        .ai-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .ai-card .stat-value {
            color: white;
        }

        .ai-card .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 992px) {
            .main-content {
                margin-right: 0;
                padding: 20px;
            }
        }
    </style>
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
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--blue-darker);
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(15, 23, 42, 0.9) 0%, transparent 50%);
        }

        .main-content {
            margin-top: 70px;
            padding: 30px 20px;
            max-width: 900px;
            margin: 70px auto 0;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
        }

        .page-header i {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
        }

        /* بطاقة الإشعار */
        .notification-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border-right: 4px solid var(--gold-primary);
            position: relative;
            overflow: hidden;
        }

        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 0%, rgba(212, 175, 55, 0.03) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .notification-card:hover {
            transform: translateX(-5px);
            border-right-color: var(--gold-light);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .notification-card:hover::before {
            opacity: 1;
        }

        .notification-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .notification-title .service-name {
            color: var(--gold-light);
        }

        .badge-new {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .notification-meta {
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .notification-meta i {
            margin-left: 5px;
            color: var(--gold-primary);
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-darker);
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
            color: var(--blue-darker);
        }

        .btn-outline-gold {
            background: transparent;
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-outline-gold:hover {
            background: var(--gold-primary);
            color: var(--blue-darker);
        }

        /* الحالة الفارغة */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
            background: var(--glass-bg);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
        }

        .empty-state i {
            font-size: 5rem;
            opacity: 0.2;
            margin-bottom: 25px;
            display: block;
            color: var(--gold-primary);
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        /* شارة الحالة */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .status-confirmed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .status-completed {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .notification-card {
                flex-direction: column;
                text-align: center;
            }

            .notification-actions {
                width: 100%;
                justify-content: center;
                margin-top: 15px;
            }

            .notification-meta {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <!-- رأس الصفحة -->
        <div class="page-header" data-aos="fade-left">
            <i class="fas fa-bell"></i>
            <h2 class="fw-bold mb-0">الإشعارات</h2>
            <span class="badge-new" id="newCount"><?php echo mysqli_num_rows($notifications); ?> جديد</span>
        </div>

        <!-- محتوى الإشعارات -->
        <?php if (mysqli_num_rows($notifications) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
                <div class="notification-card" data-aos="fade-up">
                    <div class="notification-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">
                            <span class="service-name"><?php echo htmlspecialchars($n['service_name']); ?></span>
                            <span class="status-badge status-<?php echo $n['status']; ?>">
                                <?php echo ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'][$n['status']]; ?>
                            </span>
                        </div>
                        <div class="notification-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($n['traveler_name']); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('Y-m-d', strtotime($n['booking_date'])); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo number_format($n['total_price'], 2); ?> $</span>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <a href="bookings.php?action=view&id=<?php echo $n['id']; ?>" class="btn-gold">
                            <i class="fas fa-eye"></i> عرض
                        </a>
                        <a href="bookings.php?action=confirm&id=<?php echo $n['id']; ?>" class="btn-outline-gold">
                            <i class="fas fa-check"></i> قبول
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-bell-slash"></i>
                <h3>لا توجد إشعارات جديدة 🎉</h3>
                <p>جميع حجوزاتك تمت معالجتها، استمر في التميز!</p>
                <a href="dashboard.php" class="btn-gold mt-3">
                    <i class="fas fa-arrow-right me-2"></i> العودة للوحة التحكم
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true,
            offset: 50
        });

        // تأثير وميض للعداد عند وجود إشعارات جديدة
        <?php if (mysqli_num_rows($notifications) > 0): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const badge = document.getElementById('newCount');
                if (badge) {
                    badge.style.animation = 'pulse 1.5s infinite';
                    setTimeout(() => {
                        badge.style.animation = 'none';
                    }, 5000);
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>