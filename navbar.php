<?php
// Traveler/navbar.php - النافبار العلوي الفاخر
session_start();

// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);

// بيانات المسافر
$traveler = null;
$unread_notifications = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $traveler = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT t.*, u.username, u.email 
         FROM travelers t 
         JOIN users u ON t.user_id = u.id 
         WHERE t.user_id = $uid"
    ));

    // عد الإشعارات غير المقروءة (حجوزات مؤكدة/معلقة)
    $unread_notifications = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) as c FROM bookings 
         WHERE traveler_id = {$traveler['id']} 
         AND status IN ('pending', 'confirmed') 
         AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    ))['c'] ?? 0;
}
?>
<nav class="luxury-navbar">
    <div class="navbar-container">
        <!-- الشعار -->
        <div class="navbar-brand">
            <a href="index.php" class="brand-link">
                <i class="fas fa-plane-departure brand-icon"></i>
                <span class="brand-text">
                    Travel<span class="brand-highlight">Planner</span>
                </span>
            </a>
        </div>

        <!-- روابط التنقل الرئيسية -->
        <div class="navbar-links" id="navbarLinks">
            <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>
            <a href="search.php" class="nav-link <?php echo $current_page === 'search.php' ? 'active' : ''; ?>">
                <i class="fas fa-search"></i>
                <span>استكشف</span>
            </a>
            <a href="plan_trip.php" class="nav-link <?php echo $current_page === 'plan_trip.php' ? 'active' : ''; ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>خطط رحلتك</span>
            </a>
            <a href="my_bookings.php" class="nav-link <?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>حجوزاتي</span>
                <?php if ($unread_notifications > 0): ?>
                    <span class="nav-badge"><?php echo min($unread_notifications, 9); ?>+</span>
                <?php endif; ?>
            </a>
            <a href="my_itinerary.php" class="nav-link <?php echo $current_page === 'my_itinerary.php' ? 'active' : ''; ?>">
                <i class="fas fa-route"></i>
                <span>مسار رحلتي</span>
            </a>
        </div>

        <!-- أدوات المستخدم -->
        <div class="navbar-actions">
            <!-- زر الإشعارات -->
            <div class="nav-dropdown notification-dropdown">
                <button class="nav-icon-btn" id="notificationBtn" aria-label="الإشعارات">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="icon-badge pulse-animation"><?php echo min($unread_notifications, 9); ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu notification-menu" id="notificationMenu">
                    <div class="dropdown-header">
                        <span>🔔 الإشعارات</span>
                        <a href="#" class="mark-all-read">تحديد الكل كمقروء</a>
                    </div>
                    <div class="dropdown-items">
                        <?php if ($unread_notifications > 0): ?>
                            <div class="notification-item unread">
                                <i class="fas fa-check-circle text-success"></i>
                                <div class="notification-content">
                                    <strong>تم تأكيد حجزك</strong>
                                    <small>فندق النخيل الذهبي • منذ ساعتين</small>
                                </div>
                            </div>
                            <div class="notification-item unread">
                                <i class="fas fa-gift text-warning"></i>
                                <div class="notification-content">
                                    <strong>🎉 +50 نقطة ولاء</strong>
                                    <small>مكافأة على حجزك الأخير</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="notification-item empty">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="mb-0 text-muted">لا توجد إشعارات جديدة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-footer">
                        <a href="notifications.php">عرض جميع الإشعارات</a>
                    </div>
                </div>
            </div>


            <!-- قائمة المستخدم -->
            <div class="nav-dropdown user-dropdown">
                <button class="user-btn" id="userBtn">
                    <div class="user-avatar">
                        <?php echo mb_substr($traveler['full_name'] ?? 'م', 0, 1); ?>
                    </div>
                    <div class="user-info d-none d-lg-block">
                        <span class="user-name"><?php echo htmlspecialchars($traveler['full_name'] ?? 'مسافر'); ?></span>
                        <span class="user-points">
                            <i class="fas fa-coins text-warning"></i>
                            <?php echo number_format($traveler['loyalty_points'] ?? 0); ?>
                        </span>
                    </div>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="dropdown-menu user-menu" id="userMenu">
                    <div class="user-menu-header">
                        <div class="user-avatar large">
                            <?php echo mb_substr($traveler['full_name'] ?? 'م', 0, 1); ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($traveler['full_name'] ?? 'مسافر'); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($traveler['email'] ?? ''); ?></small>
                        </div>
                    </div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> ملفي الشخصي
                    </a>
                    <!-- <a href="my_bookings.php" class="dropdown-item">
                        <i class="fas fa-history"></i> سجل الحجوزات
                    </a>
                    <a href="reviews.php" class="dropdown-item">
                        <i class="fas fa-star"></i> تقييماتي
                    </a> -->

                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>

            <!-- زر القائمة للموبايل -->
            <button class="mobile-toggle" id="mobileToggle" aria-label="القائمة">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- القائمة المنسدلة للموبايل -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php" class="mobile-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> الرئيسية
        </a>
        <a href="search.php" class="mobile-link <?php echo $current_page === 'search.php' ? 'active' : ''; ?>">
            <i class="fas fa-search"></i> استكشف
        </a>
        <a href="plan_trip.php" class="mobile-link <?php echo $current_page === 'plan_trip.php' ? 'active' : ''; ?>">
            <i class="fas fa-map-marked-alt"></i> خطط رحلتك
        </a>
        <a href="my_bookings.php" class="mobile-link <?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i> حجوزاتي
        </a>
        <a href="my_itinerary.php" class="mobile-link <?php echo $current_page === 'my_itinerary.php' ? 'active' : ''; ?>">
            <i class="fas fa-route"></i> مسار رحلتي
        </a>
        <a href="profile.php" class="mobile-link">
            <i class="fas fa-user"></i> ملفي الشخصي
        </a>
        <a href="logout.php" class="mobile-link text-danger">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
        </a>
    </div>
