<?php
// تحديد الصفحة الحالية لإضافة كلاس active
$current_page = basename($_SERVER['PHP_SELF']);

// التأكد من وجود المتغيرات الضرورية
if (!isset($conn) || !isset($provider)) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'provider') {
        include '../config/db.php';
        $uid = $_SESSION['user_id'];
        $provider = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM providers WHERE user_id = $uid"));
    }
}

// ✅ عد الحجوزات المعلقة للإشعارات
$pending_count = 0;
$pid = $provider['id'] ?? 0;
if ($pid > 0 && isset($conn)) {
    $pending_query = "SELECT COUNT(*) as c FROM bookings b 
        JOIN (
            SELECT id, provider_id FROM hotels 
            UNION ALL 
            SELECT id, provider_id FROM restaurants 
            UNION ALL 
            SELECT id, provider_id FROM activities
        ) s ON b.service_id = s.id 
        WHERE s.provider_id = $pid AND b.status = 'pending'";
    $pending_result = mysqli_query($conn, $pending_query);
    $pending_count = $pending_result ? (mysqli_fetch_assoc($pending_result)['c'] ?? 0) : 0;
}

// ✅ عد التقييمات الجديدة (اختياري)
$reviews_count = 0;
if ($pid > 0 && isset($conn)) {
    $reviews_query = "SELECT COUNT(*) as c FROM reviews r 
        WHERE r.service_id IN (
            SELECT id FROM hotels WHERE provider_id = $pid
            UNION SELECT id FROM restaurants WHERE provider_id = $pid
            UNION SELECT id FROM activities WHERE provider_id = $pid
        )";
    $reviews_result = mysqli_query($conn, $reviews_query);
    $reviews_count = $reviews_result ? (mysqli_fetch_assoc($reviews_result)['c'] ?? 0) : 0;
}
?>
<div class="sidebar bg-dark text-white h-100 position-fixed" style="width: 270px; top: 0; right: 0; z-index: 1000; box-shadow: -5px 0 25px rgba(0,0,0,0.15);">
    <div class="p-4 text-center border-bottom border-secondary" style="background: linear-gradient(135deg, #0f7a71, #1a1a2e);">
        <i class="fas fa-store fa-2x mb-2" style="background: linear-gradient(135deg, #11998e, #38ef7d); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
        <h5 class="fw-bold mb-1">TravelPlanner</h5>
        <small class="text-muted">لوحة مزود الخدمة</small>
    </div>

    <ul class="nav flex-column p-3">
        <!-- لوحة التحكم -->
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-3" style="width: 20px;"></i> لوحة التحكم
            </a>
        </li>

        <!-- إدارة الخدمات -->
        <li class="nav-item mb-2">
            <a href="manage_services.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'manage_services.php' ? 'active' : ''; ?>">
                <i class="fas fa-concierge-bell me-3" style="width: 20px;"></i> خدماتي
            </a>
        </li>

        <!-- الحجوزات مع عداد المعلق -->
        <li class="nav-item mb-2">
            <a href="bookings.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check me-3" style="width: 20px;"></i> الحجوزات
                <?php if ($pending_count > 0): ?>
                    <span class="badge bg-danger rounded-pill me-2"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- ✅ الإشعارات الجديدة -->
        <li class="nav-item mb-2">
            <a href="notifications.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell me-3" style="width: 20px;"></i> الإشعارات
                <?php if ($pending_count > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill me-2"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- التحليلات والذكاء الاصطناعي -->
        <li class="nav-item mb-2">
            <a href="analytics.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-3" style="width: 20px;"></i> التحليلات والـ AI
            </a>
        </li>

        <!-- ✅ التقييمات الجديدة -->
        <li class="nav-item mb-2">
            <a href="reviews.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star me-3" style="width: 20px;"></i> التقييمات
                <?php if ($reviews_count > 0): ?>
                    <span class="badge bg-info text-dark rounded-pill me-2"><?php echo $reviews_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- الملف الشخصي (قائمة منسدلة) -->
        <li class="nav-item mb-2">
            <a href="profile.php" class="nav-link text-white py-3 px-3 rounded-3 hover-effect <?php echo in_array($current_page, ['profile.php', 'manage_profile.php']) ? 'active' : ''; ?>">
                <i class="fas fa-user-cog me-3" style="width: 20px;"></i> الملف الشخصي
                <i class="fas fa-chevron-left me-1 float-start" style="font-size: 0.7rem; margin-top: 5px;"></i>
            </a>
            <!-- قائمة فرعية -->
            <ul class="nav flex-column pe-4" style="display: <?php echo in_array($current_page, ['profile.php', 'manage_profile.php']) ? 'block' : 'none'; ?>;">
                <li class="nav-item">
                    <a href="profile.php" class="nav-link text-white-50 py-2 px-3 small <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-eye me-2" style="width: 16px;"></i> عرض البيانات
                    </a>
                </li>

            </ul>
        </li>

        <!-- فاصل -->
        <li class="nav-item mt-3 pt-3 border-top border-secondary">
            <small class="text-muted px-3">إعدادات سريعة</small>
        </li>



        <!-- تسجيل الخروج -->
        <li class="nav-item mt-2">
            <a href="logout.php" class="nav-link text-danger py-3 px-3 rounded-3 hover-effect">
                <i class="fas fa-sign-out-alt me-3" style="width: 20px;"></i> تسجيل الخروج
            </a>
        </li>
    </ul>

    <!-- حالة الحساب في الأسفل -->
    <div class="p-3 mt-auto border-top border-secondary">
        <div class="d-flex align-items-center justify-content-between">
            <span class="badge <?php echo ($provider['is_verified'] ?? false) ? 'bg-success' : 'bg-warning'; ?> px-3 py-2">
                <?php echo ($provider['is_verified'] ?? false) ? '✅ معتمد' : '⏳ قيد المراجعة'; ?>
            </span>
            <small class="text-muted"><?php echo date('Y-m-d'); ?></small>
        </div>
        <small class="text-muted d-block mt-2 text-truncate" title="<?php echo htmlspecialchars($provider['business_name'] ?? ''); ?>">
            <?php echo htmlspecialchars($provider['business_name'] ?? 'مزود خدمة'); ?>
        </small>
    </div>
</div>

<style>
    .hover-effect {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .hover-effect::before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(135deg, #11998e, #38ef7d);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .hover-effect:hover {
        background: rgba(17, 153, 142, 0.15);
        transform: translateX(-5px);
    }

    .hover-effect:hover::before {
        opacity: 1;
    }

    .hover-effect.active {
        background: linear-gradient(135deg, rgba(17, 153, 142, 0.3), rgba(56, 239, 125, 0.3));
        border-right: 4px solid #11998e;
        font-weight: 600;
    }

    .hover-effect.active i {
        color: #11998e !important;
    }

    /* القائمة الفرعية */
    .nav.flex-column .nav-item .nav-link.small {
        padding: 8px 12px !important;
        font-size: 0.9rem;
    }

    .nav.flex-column .nav-item .nav-link.small:hover,
    .nav.flex-column .nav-item .nav-link.small.active {
        background: rgba(17, 153, 142, 0.2) !important;
        border-radius: 10px;
        color: white !important;
    }

    /* تأثيرات الشارات */
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }
</style>

<script>
    // ✅ تحسين: إظهار/إخفاء القائمة الفرعية للملف الشخصي عند النقر
    document.addEventListener('DOMContentLoaded', function() {
        const profileLink = document.querySelector('a[href="profile.php"]');
        const submenu = profileLink?.parentElement.querySelector('ul.nav.flex-column');

        if (profileLink && submenu) {
            profileLink.addEventListener('click', function(e) {
                e.preventDefault();
                const isHidden = submenu.style.display === 'none' || !submenu.style.display;

                // إغلاق جميع القوائم الأخرى أولاً
                document.querySelectorAll('.nav.flex-column .nav-item ul').forEach(ul => {
                    if (ul !== submenu) ul.style.display = 'none';
                });

                // تبديل حالة القائمة الحالية
                submenu.style.display = isHidden ? 'block' : 'none';

                // تدوير الأيقونة
                const icon = profileLink.querySelector('.fa-chevron-left');
                if (icon) {
                    icon.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0)';
                    icon.style.transition = 'transform 0.2s';
                }
            });
        }
    });
</script>