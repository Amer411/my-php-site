<?php
// قراءة المشاريع المعتمدة
$projectsFile = __DIR__ . '/projects.json';
$statsFile = __DIR__ . '/stats.json';
$approved_projects = [];
$categories = [];
$governorates = [];

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
$stats['site_visits'] = ($stats['site_visits'] ?? 0) + 1;
file_put_contents($statsFile, json_encode($stats));

// معالجة طلب الإعجاب إذا كان موجوداً
if (isset($_GET['like_project'])) {
    $projectId = $_GET['like_project'];
    $stats['project_likes'][$projectId] = ($stats['project_likes'][$projectId] ?? 0) + 1;
    file_put_contents($statsFile, json_encode($stats));
    header('Location: ' . str_replace('?like_project='.$projectId, '', $_SERVER['REQUEST_URI']));
    exit;
}

if (file_exists($projectsFile)) {
    $all_projects = json_decode(file_get_contents($projectsFile), true) ?: [];
    $approved_projects = array_filter($all_projects, function($project) {
        return $project['status'] === 'approved';
    });
    
    // استخراج الأقسام الفريدة
    $categories = array_unique(array_reduce($all_projects, function($carry, $project) {
        if (!empty($project['category'])) {
            $carry[] = $project['category'];
        }
        return $carry;
    }, []));
    
    // استخراج المحافظات الفريدة
    $governorates = array_unique(array_reduce($all_projects, function($carry, $project) {
        if (!empty($project['governorate'])) {
            $carry[] = $project['governorate'];
        }
        return $carry;
    }, []));
    
    // ترتيب المشاريع حسب عدد الإعجابات (الأعلى أولاً)
    usort($approved_projects, function($a, $b) use ($stats) {
        $likesA = $stats['project_likes'][$a['id']] ?? 0;
        $likesB = $stats['project_likes'][$b['id']] ?? 0;
        return $likesB - $likesA;
    });
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title>مشاريع التاجرات اليمنيات | منصة لدعم الأعمال النسائية</title>
    <meta name="description" content="منصة متخصصة لعرض مشاريع التاجرات ورواد الأعمال النسائية في اليمن">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://my-php-site-hma1.onrender.com/" />
    <meta property="og:title" content="مشاريع التاجرات اليمنيات" />
    <meta property="og:description" content="منصة لدعم وتمكين التاجرات اليمنيات لعرض مشاريعهن التجارية" />
    <meta property="og:image" itemprop="image" content="https://my-php-site-hma1.onrender.com/mnsah.jpg" />
    <meta property="og:image:type" content="jpg" />
    <meta property="og:image:width" content="256" />
    <meta property="og:image:height" content="256" />
    <link rel="image_src" href="https://my-php-site-hma1.onrender.com/mnsah.jpg">
    <!-- Twitter -->
    <meta property="twitter:card" content="summary" />
    <meta property="twitter:url" content="https://my-php-site-hma1.onrender.com/" />
    <meta property="twitter:title" content="مشاريع التاجرات اليمنيات" />
    <meta property="twitter:description" content="منصة لدعم وتمكين التاجرات اليمنيات لعرض مشاريعهن التجارية" />
    <meta property="twitter:image" content="https://my-php-site-hma1.onrender.com/mnsah.jpg" />
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>  
    :root {  
    --primary: #6a11cb;  
    --secondary: #2575fc;  
    --accent: #ff6b6b;  
    --light: #f8f9fa;  
    --dark: #212529;  
    --success: #25D366;  
    --instagram: #E1306C;  
    --gray: #6c757d;  
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
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);  
    color: var(--dark);  
    min-height: 100vh;  
    }  
    
    header {  
    background: linear-gradient(to right, var(--primary), var(--secondary));  
    color: white;  
    padding: 1.5rem;  
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);  
    position: sticky;  
    top: 0;  
    z-index: 100;  
    }  
    
    nav {  
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
    font-size: 1.5rem;  
    font-weight: 700;  
    }  
    
    .logo i {  
    background: white;  
    color: var(--primary);  
    width: 40px;  
    height: 40px;  
    border-radius: 50%;  
    display: flex;  
    align-items: center;  
    justify-content: center;  
    }  
    
    .site-stats {  
    display: flex;  
    gap: 1rem;  
    font-size: 0.9rem;  
    color: rgba(255, 255, 255, 0.8);  
    margin-left: 1rem;  
    }  
    
    .site-stats span {  
    display: flex;  
    align-items: center;  
    gap: 0.3rem;  
    }  
    
    .nav-links {  
    display: flex;  
    gap: 1.5rem;  
    list-style: none;  
    }  
    
    .nav-links a {  
    color: white;  
    text-decoration: none;  
    font-weight: 500;  
    transition: all 0.3s;  
    padding: 0.5rem 1rem;  
    border-radius: 50px;  
    }  
    
    .nav-links a:hover {  
    background: rgba(255, 255, 255, 0.2);  
    }  
    
    .btn {  
    background: var(--accent);  
    color: white;  
    border: none;  
    padding: 0.75rem 1.5rem;  
    border-radius: 50px;  
    font-weight: 600;  
    cursor: pointer;  
    transition: all 0.3s;  
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);  
    text-decoration: none;  
    display: inline-block;  
    }  
    
    .btn:hover {  
    transform: translateY(-3px);  
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);  
    }  
    
    .hero {  
    text-align: center;  
    padding: 4rem 2rem;  
    max-width: 1200px;  
    margin: 0 auto;  
    background: linear-gradient(to right, var(--primary), var(--secondary));  
    border-radius: 20px;  
    color: white;  
    margin-top: 20px;  
    margin-bottom: 40px;  
    }  
    
    .hero h1 {  
    font-size: 2.5rem;  
    line-height: 1.3;  
    margin-bottom: 1.5rem;  
    color: white;  
    }  
    
    .hero p {  
    font-size: 1.2rem;  
    line-height: 1.7;  
    margin-bottom: 2rem;  
    color: rgba(255, 255, 255, 0.9);  
    }  
    
    .filters-section {  
    max-width: 1200px;  
    margin: 2rem auto;  
    padding: 1rem;  
    background: white;  
    border-radius: 16px;  
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);  
    }  
    
    .filters-header {  
    display: flex;  
    justify-content: space-between;  
    align-items: center;  
    margin-bottom: 1.5rem;  
    }  
    
    .filters-header h3 {  
    font-size: 1.3rem;  
    color: var(--primary);  
    }  
    
    .filter-buttons {  
    display: flex;  
    gap: 0.5rem;  
    }  
    
    .filter-btn {  
    background: white;  
    border: 1px solid #ddd;  
    padding: 0.5rem 1rem;  
    border-radius: 50px;  
    cursor: pointer;  
    transition: all 0.3s;  
    }  
    
    .filter-btn:hover {  
    border-color: var(--primary);  
    color: var(--primary);  
    }  
    
    .filter-btn.active {  
    background: var(--primary);  
    color: white;  
    border-color: var(--primary);  
    }  
    
    .categories-container {  
    margin-bottom: 1.5rem;  
    }  
    
    .categories-title {  
    font-size: 1.2rem;  
    margin-bottom: 1rem;  
    color: var(--primary);  
    display: flex;  
    align-items: center;  
    gap: 0.5rem;  
    }  
    
    .categories-title i {  
    color: var(--primary);  
    }  
    
    .governorates-title {  
    font-size: 1.2rem;  
    margin-bottom: 1rem;  
    color: var(--secondary);  
    display: flex;  
    align-items: center;  
    gap: 0.5rem;  
    }  
    
    .governorates-title i {  
    color: var(--secondary);  
    }  
    
    .filters-grid {  
    display: grid;  
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));  
    gap: 0.5rem;  
    }  
    
    .filter-card {  
    background: white;  
    border-radius: 8px;  
    padding: 0.5rem;  
    text-align: center;  
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);  
    transition: all 0.3s;  
    cursor: pointer;  
    border: 1px solid #eee;  
    font-size: 0.85rem;  
    }  
    
    .filter-card:hover {  
    transform: translateY(-3px);  
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);  
    }  
    
    .filter-card i {  
    font-size: 1.2rem;  
    margin-bottom: 0.2rem;  
    }  
    
    .filter-card h3 {  
    font-size: 0.8rem;  
    margin: 0;  
    }  
    
    .filter-card.active {  
    border-color: var(--primary);  
    background: rgba(106, 17, 203, 0.05);  
    }  
    
    /* ألوان الأقسام */  
    .filter-card.food {  
    color: var(--food);  
    }  
    
    .filter-card.beauty {  
    color: var(--beauty);  
    }  
    
    .filter-card.fashion {  
    color: var(--fashion);  
    }  
    
    .filter-card.sewing {  
    color: var(--sewing);  
    }  
    
    .filter-card.decor {  
    color: var(--decor);  
    }  
    
    .filter-card.shopping {  
    color: var(--shopping);  
    }  
    
    .filter-card.photography {  
    color: var(--photography);  
    }  
    
    .filter-card.design {  
    color: var(--design);  
    }  
    
    .filter-card.natural {  
    color: var(--natural);  
    }  
    
    .filter-card.kids {  
    color: var(--kids);  
    }  
    
    .filter-card.education {  
    color: var(--education);  
    }  
    
    .filter-card.misc {  
    color: var(--misc);  
    }  
    
    .filter-card.home {  
    color: var(--home);  
    }  
    
    .projects-section {  
    max-width: 1200px;  
    margin: 3rem auto;  
    padding: 1.5rem;  
    }  
    
    .section-header {  
    text-align: center;  
    margin-bottom: 2rem;  
    }  
    
    .section-header h2 {  
    font-size: 2rem;  
    color: var(--primary);  
    margin-bottom: 1rem;  
    }  
    
    .section-header p {  
    font-size: 1rem;  
    color: var(--gray);  
    max-width: 700px;  
    margin: 0 auto;  
    }  
    
    .projects-grid {  
    display: grid;  
    grid-template-columns: repeat(2, minmax(0, 1fr));  
    gap: 15px;  
    width: 100%;  
    }  
    
    .project-card {  
    background: white;  
    border-radius: 12px;  
    overflow: hidden;  
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);  
    transition: all 0.3s ease;  
    display: flex;  
    flex-direction: column;  
    position: relative;  
    }  
    
    .project-card:hover {  
    transform: translateY(-5px);  
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);  
    }  
    
    .project-badge {  
    position: absolute;  
    top: 10px;  
    left: 10px;  
    background: var(--primary);  
    color: white;  
    padding: 0.2rem 0.6rem;  
    border-radius: 50px;  
    font-size: 0.7rem;  
    font-weight: 600;  
    z-index: 1;  
    }  
    
    .project-image {  
    height: 150px;  
    overflow: hidden;  
    flex-shrink: 0;  
    }  
    
    .project-image img {  
    width: 100%;  
    height: 100%;  
    object-fit: cover;  
    transition: transform 0.5s;  
    }  
    
    .project-card:hover .project-image img {  
    transform: scale(1.05);  
    }  
    
    .project-content {  
    padding: 1rem;  
    flex: 1;  
    display: flex;  
    flex-direction: column;  
    }  
    
    .project-title {  
    font-size: 1rem;  
    margin-bottom: 0rem;  
    color: var(--dark);  
    display: -webkit-box;  
    -webkit-line-clamp: 2;  
    -webkit-box-orient: vertical;  
    overflow: hidden;  
    line-height: 1.4;  
    font-weight: 600;  
    }  
    
    .project-description {  
    color: var(--gray);  
    line-height: 1.5;  
    margin-bottom: 0rem;  
    min-height: 0rem;  
    display: -webkit-box;  
    -webkit-line-clamp: 2;  
    -webkit-box-orient: vertical;  
    overflow: hidden;  
    font-size: 0.85rem;  
    }  
    
    .project-meta {  
    display: flex;  
    flex-wrap: wrap;  
    gap: 0.5rem;  
    margin-bottom: 0.8rem;  
    }  
    
    .project-category {  
    display: inline-block;  
    padding: 0.2rem 0.6rem;  
    border-radius: 50px;  
    font-size: 0.7rem;  
    font-weight: 600;  
    }  
    
    .project-governorate {  
    display: inline-block;  
    padding: 0.2rem 0.6rem;  
    border-radius: 50px;  
    font-size: 0.7rem;  
    font-weight: 600;  
    background: rgba(37, 117, 252, 0.2);  
    color: var(--secondary);  
    }  
    
    /* ألوان فئات المشاريع */  
    .category-food {  
    background-color: rgba(255, 154, 60, 0.2);  
    color: var(--food);  
    }  
    
    .category-beauty {  
    background-color: rgba(255, 107, 157, 0.2);  
    color: var(--beauty);  
    }  
    
    .category-fashion {  
    background-color: rgba(167, 139, 250, 0.2);  
    color: var(--fashion);  
    }  
    
    .category-sewing {  
    background-color: rgba(107, 206, 187, 0.2);  
    color: var(--sewing);  
    }  
    
    .category-decor {  
    background-color: rgba(244, 114, 182, 0.2);  
    color: var(--decor);  
    }  
    
    .category-shopping {  
    background-color: rgba(148, 163, 184, 0.2);  
    color: var(--shopping);  
    }  
    
    .category-photography {  
    background-color: rgba(96, 165, 250, 0.2);  
    color: var(--photography);  
    }  
    
    .category-design {  
    background-color: rgba(168, 85, 247, 0.2);  
    color: var(--design);  
    }  
    
    .category-natural {  
    background-color: rgba(132, 204, 22, 0.2);  
    color: var(--natural);  
    }  
    
    .category-kids {  
    background-color: rgba(245, 158, 11, 0.2);  
    color: var(--kids);  
    }  
    
    .category-education {  
    background-color: rgba(16, 185, 129, 0.2);  
    color: var(--education);  
    }  
    
    .category-misc {  
    background-color: rgba(100, 116, 139, 0.2);  
    color: var(--misc);  
    }  
    
    .category-home {  
    background-color: rgba(249, 115, 22, 0.2);  
    color: var(--home);  
    }  
    
    .project-stats {  
    display: flex;  
    gap: 0.8rem;  
    margin: 0.5rem 0;  
    font-size: 0.75rem;  
    color: var(--gray);  
    }  
    
    .stat-item {  
    display: flex;  
    align-items: center;  
    gap: 0.3rem;  
    }  
    
    .stat-item i {  
    color: var(--accent);  
    font-size: 0.8rem;  
    }  
    
    .project-actions {  
    display: flex;  
    align-items: center;  
    gap: 0.5rem;  
    margin-top: auto;  
    padding-top: 0.5rem;  
    }  
    
    .like-btn {  
    background: white;  
    color: var(--accent);  
    border: 1px solid var(--accent);  
    width: 32px;  
    height: 32px;  
    border-radius: 50%;  
    display: flex;  
    align-items: center;  
    justify-content: center;  
    cursor: pointer;  
    transition: all 0.3s;  
    font-size: 0.85rem;  
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
    
    .project-link {  
    display: inline-block;  
    padding: 0.5rem 1rem;  
    background: var(--primary);  
    color: white;  
    border-radius: 8px;  
    text-decoration: none;  
    transition: all 0.3s;  
    flex-grow: 1;  
    text-align: center;  
    font-size: 0.85rem;  
    font-weight: 500;  
    }  
    
    .project-link:hover {  
    background: var(--secondary);  
    }  
    
    .add-project {  
    max-width: 800px;  
    margin: 3rem auto;  
    padding: 2rem;  
    background: white;  
    border-radius: 20px;  
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);  
    }  
    
    .add-project h2 {  
    text-align: center;  
    margin-bottom: 1.5rem;  
    color: var(--primary);  
    font-size: 1.8rem;  
    }  
    
    .form-group {  
    margin-bottom: 1.2rem;  
    }  
    
    .form-group label {  
    display: block;  
    margin-bottom: 0.5rem;  
    font-weight: 500;  
    font-size: 0.95rem;  
    }  
    
    .form-control {  
    width: 100%;  
    padding: 0.8rem 1rem;  
    border: 1px solid #ddd;  
    border-radius: 12px;  
    font-size: 0.95rem;  
    transition: border 0.3s;  
    }  
    
    .form-control:focus {  
    outline: none;  
    border-color: var(--secondary);  
    box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.2);  
    }  
    
    textarea.form-control {  
    min-height: 120px;  
    resize: vertical;  
    }  
    
    .category-options {  
    display: grid;  
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));  
    gap: 0.6rem;  
    margin-top: 0.5rem;  
    }  
    
    .category-option {  
    display: none;  
    }  
    
    .category-option + label {  
    display: block;  
    padding: 0.5rem;  
    border-radius: 8px;  
    text-align: center;  
    cursor: pointer;  
    transition: all 0.3s;  
    border: 2px solid #eee;  
    font-size: 0.8rem;  
    font-weight: 500;  
    }  
    
    .category-option + label i {  
    font-size: 1.2rem;  
    margin-bottom: 0.2rem;  
    display: block;  
    }  
    
    .category-option:checked + label {  
    border-color: var(--primary);  
    background: rgba(106, 17, 203, 0.1);  
    }  
    
    /* ألوان خيارات الأقسام في النموذج */  
    .category-option.food + label {  
    color: var(--food);  
    border-color: rgba(255, 154, 60, 0.3);  
    }  
    
    .category-option.beauty + label {  
    color: var(--beauty);  
    border-color: rgba(255, 107, 157, 0.3);  
    }  
    
    .category-option.fashion + label {  
    color: var(--fashion);  
    border-color: rgba(167, 139, 250, 0.3);  
    }  
    
    .category-option.sewing + label {  
    color: var(--sewing);  
    border-color: rgba(107, 206, 187, 0.3);  
    }  
    
    .category-option.decor + label {  
    color: var(--decor);  
    border-color: rgba(244, 114, 182, 0.3);  
    }  
    
    .category-option.shopping + label {  
    color: var(--shopping);  
    border-color: rgba(148, 163, 184, 0.3);  
    }  
    
    .category-option.photography + label {  
    color: var(--photography);  
    border-color: rgba(96, 165, 250, 0.3);  
    }  
    
    .category-option.design + label {  
    color: var(--design);  
    border-color: rgba(168, 85, 247, 0.3);  
    }  
    
    .category-option.natural + label {  
    color: var(--natural);  
    border-color: rgba(132, 204, 22, 0.3);  
    }  
    
    .category-option.kids + label {  
    color: var(--kids);  
    border-color: rgba(245, 158, 11, 0.3);  
    }  
    
    .category-option.education + label {  
    color: var(--education);  
    border-color: rgba(16, 185, 129, 0.3);  
    }  
    
    .category-option.misc + label {  
    color: var(--misc);  
    border-color: rgba(100, 116, 139, 0.3);  
    }  
    
    .category-option.home + label {  
    color: var(--home);  
    border-color: rgba(249, 115, 22, 0.3);  
    }  
    
    .social-input-group {  
    display: flex;  
    align-items: center;  
    margin-bottom: 8px;  
    }  
    
    .social-input-group i {  
    width: 36px;  
    height: 36px;  
    display: flex;  
    align-items: center;  
    justify-content: center;  
    background: #f5f5f5;  
    border-radius: 8px 0 0 8px;  
    font-size: 1rem;  
    }  
    
    .social-input {  
    flex: 1;  
    border-radius: 0 8px 8px 0 !important;  
    padding: 0.7rem 1rem;  
    }  
    
    .file-upload {  
    border: 2px dashed #ddd;  
    border-radius: 12px;  
    padding: 1.5rem;  
    text-align: center;  
    cursor: pointer;  
    transition: all 0.3s;  
    }  
    
    .file-upload:hover {  
    border-color: var(--secondary);  
    background: #f8fbff;  
    }  
    
    .file-upload i {  
    font-size: 2.5rem;  
    color: var(--secondary);  
    margin-bottom: 0.8rem;  
    }  
    
    .file-upload p {  
    color: var(--gray);  
    font-size: 0.9rem;  
    }  
    
    .submit-btn {  
    background: linear-gradient(to right, var(--primary), var(--secondary));  
    color: white;  
    border: none;  
    padding: 0.9rem;  
    border-radius: 12px;  
    font-size: 1rem;  
    font-weight: 600;  
    cursor: pointer;  
    width: 100%;  
    margin-top: 0.8rem;  
    transition: all 0.3s;  
    position: relative;  
    }  
    
    .submit-btn:hover {  
    transform: translateY(-3px);  
    box-shadow: 0 6px 20px rgba(106, 17, 203, 0.3);  
    }  
    
    .submit-btn:disabled {  
    opacity: 0.7;  
    cursor: not-allowed;  
    transform: none;  
    box-shadow: none;  
    }  
    
    .submit-btn .loading-spinner {  
    display: none;  
    margin-right: 8px;  
    animation: spin 1s linear infinite;  
    }  
    
    .submit-btn.loading .loading-spinner {  
    display: inline-block;  
    }  
    
    .submit-btn.loading .btn-text {  
    display: none;  
    }  
    
    @keyframes spin {  
    0% { transform: rotate(0deg); }  
    100% { transform: rotate(360deg); }  
    }  
    
    .alert {  
    padding: 1rem;  
    border-radius: 12px;  
    margin: 1.2rem 0;  
    display: none;  
    font-size: 0.9rem;  
    }  
    
    .alert-warning {  
    background: #fff8e6;  
    border: 1px solid #ffdf99;  
    color: var(--warning);  
    }  
    
    footer {  
    background: var(--dark);  
    color: white;  
    padding: 3rem 0 1.5rem;  
    margin-top: 3rem;  
    }  
    
    .footer-content {  
    max-width: 1200px;  
    margin: 0 auto;  
    display: grid;  
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));  
    gap: 2rem;  
    padding: 0 1.5rem;  
    }  
    
    .footer-col h3 {  
    font-size: 1.3rem;  
    margin-bottom: 1.2rem;  
    position: relative;  
    padding-bottom: 0.5rem;  
    }  
    
    .footer-col h3::after {  
    content: '';  
    position: absolute;  
    bottom: 0;  
    right: 0;  
    width: 50px;  
    height: 3px;  
    background: var(--accent);  
    }  
    
    .footer-col p {  
    line-height: 1.6;  
    margin-bottom: 1rem;  
    color: #ddd;  
    font-size: 0.9rem;  
    }  
    
    .footer-links {  
    list-style: none;  
    }  
    
    .footer-links li {  
    margin-bottom: 0.7rem;  
    }  
    
    .footer-links a {  
    color: #bbb;  
    text-decoration: none;  
    transition: all 0.3s;  
    font-size: 0.9rem;  
    }  
    
    .footer-links a:hover {  
    color: white;  
    padding-right: 5px;  
    }  
    
    .copyright {  
    text-align: center;  
    padding-top: 1.5rem;  
    margin-top: 1.5rem;  
    border-top: 1px solid #444;  
    color: #999;  
    font-size: 0.85rem;  
    }  
    
    @media (max-width: 1200px) {  
    .projects-grid {  
    grid-template-columns: repeat(2, 1fr);  
    }  
    }  
    
    @media (max-width: 900px) {  
    .nav-links {  
    display: none;  
    }  
    
    .site-stats {  
    display: none;  
    }  
    
    .hero h1 {  
    font-size: 2.2rem;  
    }  
    
    .hero p {  
    font-size: 1.1rem;  
    }  
    }  
    
    @media (max-width: 768px) {  
    .projects-grid {  
    grid-template-columns: repeat(2, 1fr);  
    gap: 1rem;  
    }  
    
    .project-image {  
    height: 140px;  
    }  
    
    .hero {  
    padding: 3rem 1.5rem;  
    margin-top: 15px;  
    margin-bottom: 30px;  
    }  
    
    .hero h1 {  
    font-size: 1.8rem;  
    }  
    
    .section-header h2 {  
    font-size: 1.6rem;  
    }  
    
    .filters-grid {  
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));  
    }  
    
    .category-options {  
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));  
    }  
    }  
    
    @media (max-width: 480px) {  
    .projects-grid {  
    grid-template-columns: repeat(2, 1fr);  
    gap: 10px;  
    }  
    
    .project-image {  
    height: 120px;  
    }  
    
    .project-content {  
    padding: 0.8rem;  
    }  
    
    .project-title {  
    font-size: 0.85rem;  
    }  
    
    .project-description {  
    font-size: 0.75rem;  
    }  
    
    .project-link {  
    padding: 0.4rem 0.8rem;  
    font-size: 0.75rem;  
    }  
    }  
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-store"></i>
                <span>مشاريع التاجرات اليمنيات</span>
            </div>
            <div class="site-stats">
                <span title="عدد زوار الموقع">
                    <i class="fas fa-users"></i> <?= $stats['site_visits'] ?? 0 ?>
                </span>
                <span title="عدد المشاريع">
                    <i class="fas fa-project-diagram"></i> <?= count($approved_projects) ?>
                </span>
            </div>
            <ul class="nav-links">
                <li><a href="#home">الرئيسية</a></li>
                <li><a href="#projects">المشاريع</a></li>
                <li><a href="#categories">الأقسام</a></li>
                <li><a href="#add-project">إضافة مشروع</a></li>
                <li><a href="#about">عن المنصة</a></li>
            </ul>
            <a href="login.php" class="btn">تسجيل الدخول</a>
        </nav>
    </header>

    <section class="hero" id="home">
        <div class="hero-content">
            <h1>منصة لنشر مشاريع التاجرات اليمنيات <br>والتعريف بها</h1>
            <p>سواء كنت تاجرة مبتدئة أو صاحبة مشروع قائم، منصتنا توفر لك فرصة لعرض مشروعك على آلاف الزوار المهتمين بمجال التجارة والأعمال النسائية في اليمن.</p>
            <a href="#add-project" class="btn">أضف مشروعك الآن</a>
        </div>
    </section>

    <section class="filters-section" id="categories">
        <div class="filters-header">
            <h3>تصفح المشاريع</h3>
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterProjects('all')">الكل</button>
                <button class="filter-btn" onclick="sortProjects('likes')">الأكثر إعجاباً</button>
                <button class="filter-btn" onclick="sortProjects('recent')">الأحدث</button>
            </div>
        </div>
        
        <div class="categories-container">
            <h4 class="categories-title">
                <i class="fas fa-tags"></i>
                الأقسام
            </h4>
            <div class="filters-grid">
                <div class="filter-card food" onclick="filterProjects('food')">
                    <i class="fas fa-utensils"></i>
                    <h3>الطعام والمأكولات</h3>
                </div>
                
                <div class="filter-card beauty" onclick="filterProjects('beauty')">
                    <i class="fas fa-spa"></i>
                    <h3>التجميل والعناية</h3>
                </div>
                
                <div class="filter-card fashion" onclick="filterProjects('fashion')">
                    <i class="fas fa-tshirt"></i>
                    <h3>الموضة والأزياء</h3>
                </div>
                
                <div class="filter-card sewing" onclick="filterProjects('sewing')">
                    <i class="fas fa-cut"></i>
                    <h3>الخياطة</h3>
                </div>
                
                <div class="filter-card decor" onclick="filterProjects('decor')">
                    <i class="fas fa-couch"></i>
                    <h3>الديكور والمفروشات</h3>
                </div>
                
                <div class="filter-card shopping" onclick="filterProjects('shopping')">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>التسوق العام</h3>
                </div>
                
                <div class="filter-card photography" onclick="filterProjects('photography')">
                    <i class="fas fa-camera"></i>
                    <h3>التصوير</h3>
                </div>
                
                <div class="filter-card design" onclick="filterProjects('design')">
                    <i class="fas fa-palette"></i>
                    <h3>التصميم</h3>
                </div>
                
                <div class="filter-card natural" onclick="filterProjects('natural')">
                    <i class="fas fa-leaf"></i>
                    <h3>المنتجات الطبيعية</h3>
                </div>
                
                <div class="filter-card kids" onclick="filterProjects('kids')">
                    <i class="fas fa-baby"></i>
                    <h3>الأطفال والرضّع</h3>
                </div>
                
                <div class="filter-card education" onclick="filterProjects('education')">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>التعليم والتدريب</h3>
                </div>
                
                <div class="filter-card home" onclick="filterProjects('home')">
                    <i class="fas fa-home"></i>
                    <h3>أدوات منزلية</h3>
                </div>
                
                <div class="filter-card misc" onclick="filterProjects('misc')">
                    <i class="fas fa-ellipsis-h"></i>
                    <h3>منتجات متنوعة</h3>
                </div>
            </div>
        </div>
        
        <div class="governorates-container">
            <h4 class="governorates-title">
                <i class="fas fa-map-marker-alt"></i>
                المحافظات
            </h4>
            <div class="filters-grid">
                <?php foreach($governorates as $gov): ?>
                <div class="filter-card" onclick="filterByGovernorate('<?= htmlspecialchars($gov) ?>')">
                    <i class="fas fa-map-pin"></i>
                    <h3><?= htmlspecialchars($gov) ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="projects-section" id="projects">
        <div class="section-header">
            <h2>المشاريع المعروضة</h2>
            <p>استكشف أحدث المشاريع المضافة من قبل التاجرات اليمنيات والتي حصلت على موافقة المشرفين</p>
        </div>
        
        <div class="projects-grid" id="projects-container">
            <?php if(empty($approved_projects)): ?>
                <div class="no-projects" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                    <p style="color: var(--gray);">لا توجد مشاريع معتمدة لعرضها حالياً</p>
                </div>
            <?php else: ?>
                <?php foreach($approved_projects as $index => $project): 
                    $projectId = $project['id'];
                    $likes = $stats['project_likes'][$projectId] ?? 0;
                    $visits = $stats['project_visits'][$projectId] ?? 0;
                ?>
                <div class="project-card" data-category="<?= htmlspecialchars($project['category'] ?? 'misc') ?>" data-governorate="<?= htmlspecialchars($project['governorate'] ?? '') ?>">
                    <?php if($index < 3): ?>
                        <div class="project-badge">الأعلى تقييماً</div>
                    <?php endif; ?>
                    <div class="project-image">
                        <?php if(!empty($project['images'][0]['permanent_url'])): ?>
                            <img src="<?= htmlspecialchars($project['images'][0]['permanent_url']) ?>" alt="<?= htmlspecialchars($project['name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/800x600?text=No+Image" alt="صورة افتراضية">
                        <?php endif; ?>
                    </div>
                    <div class="project-content">
                        <div class="project-meta">
                            <span class="project-category category-<?= htmlspecialchars($project['category'] ?? 'misc') ?>">
                                <?php 
                                switch($project['category'] ?? 'misc') {
                                    case 'food': echo 'الطعام والمأكولات'; break;
                                    case 'beauty': echo 'التجميل والعناية'; break;
                                    case 'fashion': echo 'الموضة والأزياء'; break;
                                    case 'sewing': echo 'الخياطة'; break;
                                    case 'decor': echo 'الديكور والمفروشات'; break;
                                    case 'shopping': echo 'التسوق العام'; break;
                                    case 'photography': echo 'التصوير'; break;
                                    case 'design': echo 'التصميم'; break;
                                    case 'natural': echo 'المنتجات الطبيعية'; break;
                                    case 'kids': echo 'الأطفال والرضّع'; break;
                                    case 'education': echo 'التعليم والتدريب'; break;
                                    case 'home': echo 'أدوات منزلية'; break;
                                    default: echo 'منتجات متنوعة';
                                }
                                ?>
                            </span>
                            <?php if(!empty($project['governorate'])): ?>
                                <span class="project-governorate"><?= htmlspecialchars($project['governorate']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="project-title"><?= !empty($project['name']) ? htmlspecialchars($project['name']) : 'بدون اسم' ?></h3>
                        <p class="project-description"><?= !empty($project['description']) ? htmlspecialchars($project['description']) : 'لا يوجد وصف' ?></p>
                        
                        <div class="project-stats">
                            <span class="stat-item" title="عدد الإعجابات">
                                <i class="fas fa-heart"></i> <?= $likes ?>
                            </span>
                            <span class="stat-item" title="عدد الزوار">
                                <i class="fas fa-eye"></i> <?= $visits ?>
                            </span>
                        </div>
                        
                        <div class="project-actions">
                            <a href="?like_project=<?= $projectId ?>" class="like-btn" title="أعجبني">
                                <i class="far fa-heart"></i>
                            </a>
                            <a href="project-details.php?id=<?= $projectId ?>" class="project-link">عرض التفاصيل</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="add-project" id="add-project">
        <h2>إضافة مشروع جديد</h2>
        
        <div class="alert alert-warning" id="pending-alert">
            <i class="fas fa-info-circle"></i> تم إرسال مشروعك بنجاح وهو الآن قيد المراجعة من قبل مشرف الموقع.
        </div>
        
        <form id="project-form" method="POST" action="upload.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="project-name">اسم المشروع</label>
                <input type="text" id="project-name" name="project-name" class="form-control" placeholder="أدخل اسم مشروعك" required>
            </div>
            
            <div class="form-group">
                <label for="project-description">وصف المشروع</label>
                <textarea id="project-description" name="project-description" class="form-control" placeholder="اكتب وصفاً مفصلاً لمشروعك..." required></textarea>
            </div>
            
            <div class="form-group">
                <label for="project-governorate">المحافظة</label>
                <select id="project-governorate" name="governorate" class="form-control" required>
                <option value="">اختر المحافظة</option>
                <option value="عدن">عدن</option>
                <option value="صنعاء">صنعاء</option>
                <option value="تعز">تعز</option>
                <option value="أبين">أبين</option>
                <option value="لحج">لحج</option>
                <option value="الضالع">الضالع</option>
                <option value="حضرموت">حضرموت</option>
                <option value="الحديدة">الحديدة</option>
                <option value="إب">إب</option>
                <option value="ذمار">ذمار</option>
                <option value="مأرب">مأرب</option>
                <option value="شبوة">شبوة</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>اختر القسم</label>
                <div class="category-options">
                    <input type="radio" id="category-food" name="category" value="food" class="category-option food" checked>
                    <label for="category-food"><i class="fas fa-utensils"></i> الطعام والمأكولات</label>
                    
                    <input type="radio" id="category-beauty" name="category" value="beauty" class="category-option beauty">
                    <label for="category-beauty"><i class="fas fa-spa"></i> التجميل والعناية</label>
                    
                    <input type="radio" id="category-fashion" name="category" value="fashion" class="category-option fashion">
                    <label for="category-fashion"><i class="fas fa-tshirt"></i> الموضة والأزياء</label>
                    
                    <input type="radio" id="category-sewing" name="category" value="sewing" class="category-option sewing">
                    <label for="category-sewing"><i class="fas fa-cut"></i> الخياطة</label>
                    
                    <input type="radio" id="category-decor" name="category" value="decor" class="category-option decor">
                    <label for="category-decor"><i class="fas fa-couch"></i> الديكور والمفروشات</label>
                    
                    <input type="radio" id="category-shopping" name="category" value="shopping" class="category-option shopping">
                    <label for="category-shopping"><i class="fas fa-shopping-bag"></i> التسوق العام</label>
                    
                    <input type="radio" id="category-photography" name="category" value="photography" class="category-option photography">
                    <label for="category-photography"><i class="fas fa-camera"></i> التصوير</label>
                    
                    <input type="radio" id="category-design" name="category" value="design" class="category-option design">
                    <label for="category-design"><i class="fas fa-palette"></i> التصميم</label>
                    
                    <input type="radio" id="category-natural" name="category" value="natural" class="category-option natural">
                    <label for="category-natural"><i class="fas fa-leaf"></i> المنتجات الطبيعية</label>
                    
                    <input type="radio" id="category-kids" name="category" value="kids" class="category-option kids">
                    <label for="category-kids"><i class="fas fa-baby"></i> الأطفال والرضّع</label>
                    
                    <input type="radio" id="category-education" name="category" value="education" class="category-option education">
                    <label for="category-education"><i class="fas fa-graduation-cap"></i> التعليم والتدريب</label>
                    
                    <input type="radio" id="category-home" name="category" value="home" class="category-option home">
                    <label for="category-home"><i class="fas fa-home"></i> أدوات منزلية</label>
                    
                    <input type="radio" id="category-misc" name="category" value="misc" class="category-option misc">
                    <label for="category-misc"><i class="fas fa-ellipsis-h"></i> منتجات متنوعة</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>وسائل التواصل</label>
                <div class="social-input-group">
                    <i class="fab fa-whatsapp" style="color: var(--success);"></i>
                    <input type="text" id="whatsapp" name="whatsapp" class="form-control social-input" placeholder="" value="https://wa.me/967">
                </div>
                <div class="social-input-group">
                    <i class="fab fa-instagram" style="color: var(--instagram);"></i>
                    <input type="text" id="instagram" name="instagram" class="form-control social-input" placeholder="@اسم المستخدم">
                </div>
            </div>
            
            <div class="form-group">
                <label>صور المشروع</label>
                <div class="file-upload" id="file-upload">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>انقر لرفع الصور أو اسحبها وأفلتها هنا</p>
                    <input type="file" id="project-images" name="images[]" accept="image/*" multiple style="display: none;">
                </div>
                <div id="file-names" style="margin-top: 10px; font-size: 0.9rem; color: var(--gray);"></div>
            </div>
            
            <button type="submit" class="submit-btn" id="submit-btn">
                <i class="fas fa-spinner loading-spinner"></i>
                <span class="btn-text">إرسال المشروع للمراجعة</span>
            </button>
        </form>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-col">
                <h3>عن المنصة</h3>
                <p>منصة "مشاريع التاجرات اليمنيات" تهدف إلى دعم وتمكين المرأة اليمنية في مجال التجارة والأعمال من خلال توفير مساحة لعرض مشاريعها وزيادة فرص نجاحها.</p>
            </div>
            
            <div class="footer-col">
                <h3>روابط سريعة</h3>
                <ul class="footer-links">
                    <li><a href="#home">الرئيسية</a></li>
                    <li><a href="#projects">المشاريع</a></li>
                    <li><a href="#categories">الأقسام</a></li>
                    <li><a href="#add-project">إضافة مشروع</a></li>
                    <li><a href="#">سياسة الخصوصية</a></li>
                    <li><a href="#">شروط الاستخدام</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>اتصل بنا</h3>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope"></i> info@yemen-projects.com</li>
                    <li><i class="fas fa-phone"></i> +967 780 506 324</li>
                    <li><i class="fas fa-map-marker-alt"></i> صنعاء، الجمهورية اليمنية</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>جميع الحقوق محفوظة &copy; 2023 منصة مشاريع التاجرات اليمنيات</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // معالجة رفع الملفات
        document.getElementById('file-upload').addEventListener('click', function() {
            document.getElementById('project-images').click();
        });
        
        document.getElementById('project-images').addEventListener('change', function(e) {
            const fileNames = document.getElementById('file-names');
            const files = e.target.files;
            let names = '';
            
            for (let i = 0; i < files.length; i++) {
                const fileSize = (files[i].size / (1024*1024)).toFixed(2);
                names += `<div><i class="fas fa-paperclip"></i> ${files[i].name} (${fileSize} MB)</div>`;
            }
            
            fileNames.innerHTML = names || 'لم يتم اختيار أي ملفات';
        });
        
        // تصفية المشاريع حسب القسم
        function filterProjects(category) {
            const projects = document.querySelectorAll('.project-card');
            const filterCards = document.querySelectorAll('.filter-card');
            
            // تحديث حالة الأزرار
            filterCards.forEach(card => {
                card.classList.remove('active');
                if (card.classList.contains(category)) {
                    card.classList.add('active');
                }
            });
            
            // تصفية المشاريع
            projects.forEach(project => {
                if (category === 'all' || project.dataset.category === category) {
                    project.style.display = 'block';
                } else {
                    project.style.display = 'none';
                }
            });
        }
        
        // تصفية المشاريع حسب المحافظة
        function filterByGovernorate(governorate) {
            const projects = document.querySelectorAll('.project-card');
            const filterCards = document.querySelectorAll('.filter-card');
            
            // تحديث حالة الأزرار
            filterCards.forEach(card => {
                card.classList.remove('active');
            });
            
            // تصفية المشاريع
            projects.forEach(project => {
                if (governorate === 'all' || project.dataset.governorate === governorate) {
                    project.style.display = 'block';
                } else {
                    project.style.display = 'none';
                }
            });
        }
        
        // ترتيب المشاريع
        function sortProjects(sortBy) {
            const container = document.getElementById('projects-container');
            const projects = Array.from(document.querySelectorAll('.project-card'));
            
            projects.sort((a, b) => {
                if (sortBy === 'likes') {
                    const likesA = parseInt(a.querySelector('.fa-heart').nextSibling.textContent.trim());
                    const likesB = parseInt(b.querySelector('.fa-heart').nextSibling.textContent.trim());
                    return likesB - likesA;
                } else {
                    // يمكن إضافة ترتيب حسب التاريخ إذا كان متاحاً في البيانات
                    return 0;
                }
            });
            
            // إعادة ترتيب المشاريع في الصفحة
            projects.forEach(project => container.appendChild(project));
            
            // تحديث أزرار التصفية
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(sortBy === 'likes' ? 'إعجاباً' : 'الأحدث')) {
                    btn.classList.add('active');
                }
            });
        }
        
        // معالجة الإعجابات بدون إعادة تحميل الصفحة
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const likeUrl = this.getAttribute('href');
                
                // التحقق من وجود كوكي للإعجاب
                if (document.cookie.includes('liked_' + likeUrl.split('=')[1])) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لقد أعجبت بهذا المشروع مسبقاً',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    return;
                }
                
                try {
                    const response = await fetch(likeUrl);
                    if (response.ok) {
                        // تحديث الواجهة
                        this.classList.add('liked');
                        this.innerHTML = '<i class="fas fa-heart"></i>';
                        
                        // تحديث العداد
                        const likesElement = this.closest('.project-actions').previousElementSibling.querySelector('.fa-heart').parentElement;
                        const currentLikes = parseInt(likesElement.textContent.trim());
                        likesElement.textContent = ` ${currentLikes + 1}`;
                        
                        // تعيين كوكي لمنع الإعجاب المتكرر (30 يوم)
                        document.cookie = `liked_${likeUrl.split('=')[1]}=true; max-age=${60*60*24*30}; path=/`;
                        
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
        });

        // معالجة إرسال النموذج
        document.getElementById('project-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit-btn');
            const formData = new FormData(this);
            
            // عرض حالة التحميل
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('pending-alert').style.display = 'block';
                    this.reset();
                    document.getElementById('file-names').innerHTML = '';
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: 'تم إرسال مشروعك للمراجعة',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: result.message || 'حدث خطأ أثناء الإرسال',
                        confirmButtonText: 'حسناً'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ في الاتصال بالخادم',
                    confirmButtonText: 'حسناً'
                });
            } finally {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>