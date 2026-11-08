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
    "SELECT t.id as traveler_id, t.full_name, t.phone, t.loyalty_points, u.email 
     FROM travelers t JOIN users u ON t.user_id = u.id WHERE t.user_id = $uid"
));

// جلب تفاصيل الخدمة
$service_id = $_GET['id'] ?? 0;
$service_type = $_GET['type'] ?? '';
$service = null;

if ($service_id && $service_type) {
    $table = ['hotel' => 'hotels', 'restaurant' => 'restaurants', 'activity' => 'activities'][$service_type] ?? '';
    if ($table) {
        $service = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT s.*, p.business_name as provider_name, p.user_id as provider_user_id
             FROM $table s JOIN providers p ON s.provider_id = p.id 
             WHERE s.id = $service_id"
        ));
    }
}

// معالجة الحجز
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now']) && $service) {
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $guests = intval($_POST['guests'] ?? 1);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    $base_price = $service['price'] ?? $service['price_per_night'] ?? $service['avg_meal_price'] ?? 0;
    $total_price = $base_price * $guests;

    $sql = "INSERT INTO bookings (traveler_id, service_id, service_type, booking_date, total_price, status, created_at)
            VALUES ({$traveler['traveler_id']}, $service_id, '$service_type', '$booking_date', $total_price, 'pending', NOW())";

    if (mysqli_query($conn, $sql)) {
        $points_earned = floor($total_price / 10);
        mysqli_query($conn, "UPDATE travelers SET loyalty_points = loyalty_points + $points_earned WHERE id = {$traveler['traveler_id']}");
        $msg = "✅ تم إرسال طلب الحجز بنجاح! (+$points_earned نقطة ولاء)";
        $msgType = "success";
    } else {
        $msg = "❌ خطأ في الحجز: " . mysqli_error($conn);
        $msgType = "danger";
    }
}

// جلب التقييمات
$reviews = mysqli_query(
    $conn,
    "SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id 
     WHERE r.service_id = $service_id AND r.service_type = '$service_type' ORDER BY r.created_at DESC LIMIT 5"
);
$review_stats = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as total, AVG(rating) as avg FROM reviews 
     WHERE service_id = $service_id AND service_type = '$service_type'"
));

