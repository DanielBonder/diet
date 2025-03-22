<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName']);
    $userName = trim($_POST['userName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];

    if (empty($fullName) || empty($userName) || empty($email) || empty($password) || empty($weight) || empty($height)) {
        displayError("All fields are required.");
    }

    // חישוב BMI
    $bmi = $height > 0 ? $weight / pow($height / 100, 2) : null;

    // חיבור למסד הנתונים
    $conn = new mysqli("localhost", "root", "", "Db_Management_App");
    if ($conn->connect_error) {
        displayError("Connection failed: " . $conn->connect_error);
    }

    // בדיקה אם המשתמש כבר קיים לפי שם משתמש או אימייל
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $userName, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        displayError("Username or email already exists.");
    }

    $stmt->close();

    // הכנסת המשתמש למסד הנתונים כולל BMI
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, weight, height, bmi) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdd", $fullName, $userName, $email, $password, $weight, $height, $bmi);

    if ($stmt->execute()) {
        // הצלחה – עיצוב הודעת מעבר
        echo '
        <div class="signup-success">
            <h2>Welcome, ' . htmlspecialchars($fullName) . '!</h2>
            <p>Your account has been successfully created.<br>Redirecting to login page...</p>
        </div>
        <style>
            body {
                background: linear-gradient(to right, #ffe4b5, #ffb6c1);
                font-family: Arial, sans-serif;
                text-align: center;
                margin: 0;
                padding: 0;
            }
            .signup-success {
                margin: 100px auto;
                padding: 40px;
                background-color: #fff0e1;
                border-radius: 40px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                animation: fadeIn 1s ease-in-out;
            }
            .signup-success h2 {
                font-family: "Suez One", serif;
                font-size: 28px;
                color: #2e7d32;
                margin-bottom: 20px;
            }
            .signup-success p {
                font-size: 18px;
                color: #444;
                line-height: 1.6;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to   { opacity: 1; transform: translateY(0); }
            }
        </style>';

        header("Refresh:3; url=../login/login.html");
        exit();
    } else {
        displayError("Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}

function displayError($message) {
    echo '
    <div class="error-message">
        ' . htmlspecialchars($message) . '
    </div>
    <style>
        body {
            background: linear-gradient(to right, #ffe4b5, #ffb6c1);
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .error-message {
            margin: 100px auto;
            padding: 30px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>';
    exit();
}
?>
