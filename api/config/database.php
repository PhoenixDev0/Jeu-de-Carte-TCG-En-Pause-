<?php

/* Fichier d'accès à la base de données */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "localhost";
$db_name = "anime_game_card";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->exec("set names utf8");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo json_encode(["error" => "Erreur de connexion : " . $exception->getMessage()]);
    exit();
}
?>