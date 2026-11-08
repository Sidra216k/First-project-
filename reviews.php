<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$provider = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT id FROM providers WHERE user_id = $uid"
));
$pid = $provider['id'] ?? 0;

// جلب جميع التقييمات لخدمات هذا المزود
$reviews = mysqli_query(
    $conn,
    "SELECT r.*, u.username as reviewer_name,
            CASE 
                WHEN h.id IS NOT NULL THEN h.name
                WHEN rest.id IS NOT NULL THEN rest.name
                WHEN a.id IS NOT NULL THEN a.name
            END as service_name,
            r.service_type
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     LEFT JOIN hotels h ON r.service_id = h.id AND r.service_type = 'hotel'
     LEFT JOIN restaurants rest ON r.service_id = rest.id AND r.service_type = 'restaurant'
     LEFT JOIN activities a ON r.service_id = a.id AND r.service_type = 'activity'
     WHERE r.service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
     )
     ORDER BY r.created_at DESC"
);

// إحصائيات التقييمات
$review_stats = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as total, AVG(rating) as avg_rating,
            SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as one_star
     FROM reviews 
     WHERE service_id IN (
        SELECT id FROM hotels WHERE provider_id=$pid
        UNION SELECT id FROM restaurants WHERE provider_id=$pid
        UNION SELECT id FROM activities WHERE provider_id=$pid
     )"
));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقييمات | مقدم الخدمة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        body {
            background: #f4f6f9;
            font-family: 'Tajawal', sans-serif;
        }

        .main-content {
            margin-right: 270px;
            padding: 30px;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
        }

        .rating-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 4px;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-right: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 class="fw-bold mb-4" data-aos="fade-right">
            <i class="fas fa-star me-2" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            تقييمات العملاء
        </h2>

        <div class="row g-4">
            <!-- ملخص التقييمات -->
            <div class="col-lg-4" data-aos="fade-up">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <div class="display-3 fw-bold text-warning">
                            <?php echo number_format($review_stats['avg_rating'] ?? 0, 1); ?>
                        </div>
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= ($review_stats['avg_rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted"><?php echo $review_stats['total'] ?? 0; ?> تقييم إجمالي</small>
                    </div>

                    <div class="mt-4">
                        <?php
                        $stars = [5 => '5 نجوم', 4 => '4 نجوم', 3 => '3 نجوم', 2 => 'نجمتين', 1 => 'نجمة واحدة'];
                        $keys = ['five_star', 'four_star', 'three_star', 'two_star', 'one_star'];
                        $total = $review_stats['total'] ?? 1;
                        foreach ([5, 4, 3, 2, 1] as $star):
                            $key = $keys[5 - $star];
                            $count = $review_stats[$key] ?? 0;
                            $percent = ($count / $total) * 100;
                        ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="text-muted" style="width:70px;"><?php echo $stars[$star]; ?></span>
                                <div class="rating-bar flex-grow-1">
                                    <div class="rating-fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <span class="text-muted" style="width:30px;"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- قائمة التقييمات -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <?php if (mysqli_num_rows($reviews) > 0): ?>
                    <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($rev['reviewer_name']); ?></strong>
                                    <span class="text-muted me-2">|</span>
                                    <small class="text-muted"><?php echo htmlspecialchars($rev['service_name']); ?></small>
                                </div>
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></small>
                            </div>

                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $rev['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </div>

                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash fa-4x text-muted mb-3 opacity-25"></i>
                        <h5 class="text-muted">لا توجد تقييمات بعد</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 600
        });
    </script>
</body>

</html>