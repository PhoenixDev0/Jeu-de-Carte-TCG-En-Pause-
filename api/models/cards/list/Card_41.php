<?php
// Card 41: Kurenai Yuhi
class Card_41 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        if (!empty($state[$oppKey]['board'])) {
            $randIndex = array_rand($state[$oppKey]['board']);
            $state[$oppKey]['board'][$randIndex]['prochain_tour_confus'] = true;
            $state[$oppKey]['board'][$randIndex]['confused'] = true;
            return "Kurenai ! Genjutsu (Ennemi confus) !";
        }
        return "Kurenai : Genjutsu raté (pas de cible).";
    }
}
