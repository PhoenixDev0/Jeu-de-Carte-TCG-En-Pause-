<?php
// Fichier de gestion des quêtes

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Quest.php';
include_once __DIR__ . '/../models/Player.php';

class QuestController {
    private $db;
    private $quest;
    private $player;

    public function __construct($db) {
        $this->db = $db;
        $this->quest = new Quest($db);
        $this->player = new Player($db);
    }

    // Récupère les quêtes du joueur
    public function getQuests() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $pid = $_SESSION['player_id'];

        $quests = $this->quest->getPlayerQuests($pid);
        $user = $this->player->findById($pid);
        
        // Gestion des quêtes passives
        $currentLevel = $user['level'];
        $claimedLevel = isset($user['claimed_pass_level']) ? $user['claimed_pass_level'] : 0;
        
        $passTrack = [];
        
        // Création du tableau des quêtes passives
        for ($i = 1; $i <= 50; $i++) {
            $isUnlocked = ($currentLevel >= $i);
            $isClaimed = ($claimedLevel >= $i);
            
            // Réclamation possible si le niveau est débloqué et non réclamé et que le niveau précédent est réclamé
            $canClaim = ($isUnlocked && !$isClaimed && ($claimedLevel == $i - 1));
            
            // Gestion des statuts
            $status = 'locked';
            if ($isClaimed) $status = 'claimed';
            else if ($canClaim) $status = 'ready';
            else if ($isUnlocked) $status = 'pending';

            $passTrack[] = [
                "level" => $i,
                "free_reward" => ($i % 10 == 0) ? "Booster Extension" : "Rien",
                "premium_reward" => "Booster Standard",
                "status" => $status
            ];
        }

        $data = [
            "daily" => array_filter($quests, function($q) { return $q['type'] === 'daily'; }),
            "weekly" => array_filter($quests, function($q) { return $q['type'] === 'weekly'; }),
            "pass" => [
                "current_level" => $currentLevel,
                "claimed_level" => $claimedLevel,
                "is_premium" => (bool)$user['is_premium'],
                "track" => $passTrack
            ]
        ];

        sendJson($data, 200);
    }

    // Réclamation d'une récompense du pass
    public function claimPassReward() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $data = json_decode(file_get_contents("php://input"));
        $targetLevel = $data->level;
        $pid = $_SESSION['player_id'];

        $user = $this->player->findById($pid);
        $currentLevel = $user['level'];
        $claimedLevel = isset($user['claimed_pass_level']) ? $user['claimed_pass_level'] : 0;

        if ($targetLevel > $currentLevel) sendJson(["message" => "Niveau pas encore atteint !"], 400);
        if ($targetLevel <= $claimedLevel) sendJson(["message" => "Déjà récupéré."], 400);
        if ($targetLevel != $claimedLevel + 1) sendJson(["message" => "Récupérez les récompenses dans l'ordre !"], 400);

        try {
            $this->db->beginTransaction();

            $allCards = [];

            if ($user['is_premium']) {
                $cards = $this->giveRandomBooster($pid, 'Standard');
                $allCards = array_merge($allCards, $cards);
            }
            if ($targetLevel % 10 == 0) {
                $cards = $this->giveRandomBooster($pid, 'Extension');
                $allCards = array_merge($allCards, $cards);
            }

            $upd = $this->db->prepare("UPDATE players SET claimed_pass_level = :lvl WHERE id = :pid");
            $upd->bindParam(':lvl', $targetLevel);
            $upd->bindParam(':pid', $pid);
            $upd->execute();

            $this->db->commit();
            sendJson(["message" => "Niveau $targetLevel récupéré !", "cards" => $allCards], 200);

        } catch (Exception $e) {
            $this->db->rollBack();
            sendJson(["message" => "Erreur : " . $e->getMessage()], 500);
        }
    }

    // Donne 5 cartes aléatoires
    private function giveRandomBooster($pid, $type) {
        $cardsGiven = [];
        for($i=0; $i<5; $i++) {
            $stmt = $this->db->prepare("SELECT * FROM cards ORDER BY RAND() LIMIT 1");
            $stmt->execute();
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($card) {
                $ins = $this->db->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
                $ins->bindParam(':pid', $pid);
                $ins->bindParam(':cid', $card['id']);
                $ins->execute();
                $cardsGiven[] = $card;
            }
        }
        return $cardsGiven;
    }

    // Achat d'un pass premium
    public function buyPremiumPass() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $pid = $_SESSION['player_id'];
        
        $user = $this->player->findById($pid);
        if ($user['is_premium']) sendJson(["message" => "Déjà Premium !"], 400);
        if ($user['gems'] < 950) sendJson(["message" => "Pas assez de gemmes"], 400);
        
        try {
            $this->db->beginTransaction();
            $upd = $this->db->prepare("UPDATE players SET gems = gems - 950, is_premium = 1 WHERE id = :id");
            $upd->bindParam(':id', $pid);
            $upd->execute();

            // Rétroactif
            $nbBoosters = $user['level'];
            $nbCards = $nbBoosters * 5;
            for($i=0; $i < $nbCards; $i++) {
                $stmt = $this->db->prepare("SELECT id FROM cards ORDER BY RAND() LIMIT 1");
                $stmt->execute();
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($card) {
                    $ins = $this->db->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
                    $ins->bindParam(':pid', $pid);
                    $ins->bindParam(':cid', $card['id']);
                    $ins->execute();
                }
            }

            $this->db->commit();
            sendJson(["message" => "Pass activé ! Vous avez reçu $nbBoosters boosters rétroactifs !"], 200);

        } catch (Exception $e) {
            $this->db->rollBack();
            sendJson(["message" => "Erreur transaction"], 500);
        }
    }
    
    // Réclamation d'une quête
    public function claim() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];

        $res = $this->quest->claimReward($pid, $data->quest_id);
        if (is_array($res) && $res['success']) {
            sendJson(["message" => "Récompense récupérée !", "reward" => $res], 200);
        } else {
            sendJson(["message" => $res], 400);
        }
    }
}
?>