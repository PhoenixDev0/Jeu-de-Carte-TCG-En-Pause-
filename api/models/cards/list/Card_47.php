<?php
// Card 47: Tsunade
class Card_47 extends AbstractCard {
    public function onEndTurn(&$state, $unitData, $ownerKey) {
        $state[$ownerKey]['hp'] = 30; 
    }
    public function onStartTurn(&$state, $unitData, $ownerKey) {
         $state[$ownerKey]['hp'] = 30; 
    }
}
