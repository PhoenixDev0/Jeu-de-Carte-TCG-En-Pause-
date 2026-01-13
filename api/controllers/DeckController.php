<?php
//Fichier de gestion des decks

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Card.php';
include_once __DIR__ . '/../models/Deck.php';

class DeckController {
    private $db;
    private $card;
    private $deck;

    public function __construct($db) {
        $this->db = $db;
        $this->card = new Card($db);
        $this->deck = new Deck($db);
    }

    // Récupère la collection complète et les decks du joueur
    public function getCollectionData() {
        if (!isset($_SESSION['player_id'])) {
            sendJson(["message" => "Non connecté"], 401);
        }
        $pid = $_SESSION['player_id'];

        $cards = $this->card->getAllWithUserQuantity($pid);
        $decks = $this->deck->getUserDecks($pid);

        sendJson([
            "cards" => $cards,
            "decks" => $decks
        ], 200);
    }

    // Récupère le contenu d'un deck précis pour l'éditer
    public function getDeckContent() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if (!$id) sendJson(["message" => "ID manquant"], 400);

        $cards = $this->deck->getDeckCards($id);
        sendJson(["cards" => $cards], 200);
    }

    // Crée un deck
    public function createDeck() {
        $data = json_decode(file_get_contents("php://input"));
        $pid = $_SESSION['player_id'];
        
        $newId = $this->deck->create($pid, $data->name);
        if ($newId) {
            sendJson(["message" => "Deck créé", "id" => $newId], 201);
        } else {
            sendJson(["message" => "Erreur création"], 500);
        }
    }

    // Sauvegarde le deck
    public function saveDeck() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validation : Max 50 cartes dans le deck
        $total = 0;
        foreach ($data['cards'] as $c) {
            $total += intval($c['quantity']);
        }

        if ($total > 50) {
            sendJson(["message" => "Limite de deck dépassée (Max 50 cartes)"], 400);
            return;
        }

        if ($this->deck->saveCards($data['deck_id'], $data['cards'])) {
            sendJson(["message" => "Deck sauvegardé !"], 200);
        } else {
            sendJson(["message" => "Erreur sauvegarde"], 500);
        }
    }
    
    // Supprime un deck
    public function deleteDeck() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if($this->deck->delete($id)) {
            sendJson(["message" => "Deck supprimé"], 200);
        } else {
            sendJson(["message" => "Erreur suppression"], 500);
        }
    }
}
?>