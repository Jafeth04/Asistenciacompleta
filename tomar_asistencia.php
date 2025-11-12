<?php
session_start();

// Validar rol docente (descomenta si usas roles)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    header("#");
    exit;
}

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

$docente_id = $_SESSION['usuario_id'];

// Consultar asignaciones del docente
$asignaciones_stmt = $pdo->prepare("
    SELECT da.id, g.id AS grupo_id, g.nombre AS grupo, a.id AS asignatura_id, a.nombre AS asignatura
    FROM docente_asignacion da
    INNER JOIN grupos g ON da.grupo_id = g.id
    INNER JOIN asignaturas a ON da.asignatura_id = a.id
    WHERE da.usuario_id = ?
");
$asignaciones_stmt->execute([$docente_id]);
$asignaciones = $asignaciones_stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables
$estudiantes = [];
$grupo_id = $_GET['grupo_id'] ?? null;
$asignatura_id = $_GET['asignatura_id'] ?? null;
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Cargar estudiantes si se selecciona grupo y asignatura
if ($grupo_id && $asignatura_id) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.nombre_completo
        FROM matriculas m
        INNER JOIN estudiantes e ON m.estudiante_id = e.id
        WHERE m.grupo_id = ?
    ");
    $stmt->execute([$grupo_id]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Guardar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asistencia'])) {
    foreach ($_POST['asistencia'] as $estudiante_id => $estado) {
        $observaciones = $_POST['observaciones'][$estudiante_id] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO asistencia (fecha, grupo_id, asignatura_id, docente_id, estudiante_id, estado, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), observaciones = VALUES(observaciones)
        ");
        $stmt->execute([$fecha, $grupo_id, $asignatura_id, $docente_id, $estudiante_id, $estado, $observaciones]);
    }
    $mensaje = "✅ Asistencia guardada correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tomar Asistencia</title>
    <link rel="stylesheet" href="tomar_asistencia.css">
</head>
<body>
<div class="container">
    <h1>Tomar Asistencia</h1>
    <a href="logout.php" class="logout">Cerrar Sesión</a>

    <?php if (!empty($mensaje)): ?>
        <p class="success"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="GET" class="filtro-form">
        <label>Fecha:</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" required>

        <label>Grupo:</label>
        <select name="grupo_id" required>
            <option value="">Seleccione Grupo</option>
            <?php foreach ($asignaciones as $as): ?>
                <option value="<?= $as['grupo_id'] ?>" <?= $grupo_id == $as['grupo_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($as['grupo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Asignatura:</label>
        <select name="asignatura_id" required>
            <option value="">Seleccione Asignatura</option>
            <?php foreach ($asignaciones as $as): ?>
                <option value="<?= $as['asignatura_id'] ?>" <?= $asignatura_id == $as['asignatura_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($as['asignatura']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Cargar Estudiantes</button>
    </form>

    <?php if ($estudiantes): ?>
        <form method="POST" class="asistencia-form">
            <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($grupo_id) ?>">
            <input type="hidden" name="asignatura_id" value="<?= htmlspecialchars($asignatura_id) ?>">
            <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">

            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['nombre_completo']) ?></td>
                            <td>
                                <select name="asistencia[<?= $e['id'] ?>]" required>
                                    <option value="presente">Presente</option>
                                    <option value="ausente">Ausente</option>
                                    <option value="justificado">Justificado</option>
                                    <option value="tarde">Tarde</option>
                                </select>
                            </td>
                            <td><input type="text" name="observaciones[<?= $e['id'] ?>]" placeholder="Observación"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" class="guardar-btn">Guardar Asistencia</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
