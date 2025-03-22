<?php session_start(); ?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>איזון | בריאות | חיים</title>
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Suez+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Attraction&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="logo">
        <img src="תמונות(דף ראשון)/logo2.png" alt="לוגו">
    </div>
    <nav>
        <ul>
            <li><a href="#">בית</a></li>
            <li><a href="#">נעים להכיר</a></li>
            <li><a href="#">תוכניות</a></li>
            <li><a href="#">תשאלו אותם</a></li>
            <li><a href="../price/price.php">תפריטים ועוד</a></li>


            <?php if (isset($_SESSION['username'])): ?>
                <li><a href="../user/user_dashboard.php">שלום, <?= htmlspecialchars($_SESSION['username']) ?></a></li>
                <li><a href="../login/logout.php">התנתקות</a></li>
            <?php else: ?>
                <li><a href="../login/login.html" class="login-btn">התחברות</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<section class="hero">
    <h1>איזון. בריאות. חיים.</h1>
    <div class="images-container">
        <img src="תמונות(דף ראשון)/מדריכה.jpeg" alt="תמונה 1">
        <img src="תמונות(דף ראשון)/סלטה.jpeg" alt="תמונה 2">
        <img src="תמונות(דף ראשון)/שפגט.jpeg" alt="תמונה 3">
    </div>
</section>

<footer>
    <div class="social-icons">
        <a href="#"><img src="תמונות(דף ראשון)/facebook-app-symbol.png" alt="פייסבוק"></a>
        <a href="#"><img src="תמונות(דף ראשון)/instagram.png" alt="אינסטגרם"></a>
        <a href="#"><img src="תמונות(דף ראשון)/whatsapp.png" alt="וואטסאפ"></a>
    </div>
</footer>

</body>
</html>
