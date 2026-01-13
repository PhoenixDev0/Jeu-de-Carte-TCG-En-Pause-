<?php
// Konohamaru: Râle d'agonie : Ajoute un Naruto (Genin) dans votre main.
class Card_5 extends AbstractCard {
    public function deathrattle(&$state, $unitData, $ownerKey) {
        if (count($state[$ownerKey]['hand']) < 10) {
             $state[$ownerKey]['hand'][] = [
                "uid" => uniqid(),
                "id" => 1 // Naruto (Genin)
            ];
        }
    }
}
