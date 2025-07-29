<?php
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
        'project_likes' => []
    ]));
}

// قراءة إحصائيات الموقع
$stats = json_decode(file_get_contents($statsFile), true) ?: [];

// تحديث عدد زيارات المشروع
$stats['project_visits'][$projectId] = ($stats['project_visits'][$projectId] ?? 0) + 1;
file_put_contents($statsFile, json_encode($stats));

// معالجة طلب الإعجاب
if (isset($_GET['like'])) {
    $stats['project_likes'][$projectId] = ($stats['project_likes'][$projectId] ?? 0) + 1;
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

// إعداد بيانات المشاركة
$baseUrl = 'https://my-php-site-hma1.onrender.com';
$currentUrl = $baseUrl . '/project-details.php?id=' . urlencode($projectId);
$projectTitle = htmlspecialchars($project['name'] ?? 'مشروع التاجرة');
$description = htmlspecialchars(mb_substr(strip_tags($project['description'] ?? 'وصف مشروع التاجرة'), 0, 160));

// معالجة رابط الصورة
$imageUrl = $baseUrl . '/mnsah.jpg'; // صورة افتراضية
if (!empty($project['images'][0]['permanent_url'])) {
    $imageUrl = $project['images'][0]['permanent_url'];
    // إذا كان الرابط نسبيًا، أضف عنوان الموقع الأساسي
    if (strpos($imageUrl, 'http') !== 0) {
        $imageUrl = $baseUrl . '/' . ltrim($imageUrl, '/');
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
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta property="og:image:secure_url" content="<?= $imageUrl ?>">
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
    <meta name="twitter:image" content="<?= $imageUrl ?>">

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
            --light: #f8f9fa;
            --dark: #212529;
            --beauty: #ff6b9d;
            --food: #ff9a3c;
            --handmade: #6bcebb;
            --fashion: #a78bfa;
            --other: #94a3b8;
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

        .project-image-container {
            width: 100%;
            height: auto;
            max-height: 500px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f5f5;
        }
            
        .project-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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

            .project-image-container {
                max-height: 300px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="project-header">
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

        <?php if(!empty($project['images'][0]['permanent_url'])): ?>
            <div class="project-image-container">
                <img src="<?= htmlspecialchars($imageUrl) ?>" class="project-image" alt="<?= $projectTitle ?>" loading="lazy">
            </div>
        <?php endif; ?>
    </div>

    <div class="project-description">
        <?= nl2br(htmlspecialchars($project['description'] ?? 'لا يوجد وصف متاح')) ?>
    </div>

    <?php if(!empty($project['images']) && count($project['images']) > 1): ?>
        <div class="image-gallery">
            <?php foreach($project['images'] as $image): ?>
                <?php 
                $imgUrl = $image['permanent_url'];
                if (strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = $baseUrl . '/' . ltrim($imgUrl, '/');
                }
                ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" class="gallery-image" alt="<?= $projectTitle ?>" loading="lazy">
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
                    likesCount.textContent = parseInt(likesCount.textContent) + 1;
                    
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

        // مشاركة المشروع
        function shareProject() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= $projectTitle ?>',
                    text: '<?= $description ?>',
                    url: '<?= $currentUrl ?>'
                }).catch(err => {
                    console.log('Error sharing:', err);
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                Swal.fire({
                    title: 'مشاركة المشروع',
                    text: 'انسخ الرابط التالي لمشاركة هذا المشروع:',
                    input: 'text',
                    inputValue: '<?= $currentUrl ?>',
                    showCancelButton: true,
                    confirmButtonText: 'نسخ الرابط',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        navigator.clipboard.writeText('<?= $currentUrl ?>');
                        Swal.fire({
                            icon: 'success',
                            title: 'تم النسخ!',
                            text: 'تم نسخ رابط المشروع بنجاح',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            }
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