<?php
session_start();
include '../config/db.php';

// التحقق من دخول المسافر
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$traveler = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT t.*, u.username, u.email 
     FROM travelers t 
     JOIN users u ON t.user_id = u.id 
     WHERE t.user_id = $uid"
));

// إحصائيات سريعة
$stats = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT 
        COUNT(CASE WHEN service_type='hotel' THEN 1 END) as hotels,
        COUNT(CASE WHEN service_type='restaurant' THEN 1 END) as restaurants,
        COUNT(CASE WHEN service_type='activity' THEN 1 END) as activities,
        SUM(total_price) as total_spent
     FROM bookings 
     WHERE traveler_id = {$traveler['id']} AND status != 'cancelled'"
));

// وجهات مميزة
$featured_destinations = mysqli_query(
    $conn,
    "SELECT * FROM destinations WHERE is_featured = 1 LIMIT 6"
);

// أنشطة مقترحة (بناءً على المدينة)
$suggested_activities = mysqli_query(
    $conn,
    "SELECT a.*, p.business_name as provider_name 
     FROM activities a 
     JOIN providers p ON a.provider_id = p.id 
     WHERE a.rating >= 4.5 
     ORDER BY a.rating DESC, a.price ASC 
     LIMIT 4"
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرئيسية | TravelPlanner</title>

    <!-- الخطوط والمكتبات -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* === المتغيرات العامة === */
        :root {
            --gold-primary: #d4af37;
            --gold-light: #e6c96a;
            --gold-dark: #b89628;
            --blue-dark: #0f172a;
            --blue-darker: #020617;
            --blue-light: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(212, 175, 55, 0.2);
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* === خلفية متحركة فاخرة === */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background:
                radial-gradient(ellipse at 20% 80%, rgba(212, 175, 55, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(30, 64, 175, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(15, 23, 42, 0.9) 0%, var(--blue-darker) 100%);
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

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--gold-primary);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.3;
            }

            90% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* === المحتوى الرئيسي === */
        .main-content {
            margin-top: 70px;
            padding: 30px 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* === بطاقة الترحيب === */
        .welcome-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--gold-dark), var(--gold-primary), var(--gold-light));
            animation: glowLine 3s ease-in-out infinite;
        }

        @keyframes glowLine {

            0%,
            100% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
                box-shadow: 0 0 20px var(--gold-primary);
            }
        }

        .welcome-content h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .welcome-content h1 span {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-content p {
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        .welcome-cta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            color: var(--blue-dark);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--glass-border);
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-outline:hover {
            border-color: var(--gold-primary);
            background: rgba(212, 175, 55, 0.1);
        }

        .loyalty-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(245, 158, 11, 0.15);
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .loyalty-badge i {
            color: var(--warning);
            font-size: 1.2rem;
        }

        .loyalty-badge span {
            font-weight: 700;
            color: var(--warning);
        }

        /* === بطاقات الإحصائيات === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold-dark), var(--gold-primary), var(--gold-light));
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold-primary);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }

        .stat-icon.hotels {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .stat-icon.restaurants {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .stat-icon.activities {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .stat-icon.spent {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* === قسم العناوين === */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--glass-border);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--gold-primary);
        }

        .view-all {
            color: var(--gold-primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .view-all:hover {
            gap: 8px;
            color: var(--gold-light);
        }

        /* === بطاقات الوجهات === */
        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .destination-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        .destination-card:hover {
            transform: translateY(-8px);
            border-color: var(--gold-primary);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        .destination-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .destination-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .destination-card:hover .destination-image img {
            transform: scale(1.1);
        }

        .destination-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .destination-content {
            padding: 20px;
        }

        .destination-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .destination-country {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .destination-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 12px;
        }

        /* === الأنشطة المقترحة === */
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .activity-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            gap: 15px;
            transition: var(--transition);
        }

        .activity-card:hover {
            border-color: var(--gold-primary);
            transform: translateX(-5px);
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(212, 175, 55, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-primary);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-name {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .activity-meta i {
            color: var(--gold-primary);
        }

        .activity-price {
            font-weight: 700;
            color: var(--success);
            font-size: 1.1rem;
        }

        .activity-rating {
            display: flex;
            align-items: center;
            gap: 3px;
            color: var(--warning);
            font-size: 0.85rem;
        }

        /* === البحث السريع === */
        .quick-search {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 40px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .search-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .search-group label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .search-group input,
        .search-group select {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .search-group input:focus,
        .search-group select:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 46px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        /* === التجاوب === */
        @media (max-width: 768px) {
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }

            .welcome-cta {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .destinations-grid {
                grid-template-columns: 1fr;
            }

            .activities-grid {
                grid-template-columns: 1fr;
            }
        }

        /* === أنيميشن الظهور === */
        [data-aos] {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        [data-aos].aos-animate {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <!-- الخلفية المتحركة -->
    <div class="bg-animation"></div>
    <div class="particles" id="particles"></div>

    <!-- النافبار الفاخر -->
    <?php include 'navbar.php'; ?>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">
        <!-- بطاقة الترحيب -->
        <div class="welcome-card" data-aos="fade-down">
            <div class="welcome-content">
                <h1>أهلاً بك، <span><?php echo htmlspecialchars(explode(' ', $traveler['full_name'])[0]); ?>!</span> 👋</h1>
                <p>جاهز لاكتشاف وجهتك القادمة؟</p>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="loyalty-badge">
                    <i class="fas fa-coins"></i>
                    <span><?php echo number_format($traveler['loyalty_points']); ?> نقطة</span>
                </div>
                <div class="welcome-cta">
                    <a href="plan_trip.php" class="btn-gold">
                        <i class="fas fa-plus"></i> رحلة جديدة
                    </a>
                    <a href="search.php" class="btn-outline">
                        <i class="fas fa-search"></i> استكشف
                    </a>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon hotels">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="stat-value"><?php echo $stats['hotels'] ?? 0; ?></div>
                <div class="stat-label">فنادق محجوزة</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon restaurants">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-value"><?php echo $stats['restaurants'] ?? 0; ?></div>
                <div class="stat-label">مطاعم محجوزة</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon activities">
                    <i class="fas fa-umbrella-beach"></i>
                </div>
                <div class="stat-value"><?php echo $stats['activities'] ?? 0; ?></div>
                <div class="stat-label">أنشطة محجوزة</div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-icon spent">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 0); ?> $</div>
                <div class="stat-label">إجمالي الإنفاق</div>
            </div>
        </div>

        <!-- بحث سريع -->
        <div class="quick-search" data-aos="fade-up">
            <form action="search.php" method="GET" class="search-form">
                <div class="search-group">
                    <label><i class="fas fa-location-dot me-1"></i>الوجهة</label>
                    <input type="text" name="destination" placeholder="أين تريد السفر؟">
                </div>
                <div class="search-group">
                    <label><i class="fas fa-calendar me-1"></i>تاريخ السفر</label>
                    <input type="date" name="start_date">
                </div>
                <div class="search-group">
                    <label><i class="fas fa-users me-1"></i>عدد الأشخاص</label>
                    <select name="guests">
                        <option value="1">شخص واحد</option>
                        <option value="2">شخصان</option>
                        <option value="3-4">3-4 أشخاص</option>
                        <option value="5+">5+ أشخاص</option>
                    </select>
                </div>
                <div class="search-group">
                    <label><i class="fas fa-tag me-1"></i>الميزانية</label>
                    <select name="budget">
                        <option value="">أي ميزانية</option>
                        <option value="economy">💰 اقتصادية</option>
                        <option value="medium">⭐ متوسطة</option>
                        <option value="luxury">👑 فاخرة</option>
                    </select>
                </div>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> ابحث
                </button>
            </form>
        </div>

        <!-- وجهات مميزة -->
        <div class="section-header" data-aos="fade-right">
            <h2 class="section-title">
                <i class="fas fa-star"></i>
                وجهات مميزة لك
            </h2>
            <a href="search.php" class="view-all">
                عرض الكل <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        <div class="destinations-grid">
            <?php while ($dest = mysqli_fetch_assoc($featured_destinations)): ?>
                <div class="destination-card" data-aos="fade-up">
                    <div class="destination-image">
                        <?php if ($dest['image_url']): ?>
                            <img src="../<?php echo htmlspecialchars($dest['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($dest['name']); ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,#1e293b,#0f172a);display:flex;align-items:center;justify-content:center;color:var(--gold-primary);font-size:3rem;">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                        <?php endif; ?>
                        <span class="destination-badge">مميز</span>
                    </div>
                    <div class="destination-content">
                        <h3 class="destination-name"><?php echo htmlspecialchars($dest['name']); ?></h3>
                        <p class="destination-country">
                            <i class="fas fa-globe"></i>
                            <?php echo htmlspecialchars($dest['country']); ?>
                        </p>
                        <div class="destination-actions">
                            <a href="search.php?destination=<?php echo urlencode($dest['name']); ?>"
                                class="btn-gold btn-sm">
                                <i class="fas fa-eye"></i> استكشف
                            </a>
                            <a href="plan_trip.php?dest=<?php echo urlencode($dest['name']); ?>"
                                class="btn-outline btn-sm">
                                <i class="fas fa-plus"></i> أضف لرحلتي
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- أنشطة مقترحة -->
        <div class="section-header" data-aos="fade-right">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                أنشطة مقترحة
            </h2>
            <a href="search.php?type=activity" class="view-all">
                عرض الكل <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        <div class="activities-grid">
            <?php while ($activity = mysqli_fetch_assoc($suggested_activities)): ?>
                <div class="activity-card" data-aos="fade-up">
                    <div class="activity-icon">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <div class="activity-content">
                        <h4 class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></h4>
                        <div class="activity-meta">
                            <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($activity['city']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo $activity['duration_hours']; ?> ساعة</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="activity-price"><?php echo number_format($activity['price'], 2); ?> $</span>
                            <div class="activity-rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($activity['rating'], 1); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- المكتبات -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // === تهيئة مكتبة الأنيميشن ===
        AOS.init({
            duration: 600,
            once: true,
            offset: 50
        });

        // === إنشاء الجسيمات العائمة ===
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 20) + 's';
                container.appendChild(particle);
            }
        }

        createParticles();

        // === تأثيرات إضافية ===
        document.addEventListener('scroll', function() {
            const navbar = document.querySelector('.luxury-navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(15, 23, 42, 0.95)';
            } else {
                navbar.style.background = 'rgba(15, 23, 42, 0.85)';
            }
        });
    </script>
</body>

</html>