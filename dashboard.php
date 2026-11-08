<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$provider = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT p.*, u.username, u.email, u.status 
     FROM providers p 
     JOIN users u ON p.user_id = u.id 
     WHERE p.user_id = $uid"
));
$pid = $provider['id'] ?? 0;
$pname = $provider['business_name'] ?? 'مزود الخدمة';

// === إحصائيات شاملة ===
$stats = [];

// عدد الخدمات النشطة
$services_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) as count FROM (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION ALL
        SELECT id FROM restaurants WHERE provider_id=$pid
        UNION ALL
        SELECT id FROM activities WHERE provider_id=$pid
    ) as all_services"
);
$stats['total_services'] = mysqli_fetch_assoc($services_query)['count'] ?? 0;

// الحجوزات والإيرادات
$bookings_stats = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN total_price ELSE 0 END), 0) as total_revenue
     FROM bookings 
     WHERE service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
     )"
));
$stats['bookings'] = $bookings_stats;

// التقييمات
$reviews_stats = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating 
     FROM reviews 
     WHERE service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
     )"
));
$stats['reviews'] = $reviews_stats;

// آخر 5 حجوزات
$recent_bookings = mysqli_query(
    $conn,
    "SELECT b.*, t.full_name as traveler_name, t.phone,
            CASE 
                WHEN h.id IS NOT NULL THEN h.name
                WHEN r.id IS NOT NULL THEN r.name
                WHEN a.id IS NOT NULL THEN a.name
            END as service_name
     FROM bookings b
     JOIN travelers t ON b.traveler_id = t.id
     LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
     LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
     LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
     WHERE b.service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
     )
     ORDER BY b.created_at DESC LIMIT 5"
);

// تنبؤات AI
$ai_predictions = mysqli_query(
    $conn,
    "SELECT * FROM ai_predictions ORDER BY generated_at DESC LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | <?php echo htmlspecialchars($pname); ?></title>
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
            margin-right: 270px;
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
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-left">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-chart-line me-2" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                    لوحة التحكم
                </h2>
                <p class="text-muted mb-0">نظرة عامة على أداء خدماتك</p>
            </div>
            <div class="text-start">
                <span class="badge bg-success px-3 py-2 rounded-pill">
                    <i class="fas fa-check-circle me-1"></i>
                    <?php echo $provider['is_verified'] ? 'معتمد' : 'قيد المراجعة'; ?>
                </span>
            </div>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="row g-4 mb-4" data-aos="fade-up">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon primary">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['total_services']; ?></div>
                            <div class="stat-label">خدمة نشطة</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon warning">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['bookings']['pending'] ?? 0; ?></div>
                            <div class="stat-label">حجوزات معلقة</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['bookings']['total_revenue'] ?? 0, 0); ?> $</div>
                            <div class="stat-label">إجمالي الإيرادات</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon info">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['reviews']['avg_rating'] ?? 0, 1); ?></div>
                            <div class="stat-label">متوسط التقييمات</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- آخر الحجوزات -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-clock me-2 text-primary"></i>آخر الحجوزات
                        </h5>
                        <a href="bookings.php" class="btn btn-sm btn-outline-primary rounded-pill">
                            عرض الكل <i class="fas fa-arrow-left me-1"></i>
                        </a>
                    </div>

                    <?php if (mysqli_num_rows($recent_bookings) > 0): ?>
                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                            <div class="booking-item status-<?php echo $booking['status']; ?>">
                                <div class="stat-icon <?php
                                                        echo $booking['service_type'] == 'hotel' ? 'primary' : ($booking['service_type'] == 'restaurant' ? 'warning' : 'info');
                                                        ?>" style="width:50px;height:50px;font-size:1.2rem;">
                                    <i class="fas fa-<?php
                                                        echo $booking['service_type'] == 'hotel' ? 'hotel' : ($booking['service_type'] == 'restaurant' ? 'utensils' : 'umbrella-beach');
                                                        ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['service_name'] ?? 'خدمة'); ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($booking['traveler_name']); ?>
                                        • <i class="fas fa-calendar me-1"></i><?php echo $booking['booking_date']; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success"><?php echo number_format($booking['total_price'], 2); ?> $</div>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php
                                        $status_ar = ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'];
                                        echo $status_ar[$booking['status']] ?? $booking['status'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                            <p>لا توجد حجوزات بعد</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- تنبؤات AI -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="ai-card mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-robot me-2"></i>تنبؤات الذكاء الاصطناعي
                    </h5>
                    <?php if (mysqli_num_rows($ai_predictions) > 0): ?>
                        <?php while ($pred = mysqli_fetch_assoc($ai_predictions)): ?>
                            <div class="mb-3 pb-3 border-bottom border-white border-opacity-25">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($pred['destination']); ?></span>
                                    <span class="badge bg-white text-primary">
                                        <?php
                                        $price_ar = ['low' => 'منخفض', 'medium' => 'متوسط', 'high' => 'مرتفع'];
                                        echo $price_ar[$pred['predicted_price_level']] ?? $pred['predicted_price_level'];
                                        ?>
                                    </span>
                                </div>
                                <small class="opacity-75">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    أفضل وقت: <?php echo htmlspecialchars($pred['best_time_to_book']); ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="opacity-75">لا توجد تنبؤات متاحة حالياً</p>
                    <?php endif; ?>
                    <a href="analytics.php" class="btn btn-light rounded-pill w-100 mt-2">
                        <i class="fas fa-chart-bar me-2"></i>التحليلات الكاملة
                    </a>
                </div>

                <!-- ملخص التقييمات -->
                <div class="chart-container">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-comments me-2 text-warning"></i>التقييمات
                    </h5>
                    <div class="text-center">
                        <div class="display-4 fw-bold text-warning">
                            <?php echo number_format($stats['reviews']['avg_rating'] ?? 0, 1); ?>
                        </div>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= ($stats['reviews']['avg_rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted"><?php echo $stats['reviews']['total_reviews'] ?? 0; ?> تقييم</small>
                    </div>
                    <a href="reviews.php" class="btn btn-outline-primary rounded-pill w-100 mt-3">
                        <i class="fas fa-eye me-2"></i>عرض جميع التقييمات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 600
        });
    </script>
</body>

</html>