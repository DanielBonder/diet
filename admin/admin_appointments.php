<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(" אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';

$now_date = date('Y-m-d');
$now_time = date('H:i');

$conn->query("
    DELETE FROM appointments 
    WHERE 
        (available_date < '$now_date') OR 
        (available_date = '$now_date' AND available_time < '$now_time')
");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM appointments WHERE id = $id");
    header("Location: admin_appointments.php");
    exit;
}

$message = "";
$id = $_GET['edit'] ?? null;
$edit_data = null;
$show_form = isset($_GET['new']) || isset($_GET['edit']);

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_data = $res->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $available_date = $_POST['available_date'] ?? '';
    $available_time = $_POST['available_time'] ?? '';
    $meeting_type = $_POST['meeting_type'] ?? '';

    if ($full_name && $available_date && $available_time && $meeting_type) {
        if (isset($_POST['id']) && $_POST['id']) {
            $edit_id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE appointments SET full_name=?, available_date=?, available_time=?, meeting_type=? WHERE id=?");
            $stmt->bind_param("ssssi", $full_name, $available_date, $available_time, $meeting_type, $edit_id);
            $message = "\u2705 הפגישה עודכנה בהצלחה.";
            $stmt->execute();
            $stmt->close();
        } else {
            $admin_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, full_name, available_date, available_time, meeting_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $admin_id, $full_name, $available_date, $available_time, $meeting_type);
            $message = "\u2705 הפגישה נוספה בהצלחה.";
            $stmt->execute();
            $stmt->close();
        }
        header("Location: admin_appointments.php");
        exit;
    } else {
        $message = " נא למלא את כל השדות.";
        $show_form = true;
    }
}

$sql = "SELECT id, full_name, available_date, available_time, created_at FROM appointments ORDER BY available_date ASC, available_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>ניהול פגישות</title>
    <link rel="stylesheet" href="../assets/css/admin_css/admin_appointments.css">

</head>
<body>





<div id="appointmentsSection" style="display: none;">
    
    <h2>ניהול פגישות</h2>


    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>


    
    <?php if ($show_form): ?>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
        <input type="text" name="full_name" placeholder="שם מלא" value="<?= $edit_data['full_name'] ?? '' ?>" required>
        <input type="date" name="available_date" value="<?= $edit_data['available_date'] ?? '' ?>" min="<?= date('Y-m-d') ?>" required>
        <input type="time" name="available_time" value="<?= $edit_data['available_time'] ?? '' ?>" required>
        <select name="meeting_type" required>
            <option value="">-- סוג פגישה --</option>
            <option value="initial" <?= (isset($edit_data['meeting_type']) && $edit_data['meeting_type'] === 'initial') ? 'selected' : '' ?>>פגישה ראשונית</option>
            <option value="weekly" <?= (isset($edit_data['meeting_type']) && $edit_data['meeting_type'] === 'weekly') ? 'selected' : '' ?>>שקילה שבועית</option>
        </select>
        <button type="submit"><?= $edit_data ? 'עדכן פגישה' : 'הוסף פגישה' ?></button>
        <a href="admin_appointments.php" class="button danger" style="margin-top: 10px; display: inline-block;">❌ סגור</a>
    </form>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>שם משתמש</th>
                <th>תאריך</th>
                <th>שעה</th>
                <th>נקבע בתאריך</th>
                <th>פעולות</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['available_date'])) ?></td>
                    <td><?= substr($row['available_time'], 0, 5) ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="admin_appointments.php?edit=<?= $row['id'] ?>" class="button">✏️ ערוך</a>
                        <a href="admin_appointments.php?delete=<?= $row['id'] ?>" class="button danger" onclick="return confirm('האם אתה בטוח שברצונך למחוק את הפגישה?');">🗑️ מחק</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>אין פגישות שנקבעו עדיין.</p>
    <?php endif; ?>
</div>
<div id="availabilityPanel">
    <form method="POST" action="add_availability.php">
        <h3>הוספת זמינות לפי טווח תאריכים</h3>

        <label for="start_date">תאריך התחלה:</label>
        <input type="date" name="start_date" id="start_date" required>

        <label for="end_date">תאריך סיום:</label>
        <input type="date" name="end_date" id="end_date" required>

        <label for="start_time">שעת התחלה:</label>
        <input type="time" name="start_time" id="start_time" required>

        <label for="end_time">שעת סיום:</label>
        <input type="time" name="end_time" id="end_time" required>

        <button type="submit" class="button green">הוסף זמינות</button>
    </form>
</div>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- קובץ JS החיצוני שלך -->
<script src="../assets/js/admin_appointments.js"></script>

</body>
</html>
