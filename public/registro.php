<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <title>Registro</title>
</head>
<body>
    <nav>
        <div>
            <p>¿Ya tienes una cuenta?</p>
            <a href="login.php" class="btn">Ingresar</a>
        </div>
    </nav>
    <form action="" method="post">
        <label for="name">Nombre de usuario:</label>
        <input type="text" id="name" name="name" required>
        <label for="lastname">Apellido:</label>
        <input type="text" id="lastname" name="lastname" required>
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm_password">Confirmar contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <button type="submit">Registrarse</button>
    </form>
    <p>Formulario de registro</p>
</body>
</html>
