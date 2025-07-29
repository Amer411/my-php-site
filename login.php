<?php
session_start();

// بيانات الدخول الثابتة (يمكنك تغييرها)
$admin_username = 'admin';
$admin_password = 'admin123';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'مدير النظام';
        header('Location: admin.php');
        exit();
    } else {
        $error = 'بيانات الدخول غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل دخول المشرف</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-box {
            width: 400px;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #6a11cb;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: right;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #212529;
        }
        input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #2575fc;
            box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.2);
        }
        button {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.3);
        }
        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2><i class="fas fa-lock"></i> تسجيل دخول المشرف</h2>
        <?php if(isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</button>
        </form>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>