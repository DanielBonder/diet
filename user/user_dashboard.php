<?php
session_start();

// אם המשתמש לא מחובר – הפניה
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../admin/db.php';

$message = "";

// טיפול בקביעת פגישה
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'];
    $date = $_POST['available_date'];
    $time = $_POST['available_time'];

    // הכנסה לטבלת פגישות
    $stmt = $conn->prepare("INSERT INTO appointments (user_id, full_name, available_date, available_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $full_name, $date, $time);
    if ($stmt->execute()) {
        // מחיקת התאריך מהטבלה availability
        $del = $conn->prepare("DELETE FROM availability WHERE available_date = ? AND available_time = ?");
        $del->bind_param("ss", $date, $time);
        $del->execute();
        $message = "✅ הפגישה נקבעה בהצלחה!";
    } else {
        $message = "❌ שגיאה בקביעת הפגישה: " . $stmt->error;
    }
    $stmt->close();
}

// שליפת זמינויות
$sql = "SELECT available_date, available_time FROM availability ORDER BY available_date ASC, available_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>לוח משתמש</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 40px;
            text-align: center;
        }
        h1 {
            margin-bottom: 30px;
        }
        .message {
            margin-bottom: 20px;
            color: green;
            font-weight: bold;
        }
        table {
            margin: 0 auto;
            border-collapse: collapse;
            width: 90%;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 20px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #ffe4b5;
        }
        form {
            margin: 0;
        }
    </style>
</head>
<body>
    <h1>שלום <?= htmlspecialchars($_SESSION['full_name']) ?>, ברוך הבא ללוח המשתמש</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>תאריכים זמינים לפגישה עם המנהל</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>תאריך</th>
                <th>שעה</th>
                <th>פעולה</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= date("d/m/Y", strtotime($row['available_date'])) ?></td>
                    <td><?= substr($row['available_time'], 0, 5) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="available_date" value="<?= $row['available_date'] ?>">
                            <input type="hidden" name="available_time" value="<?= $row['available_time'] ?>">
                            <button type="submit">קבע פגישה</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>לא נמצאו תאריכים זמינים כרגע.</p>
    <?php endif; ?>
</body>
</html>
