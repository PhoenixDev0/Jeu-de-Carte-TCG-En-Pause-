<?php
// Card 38: Zabuza Momochi
class Card_38 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $myIndex = count($state[$playerKey]['board']) - 1;
        $state[$playerKey]['board'][$myIndex]['stealth'] = true;
        return "Zabuza invoqué dans la brume (Camouflage) !";
    }
}
