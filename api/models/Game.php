<?php
// Fichier de gestion des parties PvP

class Game {
    private $conn;
    private $table = "game_history";
    private $active_table = "active_games";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupère les 5 derniers matchs du joueur
    public function getHistory($playerId) {
        $query = "SELECT gh.*, 
                         p1.username as p1_name, p1.avatar as p1_avatar,
                         p2.username as p2_name, p2.avatar as p2_avatar
                  FROM " . $this->table . " gh
                  LEFT JOIN players p1 ON gh.player_1_id = p1.id
                  LEFT JOIN players p2 ON gh.player_2_id = p2.id
                  WHERE gh.player_1_id = :pid OR gh.player_2_id = :pid
                  ORDER BY gh.played_at DESC
                  LIMIT 5";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pid', $playerId);
            $stmt->execute();
            
            $history = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Déterminer le résultat
                $isWinner = ($row['winner_id'] == $playerId);
                
                // Si P2 est NULL => IA
                if ($row['player_2_id'] === null) {
                    $resultLabel = $isWinner ? "VICTOIRE" : "DÉFAITE";
                } else {
                    // PvP
                    if ($row['winner_id'] === null) {
                        $resultLabel = "EGALITÉ";
                    } else {
                        $resultLabel = $isWinner ? "VICTOIRE" : "DÉFAITE";
                    }
                }

                // Déterminer qui est l'adversaire
                if ($row['player_1_id'] == $playerId) {
                    $opponentName = $row['p2_name'];
                    $opponentAvatar = $row['p2_avatar'];
                } else {
                    $opponentName = $row['p1_name'];
                    $opponentAvatar = $row['p1_avatar'];
                }

                // Ajouter à l'historique
                $history[] = [
                    "result" => $resultLabel,
                    "opponent" => $opponentName ? $opponentName : "IA d'Entraînement",
                    "opponent_avatar" => $opponentAvatar,
                    "duration" => $this->formatDuration($row['duration_seconds']),
                    "date" => $row['played_at']
                ];
            }
            return $history;
        } catch(PDOException $e) {
            return [];
        }
    }

    // Formater la durée
    private function formatDuration($seconds) {
        $m = floor($seconds / 60);
        $s = $seconds % 60;
        return sprintf("%02d:%02d", $m, $s);
    }

    // Partie active :

    // Créer une nouvelle partie contre l'IA
    public function createPvE($playerId, $playerDeck) {
        // Initialiser l'état du jeu
        $initialState = [
            "turn" => 1,
            "phase" => "main", // start, main ou end
            "p1" => [
                "id" => $playerId,
                "hp" => 30,
                "mana" => 1,
                "max_mana" => 1,
                "hand" => [],
                "deck" => $playerDeck, // Liste des IDs des cartes
                "board" => [], // Unités posées
                "graveyard" => []
            ],
            "p2" => [
                "id" => "ai",
                "username" => "Maître IA",
                "avatar" => "ai_avatar.png",
                "hp" => 30,
                "mana" => 1,
                "max_mana" => 1,
                "hand" => [],
                "deck" => $this->generateAIDeck(), // Deck aléatoire
                "board" => [],
                "graveyard" => []
            ]
        ];

        // Piocher les mains de départ (3 cartes)
        $initialState = $this->drawCards($initialState, 'p1', 3);
        $initialState = $this->drawCards($initialState, 'p2', 3);

        // Sauvegarder en BDD
        $json = json_encode($initialState);
        $query = "INSERT INTO " . $this->active_table . " (player_1_id, game_state, status) VALUES (:pid, :state, 'active')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->bindParam(':state', $json);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Récupérer l'état d'une partie
    public function getGameState($gameId) {
        $query = "SELECT * FROM " . $this->active_table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $gameId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mettre à jour l'état d'une partie
    public function updateGameState($gameId, $newState) {
        $json = json_encode($newState);
        if ($json === false) {
            error_log("JSON Encode Error in updateGameState: " . json_last_error_msg());
            return false;
        }

        $query = "UPDATE " . $this->active_table . " SET game_state = :state WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':state', $json);
        $stmt->bindParam(':id', $gameId);
        
        $res = $stmt->execute();
        if (!$res) {
            $err = $stmt->errorInfo();
            error_log("SQL Error in updateGameState: " . print_r($err, true));
        }
        return $res;
    }

    // Archiver une partie
    public function archiveGame($gameId, $winnerId) {
        $query = "SELECT player_1_id, player_2_id, created_at, mode FROM active_games 
                  LEFT JOIN matchmaking_queue ON active_games.player_1_id = matchmaking_queue.player_id 
                  WHERE active_games.id = :id";
        
        $stmt = $this->conn->prepare("SELECT player_1_id, player_2_id, created_at FROM active_games WHERE id = :id");
        $stmt->bindParam(':id', $gameId);
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) return false;

        $p1 = $game['player_1_id'];
        $p2 = $game['player_2_id']; // Peut être NULL (PvE)
        
        // Déterminer le vainqueur (par l'ID)
        $wId = null;
        if ($winnerId === 'p1') $wId = $p1;
        else if ($winnerId === 'p2' && $p2 !== null) $wId = $p2;

        // Mise à jour des stats P1
        if ($winnerId === 'p1') $this->updatePlayerStats($p1, 'win');
        else $this->updatePlayerStats($p1, 'loss');

        // Mise à jour des stats P2 (si humain)
        if ($p2 !== null) {
            if ($winnerId === 'p2') $this->updatePlayerStats($p2, 'win');
            else $this->updatePlayerStats($p2, 'loss');
        }

        // Insertion dans l'historique
        $duration = time() - strtotime($game['created_at']);
        
        $queryHist = "INSERT INTO game_history (player_1_id, player_2_id, winner_id, duration_seconds) 
                      VALUES (:p1, :p2, :win, :dur)";
        $stmtHist = $this->conn->prepare($queryHist);
        $stmtHist->bindParam(':p1', $p1);
        $stmtHist->bindParam(':p2', $p2);
        $stmtHist->bindParam(':win', $wId);
        $stmtHist->bindParam(':dur', $duration);
        
        $res = $stmtHist->execute();

        if ($res) {
            // Supprimer de la liste de parties active
            $del = $this->conn->prepare("DELETE FROM active_games WHERE id = :id");
            $del->bindParam(':id', $gameId);
            $del->execute();
        }
        return $res;
    }

    // Mise à jour des stats d'un joueur
    private function updatePlayerStats($pid, $result) {
        $gold = ($result === 'win') ? 50 : 10;
        $xp = ($result === 'win') ? 100 : 25;
        
        $sql = "UPDATE players SET 
                gold = gold + :gold, 
                xp = xp + :xp, 
                wins = wins + (CASE WHEN :res = 'win' THEN 1 ELSE 0 END),
                losses = losses + (CASE WHEN :res = 'loss' THEN 1 ELSE 0 END)
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':gold', $gold);
        $stmt->bindParam(':xp', $xp);
        $stmt->bindParam(':res', $result);
        $stmt->bindParam(':id', $pid);
        $stmt->execute();
    }

    // Ajouter un joueur à la file d'attente
    public function addToQueue($pid, $deckId, $mode) {
        $query = "INSERT INTO matchmaking_queue (player_id, deck_id, mode) VALUES (:pid, :did, :mode)
                  ON DUPLICATE KEY UPDATE deck_id = :did, mode = :mode, joined_at = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $pid);
        $stmt->bindParam(':did', $deckId);
        $stmt->bindParam(':mode', $mode);
        return $stmt->execute();
    }

    // Supprimer un joueur de la file d'attente
    public function removeFromQueue($pid) {
        $query = "DELETE FROM matchmaking_queue WHERE player_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $pid);
        return $stmt->execute();
    }

    // Récupérer les joueurs dans la file d'attente
    public function getQueuePlayers($mode) {
        $query = "SELECT * FROM matchmaking_queue WHERE mode = :mode ORDER BY joined_at ASC LIMIT 2";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mode', $mode);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer le mode de la file d'attente d'un joueur
    public function getPlayerQueueMode($pid) {
        $query = "SELECT mode FROM matchmaking_queue WHERE player_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $pid);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['mode'] : null;
    }

    // Trouver une partie active pour un joueur
    public function findActiveGameForPlayer($pid) {
        // Cherche une partie où je suis p1 ou p2 ET qui est active
        $query = "SELECT id FROM active_games WHERE (player_1_id = :pid OR player_2_id = :pid) AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $pid);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : null;
    }

    // Créer une partie PvP
    public function createPvP($p1_id, $p2_id, $p1_deck, $p2_deck) {
        // Récupérer les infos des joueurs (Pseudo/Avatar)
        $p1 = $this->getPlayerInfo($p1_id);
        $p2 = $this->getPlayerInfo($p2_id);

        $initialState = [
            "turn" => 1,
            "phase" => "main",
            "p1" => [
                "id" => $p1_id,
                "username" => $p1['username'],
                "avatar" => $p1['avatar'],
                "hp" => 30,
                "mana" => 1,
                "max_mana" => 1,
                "hand" => [],
                "deck" => $p1_deck,
                "board" => [],
                "graveyard" => []
            ],
            "p2" => [
                "id" => $p2_id,
                "username" => $p2['username'],
                "avatar" => $p2['avatar'],
                "hp" => 30,
                "mana" => 1,
                "max_mana" => 1,
                "hand" => [],
                "deck" => $p2_deck,
                "board" => [],
                "graveyard" => []
            ]
        ];

        // Piocher
        $initialState = $this->drawCards($initialState, 'p1', 3);
        $initialState = $this->drawCards($initialState, 'p2', 3);

        // Sauvegarder
        $json = json_encode($initialState);
        $query = "INSERT INTO active_games (player_1_id, player_2_id, game_state, status) VALUES (:p1, :p2, :state, 'active')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':p1', $p1_id);
        $stmt->bindParam(':p2', $p2_id);
        $stmt->bindParam(':state', $json);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Récupérer les infos d'un joueur
    private function getPlayerInfo($pid) {
        $query = "SELECT username, avatar FROM players WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $pid);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Générer un deck AI
    private function generateAIDeck() {
        // Récupérer tous les IDs de cartes disponibles
        $query = "SELECT id FROM cards";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $allCardIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($allCardIds)) return [];

            // Générer un deck de 50 cartes (avec doublons possibles)
            $deck = [];
            for ($i = 0; $i < 50; $i++) {
                $randomIndex = array_rand($allCardIds);
                $deck[] = $allCardIds[$randomIndex];
            }
            return $deck;

        } catch(PDOException $e) {
            return [];
        }
    }

    // Fonction utilitaire pour piocher
    public function drawCards($state, $playerKey, $count) {
        for ($i=0; $i<$count; $i++) {
            if (!empty($state[$playerKey]['deck'])) {
                $cardId = array_shift($state[$playerKey]['deck']);
                // Générer un ID unique pour l'instance de la carte en main (uid) pour différencier deux cartes identiques
                $state[$playerKey]['hand'][] = [
                    "uid" => uniqid(),
                    "id" => $cardId
                ];
            }
        }
        return $state;
    }

    // Forcer l'abandon de toutes les parties actives d'un joueur
    public function forfeitAllActiveGames($playerId) {
        $query = "SELECT id FROM active_games WHERE (player_1_id = :pid OR player_2_id = :pid) AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $del = $this->conn->prepare("DELETE FROM active_games WHERE id = :id");
            $del->bindParam(':id', $row['id']);
            $del->execute();
        }
    }

    // Récupérer une partie récemment archivée
    public function getRecentGameHistory($playerId) {
        // Chercher une partie récemment archivée (dernières 30 secondes)
        $query = "SELECT * FROM game_history 
                  WHERE (player_1_id = :pid OR player_2_id = :pid)
                  AND played_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                  ORDER BY played_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>