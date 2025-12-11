<?php
// ========================
// API المصادقة وإدارة المستخدمين
// ========================

require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'updateProfile':
        updateProfile();
        break;
    case 'changePassword':
        changePassword();
        break;
    case 'checkSession':
        checkSession();
        break;
    default:
        sendError('إجراء غير صالح');
}

// تسجيل مستخدم جديد
function register() {
    $name = cleanInput($_POST['name'] ?? '');
    $type = cleanInput($_POST['type'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // التحقق من البيانات
    if (empty($name) || empty($type) || empty($email) || empty($password)) {
        sendError('جميع الحقول مطلوبة!');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('البريد الإلكتروني غير صالح!');
    }
    
    if (strlen($password) < 6) {
        sendError('كلمة المرور يجب أن تكون 6 أحرف على الأقل!');
    }
    
    if (!in_array($type, ['أم', 'أب'])) {
        sendError('نوع المستخدم غير صالح!');
    }
    
    $db = getDB();
    
    // التحقق من وجود البريد الإلكتروني
    $stmt = $db->prepare("SELECT parent_id FROM PARENTS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendError('البريد الإلكتروني مسجل بالفعل!');
    }
    
    // تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // إدراج المستخدم الجديد
    $parentId = generateId('parent');
    $stmt = $db->prepare("INSERT INTO PARENTS (parent_id, name, type, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $parentId, $name, $type, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        // تسجيل الدخول تلقائياً
        $_SESSION['user_id'] = $parentId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_type'] = $type;
        $_SESSION['user_email'] = $email;
        
        // تحديث last_login
        $updateStmt = $db->prepare("UPDATE PARENTS SET last_login = NOW() WHERE parent_id = ?");
        $updateStmt->bind_param("s", $parentId);
        $updateStmt->execute();
        
        sendSuccess([
            'parent_id' => $parentId,
            'name' => $name,
            'type' => $type,
            'email' => $email
        ], 'تم إنشاء الحساب بنجاح!');
    } else {
        sendError('فشل إنشاء الحساب!');
    }
}

// تسجيل الدخول
function login() {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendError('البريد الإلكتروني وكلمة المرور مطلوبان!');
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("SELECT parent_id, name, type, email, password FROM PARENTS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendError('البريد الإلكتروني أو كلمة المرور غير صحيحة!');
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        sendError('البريد الإلكتروني أو كلمة المرور غير صحيحة!');
    }
    
    // تسجيل الدخول
    $_SESSION['user_id'] = $user['parent_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_type'] = $user['type'];
    $_SESSION['user_email'] = $user['email'];
    
    // تحديث last_login
    $updateStmt = $db->prepare("UPDATE PARENTS SET last_login = NOW() WHERE parent_id = ?");
    $updateStmt->bind_param("s", $user['parent_id']);
    $updateStmt->execute();
    
    sendSuccess([
        'parent_id' => $user['parent_id'],
        'name' => $user['name'],
        'type' => $user['type'],
        'email' => $user['email']
    ], 'تم تسجيل الدخول بنجاح!');
}

// تسجيل الخروج
function logout() {
    session_destroy();
    sendSuccess([], 'تم تسجيل الخروج بنجاح!');
}

// تحديث الملف الشخصي
function updateProfile() {
    if (!isset($_SESSION['user_id'])) {
        sendError('يجب تسجيل الدخول أولاً!', 401);
    }
    
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($name) || empty($email)) {
        sendError('الاسم والبريد الإلكتروني مطلوبان!');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('البريد الإلكتروني غير صالح!');
    }
    
    $db = getDB();
    
    // التحقق من عدم وجود البريد الإلكتروني لمستخدم آخر
    $stmt = $db->prepare("SELECT parent_id FROM PARENTS WHERE email = ? AND parent_id != ?");
    $stmt->bind_param("ss", $email, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendError('البريد الإلكتروني مستخدم من قبل!');
    }
    
    // تحديث البيانات
    $stmt = $db->prepare("UPDATE PARENTS SET name = ?, email = ? WHERE parent_id = ?");
    $stmt->bind_param("sss", $name, $email, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        sendSuccess([
            'name' => $name,
            'email' => $email
        ], 'تم تحديث البيانات بنجاح!');
    } else {
        sendError('فشل تحديث البيانات!');
    }
}

// تغيير كلمة المرور
function changePassword() {
    if (!isset($_SESSION['user_id'])) {
        sendError('يجب تسجيل الدخول أولاً!', 401);
    }
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        sendError('جميع الحقول مطلوبة!');
    }
    
    if (strlen($newPassword) < 6) {
        sendError('كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل!');
    }
    
    $db = getDB();
    
    // التحقق من كلمة المرور الحالية
    $stmt = $db->prepare("SELECT password FROM PARENTS WHERE parent_id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $user['password'])) {
        sendError('كلمة المرور الحالية غير صحيحة!');
    }
    
    // تحديث كلمة المرور
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE PARENTS SET password = ? WHERE parent_id = ?");
    $stmt->bind_param("ss", $hashedPassword, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        sendSuccess([], 'تم تغيير كلمة المرور بنجاح!');
    } else {
        sendError('فشل تغيير كلمة المرور!');
    }
}

// التحقق من الجلسة
function checkSession() {
    if (isset($_SESSION['user_id'])) {
        sendSuccess([
            'logged_in' => true,
            'user' => [
                'parent_id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'type' => $_SESSION['user_type'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    } else {
        sendSuccess(['logged_in' => false]);
    }
}
?>