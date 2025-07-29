<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$tempDir = __DIR__ . '/temp_uploads/';
$permanentDir = __DIR__ . '/images/';

if (!file_exists($permanentDir)) {
    if (!mkdir($permanentDir, 0755, true)) {
        die(json_encode(['success' => false, 'message' => 'فشل إنشاء مجلد الصور']));
    }
}

$response = ['success' => false, 'message' => '', 'permanent_files' => []];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['temp_files'])) {
        throw new Exception('بيانات غير صالحة');
    }

    foreach ($input['temp_files'] as $tempFile) {
        $tempPath = $tempDir . $tempFile['temp_name'];
        $permanentPath = $permanentDir . $tempFile['temp_name'];
        
        if (file_exists($tempPath)) {
            if (rename($tempPath, $permanentPath)) {
                $response['permanent_files'][] = [
                    'original_name' => $tempFile['original_name'],
                    'permanent_name' => $tempFile['temp_name'],
                    'permanent_url' => '/images/' . $tempFile['temp_name'] // مسار للعرض في الموقع
                ];
            } else {
                throw new Exception('فشل في نقل الملف: ' . $tempFile['original_name']);
            }
        } else {
            throw new Exception('الملف غير موجود: ' . $tempFile['original_name']);
        }
    }

    $response['success'] = true;
    $response['message'] = 'تمت الموافقة على المشروع بنجاح';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    foreach ($response['permanent_files'] as $file) {
        if (file_exists($permanentDir . $file['permanent_name'])) {
            rename($permanentDir . $file['permanent_name'], $tempDir . $file['permanent_name']);
        }
    }
    $response['permanent_files'] = [];
}

echo json_encode($response);
?>