document.addEventListener("DOMContentLoaded", function () {
    document.querySelector("form").addEventListener("submit", function (event) {
        if (!checkLoginForm()) {
            event.preventDefault(); // מונע שליחת טופס אם יש שגיאה
        }
    });
});

function checkLoginForm() {
    let loginInput = document.getElementById("loginInput").value.trim();
    let password = document.getElementById("password").value.trim();
    let errorDiv = document.getElementById("error");

    errorDiv.innerHTML = ""; // איפוס הודעות קודמות

    if (!loginInput || !password) {
        errorDiv.innerHTML = "Both fields are required.";
        return false;
    }

    if (!validateEmail(loginInput) && !validateUsername(loginInput)) {
        errorDiv.innerHTML = "Please enter a valid email or username.";
        return false;
    }

    if (password.length < 6) {
        errorDiv.innerHTML = "Password must be at least 6 characters.";
        return false;
    }

    return true;
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validateUsername(username) {
    return /^[a-zA-Z0-9_]+$/.test(username);
}