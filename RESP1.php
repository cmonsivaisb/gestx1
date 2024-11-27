<?php
// Iniciar sesión
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirigir si no está autenticado
    exit();
}

// Conectar a la base de datos
include 'db.php';







// Procesar la búsqueda de gestiones con filtros
if (isset($_POST['action']) && $_POST['action'] == 'buscar') {

    $tema = $_POST['tema'] ?? '';
   $subtema = $_POST['subtema'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $municipio = $_POST['municipio'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $numExterior = $_POST['numExterior'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $responsable = $_POST['responsable'] ?? '';
   

    $query = "SELECT g.*, e.nombre_estado, m.nombre_municipio, c.nombre_colonia, t.nombre_tema,g.nombre, g.calle, r.nombre_responsable,s.nombre_subtema
              FROM gestiones g
              INNER JOIN estados e ON g.id_estado = e.id
              INNER JOIN municipios m ON g.id_municipio = m.id
              INNER JOIN colonias c ON g.id_colonia = c.id
              INNER JOIN temas t ON g.id_tema = t.id
              INNER JOIN subtemas s ON g.id_subtema = s.id
              INNER JOIN responsables r ON g.id_responsable=r.id_responsable
              WHERE 1=1
             ";

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
        $query .= " AND g.num_exterior LIKE ?";
        $params[] = "%$numExterior%";
    }
    if ($celular) {
        $query .= " AND g.celular LIKE ?";
        $params[] = "%$celular%";
    }
      if ($responsable) {
        $query .= " AND r.nombre_responsable LIKE ?";
        $params[] = "%$responsable%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $gestiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($gestiones);
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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Sidebar general */
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

        /* Estilo para los enlaces del sidebar */
        .sidebar a {
            display: block;
           
            text-decoration: none;
            color: inherit; /* Hereda el color del padre */
            font-weight: bold;
            margin-bottom: 20px;
        }

        .sidebar a:hover {
            color: #000;
        }

        /* Contenido general */
        .content {
            margin-left: 240px;
            padding: 20px;
        }

        /* Estilos responsivos */
        @media (max-width: 768px) {
            .sidebar {
                width: 120px;
            }

            .content {
                margin-left: 180px;
            }

            /* Formulario en una sola columna en pantallas pequeñas */
            .row.mb-3 {
                display: flex;
                flex-direction: column;
            }

            .row.mb-3 .col-md-4,
            .row.mb-3 .col-md-2 {
                width: 100%;
                margin-bottom: 10px;
            }

            /* Ajustar tabla */
            .table-responsive {
                overflow-x: auto;
            }

            /* Botones más grandes para pantallas pequeñas */
            .btn {
                padding: 10px 20px;
                font-size: 16px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 120px;
            }

            .content {
                margin-left: 120px;
            }

            /* Ajuste en la tabla */
            table.table {
                font-size: 12px;
            }

            table.table th, table.table td {
                padding: 5px;
            }

            /* Links del sidebar más pequeños en pantallas muy pequeñas */
            .sidebar a {
                font-size: 14px;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>

</head>
<body>

<div class="sidebar">
 <div class="text-center">
        <img src="logo.png" alt="Logo" class="img-fluid" style="max-width: 100%; height: auto;">
    </div>
    <h4 class="text-center">MENU</h4>
    <a href="dashboard.php">DASHBOARD</a>
    <a href="gestion.php">GESTIONES</a>
    <a href="logout.php">CERRAR SESIÓN</a>
</div>

<div class="content">
    <!-- Formulario de búsqueda de reportes -->
    <div class="mt-5">
        <h5 class="mb-4">Generar Reportes</h5>
        <form id="filtroForm">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="buscarTema" class="form-label">Tema</label>
                    <input type="text" class="form-control" id="buscarTema" name="tema">
                </div>
                  <div class="col-md-4">
                    <label for="subtema" class="form-label">Subtema</label>
                    <input type="text" class="form-control" id="subtema" name="subtema">
                </div>
            
                <div class="col-md-4">
                    <label for="origen" class="form-label">Origen</label>
                    <input type="text" class="form-control" id="origen" name="origen">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" class="form-control" id="estado" name="estado">
                </div>
                <div class="col-md-4">
                    <label for="municipio" class="form-label">Municipio</label>
                    <input type="text" class="form-control" id="municipio" name="municipio">
                </div>
                <div class="col-md-4">
                    <label for="colonia" class="form-label">Colonia</label>
                    <input type="text" class="form-control" id="colonia" name="colonia">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="numExterior" class="form-label">Número Exterior</label>
                    <input type="text" class="form-control" id="numExterior" name="numExterior">
                </div>
                <div class="col-md-4">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="text" class="form-control" id="celular" name="celular">
                </div>
                 <div class="col-md-4">
                    <label for="responsable" class="form-label">Responsable</label>
                    <input type="text" class="form-control" id="responsable" name="responsable">
                </div>
            </div>
            <div class="text-end">
               
                        <button type="button" id="exportarExcelBtn" class="btn btn-success">Exportar a Excel</button>
                         <button type="button" id="buscarBtn" class="btn btn-primary">Buscar</button>
            </div>
        </form>
    </div>

    <!-- Resultados de la búsqueda -->
    <div class="table-responsive mt-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tema</th>
                    <th>Subtema</th>
                    <th>Origen</th>
                    <th>Estado</th>
                    <th>Municipio</th>
                    <th>Colonia</th>
                    <th>Número Exterior</th>
                    <th>Nombre</th>
                    <th>Calle</th>
                    <th>Celular</th>
                    <th>Fecha</th>
                    <th>Estatus</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody id="resultadosBusqueda">
                <!-- Los resultados de la búsqueda se insertarán aquí -->
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts de búsqueda AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>


    $(document).ready(function() {
         let resultados = [];
        // Búsqueda AJAX
        $('#buscarBtn').on('click', function() {
            var formData = $('#filtroForm').serialize();
            formData += '&action=buscar';

            $.ajax({
                type: 'POST',
                url: 'dashboard.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    var resultadosHtml = '';
                    resultados = response; // Guardar los resultados para la exportación a Excel

                    if (response.length > 0) {
                        $.each(response, function(key, value) {
                            resultadosHtml += '<tr>' +
                                '<td>' + value.id + '</td>' +
                                '<td>' + value.nombre_tema + '</td>' +
                                 '<td>' + value.nombre_subtema + '</td>' +
                                '<td>' + value.origen + '</td>' +
                                '<td>' + value.nombre_estado + '</td>' +
                                '<td>' + value.nombre_responsable + '</td>' +
                                '<td>' + value.nombre_municipio + '</td>' +
                                '<td>' + value.nombre_colonia + '</td>' +
                                '<td>' + value.num_exterior + '</td>' +
                                '<td>' + value.nombre + '</td>' +
                                '<td>' + value.calle + '</td>' +
                                '<td>' + value.celular + '</td>' +
                                '<td>' + value.fecha + '</td>' +
                                '<td>' + value.estatus + '</td>' +
                                '<td>' + value.detalle + '</td>' +
                            '</tr>';
                        });
                    } else {
                        resultadosHtml = '<tr><td colspan="12" class="text-center">No se encontraron resultados</td></tr>';
                    }
                    $('#resultadosBusqueda').html(resultadosHtml);
                }
            });
        });

  
          // Exportar a Excel con los resultados actuales
        $('#exportarExcelBtn').on('click', function() {
            if (resultados.length === 0) {
                alert('No hay datos para exportar.');
                return;
            }

            // Crear los encabezados
            var datos = [
                ["ID", "Tema", "Subtema", "Origen", "Estado","Responsable", "Municipio", "Colonia", "Número Exterior", "Celular", "Fecha", "Estatus", "Detalle"]
            ];

            // Iterar sobre los resultados y añadirlos al array 'datos'
            resultados.forEach(function(gestion) {
                datos.push([
                    gestion.id,
                    gestion.nombre_tema,
                  
                    gestion.origen,
                    gestion.nombre_estado,
                    gestion.nombre_responsable,
                    gestion.nombre_municipio,
                    gestion.nombre_colonia,
                    gestion.num_exterior,
                    gestion.celular,
                    gestion.fecha,
                    gestion.estatus,
                    gestion.detalle
                ]);
            });

            // Crear una nueva hoja de cálculo
            var ws = XLSX.utils.aoa_to_sheet(datos);

            // Crear un nuevo libro de Excel
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Reporte");

            // Exportar y descargar el archivo Excel
            XLSX.writeFile(wb, "reporte_gestiones.xlsx");
        });
    });
</script>
</body>
</html>


<?php
// Iniciar sesión
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirigir si no está autenticado
    exit();
}

// Conectar a la base de datos
include 'db.php';


function obtenerUltimasGestiones($pdo) {
    $stmt = $pdo->query("SELECT g.id AS idgestion, g.origen,g.nombre,g.num_exterior,g.calle,g.estatus,g.detalle,g.celular,g.fecha, t.nombre_tema , m.nombre_municipio, c.nombre_colonia,e.nombre_estado
                         FROM gestiones g
                         INNER JOIN temas t ON g.id_tema = t.id
                         INNER JOIN subtemas s ON g.id_subtema = s.id
                         INNER JOIN estados e ON g.id_estado = e.id
                         INNER JOIN municipios m ON g.id_municipio = m.id
                         INNER JOIN colonias c ON g.id_colonia = c.id
                         WHERE g.id>28
                         ORDER BY g.fecha DESC
                         LIMIT 20");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$ultimas_gestiones = obtenerUltimasGestiones($pdo);

// Función para obtener todos los temas ojo son los mayores a 16 ya que los primeros 15 son pruebas
function obtenerTemas($pdo) {
    $stmt = $pdo->query("SELECT temas.id, temas.nombre_tema FROM temas where id>16 ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function obtenersubTemas($pdo) {
    $stmt = $pdo->query("SELECT subtemas.id, subtemas.nombre_subtema FROM subtemas where id>26 ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function obtenerResponsables($pdo) {
    $stmt = $pdo->query("SELECT id_responsable, nombre_responsable FROM responsables ORDER BY nombre_responsable ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Llama a la función y almacena los responsables


if (isset($_POST['action']) && $_POST['action'] == 'getSubtemas') {
    $tema_id = $_POST['tema_id'];
    $stmt = $pdo->prepare("SELECT subtemas.id,nombre_subtema FROM subtemas WHERE id_tema = ? ORDER BY nombre_subtema ASC");
    $stmt->execute([$tema_id]);
    $subtemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($subtemas);
    exit();
}


function obtenerOCrearSubtema($pdo, $nombre_subtema, $tema_id) {
    // Verificar si el subtema ya existe para el tema seleccionado
    $stmt = $pdo->prepare("SELECT nombre_subtema FROM subtemas WHERE nombre_subtema = ? AND id_tema = ?");
    $stmt->execute([$nombre_subtema, $tema_id]);
    $subtema = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subtema) {
        return $subtema['nombre_subtema'];  // Si ya existe, devolver su ID
    } 
}


// Obtener los temas para el selector
$temas = obtenerTemas($pdo);
$subtemas = obtenersubTemas($pdo);
$responsables = obtenerResponsables($pdo);
// Si el formulario es enviado, procesar y guardar la gestión
// Procesar la solicitud AJAX para obtener municipios o colonias


if (isset($_POST['action'])) {
    if ($_POST['action'] == 'guardar') {
  
    $detalle = $_POST['detalle'];
    $origen = $_POST['origen'];
    $id_estado = $_POST['estadoNacimiento'];
    $id_municipio = $_POST['municipio'];
    $id_colonia = $_POST['colonia'];
    $num_exterior = $_POST['numExterior'];

    $nombre = $_POST['nombre'];
    $calle = $_POST['calle'];
    $celular = $_POST['celular'];
    $id_usuario = $_SESSION['usuario_id']; // Usar el ID del usuario autenticado

    // Obtener o crear el tema
    $id_tema =$_POST['tema'];
    $id_subtema =$_POST['subtema'];
    $id_responsable=$_POST['responsable'];

    // Obtener o crear el subtema


   $status= $_POST['status'];
    // Insertar la gestión en la base de datos
    $stmt = $pdo->prepare("INSERT INTO gestiones (id_tema, id_subtema, detalle, origen, id_estado, id_municipio, id_colonia, num_exterior, celular, id_usuario,estatus,nombre,calle,id_responsable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?)");
    $stmt->execute([$id_tema, $id_subtema, $detalle, $origen, $id_estado, $id_municipio, $id_colonia, $num_exterior, $celular, $id_usuario,$status,$nombre,$calle,$id_responsable]);

    echo "Gestión registrada correctamente.";
}
}

// Procesar la solicitud AJAX para obtener municipios o colonias
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'getMunicipios') {
        $estado_id = $_POST['estado_id'];
        $query = "SELECT id, nombre_municipio FROM municipios WHERE id_estado = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$estado_id]);
        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($municipios);
        exit();
    } elseif ($_POST['action'] == 'getColonias') {
        $municipio_id = $_POST['municipio_id'];
        $query = "SELECT id, nombre_colonia FROM colonias WHERE id_municipio = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$municipio_id]);
        $colonias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($colonias);
        exit();
    }
}

// Procesar la solicitud de búsqueda de gestiones con filtros
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'buscar') {
        $tema = $_POST['tema'] ?? '';
        $subtema = $_POST['subtema'] ?? '';
        $origen = $_POST['origen'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $municipio = $_POST['municipio'] ?? '';
        $colonia = $_POST['colonia'] ?? '';
        $numExterior = $_POST['numExterior'] ?? '';
        $celular = $_POST['celular'] ?? '';

        $query = "SELECT g.*, e.nombre_estado, m.nombre_municipio, c.nombre_colonia, t.nombre_tema, s.nombre_subtema 
                FROM gestiones g 
                JOIN estados e ON g.id_estado = e.id 
                JOIN municipios m ON g.id_municipio = m.id 
                JOIN colonias c ON g.id_colonia = c.id 
                JOIN temas t ON g.id_tema = t.id 
                JOIN subtemas s ON g.id_subtema = s.id 
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
            $query .= " AND g.num_exterior LIKE ?";
            $params[] = "%$numExterior%";
        }
        if ($celular) {
            $query .= " AND g.celular LIKE ?";
            $params[] = "%$celular%";
            
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $gestiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($gestiones);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Gestión y Consulta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .content {
            margin: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        body {
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 180px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
    .sidebar a {
            display: block;
         
            text-decoration: none;
            color: inherit; /* Hereda el color del padre */
            font-weight: bold;
            margin-bottom: 10px;
        }
          .sidebar-link {
        text-decoration: none;  /* Sin subrayado */
        color: inherit;         /* Mantener el color heredado del padre */
        font-weight: bold;      /* Negrita */
    }

    /* Si quieres cambiar el color al pasar el ratón por encima */
    .sidebar-link:hover {
        color: #000;            /* Cambiar a negro, por ejemplo, al hacer hover */
    }

        .content {
            margin-left: 120px;
            padding: 20px;
        }

        /* Estilos base generales */
body {
    font-family: 'Poppins', sans-serif;
}

/* Sidebar Responsivo */
.sidebar {
    height: 100vh;
    background-color: #f8f9fa;
 
    position: fixed;
    top: 0;
    left: 0;
    width: 120px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

/* Ajustar el contenido principal con margen a la izquierda */
.content {
    margin-left: 120px;
 
}

/* Estilos responsivos */
@media (max-width: 768px) {
    /* Hacer que el sidebar ocupe menos espacio en pantallas más pequeñas */
    .sidebar {
        width: 120px;
    }

    /* Ajustar el margen del contenido cuando el sidebar se reduce */
    .content {
        margin-left: 80px;
    }

    /* Ajustar el tamaño de la tabla en pantallas pequeñas */
    .table-responsive {
        overflow-x: auto;
    }

    /* Formulario en una sola columna en pantallas pequeñas */
    .row.mb-3 {
        display: flex;
        flex-direction: column;
    }

    .row.mb-3 .col-md-4,
    .row.mb-3 .col-md-2 {
        width: 100%;
        margin-bottom: 10px;
    }

    /* Eliminar el margen superior en tarjetas en pantallas pequeñas */
    .card {
        margin-top: 0;
    }

    /* Aumentar el tamaño de los botones para que sean más fáciles de pulsar */
    .btn {
        padding: 10px 20px;
        font-size: 16px;
    }
}

@media (max-width: 576px) {
    /* Ajustar el sidebar aún más para pantallas muy pequeñas */
    .sidebar {
        width: 120px;
    }

    .content {
        margin-left: 120px;
    }

    /* Estilos específicos para pantallas muy pequeñas */
    h5.card-title {
        font-size: 18px;
    }

    /* Ajustar los enlaces de la barra lateral en pantallas pequeñas */
    .sidebar-link {
        font-size: 14px;
        padding: 12px;
        display: block;
    }

    /* Ajustar la tabla y su contenido en pantallas pequeñas */
    table.table {
        font-size: 12px;
    }

    table.table th, table.table td {
        padding: 5px;
    }
}

    </style>
</head>
<body>

<div class="sidebar">
 <div class="text-center">
        <img src="logo.png" alt="Logo" class="img-fluid" style="max-width: 100%; height: auto;">
    </div>
    <h4 class="text-center"  class="sidebar-link">MENU</h4>
    <a href="dashboard.php"  class="sidebar-link">DASHBOARD</a> <!-- Asignar la ruta al dashboard -->
    <a href="gestion.php"  class="sidebar-link">GESTIONES</a>
    <ul class="list-unstyled ps-3">
        <li><a href="#gestionForm"  class="sidebar-link">Captura</a> <!-- Enlace hacia el formulario de captura de gestiones -->
        </li>
        <li><a href="#filtroForm"  class="sidebar-link">Consulta</a> <!-- Enlace hacia el formulario de filtros para consulta de gestiones -->
        </li>
    </ul>

    <a href="logout.php"  class="sidebar-link">CERRAR SESION</a> <!-- Ruta para cerrar sesión -->
</div>

    <div class="content">
        <div class="card mt-5">
            <div class="card-body">
                <h5 class="card-title">Captura de Gestión</h5>
                <form id="gestionForm" method="POST">
                    <!-- Tema y Subtema -->
                    <div class="row mb-3">
                   
                        <label for="tema">Tema:</label>
                        <select id="tema" name="tema" required>
                            <option value="">Selecciona un tema</option>
                            <?php foreach ($temas as $tema): ?>
                                <option value="<?= $tema['id'] ?>"><?= $tema['nombre_tema'] ?></option>
                            <?php endforeach; ?>
                        </select>
                      
                     </div>
                      <div class="row mb-3">

                     

                       <label for="subtema">Subtema:</label>
                        <select id="subtema" name="subtema" required>
                            <option value="">Selecciona un tema primero</option>
                        </select>

                       
                        <label for="responsable">Responsable:</label>
<select id="responsable" name="responsable" required>
    <option value="">Selecciona un responsable</option>
    <?php foreach ($responsables as $responsable): ?>
        <option value="<?= $responsable['id_responsable'] ?>"><?= htmlspecialchars($responsable['nombre_responsable']) ?></option>
    <?php endforeach; ?>
</select>
</div>


                        <div class="col-md-4">
                           <label for="origen" class="form-label">Origen</label>
                            <select class="form-select" id="origen" name="origen">
                                <option value="Redes Sociales">Redes Sociales</option>
                                <option value="Evento campaña">Evento campaña</option>
                                 <option value="Evento gobierno">Evento gobierno</option>
                                   <option value="Administracion 2022-2024">Administracion 2022-2024</option>
                                     <option value="Administracion 2024-2027">Administracion 2024-2027</option>
                            </select>
                             <label for="status" class="form-label">Estatus</label>
                            <select class="form-select" id="status" name="status">
                                <option value=1>En proceso</option>
                                <option value=2>Completado</option>
                                <option value=3>Cancelado</option>
                                <option value=4>En espera de Beneficiario</option>
                                 
                                 
                            </select>
                       
                 </div>

                    <!-- Otros campos (Estado, Municipio, Colonia, etc.) -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="estadoNacimiento" class="form-label">Estado de Nacimiento</label>
                            <select class="form-select" id="estadoNacimiento" name="estadoNacimiento">
                                <option value="">Seleccione un estado</option>
                                <?php
                                // Consultar los estados para llenar el select
                                $query = "SELECT id, nombre_estado FROM estados";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['id']}'>{$row['nombre_estado']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="municipio" class="form-label">Municipio</label>
                            <select class="form-select" id="municipio" name="municipio">
                                <option value="">Seleccione un municipio</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="colonia" class="form-label">Colonia</label>
                            <select class="form-select" id="colonia" name="colonia">
                                <option value="">Seleccione una colonia</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                       <div class="col-md-2">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-2">
                            <label for="calle" class="form-label">Calle</label>
                            <input type="text" class="form-control" id="calle" name="calle" required>
                        </div>
                        <div class="col-md-2">
                            <label for="numExterior" class="form-label">No. Exterior</label>
                            <input type="text" class="form-control" id="numExterior" name="numExterior" required>
                        </div>
                        <div class="col-md-2">
                            <label for="celular" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="celular" name="celular" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="detalle" class="form-label">Detalle</label>
                             <input type="text" class="form-control" id="detalle" name="detalle" required>
                        </div>
                    </div>

                    

                    <!-- Botón de Guardar -->
                    <div class="text-end">
                        <button id="guardarBtn"  class="btn btn-primary" >Guardar Gestión</button>
                    </div>
                    
                </form>
            </div>
        </div>

        <!-- Sección de búsqueda -->
        <div class="card mt-5">
            <div class="card-body">
                <h5 class="card-title">Consultar Gestiones</h5>
                <form id="filtroForm" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="buscarTema" class="form-label">Tema</label>
                            <input type="text" class="form-control" id="buscarTema" name="tema">
                        </div>
                        <div class="col-md-4">
                            <label for="buscarSubtema" class="form-label">Subtema</label>
                            <input type="text" class="form-control" id="buscarSubtema" name="subtema">
                        </div>
                    </div>
                    <!-- Filtros adicionales... -->
                    <div class="text-end">
                        <button type="button" id="buscarBtn" class="btn btn-primary">Buscar</button>
                    </div>
                </form>

                <!-- Resultados -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tema</th>
                                <th>Origen</th>
                                <th>Estado</th>
                                <th>Municipio</th>
                                <th>Colonia</th>
                                <th>Nombre</th>
                                <th>Calle</th>
                                <th>Número Exterior</th>
                                <th>Celular</th>
                                <th>Fecha</th>
                                <th>Status</th>
                                 <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="resultadosTable">

                         <!-- Aquí se insertan las últimas gestiones al cargar la página -->
                    <?php foreach ($ultimas_gestiones as $gestion): ?>
                        <tr>
                            <td><?= $gestion['idgestion'] ?></td>
                            <td><?= $gestion['nombre_tema'] ?></td>
                            <td><?= $gestion['origen'] ?></td>
                            <td><?= $gestion['nombre_estado'] ?></td>
                            <td><?= $gestion['nombre_municipio'] ?></td>
                            <td><?= $gestion['nombre_colonia'] ?></td>
                            <td><?= $gestion['nombre'] ?></td>
                            <td><?= $gestion['calle'] ?></td>
                            <td><?= $gestion['num_exterior'] ?></td>
                            <td><?= $gestion['celular'] ?></td>
                            <td><?= $gestion['fecha'] ?></td>
                            <td><?= $gestion['estatus'] ?></td>
                            <td><?= $gestion['detalle'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                          
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de búsqueda AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#estadoNacimiento').on('change', function() {
            var estadoID = $(this).val();
            if (estadoID) {
                $.ajax({
                    type: 'POST',
                    url: 'gestion.php',
                    data: { action: 'getMunicipios', estado_id: estadoID },
                    dataType: 'json',
                    success: function(response) {
                        $('#municipio').empty().append('<option value="">Seleccione un municipio</option>');
                        $.each(response, function(key, value) {
                            $('#municipio').append('<option value="' + value.id + '">' + value.nombre_municipio + '</option>');
                        });
                        $('#colonia').html('<option value="">Seleccione un municipio primero</option>');
                    }
                });
            } else {
                $('#municipio').html('<option value="">Seleccione un estado primero</option>');
                $('#colonia').html('<option value="">Seleccione un municipio primero</option>');
            }
        });

        $('#municipio').on('change', function() {
            var municipioID = $(this).val();
            if (municipioID) {
                $.ajax({
                    type: 'POST',
                    url: 'gestion.php',
                    data: { action: 'getColonias', municipio_id: municipioID },
                    dataType: 'json',
                    success: function(response) {
                        $('#colonia').empty().append('<option value="">Seleccione una colonia</option>');
                        $.each(response, function(key, value) {
                            $('#colonia').append('<option value="' + value.id + '">' + value.nombre_colonia + '</option>');
                        });
                    }
                });
            } else {
                $('#colonia').html('<option value="">Seleccione un municipio primero</option>');
            }
        });

        $('#tema').on('change', function() {
    var temaID = $(this).val();
    if (temaID) {
        $.ajax({
            type: 'POST',
            url: 'gestion.php',
            data: { action: 'getSubtemas', tema_id: temaID },
            dataType: 'json',
            success: function(response) {
                $('#subtema').empty().append('<option value="">Selecciona un subtema</option>');
                $.each(response, function(key, value) {
                    $('#subtema').append('<option value="' + value.id + '">' + value.nombre_subtema + '</option>');
                });
            },
            error: function() {
                alert('Error al cargar los subtemas.');
            }
        });
    } else {
        $('#subtema').html('<option value="">Selecciona un tema primero</option>');
    }
});



     

     $('#guardarBtn').on('click', function() {
        // Obtener los datos del formulario y agregar el parámetro 'action=guardar'
        var formData = $('#gestionForm').serialize();
        formData += '&action=guardar';

        // Realizar la solicitud AJAX
        $.ajax({
            type: 'POST',
            url: 'gestion.php',  // La URL del archivo PHP que procesará los datos
            data: formData,
            dataType: 'json', // Si esperas una respuesta JSON
            success: function(response) {

                    alert('Gestión registrada correctamente.');
                    // Aquí puedes mostrar algo en la tabla o resetear el formulario
                    $('#gestionForm')[0].reset(); // Opción de resetear el formulario
              
            }
        });
    });

        // Búsqueda AJAX
        $('#buscarBtn').on('click', function() {
           
            var formData = $('#filtroForm').serialize(); // Obtener datos del formulario
       // Añadir el parámetro de búsqueda

            formData += '&action=buscar';

            $.ajax({
                type: 'POST',
                url: 'gestion.php',
                data: formData ,
                dataType: 'json',
                success: function(response) {
                    var resultadosHtml = '';
                    $.each(response, function(key, value) {
                        resultadosHtml += '<tr>' +
                            '<td>' + value.idgestion + '</td>' +
                            '<td>' + value.nombre_tema + '</td>' +
                            '<td>' + value.nombre_subtema + '</td>' +
                            '<td>' + value.origen + '</td>' +
                            '<td>' + value.nombre_estado + '</td>' +
                            '<td>' + value.nombre_municipio + '</td>' +
                            '<td>' + value.nombre_colonia + '</td>' +
                            '<td>' + value.num_exterior + '</td>' +
                            '<td>' + value.celular + '</td>' +
                            '<td>' + value.fecha + '</td>' +
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