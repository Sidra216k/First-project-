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
    "SELECT t.id as traveler_id FROM travelers t WHERE t.user_id = $uid"
));
$tid = $traveler['traveler_id'] ?? 0;

// حذف رحلة
if (isset($_GET['delete'])) {
    $trip_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM trips WHERE id=$trip_id AND traveler_id=$tid");
    header("Location: my_itinerary.php?msg=deleted");
    exit();
}

$trips = mysqli_query($conn, "SELECT * FROM trips WHERE traveler_id=$tid ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مسار رحلتي | TravelPlanner</title>
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
            max-width: 1200px;
            margin: 70px auto 0;
        }

        .trip-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
        }

        .trip-info h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .trip-meta {
            color: var(--text-secondary);
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .trip-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .trip-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-trip {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-view {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-primary);
        }

        .btn-view:hover {
            background: var(--gold-primary);
            color: var(--blue-dark);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
            display: block;
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

        @media (max-width: 768px) {
            .trip-card {
                grid-template-columns: 1fr;
            }

            .trip-actions {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right">
            <i class="fas fa-route me-2" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            مسارات رحلتي
        </h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">✅ تم حذف الرحلة بنجاح</div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($trips) > 0): ?>
            <?php while ($trip = mysqli_fetch_assoc($trips)):
                $itinerary = json_decode($trip['itinerary_json'], true) ?? [];
            ?>
                <div class="trip-card" data-aos="fade-up">
                    <div class="trip-info">
                        <h3>📍 <?php echo htmlspecialchars($trip['destination']); ?></h3>
                        <div class="trip-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo $trip['start_date']; ?> - <?php echo $trip['end_date']; ?></span>
                            <span><i class="fas fa-wallet"></i> <?php echo number_format($trip['total_budget'], 2); ?> $</span>
                            <span><i class="fas fa-list"></i> <?php echo count($itinerary); ?> خدمات</span>
                        </div>
                        <?php if (count($itinerary) > 0): ?>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php foreach (array_slice($itinerary, 0, 5) as $item): ?>
                                    <span style="background:rgba(212,175,55,0.1);padding:4px 10px;border-radius:8px;font-size:0.85rem;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="trip-actions">
                        <!-- بدلاً من: -->

                        <!-- استخدم: -->
                        <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn-trip btn-view">
                            <i class="fas fa-eye"></i> عرض التفاصيل
                        </a> <a href="?delete=<?php echo $trip['id']; ?>" class="btn-trip btn-delete" onclick="return confirm('حذف هذه الرحلة؟')">
                            <i class="fas fa-trash"></i> حذف
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-map-marked-alt"></i>
                <h3>لا توجد رحلات محفوظة</h3>
                <p>ابدأ بتخطيط رحلتك الأولى!</p>
                <a href="plan_trip.php" class="btn-trip btn-view" style="margin-top:20px;">
                    <i class="fas fa-plus"></i> خطة رحلة جديدة
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
    </script>
</body>

</html>