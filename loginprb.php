<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    if (!empty($usuario) && !empty($contrasena)) {
        // Consulta el usuario
        $stmt = $pdo->prepare("SELECT id, usuario, contrasena, rol FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Compara texto plano (solo para pruebas)
        if ($user && $contrasena === $user['contrasena']) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];

            // Redirección según rol
            switch ($user['rol']) {
                case 'admin':
                    header("Location: Admin_pantallaprincipal.php");
                    break;
                case 'docente':
                    header("Location: tomar_asistencia.php");
                    break;
                case 'padre':
                    header("Location: padre_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Asistencia</title>
    <link rel="stylesheet" href="loginprb.css">
</head>
<body>
<div class="login-container">
    <h2>Iniciar Sesión</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>
