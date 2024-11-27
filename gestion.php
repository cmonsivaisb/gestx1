
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

// Connect to the database with error handling
try {
    include 'db.php';
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}


if (isset($_POST['action']) && $_POST['action'] == 'verificar') {
    $nombre = trim($_POST['nombre'] ?? '');

    if (!empty($nombre)) {
        $stmt = $pdo->prepare("SELECT id FROM gestiones WHERE TRIM(nombre) = ?");
        $stmt->execute([$nombre]);
        $gestion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($gestion) {
            echo json_encode(['exists' => true, 'idgestion' => $gestion['id']]);
        } else {
            echo json_encode(['exists' => false]);
        }
    } else {
        echo json_encode(['exists' => false, 'error' => 'El campo nombre está vacío o no es válido.']);
    }
    exit();
}


// Fetch latest records
function obtenerUltimasGestiones($pdo) {
    $stmt = $pdo->query("SELECT g.id AS idgestion, g.origen, g.nombre, g.num_exterior, g.calle, g.estatus, g.detalle, g.celular, g.fecha, 
                                t.nombre_tema, m.nombre_municipio, c.nombre_colonia, e.nombre_estado, r.nombre_responsable, s.nombre_subtema, g.seccional
                         FROM gestiones g
                         INNER JOIN temas t ON g.id_tema = t.id
                         INNER JOIN subtemas s ON g.id_subtema = s.id
                         INNER JOIN estados e ON g.id_estado = e.id
                         INNER JOIN municipios m ON g.id_municipio = m.id
                         INNER JOIN colonias c ON g.id_colonia = c.id
                         INNER JOIN responsables r ON g.id_responsable = r.id_responsable
                         WHERE g.id > 22
                         ORDER BY g.fecha DESC
                         LIMIT 40");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
          nombre_tema,
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

// Fetch or create subtopic
function obtenerOCrearSubtema($nombre_subtema, $pdo) {
    if (empty($nombre_subtema)) {
        return 1; // Return null if responsible person name is empty
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

// Fetch or create responsible person
function obtenerOCrearResponsable($nombre_responsable, $pdo) {
    if (empty($nombre_responsable)) {
        return 1; // Return null if responsible person name is empty
    }

    $stmt = $pdo->prepare("SELECT id_responsable FROM responsables WHERE nombre_responsable = ?");
    $stmt->execute([$nombre_responsable]);
    $responsable = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($responsable) {
        return $responsable['id_responsable'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO responsables (nombre_responsable) VALUES (?)");
        $stmt->execute([$nombre_responsable]);
        return $pdo->lastInsertId();
    }
}

function obtenerColonias($pdo) {
    $stmt = $pdo->query("SELECT id, nombre_colonia FROM colonias");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtain topics for the dropdown
$temas = obtenerTemas($pdo);
$colonias = obtenerColonias($pdo);
$ultimas_gestiones = obtenerUltimasGestiones($pdo);

if (isset($_POST['action']) && $_POST['action'] == 'guardar') {
    // Sanitización de datos
    $detalle = trim($_POST['detalle'] ?? '');
    $origen = $_POST['origen'] ?? 1;
    $id_estado = $_POST['estadoNacimiento'] ?? 5;
    $id_municipio = $_POST['municipio'] ?? 34;
    $id_colonia = $_POST['colonia'] ?? 1;
    $num_exterior = trim($_POST['numExterior'] ?? '');
    $seccional = trim($_POST['seccional'] ?? ''); // Permitir valores vacíos si es opcional
    $nombre = trim($_POST['nombre'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $id_usuario = $_SESSION['usuario_id'];
    $id_tema = $_POST['tema'] ?? 1;
    $estatus = $_POST['status'] ?? 1;
    $fecha = $_POST['fecha'] ?? date('Y-m-d H:i:s');
    $subtema = trim($_POST['subtema'] ?? '');
    $responsable_name = trim($_POST['responsable'] ?? '');

    // Validación en el backend
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'El campo "nombre" es obligatorio.']);
        exit();
    }

    // Verificar si el nombre ya existe (opcional aquí)
    $stmt = $pdo->prepare("SELECT id FROM gestiones WHERE TRIM(nombre) = ?");
    $stmt->execute([$nombre]);
    $gestion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gestion) {
        echo json_encode(['success' => false, 'error' => 'Ya existe una gestión con este nombre.']);
        exit();
    }

    // Insertar en la base de datos
    try {
        $stmt = $pdo->prepare("INSERT INTO gestiones (id_tema, id_subtema, detalle, origen, id_estado, id_municipio, id_colonia, 
                               num_exterior, celular, seccional, id_usuario, estatus, nombre, calle, id_responsable, fecha) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_tema, $id_subtema, $detalle, $origen, $id_estado, $id_municipio, $id_colonia, $num_exterior, 
                        $celular, $seccional, $id_usuario, $estatus, $nombre, $calle, $id_responsable, $fecha]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar la gestión: ' . $e->getMessage()]);
    }
    exit();
}



if (isset($_POST['action']) &&$_POST['action'] == 'buscar') {
    $tema = $_POST['tema'] ?? '';
    $subtema = $_POST['subtema'] ?? '';
  
    $origen = $_POST['origen'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $municipio = $_POST['municipio'] ?? '';
    $colonia = $_POST['colonia'] ?? 0;
    $numExterior = $_POST['numExterior'] ?? 0;
        $seccional = $_POST['seccional'] ?? 0;
    $celular = $_POST['celular'] ?? 0;
$nombre = $_POST['nombre'] ?? '';
$calle = $_POST['calle'] ?? '';
$estatus = $_POST['estatus'] ?? '';




    $query = "SELECT g.id AS idgestion, g.origen,g.nombre ,g.num_exterior,g.calle,g.estatus,g.detalle,g.celular,g.fecha, t.nombre_tema , m.nombre_municipio, c.nombre_colonia,e.nombre_estado, r.nombre_responsable,s.nombre_subtema,g.seccional
                     FROM gestiones g
                     INNER JOIN temas t ON g.id_tema = t.id
                     INNER JOIN subtemas s ON g.id_subtema = s.id
                     INNER JOIN estados e ON g.id_estado = e.id
                     INNER JOIN municipios m ON g.id_municipio = m.id
                     INNER JOIN colonias c ON g.id_colonia = c.id
                     INNER JOIN responsables r on g.id_responsable=r.id_responsable
            WHERE 1=1";

    $params = [];

    if ($tema) {
        $query .= " AND t.nombre_tema LIKE ?";
        $params[] = "%$tema%";
    }
    if ($subtema) {
        $query .= " AND s.nombre_subtema LIKE ?";
        $params[] = "%$subtema%";
    }
    if ($origen) {
        $query .= " AND g.origen = ?";
        $params[] = $origen;
    }
    if ($estado) {
        $query .= " AND g.id_estado = ?";
        $params[] = $estado;
    }
    if ($municipio) {
        $query .= " AND g.id_municipio = ?";
        $params[] = $municipio;
    }
    if ($colonia) {
        $query .= " AND g.id_colonia = ?";
        $params[] = $colonia;
    }
    if ($numExterior) {
        $query .= " AND g.num_exterior = ?";
        $params[] = $numExterior;
    }
     if ($estatus) {
        $query .= " AND g.estatus = ?";
        $params[] = $estatus;
    }
     if ($seccional) {
        $query .= " AND g.seccional = ?";
        $params[] = $seccional;
    }
    
      if ($nombre) {
        $query .= " AND g.nombre LIKE ?";
        $params[] = "%$nombre%";
    }
    if ($calle) {
        $query .= " AND g.calle LIKE ?";
        $params[] = "%$calle%";
    }
    if ($celular) {
        $query .= " AND g.celular LIKE ?";
        $params[] = "%$celular%";
        
    }
     
$query .= " ORDER BY fecha  DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $gestiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($gestiones);
    exit();

}


if (isset($_POST['action']) && $_POST['action'] == 'eliminar') {
    $idgestion = $_POST['idgestion'] ?? null;

    if ($idgestion) {
        try {
            $stmt = $pdo->prepare("DELETE FROM gestiones WHERE id = ?");
            $stmt->execute([$idgestion]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'El registro no fue encontrado.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
    }
    exit();
}




?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script>
function confirmDelete(idgestion) {
    if (confirm('¿Estás seguro de que deseas eliminar este registro? Esta acción es irreversible.')) {
        $.ajax({
            type: 'POST',
            url: 'gestion.php',
            data: {
                action: 'eliminar',
                idgestion: idgestion
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Registro eliminado correctamente.');
                    $('#buscarBtn').click(); // Recarga los resultados
                        // Redirigir a gestion.php después de la eliminación
                        window.location.href = 'gestion.php';
                } else {
                    alert('Error al eliminar: ' + response.message);
                }
            },
            error: function() {
                alert('Ocurrió un error inesperado al procesar la solicitud.');
            }
        });
    }
}

    </script>
    
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
        <li><a href="#gestionForm">Captura</a></li>
        <li><a href="#filtroForm">Consulta</a></li>
    </ul>
    <a href="logout.php">CERRAR SESION</a>
</div>
<div class="content">
<div class="card mt-5">
    <div class="card-body">
        <h5 class="card-title">Generar gestiones</h5>
        <form id="gestionForm" method="POST">
            <div class="row mb-3">
                <label for="tema">Tema:</label>
                <select id="tema" name="tema" class="form-select">
                    <option value="">Selecciona un tema</option>
                    <?php foreach ($temas as $tema): ?>
                        <option value="<?= $tema['id'] ?>"><?= $tema['nombre_tema'] ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="col-md-4">
                    <label for="subtema" class="form-label">Subtema</label>
                    <input type="text" class="form-control" id="subtema" name="subtema" placeholder="Subtema" placeholder="subtema" >
                </div>
                <div class="col-md-4">
                    <label for="sugerenciasubtema" class="form-label" id="labelsubtemasugerencia">Sugerencias Subtema</label>
                    <textarea rows="4" cols="50" class="form-control" id="sugerenciasubtema" name="sugerenciasubtema" readonly></textarea>
                </div>

                <div class="col-md-4">
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
                    <label for="responsable">Responsable:</label>
                    <input type="text" class="form-control" id="responsable" name="responsable" required>
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
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="estadoNacimiento" class="form-label">Estado</label>
                    <select class="form-select" id="estadoNacimiento" name="estadoNacimiento">
                        <option value="5">Coahuila de Zaragoza</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="municipio" class="form-label">Municipio</label>
                    <select class="form-select" id="municipio" name="municipio">
                        <option value="24">Parras</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="colonia" class="form-label">Colonia</label>
                    <select class="form-select" id="colonia" name="colonia">
                        <?php foreach ($colonias as $colonia): ?>
                            <option value="<?= $colonia['id'] ?>"><?= $colonia['nombre_colonia'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="seccional" class="form-label">Seccional</label>
                    <input type="text" class="form-control" id="seccional" name="seccional" placeholder="Seccional">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre">
                </div>
                <div class="col-md-2">
                    <label for="calle" class="form-label">Calle</label>
                    <input type="text" class="form-control" id="calle" name="calle" placeholder="Calle">
                </div>
                <div class="col-md-2">
                    <label for="numExterior" class="form-label">No. Exterior</label>
                    <input type="text" class="form-control" id="numExterior" name="numExterior" placeholder="Número exterior">
                </div>
                <div class="col-md-2">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="text" class="form-control" id="celular" name="celular" placeholder="Celular">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="detalle" class="form-label">Detalle</label>
                    <input type="text" class="form-control" id="detalle" name="detalle" placeholder="Detalle">
                </div>
            </div>

            <div class="col-md-4">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" class="form-control" id="fecha" name="fecha">
            </div>

            <div class="text-end">
                <button type="button" id="guardarBtn" class="btn btn-primary">Guardar Gestión</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-5">
    <div class="card-body">
        <h5 class="card-title">Consultar Gestiones</h5>
        <form id="filtroForm">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre">
            <button type="button" id="buscarBtn" class="btn btn-primary">Buscar</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>EDITAR</th>
                        <th>ElIMINAR</th>

                        <th>Folio</th>
                        <th>Nombre</th>
                        <th>Fecha</th>
                        <th>Tema</th>
                        <th>Subtema</th>
                        <th>Responsable</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th>Municipio</th>
                        <th>Colonia</th>
                        <th>Seccional</th>
                        <th>Calle</th>
                        <th>Número Exterior</th>
                        <th>Celular</th>
                        <th>Estatus</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="resultadosTable">
    <?php foreach ($ultimas_gestiones as $gestion): ?>
        <tr>
            <td><a href="update.php?id=<?= htmlspecialchars($gestion['idgestion']) ?>" class="btn btn-sm btn-primary">Editar</a></td>
            <td><button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $gestion['idgestion'] ?>)">Eliminar</button></td>
            <td><?= htmlspecialchars($gestion['idgestion']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre']) ?></td>
            <td><?= htmlspecialchars($gestion['fecha']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_tema']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_subtema']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_responsable']) ?></td>
            <td><?= htmlspecialchars($gestion['origen']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_estado']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_municipio']) ?></td>
            <td><?= htmlspecialchars($gestion['nombre_colonia']) ?></td>
            <td><?= htmlspecialchars($gestion['seccional']) ?></td>
            <td><?= htmlspecialchars($gestion['calle']) ?></td>
            <td><?= htmlspecialchars($gestion['num_exterior']) ?></td>
            <td><?= htmlspecialchars($gestion['celular']) ?></td>
            <td><?= htmlspecialchars($gestion['estatus']) ?></td>
            <td><?= htmlspecialchars($gestion['detalle']) ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const textareasubt = document.getElementById('sugerenciasubtema');
    const temaSelect = document.getElementById('tema');
    const labelsubtemasugerencia = document.getElementById('labelsubtemasugerencia');
    // Change event for the Tema select
    $(temaSelect).change(function() {
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

        const subtemas = subtemasPorTema[temaSelect.value] || [];
        if (subtemas.length > 0) {
            textareasubt.style.display = "block";
            textareasubt.value = subtemas.join("\n");
        } else {
            textareasubt.style.display = "none";
            textareasubt.value = "";
        }
    });

    // Click event for the textarea suggestions
    textareasubt.addEventListener('click', (event) => {
        const lines = textareasubt.value.split("\n");
        const lineHeight = parseInt(window.getComputedStyle(textareasubt).lineHeight);
        const clickedLine = Math.floor(event.offsetY / lineHeight);

        if (lines[clickedLine]) {
            document.getElementById('subtema').value = lines[clickedLine];
            textareasubt.style.display = "none";
            labelsubtemasugerencia.style.display = "none";
        }
    });

    // Save button click event
    $('#guardarBtn').on('click', function () {
    var formData = $('#gestionForm').serializeArray();
    var formValues = {};

    // Procesar datos del formulario
    formData.forEach(function (item) {
        formValues[item.name] = item.value.trim(); // Eliminar espacios en blanco y tabuladores
    });

    // Validar campos vacíos o importantes
    if (!formValues['nombre'] || formValues['nombre'].length < 3) {
        alert('El nombre es obligatorio y debe tener al menos 3 caracteres.');
        return;
    }

    if (!formValues['calle'] || !formValues['numExterior']) {
        alert('La calle y el número exterior son obligatorios.');
        return;
    }

    // Validar si existe una gestión con el mismo nombre
    $.ajax({
        type: 'POST',
        url: 'gestion.php',
        data: {
            action: 'verificar',
            nombre: formValues['nombre']
        },
        dataType: 'json',
        success: function (response) {
            if (response.exists) {
                // Alerta al usuario y muestra un enlace para editar
                if (confirm('Ya existe una gestión con el mismo nombre. ¿Deseas editarla en su lugar?')) {
                    window.location.href = 'update.php?id=' + response.idgestion;
                }
            } else {
                // Proceder con el guardado si no existe
                formValues['action'] = 'guardar';
                $.ajax({
                    type: 'POST',
                    url: 'gestion.php',
                    data: formValues,
                    dataType: 'json',
                    success: function (saveResponse) {
                        if (saveResponse.success) {
                            alert('Gestión registrada correctamente.');
                            window.location.href = 'gestion.php';
                        } else {
                            alert('Error al guardar la gestión: ' + saveResponse.error);
                        }
                    },
                    error: function () {
                        alert('Error inesperado al guardar la gestión.');
                    }
                });
            }
        },
        error: function () {
            alert('Error al verificar la existencia de la gestión.');
        }
    });
});



    // Search button click event
    $('#buscarBtn').on('click', function() {
        var formData = $('#filtroForm').serialize();
        formData += '&action=buscar';

        $.ajax({
            type: 'POST',
            url: 'gestion.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                var resultadosHtml = '';
                $.each(response, function(key, value) {
                    resultadosHtml += '<tr>' +
            '<td><a href="update.php?id=' + value.idgestion + '" class="btn btn-sm btn-primary">Editar</a></td>' +
            '<td><button class="btn btn-sm btn-danger" onclick="confirmDelete(' + value.idgestion + ')">Eliminar</button></td>' +
            '<td>' + value.idgestion + '</td>' +
            '<td>' + value.nombre + '</td>' +
            '<td>' + value.fecha + '</td>' +
            '<td>' + value.nombre_tema + '</td>' +
            '<td>' + value.nombre_subtema + '</td>' +
            '<td>' + value.nombre_responsable + '</td>' +
            '<td>' + value.origen + '</td>' +
            '<td>' + value.nombre_estado + '</td>' +
            '<td>' + value.nombre_municipio + '</td>' +
            '<td>' + value.nombre_colonia + '</td>' +
            '<td>' + value.seccional + '</td>' +
            '<td>' + value.calle + '</td>' +
            '<td>' + value.num_exterior + '</td>' +
            '<td>' + value.celular + '</td>' +
            '<td>' + value.estatus + '</td>' +
            '<td>' + value.detalle + '</td>' +
        '</tr>';
    });
    $('#resultadosTable').html(resultadosHtml);
            }
        });
    });
});
</script>
</body>
</html>
