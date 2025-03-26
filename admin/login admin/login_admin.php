<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginInput = isset($_POST['loginInput']) ? trim($_POST['loginInput']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($loginInput) || empty($password)) {
        displayError("יש למלא את כל השדות.");
    }

    $conn = new mysqli("localhost", "root", "", "Db_Management_App");
    if ($conn->connect_error) {
        displayError("שגיאת חיבור למסד הנתונים: " . $conn->connect_error);
    }

    $sql = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
        ? "SELECT * FROM users WHERE email = ?"
        : "SELECT * FROM users WHERE username = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        displayError("שגיאה בהכנת הבקשה: " . $conn->error);
    }

    $stmt->bind_param("s", $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // השוואה ישירה - החלף ב-password_verify אם הסיסמה מוצפנת
        if ($password === $user['password']) {

            // בדיקת הרשאות
            if ((int)$user['is_admin'] !== 1) {
                displayError("גישה מיועדת למנהלים בלבד.");
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];

            echo '
            <div class="login-success">
                <h2>שלום ' . htmlspecialchars($user['full_name']) . '</h2>
                <p>התחברת בהצלחה! מועבר ללוח הניהול...</p>
            </div>
            <style>
                body {
                    background: linear-gradient(to right, #f0fff0, #d4edda);
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding-top: 100px;
                    margin: 0;
                }
                .login-success {
                    background-color: #e6ffed;
                    padding: 30px;
                    border-radius: 20px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    display: inline-block;
                }
                .login-success h2 {
                    color: #155724;
                    margin-bottom: 10px;
                }
                .login-success p {
                    color: #155724;
                    font-size: 16px;
                }
            </style>';

            header("Refresh:2; url=../admin_dashboard.php");
            exit();

        } else {
            displayError("סיסמה שגויה.");
        }
    } else {
        displayError("שם משתמש או אימייל לא נמצאו.");
    }

    $stmt->close();
    $conn->close();
}

function displayError($message) {
    echo '
    <div class="error-message">
        <h2>שגיאה</h2>
        <p>' . htmlspecialchars($message) . '</p>
    </div>
    <style>
        body {
            background: linear-gradient(to right, #fff0f0, #ffd6d6);
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 100px;
            margin: 0;
        }
        .error-message {
            background-color: #f8d7da;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: inline-block;
        }
        .error-message h2 {
            color: #721c24;
            margin-bottom: 10px;
        }
        .error-message p {
            color: #721c24;
            font-size: 16px;
        }
    </style>';
    exit();
}
?>
