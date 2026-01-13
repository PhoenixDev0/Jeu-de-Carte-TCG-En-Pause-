<?php
// Card 7: Kunai Explosif
class Card_7 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $state[$oppKey]['hp'] -= 3;
        return "Kunai explosif !";
    }
}
