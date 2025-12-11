<?php
require_once 'config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userName = $_SESSION['user_name'];
$userType = $_SESSION['user_type'];
$userEmail = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأهل 👨‍👩‍👧‍👦</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- الشريط العلوي -->
        <header class="header">
            <div class="header-content">
                <div class="logo-section">
                    <h1>🌈 لوحة تحكم الأهل</h1>
                </div>
                <div class="user-section">
                    <span id="user-name">مرحباً، <?php echo htmlspecialchars($userName); ?> 👋</span>
                    <button onclick="logout()" class="btn-logout">تسجيل الخروج 🚪</button>
                </div>
            </div>
        </header>

        <!-- التبويبات الرئيسية -->
        <div class="tabs-container">
            <button class="main-tab active" onclick="showMainTab('profile')">
                👤 المعلومات الشخصية
            </button>
            <button class="main-tab" onclick="showMainTab('children')">
                👶 التحكم بالأطفال
            </button>
            <button class="main-tab" onclick="showMainTab('reports')">
                📊 التقارير
            </button>
        </div>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <!-- قسم المعلومات الشخصية -->
            <div id="profile" class="tab-content active">
                <div class="content-card">
                    <h2>📋 معلوماتك الشخصية</h2>
                    <div class="profile-info">
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <input type="text" id="profile-name" class="info-value-input" value="<?php echo htmlspecialchars($userName); ?>">
                        </div>
                        <div class="info-row">
                            <span class="info-label">النوع:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userType); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">البريد الإلكتروني:</span>
                            <input type="email" id="profile-email" class="info-value-input" value="<?php echo htmlspecialchars($userEmail); ?>">
                        </div>
                    </div>
                    <div class="profile-actions">
                        <button onclick="updateProfile()" class="btn btn-primary">حفظ التعديلات 💾</button>
                        <button onclick="showChangePassword()" class="btn btn-secondary">تغيير كلمة المرور 🔐</button>
                    </div>
                </div>

                <!-- تغيير كلمة المرور -->
                <div id="change-password" class="content-card" style="display: none; margin-top: 20px;">
                    <h3>🔐 تغيير كلمة المرور</h3>
                    <div class="form-group">
                        <label>كلمة المرور الحالية:</label>
                        <input type="password" id="current-password" placeholder="أدخل كلمة المرور الحالية">
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور الجديدة:</label>
                        <input type="password" id="new-password" placeholder="أدخل كلمة المرور الجديدة">
                    </div>
                    <div class="form-group">
                        <label>تأكيد كلمة المرور:</label>
                        <input type="password" id="confirm-new-password" placeholder="أعد إدخال كلمة المرور">
                    </div>
                    <button onclick="changePassword()" class="btn btn-primary">تحديث كلمة المرور ✅</button>
                    <button onclick="hideChangePassword()" class="btn btn-secondary">إلغاء</button>
                </div>
            </div>

            <!-- قسم الأطفال -->
            <div id="children" class="tab-content">
                <div class="content-card">
                    <div class="section-header">
                        <h2>👶 قائمة الأطفال</h2>
                        <button onclick="showAddChild()" class="btn btn-add">إضافة طفل جديد ➕</button>
                    </div>
                    
                    <div id="children-list" class="children-grid">
                        <div style="text-align: center; padding: 40px;">
                            جاري التحميل...
                        </div>
                    </div>
                </div>

                <!-- نموذج إضافة طفل -->
                <div id="add-child-form" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close" onclick="hideAddChild()">&times;</span>
                        <h3>➕ إضافة طفل جديد</h3>
                        <form id="add-child-form-element">
                            <div class="form-group">
                                <label>اسم الطفل:</label>
                                <input type="text" name="name" placeholder="أدخل اسم الطفل" required>
                            </div>
                            <div class="form-group">
                                <label>تاريخ الميلاد:</label>
                                <input type="date" name="birthdate" required>
                            </div>
                            <div class="form-group">
                                <label>الجنس:</label>
                                <select name="gender" required>
                                    <option value="">اختر...</option>
                                    <option value="ذكر">ذكر 👦</option>
                                    <option value="أنثى">أنثى 👧</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">إضافة ✅</button>
                        </form>
                    </div>
                </div>

                <!-- نموذج تعديل طفل -->
                <div id="edit-child-form" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close" onclick="hideEditChild()">&times;</span>
                        <h3>✏️ تعديل بيانات الطفل</h3>
                        <form id="edit-child-form-element">
                            <input type="hidden" name="child_id" id="edit-child-id">
                            <div class="form-group">
                                <label>اسم الطفل:</label>
                                <input type="text" name="name" id="edit-child-name" required>
                            </div>
                            <div class="form-group">
                                <label>تاريخ الميلاد:</label>
                                <input type="date" name="birthdate" id="edit-child-birthdate" required>
                            </div>
                            <div class="form-group">
                                <label>الجنس:</label>
                                <select name="gender" id="edit-child-gender" required>
                                    <option value="ذكر">ذكر 👦</option>
                                    <option value="أنثى">أنثى 👧</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ التعديلات 💾</button>
                            <button type="button" onclick="hideEditChild()" class="btn btn-secondary">إلغاء</button>
                        </form>
                    </div>
                </div>

                <!-- نموذج إدارة المهام -->
                <div id="manage-tasks-modal" class="modal" style="display: none;">
                    <div class="modal-content large">
                        <span class="close" onclick="hideManageTasks()">&times;</span>
                        <h3>📝 إدارة مهام <span id="tasks-child-name"></span></h3>
                        
                        <div class="tasks-section">
                            <h4>المهام الحالية:</h4>
                            <div id="current-tasks-list"></div>
                        </div>

                        <div class="add-task-section">
                            <h4>إضافة مهمة جديدة:</h4>
                            <form id="add-task-form">
                                <div class="form-group">
                                    <label>اختر المحتوى:</label>
                                    <select name="content_id" id="task-content" required></select>
                                </div>
                                <div class="form-group">
                                    <label>الوقت المخصص (دقائق):</label>
                                    <input type="number" name="duration" id="task-duration" min="5" max="60" placeholder="10" required>
                                </div>
                                <div class="form-group">
                                    <label>ملاحظات (اختياري):</label>
                                    <textarea name="notes" id="task-notes" placeholder="ملاحظات للطفل..." rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-add">إضافة المهمة ➕</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قسم التقارير -->
            <div id="reports" class="tab-content">
                <div class="content-card">
                    <h2>📊 تقارير الأداء</h2>
                    <div id="reports-list">
                        <div style="text-align: center; padding: 40px;">
                            جاري التحميل...
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- رسالة التنبيه -->
        <div id="toast" class="toast"></div>
    </div>

    <script src="js/parent-php.js"></script>
</body>
</html>