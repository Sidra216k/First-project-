<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// جدول الإعدادات (أنشئه إذا لم يكن موجوداً)
/*
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- بيانات افتراضية
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'TravelPlanner', 'string', 'اسم الموقع'),
('commission_rate', '10', 'number', 'نسبة عمولة المنصة (%)'),
('min_budget', '500', 'number', 'أدنى ميزانية مسموحة للرحلة'),
('max_budget', '50000', 'number', 'أقصى ميزانية مسموحة للرحلة'),
('ai_enabled', 'true', 'boolean', 'تفعيل نظام الذكاء الاصطناعي'),
('maintenance_mode', 'false', 'boolean', 'وضع الصيانة');
*/

$msg = '';
$msgType = '';

// حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key', '$value') 
            ON DUPLICATE KEY UPDATE setting_value='$value'");
    }
    $msg = "✅ تم حفظ الإعدادات بنجاح";
    $msgType = "success";
}

// جلب الإعدادات
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM system_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إعدادات النظام | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .setting-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info {
            flex: 1;
        }

        .setting-info h6 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .setting-info small {
            color: #888;
        }

        .setting-control {
            min-width: 200px;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 10px 15px;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            border-radius: 28px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        input:checked+.toggle-slider {
            background: var(--gradient);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(22px);
        }

        .btn-custom {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .section-title {
            font-weight: 700;
            margin: 30px 0 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right"><i class="fas fa-cogs me-2" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>إعدادات النظام</h2>

        <?php if ($msg) echo "<div class='alert alert-$msgType alert-dismissible fade show'>$msg <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"; ?>

        <form method="POST">
            <!-- عام -->
            <h5 class="section-title" data-aos="fade-right"><i class="fas fa-cog"></i>إعدادات عامة</h5>
            <div class="card" data-aos="fade-up">
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>اسم الموقع</h6>
                        <small>يظهر في شريط العنوان ورسائل النظام</small>
                    </div>
                    <div class="setting-control">
                        <input type="text" name="settings[site_name]" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'TravelPlanner'); ?>">
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>وضع الصيانة</h6>
                        <small>عند التفعيل، لن يتمكن إلا الأدمن من الدخول</small>
                    </div>
                    <div class="setting-control text-end">
                        <label class="toggle-switch">
                            <input type="checkbox" name="settings[maintenance_mode]" value="true" <?php echo ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- مالي -->
            <h5 class="section-title" data-aos="fade-right"><i class="fas fa-wallet"></i>إعدادات مالية</h5>
            <div class="card" data-aos="fade-up">
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>نسبة عمولة المنصة (%)</h6>
                        <small>نسبة الخصم من كل حجز لمزود الخدمة</small>
                    </div>
                    <div class="setting-control">
                        <input type="number" name="settings[commission_rate]" class="form-control" min="0" max="50" value="<?php echo htmlspecialchars($settings['commission_rate'] ?? '10'); ?>">
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>أدنى ميزانية للرحلة</h6>
                        <small>بال$ </small>
                    </div>
                    <div class="setting-control">
                        <input type="number" name="settings[min_budget]" class="form-control" value="<?php echo htmlspecialchars($settings['min_budget'] ?? '500'); ?>">
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>أقصى ميزانية للرحلة</h6>
                        <small>بال$ </small>
                    </div>
                    <div class="setting-control">
                        <input type="number" name="settings[max_budget]" class="form-control" value="<?php echo htmlspecialchars($settings['max_budget'] ?? '50000'); ?>">
                    </div>
                </div>
            </div>

            <!-- ذكاء اصطناعي -->
            <h5 class="section-title" data-aos="fade-right"><i class="fas fa-brain"></i>إعدادات الذكاء الاصطناعي</h5>
            <div class="card" data-aos="fade-up">
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>تفعيل نظام الذكاء الاصطناعي</h6>
                        <small>التحكم في تشغيل/إيقاف ميزات الـ AI</small>
                    </div>
                    <div class="setting-control text-end">
                        <label class="toggle-switch">
                            <input type="checkbox" name="settings[ai_enabled]" value="true" <?php echo ($settings['ai_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- حفظ -->
            <div class="text-end mt-4" data-aos="fade-up">
                <button type="submit" name="save_settings" class="btn btn-custom">
                    <i class="fas fa-save me-2"></i> حفظ جميع الإعدادات
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true
        });
        // تحويل القيم للـ checkbox
        document.querySelectorAll('input[type="checkbox"][name^="settings["]').forEach(cb => {
            cb.addEventListener('change', function() {
                this.value = this.checked ? 'true' : 'false';
            });
        });
    </script>
</body>

</html>