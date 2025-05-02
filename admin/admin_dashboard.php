<?php
session_start();

// בדיקת הרשאות - רק admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("⛔ אין גישה. עמוד זה מיועד רק למנהלים.");
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>לוח ניהול מנהל</title>
    <link rel="stylesheet" href="../assets/css/admin_css/admin_dashboard.css">
</head>
<body>
<div id="overlay" class="overlay"></div>

<div class="container">
  <div class="sidebar" id="sidebar">

    <!-- כפתור ראשי -->
    <button id="menu-appointments" class="menu-button">📅 תורים</button>

    <!-- שכבת רקע כהה -->
    <div id="overlay-appointments" class="overlay-background">
      <!-- תפריט צף ליד הכפתור -->
      <div id="appointmentsPopover" class="submenu-popover">
        <button class="menu-button submenu-btn" data-page="admin_appointments.php">✅ פגישות שלי</button>
        <button class="menu-button submenu-btn" data-page="add_availability.php">➕ הוסף פגישה חדשה</button>
      </div>
    </div>



        <!-- תפריט לקוחות -->
        <button class="menu-button has-submenu" data-target="customersPopover">👥 ניהול לקוחות</button>
<div class="overlay-background" id="overlay-customers">
  <div class="submenu-popover sidebar-bg" id="customersPopover">
    <button class="menu-button submenu-btn" data-page="manage_customers.php">📄 רשימת לקוחות</button>
    <button class="menu-button submenu-btn" data-page="manage_customers.php" data-action="toggleSummary">📊 סיכום נתונים</button>
    <button class="menu-button submenu-btn" data-page="assign_payment_plan.php" data-action="darken">💳 הקצאת תשלום</button>
  </div>
</div>
    <!-- כפתור תפריט הקצאת תפריט -->
    <button id="menu-meal" class="menu-button">🍽 הקצאת תפריט</button>

    <!-- שכבת רקע כהה ותפריט צף -->
    <div id="overlay-meal" class="overlay-background">
    <div id="mealPopover" class="submenu-popover">
        <button class="menu-button submenu-btn" data-page="admin_assign_menu.php" data-action="toggleMenuForm">📋 הקצה תפריט שבועי</button>
        <button class="menu-button submenu-btn" data-page="admin_assign_menu.php" data-action="toggleActualMeals">🍽 ארוחות בפועל</button>
    </div>
    </div>

        <!-- יציאה -->
        <a href="login admin/login_admin.html" class="logout">🚪 התנתק</a>
    </div>

    <div class="main-content">
        <h1>ברוך הבא <?= htmlspecialchars($_SESSION['full_name'] ?? 'משתמש') ?></h1>
        <iframe id="contentFrame" src=""></iframe>
    </div>
</div>

<div id="overlay-appointments" class="overlay-background">

<script src="../assets/js/admin_dashboard.js" ></script>

</body>
</html>
