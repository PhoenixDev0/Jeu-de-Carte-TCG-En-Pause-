<?php
// Fichier de gestion des classements

class RankingController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupère le classement
    public function getLeaderboard() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $pid = $_SESSION['player_id'];
        $type = isset($_GET['type']) ? $_GET['type'] : 'global'; // 'global' ou 'friends'

        if ($type === 'friends') {
            // Classement Amis (Moi + Mes Amis)
            $query = "SELECT p.id, p.username, p.avatar, p.title, p.elo, p.level 
                      FROM players p
                      JOIN friendships f ON (f.requester_id = p.id OR f.addressee_id = p.id)
                      WHERE (f.requester_id = :pid OR f.addressee_id = :pid) 
                      AND f.status = 'accepted'
                      UNION
                      SELECT id, username, avatar, title, elo, level FROM players WHERE id = :pid
                      ORDER BY elo DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pid', $pid);
        } else {
            // Classement Global (Top 50)
            $query = "SELECT id, username, avatar, title, elo, level FROM players ORDER BY elo DESC LIMIT 50";
            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajout du rang (1, 2, 3...)
        foreach ($players as $index => &$player) {
            $player['rank'] = $index + 1;
            $player['is_me'] = ($player['id'] == $pid);
        }

        sendJson(["ranking" => $players], 200);
    }
}
?>