<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$errores = [];
$email = '';
$registrado = isset($_GET['registrado']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errores[] = 'Ingresa tu correo y tu contraseña.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nombre, password_hash FROM usuarios WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];

                
                if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                    $upd = $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
                    $upd->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $usuario['id']]);
                }

                header('Location: index.php');
                exit;
            }

            $errores[] = 'Correo o contraseña incorrectos.';
        } catch (PDOException $e) {
            error_log('Login: ' . $e->getMessage());
            $errores[] = 'No se pudo iniciar sesión. Revisa que la base de datos esté creada (includes/schema.sql).';
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
    <?php if ($registrado): ?>
        <p class="exito" role="status">Cuenta creada. Ya puedes iniciar sesión.</p>
    <?php endif; ?>
    <?php if ($errores): ?>
        <div class="errores" role="alert">
            <?php foreach ($errores as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="" method="post" class="login-form">
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Iniciar sesión</button>
    </form>
</body>
</html>