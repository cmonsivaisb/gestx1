<?php
// Iniciar sesión
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Redirigir si no está autenticado
    exit();
}

// Conectar a la base de datos
include 'db.php';









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
$responsable = $_POST['responsable'] ?? '';







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
       
       if ($responsable) {
           $query .= " AND r.nombre_responsable LIKE ?";
           $params[] = "%$responsable%";
       }
       
$query .= " ORDER BY fecha  DESC";
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
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre">
                </div>
                <div class="col-md-4">
                    <label for="buscarTema" class="form-label">Tema</label>
                    <input type="text" class="form-control" id="buscarTema" name="tema">
                </div>
                  <div class="col-md-4">
                    <label for="subtema" class="form-label">Subtema</label>
                    <input type="text" class="form-control" id="subtema" name="subtema">
                </div>
            
               
            </div>
            <div class="row mb-3">
             <div class="col-md-4">
                    <label for="origen" class="form-label">Origen</label>
                    <input type="text" class="form-control" id="origen" name="origen">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" class="form-control" id="estado" name="estado">
                </div>
                <div class="col-md-4">
                    <label for="municipio" class="form-label">Municipio</label>
                    <input type="text" class="form-control" id="municipio" name="municipio">
                </div>
             
            </div>
            <div class="row mb-3">
               <div class="col-md-4">
                    <label for="colonia" class="form-label">Colonia</label>
                    <input type="text" class="form-control" id="colonia" name="colonia">
                </div>
                 <div class="col-md-4">
                    <label for="seccional" class="form-label">Seccional</label>
                    <input type="text" class="form-control" id="seccional" name="seccional">
                </div>
                <div class="col-md-4">
                    <label for="numExterior" class="form-label">Número Exterior</label>
                    <input type="text" class="form-control" id="numExterior" name="numExterior">
                </div>
               
               
            </div>

             <div class="row mb-3">
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
                    <th>Número Exterior</th>
                    <th>Calle</th>
                    <th>Celular</th>
                 
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
                                '<td>' + value.num_exterior + '</td>' +     
                            '<td>' + value.calle + '</td>' +
                                '<td>' + value.celular + '</td>' +
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
                [ "Nombre","Fecha","Tema", "Subtema", "Origen", "Estado","Responsable", "Municipio", "Colonia", "Seccional","Número Exterior", "Celular",  "Estatus", "Detalle"]
            ];

            // Iterar sobre los resultados y añadirlos al array 'datos'
            resultados.forEach(function(gestion) {
                datos.push([
                  gestion.nombre,
                  gestion.fecha,
                    gestion.nombre_tema,
                    gestion.nombre_subtema,
                    gestion.origen,
                    gestion.nombre_estado,
                    gestion.nombre_responsable,
                    gestion.nombre_municipio,
                    gestion.nombre_colonia,
                     gestion.seccional,
                    gestion.num_exterior,
                    gestion.celular,
                   
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
