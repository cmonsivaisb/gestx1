<?php
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
        return ['id' => null, 'nombre_subtema' => 'Error: ' . $e->getMessage()];
    }
}


function obtenerOCrearResponsable($nombre_responsable, $pdo) {
    if (empty($nombre_responsable)) {
        return ['id' => null, 'nombre_responsable' => 'No asignado'];
    }

    try {
        // Verificar si el responsable ya existe
        $stmt = $pdo->prepare("SELECT id_responsable FROM responsables WHERE nombre_responsable = ?");
        $stmt->execute([$nombre_responsable]);
        $responsable = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($responsable) {
            return ['id' => $responsable['id_responsable'], 'nombre_responsable' => $nombre_responsable];
        }

        // Si no existe, insertar nuevo responsable
        $stmt = $pdo->prepare("INSERT INTO responsables (nombre_responsable) VALUES (?)");
        $stmt->execute([$nombre_responsable]);
        $new_id = $pdo->lastInsertId();

        return ['id' => $new_id, 'nombre_responsable' => $nombre_responsable];
    } catch (PDOException $e) {
        debug_log("Error al manejar responsable: " . $e->getMessage());
        return ['id' => null, 'nombre_responsable' => 'Error: ' . $e->getMessage()];
    }
}

debug_log("Verificando método de solicitud.");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit();
}

debug_log("Recibiendo datos POST.");

// Establecer encabezado de respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Datos POST
$id = (int) ($_POST['id'] ?? 0);
$id_tema = (int) ($_POST['tema'] ?? 0);
$subtema_nombre = trim($_POST['subtema'] ?? '');
$responsable_nombre = trim($_POST['responsable'] ?? 'no asignado');
$detalle = trim($_POST['detalle'] ?? '');
$origen = (int) ($_POST['origen'] ?? 1);
$id_estado = (int) ($_POST['estadoNacimiento'] ?? 0);
$id_municipio = (int) ($_POST['municipio'] ?? 0);
$id_colonia = (int) ($_POST['colonia'] ?? 0);
$num_exterior = trim($_POST['numExterior'] ?? '');
$seccional = trim($_POST['seccional'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$calle = trim($_POST['calle'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$estatus = (int) ($_POST['status'] ?? 1);

debug_log("Datos sanitizados recibidos.");

// Validar campos obligatorios
$errors = [];
if ($id <= 0) $errors[] = 'ID de gestión no proporcionado o inválido.';
if ($id_tema <= 0) $errors[] = 'Tema no proporcionado o inválido.';
if (empty($subtema_nombre)) $errors[] = 'Subtema no proporcionado.';

if (!empty($errors)) {
    debug_log("Errores de validación: " . implode(', ', $errors));
    echo json_encode(['error' => implode(', ', $errors)], JSON_UNESCAPED_UNICODE);
    exit();
}

debug_log("Validación exitosa. Manejar subtema y responsable.");

// Manejar el subtema
$subtema = obtenerOCrearSubtema($subtema_nombre, $pdo);
if (!$subtema['id']) {
    debug_log("Error con el subtema: " . $subtema['nombre_subtema']);
    echo json_encode(['error' => 'Error con el subtema: ' . $subtema['nombre_subtema']], JSON_UNESCAPED_UNICODE);
    exit();
}
$responsable = obtenerOCrearResponsable($responsable_nombre, $pdo);
if (!$responsable['id']) {
    debug_log("Error con el subtema: " . $subtema['nombre_subtema']);
    echo json_encode(['error' => 'Error con el subtema: ' . $subtema['nombre_subtema']], JSON_UNESCAPED_UNICODE);
    exit();
}


try {
    $stmt = $pdo->prepare("
        UPDATE gestiones 
        SET id_tema = ?, id_subtema = ?, id_responsable = ?, detalle = ?, origen = ?, 
            id_estado = ?, id_municipio = ?, id_colonia = ?, num_exterior = ?, celular = ?, 
            seccional = ?, nombre = ?, calle = ?, estatus = ?, fecha = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([
        $id_tema, $subtema['id'], $responsable['id'], $detalle, $origen,
        $id_estado, $id_municipio, $id_colonia, $num_exterior, $celular,
        $seccional, $nombre, $calle, $estatus, $id
    ]);

    if ($stmt->rowCount() > 0) {
        debug_log("Gestión actualizada correctamente: ID $id");
        echo json_encode(['success' => 'Gestión actualizada correctamente.'], JSON_UNESCAPED_UNICODE);
    } else {
        debug_log("No se realizaron cambios en la gestión: ID $id");
        echo json_encode(['success' => 'No se realizaron cambios en la gestión.'], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    debug_log("Error al actualizar la gestión: " . $e->getMessage());
    echo json_encode(['error' => 'Error al actualizar la gestión: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
