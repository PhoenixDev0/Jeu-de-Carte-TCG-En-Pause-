<?php
// Card 35: Kabuto Yakushi
class Card_35 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        foreach ($state[$playerKey]['board'] as &$myUnit) {
            if ($myUnit['hp'] < $myUnit['max_hp']) {
                $myUnit['hp'] = min($myUnit['max_hp'], $myUnit['hp'] + 2);
            }
        }
        return "Kabuto : Soins médicaux.";
    }
}
