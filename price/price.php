<!DOCTYPE html>
<html lang="he">
<head>
  <meta charset="UTF-8">
  <title>בחירת תוכנית</title>
  <link rel="stylesheet" href="../home/home.css">
</head>
<body dir="rtl" style="font-family: Arial, sans-serif;">
<div class="back-home">
    <a href="../home/home.php" class="home-button">חזרה לדף הבית</a>
</div>
  
  <form class="plan-container" onsubmit="purchase(); return false;">
    <h2>בחר תוכנית:</h2>

    <select id="plan" onchange="showPrice()" required>
      <option value="">-- בחר תוכנית --</option>
      <option value="650">ליווי חודש - ₪650</option>
      <option value="1350">ליווי 3 חודשים - ₪1350</option>
      <option value="2400">ליווי 6 חודשים - ₪2400</option>
      <option value="450">תפריט בלבד - ₪450 (חד פעמי, ללא ליווי)</option>
    </select>

    <p id="price-result" style="font-size: 18px; font-weight: bold;"></p>

    <button id="purchase-btn" type="submit" style="display:none;">
      המשך לרכישה ב־WhatsApp
    </button>
  </form>

  <script>
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
  </script>

</body>
</html>
