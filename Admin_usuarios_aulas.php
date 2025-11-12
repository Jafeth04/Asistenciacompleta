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

// Variables para edición
$usuarioEditar = null;
$aulaEditar = null;

// --- CREAR USUARIO ---
if (isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario') {
    $usuario = $_POST['usuario'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, contrasena, nombre_completo, email, rol) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario, $contrasena, $nombre, $email, $rol]);
}

// --- EDITAR USUARIO ---
if (isset($_POST['accion']) && $_POST['accion'] === 'editar_usuario') {
    $id = $_POST['id'];
    $usuario = $_POST['usuario'];
    $nombre = $_POST['nombre_completo'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];

    if (!empty($_POST['contrasena'])) {
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET usuario=?, contrasena=?, nombre_completo=?, email=?, rol=? WHERE id=?");
        $stmt->execute([$usuario, $contrasena, $nombre, $email, $rol, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET usuario=?, nombre_completo=?, email=?, rol=? WHERE id=?");
        $stmt->execute([$usuario, $nombre, $email, $rol, $id]);
    }
}

// --- ELIMINAR USUARIO ---
if (isset($_GET['eliminar_usuario'])) {
    $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$_GET['eliminar_usuario']]);
}

// --- CARGAR USUARIO PARA EDITAR ---
if (isset($_GET['editar_usuario'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $stmt->execute([$_GET['editar_usuario']]);
    $usuarioEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- CREAR AULA ---
if (isset($_POST['accion']) && $_POST['accion'] === 'crear_aula') {
    $nombre = $_POST['nombre'];
    $grado = $_POST['grado'];
    $turno = $_POST['turno'];

    $stmt = $pdo->prepare("INSERT INTO grupos (nombre, grado, turno) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $grado, $turno]);
}

// --- EDITAR AULA ---
if (isset($_POST['accion']) && $_POST['accion'] === 'editar_aula') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $grado = $_POST['grado'];
    $turno = $_POST['turno'];

    $stmt = $pdo->prepare("UPDATE grupos SET nombre=?, grado=?, turno=? WHERE id=?");
    $stmt->execute([$nombre, $grado, $turno, $id]);
}

// --- ELIMINAR AULA ---
if (isset($_GET['eliminar_aula'])) {
    $pdo->prepare("DELETE FROM grupos WHERE id=?")->execute([$_GET['eliminar_aula']]);
}

// --- CARGAR AULA PARA EDITAR ---
if (isset($_GET['editar_aula'])) {
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id=?");
    $stmt->execute([$_GET['editar_aula']]);
    $aulaEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Consultar todos
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$aulas = $pdo->query("SELECT * FROM grupos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="Admin_usuarios_aulas.css">
</head>
<body>
<div class="container">
    <h1>Panel de Administración</h1>
    <a href="logout.php">Cerrar Sesión</a>

    <!-- Usuarios -->
    <div class="section">
        <h2>Usuarios</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="<?= $usuarioEditar ? 'editar_usuario' : 'crear_usuario' ?>">
            <?php if ($usuarioEditar): ?>
                <input type="hidden" name="id" value="<?= $usuarioEditar['id'] ?>">
            <?php endif; ?>
            <input type="text" name="usuario" placeholder="Usuario" value="<?= $usuarioEditar['usuario'] ?? '' ?>" required>
            <input type="text" name="nombre_completo" placeholder="Nombre completo" value="<?= $usuarioEditar['nombre_completo'] ?? '' ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?= $usuarioEditar['email'] ?? '' ?>">
            <input type="password" name="contrasena" placeholder="Contraseña <?= $usuarioEditar ? '(opcional)' : '' ?>">
            <select name="rol" required>
                <option value="admin" <?= isset($usuarioEditar) && $usuarioEditar['rol']=='admin'?'selected':'' ?>>Administrador</option>
                <option value="docente" <?= isset($usuarioEditar) && $usuarioEditar['rol']=='docente'?'selected':'' ?>>Docente</option>
                <option value="padre" <?= isset($usuarioEditar) && $usuarioEditar['rol']=='padre'?'selected':'' ?>>Padre</option>
            </select>
            <button type="submit"><?= $usuarioEditar ? 'Actualizar Usuario' : 'Agregar Usuario' ?></button>
            <?php if ($usuarioEditar): ?>
                <a href="admin_usuarios_aulas.php">Cancelar</a>
            <?php endif; ?>
        </form>

        <table>
            <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Acciones</th></tr>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                    <td><?= $u['rol'] ?></td>
                    <td>
                        <a href="?editar_usuario=<?= $u['id'] ?>">Editar</a> |
                        <a href="?eliminar_usuario=<?= $u['id'] ?>" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Aulas -->
    <div class="section">
        <h2>Aulas</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="<?= $aulaEditar ? 'editar_aula' : 'crear_aula' ?>">
            <?php if ($aulaEditar): ?>
                <input type="hidden" name="id" value="<?= $aulaEditar['id'] ?>">
            <?php endif; ?>
            <input type="text" name="nombre" placeholder="Nombre del aula" value="<?= $aulaEditar['nombre'] ?? '' ?>" required>
            <input type="text" name="grado" placeholder="Grado" value="<?= $aulaEditar['grado'] ?? '' ?>">
            <select name="turno">
                <option value="Matutino" <?= isset($aulaEditar) && $aulaEditar['turno']=='Matutino'?'selected':'' ?>>Matutino</option>
                <option value="Vespertino" <?= isset($aulaEditar) && $aulaEditar['turno']=='Vespertino'?'selected':'' ?>>Vespertino</option>
                <option value="Nocturno" <?= isset($aulaEditar) && $aulaEditar['turno']=='Nocturno'?'selected':'' ?>>Nocturno</option>
            </select>
            <button type="submit"><?= $aulaEditar ? 'Actualizar Aula' : 'Agregar Aula' ?></button>
            <?php if ($aulaEditar): ?>
                <a href="admin_usuarios_aulas.php">Cancelar</a>
            <?php endif; ?>
        </form>

        <table>
            <tr><th>ID</th><th>Nombre</th><th>Grado</th><th>Turno</th><th>Acciones</th></tr>
            <?php foreach ($aulas as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['nombre']) ?></td>
                    <td><?= htmlspecialchars($a['grado']) ?></td>
                    <td><?= $a['turno'] ?></td>
                    <td>
                        <a href="?editar_aula=<?= $a['id'] ?>">Editar</a> |
                        <a href="?eliminar_aula=<?= $a['id'] ?>" onclick="return confirm('¿Eliminar aula?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
