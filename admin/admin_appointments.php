<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("\u26d4 אין גישה. עמוד זה מיועד רק למנהלים.");
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
        $message = "\u26a0\ufe0f נא למלא את כל השדות.";
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
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 40px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        .actions {
            margin-bottom: 30px;
        }
        table {
            margin: 0 auto;
            border-collapse: collapse;
            width: 95%;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #fcd7a2;
        }
        a.button, button {
            background-color: #007bff;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        a.button:hover, button:hover {
            background-color: #0056b3;
        }
        .danger {
            background-color: #dc3545;
        }
        .danger:hover {
            background-color: #a71d2a;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, select {
            margin-bottom: 10px;
            padding: 10px;
            width: 100%;
            font-size: 16px;
        }
        .message {
            font-weight: bold;
            margin: 20px 0;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 10px 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .back-button a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<h2>ניהול פגישות</h2>

<div class="back-button">
    <a href="admin_dashboard.php">⬅️ חזרה לדשבורד</a>
</div>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="actions">
    <a href="admin_appointments.php?new=1" class="button">➕ הוסף פגישה חדשה</a>
</div>

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

</body>
</html>
