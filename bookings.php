<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$provider_query = mysqli_query($conn, "SELECT id FROM providers WHERE user_id = $uid");
$provider = mysqli_fetch_assoc($provider_query);
$pid = $provider['id'] ?? 0;

$msg = '';
$msgType = '';

// === تحديث حالة الحجز ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    // التحقق من أن الحجز يخص هذا المزود
    $check_query = "SELECT service_id FROM bookings WHERE id=$booking_id AND service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
    )";
    $check = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check) > 0) {
        $update_query = "UPDATE bookings SET status='$new_status' WHERE id=$booking_id";
        if (mysqli_query($conn, $update_query)) {
            $msg = "✅ تم تحديث حالة الحجز";
            $msgType = "success";
        } else {
            $msg = "❌ خطأ في التحديث: " . mysqli_error($conn);
            $msgType = "danger";
        }
    } else {
        $msg = "❌ غير مصرح بتعديل هذا الحجز";
        $msgType = "danger";
    }
}

// === فلترة الحجوزات ===
$filter_status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$status_filter = $filter_status !== 'all' ? "AND b.status = '$filter_status'" : '';
$search_filter = $search ? "AND (t.full_name LIKE '%$search%' OR t.phone LIKE '%$search%')" : '';

// ✅ استعلام بدون أي أعمدة غير موجودة
$sql = "SELECT b.*, t.full_name as traveler_name, t.phone, 
        u.email as traveler_email,
        CASE 
            WHEN h.id IS NOT NULL THEN h.name
            WHEN r.id IS NOT NULL THEN r.name
            WHEN a.id IS NOT NULL THEN a.name
        END as service_name,
        b.service_type
 FROM bookings b
 JOIN travelers t ON b.traveler_id = t.id
 JOIN users u ON t.user_id = u.id
 LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
 LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
 LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
 WHERE b.service_id IN (
    SELECT id FROM hotels WHERE provider_id=$pid
    UNION SELECT id FROM restaurants WHERE provider_id=$pid
    UNION SELECT id FROM activities WHERE provider_id=$pid
 )
 $status_filter $search_filter
 ORDER BY b.booking_date DESC";

$bookings = mysqli_query($conn, $sql);

if (!$bookings) {
    die("خطأ في الاستعلام: " . mysqli_error($conn));
}

// إحصائيات الحالة
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed
 FROM bookings 
 WHERE service_id IN (
    SELECT id FROM hotels WHERE provider_id=$pid
    UNION SELECT id FROM restaurants WHERE provider_id=$pid
    UNION SELECT id FROM activities WHERE provider_id=$pid
 )";

$status_counts = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الحجوزات | مقدم الخدمة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Tajawal', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
        }

        .status-tab {
            padding: 10px 25px;
            border-radius: 50px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .status-tab:hover,
        .status-tab.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
        }

        .booking-card:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
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

        .btn-custom {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-custom:hover {
            color: white;
            transform: translateY(-2px);
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
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-right">
            <h2 class="fw-bold">
                <i class="fas fa-calendar-check me-2" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                إدارة الحجوزات
            </h2>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- فلاتر الحالة -->
        <div class="card p-3 mb-4" data-aos="fade-up">
            <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?status=all" class="status-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                        الكل (<?php echo $status_counts['total'] ?? 0; ?>)
                    </a>
                    <a href="?status=pending" class="status-tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock me-1"></i>معلق (<?php echo $status_counts['pending'] ?? 0; ?>)
                    </a>
                    <a href="?status=confirmed" class="status-tab <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle me-1"></i>مؤكد (<?php echo $status_counts['confirmed'] ?? 0; ?>)
                    </a>
                    <a href="?status=completed" class="status-tab <?php echo $filter_status === 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-flag-checkered me-1"></i>مكتمل (<?php echo $status_counts['completed'] ?? 0; ?>)
                    </a>
                    <a href="?status=cancelled" class="status-tab <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle me-1"></i>ملغي (<?php echo $status_counts['cancelled'] ?? 0; ?>)
                    </a>
                </div>

                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="<?php echo $filter_status; ?>">
                    <input type="text" name="search" class="form-control rounded-pill"
                        placeholder="بحث باسم المسافر..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary rounded-pill">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- قائمة الحجوزات -->
        <div data-aos="fade-up">
            <?php if (mysqli_num_rows($bookings) > 0): ?>
                <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                    <div class="booking-card">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stat-icon" style="width:50px;height:50px;border-radius:12px;background:rgba(17,153,142,0.1);color:#11998e;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-<?php echo $b['service_type'] == 'hotel' ? 'hotel' : ($b['service_type'] == 'restaurant' ? 'utensils' : 'umbrella-beach'); ?> fa-lg"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($b['service_name'] ?? 'خدمة #' . $b['service_id']); ?></div>
                                        <small class="text-muted"><?php echo $b['service_type']; ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="fw-bold"><?php echo htmlspecialchars($b['traveler_name']); ?></div>
                                <small class="text-muted">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($b['phone']); ?>
                                </small>
                                <?php if (isset($b['traveler_email']) && !empty($b['traveler_email'])): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($b['traveler_email']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-2">
                                <div class="fw-bold text-success"><?php echo number_format($b['total_price'], 2); ?> $</div>
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($b['booking_date'])); ?></small>
                            </div>

                            <div class="col-md-2">
                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                    <?php
                                    $status_ar = ['pending' => 'معلق', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'];
                                    echo $status_ar[$b['status']] ?? $b['status'];
                                    ?>
                                </span>
                            </div>

                            <div class="col-md-2 text-start">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()" style="width: auto;">
                                            <option value="" disabled selected>تغيير الحالة</option>
                                            <option value="confirmed">تأكيد</option>
                                            <option value="cancelled">إلغاء</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="update_status" value="1">
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill">
                                            <i class="fas fa-check me-1"></i>إكمال
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted">لا توجد حجوزات</h5>
                </div>
            <?php endif; ?>
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