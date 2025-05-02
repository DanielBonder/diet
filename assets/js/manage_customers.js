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

 