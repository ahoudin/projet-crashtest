-- =============================================================================
-- utilisateur.sql – Table des comptes utilisateurs (système de login).
-- À importer dans la base `projet` APRÈS projet.sql (elle référence la table voiture).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `utilisateur` (
  `#id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  -- Pseudo affiché sur le site (unique)
  `pseudo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  -- Adresse e-mail (unique), utilisable pour se connecter
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  -- Mot de passe HACHÉ avec password_hash() : jamais stocké en clair
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  -- Voiture enregistrée comme « ma voiture » (facultatif)
  `id_voiture` int(11) DEFAULT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`#id_utilisateur`),
  UNIQUE KEY `pseudo` (`pseudo`),
  UNIQUE KEY `email` (`email`),
  KEY `id_voiture` (`id_voiture`),
  -- Si la voiture est supprimée, l'utilisateur garde son compte mais n'a plus de voiture
  CONSTRAINT `utilisateur_ibfk_1` FOREIGN KEY (`id_voiture`) REFERENCES `voiture` (`#id_voiture`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
