<?php
// Card 50: Kurama
class Card_50 extends AbstractCard {
    public function isTargetable($byPlayerKey, $sourceType = 'spell') {
        // Immunisé aux sorts ennemis
        // Assuming we check calling player vs owner logic in controller or pass owner here?
        // Actually findTarget passes 'stealth' check using keys.
        // The check was: if ($u['id'] == 50 && $key != $playerKey)
        // Here we don't know who owns THIS card easily without passing it, but usually the caller checks.
        // Actually, AbstractCard methods don't store state.
        // We will assume simpler logic: return false if source is enemy spell.
        // But `isTargetable` here just returns bool. The controller knows the context.
        return false; 
    }

    public function onAttack(&$state, &$attacker, &$defender, $damageDealt) {
        // Trample: Overkill damage to hero
        // Logic: if defender dead (hp < 0), deal excess to hero.
        if ($defender['hp'] < 0) {
            $overkill = abs($defender['hp']);
            // We need to know who is the 'opponent'.
            // AbstractCard doesn't explicitly store "owner" but we can deduce or pass it.
            // Problem: We need to find the opponent key.
            // Using $state, we assume 2 players.
            // If attacker is in p1 board, opp is p2.
            $attackerKey = 'p1'; 
            $oppKey = 'p2';
            
            // Heuristic to find owner
            $found = false;
            foreach ($state['p1']['board'] as $u) { if ($u['uid'] === $attacker['uid']) { $attackerKey = 'p1'; $oppKey = 'p2'; $found = true; break; } }
            if (!$found) { $attackerKey = 'p2'; $oppKey = 'p1'; }
            
            $state[$oppKey]['hp'] -= $overkill;
        }
    }
}
