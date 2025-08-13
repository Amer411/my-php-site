<?php
session_start();
header('Content-Type: application/json');

// تحقق من صلاحيات المشرف
if(!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسموح بالوصول']);
    exit();
}

$projectsFile = __DIR__ . '/projects.json';
$projects = json_decode(file_get_contents($projectsFile), true) ?: [];

$action = $_POST['action'] ?? '';
$projectId = $_POST['project_id'] ?? null;

$response = ['success' => false, 'message' => 'إجراء غير معروف'];

try {
    switch($action) {
        case 'delete_image':
            $imageIndex = $_POST['image_index'] ?? null;
            
            foreach($projects as &$project) {
                if($project['id'] == $projectId && isset($project['images'][$imageIndex])) {
                    // حذف الصورة من السيرفر
                    $imagePath = __DIR__ . '/images/' . $project['images'][$imageIndex]['permanent_name'];
                    if(file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    // حذف الصورة من المصفوفة
                    array_splice($project['images'], $imageIndex, 1);
                    
                    file_put_contents($projectsFile, json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    $response = ['success' => true];
                    break;
                }
            }
            break;
            
        case 'replace_image':
            $imageIndex = $_POST['image_index'] ?? null;
            $imageFile = $_FILES['image'] ?? null;
            
            if($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                foreach($projects as &$project) {
                    if($project['id'] == $projectId && isset($project['images'][$imageIndex])) {
                        // حذف الصورة القديمة
                        $oldImagePath = __DIR__ . '/images/' . $project['images'][$imageIndex]['permanent_name'];
                        if(file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                        
                        // رفع الصورة الجديدة
                        $fileName = $imageFile['name'];
                        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                        $newFileName = uniqid('img_') . '.' . $fileExt;
                        $destination = __DIR__ . '/images/' . $newFileName;
                        
                        if(move_uploaded_file($imageFile['tmp_name'], $destination)) {
                            // تحديث بيانات الصورة
                            $project['images'][$imageIndex] = [
                                'original_name' => $fileName,
                                'permanent_name' => $newFileName,
                                'permanent_url' => '/images/' . $newFileName
                            ];
                            
                            file_put_contents($projectsFile, json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                            $response = ['success' => true];
                        } else {
                            $response = ['success' => false, 'message' => 'فشل في رفع الصورة'];
                        }
                        break;
                    }
                }
            } else {
                $response = ['success' => false, 'message' => 'لم يتم اختيار صورة صالحة'];
            }
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>