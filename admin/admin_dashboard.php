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
/* מבנה כללי */
body {
    direction: rtl;
    margin: 0;
    font-family: Arial, sans-serif;
    transition: background-color 0.5s ease;
    background-color: #f9f9f9;
}

.container {
    display: flex;
    height: 100vh;
}

/* סרגל צד */
.sidebar {
    width: 220px;
    background-color: #2c3e50;
    color: white;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    z-index: 11;
    transition: background-color 0.5s ease;
}

/* כפתורים בתפריט ותתי תפריטים */
.menu-button {
    background-color: transparent;
    border: none;
    color: white;
    font-size: 16px;
    text-align: right;
    cursor: pointer;
    padding: 10px 15px;
    transition: background-color 0.3s ease, color 0.3s ease;
    text-decoration: none;
    width: 100%;
    border-radius: 5px;
}

.menu-button:hover,
.sidebar a.logout:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* תתי תפריטים */
.menu-with-submenu {
    position: relative;
}

.submenu {
    display: none;
    flex-direction: column;
    gap: 10px;
    animation: fadeIn 0.3s ease;
}

.horizontal-submenu {
    position: absolute;
    top: 0;
    right: 108%;
    background-color: #2c3e50;
    border-radius: 6px;
    padding: 12px;
    box-shadow: 0 0 6px rgba(0,0,0,0.25);
    z-index: 20;
    min-width: 200px;
    color: white;
}

/* תוכן ראשי */
.main-content {
    flex-grow: 1;
    padding: 20px;
    overflow: auto;
    position: relative;
    z-index: 10;
    background-color: white;
    transition: background-color 0.5s ease;
}

iframe {
    width: 100%;
    height: 90vh;
    border: none;
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(0,0,0,0.08);
    transition: background-color 0.5s ease;
}

/* כותרת */
h1 {
    margin-top: 0;
    font-size: 24px;
    color: #333;
}

/* כפתור יציאה */
.logout {
    background-color: transparent;
    border: none;
    color: white;
    font-size: 16px;
    text-align: right;
    cursor: pointer;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
}

/* שכבת אפור */
#overlay {
    display: none;
    position: fixed;
    top: 0;
    right: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 5;
}

/* מצב רקע כהה */
body.dark-background {
    background-color: #2a2a2a;
    color: white;
    transition: background-color 0.5s ease;
}

body.dark-background .sidebar {
    background-color: #2a2a2a;
}

body.dark-background .main-content {
    background-color: #2a2a2a;
}

body.dark-background .main-content iframe {
    background-color: #2a2a2a;
}


/* אנימציה חלקה לפתיחת תפריטים */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

</head>
<body>
<div id="overlay" onclick="closeAllSubmenus()"></div>

<div class="container">
    <div class="sidebar" id="sidebar">

        <!-- תפריט תורים -->
        <div class="menu-with-submenu">
            <button class="menu-button" onclick="toggleSubmenu('appointmentsSubmenu')">📅 תורים</button>
            <div id="appointmentsSubmenu" class="submenu horizontal-submenu">
                <button class="menu-button" onclick="loadPage('admin_appointments.php', this)" data-submenu="appointmentsSubmenu">✅ פגישות שלי</button>
                <button class="menu-button" onclick="loadPage('add_availability.php', this)" data-submenu="appointmentsSubmenu">➕ הוסף פגישה חדשה</button>
            </div>
        </div>

        <!-- תפריט לקוחות -->
        <div class="menu-with-submenu">
            <button class="menu-button" onclick="toggleSubmenu('customersSubmenu')">👥 ניהול לקוחות</button>
            <div id="customersSubmenu" class="submenu horizontal-submenu">
                <button class="menu-button" onclick="loadPage('manage_customers.php', this)" data-submenu="customersSubmenu">📄 רשימת לקוחות</button>
                <button class="menu-button" onclick="loadPage('manage_customers.php', this, 'toggleSummary')" data-submenu="customersSubmenu">📊 סיכום נתונים</button>
                <button class="menu-button" onclick="loadPage('assign_payment_plan.php', this, 'darken')" data-submenu="customersSubmenu">💳 הקצאת תשלום</button>
            </div>
        </div>

        <!-- תפריט הקצאת תפריט -->
        <div class="menu-with-submenu">
            <button class="menu-button" onclick="toggleSubmenu('menuAssignmentSubmenu')">🍽 הקצאת תפריט</button>
            <div id="menuAssignmentSubmenu" class="submenu horizontal-submenu">
                <button class="menu-button" onclick="loadPage('admin_assign_menu.php', this, 'toggleMenuForm')" data-submenu="menuAssignmentSubmenu">📋 הקצה תפריט שבועי</button>
                <button class="menu-button" onclick="loadPage('admin_assign_menu.php', this, 'toggleActualMeals')" data-submenu="menuAssignmentSubmenu">🍽 ארוחות בפועל</button>
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


    <script>
