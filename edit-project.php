<?php
session_start();

// تحقق من صلاحيات المشرف
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// مسارات الملفات
$projectsFile = __DIR__ . '/projects.json';
$statsFile = __DIR__ . '/stats.json';
$imagesDir = __DIR__ . '/images/';

// إنشاء مجلد الصور إذا لم يكن موجوداً
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// قراءة المشاريع من الملف
$projects = file_exists($projectsFile) ? json_decode(file_get_contents($projectsFile), true) : [];

// قراءة الإحصائيات
$stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [
    'project_likes' => [],
    'project_visits' => []
];

// الحصول على معرف المشروع
$projectId = $_GET['id'] ?? null;
$project = null;

// البحث عن المشروع
foreach ($projects as $p) {
    if ($p['id'] == $projectId) {
        $project = $p;
        break;
    }
}

if (!$project) {
    header('Location: admin.php');
    exit();
}

// معالجة تحديث المشروع
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // معالجة الصور الجديدة
    if (!empty($_FILES['new_images']['tmp_name'][0])) {
        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['new_images']['name'][$key];
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('img_') . '.' . $fileExt;
                $destination = $imagesDir . $newFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $project['images'][] = [
                        'original_name' => $fileName,
                        'permanent_name' => $newFileName,
                        'permanent_url' => '/images/' . $newFileName
                    ];
                }
            }
        }
    }

    // تحديث بيانات المشروع
    $project['name'] = $_POST['project-name'] ?? $project['name'];
    $project['description'] = $_POST['project-description'] ?? $project['description'];
    $project['whatsapp'] = $_POST['whatsapp'] ?? $project['whatsapp'];
    $project['instagram'] = $_POST['instagram'] ?? $project['instagram'];
    $project['category'] = $_POST['category'] ?? $project['category'];
    $project['governorate'] = $_POST['governorate'] ?? $project['governorate'];

    // تحديث الإحصائيات
    $stats['project_likes'][$projectId] = (int)($_POST['likes'] ?? $stats['project_likes'][$projectId] ?? 0);
    $stats['project_visits'][$projectId] = (int)($_POST['visits'] ?? $stats['project_visits'][$projectId] ?? 0);

    // تحديث المشروع في المصفوفة الرئيسية
    foreach ($projects as &$p) {
        if ($p['id'] == $projectId) {
            $p = $project;
            break;
        }
    }

    // حفظ التغييرات
    file_put_contents($projectsFile, json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

    $_SESSION['success_message'] = 'تم تحديث المشروع بنجاح';
    header('Location: edit-project.php?id=' . $projectId);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مشروع - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff6b6b;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --edit: #17a2b8;
            --danger: #dc3545;
            --whatsapp: #25D366;
            --instagram: #E1306C;
            --food: #ff9a3c;
            --beauty: #ff6b9d;
            --fashion: #a78bfa;
            --sewing: #6bcebb;
            --decor: #f472b6;
            --shopping: #94a3b8;
            --photography: #60a5fa;
            --design: #a855f7;
            --natural: #84cc16;
            --kids: #f59e0b;
            --education: #10b981;
            --misc: #64748b;
            --home: #f97316;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: var(--dark);
        }
        
        header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .logo i {
            background: white;
            color: var(--primary);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            background: #ff5252;
        }
        
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .edit-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .edit-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-input {
            width: 60px;
            text-align: center;
            padding: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 120px;
        }
        
        .category-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .category-option {
            display: none;
        }
        
        .category-option + label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-option:checked + label {
            border-color: var(--primary);
            background: rgba(106, 17, 203, 0.1);
        }
        
        .category-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* ألوان خيارات الأقسام */
        .category-option.food + label { color: var(--food); border-color: rgba(255, 154, 60, 0.3); }
        .category-option.beauty + label { color: var(--beauty); border-color: rgba(255, 107, 157, 0.3); }
        .category-option.fashion + label { color: var(--fashion); border-color: rgba(167, 139, 250, 0.3); }
        .category-option.sewing + label { color: var(--sewing); border-color: rgba(107, 206, 187, 0.3); }
        .category-option.decor + label { color: var(--decor); border-color: rgba(244, 114, 182, 0.3); }
        .category-option.shopping + label { color: var(--shopping); border-color: rgba(148, 163, 184, 0.3); }
        .category-option.photography + label { color: var(--photography); border-color: rgba(96, 165, 250, 0.3); }
        .category-option.design + label { color: var(--design); border-color: rgba(168, 85, 247, 0.3); }
        .category-option.natural + label { color: var(--natural); border-color: rgba(132, 204, 22, 0.3); }
        .category-option.kids + label { color: var(--kids); border-color: rgba(245, 158, 11, 0.3); }
        .category-option.education + label { color: var(--education); border-color: rgba(16, 185, 129, 0.3); }
        .category-option.misc + label { color: var(--misc); border-color: rgba(100, 116, 139, 0.3); }
        .category-option.home + label { color: var(--home); border-color: rgba(249, 115, 22, 0.3); }
        
        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 15px 0;
        }
        
        .image-box {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            cursor: pointer;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--dark);
            text-decoration: none;
            margin: 5px 0;
        }
        
        .social-link i {
            font-size: 18px;
        }
        
        .social-link.whatsapp i {
            color: var(--whatsapp);
        }
        
        .social-link.instagram i {
            color: var(--instagram);
        }
        
        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: var(--secondary);
        }
        
        @media (max-width: 768px) {
            .edit-container {
                padding: 1rem;
            }
            
            .category-options {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-cog"></i>
                <span>لوحة تحكم المشرف</span>
            </div>
            <a href="admin.php?view=approved" class="back-btn">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </header>

    <div class="edit-container">
        <div class="edit-header">
            <h1><i class="fas fa-edit"></i> تعديل المشروع</h1>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="stats">
                <div class="stat-item">
                    <i class="fas fa-heart" style="color: var(--accent);"></i>
                    <span>
                        <input type="number" name="likes" class="stat-input" 
                               value="<?= $stats['project_likes'][$projectId] ?? 0 ?>"> إعجاب
                    </span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye" style="color: var(--primary);"></i>
                    <span>
                        <input type="number" name="visits" class="stat-input" 
                               value="<?= $stats['project_visits'][$projectId] ?? 0 ?>"> زيارة
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="project-name">اسم المشروع</label>
                <input type="text" id="project-name" name="project-name" class="form-control" 
                       value="<?= htmlspecialchars($project['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="project-description">وصف المشروع</label>
                <textarea id="project-description" name="project-description" class="form-control" 
                          required><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="project-governorate">المحافظة</label>
                <select id="project-governorate" name="governorate" class="form-control" required>
                    <option value="">اختر المحافظة</option>
                    <option value="عدن" <?= ($project['governorate'] ?? '') === 'عدن' ? 'selected' : '' ?>>عدن</option>
                    <option value="صنعاء" <?= ($project['governorate'] ?? '') === 'صنعاء' ? 'selected' : '' ?>>صنعاء</option>
                    <option value="تعز" <?= ($project['governorate'] ?? '') === 'تعز' ? 'selected' : '' ?>>تعز</option>
                    <option value="أبين" <?= ($project['governorate'] ?? '') === 'أبين' ? 'selected' : '' ?>>أبين</option>
                    <option value="لحج" <?= ($project['governorate'] ?? '') === 'لحج' ? 'selected' : '' ?>>لحج</option>
                    <option value="الضالع" <?= ($project['governorate'] ?? '') === 'الضالع' ? 'selected' : '' ?>>الضالع</option>
                    <option value="حضرموت" <?= ($project['governorate'] ?? '') === 'حضرموت' ? 'selected' : '' ?>>حضرموت</option>
                    <option value="الحديدة" <?= ($project['governorate'] ?? '') === 'الحديدة' ? 'selected' : '' ?>>الحديدة</option>
                    <option value="إب" <?= ($project['governorate'] ?? '') === 'إب' ? 'selected' : '' ?>>إب</option>
                    <option value="ذمار" <?= ($project['governorate'] ?? '') === 'ذمار' ? 'selected' : '' ?>>ذمار</option>
                    <option value="مأرب" <?= ($project['governorate'] ?? '') === 'مأرب' ? 'selected' : '' ?>>مأرب</option>
                    <option value="شبوة" <?= ($project['governorate'] ?? '') === 'شبوة' ? 'selected' : '' ?>>شبوة</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>وسائل التواصل</label>
                <div class="form-group">
                    <label for="whatsapp">واتساب</label>
                    <?php
                    $whatsapp = $project['whatsapp'] ?? '';
                    $whatsappNumber = preg_replace('/[^0-9]/', '', $whatsapp);
                    if (!str_starts_with($whatsappNumber, '967') && strlen($whatsappNumber) > 0) {
                        $whatsappNumber = '967' . ltrim($whatsappNumber, '0');
                    }
                    $whatsappLink = 'https://wa.me/' . $whatsappNumber;
                    ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" id="whatsapp" name="whatsapp" class="form-control" 
                               value="<?= htmlspecialchars($whatsapp) ?>" placeholder="رقم الواتساب">
                        <?php if (!empty($whatsapp)): ?>
                            <a href="<?= $whatsappLink ?>" target="_blank" class="social-link whatsapp">
                                <i class="fab fa-whatsapp"></i> تواصل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="instagram">إنستجرام</label>
                    <?php
                    $instagram = $project['instagram'] ?? '';
                    $instagramUsername = ltrim($instagram, '@');
                    $instagramLink = 'https://instagram.com/' . $instagramUsername;
                    ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" id="instagram" name="instagram" class="form-control" 
                               value="<?= htmlspecialchars($instagram) ?>" placeholder="@اسم المستخدم">
                        <?php if (!empty($instagram)): ?>
                            <a href="<?= $instagramLink ?>" target="_blank" class="social-link instagram">
                                <i class="fab fa-instagram"></i> تواصل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>الصور الحالية</label>
                <div class="current-images">
                    <?php foreach ($project['images'] as $image): ?>
                        <?php if (!empty($image['permanent_url'])): ?>
                            <div class="image-box">
                                <img src="<?= $image['permanent_url'] ?>" alt="صورة المشروع">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <label>إضافة صور جديدة</label>
                <div class="file-upload" onclick="document.getElementById('new_images').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>انقر لرفع الصور أو اسحبها وأفلتها هنا</p>
                    <input type="file" id="new_images" name="new_images[]" multiple 
                           style="display: none;" accept="image/*">
                </div>
                <div id="file-names" style="margin-top: 10px;"></div>
            </div>
            
            <div class="form-group">
                <label>اختر القسم</label>
                <div class="category-options">
                    <input type="radio" id="category-food" name="category" value="food" 
                           class="category-option food" <?= ($project['category'] ?? '') === 'food' ? 'checked' : '' ?>>
                    <label for="category-food">
                        <i class="fas fa-utensils category-icon"></i>
                        <span>الطعام والمأكولات</span>
                    </label>
                    
                    <input type="radio" id="category-beauty" name="category" value="beauty" 
                           class="category-option beauty" <?= ($project['category'] ?? '') === 'beauty' ? 'checked' : '' ?>>
                    <label for="category-beauty">
                        <i class="fas fa-spa category-icon"></i>
                        <span>التجميل والعناية</span>
                    </label>
                    
                    <input type="radio" id="category-fashion" name="category" value="fashion" 
                           class="category-option fashion" <?= ($project['category'] ?? '') === 'fashion' ? 'checked' : '' ?>>
                    <label for="category-fashion">
                        <i class="fas fa-tshirt category-icon"></i>
                        <span>الموضة والأزياء</span>
                    </label>
                    
                    <input type="radio" id="category-sewing" name="category" value="sewing" 
                           class="category-option sewing" <?= ($project['category'] ?? '') === 'sewing' ? 'checked' : '' ?>>
                    <label for="category-sewing">
                        <i class="fas fa-cut category-icon"></i>
                        <span>الخياطة</span>
                    </label>
                    
                    <input type="radio" id="category-decor" name="category" value="decor" 
                           class="category-option decor" <?= ($project['category'] ?? '') === 'decor' ? 'checked' : '' ?>>
                    <label for="category-decor">
                        <i class="fas fa-couch category-icon"></i>
                        <span>الديكور والمفروشات</span>
                    </label>
                    
                    <input type="radio" id="category-shopping" name="category" value="shopping" 
                           class="category-option shopping" <?= ($project['category'] ?? '') === 'shopping' ? 'checked' : '' ?>>
                    <label for="category-shopping">
                        <i class="fas fa-shopping-bag category-icon"></i>
                        <span>التسوق العام</span>
                    </label>
                    
                    <input type="radio" id="category-photography" name="category" value="photography" 
                           class="category-option photography" <?= ($project['category'] ?? '') === 'photography' ? 'checked' : '' ?>>
                    <label for="category-photography">
                        <i class="fas fa-camera category-icon"></i>
                        <span>التصوير</span>
                    </label>
                    
                    <input type="radio" id="category-design" name="category" value="design" 
                           class="category-option design" <?= ($project['category'] ?? '') === 'design' ? 'checked' : '' ?>>
                    <label for="category-design">
                        <i class="fas fa-palette category-icon"></i>
                        <span>التصميم</span>
                    </label>
                    
                    <input type="radio" id="category-natural" name="category" value="natural" 
                           class="category-option natural" <?= ($project['category'] ?? '') === 'natural' ? 'checked' : '' ?>>
                    <label for="category-natural">
                        <i class="fas fa-leaf category-icon"></i>
                        <span>المنتجات الطبيعية</span>
                    </label>
                    
                    <input type="radio" id="category-kids" name="category" value="kids" 
                           class="category-option kids" <?= ($project['category'] ?? '') === 'kids' ? 'checked' : '' ?>>
                    <label for="category-kids">
                        <i class="fas fa-baby category-icon"></i>
                        <span>الأطفال والرضّع</span>
                    </label>
                    
                    <input type="radio" id="category-education" name="category" value="education" 
                           class="category-option education" <?= ($project['category'] ?? '') === 'education' ? 'checked' : '' ?>>
                    <label for="category-education">
                        <i class="fas fa-graduation-cap category-icon"></i>
                        <span>التعليم والتدريب</span>
                    </label>
                    
                    <input type="radio" id="category-home" name="category" value="home" 
                           class="category-option home" <?= ($project['category'] ?? '') === 'home' ? 'checked' : '' ?>>
                    <label for="category-home">
                        <i class="fas fa-home category-icon"></i>
                        <span>أدوات منزلية</span>
                    </label>
                    
                    <input type="radio" id="category-misc" name="category" value="misc" 
                           class="category-option misc" <?= ($project['category'] ?? '') === 'misc' ? 'checked' : '' ?>>
                    <label for="category-misc">
                        <i class="fas fa-ellipsis-h category-icon"></i>
                        <span>منتجات متنوعة</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> حفظ التغييرات
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // عرض أسماء الملفات المختارة
        document.getElementById('new_images').addEventListener('change', function(e) {
            const fileNames = document.getElementById('file-names');
            const files = e.target.files;
            let names = '';
            
            for (let i = 0; i < files.length; i++) {
                names += `<div><i class="fas fa-paperclip"></i> ${files[i].name}</div>`;
            }
            
            fileNames.innerHTML = names || '';
        });
        
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: '<?= $_SESSION['success_message'] ?>',
                timer: 2000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>