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
    // Recoger datos del formulario
    $detalle = trim($_POST['detalle'] ?? '');
    $origen = $_POST['origen'] ?? 1;
    $id_estado = $_POST['estadoNacimiento'] ?? 5;
    $id_municipio = $_POST['municipio'] ?? 34;
    $id_colonia = $_POST['colonia'] ?? 1;
    $num_exterior = trim($_POST['numExterior'] ?? '');
    $seccional = trim($_POST['seccional'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $id_usuario = $_SESSION['usuario_id'];
    $id_tema = $_POST['tema'] ?? 1;
    $status = $_POST['status'] ?? 1;
    $fecha = $_POST['fecha'] ?? date('Y-m-d H:i:s');
    $subtema = trim($_POST['subtema'] ?? '');
    $responsable_name = trim($_POST['responsable'] ?? '');

    // Validar datos obligatorios
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'El campo "nombre" es obligatorio.']);
        exit();
    }

    try {
        // Lógica para guardar la gestión
        $stmt = $pdo->prepare("INSERT INTO gestiones (id_tema, detalle, origen, id_estado, id_municipio, id_colonia, num_exterior, celular, seccional, id_usuario, estatus, nombre, calle, id_subtema, id_responsable, fecha) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_tema, $detalle, $origen, $id_estado, $id_municipio, $id_colonia, $num_exterior,
            $celular, $seccional, $id_usuario, $status, $nombre, $calle, $subtema, $responsable_name, $fecha
        ]);
          // Obtener el ID de la gestión recién insertada
          $id_gestion = $pdo->lastInsertId();

          // Responder con éxito incluyendo el ID generado
          echo json_encode([
              'success' => true,
              'message' => "Gestión guardada correctamente. El folio es: $id_gestion",
              'id_gestion' => $id_gestion
          ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
    }
}
?>
