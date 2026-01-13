<?php
// Card 48: Orochimaru
class Card_48 extends AbstractCard {
    public function deathrattle(&$state, $unitData, $ownerKey) {
        if (!isset($state[$ownerKey]['graveyard_timers'])) {
             $state[$ownerKey]['graveyard_timers'] = [];
        }
        $state[$ownerKey]['graveyard_timers'][] = [
            "id" => 48,
            "name" => "Orochimaru",
            "turns_remaining" => 2
        ];
    }
}
