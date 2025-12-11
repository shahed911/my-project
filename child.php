<?php
require_once 'config/database.php';

// الحصول على معرف الطفل
$childId = $_GET['id'] ?? '';

if (empty($childId)) {
    header('Location: parent.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحة التعلم 🎮</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="child-page">
        <!-- رأس الصفحة -->
        <div class="child-header">
            <h1 id="child-name">مرحباً 👋</h1>
            <div class="child-stats">
                <div class="stat">
                    <div class="stat-icon" id="child-icon">🎈</div>
                    <div class="stat-text" id="child-age"></div>
                </div>
                <div class="stat">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-text" id="total-stars">0 نجمة</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" id="badge-icon">🎈</div>
                    <div class="stat-text" id="badge-name">مبتدئ</div>
                </div>
            </div>
            <button onclick="goBackToParent()" class="btn btn-secondary" style="margin-top: 15px;">
                العودة للأهل 🏠
            </button>
        </div>

        <!-- المهام -->
        <div class="tasks-container" id="tasks-container">
            <div style="text-align: center; padding: 60px;">
                <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                <p style="font-size: 20px; color: white;">جاري تحميل المهام...</p>
            </div>
        </div>
    </div>

    <script>
        const CHILD_ID = '<?php echo htmlspecialchars($childId); ?>';
    </script>
    <script src="js/child-php.js"></script>
</body>
</html>