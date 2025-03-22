<?php
session_start();

// בדיקת הרשאה – רק admin יכול לגשת
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין הרשאה. אנא התחבר כמנהל.");
}

// חיבור למסד הנתונים
require_once 'db.php';

$message = "";

// טיפול בטופס
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = isset($_POST['available_date']) ? trim($_POST['available_date']) : '';
    $time = isset($_POST['available_time']) ? trim($_POST['available_time']) : '';

    if (!empty($date) && !empty($time)) {
        $stmt = $conn->prepare("INSERT INTO availability (available_date, available_time) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $date, $time);
            if ($stmt->execute()) {
                $message = "✅ התאריך והשעה נוספו בהצלחה";
            } else {
                $message = "❌ שגיאה בהרצת השאילתה: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "❌ שגיאה בהכנת השאילתה: " . $conn->error;
        }
    } else {
        $message = "❗ נא להזין תאריך ושעה";
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>הוספת זמינות</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            text-align: center;
            padding: 40px;
        }
        form {
            background-color: #fff;
            display: inline-block;
            padding: 25px 35px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label, input, button {
            font-size: 16px;
            margin: 10px 0;
            display: block;
            width: 100%;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <h2>הוספת תאריך ושעה זמינים</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="available_date">בחר תאריך:</label>
        <input type="date" name="available_date" id="available_date" required>

        <label for="available_time">בחר שעה:</label>
        <input type="time" name="available_time" id="available_time" required>

        <button type="submit">הוסף זמינות</button>
    </form>
</body>
</html>
