<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <title>Ingreso</title>
</head>
<body>
    <nav>
        <div>
            <a href="index.php" class="btn">Inicio</a>
            <p>¿No tienes cuenta?</p>
            <a href="registro.php" class="btn">Registrarse</a>
        </div>
    </nav>
    <form action="" method="post" class="login-form">
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Iniciar sesión</button>
    </form>
</body>
</html>