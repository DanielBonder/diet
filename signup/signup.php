<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../admin/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['fullName']);
    $username = $conn->real_escape_string($_POST['userName']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $bmi = $height > 0 ? $weight / pow($height / 100, 2) : null;

    // בדיקה אם המשתמש כבר קיים
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error_message = "⚠️ המשתמש כבר קיים עם אותו שם משתמש או אימייל.";
    } else {
        $check->close();

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, weight, height, bmi, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssssdd", $full_name, $email, $username, $password, $weight, $height, $bmi);
        
        if ($stmt->execute()) {
            header("Location: success.php");
            exit;
        }
        
        else {
            $error_message = "❌ שגיאה בהרשמה: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הרשמה</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/home_css/signup.css">
        
</head>
<body>
	<div class="back-home">
		<a href="../home.php" class="home-button">חזרה לדף הבית</a>
	</div>

    <div class="signup-container">
        <h2>הרשמת משתמש חדש</h2>
        <p>נא למלא את הפרטים שלך</p>

        <form method="post" action="signup.php">
            <input type="text" name="fullName" placeholder="שם מלא" required />
            <input type="text" name="userName" placeholder="שם משתמש" required />
            <input type="email" name="email" placeholder="אימייל" required />
            <input type="password" name="password" placeholder="סיסמה" required />
            <input type="number" name="weight" placeholder="משקל (ק״ג)" step="0.1" required />
            <input type="number" name="height" placeholder="גובה (ס״מ)" step="0.1" required />
            <input type="submit" value="הרשם" />
            <?php if (isset($success_message)) echo "<div id='success'>$success_message</div>"; ?>
            <?php if (isset($error_message)) echo "<div id='error'>$error_message</div>"; ?>

            <?php if (isset($error_message)) echo "<div id='error'>$error_message</div>"; ?>
        </form>

        <p>כבר יש לך חשבון? <a href="../login/login.html">התחברות</a></p>
    </div>
</body>
</html>
