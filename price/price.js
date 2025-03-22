function calculatePrice() {
    const duration = parseInt(document.getElementById("duration").value);
    const plan = document.getElementById("plan").value;

    let basePrice = 0;
    switch (plan) {
        case "basic":
            basePrice = 450;
            break;
        case "premium":
            basePrice = 650;
            break;
    }

    let total = basePrice * duration;

    if (duration === 2) total *= 0.95;
    if (duration === 3) total *= 0.90;
    if (duration === 6) total *= 0.85;

    document.getElementById("price-result").innerText = `המחיר הכולל: ₪${total.toFixed(0)}`;
    document.getElementById("purchase-btn").style.display = "block";
    window.selectedTotal = total.toFixed(0);
}

function purchase() {
    const phone = '0546781613'; // שנה למספר שלך
    const message = `שלום! אני מעוניין לרכוש את התוכנית שבחרתי בעלות של ₪${window.selectedTotal}`;
    const link = `https://wa.me/972${phone}?text=${encodeURIComponent(message)}`;
    window.open(link, '_blank');
}