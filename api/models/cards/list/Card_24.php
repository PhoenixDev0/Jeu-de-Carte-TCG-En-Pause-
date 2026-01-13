<?php
// Card 24: Temari
class Card_24 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        $boardCount = count($state[$oppKey]['board']);
        for ($i = $boardCount - 1; $i >= 0; $i--) {
            $u = &$state[$oppKey]['board'][$i];
            
            if (isset($u['divine_shield']) && $u['divine_shield'] === true) {
                $u['divine_shield'] = false;
            } else {
                 $rawDamage = 2;
                 if (isset($u['armor']) && $u['armor'] > 0) {
                     $armorDmg = min($u['armor'], $rawDamage);
                     $u['armor'] -= $armorDmg;
                     $rawDamage -= $armorDmg;
                 }
                 $u['hp'] -= $rawDamage;
            }
            // Death handled externally
        }
        return "Temari : Tempête de sable !";
    }
}
