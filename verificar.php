<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include 'db.php'; // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM gestiones WHERE TRIM(nombre) = ?");
            $stmt->execute([$nombre]);
            $gestion = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($gestion) {
                echo json_encode(['exists' => true, 'idgestion' => $gestion['id']]);
            } else {
                echo json_encode(['exists' => false]);
            }
        } catch (PDOException $e) {
            echo json_encode(['exists' => false, 'error' => 'Error en la verificación: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['exists' => false, 'error' => 'El campo nombre está vacío.']);
    }
}
?>
