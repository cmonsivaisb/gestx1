<?php
include 'db.php';

$nombre_subtema = $_POST['nombre_subtema'] ?? '';

if ($nombre_subtema) {
    $stmt = $pdo->prepare("INSERT INTO subtemas (nombre_subtema) VALUES (?)");
    $stmt->execute([$nombre_subtema]);
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'nombre_subtema' => $nombre_subtema
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Nombre de subtema no válido.']);
}
