-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 10 déc. 2025 à 19:10
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `anime_game_card`
--

-- --------------------------------------------------------

--
-- Structure de la table `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Unit','Spell','Relic','Field') NOT NULL,
  `rarity` enum('Common','Rare','Epic','Legendary','Mythic') NOT NULL,
  `cost` int(11) NOT NULL,
  `attack` int(11) DEFAULT 0,
  `hp` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `expansion_set` varchar(50) DEFAULT 'Standard',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cards`
--

INSERT INTO `cards` (`id`, `name`, `type`, `rarity`, `cost`, `attack`, `hp`, `description`, `image_url`, `expansion_set`, `created_at`) VALUES
(1, 'Naruto Uzumaki (Genin)', 'Unit', 'Common', 1, 1, 2, 'Gagne +1 ATK pour chaque autre allié.', 'naruto_genin.jpg', 'Naruto', '2025-12-08 10:57:12'),
(2, 'Sasuke Uchiha (Genin)', 'Unit', 'Common', 1, 2, 1, 'Rage : Gagne +2 ATK.', 'sasuke_genin.jpg', 'Naruto', '2025-12-08 10:57:12'),
(3, 'Sakura Haruno (Genin)', 'Unit', 'Common', 1, 1, 3, 'Fin du tour : Soigne de 1 PV un allié blessé.', 'sakura_genin.jpg', 'Naruto', '2025-12-08 10:57:12'),
(4, 'Iruka Umino', 'Unit', 'Common', 2, 2, 3, 'Cri de guerre : Donne +1/+1 à une autre unité alliée.', 'iruka.jpg', 'Naruto', '2025-12-08 10:57:12'),
(5, 'Konohamaru', 'Unit', 'Common', 1, 1, 1, 'Râle d\'agonie : Ajoute un Naruto (Genin) dans votre main.', 'konohamaru.jpg', 'Naruto', '2025-12-09 12:32:32'),
(6, 'Shuriken', 'Spell', 'Common', 1, 0, 0, 'Inflige 2 points de dégâts à une unité.', 'shuriken.jpg', 'Naruto', '2025-12-09 12:32:32'),
(7, 'Kunai Explosif', 'Spell', 'Common', 2, 0, 0, 'Inflige 3 points de dégâts au héros ennemi.', 'Kunai.jpg', 'Naruto', '2025-12-09 12:32:32'),
(8, 'Clone de l\'Ombre', 'Unit', 'Common', 1, 1, 1, '', 'clone.jpg', 'Naruto', '2025-12-09 12:32:32'),
(9, 'Mizuki', 'Unit', 'Common', 2, 3, 2, '', 'mizuki.jpg', 'Naruto', '2025-12-09 12:32:32'),
(10, 'Soldat Anbu', 'Unit', 'Common', 3, 3, 4, '', 'anbu.jpg', 'Naruto', '2025-12-09 12:32:32'),
(11, 'Ramen Ichiraku', 'Spell', 'Common', 2, 0, 0, 'Rend 5 PV à votre héros.', 'ramen.jpg', 'Naruto', '2025-12-09 12:32:32'),
(12, 'Pakkun', 'Unit', 'Common', 2, 1, 2, 'Piochez une carte.', 'pakkun.jpg', 'Naruto', '2025-12-09 12:32:32'),
(13, 'Tenten', 'Unit', 'Common', 3, 2, 4, 'Cri de guerre : Ajoute un Shuriken à votre main.', 'tenten.jpg', 'Naruto', '2025-12-09 12:32:32'),
(14, 'Kiba Inuzuka', 'Unit', 'Common', 3, 3, 3, 'Invoque Akamaru (2/2) avec lui.', 'kiba.jpg', 'Naruto', '2025-12-09 12:32:32'),
(15, 'Akamaru', 'Unit', 'Common', 2, 2, 2, 'Provocation.', 'akamaru.jpg', 'Naruto', '2025-12-09 12:32:32'),
(16, 'Shino Aburame', 'Unit', 'Common', 3, 2, 5, 'Au début de votre tour, inflige 1 dégât au héros ennemi.', 'shino.jpg', 'Naruto', '2025-12-09 12:32:32'),
(17, 'Choji Akimichi', 'Unit', 'Common', 4, 4, 5, '', 'choji.jpg', 'Naruto', '2025-12-09 12:32:32'),
(18, 'Ino Yamanaka', 'Unit', 'Common', 3, 2, 3, 'Cri de guerre : Prend le contrôle d\'une unité ennemie (ATK 2 ou moins) jusqu\'à la fin du tour.', 'ino.jpg', 'Naruto', '2025-12-09 12:32:32'),
(19, 'Technique de Permutation', 'Spell', 'Common', 1, 0, 0, 'Donne Bouclier Divin à une unité.', 'substitution.jpg', 'Naruto', '2025-12-09 12:32:32'),
(20, 'Fumigène', 'Spell', 'Common', 1, 0, 0, 'Donne Camouflage à une unité pour 1 tour.', 'smoke.jpg', 'Naruto', '2025-12-09 12:32:32'),
(21, 'Rock Lee', 'Unit', 'Rare', 4, 5, 2, 'Charge (Peut attaquer tout de suite).', 'lee.jpg', 'Naruto', '2025-12-09 12:32:32'),
(22, 'Neji Hyuga', 'Unit', 'Rare', 4, 3, 6, 'Provocation. Cri de guerre : Réduit l\'ATK d\'une unité ennemie à 1.', 'neji.jpg', 'Naruto', '2025-12-09 12:32:32'),
(23, 'Shikamaru Nara', 'Unit', 'Rare', 4, 2, 5, 'Immobilise une unité ennemie (elle ne peut pas attaquer au prochain tour).', 'shikamaru.jpg', 'Naruto', '2025-12-10 17:23:24'),
(24, 'Temari', 'Unit', 'Rare', 5, 4, 4, 'Inflige 2 dégâts à toutes les unités ennemies.', 'temari.jpg', 'Naruto', '2025-12-10 17:24:16'),
(25, 'Kankuro', 'Unit', 'Rare', 5, 3, 5, 'Râle d\'agonie : Invoque une Marionnette 3/3.', 'kankuro.jpg', 'Naruto', '2025-12-10 17:25:04'),
(26, 'Haku', 'Unit', 'Rare', 5, 5, 4, 'Gèle les personnages blessés par cette unité.', 'haku.jpg', 'Naruto', '2025-12-10 17:25:42'),
(27, 'Multi-Clonage', 'Spell', 'Rare', 4, 0, 0, 'Remplit votre plateau de Clones 1/1.', 'multiclone.jpg', 'Naruto', '2025-12-10 17:26:24'),
(28, 'Katon : Boule de Feu', 'Spell', 'Rare', 4, 0, 0, 'Inflige 6 dégâts à une unité.', 'fireball.jpg', 'Naruto', '2025-12-10 17:27:06'),
(29, 'Suiton : Dragon Aqueux', 'Spell', 'Rare', 5, 0, 0, 'Inflige 4 dégâts à deux ennemis aléatoires.', 'dragon_aqueux.jpg', 'Naruto', '2025-12-10 17:28:02'),
(30, 'Chidori', 'Spell', 'Rare', 3, 0, 0, 'Inflige 5 dégâts. Si la cible meurt, piochez une carte.', 'chidori.jpg', 'Naruto', '2025-12-10 17:29:12'),
(31, 'Rasengan', 'Spell', 'Rare', 3, 0, 0, 'Renvoie une unité ennemie dans la main de son propriétaire.', 'rasengan.jpg', 'Naruto', '2025-12-10 17:29:52'),
(32, 'Mode Ermite', 'Spell', 'Rare', 3, 0, 0, 'Donne +4/+4 à une unité.', 'sage_mode.jpg', 'Naruto', '2025-12-10 17:30:30'),
(33, 'Hinata Hyuga', 'Unit', 'Rare', 3, 2, 4, 'Vol de vie (Les dégâts infligés soignent votre héros).', 'hinata.jpg', 'Naruto', '2025-12-10 17:31:19'),
(34, 'Kimimaro', 'Unit', 'Rare', 5, 4, 6, 'Inflige 1 dégât à tous les autres personnages à la fin du tour.', 'kimimaro.jpg', 'Naruto', '2025-12-10 17:32:21'),
(35, 'Kabuto Yakushi', 'Unit', 'Rare', 4, 3, 4, 'Soigne toutes vos unités de 2 PV.', 'kabuto.jpg', 'Naruto', '2025-12-10 17:33:05'),
(36, 'Kakashi Hatake', 'Unit', 'Epic', 6, 5, 5, 'Copie : Gagne le passif de l\'unité en face.', 'kakashi.jpg', 'Naruto', '2025-12-10 17:34:05'),
(37, 'Might Guy', 'Unit', 'Epic', 6, 7, 6, 'Si Rock Lee est sur le plateau, gagne +3/+3.', 'guy.jpg', 'Naruto', '2025-12-10 17:34:56'),
(38, 'Zabuza Momochi', 'Unit', 'Epic', 6, 6, 5, 'Camouflage. Si Haku meurt, gagne +4 ATK.', 'zabuza.jpg', 'Naruto', '2025-12-10 17:35:41'),
(39, 'Gaara', 'Unit', 'Epic', 7, 2, 10, 'Provocation. Ne peut pas attaquer tant qu\'il a de l\'armure.', 'gaara.jpg', 'Naruto', '2025-12-10 17:36:29'),
(40, 'Asuma Sarutobi', 'Unit', 'Epic', 5, 5, 5, 'Râle d\'agonie : Donne +2/+2 à tous les alliés.', 'asuma.jpg', 'Naruto', '2025-12-10 17:37:16'),
(41, 'Kurenai Yuhi', 'Unit', 'Epic', 5, 4, 4, 'Cri de guerre : Rend une unité ennemie confuse (50% de chance d\'attaquer la mauvaise cible).', 'kurenai.jpg', 'Naruto', '2025-12-10 17:38:11'),
(42, 'Kisame Hoshigaki', 'Unit', 'Epic', 7, 6, 7, 'Vole 1 point de Mana à l\'adversaire.', 'kisame.jpg', 'Naruto', '2025-12-10 17:38:53'),
(43, 'Deidara', 'Unit', 'Epic', 6, 4, 4, 'Râle d\'agonie : Inflige 3 dégâts à TOUS les personnages.', 'deidara.jpg', 'Naruto', '2025-12-10 17:39:39'),
(44, 'Sasori', 'Unit', 'Epic', 6, 3, 8, 'Toxicité (Détruit toute unité blessée pas celle-ci).', 'sasori.jpg', 'Naruto', '2025-12-10 17:40:23'),
(45, 'Tsukuyomi', 'Spell', 'Epic', 8, 0, 0, 'Prenez le contrôle d\'une unité ennemie définitivement.', 'tsukuyomi.jpg', 'Naruto', '2025-12-10 17:41:09'),
(46, 'Jiraiya', 'Unit', 'Legendary', 8, 6, 8, 'Invoque deux Crapauds 3/3 avec Provocation.', 'jiraiya.jpg', 'Naruto', '2025-12-10 17:42:01'),
(47, 'Tsunade', 'Unit', 'Legendary', 8, 4, 12, 'A la fin de chaque tour, soigne complètement votre héros.', 'tsunade.jpg', 'Naruto', '2025-12-10 17:42:43'),
(48, 'Orochimaru', 'Unit', 'Legendary', 8, 6, 6, 'Râle d\'agonie : Se réincarne dans 2 tour.', 'orochimaru.jpg', 'Naruto', '2025-12-10 17:43:31'),
(49, 'Itachi Uchiha', 'Unit', 'Legendary', 9, 7, 7, 'Cri de guerre : Détruit toutes les unités ennemies ayant 5 ATK ou plus.', 'itachi.jpg', 'Naruto', '2025-12-10 17:44:23'),
(50, 'Kurama (Kyubi)', 'Unit', 'Mythic', 10, 12, 12, 'Ecraseur (Les dégâts excédentaires touchent le héros adverse). Immunisé aux sorts.', 'kurama.jpg', 'Naruto', '2025-12-10 17:45:24');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
