<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$traveler = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT t.id as traveler_id FROM travelers t WHERE t.user_id = $uid"
));
$tid = $traveler['traveler_id'] ?? 0;

// معالجة إلغاء الحجز
if (isset($_GET['cancel']) && $_GET['cancel'] > 0) {
    $bid = intval($_GET['cancel']);
    mysqli_query($conn, "UPDATE bookings SET status='cancelled' WHERE id=$bid AND traveler_id=$tid");
    header("Location: my_bookings.php?msg=cancelled");
    exit();
}

// جلب الحجوزات
$filter = $_GET['filter'] ?? 'all';
$status_filter = $filter === 'all' ? '' : "AND b.status = '$filter'";

$bookings = mysqli_query(
    $conn,
    "SELECT b.*, 
            CASE WHEN h.id IS NOT NULL THEN h.name WHEN r.id IS NOT NULL THEN r.name WHEN a.id IS NOT NULL THEN a.name END as service_name,
            b.service_type, h.city, r.city as r_city, a.city as a_city
     FROM bookings b
     LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
     LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
     LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
     WHERE b.traveler_id = $tid $status_filter
     ORDER BY b.booking_date DESC"
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>حجوزاتي | TravelPlanner</title>
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
            max-width: 1200px;
            margin: 70px auto 0;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border-radius: 50px;
            border: 2px solid var(--glass-border);
            background: var(--glass-bg);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Tajawal', sans-serif;
            font-weight: 500;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border-color: transparent;
        }

        .booking-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
        }

        .booking-card:hover {
            transform: translateX(-5px);
            border-color: var(--gold-primary);
        }

        .booking-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .booking-icon.hotel {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .booking-icon.restaurant {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .booking-icon.activity {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .booking-details h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .booking-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .booking-price {
            font-weight: 700;
            color: #22c55e;
            font-size: 1.1rem;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .status-confirmed {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .status-completed {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .booking-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-details {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .btn-details:hover {
            background: var(--gold-primary);
            color: var(--blue-dark);
        }

        .btn-cancel {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .btn-cancel:hover {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
            display: block;
        }

        @media (max-width: 768px) {
            .booking-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .booking-actions {
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right">
            <i class="fas fa-ticket-alt me-2" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            حجوزاتي
        </h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert" style="background:rgba(34,197,94,0.15);border-right:4px solid #22c55e;color:#86efac;padding:15px;border-radius:12px;margin-bottom:20px;">
                ✅ تم إلغاء الحجز بنجاح
            </div>
        <?php endif; ?>

        <div class="filters" data-aos="fade-up">
            <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="location.href='?filter=all'">الكل</button>
            <button class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>" onclick="location.href='?filter=pending'">🟡 معلق</button>
            <button class="filter-btn <?php echo $filter === 'confirmed' ? 'active' : ''; ?>" onclick="location.href='?filter=confirmed'">🟢 مؤكد</button>
            <button class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>" onclick="location.href='?filter=completed'">🔵 مكتمل</button>
            <button class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" onclick="location.href='?filter=cancelled'">🔴 ملغي</button>
        </div>

        <?php if (mysqli_num_rows($bookings) > 0): ?>
            <?php while ($b = mysqli_fetch_assoc($bookings)):
                $city = $b['city'] ?? $b['r_city'] ?? $b['a_city'] ?? '';
                $icon = ['hotel' => 'fa-hotel', 'restaurant' => 'fa-utensils', 'activity' => 'fa-umbrella-beach'][$b['service_type']] ?? 'fa-store';
            ?>
                <div class="booking-card" data-aos="fade-up">
                    <div class="booking-icon <?php echo $b['service_type']; ?>">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="booking-details">
                        <h3><?php echo htmlspecialchars($b['service_name']); ?></h3>
                        <p class="booking-meta">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($city); ?>
                            • <i class="fas fa-calendar me-1"></i> <?php echo $b['booking_date']; ?>
                        </p>
                        <span class="status-badge status-<?php echo $b['status']; ?>">
                            <?php echo ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'][$b['status']]; ?>
                        </span>
                        <div class="booking-price"><?php echo number_format($b['total_price'], 2); ?> $</div>
                    </div>
                    <div class="booking-actions">
                        <a href="service_details.php?id=<?php echo $b['service_id']; ?>&type=<?php echo $b['service_type']; ?>" class="btn-action btn-details">
                            <i class="fas fa-eye"></i> التفاصيل
                        </a>
                        <?php if ($b['status'] === 'pending' || $b['status'] === 'confirmed'): ?>
                            <a href="?cancel=<?php echo $b['id']; ?>" class="btn-action btn-cancel" onclick="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-ticket-alt"></i>
                <h3>لا توجد حجوزات</h3>
                <p>ابدأ بتخطيط رحلتك الأولى!</p>
                <a href="search.php" class="btn-action btn-details" style="margin-top:20px;">
                    <i class="fas fa-search"></i> استكشف الخدمات
                </a>
            </div>
        <?php endif; ?>
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