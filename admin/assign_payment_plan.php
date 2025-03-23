<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';
$message = "";

// טיפול בטופס הקצאת תשלום
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['plan_id'])) {
    $user_id = intval($_POST['user_id']);
    $plan_id = intval($_POST['plan_id']);

    // בדיקה אם כבר קיימים תשלומים עתידיים למשתמש
    $check = $conn->prepare("SELECT COUNT(*) AS total FROM payments WHERE user_id = ? AND due_date >= CURDATE()");
    $check->bind_param("i", $user_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $check->close();

    if ($res['total'] > 0) {
        $message = "⚠️ למשתמש כבר קיימים תשלומים עתידיים. לא ניתן להקצות שוב.";
    } else {
        // שליפת פרטי התוכנית
        $plan = $conn->prepare("SELECT duration_months, price FROM payment_plans WHERE id = ?");
        $plan->bind_param("i", $plan_id);
        $plan->execute();
        $plan_result = $plan->get_result();

        if ($plan_result->num_rows > 0) {
            $plan_data = $plan_result->fetch_assoc();
            $months = (int)$plan_data['duration_months'];
            $price = (float)$plan_data['price'];
            $monthly_amount = round($price / $months, 2);

            $today = new DateTime();
            $success = 0;

            for ($i = 0; $i < $months; $i++) {
                $due_date = clone $today;
                $due_date->modify("+{$i} month");
                $due_str = $due_date->format('Y-m-d');

                $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_id, due_date, amount) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisd", $user_id, $plan_id, $due_str, $monthly_amount);

                if ($stmt->execute()) {
                    $success++;
                }
                $stmt->close();
            }

            $message = "✅ נוצרו בהצלחה $success תשלומים חודשיים עבור המשתמש.";
        } else {
            $message = "⚠️ תוכנית לא נמצאה.";
        }
        $plan->close();
    }
}

// שליפת משתמשים
$users = $conn->query("SELECT id, full_name FROM users WHERE is_admin = 0 ORDER BY full_name ASC");

// שליפת תוכניות תשלום
$plans = $conn->query("SELECT id, name, price FROM payment_plans ORDER BY price ASC");
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>הקצאת תוכנית תשלום</title>
    <style>
        body {
            direction: rtl;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            text-align: center;
            padding: 40px;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label, select, button {
            display: block;
            margin: 15px 0;
            font-size: 16px;
            width: 100%;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
        }
        .message {
            color: #2e7d32;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<h2>הקצאת תוכנית תשלום למשתמש</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">
    <label for="user_id">בחר משתמש:</label>
    <select name="user_id" required>
        <option value="">-- בחר --</option>
        <?php while ($user = $users->fetch_assoc()): ?>
            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label for="plan_id">בחר תוכנית תשלום:</label>
    <select name="plan_id" required>
        <option value="">-- בחר --</option>
        <?php while ($plan = $plans->fetch_assoc()): ?>
            <option value="<?= $plan['id'] ?>">
                <?= htmlspecialchars($plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">📩 הקצה תוכנית</button>
</form>
</body>
</html>
