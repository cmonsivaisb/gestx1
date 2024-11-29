<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiones Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 80%; margin: 20px auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; }
        th { background-color: #f4f4f4; }
        .button { background-color: #4CAF50; color: white; padding: 10px 20px; margin-top: 20px; cursor: pointer; border: none; font-size: 16px; }
        .progress-container { width: 80%; margin: 20px auto; background-color: #f4f4f4; border-radius: 5px; overflow: hidden; }
        .progress-bar { height: 20px; width: 0; background-color: #4CAF50; }
    </style>
</head>
<body>

    <img src="logo.png" alt="Logo" style="width: 150px; margin-top: 20px;">
    <h1>Gestiones AI Report</h1>
    
    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <input type="file" name="gestiones_csv" accept=".csv" required>
        <button type="submit" name="generate_report" class="button">Upload and Generate AI Report</button>
    </form>

    <div class="progress-container" id="progressContainer" style="display: none;">
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <div id="results"></div>

    <script>
        document.getElementById("uploadForm").onsubmit = function () {
            document.getElementById("progressContainer").style.display = "block";
        };

        function updateProgress(percentage) {
            document.getElementById("progressBar").style.width = percentage + "%";
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

            echo "<script>document.getElementById('results').innerHTML = '<h2>AI Report Results</h2><table><tr><th>Original</th><th>Translated</th><th>Sentiment</th><th>Topic</th></tr>';</script>";
            foreach ($results as $result) {
                echo "<script>
                    document.getElementById('results').innerHTML += `<tr><td>{$result['original']}</td><td>{$result['translated']}</td><td>{$result['sentiment']}</td><td>{$result['topic']}</td></tr>`;
                    updateProgress({$result['progress']});
                </script>";
                flush();
                ob_flush();
            }
            echo "<script>document.getElementById('results').innerHTML += '</table>';</script>";
        } else {
            echo "<p style='color: red;'>Failed to upload file. Please try again.</p>";
        }
    }

    function analyzeGestionesCSV($filePath) {
        $translatedData = [];
        $allRows = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $headers = str_getcsv(array_shift($allRows));

        // Randomly select 500 rows
        shuffle($allRows);
        $selectedRows = array_slice($allRows, 0, 500);
        $totalRows = count($selectedRows);

        $rowCount = 0;
        foreach ($selectedRows as $line) {
            $data = str_getcsv($line);
            $detalle = $data[array_search("detalle", $headers)];
            $translatedText = translateToEnglish($detalle);

            $sentiment = performSentimentAnalysis($translatedText);
            $topic = classifyTopic($translatedText);

            $translatedData[] = [
                'original' => $detalle,
                'translated' => $translatedText,
                'sentiment' => $sentiment,
                'topic' => $topic,
                'progress' => round((++$rowCount / $totalRows) * 100)
            ];
        }

        return $translatedData;
    }

    function translateToEnglish($text) {
        $url = 'https://libretranslate.com/translate';
        $data = [
            'q' => $text,
            'source' => 'es',
            'target' => 'en',
            'format' => 'text'
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === FALSE) { 
            return "Translation Error";
        }
        
        $result = json_decode($response, true);
        return $result['translatedText'] ?? "Translation Error";
    }

    function performSentimentAnalysis($text) {
        $positiveKeywords = ["support", "assistance", "help", "positive"];
        $negativeKeywords = ["issue", "problem", "need", "negative"];

        foreach ($positiveKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return "Positive";
            }
        }
        foreach ($negativeKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return "Negative";
            }
        }
        return "Neutral";
    }

    function classifyTopic($text) {
        $topics = [
            "Healthcare" => ["medical", "health", "doctor", "hospital"],
            "Education" => ["school", "education", "study", "university"],
            "Financial Assistance" => ["money", "support", "financial", "aid"],
            "Transportation" => ["transport", "travel", "bus", "car"],
            "Food Assistance" => ["food", "nutrition", "hunger", "meal"]
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
    ?>
</body>
</html>
