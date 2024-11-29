<?php
session_start();

// Credenciales de usuario
define('USERNAME', 'reportvisor');
define('PASSWORD', '12345'); // Contraseña sencilla

// Verificar si el usuario ya está autenticado
if (!isset($_SESSION['authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validar credenciales
        if ($username === USERNAME && $password === PASSWORD) {
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }

    // Mostrar formulario de inicio de sesión
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <form method="post">
        <label for="username">Usuario:</label><br>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Contraseña:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Entrar</button>
    </form>';
    if (isset($error)) {
        echo '<p style="color: red;">' . $error . '</p>';
    }
    echo '</body>
</html>';
    exit;
}

// Si está autenticado, proceder a mostrar las gestiones
require 'db.php'; // Archivo con la conexión a la base de datos

function obtenerGestionesPorTema($pdo) {
    $stmt = $pdo->query("SELECT t.id AS id_tema, t.nombre_tema, COUNT(g.id) AS cantidad
                         FROM gestiones g
                         INNER JOIN temas t ON g.id_tema = t.id
                         WHERE g.id > 22
                         GROUP BY t.id, t.nombre_tema
                         ORDER BY cantidad DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Consultar los datos
$gestionesPorTema = obtenerGestionesPorTema($pdo);

// Configuración de subtemas
$subtemasPorTema = [
    "19" => ["ESTUDIO MEDICO", "APARATOS DE MOVILIDAD", "ESPECIALISTA", "MEDICAMENTO", "LENTES"],
    "21" => ["APOYO ECONOMICO", "APOYO ALIMENTARIO", "UTILES ESCOLARES", "TRASLADO", "APOYOS FUNERARIOS", "ATENCION CIUDADANA"],
    "22" => ["REDUCTORES DE VELOCIDAD", "BORDOS", "SEÑALIZACION", "CONCESIONES"],
    "23" => ["LUMINARIA", "RECOLECCION DE BASURA", "PLAZAS Y JARDINES", "LIMPIEZA", "PIPA DE AGUA"],
    "24" => ["CONTROL ANIMAL", "ESTERILIZACIONES"],
    "25" => ["BACHES", "PAVIMENTACION", "MATERIAL PARA CONSTRUCCION"],
    "27" => ["BOMBA DE AGUA", "ARREGLO DE CAMINO"],
    "28" => ["PLACAS", "REGISTRO CIVIL", "ESCRITURACION", "PODER JUDICIAL", "EMPOODERAMIENTO DE LA MUJER", "TESTAMENTO"],
    "29" => ["EMPLEO TEMPORAL", "LIQUIDACION", "PENSION", "EMPLEO"],
    "30" => ["RONDINES", "APOYO VIAL"],
    "31" => ["FALTA DE AGUA", "POCA PRESION DE AGUA", "CONTRATO", "DRENAJE", "CONVENIO"],
    "32" => ["MATERIAL DEPORTIVO", "PRESTAMO UNIDAD DEPORTIVA", "PREMIACIONES"],
    "33" => ["ASESORIA LEGAL", "TESTAMENTO", "DIVORCIO"],
    "34" => ["AMBULANCIA"],
    "35" => ["MEDICAMENTO", "CONSULTA"],
    "37" => ["APOYO PSICOLOGICO", "ASESORIA"],
    "39" => ["ASESORIA LEGAL"],
    "40" => ["BANDA MUNICIPAL", "ESPACIO CULTURAL"],
    "41" => ["ARREGLO FLORAL", "APOYO SONIDO", "APOYO MOBILIARIO"],
    "46" => ["TECHO", "PISO", "CUARTO", "BAÑO", "TINACO", "LAMINAS", "PAVIMENTACION", "DRENAJE"]
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiones por Tema</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .chart-container {
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #4CAF50;
            color: white;
        }
        .logout-button {
            display: inline-block;
            margin-top: 20px;
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestiones por Tema</h1>

        <div class="chart-container">
            <canvas id="temasChart"></canvas>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tema</th>
                    <th>Cantidad de Gestiones</th>
                    <th>Subtemas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gestionesPorTema as $tema): ?>
                    <tr>
                        <td><?= htmlspecialchars($tema['nombre_tema']) ?></td>
                        <td><?= htmlspecialchars($tema['cantidad']) ?></td>
                        <td><?= isset($subtemasPorTema[$tema['id_tema']]) ? implode(', ', $subtemasPorTema[$tema['id_tema']]) : 'N/A' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="logout.php" class="logout-button">Cerrar Sesión</a>
    </div>

    <script>
        const data = {
            labels: <?= json_encode(array_column($gestionesPorTema, 'nombre_tema')) ?>,
            datasets: [{
                label: 'Cantidad de Gestiones',
                data: <?= json_encode(array_column($gestionesPorTema, 'cantidad')) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        const ctx = document.getElementById('temasChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
