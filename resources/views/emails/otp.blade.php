<!-- C:\wamp64\www\bukanas-app\resources\views\emails\otp.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <style>
        .card { font-family: sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 400px; }
        .otp { font-size: 24px; font-weight: bold; color: #ff6600; letter-spacing: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Recuperación de Contraseña</h2>
        <p>Has solicitado restablecer tu contraseña en <strong>Bukanas App</strong>.</p>
        <p>Tu código de verificación es:</p>
        <div class="otp">{{ $otp }}</div>
        <p>Este código expirará en 15 minutos.</p>
    </div>
</body>
</html>
