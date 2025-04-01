<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}

require_once 'db.php';
$message = "";

// מצב עריכה
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;

// מחיקת לקוח
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $id AND is_admin = 0");
    header("Location: manage_customers.php");
    exit;
}

// עדכון לקוח
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


// הוספת לקוח חדש
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

// שליפת כל המשתמשים
// שליפת כל המשתמשים
$users = [];
$result = $conn->query("SELECT id, full_name, username, email, weight, height, bmi FROM users WHERE is_admin = 0 ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$total_customers = count($users);

// שליפת חובות
$customersWithDebts = [];
$sql = "SELECT user_id, SUM(amount) as total FROM payments WHERE status = 'לא שולם' GROUP BY user_id";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customersWithDebts[$row['user_id']] = $row['total'];
    }
}
$total_debt = array_sum($customersWithDebts);
$customers_in_debt = count($customersWithDebts);

// לקוחות שמשלמים (גם אם שולם)
$paying_customers = [];
$sql = "SELECT DISTINCT user_id FROM payments";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $paying_customers[] = $row['user_id'];
    }
}
$total_paying_customers = count($paying_customers);

// פילוח לפי סוג תוכנית תשלום מתוך payments וה־payment_plans
$menu_counts = [];
$sql = "SELECT COUNT(DISTINCT user_id) as count, plan_id FROM payments GROUP BY plan_id";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plan_id = $row['plan_id'];
        $plan_name_result = $conn->query("SELECT name FROM payment_plans WHERE id = $plan_id");
        $plan_name = $plan_name_result->fetch_assoc()['name'] ?? "תוכנית $plan_id";
        $menu_counts[$plan_name] = $row['count'];
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

body {
    padding-top: 80px; /* גובה ה־sidebar – תוכל להתאים לפי הצורך */
}

#summaryBox {
    display: none;
    background-color: #ffffff;  
    margin: 20px auto;
    border-radius: 12px;
    width: 400px;
    text-align: right;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: background-color 0.4s ease, box-shadow 0.3s ease;
    color: #333;
    font-size: 16px;
    line-height: 1.6;
}


#summaryBox.active {
    background-color: #f8f9fa; /* רקע פתוח */
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
}

body.default-bg {
    background-color: #f0f0f0;
    transition: background-color 0.5s ease;
}

body.summary-bg {
    background-color: #e0f7fa; /* תכלת מודרני */
}

body.customers-bg {
    background-color: #f1f8e9; /* ירקרק רך */
}

body.add-bg {
    background-color: #fff3e0; /* קרמי-כתום בהיר */
}

.button-active {
    background-color: #6c757d !important; /* אפור מודרני */
    color: white !important;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
    transition: background-color 0.3s ease;
}


    </style>
</head>
<body>


<div id="summaryBox" style="display: none;">
    <p><strong>🔢 סה״כ לקוחות:</strong> <?= $total_customers ?></p>
    <p><strong>💰 סה״כ חובות:</strong> <?= number_format($total_debt, 2) ?> ₪</p>
    <p><strong>👥 לקוחות עם חוב:</strong> <?= $customers_in_debt ?></p>
    <p><strong>💳 לקוחות שיש להם תשלומים כלשהם:</strong> <?= $total_paying_customers ?></p>
    <hr>
    <p><strong>🍽 לפי סוג תוכנית:</strong></p>
    <ul style="text-align:right;">
        <?php foreach ($menu_counts as $plan => $count): ?>
            <li><?= htmlspecialchars($plan) ?> – <?= $count ?> לקוחות</li>
        <?php endforeach; ?>
    </ul>
</div>



<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<!-- כפתור לפתיחה/סגירה -->


<div id="customerSection" style="display: none;">
    <!-- טבלת לקוחות -->
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
            <th>חוב נוכחי</th>
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
                <td>—</td>
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
                <td><?= isset($customersWithDebts[$user['id']]) ? number_format($customersWithDebts[$user['id']], 2) . ' ₪' : '0 ₪' ?></td>
                <td class="actions">
                    <a href="manage_customers.php?edit=<?= $user['id'] ?>" class="edit-btn">✏️ ערוך</a>
                    <a href="manage_customers.php?delete=<?= $user['id'] ?>" class="delete-btn" onclick="return confirm('האם אתה בטוח שברצונך למחוק לקוח זה?')">🗑️ מחק</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
        <!-- כפתור תת-פעולה: הוספת לקוח חדש -->
        <button id="addBtn" onclick="toggleAddCustomer(); activateButton('addBtn')">
        ➕ הוספת לקוח חדש
    </button>
        <!-- טופס הוספת לקוח (מוסתר כברירת מחדל) -->
        <div id="addCustomerBox" style="display: none; margin: 20px auto; width: 400px; background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2>הוספת לקוח חדש</h2>
        <form method="POST">
            <input type="text" name="full_name" placeholder="שם מלא" required>
            <input type="text" name="username" placeholder="שם משתמש" required>
            <input type="email" name="email" placeholder="אימייל" required>
            <input type="password" name="password" placeholder="סיסמה" required>
            <input type="number" name="weight" placeholder="משקל (ק&quot;ג)" step="0.1" required>
            <input type="number" name="height" placeholder="גובה (ס&quot;מ)" step="0.1" required>
            <button type="submit" name="add_new">➕ הוסף לקוח</button>
        </form>
        
    </div>


</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
window.addEventListener("message", function(event) {
    if (event.data === "toggleCustomerArea" && typeof toggleCustomerArea === "function") {
        toggleCustomerArea();
    }

    if (event.data === "toggleSummary" && typeof toggleSummary === "function") {
        toggleSummary();
    }
});

function toggleSummary() {
    $("#summaryBox").slideToggle("slow", function () {
        if ($(this).is(":visible")) {
            clearBodyBackground();
            $("body").addClass("summary-bg");
        } else {
            clearBodyBackground();
        }
    });
}


function toggleAddCustomer() {
    $("#addCustomerBox").slideToggle("slow", function () {
        if ($(this).is(":visible")) {
            clearBodyBackground();
            $("body").addClass("add-bg");
        } else {
            clearBodyBackground();
        }
    });
}


function toggleCustomerSection() {
    const section = document.getElementById("customerSection");
    section.style.display = (section.style.display === "none" || section.style.display === "") ? "block" : "none";
}

function toggleCustomerMenu() {
    const submenu = document.getElementById("customerSubmenu");
    submenu.style.display = (submenu.style.display === "none" || submenu.style.display === "") ? "flex" : "none";
}

function toggleCustomerArea() {
    $("#customerSection").slideToggle("slow", function () {
        if ($(this).is(":visible")) {
            clearBodyBackground();
            $("body").addClass("customers-bg");
        } else {
            clearBodyBackground();
        }

        // סגור גם את טופס ההוספה אם פתוח
        if (!$(this).is(":visible")) {
            $("#addCustomerBox").slideUp("fast");
        }
    });
}

function clearBodyBackground() {
    $("body").removeClass("summary-bg customers-bg add-bg").addClass("default-bg");
}

function activateButton(buttonId) {
    $(".sidebar button").removeClass("button-active");
    $(`#${buttonId}`).addClass("button-active");
}

</script>

</body>
</html>
