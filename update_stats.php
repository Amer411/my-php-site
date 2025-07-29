<?php
header('Content-Type: application/json');

$statsFile = __DIR__ . '/stats.json';

if (!file_exists($statsFile)) {
    echo json_encode(['error' => 'Stats file not found']);
    exit;
}

$stats = json_decode(file_get_contents($statsFile), true) ?: [];

if (isset($_GET['project_id'])) {
    $projectId = $_GET['project_id'];
    echo json_encode([
        'visits' => $stats['project_visits'][$projectId] ?? 0,
        'likes' => $stats['project_likes'][$projectId] ?? 0
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>