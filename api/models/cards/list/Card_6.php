<?php
// Shuriken: Inflige 2 points de dégâts à une unité.
class Card_6 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        if (!$targetData) throw new Exception("Veuillez sélectionner une cible.");
        
        $target = &$targetData['unit'];
        $tKey = $targetData['key'];
        $tIndex = $targetData['index'];

        // Logique de dégâts standard (sera peut-être refactorisée dans AbstractCard ou un helper trait plus tard)
        if (isset($target['divine_shield']) && $target['divine_shield'] === true) {
            $target['divine_shield'] = false;
        } else {
            $rawDamage = 2;
            if (isset($target['armor']) && $target['armor'] > 0) {
                $armorDmg = min($target['armor'], $rawDamage);
                $target['armor'] -= $armorDmg;
                $rawDamage -= $armorDmg;
            }
            $target['hp'] -= $rawDamage;
        }

        // Vérification de mort
        if ($target['hp'] <= 0) {
            // Note: Deathrattle sera géré par le controlleur principal pour l'instant
            // ou bien on ajoute une méthode handleDeath dans AbstractCard
            $state['death_queue'][] = ['unit' => $target, 'key' => $tKey, 'index' => $tIndex];
        } 
        
        return "Shuriken lancé !";
    }
}
