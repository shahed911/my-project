<?php
// ========================
// API إدارة الأطفال
// ========================

require_once '../config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    sendError('يجب تسجيل الدخول أولاً!', 401);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllChildren();
        break;
    case 'getOne':
        getOneChild();
        break;
    case 'add':
        addChild();
        break;
    case 'update':
        updateChild();
        break;
    case 'delete':
        deleteChild();
        break;
    case 'getBadges':
        getBadges();
        break;
    default:
        sendError('إجراء غير صالح');
}

// الحصول على جميع الأطفال للمستخدم الحالي
function getAllChildren() {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT c.*, b.name as badge_name, b.icon as badge_icon, b.color_code as badge_color
        FROM CHILD c
        LEFT JOIN BADGES b ON c.badge_id = b.badge_id
        WHERE c.parent_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $children = [];
    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }
    
    sendSuccess($children);
}

// الحصول على طفل واحد
function getOneChild() {
    $childId = cleanInput($_GET['child_id'] ?? '');
    
    if (empty($childId)) {
        sendError('معرف الطفل مطلوب!');
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT c.*, b.name as badge_name, b.icon as badge_icon, b.color_code as badge_color
        FROM CHILD c
        LEFT JOIN BADGES b ON c.badge_id = b.badge_id
        WHERE c.child_id = ? AND c.parent_id = ?
    ");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }
    
    $child = $result->fetch_assoc();
    sendSuccess($child);
}

// إضافة طفل جديد
function addChild() {
    $name = cleanInput($_POST['name'] ?? '');
    $birthdate = cleanInput($_POST['birthdate'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? '');
    
    if (empty($name) || empty($birthdate) || empty($gender)) {
        sendError('جميع الحقول مطلوبة!');
    }
    
    if (!in_array($gender, ['ذكر', 'أنثى'])) {
        sendError('الجنس غير صالح!');
    }
    
    // حساب العمر
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < 3 || $age > 12) {
        sendError('العمر المسموح من 3 إلى 12 سنة!');
    }
    
    $db = getDB();
    
    $childId = generateId('child');
    $parentId = $_SESSION['user_id'];
    
    $stmt = $db->prepare("
        INSERT INTO CHILD (child_id, parent_id, name, birthdate, age, gender, badge_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'badge_1', NOW())
    ");
    $stmt->bind_param("ssssss", $childId, $parentId, $name, $birthdate, $age, $gender);
    
    if ($stmt->execute()) {
        sendSuccess([
            'child_id' => $childId,
            'name' => $name,
            'age' => $age,
            'gender' => $gender
        ], 'تمت إضافة الطفل بنجاح!');
    } else {
        sendError('فشل إضافة الطفل!');
    }
}

// تحديث بيانات الطفل
function updateChild() {
    $childId = cleanInput($_POST['child_id'] ?? '');
    $name = cleanInput($_POST['name'] ?? '');
    $birthdate = cleanInput($_POST['birthdate'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? '');
    
    if (empty($childId) || empty($name) || empty($birthdate) || empty($gender)) {
        sendError('جميع الحقول مطلوبة!');
    }
    
    // حساب العمر
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    $db = getDB();
    
    // التحقق من ملكية الطفل
    $stmt = $db->prepare("SELECT child_id FROM CHILD WHERE child_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('الطفل غير موجود!');
    }
    
    // تحديث البيانات
    $stmt = $db->prepare("
        UPDATE CHILD 
        SET name = ?, birthdate = ?, age = ?, gender = ?
        WHERE child_id = ? AND parent_id = ?
    ");
    $stmt->bind_param("ssisss", $name, $birthdate, $age, $gender, $childId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        sendSuccess([
            'child_id' => $childId,
            'name' => $name,
            'age' => $age,
            'gender' => $gender
        ], 'تم تحديث بيانات الطفل بنجاح!');
    } else {
        sendError('فشل تحديث البيانات!');
    }
}

// حذف طفل
function deleteChild() {
    $childId = cleanInput($_POST['child_id'] ?? '');
    
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
    
    // حذف الطفل (سيتم حذف المهام والجلسات تلقائياً بسبب ON DELETE CASCADE)
    $stmt = $db->prepare("DELETE FROM CHILD WHERE child_id = ? AND parent_id = ?");
    $stmt->bind_param("ss", $childId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        sendSuccess([], 'تم حذف الطفل بنجاح!');
    } else {
        sendError('فشل حذف الطفل!');
    }
}

// الحصول على الألقاب
function getBadges() {
    $db = getDB();
    
    $result = $db->query("SELECT * FROM BADGES ORDER BY level ASC");
    
    $badges = [];
    while ($row = $result->fetch_assoc()) {
        $badges[] = $row;
    }
    
    sendSuccess($badges);
}
?>