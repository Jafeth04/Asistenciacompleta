<?php
session_start();
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

// Consultas
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$total_docentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='docente'")->fetchColumn();
$total_grupos = $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn();

$hoy = date('Y-m-d');
$asistencia_hoy = $pdo->prepare("
    SELECT estado, COUNT(*) AS cantidad
    FROM asistencia
    WHERE fecha = ?
    GROUP BY estado
");
$asistencia_hoy->execute([$hoy]);
$asistencia_data = $asistencia_hoy->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="Admin_pantallaprincipal.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <!-- <li><a href="#">Inicio</a></li> -->
                <li><a href="Admin_usuarios_aulas.php">Administrar usuarios y aulas</a></li>
                <li><a href="Asignar_Docente.php">Asignar docente a aulas</a></li>
                <li><a href="Crud_estudiante.php">Gestion de estudiantes</a></li>
                <!-- <li><a href="Admin_asistencia.php">Asistencia</a></li> -->
            </ul>
        </nav>
    </header>
<div class="container">
    <h1>Dashboard del Administrador</h1>
   <!-- <a href="#"> logout.phpCerrar Sesión</a> -->

    <div class="stats">
        <div class="card">
            <h2><?= $total_estudiantes ?></h2>
            <p>Estudiantes</p>
        </div>
        <div class="card">
            <h2><?= $total_docentes ?></h2>
            <p>Docentes</p>
        </div>
        <div class="card">
            <h2><?= $total_grupos ?></h2>
            <p>Grupos</p>
        </div>
    </div>

    <h2>Asistencia del Día (<?= $hoy ?>)</h2>
    <table>
        <tr><th>Estado</th><th>Cantidad</th></tr>
        <?php foreach ($asistencia_data as $a): ?>
            <tr>
                <td><?= ucfirst($a['estado']) ?></td>
                <td><?= $a['cantidad'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>