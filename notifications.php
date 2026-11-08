<?php
session_start();
include '../config/db.php';

// 🔐 التحقق من صلاحية المسافر
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// جلب معرف المسافر
$stmt = $conn->prepare("SELECT id FROM travelers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$traveler = $stmt->get_result()->fetch_assoc();
$tid = $traveler['id'] ?? 0;
$stmt->close();

// 🔔 جلب الإشعارات من مصدرين
$notifications = [];

// 1️⃣ الإشعارات من الجدول المركزي
$sql1 = "SELECT * FROM notifications 
         WHERE user_id = ? AND role = 'traveler' 
         ORDER BY is_read ASC, created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql1);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result1 = $stmt->get_result();
while ($row = $result1->fetch_assoc()) {
    $row['source'] = 'system';
    $notifications[] = $row;
}
$stmt->close();

// 2️⃣ إشعارات من تحديثات الحجوزات (مصدر إضافي)
$sql2 = "SELECT b.*, 
                CASE 
                    WHEN h.id IS NOT NULL THEN h.name 
                    WHEN r.id IS NOT NULL THEN r.name 
                    WHEN a.id IS NOT NULL THEN a.name 
                END as service_name,
                b.service_type, b.status, b.created_at
         FROM bookings b
         LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
         LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
         LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
         WHERE b.traveler_id = ? 
         ORDER BY b.created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("i", $tid);
$stmt->execute();
$result2 = $stmt->get_result();
while ($row = $result2->fetch_assoc()) {
    $notifications[] = [
        'id' => 'booking_' . $row['id'],
        'title' => 'تحديث حجز: ' . $row['service_name'],
        'message' => 'حالتك الحالية: ' . ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'][$row['status']],
        'type' => 'booking',
        'is_read' => ($row['status'] === 'completed') ? 1 : 0,
        'created_at' => $row['created_at'],
        'related_id' => $row['id'],
        'related_type' => 'booking',
        'action_url' => '/trip_details.php?id=' . $row['id'],
        'icon_class' => ['pending' => 'fa-clock', 'confirmed' => 'fa-check-circle', 'cancelled' => 'fa-times-circle', 'completed' => 'fa-flag-checkered'][$row['status']] ?? 'fa-bell',
        'source' => 'booking',
        'status' => $row['status']
    ];
}
$stmt->close();

