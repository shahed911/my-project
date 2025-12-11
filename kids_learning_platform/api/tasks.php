<?php
// ========================
// API إدارة المهام
// ========================

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    sendError('يجب تسجيل الدخول أولاً!', 401);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getByChild':
        getTasksByChild();
        break;
    case 'getContent':
        getContent();
        break;
    case 'getContentByAge':
        getContentByAge();
        break;
    case 'add':
        addTask();
        break;
    case 'delete':
        deleteTask();
        break;
    case 'updateOrder':
        updateTaskOrder();
        break;
    default:
        sendError('إجراء غير صالح');
}

// الحصول على مهام طفل معين
function getTasksByChild() {
    $childId = cleanInput($_GET['child_id'] ?? '');
    
    if (empty($childId)) {
        sendError('معرف الطفل مطلوب!');
    }
    
    $db = getDB();
    
    // التحقق من ملكية الطفل
    $stmt = $db->prepare("SELECT child_id FROM CHILD WHERE child_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }
    
    // الحصول على المهام
    $stmt = $db->prepare("
        SELECT t.*, c.title, c.type, c.thumbnail, c.difficulty, c.category
        FROM TASKS t
        JOIN CONTENT c ON t.content_id = c.content_id
        WHERE t.child_id = ?
        ORDER BY t.task_order ASC
    ");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    sendSuccess($tasks);
}

// الحصول على جميع المحتوى
function getContent() {
    $db = getDB();
    
    $result = $db->query("SELECT * FROM CONTENT ORDER BY age_min ASC, title ASC");
    
    $content = [];
    while ($row = $result->fetch_assoc()) {
        $content[] = $row;
    }
    
    sendSuccess($content);
}

// الحصول على المحتوى حسب العمر
function getContentByAge() {
    $age = intval($_GET['age'] ?? 0);
    
    if ($age < 3 || $age > 12) {
        sendError('العمر غير صالح!');
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT * FROM CONTENT 
        WHERE age_min <= ? AND age_max >= ?
        ORDER BY difficulty ASC, title ASC
    ");
    $stmt->bind_param("ii", $age, $age);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $content = [];
    while ($row = $result->fetch_assoc()) {
        $content[] = $row;
    }
    
    sendSuccess($content);
}

// إضافة مهمة جديدة
function addTask() {
    $childId = cleanInput($_POST['child_id'] ?? '');
    $contentId = cleanInput($_POST['content_id'] ?? '');
    $duration = intval($_POST['duration'] ?? 0);
    $notes = cleanInput($_POST['notes'] ?? '');
    
    if (empty($childId) || empty($contentId) || $duration <= 0) {
        sendError('جميع الحقول مطلوبة!');
    }
    
    $db = getDB();
    
    // التحقق من ملكية الطفل
    $stmt = $db->prepare("SELECT child_id FROM CHILD WHERE child_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }
    
    // الحصول على الترتيب التالي
    $stmt = $db->prepare("SELECT MAX(task_order) as max_order FROM TASKS WHERE child_id = ?");
    $stmt->bind_param("s", $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextOrder = ($row['max_order'] ?? 0) + 1;
    
    // إضافة المهمة
    $taskId = generateId('task');
    $parentId = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        INSERT INTO TASKS (task_id, parent_id, child_id, content_id, assigned_duration, task_order, parent_notes, assigned_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssiis", $taskId, $parentId, $childId, $contentId, $duration, $nextOrder, $notes);
    
    if ($stmt->execute()) {
        sendSuccess([
            'task_id' => $taskId,
            'task_order' => $nextOrder
        ], 'تمت إضافة المهمة بنجاح!');
    } else {
        sendError('فشل إضافة المهمة!');
    }
}

// حذف مهمة
function deleteTask() {
    $taskId = cleanInput($_POST['task_id'] ?? '');
    
    if (empty($taskId)) {
        sendError('معرف المهمة مطلوب!');
    }
    
    $db = getDB();
    
    // التحقق من ملكية المهمة
    $stmt = $db->prepare("SELECT child_id, task_order FROM TASKS WHERE task_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $taskId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('المهمة غير موجودة!');
    }
    
    $task = $result->fetch_assoc();
    $childId = $task['child_id'];
    $deletedOrder = $task['task_order'];
    
    // حذف المهمة
    $stmt = $db->prepare("DELETE FROM TASKS WHERE task_id = ?");
    $stmt->bind_param("s", $taskId);
    
    if ($stmt->execute()) {
        // إعادة ترتيب المهام المتبقية
        $stmt = $db->prepare("
            UPDATE TASKS 
            SET task_order = task_order - 1 
            WHERE child_id = ? AND task_order > ?
        ");
        $stmt->bind_param("si", $childId, $deletedOrder);
        $stmt->execute();
        
        sendSuccess([], 'تم حذف المهمة بنجاح!');
    } else {
        sendError('فشل حذف المهمة!');
    }
}

// تحديث ترتيب المهام
function updateTaskOrder() {
    $taskId = cleanInput($_POST['task_id'] ?? '');
    $direction = cleanInput($_POST['direction'] ?? ''); // 'up' or 'down'
    
    if (empty($taskId) || empty($direction)) {
        sendError('معرف المهمة والاتجاه مطلوبان!');
    }
    
    if (!in_array($direction, ['up', 'down'])) {
        sendError('اتجاه غير صالح!');
    }
    
    $db = getDB();
    
    // الحصول على المهمة الحالية
    $stmt = $db->prepare("
        SELECT t.child_id, t.task_order, c.parent_id
        FROM TASKS t
        JOIN CHILD c ON t.child_id = c.child_id
        WHERE t.task_id = ? AND c.parent_id = ?
    ");
    $stmt->bind_param("ss", $taskId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('المهمة غير موجودة!');
    }
    
    $currentTask = $result->fetch_assoc();
    $childId = $currentTask['child_id'];
    $currentOrder = $currentTask['task_order'];
    
    // تحديد الترتيب الجديد
    $targetOrder = ($direction === 'up') ? $currentOrder - 1 : $currentOrder + 1;
    
    // التحقق من وجود مهمة في الترتيب المستهدف
    $stmt = $db->prepare("SELECT task_id FROM TASKS WHERE child_id = ? AND task_order = ?");
    $stmt->bind_param("si", $childId, $targetOrder);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('لا يمكن تحريك المهمة في هذا الاتجاه!');
    }
    
    $targetTask = $result->fetch_assoc();
    $targetTaskId = $targetTask['task_id'];
    
    // تبديل الترتيب
    $db->begin_transaction();
    
    try {
        // تحديث المهمة المستهدفة
        $stmt = $db->prepare("UPDATE TASKS SET task_order = ? WHERE task_id = ?");
        $stmt->bind_param("is", $currentOrder, $targetTaskId);
        $stmt->execute();
        
        // تحديث المهمة الحالية
        $stmt = $db->prepare("UPDATE TASKS SET task_order = ? WHERE task_id = ?");
        $stmt->bind_param("is", $targetOrder, $taskId);
        $stmt->execute();
        
        $db->commit();
        sendSuccess([], 'تم تحديث الترتيب بنجاح!');
        
    } catch (Exception $e) {
        $db->rollback();
        sendError('فشل تحديث الترتيب!');
    }
}
?>