</nav>

<style>
    /* === متغيرات التصميم الفاخر === */
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
        --shadow-gold: 0 8px 32px rgba(212, 175, 55, 0.25);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* === حاوية النافبار === */
    .luxury-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--glass-border);
        box-shadow: var(--shadow-gold);
        padding: 0 20px;
        height: 70px;
        transition: var(--transition);
    }

    .luxury-navbar.scrolled {
        height: 60px;
        background: rgba(15, 23, 42, 0.95);
    }

    .navbar-container {
        max-width: 1400px;
        margin: 0 auto;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    /* === الشعار === */
    .navbar-brand {
        flex-shrink: 0;
    }

    .brand-link {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 12px;
        transition: var(--transition);
    }

    .brand-link:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .brand-icon {
        font-size: 1.5rem;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .brand-text {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-primary);
        font-family: 'Tajawal', sans-serif;
    }

    .brand-highlight {
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* === روابط التنقل === */
    .navbar-links {
        display: flex;
        align-items: center;
        gap: 5px;
        flex: 1;
        justify-content: center;
    }

    .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        padding: 10px 16px;
        text-decoration: none;
        color: var(--text-secondary);
        border-radius: 12px;
        transition: var(--transition);
        position: relative;
        font-size: 0.85rem;
        font-family: 'Tajawal', sans-serif;
    }

    .nav-link i {
        font-size: 1.2rem;
        transition: var(--transition);
    }

    .nav-link:hover {
        color: var(--text-primary);
        background: rgba(212, 175, 55, 0.1);
    }

    .nav-link:hover i {
        transform: translateY(-2px);
        color: var(--gold-primary);
    }

    .nav-link.active {
        color: var(--gold-primary);
        background: rgba(212, 175, 55, 0.15);
    }

    .nav-link.active i {
        color: var(--gold-primary);
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background: linear-gradient(90deg, var(--gold-dark), var(--gold-primary), var(--gold-light));
        border-radius: 2px;
        animation: glowLine 2s ease-in-out infinite;
    }

    @keyframes glowLine {

        0%,
        100% {
            opacity: 0.6;
            width: 20px;
        }

        50% {
            opacity: 1;
            width: 30px;
        }
    }

    .nav-badge {
        position: absolute;
        top: 2px;
        left: 50%;
        transform: translateX(50%);
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: translateX(50%) scale(1);
        }

        50% {
            transform: translateX(50%) scale(1.1);
        }
    }

    /* === أدوات المستخدم === */
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .nav-dropdown {
        position: relative;
    }

    .nav-icon-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-size: 1.2rem;
        padding: 10px;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-icon-btn:hover {
        color: var(--gold-primary);
        background: rgba(212, 175, 55, 0.15);
    }

    .icon-badge {
        position: absolute;
        top: 6px;
        left: 6px;
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        padding: 2px 5px;
        border-radius: 8px;
        min-width: 16px;
        text-align: center;
    }

    .pulse-animation {
        animation: pulseBadge 1.5s infinite;
    }

    @keyframes pulseBadge {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
        }
    }

    /* === زر المستخدم === */
    .user-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: transparent;
        border: 1px solid var(--glass-border);
        padding: 6px 12px 6px 6px;
        border-radius: 50px;
        cursor: pointer;
        transition: var(--transition);
    }

    .user-btn:hover {
        background: rgba(212, 175, 55, 0.1);
        border-color: var(--gold-primary);
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--blue-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .user-avatar.large {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        text-align: right;
    }

    .user-name {
        color: var(--text-primary);
        font-size: 0.9rem;
        font-weight: 600;
        font-family: 'Tajawal', sans-serif;
    }

    .user-points {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .toggle-icon {
        color: var(--text-secondary);
        font-size: 0.7rem;
        transition: transform 0.3s;
    }

    .user-dropdown.open .toggle-icon {
        transform: rotate(180deg);
    }

    /* === القوائم المنسدلة === */
    .dropdown-menu {
        position: absolute;
        top: calc(100% + 10px);
        left: 50%;
        transform: translateX(50%) translateY(-10px);
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 12px;
        min-width: 280px;
        box-shadow: var(--shadow-gold);
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
        z-index: 1001;
    }

    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateX(50%) translateY(0);
    }

    .notification-menu {
        left: auto;
        right: 0;
        transform: translateY(-10px);
        min-width: 320px;
    }

    .notification-menu.show {
        transform: translateY(0);
    }

    .user-menu {
        left: auto;
        right: 0;
        transform: translateY(-10px);
        min-width: 240px;
    }

    .user-menu.show {
        transform: translateY(0);
    }

    .dropdown-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-bottom: 1px solid var(--glass-border);
        margin-bottom: 8px;
    }

    .dropdown-header span {
        color: var(--text-primary);
        font-weight: 600;
        font-family: 'Tajawal', sans-serif;
    }

    .mark-all-read {
        color: var(--gold-primary);
        font-size: 0.8rem;
        text-decoration: none;
        transition: var(--transition);
    }

    .mark-all-read:hover {
        color: var(--gold-light);
    }

    .dropdown-items {
        max-height: 250px;
        overflow-y: auto;
    }

    .dropdown-items::-webkit-scrollbar {
        width: 4px;
    }

    .dropdown-items::-webkit-scrollbar-track {
        background: transparent;
    }

    .dropdown-items::-webkit-scrollbar-thumb {
        background: var(--gold-primary);
        border-radius: 2px;
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        transition: var(--transition);
        cursor: pointer;
    }

    .notification-item:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .notification-item.unread {
        background: rgba(212, 175, 55, 0.08);
        border-right: 3px solid var(--gold-primary);
    }

    .notification-item.empty {
        justify-content: center;
        padding: 20px;
        text-align: center;
    }

    .notification-item i {
        font-size: 1.2rem;
        margin-top: 2px;
    }

    .notification-content {
        flex: 1;
    }

    .notification-content strong {
        display: block;
        color: var(--text-primary);
        font-size: 0.9rem;
        margin-bottom: 2px;
        font-family: 'Tajawal', sans-serif;
    }

    .notification-content small {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .dropdown-footer {
        padding-top: 12px;
        border-top: 1px solid var(--glass-border);
        text-align: center;
    }

    .dropdown-footer a {
        color: var(--gold-primary);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: var(--transition);
    }

    .dropdown-footer a:hover {
        color: var(--gold-light);
    }

    .user-menu-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-bottom: 1px solid var(--glass-border);
        margin-bottom: 8px;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: 10px;
        transition: var(--transition);
        font-size: 0.9rem;
        font-family: 'Tajawal', sans-serif;
    }

    .dropdown-item:hover {
        background: rgba(212, 175, 55, 0.15);
        color: var(--text-primary);
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
        color: var(--gold-primary);
    }

    .dropdown-item.text-danger:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .dropdown-item.text-danger i {
        color: #ef4444;
    }

    .dropdown-divider {
        height: 1px;
        background: var(--glass-border);
        margin: 8px 0;
    }

    /* === زر الموبايل === */
    .mobile-toggle {
        display: none;
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-size: 1.3rem;
        padding: 10px;
        cursor: pointer;
        border-radius: 12px;
        transition: var(--transition);
    }

    .mobile-toggle:hover {
        background: rgba(212, 175, 55, 0.15);
        color: var(--gold-primary);
    }

    /* === قائمة الموبايل === */
    .mobile-menu {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--glass-border);
        padding: 15px 20px;
        display: none;
        flex-direction: column;
        gap: 5px;
        z-index: 999;
        animation: slideDown 0.3s ease;
    }

    .mobile-menu.show {
        display: flex;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .mobile-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: 12px;
        transition: var(--transition);
        font-size: 1rem;
        font-family: 'Tajawal', sans-serif;
    }

    .mobile-link:hover,
    .mobile-link.active {
        background: rgba(212, 175, 55, 0.15);
        color: var(--gold-primary);
    }

    .mobile-link.active {
        border-right: 3px solid var(--gold-primary);
    }

    .mobile-link i {
        width: 20px;
        text-align: center;
    }

    .mobile-link.text-danger {
        color: #ef4444;
    }

    .mobile-link.text-danger:hover {
        background: rgba(239, 68, 68, 0.15);
    }

    /* === التجاوب === */
    @media (max-width: 1024px) {
        .navbar-links {
            display: none;
        }

        .mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: none;
        }

        .user-btn {
            padding: 6px;
        }
    }

    @media (max-width: 480px) {
        .luxury-navbar {
            padding: 0 15px;
        }

        .brand-text {
            font-size: 1.1rem;
        }

        .notification-menu {
            min-width: 280px;
            right: -20px;
        }

        .user-menu {
            min-width: 220px;
            right: -10px;
        }
    }

    /* === تأثيرات إضافية فاخرة === */
    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .luxury-navbar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.1), transparent);
        animation: shimmer 3s infinite;
        pointer-events: none;
    }

    /* === تحسينات التمرير === */
    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // === تأثير التمرير للنافبار ===
        const navbar = document.querySelector('.luxury-navbar');
        let lastScroll = 0;

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });

        // === القوائم المنسدلة ===
        function setupDropdown(triggerBtn, dropdownMenu, closeOnClickOutside = true) {
            if (!triggerBtn || !dropdownMenu) return;

            triggerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = dropdownMenu.classList.contains('show');

                // إغلاق جميع القوائم الأخرى
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                        menu.closest('.nav-dropdown')?.classList.remove('open');
                    }
                });

                // تبديل القائمة الحالية
                dropdownMenu.classList.toggle('show');
                triggerBtn.closest('.nav-dropdown')?.classList.toggle('open');
            });

            if (closeOnClickOutside) {
                document.addEventListener('click', function(e) {
                    if (!triggerBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                        triggerBtn.closest('.nav-dropdown')?.classList.remove('open');
                    }
                });
            }
        }

        // تهيئة القوائم
        setupDropdown(
            document.getElementById('notificationBtn'),
            document.getElementById('notificationMenu')
        );

        setupDropdown(
            document.getElementById('userBtn'),
            document.getElementById('userMenu')
        );

        // === قائمة الموبايل ===
        const mobileToggle = document.getElementById('mobileToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileToggle && mobileMenu) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                mobileMenu.classList.toggle('show');
                const icon = mobileToggle.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });

            // إغلاق القائمة عند النقر على رابط
            mobileMenu.querySelectorAll('.mobile-link').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.remove('show');
                    mobileToggle.querySelector('i').classList.add('fa-bars');
                    mobileToggle.querySelector('i').classList.remove('fa-times');
                });
            });

            // إغلاق القائمة عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!mobileToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.remove('show');
                    mobileToggle.querySelector('i').classList.add('fa-bars');
                    mobileToggle.querySelector('i').classList.remove('fa-times');
                }
            });
        }

        // === تحديد الكل كمقروء ===
        const markAllRead = document.querySelector('.mark-all-read');
        if (markAllRead) {
            markAllRead.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                document.querySelectorAll('.icon-badge, .nav-badge').forEach(badge => {
                    badge.remove();
                });
            });
        }
    });
</script>