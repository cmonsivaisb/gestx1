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
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Validar datos obligatorios
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'El campo "nombre" es obligatorio.']);
        exit();
    }

    // Obtener ID del responsable
    $responsableNombre = trim($_POST['responsable'] ?? 'otro');
    if (empty($responsableNombre)) {
        echo json_encode(['success' => false, 'error' => 'El campo "responsable" es obligatorio.']);
        exit();
    }

    try {
        $query = "SELECT id_responsable FROM responsables WHERE nombre_responsable = :nombre LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':nombre', $responsableNombre, PDO::PARAM_STR);
        $stmt->execute();
        $responsable = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($responsable) {
            $id_responsable = $responsable['id_responsable'];
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró un responsable con el nombre proporcionado.']);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al procesar el responsable: ' . $e->getMessage()]);
        exit();
    }

    // Obtener ID del subtema
    $subtemaNombre = trim($_POST['subtema'] ?? 'otro');
    if (empty($subtemaNombre)) {
        echo json_encode(['success' => false, 'error' => 'El campo "subtema" es obligatorio.']);
        exit();
    }

    try {
        $query = "SELECT id FROM subtemas WHERE nombre_subtema = :nombre LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':nombre', $subtemaNombre, PDO::PARAM_STR);
        $stmt->execute();
        $subtema = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subtema) {
            $id_subtema = $subtema['id'];
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró un subtema con el nombre proporcionado.']);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al procesar el subtema: ' . $e->getMessage()]);
        exit();
    }

    // Insertar la gestión
    try {
        $stmt = $pdo->prepare("INSERT INTO gestiones (id_tema, detalle, origen, id_estado, id_municipio, id_colonia, num_exterior, celular, seccional, id_usuario, estatus, nombre, calle, id_subtema, id_responsable, fecha) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_tema, $detalle, $origen, $id_estado, $id_municipio, $id_colonia, $num_exterior,
            $celular, $seccional, $id_usuario, $status, $nombre, $calle, $id_subtema, $id_responsable, $fecha
        ]);

        // Obtener el ID de la gestión recién insertada
        $id_gestion = $pdo->lastInsertId();

        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => "Gestión guardada correctamente. El folio es: $id_gestion",
            'id_gestion' => $id_gestion
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar: favor de revisar que los campos estén llenados con los datos correctos. ' . $e->getMessage()]);
        exit();
    }
}
?>
