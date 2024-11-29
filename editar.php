<?php
// editar.php

// Habilitar la visualización de errores (deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirigir al login si no está autenticado
    exit();
}

// Conectar a la base de datos
try {
    include 'db.php'; // Asegúrate de que db.php inicializa $pdo como una instancia de PDO
} catch (Exception $e) {
    // Mostrar un mensaje de error amigable
    die('Error de conexión a la base de datos.');
}

// Recuperar el parámetro 'id' de la URL
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
} else {
    // Manejar el caso donde 'id' no está presente
    die('ID de gestión no proporcionado.');
}

// Recuperar los datos actuales de la gestión desde la base de datos
$stmt = $pdo->prepare("
    SELECT g.*, s.nombre_subtema 
    FROM gestiones g
    LEFT JOIN subtemas s ON g.id_subtema = s.id
    WHERE g.id = ?
");
$stmt->execute([$id]);
$gestion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gestion) {  
    die('Gestión no encontrada.');
}


// Recuperar las listas desplegables necesarias (temas, estados, municipios, colonias)
$temas = $pdo->query("SELECT id, nombre_tema FROM temas WHERE id > 26")->fetchAll(PDO::FETCH_ASSOC);
$estados = $pdo->query("SELECT id, nombre_estado FROM estados")->fetchAll(PDO::FETCH_ASSOC);
$municipios = $pdo->query("SELECT id, nombre_municipio FROM municipios")->fetchAll(PDO::FETCH_ASSOC);
$colonias = $pdo->query("SELECT id, nombre_colonia FROM colonias")->fetchAll(PDO::FETCH_ASSOC);

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Gestión</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- jQuery -->
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
            display: block;
        }
        .sidebar a:hover {
            color: #000;
        }
        .content {
            margin-left: 200px; /* Ajustado para el ancho de la sidebar */
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
        <form id="editarForm" accept-charset="UTF-8">

            <input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Campo Tema -->
            <div class="row mb-3">
                <label for="tema" class="form-label">Tema:</label>
                <select id="tema" name="tema" class="form-select">
                    <?php foreach ($temas as $tema): ?>
                        <option value="<?= htmlspecialchars($tema['id'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($gestion['id_tema']) && $gestion['id_tema'] == $tema['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tema['nombre_tema'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo Subtema -->
            <div class="mb-3">
                <label for="subtema" class="form-label">
                    Subtema
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['nombre_subtema'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="text" id="subtema" name="subtema" class="form-control" placeholder="Escribe un nuevo subtema" value="<?= htmlspecialchars($gestion['nombre_subtema'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <!-- Sugerencias Subtema -->
            <div class="mb-3">
                <label id="labelsub"  for="sugerenciasubtema" class="form-label">Sugerencias Subtema</label>
                <textarea rows="4" cols="50" class="form-control" id="sugerenciasubtema" name="sugerenciasubtema" readonly></textarea>
            </div>

            <!-- Campo Detalle -->
            <div class="mb-3">
                <label for="detalle" class="form-label">
                    Detalle
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['detalle'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="text" id="detalle" name="detalle" class="form-control" value="<?= htmlspecialchars($gestion['detalle'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Origen -->
            <div class="mb-3">
                <label for="origen" class="form-label">
                    Origen
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['origen'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <select id="origen" name="origen" class="form-select">
                    <option value="1" <?= isset($gestion['origen']) && $gestion['origen'] == 1 ? 'selected' : '' ?>>Redes Sociales</option>
                    <option value="2" <?= isset($gestion['origen']) && $gestion['origen'] == 2 ? 'selected' : '' ?>>Evento campaña</option>
                    <option value="3" <?= isset($gestion['origen']) && $gestion['origen'] == 3 ? 'selected' : '' ?>>Evento gobierno</option>
                    <option value="4" <?= isset($gestion['origen']) && $gestion['origen'] == 4 ? 'selected' : '' ?>>Administración 2022-2024</option>
                    <option value="5" <?= isset($gestion['origen']) && $gestion['origen'] == 5 ? 'selected' : '' ?>>Administración 2024-2027</option>
                </select>
            </div>

            <!-- Campo Estado -->
            <div class="mb-3">
                <label for="estadoNacimiento" class="form-label">Estado</label>
                <select id="estadoNacimiento" name="estadoNacimiento" class="form-select">
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= htmlspecialchars($estado['id'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($gestion['id_estado']) && $gestion['id_estado'] == $estado['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($estado['nombre_estado'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo Municipio -->
            <div class="mb-3">
                <label for="municipio" class="form-label">Municipio</label>
                <select id="municipio" name="municipio" class="form-select">
                    <?php foreach ($municipios as $municipio): ?>
                        <option value="<?= htmlspecialchars($municipio['id'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($gestion['id_municipio']) && $gestion['id_municipio'] == $municipio['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($municipio['nombre_municipio'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo Colonia -->
            <div class="mb-3">
                <label for="colonia" class="form-label">
                    Colonia
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['nombre_colonia'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <select id="colonia" name="colonia" class="form-select">
                    <option value="">Selecciona una colonia</option>
                    <?php foreach ($colonias as $colonia): ?>
                        <option value="<?= htmlspecialchars($colonia['id'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($gestion['id_colonia']) && $gestion['id_colonia'] == $colonia['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($colonia['nombre_colonia'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo Seccional -->
            <div class="mb-3">
                <label for="seccional" class="form-label">
                    Seccional
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= ($gestion['seccional'] ?? 'No asignado') ?>
                    </span>
                </label>
                <input type="text" id="seccional" name="seccional" class="form-control" value="<?= htmlspecialchars($gestion['seccional'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Nombre -->
            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['nombre'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($gestion['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Calle -->
            <div class="mb-3">
                <label for="calle" class="form-label">
                    Calle
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['calle'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="text" id="calle" name="calle" class="form-control" value="<?= htmlspecialchars($gestion['calle'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Número Exterior -->
            <div class="mb-3">
                <label for="numExterior" class="form-label">
                    Número Exterior
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['num_exterior'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="number" id="numExterior" name="numExterior" class="form-control" value="<?= htmlspecialchars($gestion['num_exterior'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Celular -->
            <div class="mb-3">
                <label for="celular" class="form-label">
                    Celular
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['celular'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <input type="text" id="celular" name="celular" class="form-control" value="<?= htmlspecialchars($gestion['celular'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Campo Estatus -->
            <div class="mb-3">
                <label for="status" class="form-label">
                    Estatus
                    <span class="badge bg-info text-dark ms-2">
                        Actual: <?= htmlspecialchars($gestion['estatus'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </label>
                <select class="form-select" id="status" name="status">
                    <option value="1" <?= isset($gestion['estatus']) && $gestion['estatus'] == 1 ? 'selected' : '' ?>>En proceso</option>
                    <option value="2" <?= isset($gestion['estatus']) && $gestion['estatus'] == 2 ? 'selected' : '' ?>>Completado</option>
                    <option value="3" <?= isset($gestion['estatus']) && $gestion['estatus'] == 3 ? 'selected' : '' ?>>Cancelado</option>
                    <option value="4" <?= isset($gestion['estatus']) && $gestion['estatus'] == 4 ? 'selected' : '' ?>>En espera de Beneficiario</option>
                </select>
            </div>

            <!-- Botón Guardar -->
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
        "28": ["PLACAS", "REGISTRO CIVIL", "ESCRITURACION", "PODER JUDICIAL", "EMPODERAMIENTO DE LA MUJER", "TESTAMENTO"],
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
    const estatusSelect = $('#status');
    
    const textareasubtlbl = document.getElementById('labelsub');

    function cargarSubtemas() {
        if (!temaSelect) {
            console.warn("#tema select not found.");
            return;
        }

        const subtemas = subtemasPorTema[temaSelect.value] || [];
        if (subtemas.length > 0) {
            textareasubt.style.display = "block";
            textareasubt.value = subtemas.join("\n");
        } else {
            textareasubt.style.display = "none";
            textareasubtlbl.style.display = "none";
            textareasubt.value = "";
        }
    }

    // Evento de cambio en el select de tema
    $(temaSelect).on('change', function () {
        cargarSubtemas();
    });

    // Inicializar sugerencias al cargar la página
    cargarSubtemas();

    // Selección de un subtema desde las sugerencias
    textareasubt.addEventListener('click', (event) => {
        const lines = textareasubt.value.split("\n");
        const lineHeight = parseInt(window.getComputedStyle(textareasubt).lineHeight, 10) || 20; // Fallback
        const clickedLine = Math.floor(event.offsetY / lineHeight);

        if (lines[clickedLine]) {
            subtemaInput.value = lines[clickedLine];

            // Limpiar el textarea después de seleccionar un subtema
            textareasubt.value = '';

            // Ocultar el textarea después de seleccionar
            textareasubt.style.display = 'none';
            textareasubtlbl.style.display = 'none';
        }
    });

    $('#guardarBtn').on('click', function () {
        // Serialize form data
        let formData = $('#editarForm').serializeArray();
        let formValues = {};

        // Convertir el array en un objeto clave-valor
        formData.forEach(function (item) {
            formValues[item.name] = item.value;
        });

        let isValid = true;

        // Limpiar estados previos de validación
        $('.form-select, .form-control').removeClass('is-invalid');

        // Validar Tema
        if (!temaSelect.value) {
            $(temaSelect).addClass('is-invalid');
            isValid = false;
        }

        // Validar Subtema
        if (!subtemaInput.value.trim()) {
            $(subtemaInput).addClass('is-invalid');
            isValid = false;
        }

        // Validar Estatus
        if (!estatusSelect.val()) {
            $(estatusSelect).addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            alert("Por favor, completa los campos obligatorios.");
            return;
        }

        // Validaciones adicionales (opcional)
        if (!formValues.subtema || formValues.subtema.trim() === "") {
            alert("Por favor, ingresa un subtema válido.");
            return;
        }

        // Excluir campos no deseados
        var camposNoDeseados = ['sugerenciasubtema'];
        var datosEnviar = {};
        formData.forEach(function (item) {
            if (!camposNoDeseados.includes(item.name)) {
                datosEnviar[item.name] = item.value;
            }
        });

        // Enviar datos vía AJAX
        $.ajax({
            type: 'POST',
            url: 'update.php', // Asegúrate de que esta ruta es correcta
            data: datosEnviar,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Registro actualizado correctamente.');
                    window.location.href = 'gestion.php'; // Redirige
                } else if (response.error) {
                    alert(response.error);
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
