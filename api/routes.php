<?php
// Fichier de gestion des routes
include_once './config/database.php';
include_once './utils.php';
include_once './controllers/AuthController.php';
include_once './controllers/DeckController.php';
include_once './controllers/ShopController.php';
include_once './controllers/SocialController.php';
include_once './controllers/QuestController.php';
include_once './controllers/RankingController.php';
include_once './controllers/GameController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$authController = new AuthController($conn);
$deckController = new DeckController($conn);
$shopController = new ShopController($conn);
$socialController = new SocialController($conn);
$questController = new QuestController($conn);
$rankingController = new RankingController($conn);
$gameController = new GameController($conn);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'register': if ($_SERVER['REQUEST_METHOD'] === 'POST') $authController->register(); break;
    case 'login': if ($_SERVER['REQUEST_METHOD'] === 'POST') $authController->login(); break;
    case 'logout': $authController->logout(); break;
    case 'refresh_user': $authController->refresh(); break;
    case 'add_xp': $authController->testAddXp(); break;
    case 'get_profile_data': $authController->getProfileData(); break;
    case 'update_profile': if ($_SERVER['REQUEST_METHOD'] === 'POST') $authController->updateProfile(); break;
    case 'change_password': if ($_SERVER['REQUEST_METHOD'] === 'POST') $authController->changePassword(); break;
    case 'get_collection': $deckController->getCollectionData(); break;
    case 'get_deck_content': $deckController->getDeckContent(); break;
    case 'create_deck': if ($_SERVER['REQUEST_METHOD'] === 'POST') $deckController->createDeck(); break;
    case 'save_deck': if ($_SERVER['REQUEST_METHOD'] === 'POST') $deckController->saveDeck(); break;
    case 'delete_deck': $deckController->deleteDeck(); break;
    case 'buy_booster': if ($_SERVER['REQUEST_METHOD'] === 'POST') $shopController->buyBooster(); break;
    case 'get_social_data': $socialController->getData(); break;
    case 'add_friend': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->addFriend(); break;
    case 'remove_friend': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->removeFriend(); break;
    case 'sell_card': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->sellCard(); break;
    case 'cancel_sale': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->cancelSale(); break;
    case 'buy_card': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->buyCard(); break;
    case 'wonder_trade': if ($_SERVER['REQUEST_METHOD'] === 'POST') $socialController->wonderTrade(); break;
    case 'start_training': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->startTraining(); break;
    case 'get_game_state': $gameController->getState(); break;
    case 'play_card': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->playCard(); break;
    case 'attack': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->attack(); break;
    case 'end_turn': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->endTurn(); break;
    case 'surrender': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->surrender(); break;
    case 'get_quests': $questController->getQuests(); break;
    case 'claim_quest': if ($_SERVER['REQUEST_METHOD'] === 'POST') $questController->claim(); break;
    case 'buy_pass': if ($_SERVER['REQUEST_METHOD'] === 'POST') $questController->buyPremiumPass(); break;
    case 'claim_pass_level': if ($_SERVER['REQUEST_METHOD'] === 'POST') $questController->claimPassReward(); break;
    case 'join_queue': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->joinQueue(); break;
    case 'leave_queue': if ($_SERVER['REQUEST_METHOD'] === 'POST') $gameController->leaveQueue(); break;
    case 'check_queue_status': $gameController->checkQueueStatus(); break;
    case 'get_ranking': $rankingController->getLeaderboard(); break;
    default: http_response_code(404); echo json_encode(["message" => "Action inconnue."]); break;
}
?>