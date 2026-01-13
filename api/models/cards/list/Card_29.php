<?php
// Card 29: Suiton : Dragon Aqueux
class Card_29 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $validTargets = [];
        foreach ($state[$oppKey]['board'] as $k => $u) {
            if ($u['id'] != 50) $validTargets[] = $k; 
        }
        
        if (!empty($validTargets)) {
             $keys = array_rand($validTargets, min(2, count($validTargets)));
             $indicesToHit = is_array($keys) ? $keys : [$keys];
             
             foreach ($indicesToHit as $idx) {
                 $target = &$state[$oppKey]['board'][$idx];
                 if (isset($target['divine_shield']) && $target['divine_shield'] === true) {
                     $target['divine_shield'] = false;
                 } else {
                     $target['hp'] -= 4;
                 }
             }
            return "Suiton ! Dragon Aqueux !";
        }
        return "Suiton : Aucune cible valide.";
    }
}
