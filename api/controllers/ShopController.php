<?php
// Fichier de gestion du shop

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Card.php';
include_once __DIR__ . '/../models/Player.php';

class ShopController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Achat d'un booster
    public function buyBooster() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['player_id'])) {
            $this->sendJson(["message" => "Non connecté"], 401);
        }

        $data = json_decode(file_get_contents("php://input"));
        $playerId = $_SESSION['player_id'];
        $type = $data->type; // 'standard' ou 'premium'
        $currency = $data->currency; // 'gold' ou 'gems'

        // Définir les prix
        $cost = 0;
        if ($type === 'standard') {
            $cost = ($currency === 'gold') ? 100 : 50;
        } else if ($type === 'premium') {
            $cost = 200; // Gemmes uniquement
            if ($currency === 'gold') $this->sendJson(["message" => "Premium en Gemmes uniquement"], 400);
        }

        try {
            $this->db->beginTransaction();

            // Vérifier et Débiter le joueur
            $stmt = $this->db->prepare("SELECT gold, gems FROM players WHERE id = :id FOR UPDATE");
            $stmt->bindParam(':id', $playerId);
            $stmt->execute();
            $player = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($player[$currency] < $cost) {
                $this->db->rollBack();
                $this->sendJson(["message" => "Fonds insuffisants"], 402);
            }

            $newBalance = $player[$currency] - $cost;
            $update = $this->db->prepare("UPDATE players SET $currency = :bal WHERE id = :id");
            $update->bindParam(':bal', $newBalance);
            $update->bindParam(':id', $playerId);
            $update->execute();

            // Générer 5 cartes (aléatoirement)
            $cards = [];
            for ($i = 0; $i < 5; $i++) {
                // Garantie Rare+ pour le Premium
                $minRarity = ($type === 'premium') ? 'Rare' : 'Common';
                $cards[] = $this->drawRandomCard($minRarity);
            }

            // Ajouter à la collection
            $insert = $this->db->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            
            foreach ($cards as $card) {
                $insert->bindParam(':pid', $playerId);
                $insert->bindParam(':cid', $card['id']);
                $insert->execute();
            }

            $this->db->commit();
            
            // On renvoie les cartes tirées et le nouveau solde pour l'UI
            $this->sendJson([
                "cards" => $cards, 
                "newBalance" => $newBalance, 
                "currency" => $currency
            ], 200);

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->sendJson(["message" => "Erreur transaction: " . $e->getMessage()], 500);
        }
    }

    // Algorithme de Drop pondéré
    private function drawRandomCard($minRarity = 'Common') {
        $rand = mt_rand(1, 1000); // 0.1% précision
        $rarity = 'Common';

        if ($minRarity === 'Rare') {
            // Probabilité des cartes pour le Premium
            if ($rand > 950) $rarity = 'Mythic';      // 5%
            elseif ($rand > 850) $rarity = 'Legendary'; // 10%
            elseif ($rand > 600) $rarity = 'Epic';      // 25%
            else $rarity = 'Rare';                      // 60%
        } else {
            // Probabilité des cartes pour le Standard
            if ($rand > 999) $rarity = 'Mythic';      // 0.1%
            elseif ($rand > 980) $rarity = 'Legendary'; // 1.9%
            elseif ($rand > 900) $rarity = 'Epic';      // 8%
            elseif ($rand > 700) $rarity = 'Rare';      // 20%
            else $rarity = 'Common';                    // 70%
        }

        // Récupérer une carte aléatoire de cette rareté
        $stmt = $this->db->prepare("SELECT * FROM cards WHERE rarity = :r ORDER BY RAND() LIMIT 1");
        $stmt->bindParam(':r', $rarity);
        $stmt->execute();
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card && $rarity !== 'Common') {
            return $this->drawRandomCard('Common'); // Fallback de sécurité
        }
        return $card;
    }

    // Envoi d'une réponse JSON
    private function sendJson($data, $code = 200) {
        http_response_code($code);
        header("Content-Type: application/json");
        echo json_encode($data);
        exit();
    }
}
?>