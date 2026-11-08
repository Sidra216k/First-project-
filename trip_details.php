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
    "SELECT t.id as traveler_id, t.full_name, t.loyalty_points FROM travelers t WHERE t.user_id = $uid"
));
$tid = $traveler['traveler_id'] ?? 0;

// جلب تفاصيل الرحلة
$trip_id = $_GET['id'] ?? 0;
$trip = null;

if ($trip_id > 0) {
    $trip = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM trips WHERE id = $trip_id AND traveler_id = $tid"
    ));
}

// إذا لم توجد الرحلة أو لا يملكها المستخدم
if (!$trip) {
    header("Location: my_itinerary.php?error=not_found");
    exit();
}

// فك ترميز مسار الرحلة
$itinerary = json_decode($trip['itinerary_json'], true) ?? [];

// معالجة حذف الرحلة
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    mysqli_query($conn, "DELETE FROM trips WHERE id = $trip_id AND traveler_id = $tid");
    header("Location: my_itinerary.php?msg=deleted");
    exit();
}

// معالجة حجز جميع الخدمات في الرحلة
$booking_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_all'])) {
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $success_count = 0;

    foreach ($itinerary as $item) {
        $service_id = intval($item['id']);
        $service_type = mysqli_real_escape_string($conn, $item['type']);

        // جلب سعر الخدمة الفعلي من قاعدة البيانات
        $price = 0;
        if ($service_type === 'hotel') {
            $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_night FROM hotels WHERE id = $service_id"));
            $price = $res['price_per_night'] ?? 0;
        } elseif ($service_type === 'restaurant') {
            $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT avg_meal_price FROM restaurants WHERE id = $service_id"));
            $price = $res['avg_meal_price'] ?? 0;
        } elseif ($service_type === 'activity') {
            $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM activities WHERE id = $service_id"));
            $price = $res['price'] ?? 0;
        }

        // إدخال الحجز
        $total = $price; // يمكن تعديلها حسب عدد الأشخاص لاحقاً
        $sql = "INSERT INTO bookings (traveler_id, service_id, service_type, booking_date, total_price, status, created_at)
                VALUES ($tid, $service_id, '$service_type', '$booking_date', $total, 'pending', NOW())";

        if (mysqli_query($conn, $sql)) {
            $success_count++;
        }
    }

    if ($success_count > 0) {
        // إضافة نقاط ولاء
        $points = floor($success_count * 10);
        mysqli_query($conn, "UPDATE travelers SET loyalty_points = loyalty_points + $points WHERE id = $tid");
        $booking_msg = "✅ تم إرسال <strong>$success_count</strong> طلبات حجز بنجاح! (+$points نقطة ولاء)";
    } else {
        $booking_msg = "⚠️ لم يتم تأكيد أي حجز. تأكد من توفر الخدمات.";
    }
}

