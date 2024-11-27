<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Análisis de Gestiones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        header { font-size: 1.5em;
            width: 100%;
            text-align: center;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            margin-top: 15px;
            cursor: pointer;
            border: none;
            font-size: 16px;
            border-radius: 5px;
            width: 100%;
            max-width: 200px;
        }
        .button i {
            margin-right: 8px;
        }
        .progress-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 20px;
        }
        .progress-bar {
            height: 20px;
            width: 0;
            background-color: #4CAF50;
        }
        .chart-container, .foda-container {
            margin: 20px auto;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
        }
        .chart-container h2, .foda-container h2 { font-size: 1.2em;
            margin-bottom: 10px;
        }
        .chart-container h2 i, .foda-container h2 i {
            margin-right: 8px;
        }
        .legend {
            text-align: left;
            width: 100%;
            max-width: 600px;
            margin: 10px auto;
            padding: 0;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .color-box {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 3px;
        }
        canvas {
            margin-bottom: 10px;
            width: 100%;
            height: auto;
            max-height: 300px;
        }
        .foda-section {
            margin-bottom: 20px;
        }
        .foda-section h3 { font-size: 1em;
            color: #4CAF50;
            display: flex;
            align-items: center;
        }
        .foda-section h3 i {
            margin-right: 8px;
        }
        .foda-section p {
            font-size: 1em;
            margin: 10px 0;
        }
        .foda-section ul {
            margin: 0 0 10px 20px;
            list-style: disc;
        }
        @media (max-width: 600px) {
            header {
                font-size: 1.5em;
            }
            .button {
                font-size: 14px;
                padding: 8px 15px;
            }
            .chart-container h2, .foda-container h2 {
                font-size: 1.2em;
            }
            .foda-section h3 {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>

    <header><i class="fas fa-chart-line"></i> Informe de Análisis de Gestiones</header>

    <div class="container">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="file" name="gestiones_csv" accept=".csv" required>
            <button type="submit" name="generate_report" class="button">
                <i class="fas fa-upload"></i> Subir y Generar Informe
            </button>
        </form>

        <div class="progress-container" id="progressContainer" style="display: none;">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <div class="chart-container">
        <h2><i class="fas fa-smile"></i> Gráfica de Sentimientos</h2>
        <canvas id="sentimentChart"></canvas>
        <div id="sentimentLegend" class="legend"></div>
    </div>
    <div class="chart-container">
        <h2><i class="fas fa-tags"></i> Gráfica de Temas</h2>
        <canvas id="topicChart"></canvas>
        <div id="topicLegend" class="legend"></div>
    </div>

    <div class="foda-container" id="fodaContainer">
        <h2><i class="fas fa-balance-scale"></i> Análisis FODA</h2>
        <div id="fodaAnalysis"></div>
    </div>

    <script>
        document.getElementById("uploadForm").onsubmit = function () {
            document.getElementById("progressContainer").style.display = "block";
        };

        function updateProgress(percentage) {
            document.getElementById("progressBar").style.width = percentage + "%";
        }

        function drawPieChart(data) {
            const canvas = document.getElementById('sentimentChart');
            const ctx = canvas.getContext('2d');
            const colors = ["#4CAF50", "#FFC107", "#F44336"];
            const labels = ["Positivo", "Neutral", "Negativo"];
            let total = Object.values(data).reduce((a, b) => a + b, 0);
            let startAngle = 0;

            Object.keys(data).forEach((key, index) => {
                const sliceAngle = (data[key] / total) * 2 * Math.PI;
                ctx.beginPath();
                ctx.moveTo(canvas.width / 2, canvas.height / 2);
                ctx.arc(canvas.width / 2, canvas.height / 2, Math.min(canvas.width / 2, canvas.height / 2), startAngle, startAngle + sliceAngle);
                ctx.fillStyle = colors[index];
                ctx.fill();
                startAngle += sliceAngle;
            });

            // Create legend for sentiment chart
            const sentimentLegend = document.getElementById('sentimentLegend');
            sentimentLegend.innerHTML = '';
            labels.forEach((label, index) => {
                sentimentLegend.innerHTML += `
                    <div class="legend-item">
                        <div class="color-box" style="background-color: ${colors[index]};"></div>
                        <span>${label}: ${data[labels[index]]}</span>
                    </div>
                `;
            });
        }

        function drawBarChart(data) {
            const canvas = document.getElementById('topicChart');
            const ctx = canvas.getContext('2d');
            const keys = Object.keys(data).slice(0, 5);
            const values = Object.values(data).slice(0, 5);
            const max = Math.max(...values);
            const colors = ["#4CAF50", "#2196F3", "#FFC107", "#FF5722", "#607D8B"];

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#000";
            ctx.font = "10px Arial";
            ctx.fillText("Distribución de Temas", 10, 20);

            keys.forEach((key, index) => {
                const barWidth = 30;
                const gap = 20;
                const x = 30 + index * (barWidth + gap);
                const height = (values[index] / max) * (canvas.height - 50);
                const y = canvas.height - height - 20;

                // Draw the bar with anti-aliasing consideration
                ctx.beginPath();
                ctx.fillStyle = colors[index % colors.length];
                ctx.fillRect(x, y, barWidth, height);
                ctx.closePath();

                // Draw the labels
                ctx.fillStyle = "#000";
                ctx.fillText(key, x, canvas.height - 5);
                ctx.fillText(values[index], x, y - 5);
            });

            // Create legend for topic chart
            const topicLegend = document.getElementById('topicLegend');
            topicLegend.innerHTML = '';
            keys.forEach((key, index) => {
                topicLegend.innerHTML += `
                    <div class="legend-item">
                        <div class="color-box" style="background-color: ${colors[index % colors.length]};"></div>
                        <span>${key}: ${values[index]}</span>
                    </div>
                `;
            });
        }

        function generateFODA(sentiments, topics) {
            let positiveCount = sentiments["Positivo"];
            let negativeCount = sentiments["Negativo"];
            let total = positiveCount + negativeCount + sentiments["Neutral"];

            let strengths = `
                <ul>
                    <li>Alta cantidad de gestiones positivas, lo que refleja esfuerzos exitosos para satisfacer las necesidades de la comunidad.</li>
                    <li>Temas como salud y asistencia financiera tienen alta prioridad y respuestas positivas.</li>
                </ul>
            `;
            let weaknesses = `
                <ul>
                    <li>Existencia de gestiones sin resolver y con alta insatisfacción, especialmente en áreas como infraestructura y vivienda.</li>
                    <li>Falta de seguimiento adecuado en solicitudes canceladas o en espera.</li>
                </ul>
            `;
            let opportunities = `
                <ul>
                    <li>Oportunidades para mejorar la eficiencia de respuesta en áreas de infraestructura y medio ambiente.</li>
                    <li>Potencial para desarrollar programas comunitarios que refuercen los sectores con más solicitudes.</li>
                </ul>
            `;
            let threats = `
                <ul>
                    <li>El descontento puede aumentar si no se resuelven con prontitud las solicitudes pendientes o rechazadas.</li>
                    <li>Limitación de recursos que podría afectar la capacidad de respuesta a las necesidades más urgentes.</li>
                </ul>
            `;

            const fodaAnalysis = document.getElementById('fodaAnalysis');
            fodaAnalysis.innerHTML = `
                <div class="foda-section">
                    <h3><i class="fas fa-thumbs-up"></i> Fortalezas:</h3>
                    <p>${strengths}</p>
                </div>
                <div class="foda-section">
                    <h3><i class="fas fa-exclamation-circle"></i> Debilidades:</h3>
                    <p>${weaknesses}</p>
                </div>
                <div class="foda-section">
                    <h3><i class="fas fa-lightbulb"></i> Oportunidades:</h3>
                    <p>${opportunities}</p>
                </div>
                <div class="foda-section">
                    <h3><i class="fas fa-bolt"></i> Amenazas:</h3>
                    <p>${threats}</p>
                </div>
            `;
        }
    </script>

    <?php
    if (isset($_POST['generate_report'])) {
        if (isset($_FILES['gestiones_csv']) && $_FILES['gestiones_csv']['error'] == 0) {
            $filePath = 'uploads/' . basename($_FILES['gestiones_csv']['name']);

            if (!is_dir('uploads')) {
                mkdir('uploads');
            }
            move_uploaded_file($_FILES['gestiones_csv']['tmp_name'], $filePath);

            $results = analyzeGestionesCSV($filePath);
            $sentiments = ['Positivo' => 0, 'Neutral' => 0, 'Negativo' => 0];
            $topics = [];

            foreach ($results as $result) {
                $sentiments[$result['sentiment']]++;
                $topics[$result['topic']] = ($topics[$result['topic']] ?? 0) + 1;
            }

            echo "<script>drawPieChart(" . json_encode($sentiments) . ");</script>";
            echo "<script>drawBarChart(" . json_encode($topics) . ");</script>";
            echo "<script>generateFODA(" . json_encode($sentiments) . ", " . json_encode($topics) . ");</script>";
        } else {
            echo "<p style='color: red;'>Error al cargar el archivo. Inténtelo de nuevo.</p>";
        }
    }

    function analyzeGestionesCSV($filePath) {
        $translatedData = [];
        $allRows = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $headers = str_getcsv(array_shift($allRows));

        shuffle($allRows);
        $selectedRows = array_slice($allRows, 0, 50);
        $totalRows = count($selectedRows);

        $rowCount = 0;
        foreach ($selectedRows as $line) {
            $data = str_getcsv($line);
            $detalle = end($data);
            $estatus = prev($data);

            $sentiment = performSentimentAnalysis($detalle);
            $topic = classifyTopic($detalle);
            $estatusSentiment = analyzeStatus($estatus);

            if ($estatusSentiment == "Negativo") {
                $sentiment = "Negativo";
            } elseif ($estatusSentiment == "Positivo" && $sentiment != "Negativo") {
                $sentiment = "Positivo";
            }

            $translatedData[] = [
                'detalle' => $detalle,
                'sentiment' => $sentiment,
                'topic' => $topic,
                'estatus' => $estatusSentiment,
                'progress' => round((++$rowCount / $totalRows) * 100)
            ];
        }

        return $translatedData;
    }

    function performSentimentAnalysis($text) {
        $positiveKeywords = ["apoyo", "asistencia", "ayuda", "positivo", "beneficio", "gratitud", "éxito", "felicidad", "bienestar"];
        $negativeKeywords = [
            "problema", "necesidad", "negativo", "dificultad", "inconveniente", "queja", 
            "crisis", "falla", "desventaja", "emergencia", "NO SE HA HECHO NADA", 
            "no se ha resuelto", "no se atiende", "sin respuesta", "no han trabajado", 
            "no han hecho nada", "falta de acción", "sin solución", "inacción"
        ];

        foreach ($positiveKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return "Positivo";
            }
        }
        foreach ($negativeKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return "Negativo";
            }
        }
        return "Neutral";
    }

    function classifyTopic($text) {
        $topics = [
            "Salud" => ["médico", "salud", "doctor", "hospital", "clínica", "tratamiento", "enfermedad", "emergencia"],
            "Educación" => ["escuela", "educación", "estudio", "universidad", "beca", "clases", "curso", "aprendizaje"],
            "Asistencia Financiera" => ["dinero", "apoyo", "financiero", "ayuda", "subsidio", "donación", "crédito", "préstamo"],
            "Transporte" => ["transporte", "viaje", "autobús", "coche", "movilidad", "traslado", "vehículo", "ruta"],
            "Asistencia Alimentaria" => ["alimentos", "nutrición", "hambre", "comida", "despensa", "donativo", "suministro", "alimentación"],
            "Vivienda" => ["vivienda", "hogar", "alojamiento", "casa", "departamento", "refugio", "residencia", "habitación"]
        ];

        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return $topic;
                }
            }
        }
        return "General";
    }

    function analyzeStatus($estatus) {
        $positiveStatuses = ["En progreso", "Completado", "Resuelto"];
        $negativeStatuses = ["En espera", "Cancelado", "Pendiente"];

        if (in_array($estatus, $positiveStatuses)) {
            return "Positivo";
        } elseif (in_array($estatus, $negativeStatuses)) {
            return "Negativo";
        }
        return "Neutral";
    }
    ?>
</body>
</html>
