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
.menu-button {
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    transition: background-color 0.2s;
  }

  .menu-button:hover {
    background-color: #0056b3;
  }

  .overlay-background {
    display: none;
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1000;
  }

  .submenu-popover {
    position: absolute;
    background-color: #343a40;
    color: white;     
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 1100;
    direction: rtl;
    min-width: 200px;
  }

  .submenu-popover .menu-button {
    background-color: #f0f0f0;
    color: #333;
    text-align: right;
  }

  .submenu-popover .menu-button:hover {
    background-color: #e0e0e0;
  }



/* אנימציה חלקה לפתיחת תפריטים */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

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

    <script>
function loadPage(pageUrl, element = null, action = null) {
    const iframe = document.getElementById("contentFrame");

    // סגור את כל תתי התפריטים
    document.querySelectorAll(".submenu").forEach(el => el.style.display = "none");

    // טען את הדף ב־iframe
    iframe.src = pageUrl;

    // המתן לטעינה, ואז שלח פעולה רלוונטית
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
                    win.postMessage(action, "*");
                }
            },
            "assign_payment_plan.php": () => {
                // אין פעולות נוספות
            }
        };

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

    // סגור את כל שאר תתי התפריטים
    document.querySelectorAll(".submenu").forEach(el => el.style.display = "none");

    // פתח או סגור את התפריט
    submenu.style.display = isVisible ? "none" : "flex";
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
}

document.addEventListener("DOMContentLoaded", function () {
    const button = document.getElementById("menu-appointments");
    const overlay = document.getElementById("overlay-appointments");
    const popover = document.getElementById("appointmentsPopover");
    const customersBtn = document.querySelector('[data-target="customersPopover"]');
    const customersOverlay = document.getElementById("overlay-customers");
    const customersPopover = document.getElementById("customersPopover");

customersBtn.addEventListener("click", function () {
  if (customersOverlay.style.display === "block") {
    customersOverlay.style.display = "none";
  } else {
    const rect = customersBtn.getBoundingClientRect();
    customersPopover.style.top = (rect.bottom + window.scrollY - 50) + "px";
    customersPopover.style.right = (window.innerWidth - rect.left + 20) + "px";
    customersOverlay.style.display = "block";
  }
});


customersOverlay.addEventListener("click", function (event) {
  if (
    !event.target.closest('.submenu-popover') &&
    !event.target.closest('[data-target="customersPopover"]')
  ) {
    customersOverlay.style.display = "none";
  }
  const mealBtn = document.getElementById("menu-meal");
const mealOverlay = document.getElementById("overlay-meal");
const mealPopover = document.getElementById("mealPopover");

mealBtn.addEventListener("click", function () {
  if (mealOverlay.style.display === "block") {
    mealOverlay.style.display = "none";
  } else {
    const rect = mealBtn.getBoundingClientRect();
    mealPopover.style.top = (rect.bottom + window.scrollY - 50) + "px";
    mealPopover.style.right = (window.innerWidth - rect.left + 20) + "px";
    mealOverlay.style.display = "block";
  }
});

mealOverlay.addEventListener("click", function (event) {
  if (
    !event.target.closest('.submenu-popover') &&
    !event.target.closest('#menu-meal')
  ) {
    mealOverlay.style.display = "none";
  }
});

mealPopover.querySelectorAll(".submenu-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    const page = this.dataset.page;
    const action = this.dataset.action || null;
    mealOverlay.style.display = "none";
    loadPage(page, this, action);
  });
});

});

customersPopover.querySelectorAll(".submenu-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    const page = this.dataset.page;
    const action = this.dataset.action || null;
    customersOverlay.style.display = "none";
    loadPage(page, this, action);
  });
});



    button.addEventListener("click", function () {
  if (overlay.style.display === "block") {
    overlay.style.display = "none";
  } else {
    const rect = button.getBoundingClientRect();

    popover.style.top = (rect.bottom + window.scrollY - 50) + "px";

    // כיוון ימין לפי תחילת הכפתור, כדי שהתפריט יופיע ממש לידו משמאל
    popover.style.right = (window.innerWidth - rect.left + 20) + "px";

    overlay.style.display = "block";
  }
});

    // סגירה בלחיצה מחוץ לתפריט
    overlay.addEventListener("click", function (event) {
    if (!event.target.closest('.submenu-popover') && !event.target.closest('#menu-appointments'))
    overlay.style.display = "none";
});


// סגירה בלחיצה על אחד התתי-כפתורים + טעינה
document.querySelectorAll(".submenu-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    const page = this.dataset.page;
    const action = this.dataset.action || null;
    overlay.style.display = "none";
    loadPage(page, this, action); // ✅ עכשיו ה-action יעבור
  });
});

  });

 
    </script>
</body>
</html>
