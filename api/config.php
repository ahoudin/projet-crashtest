<?php
/**
 * config.php – Configuration et connexion à la base de données.
 *
 * Ce fichier est inclus par index.php. Il contient les identifiants MySQL
 * et une fonction db() qui renvoie la connexion PDO (créée une seule fois).
 */

// --- Paramètres de connexion (valeurs par défaut de XAMPP / WAMP) ---
const DB_HOST = '127.0.0.1'; // Adresse du serveur MySQL (ici, la machine locale)
const DB_PORT = 3306;        // Port MySQL par défaut
const DB_NAME = 'projet';    // Nom de la base importée depuis le fichier .sql
const DB_USER = 'root';      // Utilisateur MySQL (root par défaut en local)
const DB_PASS = '';          // Mot de passe (vide par défaut en local)

/**
 * Renvoie la connexion PDO à la base de données.
 * Le mot-clé "static" garde la connexion en mémoire : elle n'est ouverte
 * qu'au premier appel, puis réutilisée pour toutes les requêtes suivantes.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // DSN = chaîne qui décrit où se connecter. charset=utf8mb4 gère les accents (é, è…)
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            // En cas d'erreur SQL, PDO lance une exception (attrapée dans index.php)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Les résultats sont renvoyés sous forme de tableaux associatifs ['colonne' => valeur]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requêtes préparées côté MySQL (protection contre les injections SQL)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
