<?php
session_start();

// تحقق من صلاحيات المشرف
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// مسار ملف المشاريع
$projectsFile = __DIR__ . '/projects.json';

// قراءة المشاريع من الملف
$projects = [];
if(file_exists($projectsFile)) {
    $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
}

// فلتر المشاريع حسب الحالة
$pending_projects = array_filter($projects, function($project) {
    return $project['status'] === 'pending';
});

$approved_projects = array_filter($projects, function($project) {
    return $project['status'] === 'approved';
});

$rejected_projects = array_filter($projects, function($project) {
    return $project['status'] === 'rejected';
});

// فلترة حسب القسم إذا تم تحديده
if(isset($_GET['category']) && in_array($_GET['category'], ['beauty', 'food', 'handmade', 'fashion', 'other'])) {
    $pending_projects = array_filter($pending_projects, function($project) {
        return $project['category'] === $_GET['category'];
    });
    
    $approved_projects = array_filter($approved_projects, function($project) {
        return $project['category'] === $_GET['category'];
    });
    
    $rejected_projects = array_filter($rejected_projects, function($project) {
        return $project['category'] === $_GET['category'];
    });
}

// معالجة طلبات الموافقة/الرفض/الحذف
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $action = $_POST['action'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    if($action === 'delete') {
        // حذف المشروع
        $projects = array_filter($projects, function($project) use ($project_id) {
            return $project['id'] !== $project_id;
        });
    } else {
        foreach($projects as &$project) {
            if($project['id'] == $project_id) {
                if($action === 'approve') {
                    $project['status'] = 'approved';
                    
                    // إنشاء مجلد الصور إذا لم يكن موجوداً
                    $imagesDir = __DIR__ . '/images/';
                    if (!file_exists($imagesDir)) {
                        mkdir($imagesDir, 0755, true);
                    }
                    
                    // نقل الملفات من temp_uploads إلى images
                    foreach($project['images'] as &$image) {
                        $tempPath = __DIR__ . '/temp_uploads/' . $image['temp_name'];
                        $newPath = __DIR__ . '/images/' . $image['temp_name'];
                        
                        if(file_exists($tempPath)) {
                            if(rename($tempPath, $newPath)) {
                                $image['permanent_url'] = '/images/' . $image['temp_name'];
                            }
                        }
                    }
                    
                } elseif($action === 'reject') {
                    $project['status'] = 'rejected';
                    $project['reject_reason'] = $reason;
                    
                    // حذف الملفات المؤقتة
                    foreach($project['images'] as $image) {
                        $tempPath = __DIR__ . '/temp_uploads/' . $image['temp_name'];
                        if(file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                    }
                }
                break;
            }
        }
    }
    
    // حفظ التغييرات في الملف
    file_put_contents($projectsFile, json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // إعادة تحميل الصفحة
    header('Location: admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - إدارة المشاريع</title>
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
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
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
        
        .logout-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #ff5252;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }
        
        .content-header h1 {
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .category-filter {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .category-filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .category-filter-btn.active {
            border-color: var(--primary);
            background: rgba(106, 17, 203, 0.1);
        }
        
        .category-filter-btn.beauty {
            color: var(--beauty);
            border-color: rgba(255, 107, 157, 0.3);
        }
        
        .category-filter-btn.food {
            color: var(--food);
            border-color: rgba(255, 154, 60, 0.3);
        }
        
        .category-filter-btn.handmade {
            color: var(--handmade);
            border-color: rgba(107, 206, 187, 0.3);
        }
        
        .category-filter-btn.fashion {
            color: var(--fashion);
            border-color: rgba(167, 139, 250, 0.3);
        }
        
        .category-filter-btn.other {
            color: var(--other);
            border-color: rgba(148, 163, 184, 0.3);
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1rem;
        }
        
        .tab {
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
        }
        
        .tab:hover:not(.active) {
            background: #e9ecef;
        }
        
        .projects-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .projects-table th, 
        .projects-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .projects-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .projects-table tr:hover {
            background: #f8f9fa;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-info {
            background: #e7f5ff;
            color: #1864ab;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-approve {
            background: var(--success);
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: var(--danger);
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .project-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin: 2px;
            transition: transform 0.3s;
        }
        
        .project-image:hover {
            transform: scale(1.5);
            z-index: 10;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .category-badge.category-beauty {
            background-color: rgba(255, 107, 157, 0.2);
            color: var(--beauty);
        }
        
        .category-badge.category-food {
            background-color: rgba(255, 154, 60, 0.2);
            color: var(--food);
        }
        
        .category-badge.category-handmade {
            background-color: rgba(107, 206, 187, 0.2);
            color: var(--handmade);
        }
        
        .category-badge.category-fashion {
            background-color: rgba(167, 139, 250, 0.2);
            color: var(--fashion);
        }
        
        .category-badge.category-other {
            background-color: rgba(148, 163, 184, 0.2);
            color: var(--other);
        }
        
        @media (max-width: 768px) {
            .projects-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
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
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </div>
    </header>

    <div class="dashboard">
        <div class="tabs">
            <div class="tab <?= !isset($_GET['view']) ? 'active' : '' ?>" onclick="window.location.href='admin.php'">
                <i class="fas fa-clock"></i> المشاريع المعلقة
            </div>
            <div class="tab <?= isset($_GET['view']) && $_GET['view'] === 'approved' ? 'active' : '' ?>" onclick="window.location.href='admin.php?view=approved'">
                <i class="fas fa-check-circle"></i> المشاريع المعتمدة
            </div>
            <div class="tab <?= isset($_GET['view']) && $_GET['view'] === 'rejected' ? 'active' : '' ?>" onclick="window.location.href='admin.php?view=rejected'">
                <i class="fas fa-times-circle"></i> المشاريع المرفوضة
            </div>
        </div>
        
        <!-- فلترة حسب الأقسام -->
        <div class="category-filter">
            <button class="category-filter-btn <?= !isset($_GET['category']) ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'] : '' ?>'">
                الكل
            </button>
            <button class="category-filter-btn beauty <?= isset($_GET['category']) && $_GET['category'] === 'beauty' ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'].'&' : '?' ?>category=beauty'">
                <i class="fas fa-spa"></i> التجميل
            </button>
            <button class="category-filter-btn food <?= isset($_GET['category']) && $_GET['category'] === 'food' ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'].'&' : '?' ?>category=food'">
                <i class="fas fa-utensils"></i> الطعام
            </button>
            <button class="category-filter-btn handmade <?= isset($_GET['category']) && $_GET['category'] === 'handmade' ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'].'&' : '?' ?>category=handmade'">
                <i class="fas fa-hands"></i> الأشغال اليدوية
            </button>
            <button class="category-filter-btn fashion <?= isset($_GET['category']) && $_GET['category'] === 'fashion' ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'].'&' : '?' ?>category=fashion'">
                <i class="fas fa-tshirt"></i> الموضة
            </button>
            <button class="category-filter-btn other <?= isset($_GET['category']) && $_GET['category'] === 'other' ? 'active' : '' ?>" onclick="window.location.href='admin.php<?= isset($_GET['view']) ? '?view='.$_GET['view'].'&' : '?' ?>category=other'">
                <i class="fas fa-ellipsis-h"></i> أخرى
            </button>
        </div>
        
        <div class="main-content">
            <?php if(isset($_GET['view']) && $_GET['view'] === 'approved'): ?>
                <div class="content-header">
                    <h1><i class="fas fa-check-circle"></i> المشاريع المعتمدة</h1>
                </div>
                
                <?php if(empty($approved_projects)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد مشاريع معتمدة حالياً
                    </div>
                <?php else: ?>
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>اسم المشروع</th>
                                <th>الوصف</th>
                                <th>القسم</th>
                                <th>وسائل التواصل</th>
                                <th>الصور</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approved_projects as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['name'] ?? 'بدون اسم') ?></td>
                                <td><?= htmlspecialchars(mb_substr($project['description'] ?? 'لا يوجد وصف', 0, 100)) ?>...</td>
                                <td>
                                    <span class="category-badge category-<?= htmlspecialchars($project['category'] ?? 'other') ?>">
                                        <?php 
                                        switch($project['category'] ?? 'other') {
                                            case 'beauty': echo 'التجميل'; break;
                                            case 'food': echo 'الطعام'; break;
                                            case 'handmade': echo 'الأشغال اليدوية'; break;
                                            case 'fashion': echo 'الموضة'; break;
                                            default: echo 'أخرى';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(!empty($project['whatsapp'])): ?>
                                        <div><i class="fab fa-whatsapp" style="color: var(--success);"></i> <?= htmlspecialchars($project['whatsapp']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($project['instagram'])): ?>
                                        <div><i class="fab fa-instagram" style="color: var(--instagram);"></i> <?= htmlspecialchars($project['instagram']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach($project['images'] as $image): ?>
                                        <?php if(!empty($image['permanent_url'])): ?>
                                            <img src="<?= $image['permanent_url'] ?>" class="project-image" alt="صورة المشروع">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
            <?php elseif(isset($_GET['view']) && $_GET['view'] === 'rejected'): ?>
                <div class="content-header">
                    <h1><i class="fas fa-times-circle"></i> المشاريع المرفوضة</h1>
                </div>
                
                <?php if(empty($rejected_projects)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد مشاريع مرفوضة حالياً
                    </div>
                <?php else: ?>
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>اسم المشروع</th>
                                <th>القسم</th>
                                <th>سبب الرفض</th>
                                <th>تاريخ الرفض</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rejected_projects as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['name'] ?? 'بدون اسم') ?></td>
                                <td>
                                    <span class="category-badge category-<?= htmlspecialchars($project['category'] ?? 'other') ?>">
                                        <?php 
                                        switch($project['category'] ?? 'other') {
                                            case 'beauty': echo 'التجميل'; break;
                                            case 'food': echo 'الطعام'; break;
                                            case 'handmade': echo 'الأشغال اليدوية'; break;
                                            case 'fashion': echo 'الموضة'; break;
                                            default: echo 'أخرى';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($project['reject_reason'] ?? 'لا يوجد سبب') ?></td>
                                <td><?= $project['created_at'] ?? 'غير معروف' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="content-header">
                    <h1><i class="fas fa-clock"></i> المشاريع المعلقة</h1>
                </div>
                
                <?php if(empty($pending_projects)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد مشاريع معلقة حالياً
                    </div>
                <?php else: ?>
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>اسم المشروع</th>
                                <th>الوصف</th>
                                <th>القسم</th>
                                <th>وسائل التواصل</th>
                                <th>الصور</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_projects as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['name'] ?? 'بدون اسم') ?></td>
                                <td><?= htmlspecialchars(mb_substr($project['description'] ?? 'لا يوجد وصف', 0, 100)) ?>...</td>
                                <td>
                                    <span class="category-badge category-<?= htmlspecialchars($project['category'] ?? 'other') ?>">
                                        <?php 
                                        switch($project['category'] ?? 'other') {
                                            case 'beauty': echo 'التجميل'; break;
                                            case 'food': echo 'الطعام'; break;
                                            case 'handmade': echo 'الأشغال اليدوية'; break;
                                            case 'fashion': echo 'الموضة'; break;
                                            default: echo 'أخرى';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(!empty($project['whatsapp'])): ?>
                                        <div><i class="fab fa-whatsapp" style="color: var(--success);"></i> <?= htmlspecialchars($project['whatsapp']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($project['instagram'])): ?>
                                        <div><i class="fab fa-instagram" style="color: var(--instagram);"></i> <?= htmlspecialchars($project['instagram']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach($project['images'] as $image): ?>
                                        <?php if(!empty($image['temp_name'])): ?>
                                            <img src="<?= '/temp_uploads/' . $image['temp_name'] ?>" class="project-image" alt="صورة المشروع">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من الموافقة على هذا المشروع؟')">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve">
                                                <i class="fas fa-check"></i> قبول
                                            </button>
                                        </form>
                                        <form method="POST" id="reject-form-<?= $project['id'] ?>">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="reason" id="reject-reason-<?= $project['id'] ?>">
                                            <button type="button" class="btn btn-reject" onclick="showRejectDialog('<?= $project['id'] ?>')">
                                                <i class="fas fa-times"></i> رفض
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟')">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function showRejectDialog(projectId) {
        Swal.fire({
            title: 'سبب الرفض',
            input: 'text',
            inputPlaceholder: 'أدخل سبب الرفض...',
            showCancelButton: true,
            confirmButtonText: 'رفض',
            cancelButtonText: 'إلغاء',
            icon: 'warning'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reject-reason-' + projectId).value = result.value;
                document.getElementById('reject-form-' + projectId).submit();
            }
        });
    }
    </script>
</body>
</html>