// ✅ دالة مساعدة لمسار الصورة
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
    <title><?php echo htmlspecialchars($service['name'] ?? 'تفاصيل الخدمة'); ?> | TravelPlanner</title>
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
            --success: #22c55e;
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
                radial-gradient(ellipse at 50% 50%, rgba(15, 23, 42, 0.95) 0%, var(--blue-darker) 100%);
        }

        .main-content {
            margin-top: 70px;
            padding: 30px 20px;
            max-width: 1200px;
            margin: 70px auto 0;
        }

        .service-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            box-shadow: var(--card-shadow);
        }

        .service-gallery {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            min-height: 300px;
        }

        .service-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-gallery-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-primary);
            font-size: 4rem;
            border-radius: 16px;
        }

        .service-info h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .service-provider {
            color: var(--text-secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .service-location {
            color: var(--text-secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .service-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #f59e0b;
        }

        .service-description {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .booking-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            position: sticky;
            top: 90px;
        }

        .booking-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 20px;
            text-align: center;
        }

        .booking-price span {
            font-size: 1rem;
            font-weight: 400;
            color: var(--text-secondary);
        }

        .booking-form .form-group {
            margin-bottom: 15px;
        }

        .booking-form label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .booking-form input,
        .booking-form select,
        .booking-form textarea {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
        }

        .booking-form input:focus,
        .booking-form select:focus,
        .booking-form textarea:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .btn-book {
            width: 100%;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
        }

        .reviews-section {
            margin-top: 40px;
        }

        .review-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 600;
        }

        .review-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .review-stars {
            color: #f59e0b;
            margin-bottom: 8px;
        }

        .review-comment {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-right: 4px solid #22c55e;
            color: #86efac;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border-right: 4px solid #ef4444;
            color: #fca5a5;
        }

        @media (max-width: 900px) {
            .service-header {
                grid-template-columns: 1fr;
            }

            .booking-card {
                position: static;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <?php if (!$service): ?>
            <div class="alert alert-danger">❌ الخدمة غير موجودة</div>
            <a href="search.php" class="btn-filter btn-outline">← العودة للبحث</a>
        <?php else: ?>
            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="service-header" data-aos="fade-up">
                <div>
                    <div class="service-gallery">
                        <?php
                        // ✅ استخدام الدالة المساعدة لمسار الصورة
                        $img = buildImagePath($service['image_url']);
                        $icon = ['hotel' => 'hotel', 'restaurant' => 'utensils', 'activity' => 'umbrella-beach'][$service_type] ?? 'store';
                        ?>
                        <?php if ($img['exists'] && !empty($img['browser'])): ?>
                            <img src="<?php echo $img['browser']; ?>"
                                alt="<?php echo htmlspecialchars($service['name']); ?>"
                                onerror="this.parentElement.innerHTML='<div class=\'service-gallery-placeholder\'><i class=\'fas fa-<?php echo $icon; ?>\'></i></div>'">
                        <?php else: ?>
                            <div class="service-gallery-placeholder">
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="service-info">
                    <h1><?php echo htmlspecialchars($service['name']); ?></h1>
                    <p class="service-provider"><i class="fas fa-store"></i> <?php echo htmlspecialchars($service['provider_name']); ?></p>
                    <p class="service-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($service['city']); ?></p>
                    <div class="service-rating">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= ($service['rating'] ?? 0) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span><?php echo number_format($review_stats['avg'] ?? $service['rating'] ?? 0, 1); ?>/5</span>
                        <span class="text-secondary">(<?php echo $review_stats['total'] ?? 0; ?> تقييم)</span>
                    </div>
                    <p class="service-description"><?php echo nl2br(htmlspecialchars($service['description'] ?? 'لا يوجد وصف متاح')); ?></p>
                    <?php if ($service_type === 'hotel' && isset($service['category'])): ?>
                        <p><strong>الفئة:</strong> <?php echo ['economic' => 'اقتصادي', 'medium' => 'متوسط', 'luxury' => 'فاخر'][$service['category']] ?? $service['category']; ?></p>
                    <?php elseif ($service_type === 'restaurant' && isset($service['cuisine_type'])): ?>
                        <p><strong>نوع المطبخ:</strong> <?php echo htmlspecialchars($service['cuisine_type']); ?></p>
                    <?php elseif ($service_type === 'activity' && isset($service['duration_hours'])): ?>
                        <p><strong>المدة:</strong> <?php echo $service['duration_hours']; ?> ساعة</p>
                    <?php endif; ?>
                </div>

                <div class="booking-card" id="book">
                    <div class="booking-price">
                        <?php echo number_format($service['price'] ?? $service['price_per_night'] ?? $service['avg_meal_price'] ?? 0, 2); ?>
                        <span>$ / <?php echo $service_type === 'hotel' ? 'ليلة' : ($service_type === 'restaurant' ? 'وجبة' : 'شخص'); ?></span>
                    </div>
                    <form method="POST" class="booking-form">
                        <div class="form-group">
                            <label>تاريخ <?php echo $service_type === 'hotel' ? 'الإقامة' : 'الزيارة'; ?></label>
                            <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo $service_type === 'hotel' ? 'عدد الغرف' : 'عدد الأشخاص'; ?></label>
                            <select name="guests">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $service_type === 'hotel' ? 'غرفة' : 'شخص'; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ملاحظات إضافية (اختياري)</label>
                            <textarea name="notes" rows="3" placeholder="أي طلبات خاصة..."></textarea>
                        </div>
                        <button type="submit" name="book_now" class="btn-book">
                            <i class="fas fa-calendar-check"></i> تأكيد الحجز
                        </button>
                        <small class="text-secondary d-block text-center mt-2" style="font-size:0.8rem;">
                            <i class="fas fa-shield-alt"></i> دفع آمن • إلغاء مجاني قبل 24 ساعة
                        </small>
                    </form>
                </div>
            </div>

            <div class="reviews-section">
                <h3 class="section-title mb-4"><i class="fas fa-comments me-2"></i>تقييمات العملاء</h3>
                <?php if (mysqli_num_rows($reviews) > 0): ?>
                    <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
                        <div class="review-card" data-aos="fade-up">
                            <div class="review-header">
                                <span class="reviewer-name"><?php echo htmlspecialchars($rev['username']); ?></span>
                                <span class="review-date"><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $rev['rating'] ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-secondary">لا توجد تقييمات بعد. كن أول من يقيم!</p>
                <?php endif; ?>
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