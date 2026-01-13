<?php
// Fichier de gestion des échanges

class Trade {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer les amis
    public function getFriends($playerId) {
        $query = "SELECT u.id, u.username, u.avatar, u.title, u.elo, f.status 
                  FROM friendships f
                  JOIN players u ON (f.requester_id = u.id OR f.addressee_id = u.id)
                  WHERE (f.requester_id = :pid OR f.addressee_id = :pid)
                  AND u.id != :pid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $playerId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Envoyer une demande d'ami
    public function sendFriendRequest($requesterId, $friendCode) {
        $stmt = $this->conn->prepare("SELECT id FROM players WHERE friend_code = :code");
        $stmt->bindParam(':code', $friendCode);
        $stmt->execute();
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) return "Code ami invalide.";
        if ($target['id'] == $requesterId) return "Impossible de s'ajouter soi-même.";

        $check = $this->conn->prepare("SELECT id FROM friendships WHERE (requester_id = :r AND addressee_id = :a) OR (requester_id = :a AND addressee_id = :r)");
        $check->bindParam(':r', $requesterId);
        $check->bindParam(':a', $target['id']);
        $check->execute();
        if ($check->rowCount() > 0) return "Déjà en lien avec ce joueur.";

        $ins = $this->conn->prepare("INSERT INTO friendships (requester_id, addressee_id, status) VALUES (:r, :a, 'accepted')");
        $ins->bindParam(':r', $requesterId);
        $ins->bindParam(':a', $target['id']);
        return $ins->execute() ? true : "Erreur SQL.";
    }

