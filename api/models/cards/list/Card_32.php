<?php
// Card 32: Mode Ermite
class Card_32 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $targetData['unit']['attack'] += 4;
        $targetData['unit']['hp'] += 4;
        $targetData['unit']['max_hp'] += 4;
        return "Mode Ermite ! Puissance décuplée (+4/+4) !";
    }
}
