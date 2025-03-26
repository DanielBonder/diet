<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

require_once '../../admin/db.php';
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// שליפת תוכניות תשלום
$plans = [];
$planResults = $conn->query("SELECT id, name, price FROM payment_plans ORDER BY duration_months ASC");
while ($row = $planResults->fetch_assoc()) {
    $plans[] = $row;
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>אזור אישי</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 40px;
            text-align: center;
        }
        section {
            background-color: #fff;
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 60%;
            text-align: right;
        }
        h2, h3, h4 {
            color: #333;
        }
        .link-button, button, select {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
        }
        .link-button:hover, button:hover {
            background-color: #218838;
        }
        select {
            background: white;
            color: black;
            width: 100%;
        }
    </style>
</head>
<body>

<h2>שלום <?= htmlspecialchars($full_name) ?>, ברוך הבא לאזור האישי שלך</h2>

<section>
    <h3>📅 הפגישות שלך:</h3>
    <?php
    $result = $conn->query("SELECT * FROM appointments WHERE user_id = $user_id ORDER BY available_date ASC");
    if ($result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
            $type = $row['meeting_type'] === 'initial' ? 'פגישה ראשונית' : 'שקילה שבועית';
            echo "<p>בתאריך " . date("d/m/Y", strtotime($row['available_date'])) . " בשעה " . substr($row['available_time'], 0, 5) . " ($type)</p>";
        endwhile;
    else:
        echo "<p>אין פגישות מתוכננות.</p>";
    endif;
    ?>
    <a href="user_appointments.php" class="link-button">📅 קבע פגישה נוספת</a>
</section>


<section>
    <h3>💳 מצב תשלום:</h3>
    <?php
    if (isset($_SESSION['payment_message'])) {
        echo "<p style='color: #007bff; font-weight: bold'>" . $_SESSION['payment_message'] . "</p>";
        unset($_SESSION['payment_message']);
    }

    $payQ = $conn->query("SELECT due_date, amount, status, paid_at FROM payments WHERE user_id = $user_id ORDER BY due_date ASC");
    if ($payQ && $payQ->num_rows > 0):
        while ($row = $payQ->fetch_assoc()):
            echo "<p>";
            echo "לתשלום עד: " . date("d/m/Y", strtotime($row['due_date'])) . " - סכום: " . number_format($row['amount'], 2) . " ₪";
            echo " - סטטוס: {$row['status']}";
            if ($row['status'] === 'שולם' && $row['paid_at']) {
                echo " בתאריך: " . date("d/m/Y", strtotime($row['paid_at']));
            }
            echo "</p>";
        endwhile;
    else:
        echo "<p>אין דרישות תשלום כרגע.</p>";
    endif;
    ?>

    <h4>📌 בחר תוכנית תשלום:</h4>
    <form method="POST" action="request_payment_plan.php">
        <select name="plan_id" required>
            <option value="">-- בחר תוכנית --</option>
            <?php foreach ($plans as $plan): ?>
                <option value="<?= $plan['id'] ?>">
                    <?= htmlspecialchars($plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">📩 בקש תוכנית</button>
    </form>
</section>

</body>
</html>
