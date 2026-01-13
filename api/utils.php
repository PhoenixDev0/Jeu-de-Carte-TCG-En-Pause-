<?php
// Fichier de gestion des utils

// 1. Gestion des headers CORS et Session
// Autorisation de l'origine qui fait la demande (localhost)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Credentials: true"); // INDISPENSABLE : Autorise l'envoi des Cookies
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 2. Démarrage de la session
// On configure la durée de vie du cookie
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Fonction de réponse
function sendJson($data, $code = 200) {
    http_response_code($code);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
    exit();
}
?>