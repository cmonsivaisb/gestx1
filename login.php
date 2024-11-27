<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($usuario && md5($password) === $usuario['password']) {
    $_SESSION['usuario_id'] = $usuario['id'];
    header('Location: gestion.php');
    exit();
    } else {
        echo $usuario['password'];
        echo md5($password);
        $error = "Credenciales incorrectas.";

    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3 class="text-center">Iniciar Sesión</h3>
    <?php if (isset($error)) { echo "<p class='text-danger'>$error</p>"; } ?>
    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="correo" class="form-label">Correo</label>
            <input type="email" class="form-control" id="correo" name="correo" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        <p class="mt-3">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </form>
</div>
</body>
</html>