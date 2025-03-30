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

$plans = [];
$planResults = $conn->query("SELECT id, name, price FROM payment_plans ORDER BY duration_months ASC");
while ($row = $planResults->fetch_assoc()) {
    $plans[] = $row;
}

$meal_types = ['בוקר', 'ביניים1', 'צהריים', 'ביניים2', 'ערב', 'לפני שינה'];
$days = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
$selected_day = $_GET['day'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consumed'])) {
    $day = $_POST['day'];
    $meal_type = $_POST['meal_type'];
    $actual = trim($_POST['actual']);

    $stmt = $conn->prepare("REPLACE INTO user_meals_actual (user_id, day_of_week, meal_type, actual) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $day, $meal_type, $actual);
    $stmt->execute();
    $stmt->close();

    $_SESSION['meal_message'] = "✅ הארוחה נשמרה בהצלחה!";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$weekly_menu = [];
$sql = "SELECT day_of_week, meal_type, description FROM user_weekly_menus WHERE user_id = $user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $weekly_menu[$row['day_of_week']][$row['meal_type']] = $row['description'];
}

$actual_meals = [];
$sql = "SELECT day_of_week, meal_type, actual, comment, created_at FROM user_meals_actual WHERE user_id = $user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $actual_meals[$row['day_of_week']][$row['meal_type']] = [
        'text' => $row['actual'],
        'comment' => $row['comment'],
        'time' => $row['created_at']
    ];
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
        textarea {
            width: 100%;
            margin-top: 5px;
        }
        .message {
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 15px;
        }
        small {
            color: gray;
        }
    </style>
</head>
<body>
<div style="text-align: left; margin-bottom: 20px;">
    <a href="../home.php" style="text-decoration: none; background-color: #007bff; color: white; padding: 10px 20px; border-radius: 8px;">⬅ חזרה ל־Home</a>
</div>

<h2>שלום <?= htmlspecialchars($full_name) ?>, ברוך הבא לאזור האישי שלך</h2>

<?php if (isset($_SESSION['meal_message'])): ?>
    <div class="message"><?= $_SESSION['meal_message'] ?></div>
    <?php unset($_SESSION['meal_message']); ?>
<?php endif; ?>
<button onclick="toggleSection('appointmentsSection')">📅 הפגישות שלי</button>

<div id="appointmentsSection" style="display: none; margin-top: 20px;">
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
</div>

<button onclick="toggleSection('menuSection')" style="background-color:#28a745; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; margin-top: 20px;">
    🍽️ הצג/הסתר תפריט שבועי
</button>

<div id="menuSection" style="display: none; margin-top: 20px;">
    <section>
        <h3>🍽️ התפריט השבועי שלך:</h3>
        <form method="GET">
            <label>סנן לפי יום:</label>
            <select name="day" onchange="this.form.submit()">
                <option value="">-- הצג הכל --</option>
                <?php foreach ($days as $day): ?>
                    <option value="<?= $day ?>" <?= ($day === $selected_day ? 'selected' : '') ?>><?= $day ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php
        foreach ($days as $day):
            if ($selected_day && $day !== $selected_day) continue;
            echo "<h4>📆 יום $day:</h4>";
            echo "<ul>";
            foreach ($meal_types as $type):
                $desc = $weekly_menu[$day][$type] ?? '';
                $existing = $actual_meals[$day][$type]['text'] ?? '';
                $updated_at = $actual_meals[$day][$type]['time'] ?? null;

                echo "<li><strong>$type:</strong> " . htmlspecialchars($desc) . "</li>";
                echo '<form method="POST">';
                echo "<input type='hidden' name='day' value='$day'>";
                echo "<input type='hidden' name='meal_type' value='$type'>";
                echo "<textarea name='actual' rows='2' placeholder='מה אכלת בפועל?'>" . htmlspecialchars($existing) . "</textarea>";
                if ($updated_at) {
                    echo "<small>עודכן לאחרונה: " . date("d/m/Y H:i", strtotime($updated_at)) . "</small><br>";
                }
                $btn_label = $existing ? '🔄 עדכן' : '📩 שמור';
                echo "<button type='submit' name='consumed'>$btn_label</button>";
                echo '</form>';
            endforeach;
            echo "</ul>";
        endforeach;
        ?>
    </section>
</div>


<button onclick="toggleSection('paymentSection')" style="background-color:#28a745; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; margin-top: 20px;">
    💳 הצג/הסתר מצב תשלום
</button>

<div id="paymentSection" style="display: none; margin-top: 20px;">
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
</div>

<script>
function toggleSection(id) {
    var section = document.getElementById(id);
    if (section.style.display === 'none') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}
</script>

</body>
</html>
