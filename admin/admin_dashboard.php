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
    <style>
        body {
            direction: rtl;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            transition: width 0.3s ease;
        }

        .sidebar.expanded {
            width: 280px;
        }

        .sidebar button, .sidebar a.logout {
            background-color: transparent;
            border: none;
            color: white;
            font-size: 16px;
            text-align: right;
            cursor: pointer;
            padding: 10px;
            transition: background 0.3s;
            text-decoration: none;
        }

        .sidebar button:hover, .sidebar a.logout:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .submenu {
            display: none;
            flex-direction: column;
            padding-right: 10px;
            gap: 10px;
        }

        .submenu button {
            font-size: 14px;
            background-color: rgba(255,255,255,0.05);
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow: auto;
        }

        iframe {
            width: 100%;
            height: 90vh;
            border: none;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        h1 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <button onclick="toggleSubmenu('appointmentsSubmenu')">📅 תורים</button>
            <div id="appointmentsSubmenu" class="submenu">
                <button onclick="loadPage('admin_appointments.php')">✅ פגישות שלי</button>
                <button onclick="loadPage('add_availability.php')">➕ הוסף פגישה חדשה</button>
            </div>

            <button onclick="toggleSubmenu('customersSubmenu')">👥 ניהול לקוחות</button>
            <div id="customersSubmenu" class="submenu">
            <button onclick="loadPage('manage_customers.php')">📄 רשימת לקוחות</button>
            <button onclick="loadPage('manage_customers.php', 'toggleSummary')">📊 סיכום נתונים</button>
            </div>

            <button onclick="loadPage('assign_payment_plan.php')">💳 הקצאת תשלום</button>
            <button onclick="loadPage('admin_assign_menu.php')">🍽 הקצאת תפריט</button>
            <a href="login admin/login_admin.html" class="logout">🚪 התנתק</a>
        </div>

        <div class="main-content">
            <h1>ברוך הבא <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
            <iframe id="contentFrame" src=""></iframe>
        </div>
    </div>

    <script>
function loadPage(pageUrl, message = null) {
    const iframe = document.getElementById("contentFrame");
    iframe.src = pageUrl;

    iframe.onload = function () {
        if (pageUrl === 'admin_appointments.php') {
            iframe.contentWindow.postMessage("toggleAppointments", "*");
        } else if (pageUrl === 'manage_customers.php') {
            if (!message || message === "toggleCustomerArea") {
                // שלח רק toggleCustomerArea כברירת מחדל
                iframe.contentWindow.postMessage("toggleCustomerArea", "*");
            } else if (message === "toggleSummary") {
                // שלח רק toggleSummary בלי לפתוח גם לקוחות
                iframe.contentWindow.postMessage("toggleSummary", "*");
            }
        } else if (message) {
            iframe.contentWindow.postMessage(message, "*");
        }
    };
}


        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            const sidebar = document.getElementById("sidebar");
            const isVisible = submenu.style.display === "flex";

            submenu.style.display = isVisible ? "none" : "flex";
            if (!isVisible) {
                sidebar.classList.add("expanded");
            } else {
                sidebar.classList.remove("expanded");
            }
        }
    </script>
</body>
</html>