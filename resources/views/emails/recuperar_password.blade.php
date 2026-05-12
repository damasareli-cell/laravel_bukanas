<!-- resources/views/emails/recuperar_password.blade.php -->
<h1>Hola, has solicitado restablecer tu contraseña</h1>
<p>Haz clic en el siguiente botón para elegir una nueva clave. Este enlace expira en 15 minutos.</p>
<a href="{{ $url }}" style="background: #FF5722; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
    Restablecer Contraseña
</a>
<p>Si no solicitaste este cambio, puedes ignorar este correo.</p>