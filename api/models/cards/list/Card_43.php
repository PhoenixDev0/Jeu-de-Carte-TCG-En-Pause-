<?php
// Card 43: Deidara
class Card_43 extends AbstractCard {
    public function deathrattle(&$state, $unitData, $ownerKey) {
        $targets = ['p1', 'p2'];
        foreach ($targets as $pkey) {
            $state[$pkey]['hp'] -= 3;
            for ($i = count($state[$pkey]['board']) - 1; $i >= 0; $i--) {
                $u = &$state[$pkey]['board'][$i];
                if (isset($u['divine_shield']) && $u['divine_shield'] === true) {
                    $u['divine_shield'] = false;
                } else {
                    $u['hp'] -= 3;
                }
            }
        }
    }
}
