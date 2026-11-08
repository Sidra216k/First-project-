<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// === إعدادات رفع الصور ===
define('UPLOAD_DIR', '../uploads/destinations/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$msg = '';
$msgType = '';

// === دوال مساعدة ===

// دالة لرفع الصورة بأمان
function uploadImage($fileInput)
{
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'خطأ في رفع الملف'];
    }

    $file = $_FILES[$fileInput];

    // التحقق من الحجم
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'حجم الصورة يتجاوز 5 ميجابايت'];
    }

    // التحقق من النوع
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'نوع الملف غير مدعوم (فقط JPG, PNG, GIF, WebP)'];
    }

    // توليد اسم فريد للملف
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('dest_', true) . '.' . $extension;
    $destination = UPLOAD_DIR . $newFilename;

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $newFilename, 'path' => $destination];
    }

    return ['success' => false, 'error' => 'فشل في حفظ الصورة'];
}

// دالة لحذف صورة قديمة
function deleteImage($filename)
{
    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
        return unlink(UPLOAD_DIR . $filename);
    }
    return false;
}

// === CRUD Operations ===

// ➕ إضافة وجهة جديدة (مع صورة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_destination'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $image_url = '';

    // معالجة رفع الصورة
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage('image');
        if ($upload['success']) {
            $image_url = 'uploads/destinations/' . $upload['filename'];
        } else {
            $msg = "❌ " . $upload['error'];
            $msgType = "danger";
        }
    }

    if (empty($msg)) {
        $sql = "INSERT INTO destinations (name, country, description, image_url, is_featured) 
                VALUES ('$name', '$country', '$desc', '$image_url', $is_featured)";

        if (mysqli_query($conn, $sql)) {
            $msg = "✅ تمت إضافة الوجهة بنجاح";
            $msgType = "success";
        } else {
            $msg = "❌ خطأ في الحفظ: " . mysqli_error($conn);
            $msgType = "danger";
        }
    }
}

