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
    "SELECT t.*, u.username FROM travelers t JOIN users u ON t.user_id = u.id WHERE t.user_id = $uid"
));

// === معالجة فلاتر البحث ===
$destination = $_GET['destination'] ?? '';
$service_type = $_GET['type'] ?? 'all';
$city = $_GET['city'] ?? '';
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 100000);
$min_rating = floatval($_GET['min_rating'] ?? 0);
$sort_by = $_GET['sort'] ?? 'rating_desc';

// === بناء شروط الفلترة ===
$where_parts = [];
if ($city) {
    $city_esc = mysqli_real_escape_string($conn, $city);
    $where_parts[] = "city LIKE '%$city_esc%'";
}
if ($min_price > 0) $where_parts[] = "price >= $min_price";
if ($max_price < 100000) $where_parts[] = "price <= $max_price";
if ($min_rating > 0) $where_parts[] = "rating >= $min_rating";
$where_clause = $where_parts ? "AND " . implode(" AND ", $where_parts) : "";

// === تحديد الترتيب ===
$sort_options = [
    'rating_desc' => 'ORDER BY rating DESC, price ASC',
    'rating_asc'  => 'ORDER BY rating ASC, price ASC',
    'price_asc'   => 'ORDER BY price ASC, rating DESC',
    'price_desc'  => 'ORDER BY price DESC, rating DESC',
    'name_asc'    => 'ORDER BY name ASC',
];
$order_clause = $sort_options[$sort_by] ?? 'ORDER BY rating DESC';

// === بناء استعلامات موحدة الأعمدة ===
$queries = [];

// فنادق
if ($service_type === 'all' || $service_type === 'hotel') {
    $queries[] = "
        SELECT 
            h.id,
            h.name,
            h.city,
            h.price_per_night AS price,
            h.rating,
            h.image_url,
            h.description,
            p.business_name AS provider,
            'hotel' AS type,
            h.category AS extra_info
        FROM hotels h
        JOIN providers p ON h.provider_id = p.id
        WHERE h.price_per_night IS NOT NULL $where_clause
    ";
}

// مطاعم
if ($service_type === 'all' || $service_type === 'restaurant') {
    $queries[] = "
        SELECT 
            r.id,
            r.name,
            r.city,
            r.avg_meal_price AS price,
            r.rating,
            r.image_url,
            r.description,
            p.business_name AS provider,
            'restaurant' AS type,
            r.cuisine_type AS extra_info
        FROM restaurants r
        JOIN providers p ON r.provider_id = p.id
        WHERE r.avg_meal_price IS NOT NULL $where_clause
    ";
}

// أنشطة
if ($service_type === 'all' || $service_type === 'activity') {
    $queries[] = "
        SELECT 
            a.id,
            a.name,
            a.city,
            a.price,
            a.rating,
            a.image_url,
            a.description,
            p.business_name AS provider,
            'activity' AS type,
            CONCAT(a.duration_hours, ' ساعات') AS extra_info
        FROM activities a
        JOIN providers p ON a.provider_id = p.id
        WHERE a.price IS NOT NULL $where_clause
    ";
}

// === تنفيذ الاستعلام المدمج بشكل صحيح ===
$results = null;
if (!empty($queries)) {
    $union_sql = implode(" UNION ALL ", $queries);
    $full_sql = "$union_sql $order_clause";
    $results = mysqli_query($conn, $full_sql);
    if (!$results) {
        error_log("SQL Error: " . mysqli_error($conn));
        error_log("Query: $full_sql");
    }
}

