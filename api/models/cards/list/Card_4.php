<?php
// Iruka Umino: Cri de guerre : Donne +1/+1 à une autre unité alliée.
class Card_4 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        // Battlecry logic
        // On doit trouver l'unité Iruka qu'on vient de poser pour l'exclure ? 
        // Le GameController ajoute l'unité au board AVANT d'appeler play() ?
        // Oui, selon la logique actuelle du controller.
        
        // On suppose que Iruka est la dernière unité ajoutée sur le board du joueur
        $myBoard = &$state[$playerKey]['board'];
        $myIndex = count($myBoard) - 1;
        $me = $myBoard[$myIndex];
        
        $validTargets = [];
        foreach ($myBoard as $k => $u) {
            if ($u['uid'] !== $me['uid']) $validTargets[] = $k;
        }

        if (!empty($validTargets)) {
            $randIndex = $validTargets[array_rand($validTargets)];
            $state[$playerKey]['board'][$randIndex]['attack'] += 1;
            $state[$playerKey]['board'][$randIndex]['hp'] += 1;
            $state[$playerKey]['board'][$randIndex]['max_hp'] += 1;
            return "Iruka Umino : Enseignement ninja (+1/+1) !";
        }
        
        return "Iruka Umino invoqué (Pas de cible pour le buff).";
    }
}
