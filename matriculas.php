<?php
session_start();

// Validar rol admin (descomentar si usas sesiones y control de acceso)
// if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

// Conexión DB
$host = 'localhost';
$db = 'asistencia_db';
$user = 'root';
$pass = 'rg4casador';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Inicializar variables para evitar warnings
$mensaje = '';
$error = '';

// --- CREAR MATRÍCULA + VÍNCULO PADRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'crear_matricula') {
    $estudiante_id = $_POST['estudiante_id'];
    $grupo_id = $_POST['grupo_id'];
    $padre_id = $_POST['padre_id'];
    $fecha_matricula = $_POST['fecha_matricula'];

    try {
        // Insertar matrícula
        $stmt = $pdo->prepare("INSERT INTO matriculas (estudiante_id, grupo_id, fecha_matricula) VALUES (?, ?, ?)");
        $stmt->execute([$estudiante_id, $grupo_id, $fecha_matricula]);

        // Insertar vínculo padre-estudiante
        $stmt2 = $pdo->prepare("INSERT INTO padre_estudiante (usuario_id, estudiante_id, codigo_relacion) VALUES (?, ?, ?)");
        $stmt2->execute([$padre_id, $estudiante_id, 'Padre']);

        $mensaje = "✅ Matrícula y vínculo creados correctamente.";
    } catch (PDOException $e) {
        $error = "⚠️ Error: El estudiante ya está matriculado o el vínculo ya existe.";
    }
}

// Eliminar matrícula
if (isset($_GET['eliminar'])) {
    $pdo->prepare("DELETE FROM matriculas WHERE id=?")->execute([$_GET['eliminar']]);
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit;
}

// Consultar datos
$estudiantes = $pdo->query("SELECT id, nombre_completo FROM estudiantes ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$padres = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE rol='padre' ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

$matriculas = $pdo->query("
    SELECT m.id, e.nombre_completo AS estudiante, g.nombre AS grupo, m.fecha_matricula
    FROM matriculas m
    INNER JOIN estudiantes e ON m.estudiante_id = e.id
    INNER JOIN grupos g ON m.grupo_id = g.id
    ORDER BY g.nombre, e.nombre_completo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Matrículas y Vínculo Padre</title>
    <link rel="stylesheet" href="matriculas.css">
</head>
<body>
<div>
    <h1>Gestión de Matrículas y Padres</h1>
    <a href="logout.php">Cerrar Sesión</a>

    <?php if ($mensaje): ?><p style="color:green;"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="accion" value="crear_matricula">

        <label>Estudiante:</label>
        <select name="estudiante_id" required>
            <option value="">Seleccione Estudiante</option>
            <?php foreach ($estudiantes as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre_completo']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Grupo:</label>
        <select name="grupo_id" required>
            <option value="">Seleccione Grupo</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Padre:</label>
        <select name="padre_id" required>
            <option value="">Seleccione Padre</option>
            <?php foreach ($padres as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre_completo']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Fecha de matrícula:</label>
        <input type="date" name="fecha_matricula" value="<?= date('Y-m-d') ?>" required>

        <button type="submit">Matricular y Vincular</button>
    </form>

    <h2>Matrículas Existentes</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Estudiante</th>
            <th>Grupo</th>
            <th>Fecha</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($matriculas as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><?= htmlspecialchars($m['estudiante']) ?></td>
                <td><?= htmlspecialchars($m['grupo']) ?></td>
                <td><?= $m['fecha_matricula'] ?></td>
                <td>
                    <a href="?eliminar=<?= $m['id'] ?>" onclick="return confirm('¿Eliminar matrícula?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
