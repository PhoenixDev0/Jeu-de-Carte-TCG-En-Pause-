<?php
// Card 40: Asuma Sarutobi
class Card_40 extends AbstractCard {
    public function deathrattle(&$state, $unitData, $ownerKey) {
        foreach ($state[$ownerKey]['board'] as &$u) {
            $u['attack'] += 2;
            $u['hp'] += 2;
            $u['max_hp'] += 2;
        }
    }
}
