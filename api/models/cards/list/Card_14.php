<?php
// Card 14: Kiba Inuzuka
class Card_14 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (count($state[$playerKey]['board']) < 8) {
            $state[$playerKey]['board'][] = [
                "uid" => uniqid(),
                "id" => 15,
                "name" => "Akamaru",
                "hp" => 2,
                "max_hp" => 2,
                "attack" => 2,
                "can_attack" => false,
                "taunt" => true
            ];
            return "Kiba : Akamaru, attaque !";
        }
        return "Kiba invoqué (Pas de place pour Akamaru).";
    }
}
