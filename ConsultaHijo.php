<?php
session_start();
 if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'padre') {
     header("Location: login.php");
    exit;
 }


// Conexión a la base de datos (PDO)
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

$error = '';


$padre_id = $_SESSION['usuario_id'];

// Consultar hijos del padre
$hijos = $pdo->prepare("
    SELECT e.id AS estudiante_id, e.nombre_completo
    FROM padre_estudiante pe
    INNER JOIN estudiantes e ON pe.estudiante_id = e.id
    WHERE pe.usuario_id = ?
");
$hijos->execute([$padre_id]);
$hijos = $hijos->fetchAll(PDO::FETCH_ASSOC);

$reporte = [];
$estudiante_id = $fecha_inicio = $fecha_fin = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['estudiante_id'])) {
    $estudiante_id = $_GET['estudiante_id'];
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];

    $stmt = $pdo->prepare("
        SELECT a.fecha, a.estado, g.nombre AS grupo, asig.nombre AS asignatura
        FROM asistencia a
        INNER JOIN grupos g ON a.grupo_id = g.id
        INNER JOIN asignaturas asig ON a.asignatura_id = asig.id
        WHERE a.estudiante_id = ? AND a.fecha BETWEEN ? AND ?
        ORDER BY a.fecha
    ");
    $stmt->execute([$estudiante_id, $fecha_inicio, $fecha_fin]);
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Asistencia</title>
    <link rel="stylesheet" href="ConsultaHijo.css">
</head>
<body>
<div class="container">
    <h1>Consulta de Asistencia del Hijo</h1>
    <a href="logout.php">Cerrar Sesión</a>

    <form method="GET">
        <select name="estudiante_id" required>
            <option value="">Seleccione Hijo</option>
            <?php foreach ($hijos as $h): ?>
                <option value="<?= $h['estudiante_id'] ?>" <?= $estudiante_id==$h['estudiante_id']?'selected':'' ?>>
                    <?= htmlspecialchars($h['nombre_completo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
        <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" required>
        <button type="submit">Consultar</button>
    </form>

    <?php if ($reporte): ?>
        <h2>Resultados</h2>
        <table border="1" id="tablaReporte">
            <tr><th>Fecha</th><th>Estado</th><th>Grupo</th><th>Asignatura</th></tr>
            <?php foreach ($reporte as $r): ?>
                <tr>
                    <td><?= $r['fecha'] ?></td>
                    <td><?= $r['estado'] ?></td>
                    <td><?= htmlspecialchars($r['grupo']) ?></td>
                    <td><?= htmlspecialchars($r['asignatura']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Botones JS -->
        <button id="btnPDF">Descargar PDF</button>
        <button id="btnExcel">Descargar Excel</button>
    <?php elseif ($estudiante_id && $fecha_inicio && $fecha_fin): ?>
        <p>No se encontraron registros para el periodo seleccionado.</p>
    <?php endif; ?>
</div>

<!-- Librerías JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="ConsultaHijo.js"></script>
</body>
</html>
