<?php
// Card 39: Gaara
class Card_39 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $myIndex = count($state[$playerKey]['board']) - 1;
        $state[$playerKey]['board'][$myIndex]['armor'] = 5;
        return "Gaara ! Défense Absolue (5 Armure) !";
    }
}
