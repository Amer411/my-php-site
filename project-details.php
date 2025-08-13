<?php
// بدء الجلسة في بداية الملف
session_start();

// تعطيل التخزين المؤقت بالكامل
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// معالجة بيانات المشروع
$projectId = $_GET['id'] ?? '';
$projectsFile = __DIR__ . '/projects.json';
$statsFile = __DIR__ . '/stats.json';
$project = null;

// تهيئة ملف الإحصائيات إذا لم يكن موجوداً
if (!file_exists($statsFile)) {
    file_put_contents($statsFile, json_encode([
        'site_visits' => 0,
        'project_visits' => [],
        'project_likes' => [],
        'featured_projects' => []
    ]));
}

// قراءة إحصائيات الموقع
$stats = json_decode(file_get_contents($statsFile), true) ?: [];

// تهيئة مصفوفة المشاريع المزورة في الجلسة إذا لم تكن موجودة
if (!isset($_SESSION['visited_projects'])) {
    $_SESSION['visited_projects'] = [];
}

// تحديث عدد زيارات المشروع فقط إذا لم يتم زيارة الصفحة في هذه الجلسة
if (!isset($_SESSION['visited_projects'][$projectId])) {
    $stats['project_visits'][$projectId] = ($stats['project_visits'][$projectId] ?? 0) + 1;
    
    // التحقق من وصول المشروع لشرط المشروع المميز
    if ($stats['project_visits'][$projectId] >= 100 || ($stats['project_likes'][$projectId] ?? 0) >= 100) {
        if (!in_array($projectId, $stats['featured_projects'])) {
            $stats['featured_projects'][] = $projectId;
            $showFeaturedBadge = true;
        }
    }
    
    file_put_contents($statsFile, json_encode($stats));
    $_SESSION['visited_projects'][$projectId] = true;
}

// معالجة طلب الإعجاب
if (isset($_GET['like'])) {
    $stats['project_likes'][$projectId] = ($stats['project_likes'][$projectId] ?? 0) + 1;
    
    // التحقق من وصول المشروع لشرط المشروع المميز
    if ($stats['project_likes'][$projectId] >= 100 || ($stats['project_visits'][$projectId] ?? 0) >= 100) {
        if (!in_array($projectId, $stats['featured_projects'])) {
            $stats['featured_projects'][] = $projectId;
            $showFeaturedBadge = true;
        }
    }
    
    file_put_contents($statsFile, json_encode($stats));
    header('Location: project-details.php?id=' . $projectId);
    exit;
}

if (file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
    foreach ($projects as $p) {
        if ($p['id'] === $projectId && $p['status'] === 'approved') {
            $project = $p;
            break;
        }
    }
}

if (!$project) {
    header("HTTP/1.0 404 Not Found");
    exit('المشروع غير موجود');
}

// التحقق مما إذا كان المشروع مميزاً
$isFeatured = in_array($projectId, $stats['featured_projects'] ?? []);

// إعداد بيانات المشاركة
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$currentUrl = $baseUrl . '/project-details.php?id=' . urlencode($projectId);
$projectTitle = htmlspecialchars($project['name'] ?? 'مشروع التاجرة');
$description = htmlspecialchars(mb_substr(strip_tags($project['description'] ?? 'وصف مشروع التاجرة'), 0, 160));

// معالجة رابط الصورة الرئيسية
$mainImageUrl = $baseUrl . '/mnsah.jpg'; // صورة افتراضية
if (!empty($project['images'][0]['permanent_url'])) {
    $mainImageUrl = $project['images'][0]['permanent_url'];
    // إذا كان الرابط نسبيًا، أضف عنوان الموقع الأساسي
    if (strpos($mainImageUrl, 'http') !== 0) {
        $mainImageUrl = $baseUrl . '/' . ltrim($mainImageUrl, '/');
    }
}

