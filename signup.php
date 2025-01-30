<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // קבלת נתונים ובדיקה אם הם קיימים
    $fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
    $userName = isset($_POST['userName']) ? trim($_POST['userName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;

    // הדפסת הנתונים לבדיקה
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // בדיקה אם יש שדות ריקים
    if (empty($fullName) || empty($userName) || empty($email) || empty($password) || $weight <= 0 || $height <= 0) {
        die("Error: All fields are required.");
    }

    // חישוב BMI
    $bmi = ($height > 0) ? ($weight / (($height / 100) * ($height / 100))) : null;

    // חיבור למסד הנתונים
    $conn = new mysqli("localhost", "root", "", "Db_Management_App");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // בדיקה אם שם המשתמש או האימייל כבר קיימים
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $userName, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Error: Username or email already exists.");
    }
    $stmt->close();

    // הכנסת נתונים למסד הנתונים ללא הצפנת סיסמה
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, weight, height, bmi) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!$stmt->bind_param("ssssddd", $fullName, $userName, $email, $password, $weight, $height, $bmi)) {
        die("Error binding parameters: " . $stmt->error);
    }

    if ($stmt->execute()) {
        echo "Signup successful! Your BMI is " . round($bmi, 2);
    } else {
        die("Error executing query: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>