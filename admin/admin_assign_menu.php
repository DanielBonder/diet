<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("\u26d4\ufe0f אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';
$message = "";

require_once 'db.php';
$message = "";

// ✅ טיפול בהערה שנשלחת מהמנהל
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    $meal_id = (int)$_POST['meal_id'];
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("UPDATE user_meals_actual SET comment = ? WHERE id = ?");
    $stmt->bind_param("si", $comment, $meal_id);

    if ($stmt->execute()) {
        $message = "✅ ההערה נשמרה בהצלחה!";
    } else {
        $message = "❌ שגיאה בשמירת ההערה.";
    }
    $stmt->close();
}

// ✅ שמירת תפריט שבועי
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['menu'])) {
    $user_id = (int)$_POST['user_id'];
    $menu = $_POST['menu'];

    $conn->query("DELETE FROM user_weekly_menus WHERE user_id = $user_id");
    $stmt = $conn->prepare("INSERT INTO user_weekly_menus (user_id, day_of_week, meal_type, description) VALUES (?, ?, ?, ?)");

    foreach ($menu as $meal_type => $description) {
        foreach (['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'] as $day) {
            $desc = trim($description);
            if ($desc !== '') {
                $stmt->bind_param("isss", $user_id, $day, $meal_type, $desc);
                $stmt->execute();
            }
        }
    }

    $stmt->close();
    $message = "✅ התפריט נשמר בהצלחה לכל ימות השבוע!";
}


$users = $conn->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name ASC");
$meal_types = ['בוקר', 'ביניים1', 'צהריים', 'ביניים2', 'ערב', 'לפני שינה'];
$days_of_week = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];

// סינון מה המשתמש אכל בפועל
$filter_user = $_GET['filter_user'] ?? '';
$filter_day = $_GET['filter_day'] ?? '';
$filter_meal = $_GET['filter_meal'] ?? '';

$where = [];
if ($filter_user) $where[] = "u.id = " . intval($filter_user);
if ($filter_day) $where[] = "a.day_of_week = '" . $conn->real_escape_string($filter_day) . "'";
if ($filter_meal) $where[] = "a.meal_type = '" . $conn->real_escape_string($filter_meal) . "'";

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$actual_meals = [];
$actual_result = $conn->query("SELECT u.full_name, a.id, a.day_of_week, a.meal_type, a.actual, a.comment FROM user_meals_actual a JOIN users u ON u.id = a.user_id $where_sql ORDER BY u.full_name, a.day_of_week");
if ($actual_result && $actual_result->num_rows > 0) {
    while ($row = $actual_result->fetch_assoc()) {
        $actual_meals[$row['full_name']][] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>הקצאת תפריט שבועי</title>
    <link rel="stylesheet" href="../assets/css/admin_css/admin_assign_menu.css">
</head>
<body>

<div id="menuForm" style="display: none;">
    <h2>הקצאת תפריט שבועי למשתמש</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>בחר משתמש:</label>
        <select name="user_id" required>
            <option value="">-- בחר --</option>
            <?php
            // משתמשים כבר קיימים בשאילתה למעלה
            $user_result = $conn->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name ASC");
            while ($user = $user_result->fetch_assoc()): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <table>
            <tr>
                <th>סוג ארוחה</th>
                <th>תיאור</th>
            </tr>
            <?php foreach ($meal_types as $type): ?>
                <tr>
                    <td><?= $type ?></td>
                    <td><textarea name="menu[<?= $type ?>]" rows="3"></textarea></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit">📥 שמור תפריט לכל השבוע</button>
    </form>
</div>


<div id="actualMealsSection" style="display: none;">
    <h2>🍽️ מה המשתמשים אכלו בפועל</h2>
    <button onclick="toggleFilter()" style="margin-bottom: 10px;">🔽 סינון</button>

<div id="filterForm" style="display: none; margin-bottom: 20px;">
    <form method="GET">
        <label>סנן לפי משתמש:</label>
        <select name="filter_user">
            <option value="">-- כולם --</option>
            <?php
            $user_options = $conn->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name ASC");
            while ($u = $user_options->fetch_assoc()): ?>
                <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>סנן לפי יום:</label>
        <select name="filter_day">
            <option value="">-- כל הימים --</option>
            <?php foreach ($days_of_week as $day): ?>
                <option value="<?= $day ?>" <?= $filter_day == $day ? 'selected' : '' ?>><?= $day ?></option>
            <?php endforeach; ?>
        </select>

        <label>סנן לפי סוג ארוחה:</label>
        <select name="filter_meal">
            <option value="">-- כל הסוגים --</option>
            <?php foreach ($meal_types as $type): ?>
                <option value="<?= $type ?>" <?= $filter_meal == $type ? 'selected' : '' ?>><?= $type ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">🔍 בצע סינון</button>
    </form>
</div>


    <?php if (!empty($actual_meals)): ?>
        <?php foreach ($actual_meals as $full_name => $entries): ?>
            <h3><?= htmlspecialchars($full_name) ?></h3>
            <table>
                <tr>
                    <th>יום</th>
                    <th>סוג ארוחה</th>
                    <th>מה נאכל בפועל</th>
                </tr>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['day_of_week']) ?></td>
                        <td><?= htmlspecialchars($entry['meal_type']) ?></td>
                        <td>
                            <?= htmlspecialchars($entry['actual']) ?>
                            <form method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="meal_id" value="<?= $entry['id'] ?>">
                                <textarea name="comment" rows="2" placeholder="הערה..." style="width:100%;"><?= htmlspecialchars($entry['comment']) ?></textarea>
                                <button type="submit" name="update_comment" style="margin-top:5px;">💾 שמור הערה</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <p>לא נמצאו נתונים.</p>
    <?php endif; ?>
</div>

<script>

    function toggleFilter() {
        const filter = document.getElementById('filterForm');
        filter.style.display = filter.style.display === 'none' ? 'block' : 'none';
    }
    window.addEventListener("message", function(event) {
    if (event.data === "toggleMenuForm") {
        const form = document.getElementById('menuForm');
        if (form) form.style.display = 'block';
    } else if (event.data === "toggleActualMeals") {
        const section = document.getElementById('actualMealsSection');
        if (section) section.style.display = 'block';
    }
});

</script>

</body>
</html>
