<?php
session_start();

// // --- Validar rol docente ---
// if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
//      header("Location: login.php");
//      exit;
// }   

// --- Conexión a la base de datos ---
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

// --- ID del docente ---
$docente_id = $_SESSION['usuario_id'] ?? 1;

// --- Consultar asignaciones del docente ---
$asignaciones = [];
$stmt = $pdo->prepare("
    SELECT g.id AS grupo_id, g.nombre AS grupo, a.id AS asignatura_id, a.nombre AS asignatura
    FROM docente_asignacion da
    INNER JOIN grupos g ON da.grupo_id = g.id
    INNER JOIN asignaturas a ON da.asignatura_id = a.id
    WHERE da.usuario_id = ?
");
$stmt->execute([$docente_id]);
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Variables seguras para filtro ---
$grupo_id = $_GET['grupo_id'] ?? null;
$asignatura_id = $_GET['asignatura_id'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// --- Generar reporte solo si hay datos ---
$reporte = [];
if ($grupo_id && $asignatura_id && $fecha_inicio && $fecha_fin) {
    $stmt = $pdo->prepare("
        SELECT e.nombre_completo, a.fecha, a.estado
        FROM asistencia a
        INNER JOIN estudiantes e ON a.estudiante_id = e.id
        WHERE a.docente_id = ? 
          AND a.grupo_id = ? 
          AND a.asignatura_id = ? 
          AND a.fecha BETWEEN ? AND ?
        ORDER BY e.nombre_completo, a.fecha
    ");
    $stmt->execute([$docente_id, $grupo_id, $asignatura_id, $fecha_inicio, $fecha_fin]);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Asistencia</title>
    <link rel="stylesheet" href="Reporte.css">
</head>
<body>
    <header>
        <h1>reporte de asitencia</h1>
        <nav>
            <a href="tomar_asistencia.php">Tomar Asistencia</a> |
            <a href="matriculas.php">generar Matrículas</a>
            <a href="Gestion_matriculas.php"></a>
        </nav>
    </header>
<div class="container">
    <h1>Reportes de Asistencia</h1>
    <a href="logout.php">Cerrar Sesión</a>

    <form method="GET">
        <select name="grupo_id" required>
            <option value="">Seleccione Grupo</option>
            <?php foreach ($asignaciones as $as): ?>
                <option value="<?= $as['grupo_id'] ?>" <?= $grupo_id==$as['grupo_id']?'selected':'' ?>>
                    <?= htmlspecialchars($as['grupo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="asignatura_id" required>
            <option value="">Seleccione Asignatura</option>
            <?php foreach ($asignaciones as $as): ?>
                <option value="<?= $as['asignatura_id'] ?>" <?= $asignatura_id==$as['asignatura_id']?'selected':'' ?>>
                    <?= htmlspecialchars($as['asignatura']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" required>
        <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" required>
        <button type="submit">Generar Reporte</button>
    </form>

    <?php if (!empty($reporte)): ?>
        <h2>Resultados</h2>
        <table border="1" id="tablaReporte">
            <tr><th>Estudiante</th><th>Fecha</th><th>Estado</th></tr>
            <?php foreach ($reporte as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nombre_completo']) ?></td>
                    <td><?= $r['fecha'] ?></td>
                    <td><?= $r['estado'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Botones JS -->
        <button id="btnPDF">Descargar PDF</button>
        <button id="btnExcel">Descargar Excel</button>
    <?php elseif ($grupo_id && $asignatura_id && $fecha_inicio && $fecha_fin): ?>
        <p>No se encontraron registros para el periodo seleccionado.</p>
    <?php endif; ?>
</div>

<!-- Librerías JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="Reporte.js"></script>

</body>
</html>
