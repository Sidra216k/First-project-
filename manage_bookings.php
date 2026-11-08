<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// === CRUD Operations ===

// ✏️ تحديث حالة الحجز
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $id = intval($_POST['booking_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE bookings SET status='$status' WHERE id=$id");
    $msg = "✅ تم تحديث حالة الحجز";
    $msgType = "success";
}

// 🗑️ حذف حجز
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM bookings WHERE id=$id");
    header("Location: manage_bookings.php?msg=deleted");
    exit();
}

// 🔍 فلترة وعرض الحجوزات
$filter = $_GET['filter'] ?? 'all';
$where = "";
if ($filter === 'pending') $where = "WHERE b.status = 'pending'";
elseif ($filter === 'confirmed') $where = "WHERE b.status = 'confirmed'";
elseif ($filter === 'cancelled') $where = "WHERE b.status = 'cancelled'";

$bookings = mysqli_query($conn, "SELECT b.*, t.full_name, u.email, 
    COALESCE(h.name, r.name, a.name) as service_name,
    COALESCE(h.city, r.city, a.city) as city
    FROM bookings b
    JOIN travelers t ON b.traveler_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN hotels h ON b.service_id = h.id AND b.service_type = 'hotel'
    LEFT JOIN restaurants r ON b.service_id = r.id AND b.service_type = 'restaurant'
    LEFT JOIN activities a ON b.service_id = a.id AND b.service_type = 'activity'
    $where
    ORDER BY b.booking_date DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إدارة الحجوزات | Admin</title>
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

        .filter-btn {
            padding: 8px 20px;
            border-radius: 50px;
            border: 2px solid #667eea;
            color: #667eea;
            background: white;
            font-weight: 500;
            transition: 0.3s;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--gradient);
            color: white;
            border-color: transparent;
        }

        .status-select {
            padding: 5px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }

        .badge-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-completed {
            background: #cce5ff;
            color: #004085;
        }

        .service-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.2rem;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            font-size: 0.9rem;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-right">
            <h2 class="fw-bold"><i class="fas fa-calendar-check me-2" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>إدارة الحجوزات</h2>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                <i class="fas fa-ticket-alt me-1"></i> <?php echo mysqli_num_rows($bookings); ?> حجز
            </span>
        </div>

        <?php if (isset($msg)) echo "<div class='alert alert-$msgType alert-dismissible fade show'>$msg <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<div class='alert alert-success'>✅ تم حذف الحجز</div>"; ?>

        <!-- Filters -->
        <div class="card p-3 mb-4" data-aos="fade-up">
            <div class="d-flex gap-2 flex-wrap">
                <a href="?filter=all" class="filter-btn <?php echo !isset($_GET['filter']) || $_GET['filter'] == 'all' ? 'active' : ''; ?>">الكل</a>
                <a href="?filter=pending" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'pending' ? 'active' : ''; ?>">🟡 قيد الانتظار</a>
                <a href="?filter=confirmed" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'confirmed' ? 'active' : ''; ?>">🟢 مؤكدة</a>
                <a href="?filter=cancelled" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'cancelled' ? 'active' : ''; ?>">🔴 ملغاة</a>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card p-4" data-aos="fade-up" data-aos-delay="100">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>المسافر</th>
                            <th>الخدمة</th>
                            <th>المدينة</th>
                            <th>تاريخ الحجز</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = mysqli_fetch_assoc($bookings)) {
                            $badgeClass = ['pending' => 'badge-pending', 'confirmed' => 'badge-confirmed', 'cancelled' => 'badge-cancelled', 'completed' => 'badge-completed'][$b['status']];
                            $icon = ['hotel' => 'fa-hotel', 'restaurant' => 'fa-utensils', 'activity' => 'fa-umbrella-beach'][$b['service_type']];
                        ?>
                            <tr>
                                <td><strong>#<?php echo $b['id']; ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($b['full_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($b['email']); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="service-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($b['service_name'] ?: 'خدمة #' . $b['service_id']); ?></div>
                                            <small class="text-muted"><?php echo $b['service_type']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($b['city'] ?: '-'); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($b['booking_date'])); ?></td>
                                <td class="fw-bold"><?php echo number_format($b['total_price'], 2); ?> $</td>
                                <td>
                                    <span class="badge-status <?php echo $badgeClass; ?>">
                                        <?php echo ['pending' => 'قيد الانتظار', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي', 'completed' => 'مكتمل'][$b['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn-action btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                            onclick='editBooking(<?php echo json_encode($b); ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="?delete=<?php echo $b['id']; ?>" class="btn-action btn-outline-danger" onclick="return confirm('⚠️ حذف هذا الحجز؟')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-edit me-2 text-warning"></i>تحديث حالة الحجز</h5>
                <form method="POST">
                    <input type="hidden" name="booking_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">الخدمة</label>
                        <input type="text" id="edit_service" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">المسافر</label>
                        <input type="text" id="edit_traveler" class="form-control" disabled>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">حالة الحجز</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="pending">🟡 قيد الانتظار</option>
                            <option value="confirmed">🟢 مؤكد</option>
                            <option value="cancelled">🔴 ملغي</option>
                            <option value="completed">🔵 مكتمل</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_booking" class="btn btn-custom rounded-pill px-4" style="background: var(--gradient); color: white; border: none;">تحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true
        });

        function editBooking(b) {
            document.getElementById('edit_id').value = b.id;
            document.getElementById('edit_service').value = b.service_name || 'خدمة #' + b.service_id;
            document.getElementById('edit_traveler').value = b.full_name;
            document.getElementById('edit_status').value = b.status;
        }
    </script>
</body>

</html>