// ✏️ تحديث وجهة (مع تغيير الصورة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_destination'])) {
    $id = intval($_POST['dest_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $image_url = mysqli_real_escape_string($conn, $_POST['current_image'] ?? '');

    // إذا تم رفع صورة جديدة
    if (!empty($_FILES['image']['name'])) {
        // حذف الصورة القديمة إذا وجدت
        if ($image_url && strpos($image_url, 'uploads/destinations/') === 0) {
            $oldFile = basename($image_url);
            deleteImage($oldFile);
        }

        // رفع الصورة الجديدة
        $upload = uploadImage('image');
        if ($upload['success']) {
            $image_url = 'uploads/destinations/' . $upload['filename'];
        } else {
            $msg = "❌ " . $upload['error'];
            $msgType = "danger";
        }
    }

    if (empty($msg)) {
        $sql = "UPDATE destinations SET name='$name', country='$country', description='$desc', 
                image_url='$image_url', is_featured=$is_featured WHERE id=$id";

        if (mysqli_query($conn, $sql)) {
            $msg = "✅ تم تحديث الوجهة بنجاح";
            $msgType = "success";
        } else {
            $msg = "❌ خطأ في التحديث: " . mysqli_error($conn);
            $msgType = "danger";
        }
    }
}

// 🗑️ حذف وجهة (مع حذف صورتها)
if (isset($_GET['delete_dest'])) {
    $id = intval($_GET['delete_dest']);

    // جلب الصورة لحذفها من المجلد
    $dest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_url FROM destinations WHERE id=$id"));
    if ($dest && $dest['image_url'] && strpos($dest['image_url'], 'uploads/destinations/') === 0) {
        deleteImage(basename($dest['image_url']));
    }

    mysqli_query($conn, "DELETE FROM destinations WHERE id=$id");
    header("Location: manage_content.php?msg=deleted");
    exit();
}

// جلب الوجهات للعرض
$destinations = mysqli_query($conn, "SELECT * FROM destinations ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحتوى | Admin</title>
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

        /* بطاقة الوجهة */
        .dest-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .dest-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(118, 75, 162, 0.2);
        }

        .dest-image {
            height: 180px;
            width: 100%;
            object-fit: cover;
            border-bottom: 3px solid transparent;
            transition: border-color 0.3s;
        }

        .dest-card:hover .dest-image {
            border-bottom-color: #667eea;
        }

        .dest-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .dest-title {
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }

        .dest-country {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .dest-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
            flex-grow: 1;
        }

        .featured-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(245, 87, 108, 0.3);
        }

        .image-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 3rem;
        }

        /* زر التخصيص */
        .btn-custom {
            background: var(--gradient);
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
            box-shadow: 0 10px 25px rgba(118, 75, 162, 0.4);
            color: white;
        }

        /* نموذج الرفع */
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .upload-area i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .upload-area input[type="file"] {
            display: none;
        }

        /* معاينة الصورة */
        .preview-container {
            margin-top: 15px;
            display: none;
        }

        .preview-container.show {
            display: block;
            animation: fadeIn 0.3s;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        }

        /* النموذج */
        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 20px 25px;
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
            background: var(--gradient);
            width: 0%;
            transition: width 0.3s;
            border-radius: 3px;
        }

        /* رسائل الخطأ للنموذج */
        .form-error {
            color: #f5576c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .form-error.show {
            display: block;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-right: 0;
                padding: 20px;
            }

            .dest-image {
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-right">
            <h2 class="fw-bold">
                <i class="fas fa-globe-americas me-2" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                إدارة المحتوى والوجهات
            </h2>
            <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>إضافة وجهة
            </button>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show">✅ تم حذف الوجهة بنجاح
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- شبكة الوجهات -->
        <div class="row g-4" data-aos="fade-up">
            <?php if (mysqli_num_rows($destinations) > 0): ?>
                <?php while ($d = mysqli_fetch_assoc($destinations)): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="dest-card">
                            <div class="position-relative">
                                <?php if ($d['image_url'] && file_exists('../' . $d['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($d['image_url']); ?>"
                                        class="dest-img dest-image" alt="<?php echo htmlspecialchars($d['name']); ?>">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ($d['is_featured']): ?>
                                    <span class="featured-badge">⭐ مميز</span>
                                <?php endif; ?>
                            </div>

                            <div class="dest-body">
                                <h6 class="dest-title"><?php echo htmlspecialchars($d['name']); ?></h6>
                                <small class="dest-country">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($d['country']); ?>
                                </small>
                                <p class="dest-desc">
                                    <?php echo mb_strimwidth(htmlspecialchars($d['description']), 0, 80, '...'); ?>
                                </p>

                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        onclick='editDest(<?php echo json_encode($d, JSON_UNESCAPED_UNICODE); ?>)'>
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <a href="?delete_dest=<?php echo $d['id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('⚠️ هل أنت متأكد من حذف هذه الوجهة؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card p-5 text-center">
                        <i class="fas fa-globe fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد وجهات مضافة بعد</h5>
                        <p class="text-muted mb-4">ابدأ بإضافة أول وجهة سياحية لمنصتك</p>
                        <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-2"></i>إضافة وجهة الآن
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: إضافة وجهة جديدة -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-plus-circle me-2 text-success"></i>إضافة وجهة جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="addForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- الاسم والدولة -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">اسم الوجهة *</label>
                                <input type="text" name="name" class="form-control" required
                                    placeholder="مثال: الرياض، جدة، دبي...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الدولة *</label>
                                <input type="text" name="country" class="form-control" required
                                    placeholder="المملكة العربية الة">
                            </div>

                            <!-- رفع الصورة -->
                            <div class="col-12">
                                <label class="form-label fw-bold">صورة الوجهة</label>
                                <div class="upload-area" id="uploadAreaAdd" onclick="document.getElementById('imageAdd').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-1 fw-bold">اضغط لاختيار صورة أو اسحبها هنا</p>
                                    <small class="text-muted">JPG, PNG, GIF, WebP - أقصى حجم 5 ميجابايت</small>
                                    <input type="file" name="image" id="imageAdd" accept="image/*">
                                </div>
                                <div class="upload-progress" id="progressAdd">
                                    <div class="upload-progress-bar" id="progressBarAdd"></div>
                                </div>
                                <div class="form-error" id="errorAdd"></div>

                                <!-- معاينة الصورة -->
                                <div class="preview-container position-relative" id="previewAdd">
                                    <img src="" class="preview-image" id="previewImgAdd" alt="معاينة">
                                    <button type="button" class="preview-remove" onclick="removePreview('Add')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- الوصف -->
                            <div class="col-12">
                                <label class="form-label fw-bold">الوصف</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="وصف مختصر عن الوجهة..."></textarea>
                            </div>

                            <!-- مميز -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="featuredAdd">
                                    <label class="form-check-label" for="featuredAdd">
                                        تمييز هذه الوجهة (تظهر في الصفحة الرئيسية)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_destination" class="btn btn-custom rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>حفظ الوجهة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: تعديل وجهة -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-edit me-2 text-warning"></i>تعديل الوجهة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="dest_id" id="edit_id">
                        <input type="hidden" name="current_image" id="edit_current_image">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">اسم الوجهة *</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الدولة *</label>
                                <input type="text" name="country" id="edit_country" class="form-control" required>
                            </div>

                            <!-- الصورة الحالية + رفع جديدة -->
                            <div class="col-12">
                                <label class="form-label fw-bold">صورة الوجهة</label>

                                <!-- الصورة الحالية -->
                                <div id="currentImageContainer" class="mb-3">
                                    <small class="text-muted d-block mb-2">الصورة الحالية:</small>
                                    <img src="" id="edit_current_preview" class="preview-image" style="max-height: 150px;" alt="حالية">
                                </div>

                                <!-- منطقة الرفع الجديدة -->
                                <div class="upload-area" id="uploadAreaEdit" onclick="document.getElementById('imageEdit').click()">
                                    <i class="fas fa-sync-alt"></i>
                                    <p class="mb-1 fw-bold">تغيير الصورة (اختياري)</p>
                                    <small class="text-muted">اتركها فارغة للإبقاء على الصورة الحالية</small>
                                    <input type="file" name="image" id="imageEdit" accept="image/*">
                                </div>
                                <div class="upload-progress" id="progressEdit">
                                    <div class="upload-progress-bar" id="progressBarEdit"></div>
                                </div>
                                <div class="form-error" id="errorEdit"></div>

                                <!-- معاينة الصورة الجديدة -->
                                <div class="preview-container position-relative" id="previewEdit">
                                    <img src="" class="preview-image" id="previewImgEdit" alt="معاينة جديدة">
                                    <button type="button" class="preview-remove" onclick="removePreview('Edit')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">الوصف</label>
                                <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="featuredEdit">
                                    <label class="form-check-label" for="featuredEdit">
                                        تمييز هذه الوجهة
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_destination" class="btn btn-custom rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>تحديث الوجهة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // === تهيئة AOS ===
        AOS.init({
            once: true,
            duration: 600
        });

        // === دوال معاينة الصور ===

        // إعداد معاينة للإضافة
        setupImagePreview('imageAdd', 'previewImgAdd', 'previewAdd', 'uploadAreaAdd', 'progressAdd', 'progressBarAdd', 'errorAdd');

        // إعداد معاينة للتعديل
        setupImagePreview('imageEdit', 'previewImgEdit', 'previewEdit', 'uploadAreaEdit', 'progressEdit', 'progressBarEdit', 'errorEdit');

        function setupImagePreview(inputId, previewImgId, previewContainerId, uploadAreaId, progressId, progressBarId, errorId) {
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

                // التحقق من النوع
                if (!file.type.startsWith('image/')) {
                    showError(error, '❌ يرجى اختيار ملف صورة فقط');
                    input.value = '';
                    return;
                }

                // التحقق من الحجم (5MB)
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
                    if (width >= 90) {
                        clearInterval(interval);
                    } else {
                        width += 10;
                        bar.style.width = width + '%';
                    }
                }, 100);

                // معاينة الصورة
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

            // تأثيرات السحب والإفلات
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

        // إزالة المعاينة
        function removePreview(type) {
            document.getElementById('image' + type).value = '';
            document.getElementById('previewImg' + type).src = '';
            document.getElementById('preview' + type).classList.remove('show');
            hideError(document.getElementById('error' + type));
        }

        // عرض/إخفاء رسائل الخطأ
        function showError(el, msg) {
            el.textContent = msg;
            el.classList.add('show');
        }

        function hideError(el) {
            el.classList.remove('show');
        }

        // === تعبئة نموذج التعديل ===
        function editDest(d) {
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_name').value = d.name;
            document.getElementById('edit_country').value = d.country;
            document.getElementById('edit_desc').value = d.description;
            document.getElementById('featuredEdit').checked = d.is_featured == 1;
            document.getElementById('edit_current_image').value = d.image_url;

            // عرض الصورة الحالية
            const currentPreview = document.getElementById('edit_current_preview');
            const currentContainer = document.getElementById('currentImageContainer');
            if (d.image_url && d.image_url.includes('uploads/destinations/')) {
                currentPreview.src = '../' + d.image_url;
                currentContainer.style.display = 'block';
            } else {
                currentContainer.style.display = 'none';
            }

            // إعادة تعيين منطقة الرفع الجديدة
            removePreview('Edit');
        }

        // === تحسينات إضافية ===

        // إغلاق النماذج عند الحذف
        document.querySelectorAll('[onclick*="delete_dest"]').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(m => {
                        const modal = bootstrap.Modal.getInstance(m);
                        if (modal) modal.hide();
                    });
                }, 100);
            });
        });

        // منع إرسال النموذج إذا لم يتم اختيار صورة (اختياري)
        document.getElementById('addForm').addEventListener('submit', function(e) {
            // يمكن إضافة تحقق إضافي هنا إذا لزم
        });
    </script>
</body>

</html>