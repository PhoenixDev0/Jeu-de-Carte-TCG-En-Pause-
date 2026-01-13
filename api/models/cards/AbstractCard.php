<?php

abstract class AbstractCard {
    protected $id;
    protected $name;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Exécute l'effet de la carte lors de son utilisation (Sort ou Cri de guerre).
     * @param array $state L'état actuel du jeu.
     * @param string $playerKey La clé du joueur qui joue la carte ('p1' ou 'p2').
     * @param mixed $targetData Données de ciblage si nécessaire.
     * @return string Message de log de l'action.
     * @throws Exception Si l'action est invalide.
     */
    public function play(&$state, $playerKey, $targetData = null) {
        // Par défaut, rien (pour les unités sans cri de guerre)
        return $this->name . " invoqué !";
    }

    /**
     * Retourne la définition du passif (pour les unités).
     * @return array|null
     */
    public function getPassiveEffect() {
        return null;
    }

    /**
     * Gère l'effet de râle d'agonie (Deathrattle).
     * @param array $state
     * @param array $unitData Données de l'unité mourante.
     * @param string $ownerKey Propriétaire de l'unité.
     */
    public function deathrattle(&$state, $unitData, $ownerKey) {
        // Par défaut rien
    }

    /**
     * Début le tour (Effets de début de tour).
     * @param array $state
     * @param array $unitData
     * @param string $ownerKey
     */
    public function onStartTurn(&$state, $unitData, $ownerKey) {
        // Par défaut rien
    }

    /**
     * Fin du tour (Effets de fin de tour).
     * @param array $state
     * @param array $unitData
     * @param string $ownerKey
     */
    public function onEndTurn(&$state, $unitData, $ownerKey) {
        // Par défaut rien
    }

    // Effets de combat
    
    /**
     * Appelé quand cette unité attaque.
     */
    public function onAttack(&$state, &$attacker, &$defender, $damageDealt) {}
    
    /**
     * Appelé quand cette unité est attaquée.
     */
    public function onDefend(&$state, &$attacker, &$defender, $damageDealt) {}

    /**
     * Vérifie si l'unité peut être ciblée.
     */
    public function isTargetable($byPlayerKey, $sourceType = 'spell') {
        return true; 
    }
}
