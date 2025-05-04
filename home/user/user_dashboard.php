<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header(header: "Location: ../../login/login.php");
    exit;
}

require_once '../../admin/db.php';
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$plans = [];
$planResults = $conn->query(query: "SELECT id, name, price FROM payment_plans ORDER BY duration_months ASC");
while ($row = $planResults->fetch_assoc()) {
    $plans[] = $row;
}

$meal_types = ['בוקר', 'ביניים1', 'צהריים', 'ביניים2', 'ערב', 'לפני שינה'];
$days = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];

if (isset($_GET['day']) || isset($_GET['meal_type'])) {
    $_SESSION['active_section'] = 'menuSection';
    $_SESSION['selected_day'] = $_GET['day'] ?? '';
    $_SESSION['selected_meal_type'] = $_GET['meal_type'] ?? '';
    header("Location: user_dashboard.php");
    exit;
}

$selected_day = $_SESSION['selected_day'] ?? '';
$selected_meal_type = $_SESSION['selected_meal_type'] ?? '';
unset($_SESSION['selected_day'], $_SESSION['selected_meal_type']);

$active_section = $_SESSION['active_section'] ?? '';
unset($_SESSION['active_section']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_plan'])) {
    $plan_id = (int)$_POST['plan_id'];

    // שלוף את הסכום מהתוכנית
    $plan_stmt = $conn->prepare("SELECT price FROM payment_plans WHERE id = ?");
    $plan_stmt->bind_param("i", $plan_id);
    $plan_stmt->execute();
    $plan_stmt->bind_result($price);
    $plan_stmt->fetch();
    $plan_stmt->close();

    if ($price) {
        // הגדר תאריך יעד לתשלום (למשל 7 ימים מהיום)
        $due_date = date('Y-m-d', strtotime('+7 days'));

        $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_id, due_date, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisd", $user_id, $plan_id, $due_date, $price);
        $stmt->execute();
        $stmt->close();

        $_SESSION['payment_message'] = "✅ בקשת התשלום נרשמה בהצלחה!";
        $_SESSION['active_section'] = 'paymentSection';
        header("Location: user_dashboard.php");
        exit;
    } else {
        $_SESSION['payment_message'] = "❌ שגיאה: לא נמצאה תוכנית.";
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consumed'])) {
    $day = $_POST['day'];
    $meal_type = $_POST['meal_type'];
    $actual = trim($_POST['actual']);

    $stmt = $conn->prepare("REPLACE INTO user_meals_actual (user_id, day_of_week, meal_type, actual) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $day, $meal_type, $actual);
    $stmt->execute();
    $stmt->close();

    $_SESSION['meal_message'] = "✅ הארוחה נשמרה בהצלחה!";
    $_SESSION['active_section'] = 'menuSection';
    header(header: "Location: user_dashboard.php"); 
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
    <link href="https://fonts.googleapis.com/css2?family=Suez+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Attraction&display=swap" rel="stylesheet">

</head>
<body data-active-section="<?= $active_section ?>">
<header>
    <div class="logo">
        <img src="../../assets/images/logo2.png" alt="לוגו">
    </div>
    <nav>
        <ul>
            <li><a href="../../home.php">בית</a></li>
            <li><a href="#">נעים להכיר</a></li>
            <li><a href="#">תוכניות</a></li>
            <li><a href="#">תשאלו אותם</a></li>
            <li><a href="../../home/price/price.php">תפריטים ועוד</a></li>

            <?php if (isset($_SESSION['username'])): ?>
                <li><a href="user_dashboard.php">שלום, <?= htmlspecialchars(string: $_SESSION['username']) ?></a></li>
                <li><a href="../../login/logout.php">התנתקות</a></li>
            <?php else: ?>
                <li><a href="../../login/login.html" class="login-btn">התחברות</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div id="pageOverlay" class="overlay"></div>

<?php if (isset($_SESSION['meal_message'])): ?>
    <div class="message"><?= $_SESSION['meal_message'] ?></div>
    <?php unset($_SESSION['meal_message']); ?>
<?php endif; ?>

<div class="sidebar">
    <div class="sidebar-buttons">
        <button class="header-button appointments-btn" onclick="showSection('appointmentsSection')">
            📅 פגישות
        </button>
        <button class="header-button menu-btn" onclick="showSection('menuSection')">
            🍽️ תפריט
        </button>
        <button class="header-button payment-btn" onclick="showSection('paymentSection')">
            💳 תשלום
        </button>

    </div>
</div>

<div class="welcome-banner">
    <h2>שלום <?= htmlspecialchars($full_name) ?>, ברוך הבא לאזור האישי שלך</h2>
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
        <form method="GET" action="user_dashboard.php" onsubmit="setMenuSection()">
            <label>סנן לפי יום:</label>
            <select name="day">
                <option value="">-- הצג הכל --</option>
                <?php foreach ($days as $day): ?>
                    <option value="<?= $day ?>" <?= ($day === $selected_day ? 'selected' : '') ?>><?= $day ?></option>
                <?php endforeach; ?>
            </select>

            <label>סנן לפי סוג ארוחה:</label>
            <select name="meal_type">
                <option value="">-- הצג הכל --</option>
                <?php foreach ($meal_types as $type): ?>
                    <option value="<?= $type ?>" <?= ($type === $selected_meal_type ? 'selected' : '') ?>><?= $type ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">סנן</button>
        </form>

        <?php
        foreach ($days as $day):
            if ($selected_day && $day !== $selected_day) continue;
            echo "<h4>📆 יום $day:</h4>";
            echo "<ul>";
            foreach ($meal_types as $type):
                if ($selected_meal_type && $type !== $selected_meal_type) continue;

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
                
                $comment = $actual_meals[$day][$type]['comment'] ?? '';
                if ($comment !== '') {
                    echo "<div style='margin-top:5px; background-color: lightyellow; padding: 5px; border-radius: 5px;'>📝 הערת המנהל: " . htmlspecialchars($comment) . "</div>";
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

$sql = "SELECT due_date, amount, status, paid_at, request_status 
        FROM payments 
        WHERE user_id = $user_id 
        ORDER BY due_date ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        $due_date = date("d/m/Y", strtotime($row['due_date']));
        $amount = number_format($row['amount'], 2) . " ₪";

        // קביעת סטטוס להצגה
        if ($row['request_status'] === 'בהמתנה') {
            $status = 'בהמתנה לאישור';
        } elseif ($row['request_status'] === 'נדחה') {
            $status = 'נדחה';
        } elseif ($row['status'] === 'שולם') {
            $status = 'שולם';
        } else {
            $status = 'לא שולם';
        }

        echo "<p>לתשלום עד: $due_date - סכום: $amount - סטטוס: $status";

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
        <form method="POST" action="user_dashboard.php">
            <select name="plan_id" required>
                <option value="">-- בחר תוכנית --</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= $plan['id'] ?>">
                        <?= htmlspecialchars(string: $plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="submit_plan">📩 בקש תוכנית</button>
        </form>
    </section>
</div>
<script src="../../assets/js/user_dashboard.js"></script>
</body>
</html>
