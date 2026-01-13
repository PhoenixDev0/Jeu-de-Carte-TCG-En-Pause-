<?php
// Card 37: Might Guy
class Card_37 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $hasRockLee = false;
        foreach ($state[$playerKey]['board'] as $checkUnit) {
            if ($checkUnit['id'] == 21) {
                $hasRockLee = true;
                break;
            }
        }
        
        if ($hasRockLee) {
            $myIndex = count($state[$playerKey]['board']) - 1;
            $state[$playerKey]['board'][$myIndex]['attack'] += 3;
            $state[$playerKey]['board'][$myIndex]['hp'] += 3;
            $state[$playerKey]['board'][$myIndex]['max_hp'] += 3;
            return "Might Guy ! La Fougue de la Jeunesse (+3/+3 grâce à Rock Lee) !";
        }
        return "Might Guy invoqué.";
    }
}
