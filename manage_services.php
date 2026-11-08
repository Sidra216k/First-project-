<?php
session_start();
include '../config/db.php';

// التحقق من صلاحيات المزود
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

// === إعدادات رفع الصور ===
define('UPLOAD_BASE', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// === ضمان إنشاء مجلدات الرفع بأسماء صحيحة ===
$upload_folders = ['hotels', 'restaurants', 'activities'];
foreach ($upload_folders as $folder) {
    $path = UPLOAD_BASE . $folder;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

$msg = '';
$msgType = '';

$uid = $_SESSION['user_id'];
$provider = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM providers WHERE user_id = $uid"));
$pid = $provider['id'] ?? 0;

// === دوال مساعدة ===

// دالة لتحديد اسم مجلد الرفع بناءً على نوع الخدمة
function getUploadFolder($serviceType)
{
    $folders = [
        'hotel' => 'hotels',
        'restaurant' => 'restaurants',
        'activity' => 'activities'  // ✅ التصحيح الرئيسي
    ];
    return $folders[$serviceType] ?? 'uploads';
}

// دالة لرفع الصورة بأمان
function uploadImage($fileInput, $serviceType)
{
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف يتجاوز الحد المسموح في الخادم',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف يتجاوز الحد المسموح في النموذج',
            UPLOAD_ERR_PARTIAL => 'لم يتم رفع الملف بالكامل',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
        ];
        $code = $_FILES[$fileInput]['error'] ?? UPLOAD_ERR_NO_FILE;
        return ['success' => false, 'error' => $errors[$code] ?? 'خطأ غير معروف في الرفع'];
    }

    $file = $_FILES[$fileInput];

    // التحقق من الحجم
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'حجم الصورة يتجاوز 5 ميجابايت'];
    }

    // التحقق من النوع باستخدام finfo (أكثر أماناً من الامتداد)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'نوع الملف غير مدعوم (فقط JPG, PNG, GIF, WebP)'];
    }

    // التحقق من الامتداد كطبقة حماية إضافية
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'امتداد الملف غير مسموح'];
    }

    // تحديد المجلد الصحيح
    $folder = getUploadFolder($serviceType);

    // توليد اسم فريد وآمن للملف
    $newFilename = $serviceType . '_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
    $destination = UPLOAD_BASE . $folder . '/' . $newFilename;

    // التأكد النهائي من وجود المجلد
    $destDir = dirname($destination);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // إرجاع المسار النسبي للحفظ في قاعدة البيانات
        return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $newFilename];
    }

    return ['success' => false, 'error' => 'فشل في حفظ الصورة على الخادم'];
}

// دالة لحذف صورة قديمة من الخادم
function deleteImage($filepath)
{
    if (!$filepath) return false;
    // إزالة ../ من البداية إذا وجدت
    $cleanPath = str_replace('../', '', $filepath);
    $fullPath = UPLOAD_BASE . $cleanPath;

    if (file_exists($fullPath) && is_file($fullPath)) {
        // حماية: التأكد أن الملف داخل مجلد uploads
        if (strpos(realpath($fullPath), realpath(UPLOAD_BASE)) === 0) {
            return unlink($fullPath);
        }
    }
    return false;
}

// === معالجة الطلبات ===

// ➕ إضافة خدمة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $price = floatval($_POST['price']);
    $rating = floatval($_POST['rating'] ?? 0);
    $desc = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage('image', $type); // ✅ تصحيح: نمرر النوع كما هو
        if ($upload['success']) {
            $image_url = $upload['path'];
        } else {
            $msg = "❌ " . $upload['error'];
            $msgType = "danger";
        }
    }

    if (empty($msg) && $pid > 0) {
        $tables = [
            'hotel' => [
                'table' => 'hotels',
                'extra_cols' => "category, price_per_night",
                'extra_vals' => "'" . (mysqli_real_escape_string($conn, $_POST['category'] ?? 'medium')) . "', $price"
            ],
            'restaurant' => [
                'table' => 'restaurants',
                'extra_cols' => "cuisine_type, avg_meal_price",
                'extra_vals' => "'" . (mysqli_real_escape_string($conn, $_POST['cuisine'] ?? 'عربي')) . "', $price"
            ],
            'activity' => [
                'table' => 'activities',
                'extra_cols' => "duration_hours, price",
                'extra_vals' => intval($_POST['duration'] ?? 3) . ", $price"
            ]
        ];

        if (isset($tables[$type])) {
            $t = $tables[$type];
            $sql = "INSERT INTO {$t['table']} (provider_id, name, city, {$t['extra_cols']}, rating, description, image_url) 
                    VALUES ($pid, '$name', '$city', {$t['extra_vals']}, $rating, '$desc', '$image_url')";

            if (mysqli_query($conn, $sql)) {
                $msg = "✅ تمت إضافة الخدمة بنجاح";
                $msgType = "success";
            } else {
                $msg = "❌ خطأ في الحفظ: " . mysqli_error($conn);
                $msgType = "danger";
            }
        } else {
            $msg = "❌ نوع الخدمة غير معروف";
            $msgType = "danger";
        }
    }
}

