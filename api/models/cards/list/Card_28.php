<?php
// Card 28: Katon : Boule de Feu
class Card_28 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $target = &$targetData['unit'];
        $tKey = $targetData['key'];
        
        if (isset($target['divine_shield']) && $target['divine_shield'] === true) {
            $target['divine_shield'] = false;
        } else {
            $target['hp'] -= 6;
        }
        
        if ($target['hp'] <= 0) {
             // Note: Death queue handled externally or ideally here if we had full control
        }
        return "Katon ! Boule de Feu !";
    }
}
