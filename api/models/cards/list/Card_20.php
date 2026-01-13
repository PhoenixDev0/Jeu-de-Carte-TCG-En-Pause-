<?php
// Card 20: Fumigène
class Card_20 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $targetData['unit']['stealth'] = true;
        $targetData['unit']['remove_stealth_at_eot'] = true;
        return "Fumigène ! (Camouflage)";
    }
}
