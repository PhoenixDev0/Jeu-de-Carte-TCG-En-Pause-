<?php

require_once __DIR__ . '/AbstractCard.php';

// Charger dynamiquement les classes de cartes (simple include pour l'instant)
// Dans un vrai projet on utiliserait un autoloader
foreach (glob(__DIR__ . '/list/*.php') as $filename) {
    include_once $filename;
}

class CardFactory {
    public static function create($cardId) {
        $className = 'Card_' . $cardId;
        
        // Mapping manuel pour les noms plus explicites si voulu, sinon convention Card_{ID}
        // Pour l'instant on va utiliser Card_{ID} pour matcher rapidement la structure existante
        
        if (class_exists($className)) {
            // Le nom n'est pas critique ici car il vient de la DB lors de l'affichage
            return new $className($cardId, "Card #$cardId");
        }
        
        // Fallback générique pour les cartes sans effet spécial implémenté
        return new GenericCard($cardId, "Serveur Generic");
    }
}

class GenericCard extends AbstractCard {
    // Comportement par défaut
}
