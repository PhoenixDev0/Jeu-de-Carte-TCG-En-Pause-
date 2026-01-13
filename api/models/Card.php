<?php
// Fichier de gestion des cartes

class Card {
    private $conn;
    private $table = "cards";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupère toutes les cartes et la quantité possédée par le joueur
    public function getAllWithUserQuantity($player_id) {
        $query = "SELECT cards.*, COALESCE(col.quantity, 0) as user_quantity 
                  FROM " . $this->table . " 
                  LEFT JOIN collection col ON cards.id = col.card_id AND col.player_id = :player_id
                  ORDER BY cards.cost ASC, cards.name ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':player_id', $player_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    // Récupère une carte par son ID
    public function getCardById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>