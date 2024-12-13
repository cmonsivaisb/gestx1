<?php
session_start();



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

function obtenerGestionesPorSubtema($pdo, $temaId) {
    $stmt = $pdo->prepare("SELECT s.id AS id_subtema, s.nombre_subtema, COUNT(g.id) AS cantidad
                           FROM gestiones g
                           INNER JOIN subtemas s ON g.id_subtema = s.id
                           WHERE g.id_tema = ?
                           GROUP BY s.id, s.nombre_subtema
                           ORDER BY cantidad DESC");
    $stmt->execute([$temaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Manejar la solicitud AJAX para los subtemas
if (isset($_GET['action']) && $_GET['action'] === 'obtener_subtemas') {
    $temaId = $_GET['tema_id'] ?? null;
    if ($temaId) {
        echo json_encode(obtenerGestionesPorSubtema($pdo, $temaId));
    } else {
        echo json_encode([]);
    }
    exit;
}

// Consultar los datos
$gestionesPorTema = obtenerGestionesPorTema($pdo);

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

        <div>
            <label for="temaSelect">Seleccionar Tema:</label>
            <select id="temaSelect" class="form-select">
                <option value="">Selecciona un tema</option>
                <?php foreach ($gestionesPorTema as $tema): ?>
                    <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nombre_tema']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="chart-container">
            <canvas id="temasChart"></canvas>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tema</th>
                    <th>Cantidad de Gestiones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gestionesPorTema as $tema): ?>
                    <tr>
                        <td><?= htmlspecialchars($tema['nombre_tema']) ?></td>
                        <td><?= htmlspecialchars($tema['cantidad']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="logout.php" class="logout-button">Cerrar Sesión</a>
    </div>

    <script>
        const ctx = document.getElementById('temasChart').getContext('2d');
        let chart;

        // Función para actualizar la gráfica
        function actualizarGrafica(labels, data, label) {
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        document.getElementById('temaSelect').addEventListener('change', function () {
            const temaId = this.value;

            if (temaId) {
                // Solicitar datos por subtemas del tema seleccionado
                fetch(`?action=obtener_subtemas&tema_id=${temaId}`)
                    .then(response => response.json())
                    .then(data => {
                        const labels = data.map(subtema => subtema.nombre_subtema);
                        const cantidades = data.map(subtema => subtema.cantidad);
                        actualizarGrafica(labels, cantidades, 'Gestiones por Subtema');
                    })
                    .catch(error => console.error('Error al cargar subtemas:', error));
            } else {
                // Restablecer la gráfica inicial si no hay tema seleccionado
                const labels = <?= json_encode(array_column($gestionesPorTema, 'nombre_tema')) ?>;
                const cantidades = <?= json_encode(array_column($gestionesPorTema, 'cantidad')) ?>;
                actualizarGrafica(labels, cantidades, 'Gestiones por Tema');
            }
        });

        // Cargar gráfica inicial
        const initialLabels = <?= json_encode(array_column($gestionesPorTema, 'nombre_tema')) ?>;
        const initialData = <?= json_encode(array_column($gestionesPorTema, 'cantidad')) ?>;
        actualizarGrafica(initialLabels, initialData, 'Gestiones por Tema');
    </script>
</body>
</html>
