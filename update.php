<?php
// update.php

// Habilitar la visualización de errores (deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para escribir en el log de depuración
function debug_log($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

debug_log("Inicio de update.php");

// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['usuario_id'])) {
    debug_log("Usuario no autenticado.");
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

debug_log("Usuario autenticado.");

// Conectar a la base de datos
try {
    include 'db.php'; // Asegúrate de que db.php inicializa $pdo como una instancia de PDO
    debug_log("Conectado a la base de datos.");
} catch (Exception $e) {
    debug_log("Error de conexión a la base de datos: " . $e->getMessage());
    // Responder con un error JSON si falla la conexión a la base de datos
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error de conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función para obtener o crear un subtema por nombre
 */
function obtenerOCrearSubtema($nombre_subtema, $pdo) {
    if (empty($nombre_subtema)) {
        return ['id' => null, 'nombre_subtema' => 'No asignado'];
    }

    try {
        // Verificar si el subtema ya existe
        $stmt = $pdo->prepare("SELECT id FROM subtemas WHERE nombre_subtema = ?");
        $stmt->execute([$nombre_subtema]);
        $subtema = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subtema) {
            return ['id' => $subtema['id'], 'nombre_subtema' => $nombre_subtema];
        }

        // Si no existe, insertar nuevo subtema
        $stmt = $pdo->prepare("INSERT INTO subtemas (nombre_subtema) VALUES (?)");
        $stmt->execute([$nombre_subtema]);
        $new_id = $pdo->lastInsertId();

        return ['id' => $new_id, 'nombre_subtema' => $nombre_subtema];
    } catch (PDOException $e) {
        // Devolver información de error
        return ['id' => null, 'nombre_subtema' => 'Error: ' . $e->getMessage()];
    }
}

debug_log("Verificando método de solicitud.");
// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Método de solicitud no permitido: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método de solicitud no permitido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

debug_log("Recibiendo datos POST.");

// Establecer encabezado de respuesta JSON con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');

// Inicializar arreglo de respuesta
$response = [];

// Recuperar y sanitizar datos de entrada
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$id_tema = isset($_POST['tema']) ? (int) $_POST['tema'] : 0;
$subtema = isset($_POST['subtema']) ? trim($_POST['subtema']) : '';
$detalle = isset($_POST['detalle']) ? trim($_POST['detalle']) : '';
$origen = isset($_POST['origen']) ? (int) $_POST['origen'] : 0;
$id_estado = isset($_POST['estadoNacimiento']) ? (int) $_POST['estadoNacimiento'] : 0;
$id_municipio = isset($_POST['municipio']) ? (int) $_POST['municipio'] : 0;
$id_colonia = isset($_POST['colonia']) ? (int) $_POST['colonia'] : 0;
$num_exterior = isset($_POST['numExterior']) ? (int) $_POST['numExterior'] : 0;
$seccional = isset($_POST['seccional']) ? trim($_POST['seccional']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$calle = isset($_POST['calle']) ? trim($_POST['calle']) : '';
$celular = isset($_POST['celular']) ? trim($_POST['celular']) : '';
$estatus = isset($_POST['status']) ? (int) $_POST['status'] : 1;

debug_log("Datos recibidos: " . print_r($_POST, true));

// Validar campos obligatorios
$errors = [];

if ($id <= 0) {
    $errors[] = 'ID de gestión no proporcionado o inválido.';
}

if ($id_tema <= 0) {
    $errors[] = 'Por favor, selecciona un tema válido.';
}

if (empty($subtema)) {
    $errors[] = 'Por favor, ingresa un subtema válido.';
}

if (!empty($errors)) {
    debug_log("Errores de validación: " . implode(' ', $errors));
    echo json_encode(['error' => implode(' ', $errors)], JSON_UNESCAPED_UNICODE);
    exit();
}

debug_log("Validaciones pasadas. Manejar subtema.");

// Manejar el subtema (obtener existente o crear nuevo)
$subtema_result = obtenerOCrearSubtema($subtema, $pdo);
if (!$subtema_result['id']) {
    debug_log("Error al manejar el subtema: " . $subtema_result['nombre_subtema']);
    echo json_encode(['error' => 'Error al manejar el subtema: ' . $subtema_result['nombre_subtema']], JSON_UNESCAPED_UNICODE);
    exit();
}
$id_subtema = $subtema_result['id'];
debug_log("Subtema manejado correctamente: ID subtema = " . $id_subtema);

// Actualizar la gestión en la base de datos
try {
    $stmt = $pdo->prepare("
        UPDATE gestiones 
        SET 
            id_tema = ?, 
            id_subtema = ?, 
            detalle = ?, 
            origen = ?, 
            id_estado = ?, 
            id_municipio = ?, 
            id_colonia = ?, 
            num_exterior = ?, 
            celular = ?, 
            seccional = ?, 
            nombre = ?, 
            calle = ?, 
            estatus = ?, 
            fecha = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");

    $stmt->execute([
        $id_tema, 
        $id_subtema, 
        $detalle, 
        $origen, 
        $id_estado, 
        $id_municipio, 
        $id_colonia, 
        $num_exterior, 
        $celular, 
        $seccional, 
        $nombre, 
        $calle, 
        $estatus, 
        $id
    ]);

    // Verificar si se actualizó alguna fila
    if ($stmt->rowCount() > 0) {
        $response['success'] = 'Gestión actualizada correctamente.';
        debug_log("Gestión actualizada correctamente. ID = " . $id);
    } else {
        $response['success'] = 'No se realizaron cambios en la gestión.';
        debug_log("No se realizaron cambios en la gestión. ID = " . $id);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    debug_log("Error al actualizar la gestión: " . $e->getMessage());
    // Manejar errores de la base de datos
    echo json_encode(['error' => 'Error al actualizar la gestión: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

exit();
?>
