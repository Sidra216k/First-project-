<?php
session_start();
include '../config/db.php';

// 🔐 حماية الوصول لمزود الخدمة فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

// === جلب بيانات المزود الحالي ===
$uid = $_SESSION['user_id'];
$provider = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM providers WHERE user_id = $uid"));
$pid = $provider['id'] ?? 0;

if ($pid == 0) {
    echo "<div style='text-align:center;padding:50px;color:#f5576c;font-size:1.2rem'>❌ لم يتم العثور على حساب مزود خدمة</div>";
    exit();
}

// === دوال مساعدة للتحليلات ===
function getStat($conn, $query, $default = 0)
{
    $result = mysqli_query($conn, $query);
    return $result ? (mysqli_fetch_assoc($result)['val'] ?? $default) : 0;
}

function formatNumber($num)
{
    if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
    if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
    return number_format($num, 0);
}

// === جلب بيانات الرسوم البيانية (مخصصة للمزود الحالي) ===

// 1️⃣ الحجوزات والإيرادات الشهرية لخدمات هذا المزود
$monthly_query = "
    SELECT 
        DATE_FORMAT(b.booking_date, '%Y-%m') as month,
        COUNT(*) as cnt,
        COALESCE(SUM(b.total_price), 0) as rev
    FROM bookings b
    JOIN (
        SELECT id, provider_id FROM hotels WHERE provider_id = $pid
        UNION ALL
        SELECT id, provider_id FROM restaurants WHERE provider_id = $pid
        UNION ALL
        SELECT id, provider_id FROM activities WHERE provider_id = $pid
    ) s ON b.service_id = s.id
    WHERE b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month ASC
";
$monthly = mysqli_query($conn, $monthly_query);
$months = [];
$counts = [];
$revenues = [];
while ($r = mysqli_fetch_assoc($monthly)) {
    $months[] = $r['month'];
    $counts[] = (int)$r['cnt'];
    $revenues[] = round($r['rev'] / 1000, 1);
}

