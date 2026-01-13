<?php
// Card 34: Kimimaro
class Card_34 extends AbstractCard {
    public function onEndTurn(&$state, $unitData, $ownerKey) {
        $kimimaroUid = $unitData['uid'];
        $oppKey = ($ownerKey == 'p1') ? 'p2' : 'p1';
        
        $state[$ownerKey]['hp'] -= 1;
        $state[$oppKey]['hp'] -= 1;
        
        $allKeys = [$ownerKey, $oppKey];
        foreach ($allKeys as $pkey) {
             foreach ($state[$pkey]['board'] as &$u) {
                 if ($u['uid'] !== $kimimaroUid) {
                     if (isset($u['divine_shield']) && $u['divine_shield']) {
                         $u['divine_shield'] = false;
                     } else {
                         $rawDamage = 1;
                         if (isset($u['armor']) && $u['armor'] > 0) {
                             $armorDmg = min($u['armor'], $rawDamage);
                             $u['armor'] -= $armorDmg;
                             $rawDamage -= $armorDmg;
                         }
                         $u['hp'] -= $rawDamage;
                     }
                 }
             }
        }
    }
}
