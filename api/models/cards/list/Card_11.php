<?php
// Ramen Ichiraku: Rend 5 PV à votre héros.
class Card_11 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $state[$playerKey]['hp'] = min(30, $state[$playerKey]['hp'] + 5);
        return "Un bon bol de Ramen ! (+5 PV)";
    }
}
