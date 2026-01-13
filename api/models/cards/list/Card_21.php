<?php
// Card 21: Rock Lee
class Card_21 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        // Charge: Can attack immediately
        $myIndex = count($state[$playerKey]['board']) - 1;
        if ($myIndex >= 0) {
            $state[$playerKey]['board'][$myIndex]['can_attack'] = true;
        }
        return "Rock Lee ! La Fougue de la Jeunesse (Charge) !";
    }
}
