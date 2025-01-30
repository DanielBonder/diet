<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // קבלת נתונים ובדיקה אם הם קיימים
    $fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
    $userName = isset($_POST['userName']) ? trim($_POST['userName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;

    // בדיקה אם יש שדות ריקים
    if (empty($fullName) || empty($userName) || empty($email) || empty($password) || $weight <= 0 || $height <= 0) {
        $errorMessage = "Error: All fields are required.";
    } else {
        // חישוב BMI
        $bmi = ($height > 0) ? ($weight / (($height / 100) * ($height / 100))) : null;

        // חיבור למסד הנתונים
        $conn = new mysqli("localhost", "root", "", "Db_Management_App");
        if ($conn->connect_error) {
            $errorMessage = "Connection failed: " . $conn->connect_error;
        } else {
            // בדיקה אם שם המשתמש או האימייל כבר קיימים
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $userName, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errorMessage = "Error: Username or email already exists.";
            } else {
                $stmt->close();

                // הכנסת נתונים למסד הנתונים
                $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, weight, height, bmi) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $errorMessage = "Error preparing statement: " . $conn->error;
                } else {
                    if (!$stmt->bind_param("ssssddd", $fullName, $userName, $email, $password, $weight, $height, $bmi)) {
                        $errorMessage = "Error binding parameters: " . $stmt->error;
                    } else {
                        if ($stmt->execute()) {
                            $successMessage = "Signup successful! Redirecting to login...";
                            echo "<script>setTimeout(function(){ window.location.href = 'login.html'; }, 3000);</script>";
                        } else {
                            $errorMessage = "Error executing query: " . $stmt->error;
                        }
                    }
                }
                $stmt->close();
            }
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }
        .success {
            color: green;
            font-size: 20px;
            font-weight: bold;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin: 20px auto;
            width: 50%;
            border-radius: 5px;
        }
        .error {
            color: red;
            font-size: 18px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 20px auto;
            width: 50%;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php if (!empty($errorMessage)) { echo "<div class='error'>$errorMessage</div>"; } ?>
    <?php if (!empty($successMessage)) { echo "<div class='success'>$successMessage</div>"; } ?>
</body>
</html>
