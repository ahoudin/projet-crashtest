-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 16 nov. 2026 à 08:47
-- Version du serveur : 5.7.40
-- Version de PHP : 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projet`
--

-- --------------------------------------------------------

--
-- Structure de la table `crashtest`
--

DROP TABLE IF EXISTS `crashtest`;
CREATE TABLE IF NOT EXISTS `crashtest` (
  `#id_crashtest` int(11) NOT NULL AUTO_INCREMENT,
  `choc_frontal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `choc_lateral` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `choc_arriere` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_voiture` int(11) NOT NULL,
  PRIMARY KEY (`#id_crashtest`),
  KEY `id_voiture` (`id_voiture`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `crashtest`
--

INSERT INTO `crashtest` (`#id_crashtest`, `choc_frontal`, `choc_lateral`, `choc_arriere`, `id_voiture`) VALUES
(1, '5', '5', '5', 1),
(2, '5', '5', '5', 2),
(3, '5', '5', '5', 3),
(4, '5', '5', '5', 4),
(5, '5', '5', '5', 5),
(6, '5', '5', '5', 6),
(7, '5', '5', '5', 7),
(8, '5', '5', '5', 8),
(9, '5', '5', '5', 9),
(10, '5', '5', '5', 10),
(11, '5', '5', '5', 11),
(12, '5', '5', '5', 12),
(13, '5', '5', '5', 13),
(14, '5', '5', '5', 14),
(15, '5', '5', '5', 15),
(16, '5', '5', '5', 16),
(17, '4', '4', '4', 17),
(18, '5', '5', '5', 18),
(19, '5', '5', '5', 19),
(20, '5', '5', '5', 20),
(21, '5', '5', '5', 21),
(22, '5', '5', '5', 22),
(23, '5', '5', '5', 23),
(24, '5', '5', '5', 24),
(25, '5', '5', '5', 25),
(26, '5', '5', '5', 26),
(27, '5', '5', '5', 27),
(28, '5', '5', '5', 28),
(29, '5', '5', '5', 29),
(30, '5', '5', '5', 30),
(31, '5', '5', '5', 31),
(32, '5', '5', '5', 32),
(33, '5', '5', '5', 33),
(34, '5', '5', '5', 34),
(35, '5', '5', '5', 35),
(36, '5', '5', '5', 36),
(37, '3', '3', '3', 37),
(38, '5', '5', '5', 38),
(39, '4', '4', '4', 39),
(40, '3', '3', '3', 40),
(41, '4', '4', '4', 41),
(42, '5', '5', '5', 42),
(43, '5', '5', '5', 43),
(44, '4', '4', '4', 44),
(45, '3', '3', '3', 45),
(46, '4', '4', '4', 46),
(47, '5', '5', '5', 47),
(48, '5', '5', '5', 48),
(49, '4', '4', '4', 49),
(50, '4', '4', '4', 50),
(51, '5', '5', '5', 51),
(52, '5', '5', '5', 52),
(53, '5', '5', '5', 53),
(54, '4', '4', '4', 54),
(55, '5', '5', '5', 55),
(56, '4', '4', '4', 56),
(57, '5', '5', '5', 57),
(58, '5', '5', '5', 58),
(59, '4', '4', '4', 59),
(60, '5', '5', '5', 60),
(61, '5', '5', '5', 61),
(62, '4', '4', '4', 62),
(63, '3', '3', '3', 63),
(64, '4', '4', '4', 64),
(65, '4', '4', '4', 65),
(66, '5', '5', '5', 66),
(67, '5', '5', '5', 67);

-- --------------------------------------------------------

--
-- Structure de la table `marque`
--

DROP TABLE IF EXISTS `marque`;
CREATE TABLE IF NOT EXISTS `marque` (
  `#id_marque` int(11) NOT NULL AUTO_INCREMENT,
  `nom_marque` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`#id_marque`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `marque`
--

INSERT INTO `marque` (`#id_marque`, `nom_marque`) VALUES
(1, 'BMW'),
(2, 'Volkswagen'),
(3, 'Peugeot');

-- --------------------------------------------------------

--
-- Structure de la table `voiture`
--

DROP TABLE IF EXISTS `voiture`;
CREATE TABLE IF NOT EXISTS `voiture` (
  `#id_voiture` int(11) NOT NULL AUTO_INCREMENT,
  `modele` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `annee` int(4) NOT NULL,
  `id_marque` int(11) NOT NULL,
  PRIMARY KEY (`#id_voiture`),
  KEY `id_marque` (`id_marque`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `voiture`
--

INSERT INTO `voiture` (`#id_voiture`, `modele`, `annee`, `id_marque`) VALUES
(1, 'Série 1 E81/E82/E87/E88', 2004, 1),
(2, 'Série 1 F20/F21', 2011, 1),
(3, 'Série 1 F40', 2019, 1),
(4, 'Série 2 Active Tourer/Gran Tourer F45/F46', 2014, 1),
(5, 'Série 2 Active Tourer U06', 2021, 1),
(6, 'Série 3 E90/91/92/93', 2005, 1),
(7, 'Série 3 F30/F31/F34', 2011, 1),
(8, 'Série 3 G20/G21', 2018, 1),
(9, 'Série 5 E60/E61', 2003, 1),
(10, 'Série 5 F07/F10/F11', 2009, 1),
(11, 'Série 5 G30/G31', 2016, 1),
(12, 'Série 6 G60/G61', 2023, 1),
(13, 'X1 E84', 2009, 1),
(14, 'X1 F48', 2015, 1),
(15, 'X1 U11', 2022, 1),
(16, 'X3 G01', 2017, 1),
(17, 'i3 (I01)', 2013, 1),
(18, 'iX (I20)', 2021, 1),
(19, 'Golf V', 2003, 2),
(20, 'Golf VI', 2008, 2),
(21, 'Golf VII', 2012, 2),
(22, 'Golf VIII', 2019, 2),
(23, 'Polo V', 2009, 2),
(24, 'Polo VI', 2017, 2),
(25, 'Passat B6', 2005, 2),
(26, 'Passat B7', 2010, 2),
(27, 'Passat B8', 2014, 2),
(28, 'Arteon', 2017, 2),
(29, 'up!', 2011, 2),
(30, 'T-Cross', 2018, 2),
(31, 'T-Roc', 2017, 2),
(32, 'Tiguan II', 2016, 2),
(33, 'ID.3', 2019, 2),
(34, 'ID.4', 2020, 2),
(35, 'ID. Buzz', 2022, 2),
(36, 'Touran II', 2015, 2),
(37, '406', 1995, 3),
(38, '407', 2004, 3),
(39, '607', 2000, 3),
(40, '106', 1991, 3),
(41, '206', 1998, 3),
(42, '207', 2006, 3),
(43, '208 I', 2012, 3),
(44, '208 II', 2019, 3),
(45, '306', 1993, 3),
(46, '307', 2001, 3),
(47, '308 I', 2007, 3),
(48, '308 II', 2013, 3),
(49, '308 III', 2021, 3),
(50, '408', 2022, 3),
(51, '508 I', 2010, 3),
(52, '508 II', 2018, 3),
(53, '1007', 2004, 3),
(54, 'iOn', 2010, 3),
(55, '2008 I', 2013, 3),
(56, '2008 II', 2019, 3),
(57, '3008 I', 2009, 3),
(58, '3008 II', 2016, 3),
(59, '3008 III', 2023, 3),
(60, '5008 I', 2009, 3),
(61, '5008 II', 2017, 3),
(62, '5008 III', 2024, 3),
(63, '806', 1994, 3),
(64, '807', 2002, 3),
(65, 'Partner III / Rifter', 2018, 3),
(66, 'Expert III', 2016, 3),
(67, 'Traveller', 2016, 3);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `crashtest`
--
ALTER TABLE `crashtest`
  ADD CONSTRAINT `crashtest_ibfk_1` FOREIGN KEY (`id_voiture`) REFERENCES `voiture` (`#id_voiture`);

--
-- Contraintes pour la table `voiture`
--
ALTER TABLE `voiture`
  ADD CONSTRAINT `voiture_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`#id_marque`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
