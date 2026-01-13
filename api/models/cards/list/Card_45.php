<?php
// Card 45: Tsukuyomi
class Card_45 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (count($state[$playerKey]['board']) >= 8) {
            throw new Exception("Tsukuyomi annulé : Votre plateau est plein !");
        }
        
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $stolenUnit = $targetData['unit'];
        $tKey = $targetData['key'];
        $tIndex = $targetData['index'];
        
        array_splice($state[$tKey]['board'], $tIndex, 1);
        
        $stolenUnit['can_attack'] = false; 
        unset($stolenUnit['return_at_eot']);
        unset($stolenUnit['original_owner']);
        
        $state[$playerKey]['board'][] = $stolenUnit;
        
        return "Tsukuyomi !! L'unité " . $stolenUnit['name'] . " est à vous pour toujours !";
    }
}
