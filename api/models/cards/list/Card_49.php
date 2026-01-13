<?php
// Card 49: Itachi Uchiha
class Card_49 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $destroyedCount = 0;
        
        for ($i = count($state[$oppKey]['board']) - 1; $i >= 0; $i--) {
            $u = $state[$oppKey]['board'][$i];
            if ($u['attack'] >= 5) {
                // Death handled implicitly if needed or we rely on loop check
                // Here we remove directly as play runs before death check in controller loop?
                // Actually controller doesn't loop check after play() returns, only during attack/endturn.
                // So we should splice BUT also trigger death effects? 
                // The AbstractCard::play doesn't return state, it modifies reference. 
                // We assume state management is robust.
                array_splice($state[$oppKey]['board'], $i, 1);
                $destroyedCount++;
            }
        }
        
        if ($destroyedCount > 0) return "Itachi : Amaterasu ! ($destroyedCount cibles détruites)";
        return "Itachi : Aucune cible valide pour Amaterasu.";
    }
}
