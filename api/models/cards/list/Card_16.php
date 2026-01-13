<?php
// Card 16: Shino Aburame
class Card_16 extends AbstractCard {
    public function onStartTurn(&$state, $unitData, $ownerKey) {
         $oppKey = ($ownerKey == 'p1') ? 'p2' : 'p1';
         $rawDamage = 1;
         if (isset($state[$oppKey]['armor']) && $state[$oppKey]['armor'] > 0) {
             $armorDmg = min($state[$oppKey]['armor'], $rawDamage);
             $state[$oppKey]['armor'] -= $armorDmg;
             $rawDamage -= $armorDmg;
         }
         $state[$oppKey]['hp'] -= $rawDamage;
    }
}
