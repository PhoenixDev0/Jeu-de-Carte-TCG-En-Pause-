<?php
// Fichier de gestion des amis et du marché

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Trade.php';
include_once __DIR__ . '/../models/Card.php';

class SocialController {
    private $db;
    private $trade;
    private $card;

    public function __construct($db) {
        $this->db = $db;
        $this->trade = new Trade($db);
        $this->card = new Card($db);
    }

    // Récupération des amis et du marché
    public function getData() {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); }
        $pid = $_SESSION['player_id'];

        $friends = $this->trade->getFriends($pid);
        $market = $this->trade->getListings();
        $inventory = $this->card->getAllWithUserQuantity($pid);

        sendJson([
            "friends" => $friends,
            "market" => $market,
            "inventory" => $inventory
        ], 200);
    }

    // Ajout d'un ami
    public function addFriend() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        $res = $this->trade->sendFriendRequest($pid, $data->friend_code);
        if ($res === true) sendJson(["message" => "Ami ajouté !"], 200);
        else sendJson(["message" => $res], 400);
    }

    // Retrait d'un ami
    public function removeFriend() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        
        if (!isset($data->friend_id)) {
            sendJson(["message" => "ID ami manquant"], 400);
            return;
        }

        $res = $this->trade->removeFriend($pid, $data->friend_id);
        if ($res === true) sendJson(["message" => "Ami retiré"], 200);
        else sendJson(["message" => $res], 500);
    }

    // Vente d'une carte
    public function sellCard() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        $res = $this->trade->createListing($pid, $data->card_id, $data->price);
        if ($res === true) sendJson(["message" => "En vente !"], 200);
        else sendJson(["message" => $res], 500);
    }

    // Annuler une vente
    public function cancelSale() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        $res = $this->trade->cancelListing($pid, $data->listing_id);
        if ($res === true) sendJson(["message" => "Vente annulée, carte récupérée !"], 200);
        else sendJson(["message" => $res], 400);
    }

    // Achat d'une carte
    public function buyCard() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        $res = $this->trade->buyListing($pid, $data->listing_id);
        if ($res === true) sendJson(["message" => "Achat confirmé !"], 200);
        else sendJson(["message" => $res], 400);
    }

    // Échange Miracle
    public function wonderTrade() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        $newCard = $this->trade->wonderTrade($pid, $data->card_id);
        if ($newCard) sendJson(["message" => "Échange réussi !", "new_card" => $newCard], 200);
        else sendJson(["message" => "Erreur Vortex"], 500);
    }
}
?>