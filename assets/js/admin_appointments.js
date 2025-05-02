// האזנה להודעת postMessage מתוך iframe או חלון אחר
window.addEventListener("message", function(event) {
    if (event.data === "toggleAppointments") {
        toggleAppointments();
    }
});

// פונקציית הצגת/הסתרת החלק של הפגישות
function toggleAppointments() {
    const section = document.getElementById("appointmentsSection");
    if (section) {
        section.style.display = (section.style.display === "none" || section.style.display === "") ? "block" : "none";
    }
}

// הפעלת slideToggle לפאנל הוספת זמינות כאשר הדף נטען
document.addEventListener("DOMContentLoaded", function () {
    const trigger = document.getElementById("openAvailability");
    const panel = document.getElementById("availabilityPanel");

    if (trigger && panel) {
        trigger.addEventListener("click", function () {
            if (panel.style.display === "none" || panel.style.display === "") {
                $(panel).slideDown("slow");
            } else {
                $(panel).slideUp("slow");
            }
        });
    }
});