function loadPage(pageUrl, element = null, action = null) {
    const iframe = document.getElementById("contentFrame");

    // ניקוי תתי תפריטים ורקע
    document.querySelectorAll(".submenu").forEach(el => el.style.display = "none");
    document.body.classList.remove("dark-background");
    iframe.contentWindow?.postMessage("lighten", "*");

    // טען דף חדש
    iframe.src = pageUrl;

    // טפל בהודעת פעולה (action) לאחר הטעינה
    iframe.onload = () => {
        const win = iframe.contentWindow;

        const actionsMap = {
            "admin_appointments.php": () => win.postMessage("toggleAppointments", "*"),
            "manage_customers.php": () => {
                const msg = action === "toggleSummary" ? "toggleSummary" : "toggleCustomerArea";
                win.postMessage(msg, "*");
            },
            "admin_assign_menu.php": () => {
                if (action === "toggleMenuForm" || action === "toggleActualMeals") {
                    document.body.classList.add("dark-background");
                    win.postMessage("darken", "*");
                    win.postMessage(action, "*");
                }
            },
            "assign_payment_plan.php": () => {
                if (action === "darken") {
                    document.body.classList.add("dark-background");
                    win.postMessage("darken", "*");
                }
            }
        };

        // הפעל פעולה לפי הדף
        if (actionsMap[pageUrl]) {
            actionsMap[pageUrl]();
        } else if (action) {
            win.postMessage(action, "*");
        }
    };
}



function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const isVisible = submenu.style.display === "flex";

    // סגור את כל התתי תפריטים
    document.querySelectorAll(".submenu").forEach(el => el.style.display = "none");

    if (!isVisible) {
        submenu.style.display = "flex";

        // הפעל overlay + רקע כהה
        document.body.classList.add("dark-background", "overlay-visible");

        const iframe = document.getElementById("contentFrame");
        iframe.contentWindow.postMessage("darken", "*");
    } else {
        submenu.style.display = "none";

        // הסר overlay + רקע כהה
        document.body.classList.remove("dark-background", "overlay-visible");

        const iframe = document.getElementById("contentFrame");
        iframe.contentWindow.postMessage("lighten", "*");
    }
}

function callIframeFunction(functionName) {
    const iframe = document.getElementById("contentFrame");

    iframe.onload = function () {
        // מוודא שהפונקציה קיימת בדף שב־iframe
        if (iframe.contentWindow && typeof iframe.contentWindow[functionName] === "function") {
            iframe.contentWindow[functionName]();
        } else {
            console.warn("⚠️ פונקציה '" + functionName + "' לא קיימת ב-iframe.");
        }
    };
}

function closeAllSubmenus() {
    document.querySelectorAll(".submenu").forEach(el => el.style.display = "none");
    document.body.classList.remove("dark-background", "overlay-visible");

    const iframe = document.getElementById("contentFrame");
    iframe.contentWindow.postMessage("lighten", "*");
}

window.addEventListener("message", function(event) {
    if (event.data === "darken") {
        document.body.classList.add("dark-background");
    } else if (event.data === "lighten") {
        document.body.classList.remove("dark-background");
    }
});




    </script>
</body>
</html>
