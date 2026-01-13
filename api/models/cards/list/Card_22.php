<?php
// Card 22: Neji Hyuga
class Card_22 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        if (!empty($state[$oppKey]['board'])) {
            $randIndex = array_rand($state[$oppKey]['board']);
            $state[$oppKey]['board'][$randIndex]['attack'] = 1;
            return "Neji : Points vitaux bloqués !";
        }
        return "Neji invoqué.";
    }
}
