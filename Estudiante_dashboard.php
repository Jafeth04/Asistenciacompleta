<?php
session_start();

// Validar que el estudiante esté logueado
// if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'estudiante') {
//     header("Location: login.php");
//     exit;
// }

$estudiante_id = $_SESSION['usuario_id']; // o $_SESSION['estudiante_id']

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

// Consulta la información del estudiante
$stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
$stmt->execute([$estudiante_id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

// Consulta la asistencia del estudiante
$asistencia_stmt = $pdo->prepare("
    SELECT a.fecha, g.nombre AS grupo, s.nombre AS asignatura, a.estado, a.observaciones
    FROM asistencia a
    INNER JOIN grupos g ON a.grupo_id = g.id
    INNER JOIN asignaturas s ON a.asignatura_id = s.id
    WHERE a.estudiante_id = ?
    ORDER BY a.fecha DESC
");
$asistencia_stmt->execute([$estudiante_id]);
$asistencias = $asistencia_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Estudiante</title>
    <link rel="stylesheet" href="Estudiante_dashboard.css">
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="Estudiante_dashboard.php">Inicio</a></li>
            <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <h1>Bienvenido, <?= htmlspecialchars($estudiante['nombre_completo']) ?></h1>

    <h2>Mi Asistencia</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Grupo</th>
                <th>Asignatura</th>
                <th>Estado</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($asistencias) > 0): ?>
                <?php foreach ($asistencias as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['fecha']) ?></td>
                        <td><?= htmlspecialchars($a['grupo']) ?></td>
                        <td><?= htmlspecialchars($a['asignatura']) ?></td>
                        <td class="<?= $a['estado'] ?>"><?= ucfirst($a['estado']) ?></td>
                        <td><?= htmlspecialchars($a['observaciones']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay registros de asistencia.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
