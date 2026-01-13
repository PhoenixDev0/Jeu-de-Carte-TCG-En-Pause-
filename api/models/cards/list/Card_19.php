<?php
// Card 19: Technique de Permutation
class Card_19 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $targetData['unit']['divine_shield'] = true;
        return "Permutation ! (Bouclier Divin)";
    }
}
