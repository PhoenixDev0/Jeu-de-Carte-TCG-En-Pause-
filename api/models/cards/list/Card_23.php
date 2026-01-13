<?php
// Card 23: Shikamaru Nara
class Card_23 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        if (!empty($state[$oppKey]['board'])) {
            $randIndex = array_rand($state[$oppKey]['board']);
            $state[$oppKey]['board'][$randIndex]['frozen'] = true;
            $state[$oppKey]['board'][$randIndex]['can_attack'] = false;
            return "Shikamaru : Manipulation des ombres !";
        }
        return "Shikamaru invoqué.";
    }
}
