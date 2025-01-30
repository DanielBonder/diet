<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginInput = isset($_POST['loginInput']) ? trim($_POST['loginInput']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($loginInput) || empty($password)) {
        die("Error: Both fields are required.");
    }

    // חיבור למסד הנתונים
    $conn = new mysqli("localhost", "root", "", "Db_Management_App");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // בדיקה אם הקלט הוא אימייל או שם משתמש
    if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM users WHERE email = ?";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($password === $user['password']) { // השוואה ישירה כי הסיסמה אינה מוצפנת
            // התחברות מוצלחת - שמירת נתוני המשתמש בסשן
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            echo "Login successful! Redirecting...";
            header("Refresh:2; url=home.html");
            exit();
        } else {
            die("Error: Invalid password.");
        }
    } else {
        die("Error: User not found.");
    }

    $stmt->close();
    $conn->close();
}
?>