    // Supprimer un ami
    public function removeFriend($requesterId, $friendId) {
        $query = "DELETE FROM friendships 
                  WHERE (requester_id = :r AND addressee_id = :a) 
                     OR (requester_id = :a AND addressee_id = :r)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':r', $requesterId);
        $stmt->bindParam(':a', $friendId);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Erreur lors de la suppression.";
        }
    }

    // Récupérer les ventes
    public function getListings() {
        // On récupère les ventes actives
        $query = "SELECT m.*, c.name as card_name, c.image_url, c.rarity, c.cost, c.attack, c.hp, p.username as seller_name
                  FROM market_listings m
                  JOIN cards c ON m.card_id = c.id
                  JOIN players p ON m.seller_id = p.id
                  WHERE m.status = 'active' AND m.type = 'sell'
                  ORDER BY m.created_at DESC LIMIT 100";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Créer une vente
    public function createListing($playerId, $cardId, $price) {
        $check = $this->conn->prepare("SELECT quantity FROM collection WHERE player_id = :pid AND card_id = :cid");
        $check->bindParam(':pid', $playerId);
        $check->bindParam(':cid', $cardId);
        $check->execute();
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['quantity'] < 1) return "Carte non possédée.";

        try {
            $this->conn->beginTransaction();
            
            // Retrait de la collection
            if ($row['quantity'] > 1) {
                $upd = $this->conn->prepare("UPDATE collection SET quantity = quantity - 1 WHERE player_id = :pid AND card_id = :cid");
            } else {
                $upd = $this->conn->prepare("DELETE FROM collection WHERE player_id = :pid AND card_id = :cid");
            }
            $upd->bindParam(':pid', $playerId);
            $upd->bindParam(':cid', $cardId);
            $upd->execute();

            // Création d'une offre
            $ins = $this->conn->prepare("INSERT INTO market_listings (seller_id, card_id, type, price_gold, status) VALUES (:pid, :cid, 'sell', :price, 'active')");
            $ins->bindParam(':pid', $playerId);
            $ins->bindParam(':cid', $cardId);
            $ins->bindParam(':price', $price);
            $ins->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return "Erreur transaction.";
        }
    }

    // Annuler une vente
    public function cancelListing($playerId, $listingId) {
        try {
            $this->conn->beginTransaction();

            // Vérifier que l'offre appartient au joueur et est active
            $stmt = $this->conn->prepare("SELECT card_id FROM market_listings WHERE id = :id AND seller_id = :pid AND status = 'active' FOR UPDATE");
            $stmt->bindParam(':id', $listingId);
            $stmt->bindParam(':pid', $playerId);
            $stmt->execute();
            $listing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$listing) throw new Exception("Offre introuvable ou déjà vendue.");

            // Rendre la carte au joueur
            $addCard = $this->conn->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $addCard->bindParam(':pid', $playerId);
            $addCard->bindParam(':cid', $listing['card_id']);
            $addCard->execute();

            // Marquer l'offre comme annulée
            $close = $this->conn->prepare("UPDATE market_listings SET status = 'cancelled' WHERE id = :id");
            $close->bindParam(':id', $listingId);
            $close->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return $e->getMessage();
        }
    }

    // Acheter une carte
    public function buyListing($buyerId, $listingId) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT * FROM market_listings WHERE id = :id AND status = 'active' FOR UPDATE");
            $stmt->bindParam(':id', $listingId);
            $stmt->execute();
            $listing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$listing) throw new Exception("Offre expirée.");
            if ($listing['seller_id'] == $buyerId) throw new Exception("C'est votre offre.");

            $buyerStmt = $this->conn->prepare("SELECT gold FROM players WHERE id = :id");
            $buyerStmt->bindParam(':id', $buyerId);
            $buyerStmt->execute();
            $buyerGold = $buyerStmt->fetchColumn();

            if ($buyerGold < $listing['price_gold']) throw new Exception("Pas assez d'or.");

            // Paiement
            $payBuyer = $this->conn->prepare("UPDATE players SET gold = gold - :price WHERE id = :id");
            $payBuyer->bindParam(':price', $listing['price_gold']);
            $payBuyer->bindParam(':id', $buyerId);
            $payBuyer->execute();

            $paySeller = $this->conn->prepare("UPDATE players SET gold = gold + :price WHERE id = :id");
            $paySeller->bindParam(':price', $listing['price_gold']);
            $paySeller->bindParam(':id', $listing['seller_id']);
            $paySeller->execute();

            // Transfert
            $addCard = $this->conn->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $addCard->bindParam(':pid', $buyerId);
            $addCard->bindParam(':cid', $listing['card_id']);
            $addCard->execute();

            $close = $this->conn->prepare("UPDATE market_listings SET status = 'sold' WHERE id = :id");
            $close->bindParam(':id', $listingId);
            $close->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $e->getMessage();
        }
    }

    // Le vortex
    public function wonderTrade($playerId, $cardId) {
        try {
            $this->conn->beginTransaction();

            // Vérifier et retirer la carte du joueur
            $check = $this->conn->prepare("SELECT quantity FROM collection WHERE player_id = :pid AND card_id = :cid FOR UPDATE");
            $check->bindParam(':pid', $playerId);
            $check->bindParam(':cid', $cardId);
            $check->execute();
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['quantity'] < 1) throw new Exception("Carte manquante");

            if ($row['quantity'] > 1) {
                $upd = $this->conn->prepare("UPDATE collection SET quantity = quantity - 1 WHERE player_id = :pid AND card_id = :cid");
            } else {
                $upd = $this->conn->prepare("DELETE FROM collection WHERE player_id = :pid AND card_id = :cid");
            }
            $upd->bindParam(':pid', $playerId);
            $upd->bindParam(':cid', $cardId);
            $upd->execute();

            // Mettre la carte du joueur dans le Pool
            $insPool = $this->conn->prepare("INSERT INTO market_listings (seller_id, card_id, type, status) VALUES (:pid, :cid, 'wonder', 'active')");
            $insPool->bindParam(':pid', $playerId);
            $insPool->bindParam(':cid', $cardId);
            $insPool->execute();

            // Chercher une carte dans le Pool (d'un autre joueur)
            $find = $this->conn->prepare("SELECT * FROM market_listings WHERE type = 'wonder' AND status = 'active' AND seller_id != :pid ORDER BY RAND() LIMIT 1 FOR UPDATE");
            $find->bindParam(':pid', $playerId);
            $find->execute();
            $match = $find->fetch(PDO::FETCH_ASSOC);

            $newCardId = null;

            if ($match) {
                // A. On a trouvé un échange (P2P)
                $newCardId = $match['card_id'];

                // Marquer l'offre trouvée comme 'sold'
                $close = $this->conn->prepare("UPDATE market_listings SET status = 'sold' WHERE id = :mid");
                $close->bindParam(':mid', $match['id']);
                $close->execute();

            } else {
                // B. Pas de carte disponible (Pool vide ou que des cartes à moi) : Fallback Système
                $randCard = $this->conn->prepare("SELECT id FROM cards WHERE id != :cid ORDER BY RAND() LIMIT 1");
                $randCard->bindParam(':cid', $cardId);
                $randCard->execute();
                $sysCard = $randCard->fetch(PDO::FETCH_ASSOC);
                $newCardId = $sysCard['id'];
            }

            // Donner la nouvelle carte au joueur
            $add = $this->conn->prepare("INSERT INTO collection (player_id, card_id, quantity) VALUES (:pid, :cid, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $add->bindParam(':pid', $playerId);
            $add->bindParam(':cid', $newCardId);
            $add->execute();

            // Récupérer les infos de la carte gagnée pour l'affichage
            $getInfos = $this->conn->prepare("SELECT * FROM cards WHERE id = :cid");
            $getInfos->bindParam(':cid', $newCardId);
            $getInfos->execute();
            $newCardInfos = $getInfos->fetch(PDO::FETCH_ASSOC);

            $this->conn->commit();
            return $newCardInfos;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>