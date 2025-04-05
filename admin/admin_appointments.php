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
        .sidebar {
    position: fixed;
    top: 40px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
}

.sidebar .button,
.sidebar button {
    width: 200px;
    text-align: right;
}

.button.green {
    background-color: #28a745;
}
.button.green:hover {
    background-color: #218838;
}
#availabilityPanel {
  display: none;
  background-color: #ffffff;
  border: 1px solid #ccc;
  padding: 30px;
  margin: 30px auto;
  width: 450px;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
  text-align: right;
  overflow: auto;
  max-height: 90vh;
}

#availabilityPanel label {
  font-weight: bold;
  display: block;
  margin-top: 10px;
  margin-bottom: 5px;
  text-align: right;
}

#availabilityPanel input,
#availabilityPanel button {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  font-size: 16px;
  border-radius: 6px;
  border: 1px solid #ccc;
}


    </style>
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



<!-- סקריפט לפונקציית הפגישות -->
<script>
    window.addEventListener("message", function(event) {
        if (event.data === "toggleAppointments") {
            toggleAppointments();
        }
    });

function toggleAppointments() {
    const section = document.getElementById("appointmentsSection");
    if (section.style.display === "none" || section.style.display === "") {
        section.style.display = "block";
    } else {
        section.style.display = "none";
    }
}
window.addEventListener("message", function(event) {
    if (event.data === "darken") {
        document.body.style.backgroundColor = "#2a2a2a";
        document.body.style.color = "white";
    } else if (event.data === "lighten") {
        document.body.style.backgroundColor = "";
        document.body.style.color = "";
    }
});
</script>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- סקריפט לסלייד של טופס זמינות -->
<script>
$(document).ready(function(){
  $("#openAvailability").click(function(){
    $("#availabilityPanel").slideToggle("slow");
  });
});
</script>

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


</body>
</html>
