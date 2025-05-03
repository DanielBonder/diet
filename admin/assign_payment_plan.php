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
    $plan_id = (int) $_POST['plan_id'];
    $amount = 0;

    // שלוף את המחיר של התוכנית
    $stmt = $conn->prepare("SELECT price FROM payment_plans WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($rowPlan = $result->fetch_assoc()) {
        $amount = (float) $rowPlan['price'];
    }
    $stmt->close();

    $due_date = $_POST['due_date'];
    $user_id = (int) $_POST['user_id'];
    $status = $_POST['status'];

    // ❗ כולל גם את plan_id בעדכון
    $stmt = $conn->prepare("UPDATE payments SET amount = ?, due_date = ?, user_id = ?, status = ?, plan_id = ? WHERE id = ?");
    $stmt->bind_param("dsisii", $amount, $due_date, $user_id, $status, $plan_id, $payment_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
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


$whereClauses = ["p.status = 'לא שולם'"];
$params = [];
$types = "";

// סינון לפי מזהה משתמש
if (!empty($_GET['filter_user_id'])) {
    $whereClauses[] = "u.id = ?";
    $params[] = (int) $_GET['filter_user_id'];
    $types .= "i";
}


// סינון לפי סוג תוכנית
if (!empty($_GET['plan_name'])) {
    $whereClauses[] = "pp.name = ?";
    $params[] = $_GET['plan_name'];
    $types .= "s";
}

$whereSQL = implode(" AND ", $whereClauses);

$sql = "SELECT p.id, u.full_name, p.due_date, p.amount, p.status, pp.name AS plan_name, p.plan_id 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        JOIN payment_plans pp ON p.plan_id = pp.id 
        WHERE $whereSQL
        ORDER BY p.due_date ASC";


$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$unpaid = [];
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

<!-- כפתור לפתיחת המודל -->
<button id="openAssignModal" class="button">+ הקצה תוכנית תשלום</button>

<!-- מודל ההקצאה -->
<div id="assignModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeAssignModal">&times;</span>

    <h2>📩 הקצאת תוכנית תשלום</h2>


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

        <button type="submit" class="submit-btn">📥 הקצה</button>
    </form>
  </div>
</div>

<?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
<h2>💳 תשלומים פתוחים</h2>
<form method="GET" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">
<label>
    שם משתמש:
    <select name="filter_user_id">
        <option value="">-- כל המשתמשים --</option>
        <?php foreach ($users as $user): ?>
            <option value="<?= $user['id'] ?>" <?= (isset($_GET['filter_user_id']) && $_GET['filter_user_id'] == $user['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['full_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</label>


    <label>
        סוג תוכנית:
        <select name="plan_name">
            <option value="">-- כל הסוגים --</option>
            <?php
            $planNames = $conn->query("SELECT DISTINCT name FROM payment_plans");
            while ($plan = $planNames->fetch_assoc()):
                $selected = ($_GET['plan_name'] ?? '') === $plan['name'] ? 'selected' : '';
                echo "<option value=\"{$plan['name']}\" $selected>" . htmlspecialchars($plan['name']) . "</option>";
            endwhile;
            ?>
        </select>
    </label>

    <button type="submit">🔍 סנן</button>
</form>
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
        <!-- בחירת משתמש -->
        <td>
            <select name="user_id" required>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $user['id'] == $row['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>

        <td>
            <select name="plan_id">
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= $plan['id'] ?>" <?= $plan['id'] == $row['plan_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($plan['name']) ?> - <?= number_format($plan['price'], 2) ?> ₪
                    </option>
                <?php endforeach; ?>
            </select>
        </td>


        <!-- תאריך -->
        <td><input type="date" name="due_date" value="<?= $row['due_date'] ?>"></td>

        <!-- סכום -->
        <td><input type="number" step="0.01" name="amount" value="<?= $row['amount'] ?>"> ₪</td>

        <!-- סטטוס כ-select -->
        <td>
        <?= htmlspecialchars($row['status']) ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($row['status']) ?>">
        </td>


        <!-- כפתורים -->
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


<?php if (!empty($message)): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("assignModal").style.display = "block";
  });
</script>
<?php endif; ?>



<script>
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM ready");

  const openBtn = document.getElementById("openAssignModal");
  const closeBtn = document.getElementById("closeAssignModal");
  const modal = document.getElementById("assignModal");

  // 🛑 ודא שהמודל תמיד מתחיל סגור
  modal.style.display = "none";

  openBtn?.addEventListener("click", () => {
    modal.style.display = modal.style.display === "block" ? "none" : "block";
  });

  closeBtn?.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});

</script>



</body>
</html>
