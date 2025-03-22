<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// בדיקת הרשאות - רק admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';

// טיפול במחיקה
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $delete_id");
    header("Location: manage_customers.php");
    exit;
}

// טיפול בעדכון או הוספה
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_id'])) {
        // עדכון
        $update_id = intval($_POST['save_id']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $username = $conn->real_escape_string($_POST['username']);

        $conn->query("UPDATE users SET full_name = '$full_name', email = '$email', username = '$username' WHERE id = $update_id");
        header("Location: manage_customers.php");
        exit;
    } elseif (isset($_POST['add_new'])) {
        // הוספה
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $username = $conn->real_escape_string($_POST['username']);
        $password = $conn->real_escape_string($_POST['password']);
        $weight = floatval($_POST['weight']);
        $height = floatval($_POST['height']);
        $bmi = $height > 0 ? $weight / pow($height / 100, 2) : null;

        $sql = "INSERT INTO users (full_name, email, username, password, weight, height, bmi, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssdd", $full_name, $email, $username, $password, $weight, $height, $bmi);
            $stmt->execute();
            $stmt->close();
        } else {
            die("שגיאה בהכנת השאילתה: " . $conn->error);
        }

        header("Location: manage_customers.php");
        exit;
    }
}

$result = $conn->query("SELECT id, full_name, email, username FROM users ORDER BY id DESC");
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
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
            background-color: #f5f5f5;
            padding: 40px;
            text-align: center;
        }
        h1 {
            margin-bottom: 30px;
        }
        table {
            margin: 0 auto 40px auto;
            border-collapse: collapse;
            width: 90%;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 20px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #ffe4b5;
        }
        a.btn, button.btn {
            background-color: #ff6347;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        a.btn:hover, button.btn:hover {
            background-color: #e5533d;
        }
        .update-form, .add-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }
        .update-form input, .add-form input {
            padding: 6px;
            font-size: 14px;
            width: 250px;
        }
    </style>
</head>
<body>
    <h1>ניהול לקוחות</h1>

    <h2>הוספת לקוח חדש</h2>
    <form method="POST" class="add-form">
        <input type="text" name="full_name" placeholder="שם מלא" required>
        <input type="email" name="email" placeholder="אימייל" required>
        <input type="text" name="username" placeholder="שם משתמש" required>
        <input type="text" name="password" placeholder="סיסמה" required>
        <input type="number" step="0.01" name="weight" placeholder="משקל (ק"ג)" required>
        <input type="number" step="0.01" name="height" placeholder="גובה (ס"מ)" required>
        <button type="submit" name="add_new" class="btn">הוסף לקוח</button>
    </form>

    <h2>רשימת לקוחות קיימים</h2>
    <table>
        <tr>
            <th>מספר לקוח</th>
            <th>שם מלא</th>
            <th>אימייל</th>
            <th>שם משתמש</th>
            <th>עדכון</th>
            <th>מחיקה</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <?php if ($edit_id === intval($row['id'])): ?>
                    <form method="POST" class="update-form">
                        <td><?= $row['id'] ?><input type="hidden" name="save_id" value="<?= $row['id'] ?>"></td>
                        <td><input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>"></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>"></td>
                        <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>"></td>
                        <td><button type="submit" class="btn">שמור</button></td>
                        <td><a href="manage_customers.php" class="btn">ביטול</a></td>
                    </form>
                <?php else: ?>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><a href="manage_customers.php?edit=<?= $row['id'] ?>" class="btn">עדכן</a></td>
                    <td><a href="manage_customers.php?delete=<?= $row['id'] ?>" class="btn" onclick="return confirm('האם אתה בטוח שברצונך למחוק לקוח זה?')">מחק</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
