<?php
// Card 3: Sakura Haruno
class Card_3 extends AbstractCard {
    public function onEndTurn(&$state, $unitData, $ownerKey) {
        foreach ($state[$ownerKey]['board'] as &$potentialTarget) {
            if ($potentialTarget['hp'] < $potentialTarget['max_hp']) {
                $potentialTarget['hp'] = min($potentialTarget['max_hp'], $potentialTarget['hp'] + 1);
                break; // One target
            }
        }
    }
}
