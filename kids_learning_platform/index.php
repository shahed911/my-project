<?php
require_once 'config/database.php';

// إذا كان المستخدم مسجل الدخول، نقله لصفحة الأهل
if (isset($_SESSION['user_id'])) {
    header('Location: parent.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌈 عالم التعلم الممتع للأطفال</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '⭐🌟✨💫🎈🎨🚀🎯🎪🎭';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            font-size: 50px;
            opacity: 0.1;
            animation: float 20s infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logo p {
            color: #666;
            font-size: 16px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: #f0f0f0;
            border-radius: 15px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.05);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .decoration {
            position: fixed;
            font-size: 60px;
            animation: spin 10s linear infinite;
            opacity: 0.3;
            pointer-events: none;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .star1 { top: 10%; left: 10%; animation-duration: 8s; }
        .star2 { top: 20%; right: 15%; animation-duration: 12s; }
        .star3 { bottom: 15%; left: 20%; animation-duration: 10s; }
        .star4 { bottom: 10%; right: 10%; animation-duration: 9s; }
    </style>
</head>
<body>
    <div class="decoration star1">⭐</div>
    <div class="decoration star2">🎈</div>
    <div class="decoration star3">🚀</div>
    <div class="decoration star4">🌟</div>

    <div class="container">
        <div class="logo">
            <h1>🌈 عالم التعلم الممتع</h1>
            <p>منصة تعليمية آمنة لأطفالك</p>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('login')">تسجيل الدخول 🔑</button>
            <button class="tab" onclick="showTab('register')">حساب جديد ✨</button>
        </div>

        <div id="alert" class="alert"></div>

        <!-- نموذج تسجيل الدخول -->
        <div id="login" class="form-section active">
            <form id="login-form">
                <div class="form-group">
                    <label>البريد الإلكتروني 📧</label>
                    <input type="email" name="email" required placeholder="أدخل بريدك الإلكتروني">
                </div>
                <div class="form-group">
                    <label>كلمة المرور 🔒</label>
                    <input type="password" name="password" required placeholder="أدخل كلمة المرور">
                </div>
                <button type="submit" class="btn btn-primary">دخول 🚪</button>
            </form>
        </div>

        <!-- نموذج التسجيل -->
        <div id="register" class="form-section">
            <form id="register-form">
                <div class="form-group">
                    <label>الاسم الكامل 👤</label>
                    <input type="text" name="name" required placeholder="أدخل اسمك الكامل">
                </div>
                <div class="form-group">
                    <label>أنت 👨‍👩‍👧‍👦</label>
                    <select name="type" required>
                        <option value="">اختر...</option>
                        <option value="أم">أم 🤱</option>
                        <option value="أب">أب 👨</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني 📧</label>
                    <input type="email" name="email" required placeholder="أدخل بريدك الإلكتروني">
                </div>
                <div class="form-group">
                    <label>كلمة المرور 🔒</label>
                    <input type="password" name="password" required placeholder="اختر كلمة مرور قوية" minlength="6">
                </div>
                <div class="form-group">
                    <label>تأكيد كلمة المرور 🔐</label>
                    <input type="password" name="confirm_password" required placeholder="أعد إدخال كلمة المرور">
                </div>
                <button type="submit" class="btn btn-primary">إنشاء حساب 🎉</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
            hideAlert();
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert alert-${type} show`;
        }

        function hideAlert() {
            document.getElementById('alert').classList.remove('show');
        }

        // تسجيل الدخول
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'parent.php';
                    }, 1000);
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('حدث خطأ في الاتصال!', 'error');
            }
        });

        // التسجيل
        document.getElementById('register-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (formData.get('password') !== formData.get('confirm_password')) {
                showAlert('كلمتا المرور غير متطابقتين!', 'error');
                return;
            }
            
            formData.append('action', 'register');
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'parent.php';
                    }, 1500);
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('حدث خطأ في الاتصال!', 'error');
            }
        });
    </script>
</body>
</html>