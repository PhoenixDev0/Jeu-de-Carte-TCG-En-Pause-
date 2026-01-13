<?php
// Fichier de gestion des decks

class Deck {
    private $conn;
    private $table = "decks";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau deck vide
    public function create($player_id, $name, $hero_card_id = 1) {
        $query = "INSERT INTO " . $this->table . " (player_id, name, hero_card_id) VALUES (:pid, :name, :hid)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pid', $player_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':hid', $hero_card_id);
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) { return false; }
    }

    // Récupérer les decks d'un joueur
    public function getUserDecks($player_id) {
        $query = "SELECT d.*, (SELECT COALESCE(SUM(quantity), 0) FROM deck_cards WHERE deck_id = d.id) as card_count 
                  FROM " . $this->table . " d 
                  WHERE d.player_id = :pid 
                  ORDER BY d.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $player_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les cartes d'un deck spécifique
    public function getDeckCards($deck_id) {
        $query = "SELECT dc.quantity, c.* FROM deck_cards dc
                  JOIN cards c ON dc.card_id = c.id
                  WHERE dc.deck_id = :did";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':did', $deck_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Sauvegarder le contenu d'un deck
    public function saveCards($deck_id, $cards) {
        try {
            $this->conn->beginTransaction();

            // Vider le deck
            $delQuery = "DELETE FROM deck_cards WHERE deck_id = :did";
            $delStmt = $this->conn->prepare($delQuery);
            $delStmt->bindParam(':did', $deck_id);
            $delStmt->execute();

            // Insérer de nouvelles cartes
            $insQuery = "INSERT INTO deck_cards (deck_id, card_id, quantity) VALUES (:did, :cid, :qty)";
            $insStmt = $this->conn->prepare($insQuery);

            foreach ($cards as $card) {
                $insStmt->bindParam(':did', $deck_id);
                $insStmt->bindParam(':cid', $card['id']);
                $insStmt->bindParam(':qty', $card['quantity']);
                $insStmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Supprimer un deck
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>