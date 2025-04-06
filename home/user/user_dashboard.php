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
/* Reset and base styles */
.header-container {
    direction: rtl;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    width: 100%;
    background-color: #f8f9fa;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
}

.header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.welcome-message h2 {
    color: #2c3e50;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.header-buttons {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

/* Unified button styling with explicit height control */
.header-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 6px;
    background-color: #3498db;
    color: white;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: inherit;
    line-height: 1.5;
    box-sizing: border-box;
    text-align: center;
    height: 40px; /* Fixed height */
    min-width: 120px; /* Minimum width */
}

/* Button reset */
button.header-button {
    appearance: none;
    -webkit-appearance: none;
    /* Additional button-specific resets */
    margin: 0;
    overflow: visible;
    text-transform: none;
}

/* Anchor tag specific adjustments */
a.header-button {
    /* Ensure anchor tags don't have any extra spacing */
    vertical-align: middle;
    /* Match button's line-height behavior */
    white-space: nowrap;
}

.header-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Specific button colors */
.header-button.appointments-btn { background-color: #2ecc71; }
.header-button.menu-btn { background-color: #e74c3c; }
.header-button.payment-btn { background-color: #9b59b6; }
.header-button.home-btn { background-color: #34495e; }

.header-button:active {
    transform: translateY(0);
}

.icon {
    font-size: 1.1rem;
}

/* Add padding to body */
body {
    padding-top: 120px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .header {
        padding: 1rem;
    }
    
    .welcome-message h2 {
        font-size: 1.3rem;
        text-align: center;
    }
    
    .header-buttons {
        justify-content: center;
    }
    
    .header-button {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        height: 36px; /* Slightly smaller on mobile */
        min-width: 100px;
    }

    body {
        padding-top: 100px;
    }
}

@media (max-width: 480px) {
    .header-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .header-button {
        width: 100%;
        height: 42px; /* Taller for touch targets */
    }

    body {
        padding-top: 180px;
    }
}
/* Overlay Styles */
.overlay {
   position: fixed;
   top: 0;
   left: 0;
   width: 100%;
   height: 100%;
   background-color: rgba(0, 0, 0, 0.5);
   z-index: 900;
   display: none;
}

/* Active section with overlay effect */
.section-active {
   position: relative;
   z-index: 950;
   animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
   from { opacity: 0; }
   to { opacity: 1; }
}
    </style>
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
            <a class="header-button home-btn" href="../home.php">
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
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // הסתר את כל הסקשנים כברירת מחדל
    var sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    sections.forEach(function(id) {
        var section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
        }
    });
});

function showSection(sectionId) {
    // הסתר את כל הסקשנים
    var sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    sections.forEach(function(id) {
        var section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
            section.querySelector('section').classList.remove('section-active');
        }
    });

    // הצג את האוברליי
    var overlay = document.getElementById('pageOverlay');
    overlay.style.display = 'block';

    // הצג את הסקשן שנבחר
    var selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.style.display = 'block';
        selectedSection.querySelector('section').classList.add('section-active');
        
        // הוסף אפקט הנפשה לסקשן
        setTimeout(function() {
            selectedSection.scrollIntoView({behavior: 'smooth'});
        }, 100);
    }
    
    // הוסף אפשרות לסגור את האוברליי בלחיצה
    overlay.onclick = function() {
        sections.forEach(function(id) {
            var section = document.getElementById(id);
            if (section) {
                section.style.display = 'none';
                if (section.querySelector('section')) {
                    section.querySelector('section').classList.remove('section-active');
                }
            }
        });
        this.style.display = 'none';
    };
}

 
</script>

</body>
</html>
