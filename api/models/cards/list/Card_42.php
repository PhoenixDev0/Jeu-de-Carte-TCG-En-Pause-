<?php
// Card 42: Kisame Hoshigaki
class Card_42 extends AbstractCard {
    public function play(&$state, $playerKey, $targetData = null) {
        $oppKey = ($playerKey == 'p1') ? 'p2' : 'p1';
        
        if ($state[$oppKey]['max_mana'] > 0) {
            $state[$oppKey]['max_mana'] -= 1;
            $state[$oppKey]['mana'] = min($state[$oppKey]['mana'], $state[$oppKey]['max_mana']);
        }
        
        if ($state[$playerKey]['max_mana'] < 10) {
            $state[$playerKey]['max_mana'] += 1;
            $state[$playerKey]['mana'] += 1;
        }
        return "Kisame ! Mana volé !";
    }
}
