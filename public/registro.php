<?php
require_once __DIR__ . '/../includes/database.php';

$errores = [];
$valores = ['name' => '', 'lastname' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['name'] ?? '');
    $apellido = trim($_POST['lastname'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Se conservan los datos (menos las contraseñas) para volver a mostrarlos si hay errores.
    $valores = ['name' => $nombre, 'lastname' => $apellido, 'email' => $email];

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
    }
    if ($apellido === '' || mb_strlen($apellido) > 100) {
        $errores[] = 'El apellido es obligatorio (máximo 100 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    // Validación estricta de contraseña
    if (strlen($password) < 8
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula, número y un símbolo.';
    }
    if ($password !== $confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        try {
            // Verificación de correo no duplicado
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $errores[] = 'Ya existe una cuenta registrada con ese correo.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO usuarios (nombre, apellido, email, password_hash)
                     VALUES (:nombre, :apellido, :email, :hash)'
                );
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':apellido' => $apellido,
                    ':email'    => $email,
                    ':hash'     => password_hash($password, PASSWORD_DEFAULT),
                ]);

                header('Location: login.php?registrado=1');
                exit;
            }
        } catch (PDOException $e) {
            // 23000 = violación de UNIQUE (dos registros simultáneos con el mismo correo)
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe una cuenta registrada con ese correo.';
            } else {
                error_log('Registro: ' . $e->getMessage());
                $errores[] = 'No se pudo guardar el registro. Revisa que la base de datos esté creada (includes/schema.sql).';
            }
        }
    }
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
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
            <a href="index.php" class="btn">Inicio</a>
             <p>¿Ya tienes una cuenta?</p>
            <a href="login.php" class="btn">Ingresar</a>
        </div>
    </nav>
    <?php if ($errores): ?>
        <div class="errores" role="alert">
            <?php foreach ($errores as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="" method="post" class="login-form-2">
        <label for="name">Nombre de usuario:</label>
        <input type="text" id="name" name="name" value="<?= e($valores['name']) ?>" required>
        <label for="lastname">Apellido:</label>
        <input type="text" id="lastname" name="lastname" value="<?= e($valores['lastname']) ?>" required>
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" value="<?= e($valores['email']) ?>" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm_password">Confirmar contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <button type="submit">Registrarse</button>
    </form>
    <p>Formulario de registro</p>
</body>
</html>