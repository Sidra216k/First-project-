<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $uid"));
$provider = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM providers WHERE user_id = $uid"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث بيانات المستخدم
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "UPDATE users SET username='$username', email='$email'";
    if ($password) $sql .= ", password='$password'";
    $sql .= " WHERE id = $uid";
    mysqli_query($conn, $sql);

    // تحديث بيانات المزود
    $business = mysqli_real_escape_string($conn, $_POST['business_name']);
    $type = mysqli_real_escape_string($conn, $_POST['business_type']);
    mysqli_query($conn, "UPDATE providers SET business_name='$business', business_type='$type' WHERE user_id=$uid");

    $msg = "✅ تم تحديث الملف الشخصي";
    $user['username'] = $username;
    $user['email'] = $email;
    $provider['business_name'] = $business;
    $provider['business_type'] = $type;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>الملف الشخصي | Service Provider</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            margin: 0 auto;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 45px;
            margin: 0 auto 25px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.15);
        }

        .btn-custom {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .verified-badge {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="profile-card">
            <div class="text-center">
                <div class="avatar"><i class="fas fa-store"></i></div>
                <h3 class="fw-bold"><?php echo htmlspecialchars($provider['business_name']); ?></h3>
                <p class="text-muted"><?php echo ['hotel' => '🏨 فندق', 'restaurant' => '🍽️ مطعم', 'activity' => '🎯 نشاط سياحي'][$provider['business_type']]; ?></p>
                <span class="verified-badge"><?php echo $provider['is_verified'] ? '✅ حساب معتمد' : '⏳ قيد المراجعة'; ?></span>
            </div>
            <hr>
            <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>
            <form method="POST">
                <h5 class="fw-bold mb-4"><i class="fas fa-user me-2"></i>بيانات الحساب</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">كلمة المرور الجديدة <small class="text-muted">(اتركها فارغة للإبقاء على الحالية)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <h5 class="fw-bold mb-4 mt-5"><i class="fas fa-building me-2"></i>بيانات المنشأة</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">اسم المنشأة</label>
                        <input type="text" name="business_name" class="form-control" value="<?php echo htmlspecialchars($provider['business_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">نوع الخدمة</label>
                        <select name="business_type" class="form-select">
                            <option value="hotel" <?php echo $provider['business_type'] == 'hotel' ? 'selected' : ''; ?>>🏨 فندق</option>
                            <option value="restaurant" <?php echo $provider['business_type'] == 'restaurant' ? 'selected' : ''; ?>>🍽️ مطعم</option>
                            <option value="activity" <?php echo $provider['business_type'] == 'activity' ? 'selected' : ''; ?>>🎯 نشاط سياحي</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-custom w-100 mt-4"><i class="fas fa-save me-2"></i>حفظ التغييرات</button>
            </form>
        </div>
    </div>
</body>

</html>