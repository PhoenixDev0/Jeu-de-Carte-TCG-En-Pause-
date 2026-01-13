<?php
// Card 36: Kakashi Hatake
class Card_36 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $myIndex = count($state[$playerKey]['board']) - 1;
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        
        if (isset($state[$oppKey]['board'][$myIndex])) {
            $targetUnit = $state[$oppKey]['board'][$myIndex];
            $state[$playerKey]['board'][$myIndex]['copied_passive_id'] = $targetUnit['id'];
            return "Kakashi a copié le passif de " . $targetUnit['name'] . " !";
        }
        return "Kakashi invoqué (Rien à copier).";
    }
}
