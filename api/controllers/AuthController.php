<?php
//Fichier de gestion d'authentification

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Player.php';
include_once __DIR__ . '/../models/Game.php';

class AuthController {
    private $db;
    private $player;
    private $game;

    public function __construct($db) {
        $this->db = $db;
        $this->player = new Player($db);
        $this->game = new Game($db);
    }

    //Méthode d'inscription
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        if(!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            if($this->player->emailExists($data->email)) sendJson(["message" => "Email pris."], 400);
            if($this->player->usernameExists($data->username)) sendJson(["message" => "Pseudo pris."], 400);
            $result = $this->player->create($data->username, $data->email, $data->password);
            if($result === true) sendJson(["message" => "Succès"], 201);
            else sendJson(["message" => $result], 500);
        } else sendJson(["message" => "Données incomplètes"], 400);
    }
    //Méthode de connexion
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        if(!empty($data->email) && !empty($data->password)) {
            $user = $this->player->emailExists($data->email);
            if($user && password_verify($data->password, $user['password_hash'])) {
                $_SESSION['player_id'] = $user['id'];
                unset($user['password_hash']);
                sendJson(["message" => "Connexion réussie.", "user" => $user], 200);
            } else sendJson(["message" => "Erreur identifiants."], 401);
        } else sendJson(["message" => "Données incomplètes"], 400);
    }
    //Méthode de déconnexion
    public function logout() {
        session_unset();
        session_destroy();
        sendJson(["message" => "Bye."], 200);
    }
    //Méthode de rafraîchissement
    public function refresh() {
        if (isset($_SESSION['player_id'])) {
            $user = $this->player->findById($_SESSION['player_id']);
            if ($user) {
                unset($user['password_hash']);
                sendJson(["user" => $user], 200);
            } else sendJson(["message" => "Utilisateur introuvable"], 404);
        } else sendJson(["message" => "Non connecté"], 401);
    }
    //Méthode pour récupérer les données du profil
    public function getProfileData() {
        if (!isset($_SESSION['player_id'])) sendJson(["message" => "Non connecté"], 401);
        $pid = $_SESSION['player_id'];

        $user = $this->player->findById($pid);
        unset($user['password_hash']); // hash du mot de passe pour la sécurité

        // Récupérer l'historique des parties jouées (les 5 dernières)
        $history = $this->game->getHistory($pid);

        // Récupérer les items débloqués (Pour l'instant défaut si vide)
        $avatars = $this->player->getUnlockables($pid, 'avatar'); // Avatar débloqué
        if (empty($avatars)) $avatars = ['default_avatar.png', 'warrior.png', 'mage.png']; // Avatar par défaut

        $titles = $this->player->getUnlockables($pid, 'title'); // Titre débloqué
        if (empty($titles)) $titles = ['Novice', 'Apprenti', 'Duelliste']; // Titre par défaut

        // Envoi des données
        sendJson([
            "user" => $user,
            "history" => $history,
            "avatars" => $avatars,
            "titles" => $titles
        ], 200);
    }

    //Méthode pour mettre à jour le profil
    public function updateProfile() {
        if (!isset($_SESSION['player_id'])) sendJson(["message" => "Non connecté"], 401);
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];

        // Mise à jour des données du profil
        if (isset($data->avatar)) $this->player->updateAvatar($pid, $data->avatar);
        if (isset($data->title)) $this->player->updateTitle($pid, $data->title);

        // Envoi de la réponse
        sendJson(["message" => "Profil mis à jour"], 200);
    }

    //Méthode pour changer le mot de passe
    public function changePassword() {
        if (!isset($_SESSION['player_id'])) sendJson(["message" => "Non connecté"], 401);
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];

        $user = $this->player->findById($pid); // Récupère le hash actuel
        // Vérification de l'ancien mot de passe
        if (password_verify($data->old_password, $user['password_hash'])) {
            $newHash = password_hash($data->new_password, PASSWORD_BCRYPT);
            if ($this->player->updatePassword($pid, $newHash)) {
                sendJson(["message" => "Mot de passe modifié"], 200);
            } else {
                sendJson(["message" => "Erreur serveur"], 500);
            }
        } else {
            sendJson(["message" => "Ancien mot de passe incorrect"], 403);
        }
    }
}
?>