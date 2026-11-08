<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// إحصائيات
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'],
    'travelers' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM travelers"))['c'],
    'providers' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM providers"))['c'],
    'hotels' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM hotels"))['c'],
    'bookings' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings"))['c'],
    'revenue' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price),0) as t FROM bookings WHERE status='confirmed'"))['t']
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --dark: #1a1a2e;
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
            min-height: 100vh;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: 0.4s;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: slideIn 0.6s ease forwards;
            opacity: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(118, 75, 162, 0.2);
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
        }

        .stat-info h6 {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-info h3 {
            font-weight: 800;
            color: #333;
            margin: 0;
            font-size: 1.8rem;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }

        .delay-5 {
            animation-delay: 0.5s;
        }

        .delay-6 {
            animation-delay: 0.6s;
        }

        .page-title {
            font-weight: 800;
            color: #333;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .quick-btn {
            background: white;
            border: none;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #555;
            transition: 0.3s;
            text-decoration: none;
        }

        .quick-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(118, 75, 162, 0.2);
            color: #667eea;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2 class="page-title" data-aos="fade-right"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h2>

        <!-- Quick Actions -->
        <div class="quick-actions" data-aos="fade-up">
            <a href="manage_users.php" class="quick-btn"><i class="fas fa-users"></i> المستخدمين</a>
            <a href="manage_hotels.php" class="quick-btn"><i class="fas fa-hotel"></i> الفنادق</a>
            <a href="reports.php" class="quick-btn"><i class="fas fa-chart-pie"></i> التقارير</a>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-1">
                    <div class="stat-icon" style="background: var(--gradient);"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h6>إجمالي المستخدمين</h6>
                        <h3><?php echo $stats['users']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-2">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d);"><i class="fas fa-user-friends"></i></div>
                    <div class="stat-info">
                        <h6>المسافرون</h6>
                        <h3><?php echo $stats['travelers']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-3">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);"><i class="fas fa-store"></i></div>
                    <div class="stat-info">
                        <h6>مزودي الخدمة</h6>
                        <h3><?php echo $stats['providers']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-4">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);"><i class="fas fa-hotel"></i></div>
                    <div class="stat-info">
                        <h6>الفنادق</h6>
                        <h3><?php echo $stats['hotels']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-5">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-info">
                        <h6>الحجوزات</h6>
                        <h3><?php echo $stats['bookings']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card delay-6">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #a18cd1, #fbc2eb);"><i class="fas fa-wallet"></i></div>
                    <div class="stat-info">
                        <h6>الإيرادات</h6>
                        <h3><?php echo number_format($stats['revenue'], 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="chart-card" data-aos="fade-up">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>آخر الحجوزات</h5>
                <a href="manage_bookings.php" class="btn btn-sm btn-outline-primary rounded-pill">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>المسافر</th>
                            <th>الخدمة</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bookings = mysqli_query($conn, "SELECT b.*, t.full_name, s.name as service_name FROM bookings b 
                            JOIN travelers t ON b.traveler_id = t.id 
                            LEFT JOIN (SELECT id, name FROM hotels UNION SELECT id, name FROM restaurants UNION SELECT id, name FROM activities) s ON b.service_id = s.id 
                            ORDER BY b.id DESC LIMIT 5");
                        while ($row = mysqli_fetch_assoc($bookings)) {
                            $badge = ['pending' => 'bg-warning', 'confirmed' => 'bg-success', 'cancelled' => 'bg-danger', 'completed' => 'bg-info'][$row['status']];
                            echo "<tr>
                                <td><strong>#{$row['id']}</strong></td>
                                <td>{$row['full_name']}</td>
                                <td>{$row['service_name']} ({$row['service_type']})</td>
                                <td>{$row['booking_date']}</td>
                                <td><strong>{$row['total_price']}</strong> $</td>
                                <td><span class='badge $badge'>{$row['status']}</span></td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true
        });
    </script>
</body>

</html>