// ✏️ تحديث خدمة موجودة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $id = intval($_POST['service_id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $price = floatval($_POST['price']);
    $rating = floatval($_POST['rating'] ?? 0);

    $tables = ['hotel' => 'hotels', 'restaurant' => 'restaurants', 'activity' => 'activities'];
    $table = $tables[$type] ?? null;

    if ($table && $pid > 0) {
        // جلب البيانات الحالية بما فيها الصورة
        $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_url FROM $table WHERE id=$id AND provider_id=$pid"));
        $image_url = $current['image_url'] ?? '';

        // إذا تم رفع صورة جديدة
        if (!empty($_FILES['image']['name'])) {
            // حذف الصورة القديمة أولاً
            if ($image_url) {
                deleteImage($image_url);
            }

            // رفع الصورة الجديدة
            $upload = uploadImage('image', $type);
            if ($upload['success']) {
                $image_url = $upload['path'];
            } else {
                $msg = "❌ " . $upload['error'];
                $msgType = "danger";
            }
        }

        if (empty($msg)) {
            $extraUpdates = [
                'hotel' => "category='" . mysqli_real_escape_string($conn, $_POST['category'] ?? 'medium') . "', price_per_night=$price",
                'restaurant' => "cuisine_type='" . mysqli_real_escape_string($conn, $_POST['cuisine'] ?? 'عربي') . "', avg_meal_price=$price",
                'activity' => "duration_hours=" . intval($_POST['duration'] ?? 3) . ", price=$price"
            ][$type];

            $sql = "UPDATE $table SET 
                    name='$name', 
                    city='$city', 
                    $extraUpdates, 
                    rating=$rating, 
                    image_url='$image_url' 
                    WHERE id=$id AND provider_id=$pid";

            if (mysqli_query($conn, $sql)) {
                $msg = "✅ تم تحديث الخدمة بنجاح";
                $msgType = "success";
            } else {
                $msg = "❌ خطأ في التحديث: " . mysqli_error($conn);
                $msgType = "danger";
            }
        }
    }
}

// 🗑️ حذف خدمة
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $id = intval($_GET['delete']);
    $type = $_GET['type'];
    $tables = ['hotel' => 'hotels', 'restaurant' => 'restaurants', 'activity' => 'activities'];
    $table = $tables[$type] ?? null;

    if ($table && $pid > 0) {
        // جلب الصورة لحذفها من الخادم
        $service = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_url FROM $table WHERE id=$id AND provider_id=$pid"));
        if ($service && $service['image_url']) {
            deleteImage($service['image_url']);
        }

        mysqli_query($conn, "DELETE FROM $table WHERE id=$id AND provider_id=$pid");
    }
    header("Location: manage_services.php?msg=deleted");
    exit();
}

