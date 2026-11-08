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
    "SELECT id FROM travelers WHERE user_id = $uid"
));
$tid = $traveler['id'] ?? 0;

// إضافة تقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $service_id = intval($_POST['service_id']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $rating = intval($_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    mysqli_query($conn, "INSERT INTO reviews (user_id, service_id, service_type, rating, comment, created_at)
                        VALUES ($uid, $service_id, '$service_type', $rating, '$comment', NOW())");
    header("Location: reviews.php?msg=added");
    exit();
}

$reviews = mysqli_query(
    $conn,
    "SELECT r.*, 
            CASE WHEN h.id IS NOT NULL THEN h.name WHEN rest.id IS NOT NULL THEN rest.name WHEN a.id IS NOT NULL THEN a.name END as service_name
     FROM reviews r
     LEFT JOIN hotels h ON r.service_id = h.id AND r.service_type = 'hotel'
     LEFT JOIN restaurants rest ON r.service_id = rest.id AND r.service_type = 'restaurant'
     LEFT JOIN activities a ON r.service_id = a.id AND r.service_type = 'activity'
     WHERE r.user_id = $uid ORDER BY r.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقييماتي | TravelPlanner</title>
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
            --warning: #f59e0b;
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
            max-width: 1000px;
            margin: 70px auto 0;
        }

        .review-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .service-name {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .review-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .review-stars {
            color: var(--warning);
            margin-bottom: 10px;
        }

        .review-comment {
            color: var(--text-secondary);
            line-height: 1.7;
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
    </style>
</head>

<body>
    <div class="bg-animation"></div>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right">
            <i class="fas fa-star me-2" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            تقييماتي
        </h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">✅ تم إضافة تقييمك بنجاح</div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($reviews) > 0): ?>
            <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
                <div class="review-card" data-aos="fade-up">
                    <div class="review-header">
                        <span class="service-name"><?php echo htmlspecialchars($rev['service_name'] ?? 'خدمة'); ?></span>
                        <span class="review-date"><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></span>
                    </div>
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $rev['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-comment-slash"></i>
                <h3>لا توجد تقييمات بعد</h3>
                <p>شارك تجاربك مع الآخرين!</p>
                <a href="my_bookings.php" class="btn-edit" style="margin-top:20px;">
                    <i class="fas fa-ticket-alt"></i> الذهاب لحجوزاتي
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