<?php
session_start();

// // Validar rol admin
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

// --- CREAR ASIGNACIÓN ---
if (isset($_POST['accion']) && $_POST['accion'] === 'crear_asignacion') {
    $docente_id = $_POST['docente_id'];
    $grupo_id = $_POST['grupo_id'];
    $asignatura_id = $_POST['asignatura_id'];

    $stmt = $pdo->prepare("INSERT INTO docente_asignacion (usuario_id, grupo_id, asignatura_id) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$docente_id, $grupo_id, $asignatura_id]);
    } catch (PDOException $e) {
        $error = "Error: Esta asignación ya existe o hay un conflicto.";
    }
}

// --- ELIMINAR ASIGNACIÓN ---
if (isset($_GET['eliminar'])) {
    $pdo->prepare("DELETE FROM docente_asignacion WHERE id=?")->execute([$_GET['eliminar']]);
}

// Consultar datos
$docentes = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE rol='docente' ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$asignaturas = $pdo->query("SELECT id, nombre FROM asignaturas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$asignaciones = $pdo->query("
    SELECT da.id, u.nombre_completo AS docente, g.nombre AS grupo, a.nombre AS asignatura
    FROM docente_asignacion da
    INNER JOIN usuarios u ON da.usuario_id = u.id
    INNER JOIN grupos g ON da.grupo_id = g.id
    INNER JOIN asignaturas a ON da.asignatura_id = a.id
    ORDER BY g.nombre, a.nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Docente a Aula</title>
    <link rel="stylesheet" href="Asignar_Docente.css">
</head>
<body>
<div class="container">
    <h1>Asignar Docente a Aula</h1>
    <a href="logout.php">Cerrar Sesión</a>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="accion" value="crear_asignacion">

        <select name="docente_id" required>
            <option value="">Seleccione Docente</option>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre_completo']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="grupo_id" required>
            <option value="">Seleccione Aula</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="asignatura_id" required>
            <option value="">Seleccione Asignatura</option>
            <?php foreach ($asignaturas as $a): ?>
                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Asignar</button>
    </form>

    <h2>Asignaciones Existentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Docente</th>
            <th>Aula</th>
            <th>Asignatura</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($asignaciones as $as): ?>
            <tr>
                <td><?= $as['id'] ?></td>
                <td><?= htmlspecialchars($as['docente']) ?></td>
                <td><?= htmlspecialchars($as['grupo']) ?></td>
                <td><?= htmlspecialchars($as['asignatura']) ?></td>
                <td>
                    <a href="?eliminar=<?= $as['id'] ?>" onclick="return confirm('¿Eliminar asignación?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