// === جلب المدن للفلاتر ===
$cities_result = mysqli_query($conn, "
    SELECT DISTINCT city FROM hotels WHERE city IS NOT NULL AND city != ''
    UNION
    SELECT DISTINCT city FROM restaurants WHERE city IS NOT NULL AND city != ''
    UNION
    SELECT DISTINCT city FROM activities WHERE city IS NOT NULL AND city != ''
    ORDER BY city ASC
");

// === دالة مساعدة لبناء مسار الصورة ✅ ===
function buildImagePath($image_url)
{
    if (empty($image_url)) return ['browser' => '', 'server' => '', 'exists' => false];
    $browser_path = '../' . htmlspecialchars($image_url);
    $server_path = __DIR__ . '/../' . $image_url;
    return [
        'browser' => $browser_path,
        'server' => $server_path,
        'exists' => file_exists($server_path)
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استكشف | TravelPlanner</title>
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
            --blue-light: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --glass-bg: rgba(15, 23, 42, 0.85);
            --glass-border: rgba(212, 175, 55, 0.3);
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --transition: all 0.3s ease;
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
            background: radial-gradient(ellipse at 20% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(30, 64, 175, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(15, 23, 42, 0.95) 0%, var(--blue-darker) 100%);
        }

        .main-content {
            margin-top: 70px;
            padding: 30px 20px;
            max-width: 1400px;
            margin: 70px auto 0;
        }

        .search-filters {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .filter-group label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 14px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
            transition: var(--transition);
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .btn-filter {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--glass-border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            border-color: var(--gold-primary);
            background: rgba(212, 175, 55, 0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-count {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }

        .sort-select {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 8px 14px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .service-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-8px);
            border-color: var(--gold-primary);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        .service-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .service-card:hover .service-image img {
            transform: scale(1.1);
        }

        .service-type-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
        }

        .service-type-badge.hotel {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .service-type-badge.restaurant {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .service-type-badge.activity {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .service-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .service-name {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .service-provider {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .service-location {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .service-extra {
            background: rgba(212, 175, 55, 0.1);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--gold-light);
            margin-bottom: 12px;
            display: inline-block;
        }

        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--glass-border);
        }

        .service-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #22c55e;
        }

        .service-price span {
            font-size: 0.9rem;
            font-weight: 400;
            color: var(--text-secondary);
        }

        .service-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-weight: 600;
        }

        .service-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .btn-card {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-view {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
        }

        .btn-view:hover {
            background: var(--gold-primary);
            color: var(--blue-dark);
        }

        .btn-book {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
            display: block;
        }

        .no-results h3 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .results-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <div class="search-filters" data-aos="fade-down">
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label><i class="fas fa-location-dot me-1"></i>المدينة</label>
                    <select name="city">
                        <option value="">كل المدن</option>
                        <?php while ($c = mysqli_fetch_assoc($cities_result)): ?>
                            <option value="<?php echo htmlspecialchars($c['city']); ?>" <?php echo $city === $c['city'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['city']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-layer-group me-1"></i>نوع الخدمة</label>
                    <select name="type">
                        <option value="all" <?php echo $service_type === 'all' ? 'selected' : ''; ?>>الكل</option>
                        <option value="hotel" <?php echo $service_type === 'hotel' ? 'selected' : ''; ?>>🏨 فنادق</option>
                        <option value="restaurant" <?php echo $service_type === 'restaurant' ? 'selected' : ''; ?>>🍽️ مطاعم</option>
                        <option value="activity" <?php echo $service_type === 'activity' ? 'selected' : ''; ?>>🎯 أنشطة</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag me-1"></i>السعر الأدنى</label>
                    <input type="number" name="min_price" value="<?php echo $min_price; ?>" min="0" placeholder="من">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag me-1"></i>السعر الأقصى</label>
                    <input type="number" name="max_price" value="<?php echo $max_price; ?>" min="0" placeholder="إلى">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-star me-1"></i>أدنى تقييم</label>
                    <select name="min_rating">
                        <option value="0" <?php echo $min_rating == 0 ? 'selected' : ''; ?>>أي تقييم</option>
                        <option value="3" <?php echo $min_rating == 3 ? 'selected' : ''; ?>>3+ ⭐</option>
                        <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4+ ⭐⭐</option>
                        <option value="4.5" <?php echo $min_rating == 4.5 ? 'selected' : ''; ?>>4.5+ ⭐⭐⭐</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-sort me-1"></i>ترتيب حسب</label>
                    <select name="sort">
                        <option value="rating_desc" <?php echo $sort_by === 'rating_desc' ? 'selected' : ''; ?>>الأعلى تقييماً</option>
                        <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>الأرخص أولاً</option>
                        <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>الأغلى أولاً</option>
                        <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>الاسم أ-ي</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <a href="search.php" class="btn-filter btn-outline"><i class="fas fa-undo"></i> إعادة تعيين</a>
                    <button type="submit" class="btn-filter btn-primary"><i class="fas fa-search"></i> بحث</button>
                </div>
            </form>
        </div>

        <div class="results-header">
            <div class="results-count">
                <i class="fas fa-list me-2"></i>
                <?php echo ($results && mysqli_num_rows($results) > 0) ? mysqli_num_rows($results) : 0; ?> نتيجة
            </div>
            <select class="sort-select" onchange="location.href=this.value">
                <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'rating_desc'])); ?>" <?php echo $sort_by === 'rating_desc' ? 'selected' : ''; ?>>الأعلى تقييماً</option>
                <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>الأرخص أولاً</option>
                <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>الأغلى أولاً</option>
            </select>
        </div>

        <div class="results-grid">
            <?php if ($results && mysqli_num_rows($results) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($results)):
                    // ✅ استخدام الدالة المساعدة لمسار الصورة
                    $img = buildImagePath($item['image_url']);
                    $type_badge = ['hotel' => 'فندق', 'restaurant' => 'مطعم', 'activity' => 'نشاط'][$item['type']] ?? '';
                    $icon = ['hotel' => 'hotel', 'restaurant' => 'utensils', 'activity' => 'umbrella-beach'][$item['type']] ?? 'store';
                ?>
                    <div class="service-card" data-aos="fade-up">
                        <div class="service-image">
                            <?php if ($img['exists'] && !empty($img['browser'])): ?>
                                <img src="<?php echo $img['browser']; ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    loading="lazy"
                                    onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:100%;background:linear-gradient(135deg,#1e293b,#0f172a);display:flex;align-items:center;justify-content:center;color:var(--gold-primary);font-size:3rem;\'><i class=\'fas fa-<?php echo $icon; ?>\'></i></div>'">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:linear-gradient(135deg,#1e293b,#0f172a);display:flex;align-items:center;justify-content:center;color:var(--gold-primary);font-size:3rem;">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                            <?php endif; ?>
                            <span class="service-type-badge <?php echo $item['type']; ?>"><?php echo $type_badge; ?></span>
                        </div>
                        <div class="service-content">
                            <h3 class="service-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="service-provider"><i class="fas fa-store"></i> <?php echo htmlspecialchars($item['provider']); ?></p>
                            <p class="service-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['city']); ?></p>
                            <?php if ($item['extra_info']): ?>
                                <span class="service-extra"><?php echo htmlspecialchars($item['extra_info']); ?></span>
                            <?php endif; ?>
                            <div class="service-footer">
                                <div class="service-price"><?php echo number_format($item['price'], 2); ?> <span>$</span></div>
                                <div class="service-rating">
                                    <i class="fas fa-star"></i> <?php echo number_format($item['rating'] ?? 0, 1); ?>
                                </div>
                            </div>
                            <div class="service-actions">
                                <a href="service_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['type']; ?>" class="btn-card btn-view">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                                <a href="service_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['type']; ?>#book" class="btn-card btn-book">
                                    <i class="fas fa-calendar-check"></i> احجز
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-search"></i>
                    <h3>لا توجد نتائج مطابقة</h3>
                    <p>جرب تعديل معايير البحث أو إزالة بعض الفلاتر</p>
                    <a href="search.php" class="btn-filter btn-primary" style="margin-top:20px;">
                        <i class="fas fa-undo"></i> إعادة تعيين الفلاتر
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true,
            offset: 50
        });
    </script>
</body>

</html>