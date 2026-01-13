<?php
// Card 26: Haku
class Card_26 extends AbstractCard {
    public function onAttack(&$state, &$attacker, &$defender, $damageDealt) {
        $defender['frozen'] = true;
        $defender['can_attack'] = false;
    }
    
    public function onDefend(&$state, &$attacker, &$defender, $damageDealt) {
        $attacker['frozen'] = true;
        $attacker['can_attack'] = false;
    }
}
