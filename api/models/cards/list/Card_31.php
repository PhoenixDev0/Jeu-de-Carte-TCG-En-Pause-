<?php
// Card 31: Rasengan
class Card_31 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        $target = $targetData['unit'];
        $tKey = $targetData['key'];
        $tIndex = $targetData['index'];
        $ownerKey = isset($target['original_owner']) ? $target['original_owner'] : $tKey;
        
        array_splice($state[$tKey]['board'], $tIndex, 1);
        
        if (count($state[$ownerKey]['hand']) < 10) {
            $state[$ownerKey]['hand'][] = [
                "uid" => uniqid(),
                "id" => $target['id']
            ];
            return "Rasengan ! Unité renvoyée en main.";
        } else {
            return "Rasengan ! Unité détruite (main pleine).";
        }
    }
}
