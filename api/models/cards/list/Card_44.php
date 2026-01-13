<?php
// Card 44: Sasori
class Card_44 extends AbstractCard {
    public function onAttack(&$state, &$attacker, &$defender, $damageDealt) {
        if ($damageDealt > 0) {
            $defender['hp'] = 0;
        }
    }
    
    public function onDefend(&$state, &$attacker, &$defender, $damageDealt) {
        if ($damageDealt > 0) {
            $attacker['hp'] = 0;
        }
    }
}
