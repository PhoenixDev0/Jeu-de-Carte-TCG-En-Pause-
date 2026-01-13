<?php
// Card 1: Naruto Uzumaki (Genin)
// Passive: Gagne +1 ATK pour chaque autre allié.
// Note: This requires constant re-evaluation or hooks.
// Assuming the GameController/Hydrate calls getPassiveEffect or we calculate it on the fly?
// The current architecture pushes logic. A passive stat modifier is tricky if state is static.
// We can use a `calculateAttack` method if we modify how attack is read.
// OR we apply a buff at start of turn/end of turn? No, it's dynamic.
// For now, we'll implement it as a "onStartTurn" refresh or similar, OR we assume the controller invokes a passive check.
// Since we don't have a "calculateStats" hook in the controller yet, we might rely on StartTurn/EndTurn to update the stats "snapshot".
class Card_1 extends AbstractCard {
    public function onStartTurn(&$state, $unitData, $ownerKey) {
         // Update attack based on board size
         $count = count($state[$ownerKey]['board']) - 1; // Others
         // We need base attack to not stack infinitely.
         // Current state structure mixes base and current. 
         // Implementation limitation: reset to base? 
         // Let's assume CardFactory gives base stats if we ask, but unitData has current.
         // We will just enforce the rule: Atk = Base (1) + Count.
         // Warning: Buffs from others (Iruka) would be lost.
         // Ideally Passives should be calculated at read-time (hydrate).
    }
    
    // Better: Helper for hydration?
    // Let's implement an empty class for now unless we add a `getPassiveModifiers` hook in AbstractCard used by hydrate.
}
