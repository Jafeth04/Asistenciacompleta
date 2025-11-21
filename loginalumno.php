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

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo']; // Código del estudiante

    // Buscar estudiante por código
    $stmt = $pdo->prepare("SELECT id, nombre_completo FROM estudiantes WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($estudiante) {
        // Login exitoso
        $_SESSION['rol'] = 'estudiante';
        $_SESSION['usuario_id'] = $estudiante['id'];
        $_SESSION['nombre'] = $estudiante['nombre_completo'];

        header("Location: Estudiante_dashboard.php");
        exit;
    } else {
        $mensaje = "⚠️ Código incorrecto.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login Alumno</title>
<link rel="stylesheet" href="loginalumno.css">
</head>
<body>
<div class="login-box">
    <h2>Acceso Alumno</h2>

    <?php if($mensaje): ?>
        <p class="error"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Código del estudiante</label>
        <input type="text" name="codigo" required placeholder="Ingrese su código">

        <button type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>
