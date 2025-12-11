<?php
// ========================
// API التقارير (مُعدَّل لعرض التاريخ الميلادي)
// ========================

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    sendError('يجب تسجيل الدخول أولاً!', 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllReports();
        break;
    case 'getByChild':
        getReportsByChild();
        break;
    default:
        sendError('إجراء غير صالح');
}

// الحصول على جميع التقارير
function getAllReports() {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT 
            c.child_id,
            c.name AS child_name,
            c.gender,
            c.total_stars,
            c.total_sessions,
            c.total_time_minutes,
            b.name AS badge_name,
            b.icon AS badge_icon,
            COUNT(s.session_id) AS completed_sessions,
            COALESCE(AVG(s.stars_earned), 0) AS avg_stars
        FROM CHILD c
        LEFT JOIN BADGES b ON c.badge_id = b.badge_id
        LEFT JOIN SESSION s ON c.child_id = s.child_id AND s.status = 'مكتملة'
        WHERE c.parent_id = ?
        GROUP BY c.child_id
        ORDER BY c.last_activity DESC
    ");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];

    while ($row = $result->fetch_assoc()) {
        $childId = $row['child_id'];

        // إضافة التاريخ الميلادي
        if (isset($row['last_activity'])) {
            $row['last_activity_date'] = date('Y-m-d H:i:s', strtotime($row['last_activity']));
        }

        // آخر 5 جلسات
        $sessionsStmt = $db->prepare("
            SELECT s.*, co.title, co.thumbnail
            FROM SESSION s
            JOIN CONTENT co ON s.content_id = co.content_id
            WHERE s.child_id = ? AND s.status = 'مكتملة'
            ORDER BY s.end_time DESC
            LIMIT 5
        ");
        $sessionsStmt->bind_param("s", $childId);
        $sessionsStmt->execute();
        $sessionsResult = $sessionsStmt->get_result();

        $sessions = [];
        while ($session = $sessionsResult->fetch_assoc()) {
            $session['end_time_gregorian'] = date('Y-m-d H:i:s', strtotime($session['end_time']));
            $sessions[] = $session;
        }

        $row['recent_sessions'] = $sessions;
        $reports[] = $row;
    }

    sendSuccess($reports);
}

// الحصول على تقارير طفل معين
function getReportsByChild() {
    $childId = cleanInput($_GET['child_id'] ?? '');

    if (empty($childId)) {
        sendError('معرف الطفل مطلوب!');
    }

    $db = getDB();

    // التحقق من الملكية
    $stmt = $db->prepare("SELECT child_id FROM CHILD WHERE child_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }

    // معلومات الطفل
    $stmt = $db->prepare("
        SELECT c.*, b.name AS badge_name, b.icon AS badge_icon, b.description AS badge_description
        FROM CHILD c
        LEFT JOIN BADGES b ON c.badge_id = b.badge_id
        WHERE c.child_id = ?
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();

    // تحويل التواريخ
    if (isset($child['created_at'])) {
        $child['created_at_gregorian'] = date('Y-m-d H:i:s', strtotime($child['created_at']));
    }
    if (isset($child['last_activity'])) {
        $child['last_activity_gregorian'] = date('Y-m-d H:i:s', strtotime($child['last_activity']));
    }

    // آخر 10 جلسات
    $stmt = $db->prepare("
        SELECT s.*, co.title, co.thumbnail, co.type
        FROM SESSION s
        JOIN CONTENT co ON s.content_id = co.content_id
        WHERE s.child_id = ? AND s.status = 'مكتملة'
        ORDER BY s.end_time DESC
        LIMIT 10
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    $recentSessions = [];
    while ($row = $result->fetch_assoc()) {
        $row['end_time_gregorian'] = date('Y-m-d H:i:s', strtotime($row['end_time']));
        $recentSessions[] = $row;
    }

    // إحصائيات الجلسات
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) AS total_sessions,
            COALESCE(SUM(duration_minutes), 0) AS total_time,
            COALESCE(SUM(stars_earned), 0) AS total_stars,
            COALESCE(AVG(stars_earned), 0) AS avg_stars,
            COALESCE(AVG(completion_percentage), 0) AS avg_completion
        FROM SESSION
        WHERE child_id = ? AND status = 'مكتملة'
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // إحصائيات حسب الفئات
    $stmt = $db->prepare("
        SELECT 
            co.category,
            COUNT(*) AS count,
            COALESCE(SUM(s.stars_earned), 0) AS total_stars
        FROM SESSION s
        JOIN CONTENT co ON s.content_id = co.content_id
        WHERE s.child_id = ? AND s.status = 'مكتملة'
        GROUP BY co.category
        ORDER BY count DESC
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    // الأنشطة الأكثر لعباً
    $stmt = $db->prepare("
        SELECT 
            co.title,
            co.thumbnail,
            co.type,
            COUNT(*) AS play_count,
            COALESCE(AVG(s.stars_earned), 0) AS avg_stars
        FROM SESSION s
        JOIN CONTENT co ON s.content_id = co.content_id
        WHERE s.child_id = ? AND s.status = 'مكتملة'
        GROUP BY s.content_id
        ORDER BY play_count DESC
        LIMIT 5
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    $topActivities = [];
    while ($row = $result->fetch_assoc()) {
        $topActivities[] = $row;
    }

    // الإنجازات
    $achievements = [];
    $recommendations = [];

    if ($child['total_stars'] >= 50) {
        $achievements[] = "حصل على أكثر من 50 نجمة! 🌟";
    }
    if ($child['total_sessions'] >= 20) {
        $achievements[] = "أكمل أكثر من 20 جلسة تعليمية! 📚";
    }
    if ($stats['avg_completion'] >= 80) {
        $achievements[] = "متوسط إكمال ممتاز (أكثر من 80%)! 🎯";
    }

    // التوصيات
    if ($stats['avg_completion'] < 60) {
        $recommendations[] = "حاول زيادة مدة الجلسات للحصول على نتائج أفضل";
    }
    if (count($categories) < 3) {
        $recommendations[] = "جرب أنواع مختلفة من الأنشطة لتنوع التعلم";
    }

    $report = [
        'child'            => $child,
        'stats'            => $stats,
        'categories'       => $categories,
        'top_activities'   => $topActivities,
        'recent_sessions'  => $recentSessions,
        'achievements'     => $achievements,
        'recommendations'  => $recommendations
    ];

    sendSuccess($report);
}
?>
