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
    <link rel="stylesheet" href="../../assets/css/user_css/user_dashboard.css">

</head>
<body>
<div id="pageOverlay" class="overlay"></div>

<?php if (isset($_SESSION['meal_message'])): ?>
    <div class="message"><?= $_SESSION['meal_message'] ?></div>
    <?php unset($_SESSION['meal_message']); ?>
<?php endif; ?>

<div class="header-container">
    <div class="header">
        <div class="welcome-message">
            <h2>שלום <?= htmlspecialchars($full_name) ?>, ברוך הבא לאזור האישי שלך</h2>
        </div>
        <div class="header-buttons">
            <button class="header-button appointments-btn" onclick="showSection('appointmentsSection')">
                <span class="icon">📅</span>
                <span class="text">פגישות</span>
            </button>
            <button class="header-button menu-btn" onclick="showSection('menuSection')">
                <span class="icon">🍽️</span>
                <span class="text">תפריט</span>
            </button>
            <button class="header-button payment-btn" onclick="showSection('paymentSection')">
                <span class="icon">💳</span>
                <span class="text">תשלום</span>
            </button>
            <a class="header-button home-btn" href="../../home.php">
                <span class="icon">⬅</span>
                <span class="text">חזרה ל־Home</span>
            </a>
        </div>
    </div>
</div>


<div id="appointmentsSection" style="margin-top: 20px;">
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

 

<div id="menuSection" style="margin-top: 20px;">
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


 
<div id="paymentSection" style="margin-top: 20px;">
    <section>
        <h3>💳 מצב תשלום:</h3>
        <?php
        if (isset($_SESSION['payment_message'])) {
            echo "<div class='message'>" . $_SESSION['payment_message'] . "</div>";
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
        <form method="POST" action="">
        <select name="plan_id" required>
                <option value="">-- בחר תוכנית --</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= $plan['id'] ?>">
                        <?= htmlspecialchars($plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="submit_plan">📩 בקש תוכנית</button>
            </form>
    </section>
    </div>
</div>
<script src="../../assets/js/user_dashboard.js"></script>
</body>
</html>
