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
    "SELECT t.id as traveler_id, t.full_name FROM travelers t WHERE t.user_id = $uid"
));

$msg = '';
$msgType = '';

// حفظ الرحلة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_trip'])) {
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $budget = floatval($_POST['budget']);
    $itinerary = mysqli_real_escape_string($conn, $_POST['itinerary_json']);

    $sql = "INSERT INTO trips (traveler_id, destination, start_date, end_date, total_budget, itinerary_json, created_at)
            VALUES ({$traveler['traveler_id']}, '$destination', '$start_date', '$end_date', $budget, '$itinerary', NOW())";

    if (mysqli_query($conn, $sql)) {
        $msg = "✅ تم حفظ الرحلة بنجاح!";
        $msgType = "success";
    } else {
        $msg = "❌ خطأ في الحفظ: " . mysqli_error($conn);
        $msgType = "danger";
    }
}

// جلب الخدمات للإضافة
$hotels = mysqli_query($conn, "SELECT id, name, city, price_per_night as price FROM hotels ORDER BY rating DESC LIMIT 10");
$restaurants = mysqli_query($conn, "SELECT id, name, city, avg_meal_price as price FROM restaurants ORDER BY rating DESC LIMIT 10");
$activities = mysqli_query($conn, "SELECT id, name, city, price FROM activities ORDER BY rating DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>خطط رحلتك | TravelPlanner</title>
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
            max-width: 1400px;
            margin: 70px auto 0;
        }

        .trip-planner {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .planner-form {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--glass-border);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--gold-primary);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: 'Tajawal', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .service-selector {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .service-tab {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: rgba(30, 41, 59, 0.4);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .service-tab.active {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border-color: transparent;
        }

        .service-list {
            max-height: 300px;
            overflow-y: auto;
            display: grid;
            gap: 8px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .service-item:hover {
            background: rgba(212, 175, 55, 0.2);
            transform: translateX(-5px);
        }

        .service-item.added {
            border: 1px solid var(--gold-primary);
            background: rgba(212, 175, 55, 0.1);
        }

        .itinerary-sidebar {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 90px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .itinerary-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 10px;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .itinerary-item i {
            color: var(--gold-primary);
        }

        .remove-btn {
            margin-right: auto;
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .remove-btn:hover {
            transform: scale(1.1);
        }

        .trip-summary {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold-primary);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold-primary);
            border-top: 1px solid var(--glass-border);
            padding-top: 10px;
            margin-top: 10px;
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: var(--blue-dark);
            border: none;
            border-radius: 14px;
            font-family: 'Tajawal', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
        }

        .alert {
            padding: 14px 18px;
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
            .trip-planner {
                grid-template-columns: 1fr;
            }

            .itinerary-sidebar {
                position: static;
                max-height: none;
            }
        }

        .text-secondary {
            color: var(--text-secondary);
        }

        .fw-bold {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right">
            <i class="fas fa-map-marked-alt me-2" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            خطط رحلتك
        </h2>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="trip-planner">
            <div class="planner-form" data-aos="fade-up">
                <form method="POST" id="tripForm">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-info-circle"></i> معلومات الرحلة</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>الوجهة</label>
                                <input type="text" name="destination" required placeholder="مثال: باريس, دبي, طوكيو">
                            </div>
                            <div class="form-group">
                                <label>الميزانية ($)</label>
                                <input type="number" name="budget" id="budgetInput" required min="100" placeholder="5000">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>تاريخ البدء</label>
                                <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>تاريخ الانتهاء</label>
                                <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-plus-circle"></i> إضافة خدمات</h3>
                        <div class="service-selector">
                            <div class="service-tab active" data-type="hotels">
                                <i class="fas fa-hotel"></i> فنادق
                            </div>
                            <div class="service-tab" data-type="restaurants">
                                <i class="fas fa-utensils"></i> مطاعم
                            </div>
                            <div class="service-tab" data-type="activities">
                                <i class="fas fa-umbrella-beach"></i> أنشطة
                            </div>
                        </div>

                        <div id="hotels-list" class="service-list">
                            <?php if (mysqli_num_rows($hotels) > 0): ?>
                                <?php while ($h = mysqli_fetch_assoc($hotels)): ?>
                                    <div class="service-item" onclick="addToItinerary('hotel', <?php echo $h['id']; ?>, '<?php echo htmlspecialchars($h['name']); ?>', <?php echo $h['price']; ?>)">
                                        <span><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($h['name']); ?></span>
                                        <span style="color:var(--gold-primary); font-weight:600;"><?php echo number_format($h['price']); ?> $</span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-secondary" style="text-align:center;padding:20px;">لا توجد فنادق متاحة</div>
                            <?php endif; ?>
                        </div>
                        <div id="restaurants-list" class="service-list" style="display:none;">
                            <?php if (mysqli_num_rows($restaurants) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($restaurants)): ?>
                                    <div class="service-item" onclick="addToItinerary('restaurant', <?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['name']); ?>', <?php echo $r['price']; ?>)">
                                        <span><i class="fas fa-utensils"></i> <?php echo htmlspecialchars($r['name']); ?></span>
                                        <span style="color:var(--gold-primary); font-weight:600;"><?php echo number_format($r['price']); ?> $</span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-secondary" style="text-align:center;padding:20px;">لا توجد مطاعم متاحة</div>
                            <?php endif; ?>
                        </div>
                        <div id="activities-list" class="service-list" style="display:none;">
                            <?php if (mysqli_num_rows($activities) > 0): ?>
                                <?php while ($a = mysqli_fetch_assoc($activities)): ?>
                                    <div class="service-item" onclick="addToItinerary('activity', <?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['name']); ?>', <?php echo $a['price']; ?>)">
                                        <span><i class="fas fa-umbrella-beach"></i> <?php echo htmlspecialchars($a['name']); ?></span>
                                        <span style="color:var(--gold-primary); font-weight:600;"><?php echo number_format($a['price']); ?> $</span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-secondary" style="text-align:center;padding:20px;">لا توجد أنشطة متاحة</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" name="itinerary_json" id="itinerary_json">
                    <button type="submit" name="save_trip" class="btn-save">
                        <i class="fas fa-save"></i> حفظ الرحلة
                    </button>
                </form>
            </div>

            <div class="itinerary-sidebar" data-aos="fade-up" data-aos-delay="100">
                <h3 class="section-title"><i class="fas fa-list"></i> مسار الرحلة</h3>
                <div id="itinerary-items">
                    <p class="text-secondary" style="text-align:center;padding:20px;">اضغط على الخدمات لإضافتها</p>
                </div>

                <div class="trip-summary">
                    <div class="summary-row">
                        <span>عدد الخدمات:</span>
                        <span id="item-count">0</span>
                    </div>
                    <div class="summary-row">
                        <span>الإجمالي المقدر:</span>
                        <span id="total-estimate">0 $</span>
                    </div>
                    <div class="summary-total summary-row">
                        <span>الميزانية:</span>
                        <span id="budget-display">0 $</span>
                    </div>
                    <div id="budget-warning" style="color:#ef4444; font-size:0.85rem; margin-top:8px; display:none;">
                        ⚠️ الإجمالي يتجاوز الميزانية!
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        let itinerary = [];
        let total = 0;

        // تبويب الخدمات
        document.querySelectorAll('.service-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                showServices(type);
            });
        });

        function showServices(type) {
            // إخفاء جميع القوائم
            document.querySelectorAll('.service-list').forEach(el => {
                el.style.display = 'none';
            });
            // إزالة الكلاس active من جميع التبويبات
            document.querySelectorAll('.service-tab').forEach(el => {
                el.classList.remove('active');
            });
            // إظهار القائمة المطلوبة
            const targetList = document.getElementById(type + '-list');
            if (targetList) {
                targetList.style.display = 'grid';
            }
            // إضافة الكلاس active للتبويب النشط
            event.currentTarget.classList.add('active');
        }

        function addToItinerary(type, id, name, price) {
            // التحقق من عدم التكرار
            if (itinerary.find(i => i.id === id && i.type === type)) {
                showToast('هذه الخدمة مضافة بالفعل!', 'warning');
                return;
            }

            itinerary.push({
                type: type,
                id: id,
                name: name,
                price: parseFloat(price)
            });
            total += parseFloat(price);
            renderItinerary();
            showToast('تمت إضافة ' + name + ' بنجاح', 'success');

            // تحديث عرض الميزانية
            updateBudgetDisplay();
        }

        function removeFromItinerary(index) {
            const removedItem = itinerary[index];
            total -= removedItem.price;
            itinerary.splice(index, 1);
            renderItinerary();
            showToast('تمت إزالة ' + removedItem.name, 'info');
            updateBudgetDisplay();
        }

        function renderItinerary() {
            const container = document.getElementById('itinerary-items');
            const icons = {
                hotel: 'fa-hotel',
                restaurant: 'fa-utensils',
                activity: 'fa-umbrella-beach'
            };
            const colors = {
                hotel: '#d4af37',
                restaurant: '#e6c96a',
                activity: '#b89628'
            };

            if (itinerary.length === 0) {
                container.innerHTML = '<p class="text-secondary" style="text-align:center;padding:20px;">اضغط على الخدمات لإضافتها</p>';
            } else {
                container.innerHTML = itinerary.map((item, i) => `
                    <div class="itinerary-item">
                        <i class="fas ${icons[item.type]}" style="color:${colors[item.type]}"></i>
                        <div style="flex:1;">
                            <div style="font-weight:600;">${item.name}</div>
                            <div style="font-size:0.85rem;color:var(--gold-primary);">${item.price} $</div>
                        </div>
                        <button type="button" class="remove-btn" onclick="removeFromItinerary(${i})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('');
            }

            document.getElementById('item-count').textContent = itinerary.length;
            document.getElementById('total-estimate').textContent = total.toFixed(2) + ' $';
            document.getElementById('itinerary_json').value = JSON.stringify(itinerary);
        }

        function updateBudgetDisplay() {
            const budgetInput = document.querySelector('input[name="budget"]');
            const budgetDisplay = document.getElementById('budget-display');
            const budgetWarning = document.getElementById('budget-warning');

            if (budgetInput && budgetInput.value) {
                const budget = parseFloat(budgetInput.value);
                budgetDisplay.textContent = budget.toFixed(2) + ' $';

                if (total > budget) {
                    budgetWarning.style.display = 'block';
                    budgetDisplay.style.color = '#ef4444';
                } else {
                    budgetWarning.style.display = 'none';
                    budgetDisplay.style.color = 'var(--gold-primary)';
                }
            }
        }

        // مراقبة تغيير الميزانية
        document.querySelector('input[name="budget"]')?.addEventListener('input', updateBudgetDisplay);

        // تحديث الميزانية عند تحميل الصفحة
        setTimeout(updateBudgetDisplay, 100);

        // Toast notification
        function showToast(message, type = 'success') {
            // إنشاء عنصر toast مؤقت
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${type === 'success' ? 'rgba(34, 197, 94, 0.9)' : type === 'warning' ? 'rgba(251, 191, 36, 0.9)' : 'rgba(59, 130, 246, 0.9)'};
                color: white;
                padding: 12px 20px;
                border-radius: 12px;
                z-index: 9999;
                font-size: 0.9rem;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i> ${message}`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }
    </script>
</body>

</html>