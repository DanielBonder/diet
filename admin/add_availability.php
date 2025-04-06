<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין הרשאה. אנא התחבר כמנהל.");
}

require_once 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $slot_interval = 15;

    if ($start_date && $end_date && $start_time && $end_time) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $today = new DateTime(date('Y-m-d'));
        $success = 0;
        $fail = 0;

        if ($end < $start) {
            $message = "❗ תאריך הסיום לא יכול להיות לפני תאריך ההתחלה.";
        } else {
            while ($start <= $end) {
                if ($start < $today) {
                    $start->modify('+1 day');
                    continue;
                }

                $date_str = $start->format('Y-m-d');
                $time_pointer = new DateTime("$date_str $start_time");
                $end_of_day = new DateTime("$date_str $end_time");

                while ($time_pointer < $end_of_day) {
                    $time_str = $time_pointer->format('H:i');

                    $stmt = $conn->prepare("INSERT INTO availability (available_date, available_time) VALUES (?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ss", $date_str, $time_str);
                        if ($stmt->execute()) {
                            $success++;
                        } else {
                            $fail++;
                        }
                        $stmt->close();
                    } else {
                        $fail++;
                    }

                    $time_pointer->modify("+{$slot_interval} minutes");
                }

                $start->modify('+1 day');
            }

            $message = "✅ נוסף בהצלחה: $success זמני פגישות.";
            if ($fail > 0) {
                $message .= " ❌ שגיאות בהרצה: $fail.";
            }
        }
    } else {
        $message = "❗ נא למלא את כל השדות.";
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
            background-color: #f9f9f9;
            text-align: center;
            padding: 40px;
        }

        form {
            background-color: #fff;
            display: inline-block;
            padding: 25px 35px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }

        label, input, button {
            font-size: 16px;
            margin: 10px 0;
            display: block;
            width: 100%;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .message {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }

        .back-button {
            margin-top: 20px;
        }

        .back-button a {
            display: inline-block;
            padding: 10px 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .back-button a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<h2>הוספת זמינות לפי טווח תאריכים</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

 
<form method="POST">
    <label for="start_date">תאריך התחלה:</label>
    <input type="date" name="start_date" id="start_date" required>

    <label for="end_date">תאריך סיום:</label>
    <input type="date" name="end_date" id="end_date" required>

    <label for="start_time">שעת התחלה:</label>
    <input type="time" name="start_time" id="start_time" required>

    <label for="end_time">שעת סיום:</label>
    <input type="time" name="end_time" id="end_time" required>

    <button type="submit">הוסף זמינות</button>
</form>
 

</body>
</html>
