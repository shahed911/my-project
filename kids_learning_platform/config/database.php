<?php
// ========================
// ملف الاتصال بقاعدة البيانات
// ========================

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kids_learning_platform');

// إنشاء الاتصال
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("فشل الاتصال بقاعدة البيانات: " . $this->connection->connect_error);
            }
            
            // تعيين الترميز إلى UTF-8
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("خطأ في الاتصال: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // منع النسخ
    private function __clone() {}
    
    // منع unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// دالة مساعدة للحصول على الاتصال
function getDB() {
    return Database::getInstance()->getConnection();
}

// دالة لتنظيف المدخلات
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// دالة لإرجاع JSON
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// دالة لإرجاع خطأ JSON
function sendError($message, $status = 400) {
    sendJSON(['success' => false, 'error' => $message], $status);
}

// دالة لإرجاع نجاح JSON
function sendSuccess($data = [], $message = 'تمت العملية بنجاح') {
    sendJSON(['success' => true, 'message' => $message, 'data' => $data]);
}

// دالة لتوليد معرف فريد
function generateId($prefix) {
    return $prefix . '_' . time() . '_' . bin2hex(random_bytes(8));
}

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>