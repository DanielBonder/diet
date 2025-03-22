<?php
session_start();

// בדיקת הרשאות - רק admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>לוח ניהול מנהל</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #f8f8f8, #e0e0e0);
            text-align: center;
            padding: 60px;
        }
        h1 {
            margin-bottom: 40px;
        }
        .dashboard {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
        }
        .card {
            background-color: white;
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 280px;
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .card a {
            text-decoration: none;
            background-color: #ffa500;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 10px;
        }
        .card a:hover {
            background-color: #e69500;
        }
    </style>
</head>
<body>
    <h1>לוח ניהול - ברוך הבא <?= htmlspecialchars($_SESSION['full_name']) ?></h1>

    <div class="dashboard">
        <div class="card">
            <h2>הוספת תאריך ושעה זמינים</h2>
            <a href="add_availability.php">הוסף זמינות</a>
        </div>

        <div class="card">
            <h2>צפייה בפגישות שנקבעו</h2>
            <a href="admin_appointments.php">הצג תורים</a>
        </div>

        <div class="card">
            <h2>ניהול לקוחות</h2>
            <a href="manage_customers.php">נהל לקוחות</a>
        </div>
    </div>
</body>
</html>
