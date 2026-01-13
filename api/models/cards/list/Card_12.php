<?php
// Card 12: Pakkun
class Card_12 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
         if (!empty($state[$playerKey]['deck'])) {
            $cardId = array_shift($state[$playerKey]['deck']);
            $state[$playerKey]['hand'][] = ["uid" => uniqid(), "id" => $cardId];
         }
         return "Pakkun : Carte piochée !";
    }
}
