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
    // ========== תפריט 📅 פגישות ==========
    const button = document.getElementById("menu-appointments");
    const overlay = document.getElementById("overlay-appointments");
    const popover = document.getElementById("appointmentsPopover");

    button.addEventListener("click", function () {
        if (overlay.style.display === "block") {
            overlay.style.display = "none";
        } else {
            const rect = button.getBoundingClientRect();
            popover.style.top = (rect.bottom + window.scrollY - 50) + "px";
            popover.style.right = (window.innerWidth - rect.left + 20) + "px";
            overlay.style.display = "block";
        }
    });

    overlay.addEventListener("click", function (event) {
        if (!event.target.closest('.submenu-popover') && !event.target.closest('#menu-appointments')) {
            overlay.style.display = "none";
        }
    });

    document.querySelectorAll(".submenu-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const page = this.dataset.page;
            const action = this.dataset.action || null;
            overlay.style.display = "none";
            loadPage(page, this, action);
        });
    });


    // ========== תפריט 👥 ניהול לקוחות ==========
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
    });

    customersPopover.querySelectorAll(".submenu-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const page = this.dataset.page;
            const action = this.dataset.action || null;
            customersOverlay.style.display = "none";
            loadPage(page, this, action);
        });
    });


    // ========== תפריט 🍽 הקצאת תפריט ==========
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
