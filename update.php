<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirect if not authenticated
    exit();
}

// Connect to the database
try {
    include 'db.php';
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Fetch gestion details
function obtenerGestionPorId($id, $pdo) {
    $stmt = $pdo->prepare("SELECT g.*, e.nombre_estado, m.nombre_municipio 
                           FROM gestiones g
                           LEFT JOIN estados e ON g.id_estado = e.id
                           LEFT JOIN municipios m ON g.id_municipio = m.id
                           WHERE g.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch available topics
function obtenerTemas($pdo) {
    $stmt = $pdo->query("
    SELECT id, nombre_tema 
    FROM temas 
    WHERE nombre_tema IN (
       'RECURSOS HUMANOS',
       'JURIDICO',
       'INSTITUTO MUNICIPAL DE LAS MUJERES',
       'INCLUSION Y DESARROLLO SOCIAL',
       'INFRAESTRUCTURA Y DESARROLLO URBANO',
       'VENTANILLA UNIVERSAL',
       'MOVILIDAD Y TRANSPORTE',
       'MEDIO AMBIENTE',
       'DESARROLLO RURAL',
       'TURISMO',
       'CULTURA',
       'ECONOMIA Y COMERCIO',
       'DIF',
       'CLINICA MUNICIPAL',
       'RELACIONES PUBLICAS',
       'SECRETARIA TECNICA',
       'SEGURIDAD PUBLICA',
       'PROTECCION CIVIL Y BOMBEROS',
       'GERENTE SIMAS',
       'DEPORTES',
       'SERVICIOS PRIMARIOS',
       'SECRETARIA DE AYUNTAMIENTO',
       'ESTADO'
    )
    ORDER BY FIELD(
    
       'RECURSOS HUMANOS',
       'JURIDICO',
       'INSTITUTO MUNICIPAL DE LAS MUJERES',
       'INCLUSION Y DESARROLLO SOCIAL',
       'INFRAESTRUCTURA Y DESARROLLO URBANO',
       'VENTANILLA UNIVERSAL',
       'MOVILIDAD Y TRANSPORTE',
       'MEDIO AMBIENTE',
       'DESARROLLO RURAL',
       'TURISMO',
       'CULTURA',
       'ECONOMIA Y COMERCIO',
       'DIF',
       'CLINICA MUNICIPAL',
       'RELACIONES PUBLICAS',
       'SECRETARIA TECNICA',
       'SEGURIDAD PUBLICA',
       'PROTECCION CIVIL Y BOMBEROS',
       'GERENTE SIMAS',
       'DEPORTES',
       'SERVICIOS PRIMARIOS',
       'SECRETARIA DE AYUNTAMIENTO',
       'ESTADO'
    )");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch available colonias
function obtenerColonias($pdo) {
    $stmt = $pdo->query("SELECT id, nombre_colonia FROM colonias");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all states
function obtenerEstados($pdo) {
    $stmt = $pdo->query("SELECT id, nombre_estado FROM estados");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch municipalities for a state
function obtenerMunicipios($estado_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id, nombre_municipio FROM municipios WHERE id_estado = ?");
    $stmt->execute([$estado_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['action']) && $_POST['action'] === 'editar') {
    header('Content-Type: application/json; charset=utf-8'); // Forzar tipo de contenido JSON
    ob_start(); // Inicia el buffer de salida para evitar mensajes no deseados

    try {
        // Obtén los datos enviados
        $id = (int) $_POST['id'];
        $detalle = $_POST['detalle'] ?? '';
        $origen = $_POST['origen'] ?? '';
        $id_estado = $_POST['estadoNacimiento'] ?? '';
        $id_municipio = $_POST['municipio'] ?? '';
        $id_colonia = $_POST['colonia'] ?? '';
        $num_exterior = $_POST['numExterior'] ?? '';
        $seccional = $_POST['seccional'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $calle = $_POST['calle'] ?? '';
        $celular = $_POST['celular'] ?? '';
        $id_tema = $_POST['tema'] ?? '';
        $estatus = $_POST['status'] ?? '';
        $id_subtema = obtenerOCrearSubtema($_POST['subtema'], $pdo);

        // Valida que el ID es válido
        if (!$id) {
            ob_end_clean();
            echo json_encode(['error' => 'ID de gestión no proporcionado.']);
            exit();
        }

        // Ejecuta la consulta para actualizar
        $stmt = $pdo->prepare("UPDATE gestiones 
                            SET id_tema = ?, 
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
                            WHERE id = ?");
        $stmt->execute([
            $id_tema, $id_subtema, $detalle, $origen, $id_estado, $id_municipio, $id_colonia,
            $num_exterior, $celular, $seccional, $nombre, $calle, $estatus, $id
        ]);

        // Respuesta exitosa
        ob_end_clean();
        echo json_encode(['success' => 'Gestión actualizada correctamente.']);
    } catch (PDOException $e) {
        // Captura errores y responde con JSON
        ob_end_clean();
        echo json_encode(['error' => 'Error al actualizar la gestión: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch data for editing form
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de gestión no proporcionado.");
}

$gestion = obtenerGestionPorId($id, $pdo);
if (!$gestion) {
    die("Gestión no encontrada.");
}

function obtenerOCrearSubtema($nombre_subtema = null, $pdo = null) {
    // Verifica si es una solicitud AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'obtenerOCrearSubtema') {
        header('Content-Type: application/json; charset=utf-8'); // Asegura que se devuelva un JSON
        $nombre_subtema = $_POST['nombre_subtema'] ?? '';
        try {
            if (empty($nombre_subtema)) {
                echo json_encode(['error' => 'El nombre del subtema está vacío.']);
                exit();
            }

            // Conexión a la base de datos en caso de uso con AJAX
            include 'db.php'; // Asegúrate de que `db.php` tiene la conexión `$pdo`

            $stmt = $pdo->prepare("SELECT id, nombre_subtema FROM subtemas WHERE nombre_subtema = ?");
            $stmt->execute([$nombre_subtema]);
            $subtema = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($subtema) {
                echo json_encode(['id' => $subtema['id'], 'nombre_subtema' => $subtema['nombre_subtema']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subtemas (nombre_subtema) VALUES (?)");
                $stmt->execute([$nombre_subtema]);
                echo json_encode(['id' => $pdo->lastInsertId(), 'nombre_subtema' => $nombre_subtema]);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
        }
        exit();
    }

    // Uso normal de la función en PHP
    if (empty($nombre_subtema)) {
        return 1; // Retorna un valor por defecto si el nombre está vacío
    }

    $stmt = $pdo->prepare("SELECT id, nombre_subtema FROM subtemas WHERE nombre_subtema = ?");
    $stmt->execute([$nombre_subtema]);
    $subtema = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subtema) {
        return $subtema['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO subtemas (nombre_subtema) VALUES (?)");
        $stmt->execute([$nombre_subtema]);
        return $pdo->lastInsertId();
    }
}


$temas = obtenerTemas($pdo);
$colonias = obtenerColonias($pdo);
$estados = obtenerEstados($pdo);
$municipios = obtenerMunicipios($gestion['id_estado'], $pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Gestión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            position: fixed;
            top: 0;
            left: 0;
            font-size: 14px;
            padding: 12px;
            width: 180px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar a {
            text-decoration: none;
            color: inherit;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .sidebar a:hover {
            color: #000;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="text-center">
        <img src="logo.png" alt="Logo" class="img-fluid" style="max-width: 100%; height: auto;">
    </div>
    <h4 class="text-center">MENU</h4>
    <a href="dashboard.php">DASHBOARD</a>
    <a href="gestion.php">GESTIONES</a>
    <ul class="list-unstyled ps-3">
        <li><a href="gestion.php#gestionForm">Captura</a></li>
        <li><a href="gestion.php#filtroForm">Consulta</a></li>
    </ul>
    <a href="logout.php">CERRAR SESION</a>
</div>

<div class="content">
    <div class="container mt-5">
        <h1 class="text-center">Editar Gestión</h1>
        <form id="editarForm">
            <div class="mb-3">
                <label for="tema" class="form-label">Tema</label>
                <select id="tema" name="tema" class="form-select">
                    <?php foreach ($temas as $tema): ?>
                        <option value="<?= $tema['id'] ?>" <?= $tema['id'] == $gestion['id_tema'] ? 'selected' : '' ?>>
                            <?= $tema['nombre_tema'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="col-md-4">
    <label for="subtema" class="form-label">Subtema</label>
    <input type="text" class="form-control" id="subtema" name="subtema" placeholder="Subtema"  value=  <?=obtenerOCrearSubtema() ?>>
</div>
<div class="col-md-4">
    <label for="sugerenciasubtema" class="form-label">Sugerencias Subtema</label>
    <textarea rows="4" cols="50" class="form-control" id="sugerenciasubtema"  readonly></textarea>
</div>

            </div>
            <div class="mb-3">
                <label for="detalle" class="form-label">Detalle</label>
                <input type="text" id="detalle" name="detalle" class="form-control" value="<?= $gestion['detalle'] ?>">
            </div>
            <div class="mb-3">
                    <label for="origen" class="form-label">Origen</label>
                    <select class="form-select" id="origen" name="origen">
                        <option value="0">Selecciona el origen</option>
                        <option value="1">Redes Sociales</option>
                        <option value="2">Evento campaña</option>
                        <option value="3">Evento gobierno</option>
                        <option value="4">Administracion 2022-2024</option>
                        <option value="5">Administracion 2024-2027</option>
                    </select>
                </div>
            <div class="col-md-4">
                    <label for="status" class="form-label">Estatus</label>
                    <select class="form-select" id="status" name="status">
                        <option value="0">Selecciona el estatus</option>
                        <option value="1">En proceso</option>
                        <option value="2">Completado</option>
                        <option value="3">Cancelado</option>
                        <option value="4">En espera de Beneficiario</option>
                    </select>
                </div>
            <div class="mb-3">
                <label for="estadoNacimiento" class="form-label">Estado</label>
                <select id="estadoNacimiento" name="estadoNacimiento" class="form-select">
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= $estado['id'] ?>" <?= $estado['id'] == $gestion['id_estado'] ? 'selected' : '' ?>>
                            <?= $estado['nombre_estado'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="municipio" class="form-label">Municipio</label>
                <select id="municipio" name="municipio" class="form-select">
                    <?php foreach ($municipios as $municipio): ?>
                        <option value="<?= $municipio['id'] ?>" <?= $municipio['id'] == $gestion['id_municipio'] ? 'selected' : '' ?>>
                            <?= $municipio['nombre_municipio'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="colonia" class="form-label">Colonia</label>
                <select id="colonia" name="colonia" class="form-select">
                    <?php foreach ($colonias as $colonia): ?>
                        <option value="<?= $colonia['id'] ?>" <?= $colonia['id'] == $gestion['id_colonia'] ? 'selected' : '' ?>>
                            <?= $colonia['nombre_colonia'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="seccional" class="form-label">Seccional</label>
                <input type="text" id="seccional" name="seccional" class="form-control" value="0" placeholder="seccional">
            </div>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?= $gestion['nombre'] ?>">
            </div>
            <div class="mb-3">
                <label for="calle" class="form-label">Calle</label>
                <input type="text" id="calle" name="calle" class="form-control" value="<?= $gestion['calle'] ?>">
            </div>
            
            <div class="mb-3">
                <label for="numExterior" class="form-label">Número Exterior</label>
                <input type="text" id="numExterior" name="numExterior" class="form-control" value="<?= $gestion['num_exterior'] ?>">
            </div>
            <div class="mb-3">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" id="celular" name="celular" class="form-control" value="<?= $gestion['celular'] ?>">
            </div>
            <input type="hidden" name="id" value="<?= $gestion['id'] ?>">
            <input type="hidden" name="action" value="editar">
            <button type="button" id="guardarBtn" class="btn btn-primary">Actualizar Gestión</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    const subtemasPorTema = {
        "19": ["ESTUDIO MEDICO", "APARATOS DE MOVILIDAD", "ESPECIALISTA", "MEDICAMENTO", "LENTES"],
        "21": ["APOYO ECONOMICO", "APOYO ALIMENTARIO", "UTILES ESCOLARES", "TRASLADO", "APOYOS FUNERARIOS", "ATENCION CIUDADANA"],
        "22": ["REDUCTORES DE VELOCIDAD", "BORDOS", "SEÑALIZACION", "CONCESIONES"],
        "23": ["LUMINARIA", "RECOLECCION DE BASURA", "PLAZAS Y JARDINES", "LIMPIEZA", "PIPA DE AGUA"],
        "24": ["CONTROL ANIMAL", "ESTERILIZACIONES"],
        "25": ["BACHES", "PAVIMENTACION", "MATERIAL PARA CONSTRUCCION"],
        "27": ["BOMBA DE AGUA", "ARREGLO DE CAMINO"],
        "28": ["PLACAS", "REGISTRO CIVIL", "ESCRITURACION", "PODER JUDICIAL", "EMPOODERAMIENTO DE LA MUJER", "TESTAMENTO"],
        "29": ["EMPLEO TEMPORAL", "LIQUIDACION", "PENSION", "EMPLEO"],
        "30": ["RONDINES", "APOYO VIAL"],
        "31": ["FALTA DE AGUA", "POCA PRESION DE AGUA", "CONTRATO", "DRENAJE", "CONVENIO"],
        "32": ["MATERIAL DEPORTIVO", "PRESTAMO UNIDAD DEPORTIVA", "PREMIACIONES"],
        "33": ["ASESORIA LEGAL", "TESTAMENTO", "DIVORCIO"],
        "34": ["AMBULANCIA"],
        "35": ["MEDICAMENTO", "CONSULTA"],
        "37": ["APOYO PSICOLOGICO", "ASESORIA"],
        "39": ["ASESORIA LEGAL"],
        "40": ["BANDA MUNICIPAL", "ESPACIO CULTURAL"],
        "41": ["ARREGLO FLORAL", "APOYO SONIDO", "APOYO MOBILIARIO"],
        "46": ["TECHO", "PISO", "CUARTO", "BAÑO", "TINACO", "LAMINAS", "PAVIMENTACION", "DRENAJE"]
    };

    const textareasubt = document.getElementById('sugerenciasubtema');
    const temaSelect = document.getElementById('tema');
    const subtemaInput = document.getElementById('subtema');
    
    function cargarSubtemas() {
        const subtemas = subtemasPorTema[temaSelect.value] || [];
        if (subtemas.length > 0) {
            textareasubt.style.display = "block";
            textareasubt.value = subtemas.join("\n");
        } else {
            textareasubt.style.display = "none";
            textareasubt.value = "";
        }
    }

    // Evento de cambio
    $(temaSelect).change(function () {
        cargarSubtemas();
    });

    // Inicializar sugerencias al cargar la página
    cargarSubtemas();

    // Selección de un subtema
    textareasubt.addEventListener('click', (event) => {
        const lines = textareasubt.value.split("\n");
        const lineHeight = parseInt(window.getComputedStyle(textareasubt).lineHeight);
        const clickedLine = Math.floor(event.offsetY / lineHeight);

        if (lines[clickedLine]) {
            subtemaInput.value = lines[clickedLine];
        }
    });

    // Enviar formulario con AJAX
    $('#guardarBtn').on('click', function () {
    var formData = $('#editarForm').serializeArray(); // Serializa los datos del formulario como un array
    var formValues = {};
    formData.forEach(function (item) {
        formValues[item.name] = item.value; // Convierte el array en un objeto clave-valor
    });

    // Validaciones
    var subtema = formValues['subtema'];
    var seccional = parseInt(formValues['seccional'], 10);
    var origen = parseInt(formValues['origen'], 10);
    var estatus = parseInt(formValues['estatus'], 10);

    if (!subtema || !isNaN(subtema)) {
        alert('Por favor escribe un subtema válido.');
        return;
    }

    if (seccional <= 0) {
        alert('Por favor captura un valor válido para "Seccional".');
        return;
    }

    if (origen <= 0) {
        alert('Por favor selecciona un valor válido para "Origen".');
        return;
    }

    if (estatus <= 0) {
        alert('Por favor selecciona un valor válido para "Estatus".');
        return;
    }

    // Solicitud AJAX
    $.ajax({
        type: 'POST',
        url: 'update.php', // Cambia la URL si es necesario
        data: formValues, // Envía los datos como objeto
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                alert('Registro actualizado correctamente.');
                window.location.href = 'gestion.php'; // Redirige a gestion.php
            } else if (response.error) {
                alert(response.error); // Muestra el error recibido del servidor
            }
        },
        error: function (xhr, status, error) {
            console.error('Error en la respuesta:', xhr.responseText);
            alert('Ocurrió un error inesperado. Revisa la consola para más detalles.');
        }
    });
});


        

});

      

</script>
</body>
</html>
