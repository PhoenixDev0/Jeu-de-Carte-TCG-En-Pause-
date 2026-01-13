<?php
// Card 27: Multi-Clonage
class Card_27 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        while (count($state[$playerKey]['board']) < 8) {
             $state[$playerKey]['board'][] = [
                "uid" => uniqid(),
                "id" => 998,
                "name" => "Clone",
                "hp" => 1,
                "max_hp" => 1,
                "attack" => 1,
                "can_attack" => false, 
                "image_url" => "clone.jpg"
            ];
        }
        return "Multi-Clonage (Plateau rempli) !";
    }
}