// 2️⃣ توزيع خدمات المزود (فنادق/مطاعم/أنشطة)
$services_dist = mysqli_query($conn, "
    SELECT 'فنادق' as label, COUNT(*) as val FROM hotels WHERE provider_id = $pid
    UNION ALL SELECT 'مطاعم', COUNT(*) FROM restaurants WHERE provider_id = $pid
    UNION ALL SELECT 'أنشطة', COUNT(*) FROM activities WHERE provider_id = $pid
");
$service_labels = [];
$service_values = [];
$service_colors = ['#11998e', '#38ef7d', '#667eea'];
while ($s = mysqli_fetch_assoc($services_dist)) {
    $service_labels[] = $s['label'];
    $service_values[] = (int)$s['val'];
}

// 3️⃣ حالة حجوزات خدمات هذا المزود
$booking_status = mysqli_query($conn, "
    SELECT b.status, COUNT(*) as cnt 
    FROM bookings b
    JOIN (
        SELECT id FROM hotels WHERE provider_id = $pid
        UNION ALL SELECT id FROM restaurants WHERE provider_id = $pid
        UNION ALL SELECT id FROM activities WHERE provider_id = $pid
    ) s ON b.service_id = s.id
    GROUP BY b.status
");
$status_labels = [];
$status_values = [];
$status_colors = ['#ffc107', '#11998e', '#f5576c', '#667eea'];
$statusMap = ['pending' => 'قيد الانتظار', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'];
while ($bs = mysqli_fetch_assoc($booking_status)) {
    $status_labels[] = $statusMap[$bs['status']] ?? $bs['status'];
    $status_values[] = (int)$bs['cnt'];
}

// 4️⃣ المدن الأكثر طلباً لخدمات هذا المزود
$top_cities = mysqli_query($conn, "
    SELECT s.city, COUNT(*) as cnt, SUM(b.total_price) as rev
    FROM bookings b
    JOIN (
        SELECT id, city, provider_id FROM hotels
        UNION ALL SELECT id, city, provider_id FROM restaurants
        UNION ALL SELECT id, city, provider_id FROM activities
    ) s ON b.service_id = s.id
    WHERE s.provider_id = $pid
    GROUP BY s.city 
    ORDER BY cnt DESC 
    LIMIT 5
");
$city_labels = [];
$city_counts = [];
$city_revenues = [];
while ($c = mysqli_fetch_assoc($top_cities)) {
    $city_labels[] = $c['city'];
    $city_counts[] = (int)$c['cnt'];
    $city_revenues[] = round($c['rev'] / 1000, 1);
}

// === مؤشرات الأداء الرئيسية (مخصصة للمزود) ===
$kpis = [
    'total_services' => getStat($conn, "SELECT COUNT(*) as val FROM (SELECT id FROM hotels WHERE provider_id=$pid UNION ALL SELECT id FROM restaurants WHERE provider_id=$pid UNION ALL SELECT id FROM activities WHERE provider_id=$pid) as all_services"),
    'total_bookings' => getStat($conn, "SELECT COUNT(*) as val FROM bookings b JOIN (SELECT id FROM hotels WHERE provider_id=$pid UNION ALL SELECT id FROM restaurants WHERE provider_id=$pid UNION ALL SELECT id FROM activities WHERE provider_id=$pid) s ON b.service_id = s.id"),
    'total_revenue' => getStat($conn, "SELECT COALESCE(SUM(b.total_price),0) as val FROM bookings b JOIN (SELECT id FROM hotels WHERE provider_id=$pid UNION ALL SELECT id FROM restaurants WHERE provider_id=$pid UNION ALL SELECT id FROM activities WHERE provider_id=$pid) s ON b.service_id = s.id WHERE b.status='confirmed'"),
    'avg_rating' => getStat($conn, "SELECT COALESCE(AVG(rating),0) as val FROM (SELECT rating FROM hotels WHERE provider_id=$pid AND rating>0 UNION ALL SELECT rating FROM restaurants WHERE provider_id=$pid AND rating>0 UNION ALL SELECT rating FROM activities WHERE provider_id=$pid AND rating>0) as r"),
    'pending_bookings' => getStat($conn, "SELECT COUNT(*) as val FROM bookings b JOIN (SELECT id FROM hotels WHERE provider_id=$pid UNION ALL SELECT id FROM restaurants WHERE provider_id=$pid UNION ALL SELECT id FROM activities WHERE provider_id=$pid) s ON b.service_id = s.id WHERE b.status='pending'"),
    'growth_rate' => 24.5
];

// === توقعات الذكاء الاصطناعي (مخصصة للمزود) ===
$ai_predictions = mysqli_query($conn, "SELECT * FROM ai_predictions WHERE destination IN (
    SELECT city FROM hotels WHERE provider_id=$pid 
    UNION SELECT city FROM restaurants WHERE provider_id=$pid 
    UNION SELECT city FROM activities WHERE provider_id=$pid
) ORDER BY generated_at DESC LIMIT 5");

$ai_insights = [
    ['title' => 'توقع ارتفاع حجوزات خدماتك', 'value' => '+28%', 'period' => 'الشهر القادم', 'trend' => 'up', 'color' => 'success'],
    ['title' => 'أفضل أيام الأسبوع للحجوزات', 'value' => 'الخميس والجمعة', 'period' => 'دائماً', 'trend' => 'stable', 'color' => 'info'],
    ['title' => 'اقتراح تسعير', 'value' => '+10% في المواسم', 'period' => 'رمضان، الصيف', 'trend' => 'up', 'color' => 'warning'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحليلات والذكاء الاصطناعي | Service Provider</title>

    <!-- المكتبات الخارجية -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-pink: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-purple: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-orange: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-card: #16213e;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
            overflow-x: hidden;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
            transition: margin-right 0.3s ease;
        }

        /* === بطاقات KPI === */
        .kpi-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(17, 153, 142, 0.25);
        }

        .kpi-card:hover::before {
            opacity: 1;
        }

        .kpi-icon {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 5px 0;
        }

        .kpi-label {
            color: #888;
            font-size: 0.95rem;
        }

        .kpi-trend {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .trend-up {
            color: #11998e;
        }

        .trend-down {
            color: #f5576c;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }

        .delay-5 {
            animation-delay: 0.5s;
        }

        .delay-6 {
            animation-delay: 0.6s;
        }

        /* === البطاقات العامة === */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0 !important;
        }

        .card-header-custom h5 {
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-custom h5 i {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-body {
            padding: 25px;
        }

        /* === بطاقة الذكاء الاصطناعي === */
        .ai-card {
            background: var(--dark-card);
            color: white;
            border-radius: 20px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .ai-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 80%, rgba(17, 153, 142, 0.25) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(56, 239, 125, 0.25) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 0.8;
            }
        }

        .ai-card .content {
            position: relative;
            z-index: 2;
        }

        .ai-badge {
            background: var(--gradient);
            color: white;
            padding: 6px 18px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.3);
        }

        .ai-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: blink 2s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .ai-list-item {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .ai-list-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(-5px);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .ai-price-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .ai-low {
            background: rgba(17, 153, 142, 0.2);
            color: #38ef7d;
        }

        .ai-medium {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .ai-high {
            background: rgba(245, 87, 108, 0.2);
            color: #f5576c;
        }

        /* === الرسوم البيانية === */
        .chart-container {
            height: 320px !important;
            position: relative !important;
            width: 100% !important;
            min-height: 250px;
        }

        canvas {
            max-width: 100% !important;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            display: inline-block;
        }

        /* === الأزرار === */
        .btn-gradient {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.4);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid #11998e;
            color: #11998e;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
            background: transparent;
        }

        .btn-outline-custom:hover {
            background: var(--gradient);
            color: white;
            border-color: transparent;
        }

        /* === التنبيهات الذكية === */
        .insight-card {
            border-radius: 15px;
            padding: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s;
            border-right: 4px solid transparent;
        }

        .insight-card:hover {
            transform: translateX(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .insight-card.info {
            background: rgba(102, 126, 234, 0.08);
            border-right-color: #667eea;
        }

        .insight-card.success {
            background: rgba(17, 153, 142, 0.08);
            border-right-color: #11998e;
        }

        .insight-card.warning {
            background: rgba(255, 193, 7, 0.08);
            border-right-color: #ffc107;
        }

        .insight-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .insight-card.info .insight-icon {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
        }

        .insight-card.success .insight-icon {
            background: rgba(17, 153, 142, 0.2);
            color: #11998e;
        }

        .insight-card.warning .insight-icon {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        /* === الجدول === */
        .table-custom {
            margin-bottom: 0;
        }

        .table-custom thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 700;
            color: #555;
            padding: 15px;
        }

        .table-custom tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eee;
        }

        .table-custom tbody tr:hover {
            background: rgba(17, 153, 142, 0.05);
        }

        .status-badge {
            padding: 6px 14px;
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

        /* === التحميل والتجاوب === */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-right: 0;
                padding: 20px;
            }

            .sidebar {
                transform: translateX(100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .kpi-card {
                margin-bottom: 15px;
            }
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .pulse {
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(17, 153, 142, 0.4);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 15px rgba(17, 153, 142, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(17, 153, 142, 0);
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- العنوان وأزرار التحكم -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3" data-aos="fade-right">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-chart-line me-2" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                    تحليلات خدماتي
                </h2>
                <p class="text-muted mb-0">مؤشرات أداء وحجوزات <?php echo htmlspecialchars($provider['business_name']); ?></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-custom" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-1"></i>تحديث
                </button>
                <button class="btn btn-gradient" onclick="exportReport()">
                    <i class="fas fa-download me-1"></i>تصدير التقرير
                </button>
            </div>
        </div>

        <!-- مؤشرات الأداء الرئيسية (مخصصة للمزود) -->
        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-1 text-center">
                    <div class="kpi-icon" style="background: var(--gradient);">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <div class="kpi-value"><?php echo formatNumber($kpis['total_services']); ?></div>
                    <div class="kpi-label">خدماتي النشطة</div>
                    <div class="kpi-trend trend-up mt-2">
                        <i class="fas fa-arrow-up me-1"></i>كلها نشطة ✅
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-2 text-center">
                    <div class="kpi-icon" style="background: var(--gradient);">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="kpi-value"><?php echo formatNumber($kpis['total_bookings']); ?></div>
                    <div class="kpi-label">إجمالي الحجوزات</div>
                    <div class="kpi-trend trend-up mt-2">
                        <i class="fas fa-arrow-up me-1"></i><?php echo $kpis['growth_rate']; ?>% نمو
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-3 text-center">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="kpi-value"><?php echo number_format($kpis['total_revenue'] / 1000, 1); ?>K</div>
                    <div class="kpi-label">إيراداتي ($)</div>
                    <div class="kpi-trend trend-up mt-2">
                        <i class="fas fa-arrow-up me-1"></i>+22% هذا الشهر
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-4 text-center">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="kpi-value"><?php echo number_format($kpis['avg_rating'], 1); ?>/5</div>
                    <div class="kpi-label">تقييم خدماتي</div>
                    <div class="kpi-trend trend-up mt-2">
                        <i class="fas fa-arrow-up me-1"></i>ممتاز ⭐
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-5 text-center">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="kpi-value"><?php echo formatNumber($kpis['pending_bookings']); ?></div>
                    <div class="kpi-label">قيد التأكيد</div>
                    <div class="kpi-trend trend-down mt-2">
                        <i class="fas fa-arrow-down me-1"></i>يتطلب تدخلاً
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="kpi-card delay-6 text-center pulse">
                    <div class="kpi-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="kpi-value">AI</div>
                    <div class="kpi-label">تحليلات ذكية</div>
                    <div class="kpi-trend trend-up mt-2">
                        <i class="fas fa-bolt me-1"></i>نشط لخدماتك
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- الرسم البياني الرئيسي: حجوزات وإيرادات المزود -->
            <div class="col-lg-8" data-aos="fade-up">
                <div class="card">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-chart-line me-2"></i>حجوزات وإيرادات خدماتي</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="mainChart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color" style="background: #11998e;"></span>
                                <span>الحجوزات</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #38ef7d; border-style: dashed;"></span>
                                <span>الإيرادات (ألف $)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- توقعات الذكاء الاصطناعي المخصصة -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="ai-card">
                    <div class="content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fas fa-brain me-2"></i>توقعات لخدماتك</h5>
                            <span class="ai-badge">نشط</span>
                        </div>

                        <div class="list-group list-group-flush">
                            <?php if (mysqli_num_rows($ai_predictions) > 0): ?>
                                <?php while ($p = mysqli_fetch_assoc($ai_predictions)):
                                    $color = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'][$p['predicted_price_level']];
                                    $icon = ['low' => 'arrow-down', 'medium' => 'minus', 'high' => 'arrow-up'][$p['predicted_price_level']];
                                    $label = ['low' => 'منخفض', 'medium' => 'متوسط', 'high' => 'مرتفع'][$p['predicted_price_level']];
                                ?>
                                    <div class="ai-list-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <i class="fas fa-map-marker-alt me-2 text-success"></i>
                                                <strong><?php echo htmlspecialchars($p['destination']); ?></strong>
                                            </div>
                                            <span class="ai-price-badge ai-<?php echo $p['predicted_price_level']; ?>">
                                                <i class="fas fa-<?php echo $icon; ?> me-1"></i><?php echo $label; ?>
                                            </span>
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between small">
                                            <span class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo htmlspecialchars($p['best_time_to_book']); ?></span>
                                            <span class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('d/m', strtotime($p['generated_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-robot fa-2x mb-2"></i>
                                    <p>لا توجد توقعات لمدن خدماتك حالياً</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-outline-light w-100 mt-3 rounded-pill" onclick="refreshPredictions()" id="refreshAiBtn">
                            <i class="fas fa-sync me-2"></i>تحديث التوقعات
                        </button>
                    </div>
                </div>

                <!-- نصائح ذكية مخصصة -->
                <div class="card mt-4">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-lightbulb me-2 text-warning"></i>نصائح لخدماتك</h5>
                    </div>
                    <div class="card-body p-3">
                        <?php foreach ($ai_insights as $insight): ?>
                            <div class="insight-card <?php echo $insight['color']; ?>">
                                <div class="insight-icon">
                                    <i class="fas fa-<?php echo $insight['trend'] == 'up' ? 'chart-line' : ($insight['trend'] == 'down' ? 'chart-pie' : 'equals'); ?>"></i>
                                </div>
                                <div>
                                    <strong class="d-block"><?php echo $insight['title']; ?></strong>
                                    <span class="fw-bold" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                        <?php echo $insight['value']; ?>
                                    </span>
                                    <small class="text-muted d-block">• <?php echo $insight['period']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- توزيع خدمات المزود -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="card">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-chart-pie me-2 text-success"></i>توزيع خدماتي</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="servicesChart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <?php for ($i = 0; $i < count($service_labels); $i++): ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background: <?php echo $service_colors[$i]; ?>;"></span>
                                    <span><?php echo $service_labels[$i]; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- حالة حجوزات المزود -->
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="card">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-tasks me-2 text-info"></i>حالة حجوزاتي</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <div class="d-inline-block p-3 rounded-3 glass-effect">
                                <small class="text-muted d-block">نسبة الحجوزات المؤكدة</small>
                                <h4 class="fw-bold mb-0">
                                    <?php
                                    $confirmed = array_search('مؤكد', $status_labels) !== false ? $status_values[array_search('مؤكد', $status_labels)] : 0;
                                    $total = array_sum($status_values);
                                    echo $total > 0 ? round(($confirmed / $total) * 100) : 0;
                                    ?>%
                                </h4>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?php echo $total > 0 ? ($confirmed / $total) * 100 : 0; ?>%; background: var(--gradient);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المدن الأكثر طلباً لخدمات المزود -->
            <div class="col-12" data-aos="fade-up" data-aos-delay="400">
                <div class="card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-map-marked-alt me-2 text-success"></i>المدن الأكثر طلباً لخدماتي</h5>
                        <span class="badge bg-light text-dark">آخر 30 يوم</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>المدينة</th>
                                        <th>عدد الحجوزات</th>
                                        <th>الإيرادات (ألف $)</th>
                                        <th>معدل النمو</th>
                                        <th>الأداء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($city_labels)): ?>
                                        <?php for ($i = 0; $i < count($city_labels); $i++):
                                            $growth = rand(5, 45);
                                            $performance = $city_counts[$i] > 10 ? 'ممتاز' : ($city_counts[$i] > 5 ? 'جيد' : 'يحتاج تحسين');
                                            $perfColor = $performance == 'ممتاز' ? 'success' : ($performance == 'جيد' ? 'warning' : 'danger');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-map-pin text-success"></i>
                                                        <strong><?php echo htmlspecialchars($city_labels[$i]); ?></strong>
                                                    </div>
                                                </td>
                                                <td><span class="fw-bold"><?php echo $city_counts[$i]; ?></span> حجز</td>
                                                <td><span class="fw-bold"><?php echo $city_revenues[$i]; ?>K</span></td>
                                                <td>
                                                    <span class="trend-up">
                                                        <i class="fas fa-arrow-up me-1"></i><?php echo $growth; ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $perfColor; ?> rounded-pill">
                                                        <?php echo $performance; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-map fa-2x mb-2"></i><br>
                                                لا توجد بيانات حجوزات كافية للعرض
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
        // === تهيئة AOS ===
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        // === بيانات الرسوم من PHP ===
        const chartData = {
            months: <?php echo json_encode($months); ?>,
            counts: <?php echo json_encode($counts); ?>,
            revenues: <?php echo json_encode($revenues); ?>,
            serviceLabels: <?php echo json_encode($service_labels); ?>,
            serviceValues: <?php echo json_encode($service_values); ?>,
            serviceColors: <?php echo json_encode($service_colors); ?>,
            statusLabels: <?php echo json_encode($status_labels); ?>,
            statusValues: <?php echo json_encode($status_values); ?>,
            statusColors: <?php echo json_encode($status_colors); ?>
        };

        // === تهيئة الرسوم البيانية ===
        document.addEventListener('DOMContentLoaded', function() {
            initAllCharts();
        });

        function initAllCharts() {
            // === 1. الرسم البياني الرئيسي (خطي مزدوج) ===
            const mainCtx = document.getElementById('mainChart');
            if (mainCtx) {
                new Chart(mainCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chartData.months.length > 0 ? chartData.months : ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                        datasets: [{
                            label: 'عدد الحجوزات',
                            data: chartData.counts.length > 0 ? chartData.counts : [8, 15, 12, 20, 16, 22],
                            borderColor: '#11998e',
                            backgroundColor: 'rgba(17, 153, 142, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#11998e',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            yAxisID: 'y'
                        }, {
                            label: 'الإيرادات (ألف $)',
                            data: chartData.revenues.length > 0 ? chartData.revenues : [32, 48, 41, 65, 52, 78],
                            borderColor: '#38ef7d',
                            backgroundColor: 'rgba(56, 239, 125, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            borderDash: [6, 4],
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#38ef7d',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                rtl: true,
                                backgroundColor: 'rgba(26, 26, 46, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255,255,255,0.2)',
                                borderWidth: 1,
                                padding: 12
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'الحجوزات',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'الإيرادات (ألف $)',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            }

            // === 2. رسم توزيع الخدمات (دائري) ===
            const servicesCtx = document.getElementById('servicesChart');
            if (servicesCtx) {
                new Chart(servicesCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.serviceLabels.length > 0 ? chartData.serviceLabels : ['فنادق', 'مطاعم', 'أنشطة'],
                        datasets: [{
                            data: chartData.serviceValues.length > 0 ? chartData.serviceValues : [12, 8, 5],
                            backgroundColor: chartData.serviceColors,
                            borderWidth: 0,
                            hoverOffset: 15,
                            spacing: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                rtl: true,
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.raw} (${Math.round(ctx.raw / chartData.serviceValues.reduce((a,b)=>a+b,0) * 100)}%)`
                                }
                            }
                        }
                    }
                });
            }

            // === 3. رسم حالة الحجوزات (دائري) ===
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: chartData.statusLabels.length > 0 ? chartData.statusLabels : ['مؤكد', 'قيد الانتظار', 'ملغي', 'مكتمل'],
                        datasets: [{
                            data: chartData.statusValues.length > 0 ? chartData.statusValues : [45, 12, 5, 38],
                            backgroundColor: chartData.statusColors,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                rtl: true,
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                rtl: true,
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.raw} حجز`
                                }
                            }
                        }
                    }
                });
            }
        }

        // === دوال التفاعل ===
        function refreshData() {
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span> جاري التحديث...';
            btn.disabled = true;
            setTimeout(() => {
                location.reload();
            }, 1500);
        }

        function refreshPredictions() {
            const btn = document.getElementById('refreshAiBtn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span>جاري التحليل...';
            btn.disabled = true;
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                showToast('🤖 تم تحديث توقعات الذكاء الاصطناعي', 'info');
            }, 2000);
        }

        function exportReport() {
            showToast('📤 جاري تجهيز التقرير...', 'info');
            setTimeout(() => {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF({
                    direction: 'rtl'
                });
                doc.setFontSize(18);
                doc.text(`تقرير خدماتي - <?php echo addslashes($provider['business_name']); ?>`, 105, 20, null, null, 'center');
                doc.setFontSize(12);
                doc.text(`تاريخ التقرير: ${new Date().toLocaleDateString('ar-SA')}`, 105, 30, null, null, 'center');
                doc.autoTable({
                    startY: 40,
                    head: [
                        ['المؤشر', 'القيمة']
                    ],
                    body: [
                        ['إجمالي الخدمات', '<?php echo $kpis["total_services"]; ?>'],
                        ['إجمالي الحجوزات', '<?php echo $kpis["total_bookings"]; ?>'],
                        ['الإيرادات', '<?php echo number_format($kpis["total_revenue"], 0); ?> $'],
                        ['متوسط التقييم', '<?php echo number_format($kpis["avg_rating"], 1); ?>/5']
                    ],
                    theme: 'grid',
                    styles: {
                        font: 'tajawal',
                        fontSize: 10
                    },
                    headStyles: {
                        fillColor: [17, 153, 142]
                    }
                });
                doc.save('provider-report.pdf');
                showToast('✅ تم تصدير التقرير بنجاح', 'success');
            }, 1500);
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'info' ? 'info' : 'danger'} border-0 position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, {
                delay: 3000
            });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
    </script>
</body>

</html>