// إحصائيات المشروع
$likes = $stats['project_likes'][$projectId] ?? 0;
$visits = $stats['project_visits'][$projectId] ?? 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta -->
    <title><?= $projectTitle ?> | منصة مشاريع التاجرات</title>
    <meta name="description" content="<?= $description ?>">

    <!-- Open Graph (WhatsApp / Facebook Preview) -->
    <meta property="og:title" content="<?= $projectTitle ?>">
    <meta property="og:description" content="<?= $description ?>">
    <meta property="og:image" content="<?= $mainImageUrl ?>">
    <meta property="og:image:secure_url" content="<?= $mainImageUrl ?>">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="منصة مشاريع التاجرات">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:updated_time" content="<?= time() ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $projectTitle ?>">
    <meta name="twitter:description" content="<?= $description ?>">
    <meta name="twitter:image" content="<?= $mainImageUrl ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?= $baseUrl ?>/favicon.ico">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff6b6b;
            --success: #25D366;
            --instagram: #E1306C;
            --facebook: #1877F2;
            --light: #f8f9fa;
            --dark: #212529;
            --beauty: #ff6b9d;
            --food: #ff9a3c;
            --handmade: #6bcebb;
            --fashion: #a78bfa;
            --other: #94a3b8;
            --featured: #ffd700;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            background: #f5f7fa;
            color: var(--dark);
            line-height: 1.8;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .project-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            position: relative;
        }

        .project-title {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 2rem;
            font-weight: 700;
        }

        .project-meta {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .project-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .project-governorate {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            background: rgba(37, 117, 252, 0.2);
            color: var(--secondary);
        }

        .project-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            color: var(--dark);
        }

        .stat-item i {
            color: var(--accent);
        }

        .category-beauty {
            background-color: rgba(255, 107, 157, 0.2);
            color: var(--beauty);
        }

        .category-food {
            background-color: rgba(255, 154, 60, 0.2);
            color: var(--food);
        }

        .category-handmade {
            background-color: rgba(107, 206, 187, 0.2);
            color: var(--handmade);
        }

        .category-fashion {
            background-color: rgba(167, 139, 250, 0.2);
            color: var(--fashion);
        }

        .category-other {
            background-color: rgba(148, 163, 184, 0.2);
            color: var(--other);
        }

        /* شارة المشروع المميز */
        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--featured);
            color: #000;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 2;
        }

        /* لوحة المكافآت */
        .reward-panel {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            display: <?= ($visits >= 100 || $likes >= 100) ? 'block' : 'none' ?>;
        }

        .reward-panel h3 {
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .reward-panel p {
            margin-bottom: 10px;
        }

        .reward-progress {
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            width: <?= min(100, max(($visits / 100) * 100, ($likes / 100) * 100)) ?>%;
            transition: width 0.5s ease;
        }

        .reward-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }

        .reward-stat {
            text-align: center;
        }

        .reward-stat .number {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .reward-stat .label {
            font-size: 0.8rem;
            color: #666;
        }

        /* أنماط أزرار المشاركة */
        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .share-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .share-button:hover {
            transform: scale(1.1);
        }

        .whatsapp-share {
            background: var(--success);
        }

        .instagram-share {
            background: var(--instagram);
        }

        .facebook-share {
            background: var(--facebook);
        }

        .copy-share {
            background: var(--secondary);
        }

        .share-modal-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .share-modal-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .share-modal-text {
            text-align: center;
            margin-bottom: 20px;
        }

        .share-modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        /* Slider Styles */
        .slider-container {
            position: relative;
            margin: 20px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-height: 400px;
            background: #f0f0f0;
        }

        .slider {
            display: flex;
            transition: transform 0.5s ease-in-out;
            height: 400px;
        }

        .slider img {
            min-width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            z-index: 1;
        }

        .slider-nav button {
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            margin: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .slider-nav button:hover {
            background: rgba(0,0,0,0.8);
        }

        .slider-dots {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            z-index: 1;
        }

        .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .slider-dot.active {
            background: white;
            transform: scale(1.2);
        }

        .project-description {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            white-space: pre-line;
            line-height: 1.8;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .whatsapp-link {
            background: var(--success);
        }

        .instagram-link {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .like-btn {
            background: white;
            color: var(--accent);
            border: 2px solid var(--accent);
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }

        .like-btn:hover {
            background: var(--accent);
            color: white;
        }

        .like-btn.liked {
            background: var(--accent);
            color: white;
        }

        .like-btn.liked i {
            font-weight: 900;
        }

        .back-btn {
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: var(--secondary);
        }

        .share-btn {
            background: var(--secondary);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }

        .share-btn:hover {
            background: var(--primary);
        }

        .social-link:hover, .like-btn:hover, .back-btn:hover, .share-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .gallery-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .gallery-image:hover {
            transform: scale(1.05);
        }

        @media (max-width: 600px) {
            .project-title {
                font-size: 1.6rem;
            }

            .social-link, .like-btn, .back-btn, .share-btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .slider-container, .slider {
                max-height: 300px;
                height: 300px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .featured-badge {
                font-size: 0.7rem;
                padding: 3px 8px;
            }
            
            .share-buttons {
                gap: 8px;
            }
            
            .share-button {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="project-header">
        <?php if($isFeatured): ?>
            <div class="featured-badge">
                <i class="fas fa-star"></i>
                مشروع مميز
            </div>
        <?php endif; ?>
        
        <h1 class="project-title"><?= $projectTitle ?></h1>
        
        <div class="project-meta">
            <div class="project-category category-<?= htmlspecialchars($project['category'] ?? 'other') ?>">
                <?php 
                switch($project['category'] ?? 'other') {
                    case 'beauty': echo 'التجميل'; break;
                    case 'food': echo 'الطعام'; break;
                    case 'handmade': echo 'الأشغال اليدوية'; break;
                    case 'fashion': echo 'الموضة'; break;
                    default: echo 'أخرى';
                }
                ?>
            </div>
            
            <?php if(!empty($project['governorate'])): ?>
                <div class="project-governorate"><?= htmlspecialchars($project['governorate']) ?></div>
            <?php endif; ?>
        </div>

        <div class="project-stats">
            <div class="stat-item" title="عدد الإعجابات">
                <i class="fas fa-heart"></i>
                <span id="likes-count"><?= $likes ?></span>
            </div>
            <div class="stat-item" title="عدد الزوار">
                <i class="fas fa-eye"></i>
                <span id="visits-count"><?= $visits ?></span>
            </div>
        </div>

        <?php if(!empty($project['images'])): ?>
            <div class="slider-container">
                <div class="slider" id="slider">
                    <?php foreach($project['images'] as $image): ?>
                        <?php 
                        $imgUrl = $image['permanent_url'];
                        if (strpos($imgUrl, 'http') !== 0) {
                            $imgUrl = $baseUrl . '/' . ltrim($imgUrl, '/');
                        }
                        ?>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= $projectTitle ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
                
                <div class="slider-nav">
                    <button id="prev-slide"><i class="fas fa-chevron-right"></i></button>
                    <button id="next-slide"><i class="fas fa-chevron-left"></i></button>
                </div>
                
                <div class="slider-dots" id="slider-dots">
                    <?php for($i = 0; $i < count($project['images']); $i++): ?>
                        <div class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="project-description">
        <?= nl2br(htmlspecialchars($project['description'] ?? 'لا يوجد وصف متاح')) ?>
    </div>

    <!-- لوحة المكافآت -->
    <div class="reward-panel" id="reward-panel">
        <h3><i class="fas fa-trophy"></i> تهانينا!</h3>
        <p>لقد وصل مشروعك إلى معلم هام! استمري في الترويج لمشروعك لتحصلي على المزيد من الزيارات والإعجابات.</p>
        
        <div class="reward-progress">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        
        <div class="reward-stats">
            <div class="reward-stat">
                <div class="number" id="visits-stat"><?= $visits ?></div>
                <div class="label">زيارة</div>
            </div>
            <div class="reward-stat">
                <div class="number" id="likes-stat"><?= $likes ?></div>
                <div class="label">إعجاب</div>
            </div>
        </div>
        
        <?php if($isFeatured): ?>
            <p><strong>مشروعك الآن في قائمة المشاريع المميزة!</strong></p>
        <?php else: ?>
            <p>شاركي مشروعك مع الأصدقاء للحصول على شارة "مشروع مميز" عند الوصول إلى 100 زيارة أو إعجاب.</p>
        <?php endif; ?>
    </div>

    <?php if(!empty($project['images']) && count($project['images']) > 1): ?>
        <div class="image-gallery">
            <?php foreach($project['images'] as $index => $image): ?>
                <?php 
                $imgUrl = $image['permanent_url'];
                if (strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = $baseUrl . '/' . ltrim($imgUrl, '/');
                }
                ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" class="gallery-image" alt="<?= $projectTitle ?>" loading="lazy" data-slide-index="<?= $index ?>">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="social-links">
        <?php if(!empty($project['whatsapp'])): ?>
            <?php 
            $waNumber = preg_replace('/[^0-9]/', '', $project['whatsapp']);
            $waLink = (strpos($project['whatsapp'], 'http') === 0) ? $project['whatsapp'] : 'https://wa.me/' . $waNumber;
            ?>
            <a href="<?= htmlspecialchars($waLink) ?>" class="social-link whatsapp-link" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-whatsapp"></i>
                <span>تواصل عبر واتساب</span>
            </a>
        <?php endif; ?>

        <?php if(!empty($project['instagram'])): ?>
            <?php 
            $igUser = str_replace(['@', 'https://instagram.com/', 'http://instagram.com/', '/'], '', $project['instagram']);
            $igLink = (strpos($project['instagram'], 'http') === 0) ? $project['instagram'] : 'https://instagram.com/' . $igUser;
            ?>
            <a href="<?= htmlspecialchars($igLink) ?>" class="social-link instagram-link" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-instagram"></i>
                <span>تابعنا على إنستغرام</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="action-buttons">
        <a href="?id=<?= $projectId ?>&like=1" class="like-btn <?= isset($_COOKIE['liked_'.$projectId]) ? 'liked' : '' ?>" id="like-button">
            <i class="<?= isset($_COOKIE['liked_'.$projectId]) ? 'fas' : 'far' ?> fa-heart"></i>
            <span><?= isset($_COOKIE['liked_'.$projectId]) ? 'تم الإعجاب' : 'أعجبني' ?></span>
        </a>
        
        <button class="share-btn" onclick="shareProject()">
            <i class="fas fa-share-alt"></i>
            <span>مشاركة</span>
        </button>
        
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-right"></i>
            <span>العودة إلى القائمة</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Slider functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('slider');
            const slides = document.querySelectorAll('#slider img');
            const dots = document.querySelectorAll('.slider-dot');
            const prevBtn = document.getElementById('prev-slide');
            const nextBtn = document.getElementById('next-slide');
            const galleryImages = document.querySelectorAll('.gallery-image');
            
            let currentSlide = 0;
            let slideInterval;
            const slideCount = slides.length;
            
            if (slideCount > 0) {
                // Auto slide every 5 seconds
                function startSlider() {
                    slideInterval = setInterval(() => {
                        goToSlide((currentSlide + 1) % slideCount);
                    }, 5000);
                }
                
                function goToSlide(index) {
                    currentSlide = index;
                    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
                    
                    // Update dots
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('active', i === currentSlide);
                    });
                    
                    // Reset timer when manually changing slides
                    clearInterval(slideInterval);
                    startSlider();
                }
                
                // Navigation buttons
                prevBtn.addEventListener('click', () => {
                    goToSlide((currentSlide - 1 + slideCount) % slideCount);
                });
                
                nextBtn.addEventListener('click', () => {
                    goToSlide((currentSlide + 1) % slideCount);
                });
                
                // Dot navigation
                dots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        goToSlide(parseInt(dot.dataset.index));
                    });
                });
                
                // Gallery image click to go to specific slide
                galleryImages.forEach(img => {
                    img.addEventListener('click', function() {
                        const slideIndex = parseInt(this.dataset.slideIndex);
                        goToSlide(slideIndex);
                        
                        // Scroll to slider
                        document.querySelector('.slider-container').scrollIntoView({
                            behavior: 'smooth'
                        });
                    });
                });
                
                // Start the slider
                startSlider();
            }
            
            // معالجة الإعجاب بدون إعادة تحميل الصفحة
            document.getElementById('like-button').addEventListener('click', async function(e) {
                e.preventDefault();
                
                // التحقق من وجود كوكي للإعجاب
                if (document.cookie.includes('liked_<?= $projectId ?>')) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لقد أعجبت بهذا المشروع مسبقاً',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    return;
                }
                
                try {
                    const response = await fetch(this.getAttribute('href'));
                    if (response.ok) {
                        // تحديث الواجهة
                        this.classList.add('liked');
                        this.innerHTML = '<i class="fas fa-heart"></i> <span>تم الإعجاب</span>';
                        
                        // تحديث العداد
                        const likesCount = document.getElementById('likes-count');
                        const likesStat = document.getElementById('likes-stat');
                        const newLikes = parseInt(likesCount.textContent) + 1;
                        likesCount.textContent = newLikes;
                        likesStat.textContent = newLikes;
                        
                        // تحديث شريط التقدم
                        updateProgressBar();
                        
                        // تعيين كوكي لمنع الإعجاب المتكرر
                        document.cookie = `liked_<?= $projectId ?>=true; max-age=${60*60*24*30}; path=/`;
                        
                        // عرض رسالة نجاح
                        Swal.fire({
                            icon: 'success',
                            title: 'شكراً لك!',
                            text: 'تم تسجيل إعجابك بالمشروع',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // التحقق من وصول المشروع لشرط المشروع المميز
                        checkFeaturedStatus();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ أثناء تسجيل الإعجاب',
                        confirmButtonText: 'حسناً'
                    });
                }
            });
            
            // تحديث شريط التقدم
            function updateProgressBar() {
                const visits = parseInt(document.getElementById('visits-count').textContent);
                const likes = parseInt(document.getElementById('likes-count').textContent);
                const progress = Math.max((visits / 100) * 100, (likes / 100) * 100);
                document.getElementById('progress-bar').style.width = `${Math.min(100, progress)}%`;
            }
            
            // التحقق من حالة المشروع المميز
            function checkFeaturedStatus() {
                const visits = parseInt(document.getElementById('visits-count').textContent);
                const likes = parseInt(document.getElementById('likes-count').textContent);
                
                if (visits >= 100 || likes >= 100) {
                    // عرض رسالة تهنئة
                    Swal.fire({
                        icon: 'success',
                        title: 'مبروك!',
                        html: 'لقد أصبح مشروعك الآن في قائمة المشاريع المميزة! <br><i class="fas fa-star" style="color: gold; font-size: 2rem; margin: 10px 0;"></i>',
                        confirmButtonText: 'رائع'
                    });
                    
                    // عرض لوحة المكافآت إذا كانت مخفية
                    document.getElementById('reward-panel').style.display = 'block';
                }
            }
            
            // تحديث شريط التقدم عند التحميل
            updateProgressBar();
        });

        // مشاركة المشروع
        function shareProject() {
            const shareUrl = '<?= $currentUrl ?>';
            const shareText = '<?= $projectTitle ?> - <?= $description ?>';
            
            Swal.fire({
                title: '<div class="share-modal-title"><i class="fas fa-share-alt"></i> مشاركة المشروع</div>',
                html: `
                    <div class="share-modal-text">
                        <p>كل مشاركة تزيد من فرص الترويج لمشروعك ووصوله إلى المزيد من العملاء!</p>
                        <p>شاركي الآن عبر:</p>
                    </div>
                    <div class="share-buttons">
                        <div class="share-button whatsapp-share" onclick="shareOnWhatsApp()">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="share-button instagram-share" onclick="shareOnInstagram()">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <div class="share-button facebook-share" onclick="shareOnFacebook()">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        <div class="share-button copy-share" onclick="copyProjectLink()">
                            <i class="fas fa-copy"></i>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true
            });
        }

        // مشاركة عبر واتساب
        function shareOnWhatsApp() {
            const shareUrl = '<?= $currentUrl ?>';
            const shareText = '<?= $projectTitle ?> - <?= $description ?>';
            window.open(`https://wa.me/?text=${encodeURIComponent(shareText + '\n' + shareUrl)}`, '_blank');
        }

        // مشاركة عبر إنستجرام
        function shareOnInstagram() {
            // Instagram doesn't support direct sharing, so we'll open the URL in a new tab
            const shareUrl = '<?= $currentUrl ?>';
            window.open('https://www.instagram.com/', '_blank');
        }

        // مشاركة عبر فيسبوك
        function shareOnFacebook() {
            const shareUrl = '<?= $currentUrl ?>';
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`, '_blank');
        }

        // نسخ رابط المشروع
        function copyProjectLink() {
            const shareUrl = '<?= $currentUrl ?>';
            navigator.clipboard.writeText(shareUrl).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'تم النسخ!',
                    text: 'تم نسخ رابط المشروع بنجاح',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }

        // معاينة الصور عند النقر عليها
        document.querySelectorAll('.gallery-image').forEach(img => {
            img.addEventListener('click', function() {
                Swal.fire({
                    imageUrl: this.src,
                    imageAlt: '<?= $projectTitle ?>',
                    showConfirmButton: false,
                    background: 'transparent',
                    backdrop: 'rgba(0,0,0,0.8)'
                });
            });
        });
    </script>
</body>
</html>