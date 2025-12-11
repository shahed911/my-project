<?php
// ========================
// API إدارة الجلسات التعليمية
// ========================

require_once '../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'start':
        startSession();
        break;
    case 'end':
        endSession();
        break;
    case 'getByChild':
        getSessionsByChild();
        break;
    default:
        sendError('إجراء غير صالح');
}

// بدء جلسة جديدة
function startSession() {
    $childId = cleanInput($_POST['child_id'] ?? '');
    $contentId = cleanInput($_POST['content_id'] ?? '');
    $taskId = cleanInput($_POST['task_id'] ?? '');
    
    if (empty($childId) || empty($contentId)) {
        sendError('معرف الطفل والمحتوى مطلوبان!');
    }
    
    $db = getDB();
    
    // الحصول على parent_id من معرف الطفل
    $stmt = $db->prepare("SELECT parent_id FROM CHILD WHERE child_id = ?");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }
    
    $child = $result->fetch_assoc();
    $parentId = $child['parent_id'];
    
    // إنشاء جلسة جديدة
    $sessionId = generateId('session');
    
    $stmt = $db->prepare("
        INSERT INTO SESSION (session_id, parent_id, child_id, content_id, task_id, start_time, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW(), 'جارية', NOW())
    ");
    
    if (empty($taskId)) {
        $stmt = $db->prepare("
            INSERT INTO SESSION (session_id, parent_id, child_id, content_id, start_time, status, created_at)
            VALUES (?, ?, ?, ?, NOW(), 'جارية', NOW())
        ");
        $stmt->bind_param("ssss", $sessionId, $parentId, $childId, $contentId);
    } else {
        $stmt->bind_param("sssss", $sessionId, $parentId, $childId, $contentId, $taskId);
    }
    
    if ($stmt->execute()) {
        // تحديث حالة المهمة إذا كانت موجودة
        if (!empty($taskId)) {
            $updateStmt = $db->prepare("UPDATE TASKS SET status = 'قيد التنفيذ' WHERE task_id = ?");
            $updateStmt->bind_param("s", $taskId);
            $updateStmt->execute();
        }
        
        sendSuccess([
            'session_id' => $sessionId,
            'start_time' => date('Y-m-d H:i:s')
        ], 'تم بدء الجلسة بنجاح!');
    } else {
        sendError('فشل بدء الجلسة!');
    }
}

// إنهاء جلسة
function endSession() {
    $sessionId = cleanInput($_POST['session_id'] ?? '');
    $durationMinutes = intval($_POST['duration_minutes'] ?? 0);
    $starsEarned = intval($_POST['stars_earned'] ?? 0);
    $completionPercentage = intval($_POST['completion_percentage'] ?? 0);
    
    if (empty($sessionId)) {
        sendError('معرف الجلسة مطلوب!');
    }
    
    $db = getDB();
    
    // الحصول على معلومات الجلسة
    $stmt = $db->prepare("
        SELECT s.child_id, s.task_id, c.parent_id, c.total_stars, c.total_sessions, c.total_time_minutes
        FROM SESSION s
        JOIN CHILD c ON s.child_id = c.child_id
        WHERE s.session_id = ?
    ");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الجلسة غير موجودة!');
    }
    
    $session = $result->fetch_assoc();
    $childId = $session['child_id'];
    $taskId = $session['task_id'];
    
    // بدء المعاملة
    $db->begin_transaction();
    
    try {
        // تحديث الجلسة
        $stmt = $db->prepare("
            UPDATE SESSION 
            SET end_time = NOW(), 
                duration_minutes = ?, 
                stars_earned = ?, 
                completion_percentage = ?,
                status = 'مكتملة'
            WHERE session_id = ?
        ");
        $stmt->bind_param("iiis", $durationMinutes, $starsEarned, $completionPercentage, $sessionId);
        $stmt->execute();
        
        // تحديث إحصائيات الطفل
        $newTotalStars = $session['total_stars'] + $starsEarned;
        $newTotalSessions = $session['total_sessions'] + 1;
        $newTotalTime = $session['total_time_minutes'] + $durationMinutes;
        
        $stmt = $db->prepare("
            UPDATE CHILD 
            SET total_stars = ?,
                total_sessions = ?,
                total_time_minutes = ?,
                last_activity = NOW()
            WHERE child_id = ?
        ");
        $stmt->bind_param("iiis", $newTotalStars, $newTotalSessions, $newTotalTime, $childId);
        $stmt->execute();
        
        // تحديث اللقب بناءً على النجوم
        $stmt = $db->prepare("
            SELECT badge_id FROM BADGES 
            WHERE ? BETWEEN min_stars AND max_stars
            ORDER BY level DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $newTotalStars);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $badge = $result->fetch_assoc();
            $badgeId = $badge['badge_id'];
            
            $stmt = $db->prepare("UPDATE CHILD SET badge_id = ? WHERE child_id = ?");
            $stmt->bind_param("ss", $badgeId, $childId);
            $stmt->execute();
        }
        
        // تحديث حالة المهمة إذا كانت موجودة
        if (!empty($taskId)) {
            $stmt = $db->prepare("
                UPDATE TASKS 
                SET status = 'مكتمل', 
                    times_completed = times_completed + 1,
                    last_accessed = NOW()
                WHERE task_id = ?
            ");
            $stmt->bind_param("s", $taskId);
            $stmt->execute();
        }
        
        $db->commit();
        
        // الحصول على معلومات الطفل المحدثة
        $stmt = $db->prepare("
            SELECT c.*, b.name as badge_name, b.icon as badge_icon
            FROM CHILD c
            LEFT JOIN BADGES b ON c.badge_id = b.badge_id
            WHERE c.child_id = ?
        ");
        $stmt->bind_param("s", $childId);
        $stmt->execute();
        $result = $stmt->get_result();
        $updatedChild = $result->fetch_assoc();
        
        sendSuccess([
            'child' => $updatedChild,
            'stars_earned' => $starsEarned
        ], 'تم إنهاء الجلسة بنجاح!');
        
    } catch (Exception $e) {
        $db->rollback();
        sendError('فشل إنهاء الجلسة!');
    }
}

// الحصول على جلسات طفل معين
function getSessionsByChild() {
    $childId = cleanInput($_GET['child_id'] ?? '');
    
    if (empty($childId)) {
        sendError('معرف الطفل مطلوب!');
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT s.*, c.title, c.type, c.thumbnail
        FROM SESSION s
        JOIN CONTENT c ON s.content_id = c.content_id
        WHERE s.child_id = ? AND s.status = 'مكتملة'
        ORDER BY s.end_time DESC
        LIMIT 50
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    sendSuccess($sessions);
}
?>