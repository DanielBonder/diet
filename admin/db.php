<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "Db_Management_App";

// יצירת חיבור למסד הנתונים
$conn = new mysqli($host, $user, $password, $database);

// בדיקה אם החיבור הצליח
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
