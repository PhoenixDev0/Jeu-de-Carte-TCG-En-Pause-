<?php
// Card 25: Kankuro
class Card_25 extends AbstractCard {
    public function deathrattle(&$state, $unitData, $ownerKey) {
        if (count($state[$ownerKey]['board']) < 8) {
             $state[$ownerKey]['board'][] = [
                "uid" => uniqid(),
                "id" => 902, 
                "name" => "Marionnette",
                "attack" => 3,
                "hp" => 3,
                "max_hp" => 3,
                "can_attack" => false 
            ];
        }
    }
}
