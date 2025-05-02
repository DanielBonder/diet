<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

require_once '../../admin/db.php';
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// הודעת סטטוס תשלום (אם קיימת)
$payment_message = $_SESSION['payment_message'] ?? '';
unset($_SESSION['payment_message']);

// שליפת תוכניות תשלום
$planResults = $conn->query("SELECT id, name, price FROM payment_plans ORDER BY duration_months ASC");
$plans = [];
while ($row = $planResults->fetch_assoc()) {
    $plans[] = $row;
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>אזור אישי</title>
    <link rel="stylesheet" href="../../assets/css/home.css">
</head>
<body>

<h2>שלום <?= htmlspecialchars($full_name) ?>, ברוך הבא לאזור האישי שלך</h2>

<?php if ($payment_message): ?>
    <div class="message"><?= htmlspecialchars($payment_message) ?></div>
<?php endif; ?>

<section>
    <h3>📅 הפגישות שלך:</h3>
    <?php
    $result = $conn->query("SELECT * FROM appointments WHERE user_id = $user_id ORDER BY available_date ASC");
    if ($result && $result->num_rows > 0):
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
    <h3>🥗 התפריט שלך:</h3>
    <?php
    $menuQ = $conn->query("SELECT m.name, m.description, m.price FROM user_menus um JOIN menus m ON um.menu_id = m.id WHERE um.user_id = $user_id LIMIT 1");
    if ($menuQ && $menuQ->num_rows > 0):
        $menu = $menuQ->fetch_assoc();
        echo "<p><strong>{$menu['name']}</strong>: {$menu['description']}</p>";
        echo "<p>עלות: {$menu['price']} ₪</p>";
    else:
        echo "<p>לא הוקצה לך תפריט עדיין.</p>";
    endif;
    ?>
</section>

<section>
    <h3>💳 מצב תשלום:</h3>
    <?php
    $payQ = $conn->query("SELECT due_date, amount, status, paid_at FROM payments WHERE user_id = $user_id ORDER BY due_date ASC");
    if ($payQ && $payQ->num_rows > 0):
        while ($row = $payQ->fetch_assoc()):
            echo "<p>";
            echo "לתשלום עד: " . date("d/m/Y", strtotime($row['due_date'])) . " - סכום: " . number_format($row['amount'], 2) . " ₪";
            echo " - סטטוס: {$row['status']}";
            if ($row['status'] === 'שולם') {
                echo " (שולם בתאריך " . date("d/m/Y", strtotime($row['paid_at'])) . ")";
            }
            echo "</p>";
        endwhile;
    else:
        echo "<p>אין דרישות תשלום כרגע.</p>";
    endif;
    ?>

    <h4>📌 בקשת תוכנית תשלום:</h4>
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
