console.log("✅ price.js loaded");

  function showPrice() {
    const price = document.getElementById("plan").value;
    const priceText = price ? `המחיר: ₪${price}` : "";
    document.getElementById("price-result").innerText = priceText;
    document.getElementById("purchase-btn").style.display = price ? "inline-block" : "none";
    window.selectedTotal = price;
  }

  function purchase() {
    const phone = '0546781613'; // שנה למספר שלך
    const message = `שלום! אני מעוניין לרכוש תוכנית בעלות של ₪${window.selectedTotal}`;
    const link = `https://wa.me/972${phone.substring(1)}?text=${encodeURIComponent(message)}`;
    window.open(link, '_blank');
  }