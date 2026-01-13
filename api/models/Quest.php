<?php
// Fichier de gestion des quêtes

class Quest {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer les quêtes du joueur
    public function getPlayerQuests($playerId) {
        // Assigner les quêtes quotidiennes
        $this->assignDailyQuests($playerId);

        // Récupérer les données
        $query = "SELECT q.*, pq.current_progress, pq.is_completed, pq.is_claimed
                  FROM quests q
                  JOIN player_quests pq ON q.id = pq.quest_id
                  WHERE pq.player_id = :pid
                  ORDER BY q.type, q.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Réclamer une récompense
    public function claimReward($playerId, $questId) {
        try {
            $this->conn->beginTransaction();

            // Vérifier que la quête est finie mais pas encore réclamée
            $check = $this->conn->prepare("SELECT pq.*, q.reward_type, q.reward_amount 
                                           FROM player_quests pq
                                           JOIN quests q ON pq.quest_id = q.id
                                           WHERE pq.player_id = :pid AND pq.quest_id = :qid FOR UPDATE");
            $check->bindParam(':pid', $playerId);
            $check->bindParam(':qid', $questId);
            $check->execute();
            $quest = $check->fetch(PDO::FETCH_ASSOC);

            if (!$quest) return "Quête introuvable.";
            if (!$quest['is_completed']) return "Quête non terminée.";
            if ($quest['is_claimed']) return "Récompense déjà récupérée.";

            // Donner la récompense
            $rewardType = $quest['reward_type']; // gold, gems, xp
            $amount = $quest['reward_amount'];

            if ($rewardType === 'xp') {
                $updPlayer = $this->conn->prepare("UPDATE players SET xp = xp + :am WHERE id = :pid");
            } else {
                $updPlayer = $this->conn->prepare("UPDATE players SET $rewardType = $rewardType + :am WHERE id = :pid");
            }
            $updPlayer->bindParam(':am', $amount);
            $updPlayer->bindParam(':pid', $playerId);
            $updPlayer->execute();

            // Marquer comme réclamé
            $mark = $this->conn->prepare("UPDATE player_quests SET is_claimed = 1 WHERE player_id = :pid AND quest_id = :qid");
            $mark->bindParam(':pid', $playerId);
            $mark->bindParam(':qid', $questId);
            $mark->execute();

            $this->conn->commit();
            return ["success" => true, "type" => $rewardType, "amount" => $amount];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return $e->getMessage();
        }
    }

    // Assigner les quêtes quotidiennes
    private function assignDailyQuests($playerId) {
        // Assigner les nouvelles quêtes
        $query = "INSERT INTO player_quests (player_id, quest_id, current_progress)
                  SELECT :pid, id, 0 FROM quests 
                  WHERE id NOT IN (SELECT quest_id FROM player_quests WHERE player_id = :pid)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->execute();

        // Reset des Quêtes Quotidiennes (Si updated_at < Aujourd'hui)
        $resetDaily = "UPDATE player_quests pq
                       JOIN quests q ON pq.quest_id = q.id
                       SET pq.current_progress = 0, pq.is_completed = 0, pq.is_claimed = 0, pq.updated_at = NOW()
                       WHERE pq.player_id = :pid 
                       AND q.type = 'daily' 
                       AND DATE(pq.updated_at) < CURDATE()";
        
        $stmtDaily = $this->conn->prepare($resetDaily);
        $stmtDaily->bindParam(':pid', $playerId);
        $stmtDaily->execute();

        // Reset des Quêtes Hebdomadaires (Si updated_at < Cette semaine)
        $resetWeekly = "UPDATE player_quests pq
                        JOIN quests q ON pq.quest_id = q.id
                        SET pq.current_progress = 0, pq.is_completed = 0, pq.is_claimed = 0, pq.updated_at = NOW()
                        WHERE pq.player_id = :pid 
                        AND q.type = 'weekly' 
                        AND YEARWEEK(pq.updated_at, 1) < YEARWEEK(CURDATE(), 1)";

        $stmtWeekly = $this->conn->prepare($resetWeekly);
        $stmtWeekly->bindParam(':pid', $playerId);
        $stmtWeekly->execute();
    }
}
?>