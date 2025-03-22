<?php
session_start();

// בדיקת הרשאה – רק admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';

// שליפת כל הפגישות שנקבעו
$sql = "SELECT full_name, available_date, available_time, created_at 
        FROM appointments 
        ORDER BY available_date ASC, available_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>ניהול פגישות</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 40px;
            text-align: center;
        }
        h2 {
            margin-bottom: 30px;
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
            background-color: #fcd7a2;
        }
    </style>
</head>
<body>
    <h2>רשימת הפגישות שנקבעו</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>שם משתמש</th>
                <th>תאריך</th>
                <th>שעה</th>
                <th>נקבע בתאריך</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['available_date'])) ?></td>
                    <td><?= substr($row['available_time'], 0, 5) ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>אין פגישות שנקבעו עדיין.</p>
    <?php endif; ?>
</body>
</html>
