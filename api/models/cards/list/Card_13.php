<?php
// Card 13: Tenten
class Card_13 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
         $state[$playerKey]['hand'][] = [
            "uid" => uniqid(),
            "id" => 6 // Shuriken
        ];
        return "Tenten : Shuriken prêt !";
    }
}
