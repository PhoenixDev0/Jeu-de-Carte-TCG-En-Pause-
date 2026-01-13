<?php
// Card 30: Chidori
class Card_30 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $target = &$targetData['unit'];
        
         if (isset($target['divine_shield']) && $target['divine_shield'] === true) {
             $target['divine_shield'] = false;
         } else {
             $target['hp'] -= 5;
         }
         
         if ($target['hp'] <= 0) {
             if (!empty($state[$playerKey]['deck'])) {
                $cardId = array_shift($state[$playerKey]['deck']);
                $state[$playerKey]['hand'][] = ["uid" => uniqid(), "id" => $cardId];
             }
             return "Chidori ! Cible éliminée, carte piochée !";
         }
         return "Chidori ! (Cible survit)";
    }
}