// 🔄 ترتيب الكل حسب الوقت وأخذ آخر 20
usort($notifications, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$notifications = array_slice($notifications, 0, 20);

// ✅ ✅ ✅ إصلاح: تحديث حالة "مقروء" بشكل آمن
$unread_notifications = array_filter($notifications, function ($n) {
    // تأكد أن العنصر مصفوفة ويحتوي على المفاتيح المطلوبة
    if (!is_array($n)) return false;
    if (!isset($n['is_read'], $n['source'], $n['id'])) return false;
    // نحدّث فقط إشعارات النظام غير المقروءة
    return !$n['is_read'] && $n['source'] === 'system';
});

// استخراج IDs الحقيقية (رقمية) فقط
$unread_ids = [];
foreach ($unread_notifications as $n) {
    // نتجاهل الـ IDs التي تبدأ بـ 'booking_' (لأنها من مصدر ثانوي)
    if (is_numeric($n['id'])) {
        $unread_ids[] = (int)$n['id'];
    }
}

// تنفيذ التحديث إذا وجدت إشعارات غير مقروءة
if (!empty($unread_ids)) {
    $placeholders = implode(',', array_fill(0, count($unread_ids), '?'));
    $types = str_repeat('i', count($unread_ids)); // جميعها integers

    $stmt = $conn->prepare("UPDATE notifications SET is_read=1, read_at=NOW() WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$unread_ids);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات | TravelPlanner</title>

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
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
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
            background-image:
                radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
        }

        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(ellipse at top, rgba(212, 175, 55, 0.15), transparent 70%);
            animation: bgPulse 15s ease-in-out infinite;
        }

        @keyframes bgPulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.9;
            }
        }

        .main-content {
            max-width: 850px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge-unread {
            background: linear-gradient(135deg, var(--danger), #f97316);
            color: white;
            font-size: 0.75rem;
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: 700;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
        }

        .btn-mark-read {
            background: transparent;
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-mark-read:hover {
            background: var(--gold-primary);
            color: var(--blue-darker);
        }

        .notification-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            transition: all 0.3s ease;
            border-right: 4px solid var(--gold-primary);
            position: relative;
            overflow: hidden;
        }

        .notification-card.unread {
            border-right-color: var(--warning);
            background: rgba(245, 158, 11, 0.05);
        }

        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 0%, rgba(212, 175, 55, 0.04) 100%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .notification-card:hover {
            transform: translateX(-4px);
            border-right-color: var(--gold-light);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35);
        }

        .notification-card:hover::before {
            opacity: 1;
        }

        .notif-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            border: 1px solid rgba(212, 175, 55, 0.25);
        }

        .notif-icon.booking {
            background: rgba(59, 130, 246, 0.15);
            color: var(--info);
        }

        .notif-icon.system {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .notif-icon.promotion {
            background: rgba(236, 72, 153, 0.15);
            color: #ec4899;
        }

        .notif-icon.review {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
        }

        .notif-icon.alert {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .notif-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--warning);
            display: none;
        }

        .notification-card.unread .notif-title .dot {
            display: inline-block;
        }

        .notif-message {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .notif-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .notif-meta i {
            margin-left: 4px;
            color: var(--gold-primary);
        }

        .notif-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-action {
            padding: 7px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.25s;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-darker);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
        }

        .btn-outline:hover {
            background: var(--gold-primary);
            color: var(--blue-darker);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        .type-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-booking {
            background: rgba(59, 130, 246, 0.2);
            color: var(--info);
        }

        .type-system {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold-light);
        }

        .type-promotion {
            background: rgba(236, 72, 153, 0.2);
            color: #ec4899;
        }

        .type-review {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }

        .type-alert {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .empty-state {
            text-align: center;
            padding: 70px 25px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4.5rem;
            opacity: 0.25;
            margin-bottom: 20px;
            display: block;
            color: var(--gold-primary);
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        @media (max-width: 768px) {
            .notification-card {
                flex-direction: column;
                text-align: center;
            }

            .notif-actions {
                width: 100%;
                justify-content: center;
                margin-top: 12px;
                flex-wrap: wrap;
            }

            .notif-meta {
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <!-- رأس الصفحة -->
        <div class="page-header" data-aos="fade-left">
            <div class="page-title">
                <i class="fas fa-bell"></i>
                <h2 class="fw-bold mb-0">الإشعارات</h2>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php $unread = array_filter($notifications, fn($n) => isset($n['is_read']) && !$n['is_read']); ?>
                <?php if (count($unread) > 0): ?>
                    <span class="badge-unread"><?php echo count($unread); ?> غير مقروء</span>
                <?php endif; ?>
                <a href="#" class="btn-mark-read" onclick="markAllRead(); return false;">
                    <i class="fas fa-check-double"></i> تحديد الكل كمقروء
                </a>
            </div>
        </div>

        <!-- قائمة الإشعارات -->
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $n):
                $isUnread = isset($n['is_read']) && !$n['is_read'];
                $typeClass = $n['type'] ?? 'system';
                $iconClass = $n['icon_class'] ?? 'fa-bell';
            ?>
                <div class="notification-card <?php echo $isUnread ? 'unread' : ''; ?>" data-aos="fade-up">
                    <div class="notif-icon <?php echo $typeClass; ?>">
                        <i class="fas <?php echo $iconClass; ?>"></i>
                    </div>
                    <div class="notif-content">
                        <div class="notif-title">
                            <span class="dot"></span>
                            <?php echo htmlspecialchars($n['title']); ?>
                            <?php if (isset($n['type'])): ?>
                                <span class="type-badge type-<?php echo $n['type']; ?>"><?php echo $n['type']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="notif-message">
                            <?php echo htmlspecialchars($n['message']); ?>
                        </div>
                        <div class="notif-meta">
                            <span><i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></span>
                            <?php if (isset($n['status'])): ?>
                                <span><i class="fas fa-info-circle"></i> الحالة:
                                    <?php echo ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'][$n['status']]; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($n['action_url'])): ?>
                        <div class="notif-actions">

                            <?php if (($n['related_type'] ?? '') === 'booking' && ($n['status'] ?? '') === 'pending'): ?>
                                <a href="my_bookings.php?action=cancel&id=<?php echo $n['related_id']; ?>"
                                    class="btn-action btn-danger"
                                    onclick="return confirm('هل تريد إلغاء هذا الحجز؟');">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-bell-slash"></i>
                <h3>لا توجد إشعارات حالياً 🎉</h3>
                <p>ستظهر هنا تحديثات حجوزاتك والعروض الحصرية</p>
                <a href="search.php" class="btn-action btn-primary mt-3">
                    <i class="fas fa-search me-1"></i> ابدأ رحلة جديدة
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true,
            offset: 30
        });

        // دالة تحديد الكل كمقروء (AJAX)
        function markAllRead() {
            fetch('api/mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?php echo (int)$uid; ?>,
                        role: 'traveler'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-card.unread').forEach(el => {
                            el.classList.remove('unread');
                        });
                        const badge = document.querySelector('.badge-unread');
                        if (badge) badge.remove();
                        alert('✅ تم تحديد جميع الإشعارات كمقروءة');
                    }
                })
                .catch(err => console.error('Error:', err));
        }
    </script>
</body>

</html>