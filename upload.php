<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// مسارات الملفات
$uploadDir = __DIR__ . '/temp_uploads/';
$permanentDir = __DIR__ . '/images/';
$projectsFile = __DIR__ . '/projects.json';

// إنشاء المجلدات إذا لم تكن موجودة
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die(json_encode(['success' => false, 'message' => 'فشل في إنشاء مجلد التحميل المؤقت']));
    }
}

if (!file_exists($permanentDir)) {
    if (!mkdir($permanentDir, 0755, true)) {
        die(json_encode(['success' => false, 'message' => 'فشل في إنشاء مجلد الصور الدائم']));
    }
}

// إنشاء ملف المشاريع إذا لم يكن موجوداً
if (!file_exists($projectsFile)) {
    if (!file_put_contents($projectsFile, '[]')) {
        die(json_encode(['success' => false, 'message' => 'فشل في إنشاء ملف المشاريع']));
    }
}

$response = ['success' => false, 'message' => ''];

try {
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة الطلب غير مسموحة');
    }

    // التحقق من وجود البيانات المطلوبة
    if (empty($_POST['project-name']) || empty($_POST['project-description']) || empty($_POST['governorate'])) {
        throw new Exception('البيانات المطلوبة غير مكتملة');
    }

    // استقبال البيانات
    $projectName = trim($_POST['project-name']);
    $description = trim($_POST['project-description']);
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $category = trim($_POST['category'] ?? 'other');
    $governorate = trim($_POST['governorate']);

    // التحقق من الملفات المرفوعة
    if (empty($_FILES['images']) || !is_array($_FILES['images']['tmp_name'])) {
        throw new Exception('لم يتم رفع أي صور للمشروع');
    }

    // معالجة الملفات
    $uploadedFiles = [];
    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        // التحقق من نوع الملف
        $fileType = mime_content_type($tmpName);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }

        // التحقق من حجم الملف (5MB كحد أقصى)
        if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
            continue;
        }

        // إنشاء اسم فريد للملف
        $fileName = $_FILES['images']['name'][$key];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('img_') . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $destination)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'temp_name' => $newFileName,
                'permanent_url' => ''
            ];
        }
    }

    if (empty($uploadedFiles)) {
        throw new Exception('يجب رفع صورة واحدة على الأقل');
    }

    // قراءة المشاريع الحالية
    $projects = json_decode(file_get_contents($projectsFile), true);
    if ($projects === null) {
        $projects = [];
    }

    // إضافة المشروع الجديد
    $newProject = [
        'id' => uniqid(),
        'name' => $projectName,
        'description' => $description,
        'whatsapp' => $whatsapp,
        'instagram' => $instagram,
        'category' => $category,
        'governorate' => $governorate,
        'images' => $uploadedFiles,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $projects[] = $newProject;

    // حفظ البيانات
    if (file_put_contents($projectsFile, json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        $response['success'] = true;
        $response['message'] = 'تم رفع المشروع بنجاح وهو قيد المراجعة';
    } else {
        throw new Exception('فشل في حفظ البيانات في ملف المشاريع');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // حذف الملفات التي تم رفعها في حالة حدوث خطأ
    if (!empty($uploadedFiles)) {
        foreach ($uploadedFiles as $file) {
            if (file_exists($uploadDir . $file['temp_name'])) {
                @unlink($uploadDir . $file['temp_name']);
            }
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>