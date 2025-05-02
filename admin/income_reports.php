<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';

// הכנסות לפי תאריך
$daily_income = [];
$sql = "SELECT DATE(paid_at) as date, SUM(amount) as total FROM payments WHERE status = 'שולם' GROUP BY DATE(paid_at)";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $daily_income[$row['date']] = $row['total'];
}

// הכנסות לפי חודש
$monthly_income = [];
$sql = "SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as total FROM payments WHERE status = 'שולם' GROUP BY month";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $monthly_income[$row['month']] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>דוח הכנסות</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f8f8f8;
            text-align: center;
        }
        h2 {
            margin-top: 40px;
            color: #333;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .back-button a:hover {
            background-color: #0056b3;
        }
        canvas {
            max-width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h1>📈 דוח הכנסות</h1>

<h2>הכנסות לפי תאריך</h2>
<canvas id="dailyChart" height="100"></canvas>

<h2>הכנסות לפי חודש</h2>
<canvas id="monthlyChart" height="100"></canvas>

<script>
    const dailyLabels = <?= json_encode(array_keys($daily_income)) ?>;
    const dailyData = <?= json_encode(array_values($daily_income)) ?>;

    const monthlyLabels = <?= json_encode(array_keys($monthly_income)) ?>;
    const monthlyData = <?= json_encode(array_values($monthly_income)) ?>;

    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'סה״כ הכנסות ביום',
                data: dailyData,
                backgroundColor: '#007bff'
            }]
        }
    });

    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'סה״כ הכנסות בחודש',
                data: monthlyData,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                fill: false
            }]
        }
    });
</script>
</body>
</html>
