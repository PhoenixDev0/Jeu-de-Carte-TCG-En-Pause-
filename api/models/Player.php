<?php
// Fichier de gestion des joueurs

class Player {
    private $conn;
    private $table = "players";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau joueur
    public function create($username, $email, $password) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $friend_code = substr($username, 0, 10) . '#' . rand(1000, 9999);
        $query = "INSERT INTO " . $this->table . " (username, email, password_hash, friend_code) VALUES (:username, :email, :password_hash, :friend_code)";
        try {
            $stmt = $this->conn->prepare($query);
            $username = htmlspecialchars(strip_tags($username));
            $email = htmlspecialchars(strip_tags($email));
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':friend_code', $friend_code);
            return $stmt->execute() ? true : "Erreur inconnue.";
        } catch(PDOException $e) { return "ERREUR SQL : " . $e->getMessage(); }
    }
    
    // Vérifier si un email existe
    public function emailExists($email) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return false; }
    }

    // Vérifier si un nom d'utilisateur existe
    public function usernameExists($username) {
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) { return false; }
    }

    // Trouver un joueur par ID
    public function findById($id) {
        $query = "SELECT id, username, email, friend_code, avatar, title, gold, gems, level, xp, elo, is_premium, wins, losses, created_at, password_hash, claimed_pass_level
                  FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ajouter de l'XP
    public function addXp($id, $amount) {
        $user = $this->findById($id);
        if (!$user) return false;
        $currentLevel = $user['level'];
        $currentXp = $user['xp'];
        $newXp = $currentXp + $amount;
        $xpThreshold = $currentLevel * 1000;
        while ($newXp >= $xpThreshold) {
            $newXp -= $xpThreshold;
            $currentLevel++;
            $xpThreshold = $currentLevel * 1000;
        }
        $query = "UPDATE " . $this->table . " SET level = :level, xp = :xp WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':level', $currentLevel);
        $stmt->bindParam(':xp', $newXp);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) return ["level" => $currentLevel, "xp" => $newXp, "leveled_up" => ($currentLevel > $user['level'])];
        return false;
    }

    // Récupérer les items débloqués
    public function getUnlockables($playerId, $type) {
        $query = "SELECT item_value FROM player_unlockables WHERE player_id = :pid AND item_type = :type";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Mettre à jour l'avatar
    public function updateAvatar($id, $avatar) {
        $query = "UPDATE " . $this->table . " SET avatar = :avatar WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':avatar', $avatar);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Mettre à jour le titre
    public function updateTitle($id, $title) {
        $query = "UPDATE " . $this->table . " SET title = :title WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Mettre à jour le mot de passe
    public function updatePassword($id, $newHash) {
        $query = "UPDATE " . $this->table . " SET password_hash = :hash WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $newHash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>