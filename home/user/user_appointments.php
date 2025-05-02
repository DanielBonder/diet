<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once '../../admin/db.php';

$message = "";
$available_slots = [];

$selected_type = $_POST['meeting_type'] ?? '';
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';
$from_time = $_POST['from_time'] ?? '';
$to_time = $_POST['to_time'] ?? '';

$today = date('Y-m-d');

$duration = 0;
if ($selected_type === 'initial') {
    $duration = 60;
} elseif ($selected_type === 'weekly') {
    $duration = 15;
}

// בדיקת תאריכים מהעבר
if ($from_date && $from_date < $today) {
    $message = "❗ תאריך ההתחלה לא יכול להיות לפני היום.";
    $available_slots = [];
}
if ($to_date && $to_date < $today) {
    $message = "❗ תאריך הסיום לא יכול להיות לפני היום.";
    $available_slots = [];
}

// טיפול בקביעת פגישה
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['available_date']) && isset($_POST['available_time']) && empty($message)) {
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'];
    $date = $_POST['available_date'];
    $time = $_POST['available_time'];
    $meeting_type = $_POST['meeting_type'];

    $start_dt = new DateTime("$date $time");
    $end_dt = clone $start_dt;
    $end_dt->modify("+$duration minutes");

    $slots_needed = [];
    $slot = clone $start_dt;
    while ($slot < $end_dt) {
        $slots_needed[] = [
            'date' => $slot->format('Y-m-d'),
            'time' => $slot->format('H:i')
        ];
        $slot->modify('+15 minutes');
    }

    $all_available = true;
    foreach ($slots_needed as $s) {
        $stmt = $conn->prepare("SELECT * FROM availability WHERE available_date = ? AND available_time = ?");
        $stmt->bind_param("ss", $s['date'], $s['time']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $all_available = false;
            break;
        }
        $stmt->close();
    }

    if ($all_available) {
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, full_name, available_date, available_time, meeting_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $full_name, $date, $time, $meeting_type);
        if ($stmt->execute()) {
            foreach ($slots_needed as $s) {
                $del = $conn->prepare("DELETE FROM availability WHERE available_date = ? AND available_time = ?");
                $del->bind_param("ss", $s['date'], $s['time']);
                $del->execute();
                $del->close();
            }
            $message = "✅ הפגישה נקבעה בהצלחה!";
        } else {
            $message = "❌ שגיאה בקביעת הפגישה: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ הזמן שנבחר אינו זמין לכל משך הפגישה.";
    }
}

// שליפת זמינויות מסוננות
if ($duration > 0 && $from_date && $to_date && $from_time && $to_time && empty($message)) {
    $sql = "SELECT available_date, available_time 
            FROM availability 
            WHERE available_date BETWEEN ? AND ? 
              AND available_time BETWEEN ? AND ?
            ORDER BY available_date, available_time";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $from_date, $to_date, $from_time, $to_time);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['available_date'] . ' ' . $row['available_time']);
        $end = clone $start;
        $end->modify("+$duration minutes");

        $valid = true;
        $check = clone $start;
        while ($check < $end) {
            $check_date = $check->format('Y-m-d');
            $check_time = $check->format('H:i');

            $stmt2 = $conn->prepare("SELECT * FROM availability WHERE available_date = ? AND available_time = ?");
            $stmt2->bind_param("ss", $check_date, $check_time);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows === 0) {
                $valid = false;
                break;
            }
            $stmt2->close();
            $check->modify('+15 minutes');
        }

        if ($valid) {
            $available_slots[] = $row;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>קביעת פגישה</title>
    <link rel="stylesheet" href="../../assets/css/user_css/user_appointments.css">
</head>
<body>

<div class="back-button">
    <a href="user_dashboard.php">⬅️ חזרה לאזור האישי</a>
</div>

<h2>קבע פגישה עם המנהל</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- טופס סינון -->
<form method="POST">
    <?php $today = date('Y-m-d'); ?>
    <label>בחר סוג פגישה:</label>
    <select name="meeting_type" required>
        <option value="">-- בחר --</option>
        <option value="initial" <?= $selected_type === 'initial' ? 'selected' : '' ?>>פגישה ראשונית (60 דק')</option>
        <option value="weekly" <?= $selected_type === 'weekly' ? 'selected' : '' ?>>שקילה שבועית (15 דק')</option>
    </select>

    <label>טווח תאריכים:</label>
    <input type="date" name="from_date" min="<?= $today ?>" value="<?= $from_date ?>" required>
    <input type="date" name="to_date" min="<?= $today ?>" value="<?= $to_date ?>" required>

    <label>טווח שעות:</label>
    <input type="time" name="from_time" value="<?= $from_time ?>" required>
    <input type="time" name="to_time" value="<?= $to_time ?>" required>

    <button type="submit">הצג זמינות</button>
</form>

<!-- טבלת זמינים -->
<?php if (!empty($available_slots)): ?>
    <table>
        <tr>
            <th>תאריך</th>
            <th>שעה</th>
            <th>פעולה</th>
        </tr>
        <?php foreach ($available_slots as $slot): ?>
            <tr>
                <td><?= date("d/m/Y", strtotime($slot['available_date'])) ?></td>
                <td><?= substr($slot['available_time'], 0, 5) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="available_date" value="<?= $slot['available_date'] ?>">
                        <input type="hidden" name="available_time" value="<?= $slot['available_time'] ?>">
                        <input type="hidden" name="meeting_type" value="<?= $selected_type ?>">
                        <input type="hidden" name="from_date" value="<?= $from_date ?>">
                        <input type="hidden" name="to_date" value="<?= $to_date ?>">
                        <input type="hidden" name="from_time" value="<?= $from_time ?>">
                        <input type="hidden" name="to_time" value="<?= $to_time ?>">
                        <button type="submit">קבע פגישה</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($selected_type): ?>
    <p>לא נמצאו זמני פגישה שתואמים את הסינון שבחרת.</p>
<?php endif; ?>

</body>
</html>
