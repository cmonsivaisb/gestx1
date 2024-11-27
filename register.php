<?php
session_start();
include 'db.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirigir al login si no está autenticado
    exit();
}

// Consultar los detalles del usuario autenticado
$stmt = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si el usuario tiene perfil de Administrador
if ($usuario['perfil'] !== 'Administrador') {
    echo "Acceso denegado. Solo los administradores pueden registrar nuevos usuarios.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = md5($_POST['password']);

    // Insertar nuevo usuario
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, perfil) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $correo, $password, $_POST['perfil']]);

    header('Location: gestion.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3 class="text-center">Registro de Usuario</h3>
    <form method="POST" action="register.php">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
            <label for="correo" class="form-label">Correo</label>
            <input type="email" class="form-control" id="correo" name="correo" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="perfil" class="form-label">Perfil</label>
            <select class="form-select" id="perfil" name="perfil">
                <option value="Usuario Regular">Usuario Regular</option>
                <option value="Administrador">Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Registrar Usuario</button>
    </form>
</div>
</body>
</html>
