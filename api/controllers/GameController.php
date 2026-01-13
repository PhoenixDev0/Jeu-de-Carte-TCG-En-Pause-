<?php
//Fichier de gestion des parties

// Inclusion des modèles nécessaires :
include_once __DIR__ . '/../models/Game.php';
include_once __DIR__ . '/../models/Deck.php';
include_once __DIR__ . '/../models/Card.php';
include_once __DIR__ . '/../models/cards/CardFactory.php';

class GameController {
    private $db;
    private $game;
    private $deck;
    private $card;

    public function __construct($db) {
        $this->db = $db;
        $this->game = new Game($db);
        $this->deck = new Deck($db);
        $this->card = new Card($db);
    }

    // Lancer une partie d'entraînement
    public function startTraining() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($_SESSION['player_id'])) {
            sendJson(["message" => "Non connecté"], 401);
            return;
        }

        $pid = $_SESSION['player_id'];
        
        // Nettoyer les parties actives précédentes
        $this->game->forfeitAllActiveGames($pid);

        $deckId = $data->deck_id;

        // 1. Récupérer les cartes du deck
        $deckCards = $this->deck->getDeckCards($deckId);
        
        // Validation : compter les cartes
        $totalCards = 0;
        $formattedDeck = [];
        
        foreach ($deckCards as $card) {
            $qty = intval($card['quantity']);
            $totalCards += $qty;
            // Préparer le deck pour le jeu (sous forme de liste d'IDs)
            for ($i = 0; $i < $qty; $i++) {
                $formattedDeck[] = $card['id'];
            }
        }

        // Vérification de la taille du deck
        $MIN_DECK_SIZE = 50;
        $MAX_DECK_SIZE = 50; 

        if ($totalCards < $MIN_DECK_SIZE) {
            sendJson(["message" => "Deck incomplet ! Il faut au moins $MIN_DECK_SIZE cartes."], 400);
            return;
        }
        if ($totalCards > $MAX_DECK_SIZE) {
            sendJson(["message" => "Deck trop grand ! Max $MAX_DECK_SIZE cartes."], 400);
            return;
        }

        // Mélanger le deck
        shuffle($formattedDeck); 

        // 2. Créer la partie
        $gameId = $this->game->createPvE($pid, $formattedDeck);

        // Vérification de la création
        if ($gameId) {
            sendJson(["message" => "Combat commencé !", "game_id" => $gameId], 200);
        } else {
            sendJson(["message" => "Erreur création partie"], 500);
        }
    }

    // Matchmaking pour le PvP

    // Rejoindre la file d'attente
    public function joinQueue() {
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Auth required"], 401); return; }
        
        $pid = $_SESSION['player_id'];
        
        // Nettoyer les parties actives précédentes
        $this->game->forfeitAllActiveGames($pid);

        $deckId = $data->deck_id;
        $mode = $data->mode; 

        // Nettoyer l'ancienne entrée
        $this->game->removeFromQueue($pid);

        // Ajouter à la file
        $this->game->addToQueue($pid, $deckId, $mode);

        // Essayer de trouver un match immédiatement
        $this->tryMatchmaking($mode);

        sendJson(["message" => "Joined queue"], 200);
    }

    // Quitter la file d'attente
    public function leaveQueue() {
        if (!isset($_SESSION['player_id'])) return;
        $this->game->removeFromQueue($_SESSION['player_id']);
        sendJson(["message" => "Left queue"], 200);
    }

    // Vérifier le statut de la file d'attente
    public function checkQueueStatus() {
        if (!isset($_SESSION['player_id'])) return;
        $pid = $_SESSION['player_id'];

        // Vérifier si le joueur est déjà dans une partie active
        $gameId = $this->game->findActiveGameForPlayer($pid);
        
        if ($gameId) {
            sendJson(["status" => "found", "game_id" => $gameId], 200);
        } else {
            // Relancer le matchmaking si on est toujours en attente
            $mode = $this->game->getPlayerQueueMode($pid);
            if ($mode) {
                $this->tryMatchmaking($mode);
                sendJson(["status" => "searching"], 200);
            } else {
                sendJson(["status" => "idle"], 200);
            }
        }
    }

    // Essayer de trouver une partie PvP
    private function tryMatchmaking($mode) {
        // On prend 2 joueurs du même mode de jeu
        $players = $this->game->getQueuePlayers($mode);
        
        if (count($players) >= 2) {
            $p1 = $players[0];
            $p2 = $players[1];

            // On créer la partie et récupère le deck des joueurs
            $d1 = $this->getFormattedDeck($p1['deck_id']);
            $d2 = $this->getFormattedDeck($p2['deck_id']);
            
            if (!$d1 || !$d2) {
                return;
            }

            $gameId = $this->game->createPvP($p1['player_id'], $p2['player_id'], $d1, $d2);
            
            if ($gameId) {
                $this->game->removeFromQueue($p1['player_id']);
                $this->game->removeFromQueue($p2['player_id']);
            }
        }
    }

    // Récupérer le deck formaté
    private function getFormattedDeck($deckId) {
        $deckCards = $this->deck->getDeckCards($deckId);
        $formatted = [];
        foreach ($deckCards as $card) {
            for ($i = 0; $i < intval($card['quantity']); $i++) {
                $formatted[] = $card['id'];
            }
        }
        if (count($formatted) < 50) return null;
        shuffle($formatted);
        return $formatted;
    }


    // On récupère l'état complet du jeu
    public function getState() {
        try {
            $gameId = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$gameId) { sendJson(["message" => "ID manquant"], 400); return; }

            $gameRow = $this->game->getGameState($gameId);
            if (!$gameRow) {
                if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); return; }
                $pid = $_SESSION['player_id'];
                
                $historyRow = $this->game->getRecentGameHistory($pid);
                if ($historyRow) {
                    $iWon = ($historyRow['winner_id'] == $pid);
                    $state = [
                        'status' => 'finished',
                        'i_won' => $iWon,
                        'winner' => $iWon ? 'p1' : 'p2',
                        'p1' => ['hp' => 0],
                        'p2' => ['hp' => 0]
                    ];
                    sendJson(["state" => $state], 200);
                    return;
                }
                
                sendJson(["message" => "Partie introuvable"], 404);
                return;
            }

            $state = json_decode($gameRow['game_state'], true);
            if (!$state) throw new Exception("Erreur décodage JSON state");

            if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); return; }
            $pid = $_SESSION['player_id'];

            // Calculer à qui le tour de jouer
            $isOdd = ($state['turn'] % 2 != 0);
            $originalKey = ($state['p1']['id'] == $pid) ? 'p1' : 'p2';
            $isMyTurn = ($originalKey == 'p1' && $isOdd) || ($originalKey == 'p2' && !$isOdd);
            
            // Enrichissement des cartes
            $state['p1']['hand'] = $this->hydrateCards($state['p1']['hand'], 'hand');
            $state['p1']['board'] = $this->hydrateCards($state['p1']['board'], 'board');
            $state['p2']['board'] = $this->hydrateCards($state['p2']['board'], 'board');
            
            // Comptage des cartes
            $state['p2']['hand_count'] = count($state['p2']['hand']);
            unset($state['p2']['hand']); 
            
            $state['p2']['deck_count'] = count($state['p2']['deck']);
            unset($state['p2']['deck']); 
            
            // Ajout de l'indicateur de tour
            $state['is_my_turn'] = $isMyTurn;

            sendJson(["state" => $state], 200);
        } catch (Exception $e) {
            error_log("getState Error: " . $e->getMessage());
            sendJson(["message" => "Erreur serveur: " . $e->getMessage()], 500);
        }
    }

    // Jouer une carte
    public function playCard() {
        $data = json_decode(file_get_contents("php://input"));
        $this->handleGameAction($data->game_id, function(&$state, $pid) use ($data) {
            $playerKey = ($state['p1']['id'] == $pid) ? 'p1' : 'p2';
            
            $isOdd = ($state['turn'] % 2 != 0);
            $isMyTurn = ($playerKey == 'p1' && $isOdd) || ($playerKey == 'p2' && !$isOdd);

            if (!$isMyTurn) throw new Exception("Ce n'est pas votre tour !");
            
            $cardIndex = -1;
            $cardToPlay = null;
            foreach ($state[$playerKey]['hand'] as $k => $c) {
                if ($c['uid'] == $data->card_uid) {
                    $cardIndex = $k;
                    $cardToPlay = $c;
                    break;
                }
            }

            if (!$cardToPlay) throw new Exception("Carte introuvable en main");

            $cardInfo = $this->card->getCardById($cardToPlay['id']); 
            if ($state[$playerKey]['mana'] < $cardInfo['cost']) throw new Exception("Pas assez de mana");

            // Payer le mana
            $state[$playerKey]['mana'] -= $cardInfo['cost'];
            array_splice($state[$playerKey]['hand'], $cardIndex, 1);
            
            // Instancier la classe de la carte
            $cardObj = CardFactory::create($cardToPlay['id']);

            // Gestion Unité (invocation de base)
            if ($cardInfo['type'] === 'Unit') {
                if (count($state[$playerKey]['board']) >= 8) {
                    throw new Exception("Plateau plein (Max 8 unités) !");
                }

                $boardUnit = [
                    "uid" => $cardToPlay['uid'],
                    "id" => $cardToPlay['id'],
                    "hp" => $cardInfo['hp'],
                    "max_hp" => $cardInfo['hp'],
                    "attack" => $cardInfo['attack'],
                    "can_attack" => false // Toujours false au début, la carte peut modifier via play()
                ];
                $state[$playerKey]['board'][] = $boardUnit;
            }

            // Gestion des cibles
            $targetData = null;
            if (isset($data->target_uid)) {
                $targetData = $this->findTarget($state, $playerKey, $data->target_uid);
            }

            // Exécuter l'effet
            try {
                $logMessage = $cardObj->play($state, $playerKey, $targetData);
            } catch (Exception $e) {
                throw $e; 
            }

            return $logMessage;
        });
    }

    // Attaquer
    public function attack() {
        $data = json_decode(file_get_contents("php://input"));
        $this->handleGameAction($data->game_id, function(&$state, $pid) use ($data) {
            $playerKey = ($state['p1']['id'] == $pid) ? 'p1' : 'p2';
            $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
            
            $isOdd = ($state['turn'] % 2 != 0);
            $isMyTurn = ($playerKey == 'p1' && $isOdd) || ($playerKey == 'p2' && !$isOdd);
            if (!$isMyTurn) throw new Exception("Ce n'est pas votre tour !");

            $attacker = null;
            $attIndex = -1;
            foreach ($state[$playerKey]['board'] as $k => &$u) {
                if ($u['uid'] == $data->attacker_uid) {
                    $attacker = &$u;
                    $attIndex = $k;
                    break;
                }
            }
            if (!$attacker) throw new Exception("Attaquant introuvable");
            
            if (isset($attacker['can_attack']) && !$attacker['can_attack']) throw new Exception("Cette unité ne peut pas attaquer");
            if (isset($attacker['frozen']) && $attacker['frozen']) throw new Exception("Unité gelée !");
            
            // Gestion de la confusion (Kurenai)
            $targetUid = $data->target_uid;
            $isConfused = false;
            
            if (isset($attacker['confused']) && $attacker['confused'] === true) {
                if (rand(0, 1) == 1) {
                    $isConfused = true;
                    $allUnits = [];
                    foreach($state[$playerKey]['board'] as $idx => $u) $allUnits[] = ['key' => $playerKey, 'uid' => $u['uid'], 'idx' => $idx];
                    foreach($state[$oppKey]['board'] as $idx => $u) $allUnits[] = ['key' => $oppKey, 'uid' => $u['uid'], 'idx' => $idx];
                    
                    if (!empty($allUnits)) {
                         $rnd = $allUnits[array_rand($allUnits)];
                         $targetUid = $rnd['uid'];
                    }
                }
            }

            // Identifier la cible
            $targetType = 'unknown';
            $targetUnit = null;
            $targetIndex = -1;
            
            if ($targetUid == 'hero_enemy') {
                $targetType = 'hero';
            } else {
                foreach ($state[$oppKey]['board'] as $k => &$u) {
                    if ($u['uid'] == $targetUid) {
                        $targetUnit = &$u;
                        $targetType = 'unit';
                        $targetIndex = $k;
                        break;
                    }
                }
                if (!$targetUnit && $isConfused) {
                    foreach ($state[$playerKey]['board'] as $k => &$u) {
                        if ($u['uid'] == $targetUid) {
                            $targetUnit = &$u;
                            $targetType = 'unit';
                            $targetIndex = $k;
                            break;
                        }
                    }
                }
            }
            
            if ($targetType == 'unknown') throw new Exception("Cible invalide");

            // Vérifier Taunt
            if (!$isConfused) {
                $hasTaunt = false;
                foreach ($state[$oppKey]['board'] as $u) {
                    if (isset($u['taunt']) && $u['taunt'] === true && !isset($u['stealth'])) {
                        $hasTaunt = true;
                        break;
                    }
                }
                
                if ($hasTaunt) {
                    if ($targetType == 'hero') throw new Exception("Vous devez attaquer l'unité avec Provocation !");
                    if ($targetType == 'unit' && (!isset($targetUnit['taunt']) || !$targetUnit['taunt'])) {
                        throw new Exception("Vous devez attaquer l'unité avec Provocation !");
                    }
                }
            }
            
            // Résolution
            $log = "";
            $atkCard = CardFactory::create($attacker['id']);
            
            if ($targetType == 'hero') {
                $damage = $attacker['attack'];
                if (isset($state[$oppKey]['armor']) && $state[$oppKey]['armor'] > 0) {
                     $armorBlock = min($state[$oppKey]['armor'], $damage);
                     $state[$oppKey]['armor'] -= $armorBlock;
                     $damage -= $armorBlock;
                }
                
                $state[$oppKey]['hp'] -= $damage;
                $log = $attacker['name'] . " attaque le héros (-" . $attacker['attack'] . " PV)";
            } else {
                if (!$targetUnit) throw new Exception("Erreur technique cible");
                
                $defCard = CardFactory::create($targetUnit['id']);
                
                $atkDmg = $attacker['attack'];
                if (isset($targetUnit['divine_shield']) && $targetUnit['divine_shield']) {
                    $targetUnit['divine_shield'] = false;
                    $atkDmg = 0;
                }
                
                $defDmg = $targetUnit['attack'];
                if (isset($attacker['divine_shield']) && $attacker['divine_shield']) {
                    $attacker['divine_shield'] = false;
                    $defDmg = 0;
                }
                
                if ($atkDmg > 0 && isset($targetUnit['armor']) && $targetUnit['armor'] > 0) {
                     $block = min($targetUnit['armor'], $atkDmg);
                     $targetUnit['armor'] -= $block;
                     $atkDmg -= $block;
                }
                
                if ($defDmg > 0 && isset($attacker['armor']) && $attacker['armor'] > 0) {
                     $block = min($attacker['armor'], $defDmg);
                     $attacker['armor'] -= $block;
                     $defDmg -= $block;
                }

                $targetUnit['hp'] -= $atkDmg;
                $attacker['hp'] -= $defDmg;
                
                $atkCard->onAttack($state, $attacker, $targetUnit, $atkDmg);
                $defCard->onDefend($state, $attacker, $targetUnit, $defDmg);

                $log = "Combat : " . $attacker['name'] . " vs " . $targetUnit['name'];
                
                if ($targetUnit['hp'] <= 0) {
                    $this->handleDeathrattle($state, $targetUnit, ($targetUnit['original_owner'] ?? $oppKey));
                    array_splice($state[$oppKey]['board'], $targetIndex, 1);
                }

                if ($attacker['hp'] <= 0) {
                     $this->handleDeathrattle($state, $attacker, $playerKey);
                     array_splice($state[$playerKey]['board'], $attIndex, 1);
                }
            }

            if ($attacker['hp'] > 0) { 
                $attacker['can_attack'] = false;
                $attacker['stealth'] = false; 
            }

            return $log;
        });
    }

    // Fin de tour
    public function endTurn() {
        $data = json_decode(file_get_contents("php://input"));
        $this->handleGameAction($data->game_id, function(&$state, $pid) {
            $playerKey = ($state['p1']['id'] == $pid) ? 'p1' : 'p2';
            $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';

            $isOdd = ($state['turn'] % 2 != 0);
            $isMyTurn = ($playerKey == 'p1' && $isOdd) || ($playerKey == 'p2' && !$isOdd);
            if (!$isMyTurn) throw new Exception("Ce n'est pas votre tour !");
            
            $this->handleEndTurnEffects($state, $playerKey);

            $state['turn'] += 1;
            
            $this->applyStartOfTurnEffects($state, $oppKey);
            
            if ($state[$oppKey]['max_mana'] < 10) $state[$oppKey]['max_mana'] += 1;
            $state[$oppKey]['mana'] = $state[$oppKey]['max_mana'];
            
            $state = $this->game->drawCards($state, $oppKey, 1);
            
            foreach ($state[$oppKey]['board'] as &$u) {
                if (isset($u['frozen']) && $u['frozen']) {
                    $u['frozen'] = false;
                    $u['can_attack'] = false; 
                } else {
                    $u['can_attack'] = true;
                }
                
                if (isset($u['prochain_tour_confus'])) {
                    unset($u['prochain_tour_confus']);
                } elseif (isset($u['confused'])) {
                    unset($u['confused']);
                }
            }
            
            return "Fin du tour.";
        });
    }

    // Abandonner
    public function surrender() {
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($_SESSION['player_id'])) return;
        
        $pid = $_SESSION['player_id'];
        $gameId = $data->game_id;
        
        $gameRow = $this->game->getGameState($gameId);
        if (!$gameRow) return;
        
        $jsonState = json_decode($gameRow['game_state'], true);
        $winnerKey = ($jsonState['p1']['id'] == $pid) ? 'p2' : 'p1'; 
        
        $this->game->archiveGame($gameId, $winnerKey);
        sendJson(["message" => "Abandon enregistré"], 200);
    }

    // --- Helpers Privés ---

    private function handleGameAction($gameId, $callback) {
        if (!isset($_SESSION['player_id'])) { sendJson(["message" => "Non connecté"], 401); return; }
        $pid = $_SESSION['player_id'];

        $gameRow = $this->game->getGameState($gameId);
        if (!$gameRow || $gameRow['status'] != 'active') {
            sendJson(["message" => "Partie non active"], 400); return; 
        }

        $state = json_decode($gameRow['game_state'], true);
        
        try {
            $log = $callback($state, $pid); // Passage par référence
            
            if ($state['p1']['hp'] <= 0) {
                $this->game->archiveGame($gameId, 'p2');
                sendJson(["message" => "Partie terminée (Défaite)", "game_over" => true], 200);
                return;
            }
            if ($state['p2']['hp'] <= 0) {
                $this->game->archiveGame($gameId, 'p1');
                sendJson(["message" => "Partie terminée (Victoire !)", "game_over" => true], 200);
                return;
            }
            
             $isOdd = ($state['turn'] % 2 != 0);
             if (!$isOdd && $state['p2']['id'] === 'ai') {
                 $state = $this->runAITurn($state, $gameId);
             }
            
            $this->game->updateGameState($gameId, $state);
            sendJson(["message" => $log, "state" => $state], 200); // Send raw state, front normalizes

        } catch (Exception $e) {
            sendJson(["message" => $e->getMessage()], 400);
        }
    }
    
    // Obsolète si on renvoie tout
    private function normalizeStateForPlayer($state, $pid) {
        return $state;
    }

    private function hydrateCards($cardList, $zone) {
        $hydrated = [];
        foreach ($cardList as $c) {
            $info = $this->card->getCardById($c['id']);
            if ($info) {
                $merged = array_merge($info, $c);
                $hydrated[] = $merged;
            }
        }
        return $hydrated;
    }
    
    private function handleEndTurnEffects(&$state, $playerKey) {
        foreach ($state[$playerKey]['board'] as $u) {
            $cardObj = CardFactory::create($u['id']);
            $cardObj->onEndTurn($state, $u, $playerKey);
        }
        
        $unitsToReturn = [];
        foreach ($state[$playerKey]['board'] as $k => $unit) {
            if (isset($unit['return_at_eot']) && $unit['return_at_eot'] === true) {
                $unitsToReturn[] = $k;
            }
        }
        
        rsort($unitsToReturn);
        foreach ($unitsToReturn as $idx) {
            $state[$playerKey]['board'][$idx]['can_attack'] = false;
            $owner = $state[$playerKey]['board'][$idx]['original_owner'] ?? (($playerKey == 'p1') ? 'p2' : 'p1');
            unset($state[$playerKey]['board'][$idx]['return_at_eot']);
            unset($state[$playerKey]['board'][$idx]['original_owner']);
            
            $unitToTransfer = $state[$playerKey]['board'][$idx];
            
            array_splice($state[$playerKey]['board'], $idx, 1);
            $state[$owner]['board'][] = $unitToTransfer;
        }

        foreach ($state[$playerKey]['board'] as &$unit) {
            if (isset($unit['remove_stealth_at_eot']) && $unit['remove_stealth_at_eot'] === true) {
                $unit['stealth'] = false;
                unset($unit['remove_stealth_at_eot']);
            }
        }
    }

    private function applyStartOfTurnEffects(&$state, $playerKey) {
        foreach ($state[$playerKey]['board'] as $unit) {
             $cardObj = CardFactory::create($unit['id']);
             $cardObj->onStartTurn($state, $unit, $playerKey);
        }

        // Gestion des effets du cimetière
        if (isset($state[$playerKey]['graveyard_timers'])) {
            $livingTimers = [];
            foreach ($state[$playerKey]['graveyard_timers'] as $timer) {
                $timer['turns_remaining'] -= 1;
                if ($timer['turns_remaining'] <= 0) {
                    if (count($state[$playerKey]['board']) < 8) {
                        $cardInfo = $this->card->getCardById($timer['id']);
                        if ($cardInfo) {
                             $newUnit = [
                                "id" => $timer['id'],
                                "uid" => uniqid(),
                                "name" => $cardInfo['name'],
                                "attack" => $cardInfo['attack'], 
                                "hp" => $cardInfo['hp'],    
                                "max_hp" => $cardInfo['hp'],
                                "cost" => $cardInfo['cost'],  
                                "can_attack" => false
                            ];
                            $state[$playerKey]['board'][] = $newUnit;
                        }
                    }
                } else {
                    $livingTimers[] = $timer;
                }
            }
            $state[$playerKey]['graveyard_timers'] = $livingTimers;
        }
    }

    // Gestion des effets de mort
    private function handleDeathrattle(&$state, $unit, $ownerKey) {
        $cardObj = CardFactory::create($unit['id']);
        $cardObj->deathrattle($state, $unit, $ownerKey);
    }

    private function findTarget(&$state, $playerKey, $targetUid) {
        if (!$targetUid) throw new Exception("Veuillez sélectionner une cible.");

        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $boardsToSearch = [];
        
        $boardsToSearch[$playerKey] = &$state[$playerKey]['board'];
        $boardsToSearch[$oppKey] = &$state[$oppKey]['board'];

        foreach ($boardsToSearch as $key => &$board) {
            foreach ($board as $idx => &$u) {
                if ($u['uid'] == $targetUid) {
                    if (isset($u['stealth']) && $u['stealth'] === true && $key != $playerKey) {
                         throw new Exception("Impossible de cibler une unité camouflée.");
                    }
                    
                    // Check against AbstractCard isTargetable
                    $cardObj = CardFactory::create($u['id']);
                    if (!$cardObj->isTargetable($playerKey)) {
                         throw new Exception("Cette cible ne peut pas être visée.");
                    }

                    return ['unit' => &$u, 'key' => $key, 'index' => $idx];
                }
            }
        }
        throw new Exception("Cible invalide ou introuvable.");
    }

    // --- Logique de l'IA ---
    private function runAITurn(&$state, $gameId) {
        $aiKey = 'p2';
        $playerKey = 'p1';
        
        // 1. Jouer des cartes (unités) tant que possible
        $played = true;
        while ($played) {
            $played = false;
            // Trier la main par coût croissant
            usort($state[$aiKey]['hand'], function($a, $b) {
                $cardA = $this->card->getCardById($a['id']);
                $cardB = $this->card->getCardById($b['id']);
                return ($cardA['cost'] ?? 0) - ($cardB['cost'] ?? 0);
            });
            
            foreach ($state[$aiKey]['hand'] as $idx => $cardInHand) {
                $cardInfo = $this->card->getCardById($cardInHand['id']);
                if (!$cardInfo) continue;
                
                // Vérifier le mana et le type (on joue les unités, pas les sorts pour simplifier)
                if ($cardInfo['cost'] <= $state[$aiKey]['mana'] && $cardInfo['type'] === 'Unit') {
                    // Vérifier la place sur le plateau
                    if (count($state[$aiKey]['board']) >= 8) break;
                    
                    // Jouer la carte
                    $state[$aiKey]['mana'] -= $cardInfo['cost'];
                    array_splice($state[$aiKey]['hand'], $idx, 1);
                    
                    $boardUnit = [
                        "uid" => $cardInHand['uid'],
                        "id" => $cardInHand['id'],
                        "name" => $cardInfo['name'],
                        "hp" => $cardInfo['hp'],
                        "max_hp" => $cardInfo['hp'],
                        "attack" => $cardInfo['attack'],
                        "can_attack" => false
                    ];
                    $state[$aiKey]['board'][] = $boardUnit;
                    
                    // Exécuter l'effet de la carte
                    $cardObj = CardFactory::create($cardInHand['id']);
                    try {
                        $cardObj->play($state, $aiKey, null);
                    } catch (Exception $e) {
                        // Ignorer les erreurs de ciblage pour l'IA
                    }
                    
                    $played = true;
                    break; // Recommencer la boucle avec la nouvelle main
                }
            }
        }
        
        // 2. Attaquer avec les unités qui peuvent attaquer
        foreach ($state[$aiKey]['board'] as &$unit) {
            if (!isset($unit['can_attack']) || !$unit['can_attack']) continue;
            if (isset($unit['frozen']) && $unit['frozen']) continue;
            
            // Trouver une cible (priorité: taunt > autres unités > héros)
            $targetUid = null;
            $targetIdx = -1;
            $targetIsHero = false;
            
            // Chercher un taunt d'abord
            foreach ($state[$playerKey]['board'] as $tIdx => $target) {
                if (isset($target['taunt']) && $target['taunt'] && !isset($target['stealth'])) {
                    $targetUid = $target['uid'];
                    $targetIdx = $tIdx;
                    break;
                }
            }
            
            // Si pas de taunt, attaquer le héros
            if (!$targetUid) {
                $targetUid = 'hero_enemy';
                $targetIsHero = true;
            }
            
            // Effectuer l'attaque
            if ($targetIsHero) {
                $state[$playerKey]['hp'] -= $unit['attack'];
            } else {
                // Combat unité vs unité
                $targetUnit = &$state[$playerKey]['board'][$targetIdx];
                $targetUnit['hp'] -= $unit['attack'];
                $unit['hp'] -= $targetUnit['attack'];
                
                // Nettoyer les morts
                if ($targetUnit['hp'] <= 0) {
                    $this->handleDeathrattle($state, $targetUnit, $playerKey);
                    array_splice($state[$playerKey]['board'], $targetIdx, 1);
                }
            }
            
            // L'unité a attaqué
            $unit['can_attack'] = false;
            
            // Vérifier si l'attaquant est mort
            if ($unit['hp'] <= 0) {
                // On ne peut pas supprimer pendant le foreach, on marquera pour après
            }
        }
        
        // Nettoyer les unités mortes de l'IA
        $state[$aiKey]['board'] = array_values(array_filter($state[$aiKey]['board'], function($u) {
            return $u['hp'] > 0;
        }));
        
        // 3. Fin du tour de l'IA - passage au joueur
        $state['turn'] += 1;
        
        // Appliquer les effets de début de tour du joueur
        $this->applyStartOfTurnEffects($state, $playerKey);
        
        // Augmenter le mana du joueur
        if ($state[$playerKey]['max_mana'] < 10) $state[$playerKey]['max_mana'] += 1;
        $state[$playerKey]['mana'] = $state[$playerKey]['max_mana'];
        
        // Piocher une carte pour le joueur
        $state = $this->game->drawCards($state, $playerKey, 1);
        
        // Réinitialiser les unités du joueur
        foreach ($state[$playerKey]['board'] as &$u) {
            if (isset($u['frozen']) && $u['frozen']) {
                $u['frozen'] = false;
                $u['can_attack'] = false;
            } else {
                $u['can_attack'] = true;
            }
        }
        
        return $state;
    }
}