// 🔍 جلب جميع خدمات المزود للعرض
$services = [];
$service_tables = ['hotels' => 'hotel', 'restaurants' => 'restaurant', 'activities' => 'activity'];
foreach ($service_tables as $tbl => $type) {
    $res = mysqli_query($conn, "SELECT *, '$type' as _type FROM $tbl WHERE provider_id=$pid ORDER BY id DESC");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $services[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخدمات | مزود الخدمة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Tajawal', 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
            transition: margin-right 0.3s;
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

        /* بطاقة الخدمة */
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateX(20px);
        }

        .service-card.show {
            opacity: 1;
            transform: translateX(0);
        }

        .service-card:hover {
            transform: translateX(-5px);
            box-shadow: 0 15px 35px rgba(17, 153, 142, 0.2);
        }

        .service-image {
            width: 70px;
            height: 55px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #eee;
            transition: border-color 0.3s;
        }

        .service-card:hover .service-image {
            border-color: #11998e;
        }

        .service-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, #11998e20, #38ef7d20);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #11998e;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .badge-type {
            background: var(--primary-gradient);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* الأزرار */
        .btn-custom {
            background: var(--primary-gradient);
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

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.4);
            color: white;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .btn-outline-primary {
            color: #11998e;
            border-color: #11998e;
        }

        .btn-outline-primary:hover {
            background: #11998e;
            color: white;
        }

        .btn-outline-danger {
            color: #f5576c;
            border-color: #f5576c;
        }

        .btn-outline-danger:hover {
            background: #f5576c;
            color: white;
        }

        /* منطقة رفع الصور */
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: #11998e;
            background: rgba(17, 153, 142, 0.05);
        }

        .upload-area i {
            font-size: 2.5rem;
            color: #11998e;
            margin-bottom: 10px;
        }

        .upload-area input[type="file"] {
            display: none;
        }

        .upload-area small {
            color: #888;
            font-size: 0.85rem;
        }

        /* معاينة الصورة */
        .preview-container {
            margin-top: 15px;
            display: none;
            position: relative;
        }

        .preview-container.show {
            display: block;
            animation: fadeIn 0.3s;
        }

        .preview-image {
            max-width: 100%;
            max-height: 180px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }

        .preview-remove {
            position: absolute;
            top: -8px;
            left: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f5576c;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            z-index: 2;
        }

        .preview-remove:hover {
            background: #d63031;
        }

        /* الصورة الحالية في التعديل */
        .current-image-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
        }

        .current-image-box img {
            max-width: 100%;
            max-height: 120px;
            border-radius: 8px;
            object-fit: cover;
        }

        /* الحقول والنماذج */
        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.15);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        /* المودال */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            background: #fafafa;
            border-radius: 20px 20px 0 0;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 20px 25px;
            background: #fafafa;
            border-radius: 0 0 20px 20px;
        }

        /* شريط التقدم */
        .upload-progress {
            height: 6px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
            display: none;
        }

        .upload-progress.show {
            display: block;
        }

        .upload-progress-bar {
            height: 100%;
            background: var(--primary-gradient);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        /* رسائل الخطأ والنجاح */
        .form-error {
            color: #f5576c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .form-error.show {
            display: block;
        }

        /* فلاتر النوع */
        .type-filter {
            padding: 8px 20px;
            border-radius: 50px;
            border: 2px solid #11998e;
            color: #11998e;
            background: white;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }

        .type-filter.active,
        .type-filter:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        /* رسائل التنبيه */
        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        /* التجاوب */
        @media (max-width: 992px) {
            .main-content {
                margin-right: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .service-card {
                flex-wrap: wrap;
                text-align: center;
            }

            .service-card .d-flex.gap-1 {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }

            .service-image {
                width: 60px;
                height: 45px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-right">
            <h2 class="fw-bold mb-0">
                <i class="fas fa-concierge-bell me-2" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                إدارة خدماتي
            </h2>
            <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>إضافة خدمة
            </button>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ✅ تم حذف الخدمة بنجاح
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- فلاتر نوع الخدمة -->
        <div class="card p-3 mb-4" data-aos="fade-up">
            <div class="d-flex gap-2 flex-wrap">
                <button class="type-filter active" onclick="filterServices('all')">الكل</button>
                <button class="type-filter" onclick="filterServices('hotel')">🏨 فنادق</button>
                <button class="type-filter" onclick="filterServices('restaurant')">🍽️ مطاعم</button>
                <button class="type-filter" onclick="filterServices('activity')">🎯 أنشطة</button>
            </div>
        </div>

        <!-- قائمة الخدمات -->
        <div class="card p-4" data-aos="fade-up" data-aos-delay="100">
            <?php if (empty($services)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                    <p class="mb-3">لا توجد خدمات مضافة بعد. ابدأ بإضافة خدمتك الأولى!</p>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i>إضافة خدمة الآن
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($services as $s):
                    $icon = ['hotel' => 'fa-hotel', 'restaurant' => 'fa-utensils', 'activity' => 'fa-umbrella-beach'][$s['_type']] ?? 'fa-store';

                    // تحديد السعر المناسب حسب نوع الخدمة
                    $price = $s['price'] ?? $s['price_per_night'] ?? $s['avg_meal_price'] ?? 0;

                    // التحقق من وجود الصورة
                    $imageSrc = '';
                    if (!empty($s['image_url']) && file_exists(UPLOAD_BASE . str_replace('uploads/', '', $s['image_url']))) {
                        $imageSrc = '../' . htmlspecialchars($s['image_url']);
                    }
                ?>
                    <div class="service-card" data-type="<?php echo htmlspecialchars($s['_type']); ?>">
                        <?php if ($imageSrc): ?>
                            <img src="<?php echo $imageSrc; ?>" class="service-image" alt="<?php echo htmlspecialchars($s['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="service-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                        <?php endif; ?>

                        <div class="flex-grow-1 text-start">
                            <strong class="d-block fs-6"><?php echo htmlspecialchars($s['name']); ?></strong>
                            <small class="text-muted">
                                <i class="fas fa-location-dot me-1"></i><?php echo htmlspecialchars($s['city']); ?>
                                • <i class="fas fa-star text-warning me-1"></i><?php echo number_format($s['rating'] ?? 0, 1); ?>/5
                            </small>
                        </div>

                        <div class="text-end">
                            <div class="fw-bold text-success">
                                <?php echo number_format($price, 2); ?> $
                            </div>
                            <span class="badge-type"><?php
                                                        echo ['hotel' => 'فندق', 'restaurant' => 'مطعم', 'activity' => 'نشاط'][$s['_type']] ?? $s['_type'];
                                                        ?></span>
                        </div>

                        <div class="d-flex gap-1">
                            <button class="btn-action btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                onclick='editService(<?php echo json_encode($s, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS); ?>)'
                                title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $s['id']; ?>&type=<?php echo $s['_type']; ?>"
                                class="btn-action btn-outline-danger"
                                onclick="return confirm('⚠️ هل أنت متأكد من حذف هذه الخدمة؟ لا يمكن التراجع عن هذا الإجراء.')"
                                title="حذف">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: إضافة خدمة جديدة -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-plus-circle me-2 text-success"></i>إضافة خدمة جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="addForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- نوع الخدمة -->
                            <div class="col-md-4">
                                <label class="form-label">نوع الخدمة <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" id="add_type" required onchange="toggleFields()">
                                    <option value="hotel">🏨 فندق</option>
                                    <option value="restaurant">🍽️ مطعم</option>
                                    <option value="activity">🎯 نشاط سياحي</option>
                                </select>
                            </div>

                            <!-- اسم الخدمة -->
                            <div class="col-md-8">
                                <label class="form-label">اسم الخدمة <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="مثال: فندق النخيل الذهبي">
                            </div>

                            <!-- المدينة -->
                            <div class="col-md-6">
                                <label class="form-label">المدينة <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" required placeholder="الرياض، جدة، الدمام...">
                            </div>

                            <!-- السعر -->
                            <div class="col-md-3">
                                <label class="form-label">السعر ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                            </div>

                            <!-- التقييم -->
                            <div class="col-md-3">
                                <label class="form-label">التقييم</label>
                                <input type="number" step="0.1" min="0" max="5" name="rating" class="form-control" value="0.0">
                            </div>

                            <!-- الحقول الخاصة بكل نوع -->
                            <div class="col-md-6 hotel-field">
                                <label class="form-label">فئة الفندق</label>
                                <select name="category" class="form-select">
                                    <option value="economic">💰 اقتصادي</option>
                                    <option value="medium" selected>⭐ متوسط</option>
                                    <option value="luxury">👑 فاخر</option>
                                </select>
                            </div>

                            <div class="col-md-6 restaurant-field" style="display:none;">
                                <label class="form-label">نوع المطبخ</label>
                                <select name="cuisine" class="form-select">
                                    <option value="عربي">🥙 عربي</option>
                                    <option value="إيطالي">🍝 إيطالي</option>
                                    <option value="آسيوي">🍜 آسيوي</option>
                                    <option value="عالمي">🌍 عالمي</option>
                                    <option value="آخر">✨ آخر</option>
                                </select>
                            </div>

                            <div class="col-md-6 activity-field" style="display:none;">
                                <label class="form-label">المدة (ساعات)</label>
                                <input type="number" name="duration" class="form-control" value="3" min="1" max="24">
                            </div>

                            <!-- رفع الصورة -->
                            <div class="col-12">
                                <label class="form-label">صورة الخدمة</label>
                                <div class="upload-area" id="uploadAreaAdd" onclick="document.getElementById('imageAdd').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-1 fw-bold">اضغط لاختيار صورة أو اسحبها هنا</p>
                                    <small>JPG, PNG, GIF, WebP - أقصى حجم 5 ميجابايت</small>
                                    <input type="file" name="image" id="imageAdd" accept="image/*">
                                </div>
                                <div class="upload-progress" id="progressAdd">
                                    <div class="upload-progress-bar" id="progressBarAdd"></div>
                                </div>
                                <div class="form-error" id="errorAdd"></div>

                                <!-- معاينة الصورة -->
                                <div class="preview-container position-relative" id="previewAdd">
                                    <img src="" class="preview-image" id="previewImgAdd" alt="معاينة">
                                    <button type="button" class="preview-remove" onclick="removePreview('Add')" title="إزالة">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- الوصف -->
                            <div class="col-12">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="وصف مختصر وجذاب عن الخدمة..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_service" class="btn btn-custom rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>حفظ الخدمة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: تعديل خدمة -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-edit me-2 text-warning"></i>تعديل الخدمة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="edit_id">
                        <input type="hidden" name="type" id="edit_type">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نوع الخدمة</label>
                                <input type="text" id="edit_type_display" class="form-control" disabled>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">اسم الخدمة <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المدينة <span class="text-danger">*</span></label>
                                <input type="text" name="city" id="edit_city" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">السعر ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="price" id="edit_price" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">التقييم</label>
                                <input type="number" step="0.1" min="0" max="5" name="rating" id="edit_rating" class="form-control">
                            </div>

                            <!-- الصورة: الحالية + رفع جديدة -->
                            <div class="col-12">
                                <label class="form-label">صورة الخدمة</label>

                                <!-- الصورة الحالية -->
                                <div id="currentImageContainer" class="current-image-box">
                                    <small class="text-muted d-block mb-2">الصورة الحالية:</small>
                                    <img src="" id="edit_current_preview" alt="الصورة الحالية" style="display:none;">
                                    <small class="text-muted d-block" id="noCurrentImage">لا توجد صورة حالية</small>
                                </div>

                                <!-- منطقة رفع صورة جديدة -->
                                <div class="upload-area" id="uploadAreaEdit" onclick="document.getElementById('imageEdit').click()">
                                    <i class="fas fa-sync-alt"></i>
                                    <p class="mb-1 fw-bold">تغيير الصورة (اختياري)</p>
                                    <small>اتركها فارغة للإبقاء على الصورة الحالية</small>
                                    <input type="file" name="image" id="imageEdit" accept="image/*">
                                </div>
                                <div class="upload-progress" id="progressEdit">
                                    <div class="upload-progress-bar" id="progressBarEdit"></div>
                                </div>
                                <div class="form-error" id="errorEdit"></div>

                                <!-- معاينة الصورة الجديدة -->
                                <div class="preview-container position-relative" id="previewEdit">
                                    <img src="" class="preview-image" id="previewImgEdit" alt="معاينة جديدة">
                                    <button type="button" class="preview-remove" onclick="removePreview('Edit')" title="إزالة">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_service" class="btn btn-custom rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>تحديث الخدمة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // === تهيئة مكتبة AOS للأنيميشن ===
        AOS.init({
            once: true,
            duration: 600,
            offset: 50
        });

        // === إظهار بطاقات الخدمات بتأثير متتابع ===
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.service-card').forEach((card, index) => {
                setTimeout(() => card.classList.add('show'), index * 100);
            });
        });

        // === فلترة الخدمات حسب النوع ===
        function filterServices(type) {
            // تحديث حالة الأزرار
            document.querySelectorAll('.type-filter').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // فلترة البطاقات
            document.querySelectorAll('.service-card').forEach(card => {
                const cardType = card.dataset.type;
                if (type === 'all' || cardType === type) {
                    card.style.display = 'flex';
                    setTimeout(() => card.classList.add('show'), 50);
                } else {
                    card.classList.remove('show');
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        }

        // === تبديل الحقول حسب نوع الخدمة (لنموذج الإضافة) ===
        function toggleFields() {
            const type = document.getElementById('add_type').value;
            document.querySelectorAll('.hotel-field, .restaurant-field, .activity-field').forEach(field => {
                field.style.display = 'none';
            });
            document.querySelector('.' + type + '-field').style.display = 'block';
        }

        // === إعداد معاينة الصور ورفعها ===
        setupImageUpload('imageAdd', 'previewImgAdd', 'previewAdd', 'uploadAreaAdd', 'progressAdd', 'progressBarAdd', 'errorAdd');
        setupImageUpload('imageEdit', 'previewImgEdit', 'previewEdit', 'uploadAreaEdit', 'progressEdit', 'progressBarEdit', 'errorEdit');

        function setupImageUpload(inputId, previewImgId, previewContainerId, uploadAreaId, progressId, progressBarId, errorId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewImgId);
            const container = document.getElementById(previewContainerId);
            const area = document.getElementById(uploadAreaId);
            const progress = document.getElementById(progressId);
            const bar = document.getElementById(progressBarId);
            const error = document.getElementById(errorId);

            // عند اختيار ملف
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                // التحقق من نوع الملف
                if (!file.type.startsWith('image/')) {
                    showError(error, '❌ يرجى اختيار ملف صورة فقط (JPG, PNG, GIF, WebP)');
                    input.value = '';
                    return;
                }

                // التحقق من الحجم
                if (file.size > 5 * 1024 * 1024) {
                    showError(error, '❌ حجم الصورة يتجاوز 5 ميجابايت');
                    input.value = '';
                    return;
                }

                hideError(error);

                // عرض شريط التقدم (محاكاة)
                progress.classList.add('show');
                bar.style.width = '0%';
                let width = 0;
                const interval = setInterval(() => {
                    if (width >= 90) clearInterval(interval);
                    else {
                        width += 10;
                        bar.style.width = width + '%';
                    }
                }, 80);

                // قراءة وعرض المعاينة
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.add('show');
                    progress.classList.remove('show');
                    bar.style.width = '100%';
                    setTimeout(() => progress.classList.remove('show'), 300);
                };
                reader.readAsDataURL(file);
            });

            // دعم السحب والإفلات
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('dragover');
            });
            area.addEventListener('dragleave', () => area.classList.remove('dragover'));
            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        // === إزالة معاينة الصورة ===
        function removePreview(type) {
            document.getElementById('image' + type).value = '';
            document.getElementById('previewImg' + type).src = '';
            document.getElementById('preview' + type).classList.remove('show');
            hideError(document.getElementById('error' + type));
        }

        function showError(el, msg) {
            el.textContent = msg;
            el.classList.add('show');
        }

        function hideError(el) {
            el.classList.remove('show');
        }

        // === تعبئة نموذج التعديل ببيانات الخدمة ===
        function editService(s) {
            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_type').value = s._type;
            document.getElementById('edit_type_display').value = {
                'hotel': '🏨 فندق',
                'restaurant': '🍽️ مطعم',
                'activity': '🎯 نشاط'
            } [s._type];
            document.getElementById('edit_name').value = s.name;
            document.getElementById('edit_city').value = s.city;
            document.getElementById('edit_price').value = s.price || s.price_per_night || s.avg_meal_price || '';
            document.getElementById('edit_rating').value = s.rating || 0;

            // عرض الصورة الحالية إن وجدت
            const currentPreview = document.getElementById('edit_current_preview');
            const noCurrent = document.getElementById('noCurrentImage');

            if (s.image_url && s.image_url.includes('uploads/')) {
                currentPreview.src = '../' + s.image_url;
                currentPreview.style.display = 'block';
                noCurrent.style.display = 'none';
            } else {
                currentPreview.style.display = 'none';
                noCurrent.style.display = 'block';
            }

            // إعادة تعيين حقل الصورة الجديدة
            removePreview('Edit');
        }

        // === إعادة تعيين النماذج عند إغلاق المودال ===
        document.getElementById('addModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addForm').reset();
            removePreview('Add');
            toggleFields(); // إعادة عرض الحقول الافتراضية
        });

        document.getElementById('editModal').addEventListener('hidden.bs.modal', function() {
            removePreview('Edit');
        });

        // === تهيئة الحقول عند تحميل الصفحة ===
        toggleFields();
    </script>
</body>

</html>