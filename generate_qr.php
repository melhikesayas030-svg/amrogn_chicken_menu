<?php
session_start();
// Determine the absolute URL pointing to index.php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$menuUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu QR Code - Amrogn Chicken</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: sans-serif; text-align: center; background: #fdfbf7; padding: 40px; }
        .qr-card { background: #fff; padding: 30px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        #qrcode { margin: 20px auto; display: flex; justify-content: center; }
        .btn-print { background: #d32f2f; color: #fff; border: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="qr-card">
        <h1 style="color: #d32f2f; margin: 0;">Amrogn Chicken</h1>
        <p>Scan to view our digital menu</p>
        <div id="qrcode"></div>
        <p style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($menuUrl); ?></p>
        <button onclick="window.print()" class="btn-print">Print QR Code</button>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $menuUrl; ?>",
            width: 200,
            height: 200
        });
    </script>
</body>
</html>