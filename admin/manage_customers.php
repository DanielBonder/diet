<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// בדיקת הרשאות - רק admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';
$message = "";

// מצב עריכה
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;

// ✅ מחיקת לקוח
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $id AND is_admin = 0");
    header("Location: manage_customers.php");
    exit;
}

// ✅ עדכון לקוח
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $bmi = $height > 0 ? $weight / pow($height / 100, 2) : null;

    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, weight=?, height=?, bmi=? WHERE id=? AND is_admin=0");
    $stmt->bind_param("sssdddi", $fullName, $username, $email, $weight, $height, $bmi, $id);
    if ($stmt->execute()) {
        $message = "✅ פרטי הלקוח עודכנו בהצלחה!";
        header("Location: manage_customers.php");
        exit;
    } else {
        $message = "❌ שגיאה בעדכון: " . $stmt->error;
    }
    $stmt->close();
}

// ✅ הוספת לקוח חדש
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    $fullName = trim($_POST['full_name']);
    $userName = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);

    if (empty($fullName) || empty($userName) || empty($email) || empty($password) || empty($weight) || empty($height)) {
        $message = "❗ כל השדות חובה";
    } else {
        $bmi = $height > 0 ? $weight / pow($height / 100, 2) : null;

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $userName, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "⚠️ שם המשתמש או האימייל כבר קיימים במערכת.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, weight, height, bmi, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("ssssddd", $fullName, $userName, $email, $password, $weight, $height, $bmi);
            if ($stmt->execute()) {
                $message = "✅ לקוח נוסף בהצלחה!";
                header("Location: manage_customers.php");
                exit;
            } else {
                $message = "❌ שגיאה בהוספה: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// ✅ שליפת כל המשתמשים (לא אדמינים)
$users = [];
$result = $conn->query("SELECT id, full_name, username, email, weight, height, bmi FROM users WHERE is_admin = 0 ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>ניהול לקוחות</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            text-align: center;
            padding: 40px;
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
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
        }
        th {
            background-color: #ffdb99;
        }
        input, button {
            padding: 8px;
            margin: 5px;
        }
        .message {
            color: #2e7d32;
            font-weight: bold;
        }
        .actions a, .actions button {
            margin: 2px;
            padding: 6px 10px;
            text-decoration: none;
            font-size: 14px;
        }
        .edit-btn {
            background-color: #1976d2;
            color: white;
            border: none;
        }
        .delete-btn {
            background-color: #d32f2f;
            color: white;
            border: none;
        }
        .save-btn {
            background-color: #388e3c;
            color: white;
            border: none;
        }
        .cancel-btn {
            background-color: #9e9e9e;
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="back-button">
        <a href="admin_dashboard.php">⬅️ חזרה לדשבורד</a>
    </div>

    <h1>ניהול לקוחות</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>הוספת לקוח חדש</h2>
    <form method="POST">
        <input type="text" name="full_name" placeholder="שם מלא" required>
        <input type="text" name="username" placeholder="שם משתמש" required>
        <input type="email" name="email" placeholder="אימייל" required>
        <input type="password" name="password" placeholder="סיסמה" required>
        <input type="number" name="weight" placeholder="משקל (ק\"ג)" step="0.1" required>
        <input type="number" name="height" placeholder="גובה (ס\"מ)" step="0.1" required>
        <button type="submit" name="add_new">➕ הוסף לקוח</button>
    </form>

    <h2>רשימת לקוחות</h2>
    <table>
        <tr>
            <th>#</th>
            <th>שם מלא</th>
            <th>שם משתמש</th>
            <th>אימייל</th>
            <th>משקל</th>
            <th>גובה</th>
            <th>BMI</th>
            <th>פעולות</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <?php if ($edit_id == $user['id']): ?>
            <form method="POST">
                <td><?= $user['id'] ?></td>
                <td><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"></td>
                <td><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"></td>
                <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></td>
                <td><input type="number" name="weight" step="0.1" value="<?= htmlspecialchars($user['weight']) ?>"></td>
                <td><input type="number" name="height" step="0.1" value="<?= htmlspecialchars($user['height']) ?>"></td>
                <td><?= number_format($user['bmi'], 2) ?></td>
                <td class="actions">
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    <button type="submit" name="update" class="save-btn">💾 שמור</button>
                    <a href="manage_customers.php" class="cancel-btn">❌ ביטול</a>
                </td>
            </form>
            <?php else: ?>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['full_name']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['weight']) ?></td>
                <td><?= htmlspecialchars($user['height']) ?></td>
                <td><?= number_format($user['bmi'], 2) ?></td>
                <td class="actions">
                    <a href="manage_customers.php?edit=<?= $user['id'] ?>" class="edit-btn">✏️ ערוך</a>
                    <a href="manage_customers.php?delete=<?= $user['id'] ?>" class="delete-btn" onclick="return confirm('האם אתה בטוח שברצונך למחוק לקוח זה?')">🗑️ מחק</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
