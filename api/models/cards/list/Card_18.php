<?php
// Card 18: Ino Yamanaka
class Card_18 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $validTargets = [];
        foreach ($state[$oppKey]['board'] as $k => $u) {
             if ($u['attack'] <= 2) $validTargets[] = $k;
        }
        // Assuming Ino is already on board count < 8 check might be needed for the +1 logic if play order matters
        if (!empty($validTargets) && count($state[$playerKey]['board']) < 8) {
            $randIndex = $validTargets[array_rand($validTargets)];
            $stolenUnit = $state[$oppKey]['board'][$randIndex];
            
            $stolenUnit['original_owner'] = $oppKey;
            $stolenUnit['return_at_eot'] = true;
            $stolenUnit['can_attack'] = true; 
            
            array_splice($state[$oppKey]['board'], $randIndex, 1);
            $state[$playerKey]['board'][] = $stolenUnit;
            return "Ino : Transposition !";
        }
        return "Ino : Aucune cible valide.";
    }
}