// دالة لعرض أيقونة حسب نوع الخدمة
function getServiceIcon($type)
{
    return [
        'hotel' => ['icon' => 'fa-hotel', 'color' => '#3b82f6', 'label' => 'فندق'],
        'restaurant' => ['icon' => 'fa-utensils', 'color' => '#f59e0b', 'label' => 'مطعم'],
        'activity' => ['icon' => 'fa-umbrella-beach', 'color' => '#22c55e', 'label' => 'نشاط']
    ][$type] ?? ['icon' => 'fa-store', 'color' => '#64748b', 'label' => 'خدمة'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trip['destination']); ?> | تفاصيل الرحلة</title>
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
            --success: #22c55e;
            --warning: #f59e0b;
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
            max-width: 1200px;
            margin: 70px auto 0;
        }

        /* رأس الرحلة */
        .trip-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .trip-title h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trip-title h1 i {
            color: var(--gold-primary);
        }

        .trip-dates {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .trip-budget {
            background: rgba(212, 175, 55, 0.15);
            padding: 8px 16px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--gold-light);
            font-weight: 600;
        }

        .trip-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-header {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-edit {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .btn-edit:hover {
            background: var(--gold-primary);
            color: var(--blue-dark);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        .btn-back {
            background: rgba(148, 163, 184, 0.15);
            color: var(--text-secondary);
        }

        .btn-back:hover {
            background: rgba(148, 163, 184, 0.3);
            color: var(--text-primary);
        }

        /* مسار الرحلة */
        .itinerary-section {
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--glass-border);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--gold-primary);
        }

        .itinerary-timeline {
            position: relative;
            padding-right: 30px;
        }

        .itinerary-timeline::before {
            content: '';
            position: absolute;
            right: 10px;
            top: 20px;
            bottom: 20px;
            width: 2px;
            background: linear-gradient(180deg, var(--gold-dark), var(--gold-primary), var(--gold-light));
            border-radius: 2px;
        }

        .timeline-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            position: relative;
            transition: var(--transition);
        }

        .timeline-item:hover {
            border-color: var(--gold-primary);
            transform: translateX(-5px);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            right: -37px;
            top: 25px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--gold-primary);
            border: 3px solid var(--blue-dark);
            z-index: 1;
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-content h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .timeline-content .service-type {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timeline-content .service-price {
            font-weight: 700;
            color: var(--success);
            font-size: 1.1rem;
        }

        .timeline-content .service-meta {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 8px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .timeline-content .service-meta i {
            color: var(--gold-primary);
            margin-left: 4px;
        }

        .timeline-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .btn-timeline {
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-book-now {
            background: linear-gradient(135deg, var(--success), #16a34a);
            color: white;
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
        }

        .btn-view-service {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .btn-view-service:hover {
            background: var(--gold-primary);
            color: var(--blue-dark);
        }

        /* نموذج الحجز الجماعي */
        .bulk-booking-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 2px solid var(--gold-primary);
            border-radius: 20px;
            padding: 25px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
        }

        .bulk-booking-card h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .bulk-booking-card h3 i {
            color: var(--gold-primary);
        }

        .booking-form {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: end;
        }

        .booking-form .form-group {
            text-align: right;
        }

        .booking-form label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .booking-form input {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: rgba(30, 41, 59, 0.6);
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
            font-size: 0.95rem;
        }

        .booking-form input:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .btn-book-all {
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            border-radius: 12px;
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-book-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
        }

        .btn-book-all:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* رسائل التنبيه */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-right: 4px solid var(--success);
            color: #86efac;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border-right: 4px solid var(--warning);
            color: #fcd34d;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.15);
            border-right: 4px solid #3b82f6;
            color: #93c5fd;
        }

        /* حالة فارغة */
        .empty-itinerary {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-itinerary i {
            font-size: 3rem;
            opacity: 0.3;
            margin-bottom: 15px;
            display: block;
        }

        /* ملخص الرحلة */
        .trip-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--glass-border);
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 12px;
        }

        .summary-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gold-primary);
        }

        .summary-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .trip-header {
                flex-direction: column;
                text-align: center;
            }

            .trip-actions {
                justify-content: center;
            }

            .itinerary-timeline::before {
                right: 15px;
            }

            .timeline-item::before {
                right: -32px;
            }

            .timeline-item {
                flex-direction: column;
                text-align: center;
            }

            .timeline-actions {
                align-items: center;
            }

            .booking-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <!-- رأس الرحلة -->
        <div class="trip-header" data-aos="fade-down">
            <div class="trip-title">
                <h1><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($trip['destination']); ?></h1>
                <p class="trip-dates">
                    <i class="fas fa-calendar-range me-1"></i>
                    <?php echo date('Y/m/d', strtotime($trip['start_date'])); ?>
                    → <?php echo date('Y/m/d', strtotime($trip['end_date'])); ?>
                </p>
                <span class="trip-budget">
                    <i class="fas fa-wallet"></i> <?php echo number_format($trip['total_budget'], 2); ?> $ ميزانية
                </span>
            </div>
            <div class="trip-actions">
                <a href="my_itinerary.php" class="btn-header btn-back">
                    <i class="fas fa-arrow-right"></i> العودة
                </a>
                <a href="plan_trip.php?edit=<?php echo $trip['id']; ?>" class="btn-header btn-edit">
                    <i class="fas fa-edit"></i> تعديل
                </a>
                <a href="?id=<?php echo $trip['id']; ?>&action=delete" class="btn-header btn-delete"
                    onclick="return confirm('⚠️ هل أنت متأكد من حذف هذه الرحلة؟ لا يمكن التراجع.')">
                    <i class="fas fa-trash"></i> حذف
                </a>
            </div>
        </div>

        <?php if ($booking_msg): ?>
            <div class="alert alert-success"><?php echo $booking_msg; ?></div>
        <?php endif; ?>

        <!-- مسار الرحلة -->
        <div class="itinerary-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-route"></i> مسار الرحلة</h3>
                <span class="text-secondary"><?php echo count($itinerary); ?> خدمة مضافة</span>
            </div>

            <?php if (count($itinerary) > 0): ?>
                <div class="itinerary-timeline">
                    <?php $step = 1;
                    foreach ($itinerary as $item):
                        $service = getServiceIcon($item['type']);
                    ?>
                        <div class="timeline-item" data-aos="fade-left">
                            <div class="timeline-icon" style="background: rgba(<?php
                                                                                echo implode(',', array_map(
                                                                                    fn($hex) => hexdec(substr($hex, 1, 2)),
                                                                                    [preg_match('/#([a-f0-9]{3}){1,2}$/i', $service['color']) ? $service['color'] : '#64748b']
                                                                                )) ?? '100,116,139'; ?>, 0.15); color: <?php echo $service['color']; ?>;">
                                <i class="fas fa-<?php echo $service['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <span class="text-secondary" style="font-size:0.8rem;">الخطوة <?php echo $step++; ?></span>
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p class="service-type">
                                    <i class="fas fa-tag"></i> <?php echo $service['label']; ?>
                                </p>
                                <p class="service-price"><?php echo number_format($item['price'], 2); ?> $</p>
                                <div class="service-meta">
                                    <span><i class="fas fa-id-card"></i> ID: #<?php echo $item['id']; ?></span>
                                </div>
                            </div>
                            <div class="timeline-actions">
                                <a href="service_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['type']; ?>"
                                    class="btn-timeline btn-view-service" target="_blank">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                                <a href="service_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['type']; ?>#book"
                                    class="btn-timeline btn-book-now">
                                    <i class="fas fa-calendar-check"></i> احجز
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-itinerary">
                    <i class="fas fa-clipboard-list"></i>
                    <h4>لا توجد خدمات في هذه الرحلة</h4>
                    <p>أضف فنادق، مطاعم، أو أنشطة لمسار رحلتك</p>
                    <a href="plan_trip.php?edit=<?php echo $trip['id']; ?>" class="btn-header btn-edit" style="margin-top:15px;">
                        <i class="fas fa-plus"></i> إضافة خدمات
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ملخص الرحلة -->
        <?php if (count($itinerary) > 0):
            $total_estimated = array_sum(array_column($itinerary, 'price'));
        ?>
            <div class="trip-summary">
                <div class="summary-item">
                    <div class="summary-value"><?php echo count($itinerary); ?></div>
                    <div class="summary-label">خدمات</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo number_format($total_estimated, 0); ?> $</div>
                    <div class="summary-label">إجمالي مقدر</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo number_format($trip['total_budget'], 0); ?> $</div>
                    <div class="summary-label">الميزانية</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" style="color: <?php echo $total_estimated <= $trip['total_budget'] ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?php echo $total_estimated <= $trip['total_budget'] ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="summary-label">الحالة</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- حجز جميع الخدمات -->
        <?php if (count($itinerary) > 0): ?>
            <div class="bulk-booking-card" data-aos="fade-up">
                <h3><i class="fas fa-shopping-cart"></i> حجز جميع الخدمات دفعة واحدة</h3>
                <p class="text-secondary" style="margin-bottom:20px;">
                    احجز كل الخدمات في رحلتك بتاريخ واحد وبخطوة واحدة
                </p>
                <form method="POST" class="booking-form">
                    <div class="form-group">
                        <label>تاريخ الحجز</label>
                        <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo $trip['start_date']; ?>">
                    </div>
                    <button type="submit" name="book_all" class="btn-book-all">
                        <i class="fas fa-check-circle"></i> تأكيد حجز <?php echo count($itinerary); ?> خدمات
                    </button>
                </form>
                <small class="text-secondary" style="display:block;margin-top:15px;">
                    <i class="fas fa-info-circle me-1"></i>
                    سيتم إنشاء حجوزات منفصلة لكل خدمة • يمكن الإلغاء قبل 24 ساعة
                </small>
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

        // تحسين: تعطيل زر الحجز إذا لم يكن هناك خدمات
        document.addEventListener('DOMContentLoaded', function() {
            const bookAllBtn = document.querySelector('.btn-book-all');
            const itemCount = <?php echo count($itinerary); ?>;
            if (bookAllBtn && itemCount === 0) {
                bookAllBtn.disabled = true;
                bookAllBtn.title = 'أضف خدمات أولاً للحجز';
            }
        });
    </script>
</body>

</html>