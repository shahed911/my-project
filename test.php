<?php
// ملف اختبار للتأكد من أن PHP يعمل

echo "<h1>اختبار PHP</h1>";
echo "<p>إذا ظهرت هذه الرسالة، فإن PHP يعمل بشكل صحيح! ✅</p>";

// اختبار الاتصال بقاعدة البيانات
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'kids_learning_platform';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error . "</p>";
    echo "<h3>الحل:</h3>";
    echo "<ol>";
    echo "<li>تأكد من أن MySQL شغال في XAMPP</li>";
    echo "<li>تأكد من أنك أنشأت قاعدة بيانات اسمها: kids_learning_platform</li>";
    echo "<li>تأكد من استيراد ملف database.sql</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات ناجح!</p>";
    
    // فحص الجداول
    $tables = ['PARENTS', 'CHILD', 'CONTENT', 'TASKS', 'SESSION', 'BADGES', 'REPORT'];
    echo "<h3>الجداول الموجودة:</h3>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<li style='color: green;'>✅ $table</li>";
        } else {
            echo "<li style='color: red;'>❌ $table (غير موجود)</li>";
        }
    }
    echo "</ul>";
    
    $conn->close();
}

echo "<hr>";
echo "<h3>معلومات النظام:</h3>";
echo "<ul>";
echo "<li>نسخة PHP: " . phpversion() . "</li>";
echo "<li>المسار الحالي: " . __DIR__ . "</li>";
echo "</ul>";

echo "<hr>";
echo "<a href='index.php' style='display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 10px; font-size: 18px;'>انتقل لصفحة تسجيل الدخول 🚀</a>";
?>