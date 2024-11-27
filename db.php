<?php

$host = 'localhost';

$db = 'prrs2224coah_gestiones';

$user = 'prrs2224coah_gestiones';

$pass = '03&[8^dm.AQn';



try {

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Error en la conexión: " . $e->getMessage());

}

?>