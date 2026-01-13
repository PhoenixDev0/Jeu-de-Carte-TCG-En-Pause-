-- Création de la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS anime_game_card,
USE anime_game_card,

-- ==========================================
-- 1. UTILISATEURS & PROGRESSION [cite: 105-151, 356-366]
-- ==========================================

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE, -- Pseudo unique
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Customisation Profil [cite: 122-127]
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    title VARCHAR(100) DEFAULT 'Novice',
    
    -- Economie [cite: 358-365]
    gold INT DEFAULT 100, -- Monnaie Jeu
    gems INT DEFAULT 0,   -- Monnaie Premium
    
    -- Progression & Stats [cite: 128-137, 421]
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    elo INT DEFAULT 1000, -- Départ à 1000 ELO
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    
    -- Battle Pass (Cursus Elite) [cite: 411]
    is_premium BOOLEAN DEFAULT FALSE, -- A acheté le pass "Cursus Elite"
    pass_level INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 2. CARTES (DONNÉES STATIQUES) [cite: 24-101]
-- ==========================================

CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Unit', 'Spell', 'Relic', 'Field') NOT NULL, -- Types définis [cite: 25-28]
    rarity ENUM('Common', 'Rare', 'Epic', 'Common', 'Mythic') NOT NULL, -- Raretés [cite: 283-322]
    
    -- Coûts et Stats [cite: 88, 93-94]
    cost INT NOT NULL, -- Coût en ES
    attack INT DEFAULT 0,
    hp INT DEFAULT 0,
    
    -- Visuel & Texte
    description TEXT, -- Texte du passif
    image_url VARCHAR(255),
    expansion_set VARCHAR(50) DEFAULT 'Standard', -- Pour les boosters d'extension [cite: 268]
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 3. COLLECTION (INVENTAIRE) [cite: 232-233]
-- ==========================================

CREATE TABLE IF NOT EXISTS collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    card_id INT NOT NULL,
    quantity INT DEFAULT 1, -- Nombre d'exemplaires possédés
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE(player_id, card_id) -- Empêche les doublons de lignes pour la même carte
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 4. DECKS (GRIMOIRES) [cite: 179-227]
-- ==========================================

CREATE TABLE IF NOT EXISTS decks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    hero_card_id INT, -- Image du héros représentant le deck [cite: 184]
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    -- On ne lie pas hero_card_id en FK stricte pour éviter les erreurs si la carte est supprimée, 
    -- mais idéalement elle devrait l'être.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Contenu des Decks (Liaison Deck <-> Cartes)
CREATE TABLE IF NOT EXISTS deck_cards (
    deck_id INT NOT NULL,
    card_id INT NOT NULL,
    quantity INT DEFAULT 1, -- Max 3 par règle JS [cite: 258]
    
    PRIMARY KEY (deck_id, card_id),
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 5. SOCIAL & ÉCHANGES [cite: 152-165, 381-391]
-- ==========================================

-- Liste d'amis
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL, -- Celui qui demande
    addressee_id INT NOT NULL, -- Celui qui reçoit
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requester_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (addressee_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Hôtel des Ventes / Échange Miracle
CREATE TABLE IF NOT EXISTS market_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    card_id INT NOT NULL, -- La carte vendue
    
    type ENUM('sell', 'trade', 'wonder') NOT NULL, -- Vente, Echange ciblé, ou Miracle [cite: 162]
    
    price_gold INT DEFAULT 0, -- Prix en Or (si Vente)
    target_card_id INT DEFAULT NULL, -- Carte voulue (si Echange)
    
    status ENUM('active', 'sold', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (seller_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 6. SYSTÈME DE QUÊTES [cite: 401-408]
-- ==========================================

CREATE TABLE IF NOT EXISTS quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('daily', 'weekly', 'achievement') NOT NULL,
    objective_count INT NOT NULL, -- Ex: 3 (victoires), 500 (dégâts)
    reward_type ENUM('gold', 'gems', 'xp') NOT NULL,
    reward_amount INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Progression du joueur dans les quêtes
CREATE TABLE IF NOT EXISTS player_quests (
    player_id INT NOT NULL,
    quest_id INT NOT NULL,
    current_progress INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    is_claimed BOOLEAN DEFAULT FALSE, -- Récompense récupérée ? [cite: 405]
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (player_id, quest_id),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- ==========================================
-- 7. HISTORIQUE DES PARTIES [cite: 139-140]
-- ==========================================

CREATE TABLE IF NOT EXISTS game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_1_id INT NOT NULL,
    player_2_id INT NULL,
    winner_id INT, -- NULL si match nul
    duration_seconds INT,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_1_id) REFERENCES players(id),
    FOREIGN KEY (player_2_id) REFERENCES players(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Insertion d'une quête quotidienne
INSERT INTO quests (title, description, type, objective_count, reward_type, reward_amount) VALUES
('Premier Sang', 'Gagner 1 duel.', 'daily', 1, 'gold', 50),

-- Stockage des cosmétiques débloqués (Avatars et Titres)
CREATE TABLE IF NOT EXISTS player_unlockables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    item_type ENUM('avatar', 'title') NOT NULL,
    item_value VARCHAR(255) NOT NULL, -- Le nom du fichier image ou le texte du titre
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Ajout du Code Ami dans la table players
ALTER TABLE players ADD COLUMN friend_code VARCHAR(10) UNIQUE AFTER username, 
-- Note : On générera ce code en PHP à l'inscription (ex: PSEUDO + 4 chiffres aléatoires)

-- Table des Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    type ENUM('friend_req', 'duel_req', 'system', 'market') NOT NULL,
    message VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

ALTER TABLE players MODIFY friend_code VARCHAR(60),

ALTER TABLE players ADD COLUMN claimed_pass_level INT DEFAULT 0,

-- ==========================================
-- 8. MATCHMAKING & PARTIES EN COURS
-- ==========================================

CREATE TABLE IF NOT EXISTS matchmaking_queue (
    player_id INT PRIMARY KEY,
    deck_id INT NOT NULL,
    mode ENUM('training', 'casual', 'ranked') NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

CREATE TABLE IF NOT EXISTS active_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_1_id INT NOT NULL,
    player_2_id INT NOT NULL, -- NULL si c'est une IA (Training)
    current_turn INT NOT NULL DEFAULT 1, -- ID du joueur dont c'est le tour
    turn_count INT DEFAULT 1,
    status ENUM('active', 'finished') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (player_1_id) REFERENCES players(id),
    FOREIGN KEY (player_2_id) REFERENCES players(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4,

-- Permettre à player_2_id d'être NULL pour l'IA
ALTER TABLE active_games MODIFY player_2_id INT NULL,

-- Ajouter une colonne pour stocker tout l'état du jeu (JSON)
ALTER TABLE active_games ADD COLUMN game_state JSON,