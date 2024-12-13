<?php
include 'db.php';

$id_tema = $_GET['id_tema'] ?? 0;

$stmt = $pdo->prepare("SELECT id, nombre_subtema AS nombre FROM subtemas WHERE id_tema = ?");
$stmt->execute([$id_tema]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
