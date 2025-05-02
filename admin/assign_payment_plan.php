<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "Db_Management_App";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("שגיאה בהתחברות: " . $conn->connect_error);
}

$message = "";
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = (int) $_POST['payment_id'];
    $amount = (float) $_POST['amount'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE payments SET amount = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("dsi", $amount, $due_date, $payment_id);

    if ($stmt->execute()) {
        $stmt->close(); // ✅ קודם סוגרים את ה-statement
        header("Location: " . $_SERVER['PHP_SELF']); // ✅ ואז מבצעים הפנייה מחדש
        exit;
    } else {
        $message = "❌ שגיאה בעדכון התשלום.";
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $payment_id = (int) $_POST['payment_id'];
    $stmt = $conn->prepare("UPDATE payments SET status = 'שולם', paid_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $stmt->close();
    $message = "✅ תשלום סומן כשולם.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['plan_id']) && !isset($_POST['update_payment'])) {
    $user_id = (int) $_POST['user_id'];
    $plan_id = (int) $_POST['plan_id'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM payments WHERE user_id = ? AND due_date >= CURDATE()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res['total'] > 0) {
        $message = "⚠️ למשתמש כבר קיימים תשלומים עתידיים. לא ניתן להקצות שוב.";
    } else {
        $stmt = $conn->prepare("SELECT duration_months, price FROM payment_plans WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($plan) {
            $months = (int) $plan['duration_months'];
            $price = (float) $plan['price'];
            $monthly_amount = round($price / $months, 2);

            $success = 0;
            $today = new DateTime();

            for ($i = 0; $i < $months; $i++) {
                $due_date = (clone $today)->modify("+$i month")->format('Y-m-d');
                $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_id, due_date, amount) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisd", $user_id, $plan_id, $due_date, $monthly_amount);
                if ($stmt->execute()) {
                    $success++;
                }
                $stmt->close();
            }
            $message = "✅ נוצרו בהצלחה $success תשלומים חודשיים עבור המשתמש.";
        } else {
            $message = "⚠️ תוכנית לא נמצאה.";
        }
    }
}

$users = [];
$result = $conn->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name ASC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$plans = [];
$result = $conn->query("SELECT id, name, price FROM payment_plans ORDER BY price ASC");
while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}

$unpaid = [];
$sql = "SELECT p.id, u.full_name, p.due_date, p.amount, p.status, pp.name AS plan_name 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        JOIN payment_plans pp ON p.plan_id = pp.id 
        WHERE p.status = 'לא שולם' 
        ORDER BY p.due_date ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unpaid[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>ניהול תשלומים</title>
    <link rel="stylesheet" href="../assets/css/admin_css/assign_payment_plan.css">

</head>
<body class="default-bg">

<h2>📩 הקצאת תוכנית תשלום</h2>

<?php if (!empty($message)): ?>
    <div class="message"> <?= htmlspecialchars($message) ?> </div>
<?php endif; ?>

<form method="POST">
    <label>בחר משתמש:</label>
    <select name="user_id" required>
        <option value="">-- בחר --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>בחר תוכנית:</label>
    <select name="plan_id" required>
        <option value="">-- בחר --</option>
        <?php foreach ($plans as $plan): ?>
            <option value="<?= $plan['id'] ?>">
                <?= htmlspecialchars($plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">📥 הקצה</button>
</form>

<h2>💳 תשלומים פתוחים</h2>
<table>
    <tr>
        <th>שם משתמש</th>
        <th>סוג תוכנית</th>
        <th>תאריך</th>
        <th>סכום</th>
        <th>סטטוס</th>
        <th>פעולה</th>
    </tr>
    <?php foreach ($unpaid as $row): ?>
        <?php if ($edit_id === (int)$row['id']): ?>
            <form method="POST">
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['plan_name']) ?></td>
                    <td><input type="date" name="due_date" value="<?= $row['due_date'] ?>"></td>
                    <td><input type="number" step="0.01" name="amount" value="<?= $row['amount'] ?>"> ₪</td>
                    <td><?= $row['status'] ?></td>
                    <td class="inline-actions">
                        <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="update_payment">💾 שמור</button>
                        <a href="?">❌ ביטול</a>
                    </td>
                </tr>
            </form>
        <?php else: ?>
            <tr>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['plan_name']) ?></td>
                <td><?= htmlspecialchars($row['due_date']) ?></td>
                <td><?= number_format($row['amount'], 2) ?> ₪</td>
                <td><?= $row['status'] ?></td>
                <td class="inline-actions">
                    <a href="?edit=<?= $row['id'] ?>">✏️ ערוך</a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="mark_paid" class="small-button">✅ סמן כשולם</button>
                        </form>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
</table>






</body>
</html>
