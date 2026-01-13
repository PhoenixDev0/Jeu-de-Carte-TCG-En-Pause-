<?php
// Card 46: Jiraiya
class Card_46 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $toadsSpawned = 0;
        for ($i = 0; $i < 2; $i++) {
            if (count($state[$playerKey]['board']) < 8) { 
                $toad = [
                    "id" => 901, 
                    "uid" => uniqid(),
                    "name" => "Crapaud",
                    "attack" => 3,
                    "hp" => 3,
                    "max_hp" => 3,
                    "cost" => 1,
                    "can_attack" => false, 
                    "taunt" => true
                ];
                $state[$playerKey]['board'][] = $toad;
                $toadsSpawned++;
            }
        }
        if ($toadsSpawned > 0) return "Jiraiya : Invocations des Crapauds (x$toadsSpawned) !";
        return "Jiraiya : Pas de place pour les Crapauds.";
    }
}
