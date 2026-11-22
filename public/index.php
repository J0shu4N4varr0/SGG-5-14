<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once __DIR__ . '/../includes/helpers.php';

const CATEGORIAS = ['Alimentación', 'Transporte', 'Vivienda', 'Servicios', 'Salud', 'Educación', 'Ocio', 'Otros'];
const MESES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$logueado = isset($_SESSION['usuario_id']);
$errores = [];
$aviso = '';
$valores = ['descripcion' => '', 'monto' => '', 'categoria' => '', 'fecha' => date('Y-m-d')];

$totalMes = $cantMes = $totalGeneral = $cantGeneral = 0;
$porCategoria = [];
$ultimos = [];

if ($logueado) {
    require_once __DIR__ . '/../includes/database.php';
    $uid = (int) $_SESSION['usuario_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valido()) {
            $errores[] = 'La sesión del formulario expiró. Vuelve a intentarlo.';
        } else {
            $accion = $_POST['accion'] ?? '';

            try {
                if ($accion === 'agregar') {
                    $descripcion = trim($_POST['descripcion'] ?? '');
                    $montoTxt    = str_replace(',', '.', trim($_POST['monto'] ?? ''));
                    $categoria   = $_POST['categoria'] ?? '';
                    $fecha       = $_POST['fecha'] ?? '';

                    $valores = [
                        'descripcion' => $descripcion,
                        'monto'       => $_POST['monto'] ?? '',
                        'categoria'   => $categoria,
                        'fecha'       => $fecha,
                    ];

                    if ($descripcion === '' || mb_strlen($descripcion) > 150) {
                        $errores[] = 'La descripción es obligatoria (máximo 150 caracteres).';
                    }
                    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $montoTxt) || (float) $montoTxt <= 0) {
                        $errores[] = 'El monto debe ser un número mayor a 0 (hasta 2 decimales).';
                    }
                    if (!in_array($categoria, CATEGORIAS, true)) {
                        $errores[] = 'Elige una categoría válida.';
                    }
                    $d = DateTime::createFromFormat('Y-m-d', $fecha);
                    if (!$d || $d->format('Y-m-d') !== $fecha) {
                        $errores[] = 'La fecha no es válida.';
                    }

                    if (!$errores) {
                        $stmt = $pdo->prepare(
                            'INSERT INTO gastos (usuario_id, descripcion, monto, categoria, fecha)
                             VALUES (:u, :d, :m, :c, :f)'
                        );
                        $stmt->execute([':u' => $uid, ':d' => $descripcion, ':m' => $montoTxt, ':c' => $categoria, ':f' => $fecha]);
                        header('Location: index.php?ok=agregado');
                        exit;
                    }
                } elseif ($accion === 'eliminar') {
                    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                    $borrados = 0;
                    if ($id) {
                        // Se filtra por usuario_id: nadie puede borrar gastos ajenos.
                        $stmt = $pdo->prepare('DELETE FROM gastos WHERE id = :id AND usuario_id = :u');
                        $stmt->execute([':id' => $id, ':u' => $uid]);
                        $borrados = $stmt->rowCount();
                    }
                    if ($borrados > 0) {
                        header('Location: index.php?ok=eliminado');
                        exit;
                    }
                    $errores[] = 'No se encontró ese gasto.';
                }
            } catch (PDOException $e) {
                error_log('Gastos: ' . $e->getMessage());
                $errores[] = 'No se pudo guardar el cambio. ¿Ejecutaste includes/schema.sql para crear la tabla gastos?';
            }
        }
    }

    if (($_GET['ok'] ?? '') === 'agregado') {
        $aviso = 'Gasto registrado.';
    } elseif (($_GET['ok'] ?? '') === 'eliminado') {
        $aviso = 'Gasto eliminado.';
    }

    try {
        $ini = date('Y-m-01');
        $fin = date('Y-m-01', strtotime('+1 month', strtotime($ini)));

        $s = $pdo->prepare('SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS n FROM gastos
                            WHERE usuario_id = :u AND fecha >= :ini AND fecha < :fin');
        $s->execute([':u' => $uid, ':ini' => $ini, ':fin' => $fin]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $totalMes = (float) $r['total'];
        $cantMes  = (int) $r['n'];

        $s = $pdo->prepare('SELECT COALESCE(SUM(monto), 0) AS total, COUNT(*) AS n FROM gastos WHERE usuario_id = :u');
        $s->execute([':u' => $uid]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $totalGeneral = (float) $r['total'];
        $cantGeneral  = (int) $r['n'];

        $s = $pdo->prepare('SELECT categoria, SUM(monto) AS total FROM gastos
                            WHERE usuario_id = :u AND fecha >= :ini AND fecha < :fin
                            GROUP BY categoria ORDER BY total DESC');
        $s->execute([':u' => $uid, ':ini' => $ini, ':fin' => $fin]);
        $porCategoria = $s->fetchAll(PDO::FETCH_ASSOC);

        $s = $pdo->prepare('SELECT id, descripcion, monto, categoria, fecha FROM gastos
                            WHERE usuario_id = :u ORDER BY fecha DESC, id DESC LIMIT 20');
        $s->execute([':u' => $uid]);
        $ultimos = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Gastos (lectura): ' . $e->getMessage());
        $errores[] = 'No se pudieron leer los gastos. ¿Ejecutaste includes/schema.sql para crear la tabla gastos?';
    }
}

$mesActual = MESES[(int) date('n')] . ' de ' . date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <script src="js/script.js"></script>
    <title>Sistema Gestor de Gastos</title>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="contenedor">
    <?php if (!$logueado): ?>

        <section class="hero">
            <h1>Sistema Gestor de Gastos</h1>
            <p>Registra en qué gastas tu dinero y mira, mes a mes, cuánto va a cada categoría. Todo en tu propio equipo, con tu propia base de datos.</p>
            <div class="hero-acciones">
                <a href="registro.php" class="btn">Crear cuenta</a>
                <a href="login.php" class="btn btn-sec">Ya tengo cuenta</a>
            </div>
        </section>

        <section class="funciones">
            <article class="tarjeta">
                <h2>Registra tus gastos</h2>
                <p>Carga cada gasto con su descripción, monto, categoría y fecha en pocos segundos.</p>
            </article>
            <article class="tarjeta">
                <h2>Ordénalos por categoría</h2>
                <p>Alimentación, transporte, vivienda, servicios, salud, educación, ocio y más.</p>
            </article>
            <article class="tarjeta">
                <h2>Mira el resumen del mes</h2>
                <p>Total gastado, cantidad de gastos y qué porcentaje se lleva cada categoría.</p>
            </article>
            <article class="tarjeta">
                <h2>Tus datos son tuyos</h2>
                <p>Cada usuario ve solo sus propios gastos y las contraseñas se guardan cifradas.</p>
            </article>
        </section>

    <?php else: ?>

        <h1>Panel de gastos</h1>
        <p class="sub">Resumen de <?= e($mesActual) ?></p>

        <?php if ($aviso): ?>
            <p class="exito" role="status"><?= e($aviso) ?></p>
        <?php endif; ?>
        <?php if ($errores): ?>
            <div class="errores" role="alert">
                <?php foreach ($errores as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="resumen">
            <div class="dato">
                <span class="dato-valor"><?= e(dinero($totalMes)) ?></span>
                <span class="dato-etiqueta">Gastado este mes</span>
            </div>
            <div class="dato">
                <span class="dato-valor"><?= $cantMes ?></span>
                <span class="dato-etiqueta">Gastos este mes</span>
            </div>
            <div class="dato">
                <span class="dato-valor"><?= e(dinero($totalGeneral)) ?></span>
                <span class="dato-etiqueta">Total histórico (<?= $cantGeneral ?>)</span>
            </div>
        </section>

        <div class="panel-grid">
            <section class="caja">
                <h2>Nuevo gasto</h2>
                <form action="index.php" method="post" class="form-gasto">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="agregar">

                    <label for="descripcion">Descripción:</label>
                    <input type="text" id="descripcion" name="descripcion" maxlength="150" value="<?= e($valores['descripcion']) ?>" required>

                    <label for="monto">Monto:</label>
                    <input type="text" id="monto" name="monto" inputmode="decimal" placeholder="0,00" value="<?= e($valores['monto']) ?>" required>

                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="" disabled <?= $valores['categoria'] === '' ? 'selected' : '' ?>>Elige una…</option>
                        <?php foreach (CATEGORIAS as $c): ?>
                            <option value="<?= e($c) ?>" <?= $valores['categoria'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" value="<?= e($valores['fecha']) ?>" required>

                    <button type="submit">Agregar gasto</button>
                </form>
            </section>

            <section class="caja">
                <h2>Por categoría (<?= e($mesActual) ?>)</h2>
                <?php if (!$porCategoria): ?>
                    <p class="vacio">Todavía no hay gastos este mes.</p>
                <?php else: ?>
                    <ul class="categorias">
                        <?php foreach ($porCategoria as $fila):
                            $pct = $totalMes > 0 ? round(((float) $fila['total'] / $totalMes) * 100) : 0; ?>
                            <li>
                                <div class="cat-linea">
                                    <span><?= e($fila['categoria']) ?></span>
                                    <span><?= e(dinero($fila['total'])) ?> · <?= $pct ?>%</span>
                                </div>
                                <div class="barra"><div class="barra-llena" style="width: <?= $pct ?>%"></div></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <section class="caja">
            <h2>Últimos gastos</h2>
            <?php if (!$ultimos): ?>
                <p class="vacio">Aún no registraste ningún gasto. Usa el formulario para cargar el primero.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="tabla">
                        <thead>
                            <tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th class="der">Monto</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ultimos as $g): ?>
                            <tr>
                                <td><?= e(date('d/m/Y', strtotime($g['fecha']))) ?></td>
                                <td><?= e($g['descripcion']) ?></td>
                                <td><?= e($g['categoria']) ?></td>
                                <td class="der"><?= e(dinero($g['monto'])) ?></td>
                                <td class="der">
                                    <form action="index.php" method="post" onsubmit="return confirm('¿Eliminar este gasto?');">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                        <button type="submit" class="btn-borrar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($cantGeneral > count($ultimos)): ?>
                    <p class="vacio">Se muestran los 20 más recientes de <?= $cantGeneral ?>.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
