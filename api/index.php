<?php
/**
 * index.php – Point d'entrée unique de l'API REST.
 *
 * Toutes les requêtes passent par ce fichier. Il lit l'URL (ex : /voitures/3)
 * et la méthode HTTP (GET, POST, PUT, DELETE), exécute la bonne requête SQL
 * et renvoie le résultat au format JSON.
 *
 * Ressources gérées : marques, voitures, crashtests.
 */

// Active le typage strict : PHP refuse les conversions de type implicites
declare(strict_types=1);

// Charge la configuration et la fonction db() de connexion à MySQL
require __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// En-têtes HTTP envoyés avec chaque réponse
// ---------------------------------------------------------------------------

// La réponse est du JSON encodé en UTF-8
header('Content-Type: application/json; charset=utf-8');
// CORS : autorise n'importe quel site (ou un fichier ouvert en local) à appeler l'API
header('Access-Control-Allow-Origin: *');
// Méthodes HTTP autorisées
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// Le client a le droit d'envoyer un en-tête Content-Type (pour le JSON)
header('Access-Control-Allow-Headers: Content-Type');

// Méthode HTTP de la requête (GET, POST, PUT, DELETE…)
$method = $_SERVER['REQUEST_METHOD'];

// Le navigateur envoie parfois une requête OPTIONS « de vérification » avant un POST/PUT :
// on répond simplement « OK, pas de contenu » (204)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------------------------
// Fonctions utilitaires
// ---------------------------------------------------------------------------

/**
 * Envoie une réponse JSON avec un code HTTP, puis arrête le script.
 * @param mixed $data   Données à convertir en JSON
 * @param int   $status Code HTTP (200 OK, 201 créé, 204 sans contenu…)
 */
function respond($data, int $status = 200): void
{
    http_response_code($status);
    // Un code 204 ne doit pas avoir de corps de réponse
    if ($status !== 204) {
        // JSON_UNESCAPED_UNICODE garde les accents lisibles, JSON_PRETTY_PRINT indente le JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

/**
 * Envoie une réponse d'erreur au format {"erreur": "message"} et arrête le script.
 */
function error(string $message, int $status): void
{
    respond(['erreur' => $message], $status);
}

/**
 * Lit le corps de la requête (envoyé en POST/PUT) et le convertit de JSON en tableau PHP.
 * Renvoie une erreur 400 si le JSON est absent ou mal formé.
 */
function body(): array
{
    // php://input contient le texte brut envoyé par le client
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($data)) {
        error('Corps JSON invalide ou manquant', 400);
    }
    return $data;
}

/**
 * Vérifie les données envoyées par le client.
 *
 * @param array $data    Données reçues
 * @param array $rules   Règles : ['nom_du_champ' => 'int' ou 'string']
 * @param bool  $partial true pour une mise à jour (PUT) : les champs absents sont ignorés
 * @return array         Données nettoyées (entiers convertis, espaces retirés)
 */
function validate(array $data, array $rules, bool $partial = false): array
{
    $clean = [];

    foreach ($rules as $field => $type) {
        // Champ absent : erreur en création, ignoré en modification partielle
        if (!array_key_exists($field, $data)) {
            if ($partial) continue;
            error("Champ requis manquant : $field", 422);
        }

        $value = $data[$field];

        if ($type === 'int') {
            // Le champ doit être un nombre entier
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                error("Le champ $field doit être un entier", 422);
            }
            $clean[$field] = (int) $value;
        } else {
            // Le champ doit être un texte non vide de 50 caractères maximum (taille des colonnes VARCHAR(50))
            if (!is_scalar($value) || trim((string) $value) === '' || mb_strlen((string) $value) > 50) {
                error("Le champ $field doit être une chaîne non vide (50 caractères max)", 422);
            }
            $clean[$field] = trim((string) $value);
        }
    }

    // En modification, il faut au moins un champ à changer
    if ($partial && !$clean) {
        error('Aucun champ à mettre à jour', 422);
    }

    return $clean;
}

/**
 * Indique si une ligne existe dans une table (utilisé pour vérifier les clés étrangères).
 * @param string $table Nom de la table
 * @param string $pk    Nom de la clé primaire (ex : '#id_marque')
 * @param int    $id    Valeur recherchée
 */
function exists(string $table, string $pk, int $id): bool
{
    // Les accents graves `…` sont obligatoires car les colonnes commencent par #
    $stmt = db()->prepare("SELECT 1 FROM `$table` WHERE `$pk` = ?");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// Requêtes SQL de lecture (réutilisées par plusieurs routes)
// ---------------------------------------------------------------------------

// Toutes les marques. « AS id » renomme la colonne `#id_marque` en « id » dans le JSON
const SQL_MARQUE = 'SELECT `#id_marque` AS id, nom_marque FROM marque';

// Voitures avec leur marque (JOIN) et leur crash-test (LEFT JOIN : la voiture
// apparaît même si elle n'a pas encore de crash-test)
const SQL_VOITURE = 'SELECT v.`#id_voiture` AS id, v.modele, v.annee,
        m.`#id_marque` AS id_marque, m.nom_marque,
        c.`#id_crashtest` AS id_crashtest, c.choc_frontal, c.choc_lateral, c.choc_arriere
    FROM voiture v
    JOIN marque m ON m.`#id_marque` = v.id_marque
    LEFT JOIN crashtest c ON c.id_voiture = v.`#id_voiture`';

// Crash-tests avec le modèle et la marque de la voiture testée
const SQL_CRASHTEST = 'SELECT c.`#id_crashtest` AS id, c.choc_frontal, c.choc_lateral, c.choc_arriere,
        c.id_voiture, v.modele, m.nom_marque
    FROM crashtest c
    JOIN voiture v ON v.`#id_voiture` = c.id_voiture
    JOIN marque m ON m.`#id_marque` = v.id_marque';

/**
 * Transforme une ligne SQL « plate » de voiture en objet JSON imbriqué :
 * { id, modele, annee, marque: {id, nom}, crashtest: {…} ou null }
 */
function formatVoiture(array $r): array
{
    return [
        'id'        => (int) $r['id'],
        'modele'    => $r['modele'],
        'annee'     => (int) $r['annee'],
        'marque'    => ['id' => (int) $r['id_marque'], 'nom' => $r['nom_marque']],
        // Pas de crash-test (LEFT JOIN vide) -> null
        'crashtest' => $r['id_crashtest'] === null ? null : [
            'id'           => (int) $r['id_crashtest'],
            'choc_frontal' => $r['choc_frontal'],
            'choc_lateral' => $r['choc_lateral'],
            'choc_arriere' => $r['choc_arriere'],
        ],
    ];
}

/**
 * Transforme une ligne SQL de crash-test en objet JSON avec la voiture imbriquée.
 */
function formatCrashtest(array $r): array
{
    return [
        'id'           => (int) $r['id'],
        'choc_frontal' => $r['choc_frontal'],
        'choc_lateral' => $r['choc_lateral'],
        'choc_arriere' => $r['choc_arriere'],
        'voiture'      => ['id' => (int) $r['id_voiture'], 'modele' => $r['modele'], 'marque' => $r['nom_marque']],
    ];
}

/** Renvoie une marque par son id, ou une erreur 404 si elle n'existe pas. */
function getMarque(int $id): array
{
    $stmt = db()->prepare(SQL_MARQUE . ' WHERE `#id_marque` = ?');
    $stmt->execute([$id]);
    // fetch() renvoie false si aucune ligne : on envoie alors l'erreur 404
    $r = $stmt->fetch() ?: error('Marque introuvable', 404);
    return ['id' => (int) $r['id'], 'nom_marque' => $r['nom_marque']];
}

/** Renvoie une voiture (avec marque et crash-test) par son id, ou une erreur 404. */
function getVoiture(int $id): array
{
    $stmt = db()->prepare(SQL_VOITURE . ' WHERE v.`#id_voiture` = ?');
    $stmt->execute([$id]);
    return formatVoiture($stmt->fetch() ?: error('Voiture introuvable', 404));
}

/** Renvoie un crash-test (avec sa voiture) par son id, ou une erreur 404. */
function getCrashtest(int $id): array
{
    $stmt = db()->prepare(SQL_CRASHTEST . ' WHERE c.`#id_crashtest` = ?');
    $stmt->execute([$id]);
    return formatCrashtest($stmt->fetch() ?: error('Crash-test introuvable', 404));
}

/**
 * Liste des voitures avec filtres optionnels passés dans l'URL :
 *   ?marque=BMW ou ?marque=1   (nom ou id de la marque)
 *   ?annee=2019                (année exacte)
 *   ?annee_min=2010&annee_max=2020
 *   ?q=golf                    (recherche dans le nom du modèle)
 */
function listVoitures(array $filters): array
{
    $where = [];  // Conditions SQL à ajouter (combinées par AND)
    $params = []; // Valeurs correspondant aux « ? » de la requête préparée

    // Filtre marque : un nombre = id de la marque, sinon = nom de la marque
    if (isset($filters['marque'])) {
        if (ctype_digit((string) $filters['marque'])) {
            $where[] = 'm.`#id_marque` = ?';
        } else {
            $where[] = 'm.nom_marque = ?';
        }
        $params[] = $filters['marque'];
    }

    // Filtres sur l'année : chaque paramètre correspond à un opérateur SQL
    foreach (['annee' => '=', 'annee_min' => '>=', 'annee_max' => '<='] as $key => $op) {
        if (isset($filters[$key])) {
            $where[] = "v.annee $op ?";
            $params[] = (int) $filters[$key];
        }
    }

    // Recherche texte : LIKE '%golf%' trouve « Golf V », « Golf VI »…
    if (isset($filters['q'])) {
        $where[] = 'v.modele LIKE ?';
        $params[] = '%' . $filters['q'] . '%';
    }

    // Construction de la requête finale (WHERE seulement s'il y a des filtres)
    $sql = SQL_VOITURE . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY v.`#id_voiture`';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    // On formate chaque ligne en objet JSON imbriqué
    return array_map('formatVoiture', $stmt->fetchAll());
}

// ---------------------------------------------------------------------------
// Routage : découpe l'URL en /ressource[/id[/sous-ressource]]
// Exemple : /marques/2/voitures -> $resource = 'marques', $id = 2, $sub = 'voitures'
// ---------------------------------------------------------------------------

// PATH_INFO = partie de l'URL après index.php (ex : « /voitures/3 »)
$path = $_SERVER['PATH_INFO'] ?? '';
if ($path === '') {
    // Solution de secours si PATH_INFO n'est pas fourni par le serveur :
    // on retire le dossier du script de l'URL demandée
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $path = substr($uri, strlen($base));
    $path = preg_replace('#^/index\.php#', '', $path);
}

// Découpe le chemin selon les « / » en ignorant les morceaux vides
$parts = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
$resource = $parts[0] ?? ''; // ex : 'voitures'
$id = $parts[1] ?? null;     // ex : '3'
$sub = $parts[2] ?? null;    // ex : 'crashtest'

// L'identifiant doit être composé uniquement de chiffres
if ($id !== null && !ctype_digit($id)) {
    error('Identifiant invalide', 400);
}
$id = $id === null ? null : (int) $id;

// Le try/catch intercepte toute erreur SQL pour renvoyer un message JSON propre (500)
try {
    $pdo = db();

    switch ($resource) {
        // ------------------------------------------------------------ ACCUEIL
        // GET /  -> liste des routes disponibles (documentation rapide)
        case '':
            respond([
                'api' => 'Projet voitures / crash-tests',
                'endpoints' => [
                    'GET|POST /marques', 'GET|PUT|DELETE /marques/{id}', 'GET /marques/{id}/voitures',
                    'GET|POST /voitures (?marque=&annee=&annee_min=&annee_max=&q=)', 'GET|PUT|DELETE /voitures/{id}',
                    'GET /voitures/{id}/crashtest',
                    'GET|POST /crashtests', 'GET|PUT|DELETE /crashtests/{id}',
                ],
            ]);

        // ------------------------------------------------------------ MARQUES
        case 'marques':
            // --- Routes sans id : /marques ---
            if ($id === null) {
                // GET /marques -> toutes les marques
                if ($method === 'GET') {
                    $rows = $pdo->query(SQL_MARQUE . ' ORDER BY `#id_marque`')->fetchAll();
                    respond(array_map(fn($r) => ['id' => (int) $r['id'], 'nom_marque' => $r['nom_marque']], $rows));
                }
                // POST /marques -> création d'une marque {"nom_marque": "..."}
                if ($method === 'POST') {
                    $d = validate(body(), ['nom_marque' => 'string']);
                    $pdo->prepare('INSERT INTO marque (nom_marque) VALUES (?)')->execute([$d['nom_marque']]);
                    // 201 = ressource créée ; on renvoie la marque avec son nouvel id
                    respond(getMarque((int) $pdo->lastInsertId()), 201);
                }
                break; // Autre méthode -> 405 plus bas
            }

            // GET /marques/{id}/voitures -> voitures de cette marque
            if ($sub === 'voitures' && $method === 'GET') {
                getMarque($id); // 404 si la marque n'existe pas
                respond(listVoitures(['marque' => (string) $id]));
            }
            // Toute autre sous-ressource est inconnue
            if ($sub !== null) error('Route introuvable', 404);

            // GET /marques/{id} -> une marque
            if ($method === 'GET') respond(getMarque($id));

            // PUT /marques/{id} -> renommer une marque
            if ($method === 'PUT') {
                getMarque($id); // 404 si inexistante
                $d = validate(body(), ['nom_marque' => 'string']);
                $pdo->prepare('UPDATE marque SET nom_marque = ? WHERE `#id_marque` = ?')->execute([$d['nom_marque'], $id]);
                respond(getMarque($id));
            }

            // DELETE /marques/{id} -> supprimer une marque (seulement si aucune voiture ne l'utilise)
            if ($method === 'DELETE') {
                getMarque($id);
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM voiture WHERE id_marque = ?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    // 409 = conflit : la clé étrangère empêcherait la suppression
                    error('Impossible de supprimer : des voitures sont liées à cette marque', 409);
                }
                $pdo->prepare('DELETE FROM marque WHERE `#id_marque` = ?')->execute([$id]);
                respond(null, 204); // 204 = supprimé, pas de contenu
            }
            break;

        // ----------------------------------------------------------- VOITURES
        case 'voitures':
            // Champs attendus pour créer / modifier une voiture
            $rules = ['modele' => 'string', 'annee' => 'int', 'id_marque' => 'int'];

            // --- Routes sans id : /voitures ---
            if ($id === null) {
                // GET /voitures -> liste filtrable avec les paramètres de l'URL ($_GET)
                if ($method === 'GET') respond(listVoitures($_GET));

                // POST /voitures -> création d'une voiture
                if ($method === 'POST') {
                    $d = validate(body(), $rules);
                    // La marque indiquée doit exister (clé étrangère)
                    if (!exists('marque', '#id_marque', $d['id_marque'])) error('Marque inexistante', 422);
                    $pdo->prepare('INSERT INTO voiture (modele, annee, id_marque) VALUES (?, ?, ?)')
                        ->execute([$d['modele'], $d['annee'], $d['id_marque']]);
                    respond(getVoiture((int) $pdo->lastInsertId()), 201);
                }
                break;
            }

            // GET /voitures/{id}/crashtest -> le crash-test de cette voiture
            if ($sub === 'crashtest' && $method === 'GET') {
                $v = getVoiture($id);
                // ?? : si crashtest vaut null, on renvoie une erreur 404
                $v['crashtest'] ?? error('Aucun crash-test pour cette voiture', 404);
                respond(getCrashtest($v['crashtest']['id']));
            }
            if ($sub !== null) error('Route introuvable', 404);

            // GET /voitures/{id} -> une voiture
            if ($method === 'GET') respond(getVoiture($id));

            // PUT /voitures/{id} -> modification (partielle : on peut n'envoyer que l'année, par ex.)
            if ($method === 'PUT') {
                getVoiture($id);
                $d = validate(body(), $rules, true);
                if (isset($d['id_marque']) && !exists('marque', '#id_marque', $d['id_marque'])) error('Marque inexistante', 422);
                // Construit « `modele` = ?, `annee` = ? » uniquement avec les champs envoyés
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($d)));
                // ... déplie les valeurs des champs, suivies de l'id pour le WHERE
                $pdo->prepare("UPDATE voiture SET $set WHERE `#id_voiture` = ?")->execute([...array_values($d), $id]);
                respond(getVoiture($id));
            }

            // DELETE /voitures/{id} -> supprime la voiture ET son crash-test
            if ($method === 'DELETE') {
                getVoiture($id);
                // Transaction : les deux suppressions réussissent ensemble ou pas du tout
                $pdo->beginTransaction();
                // D'abord le crash-test (il dépend de la voiture via la clé étrangère)
                $pdo->prepare('DELETE FROM crashtest WHERE id_voiture = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM voiture WHERE `#id_voiture` = ?')->execute([$id]);
                $pdo->commit();
                respond(null, 204);
            }
            break;

        // --------------------------------------------------------- CRASHTESTS
        case 'crashtests':
            // Champs attendus : les trois notes (texte, ex : "5") et la voiture testée
            $rules = ['choc_frontal' => 'string', 'choc_lateral' => 'string', 'choc_arriere' => 'string', 'id_voiture' => 'int'];

            // --- Routes sans id : /crashtests ---
            if ($id === null) {
                // GET /crashtests -> tous les crash-tests
                if ($method === 'GET') {
                    $rows = $pdo->query(SQL_CRASHTEST . ' ORDER BY c.`#id_crashtest`')->fetchAll();
                    respond(array_map('formatCrashtest', $rows));
                }
                // POST /crashtests -> création d'un crash-test
                if ($method === 'POST') {
                    $d = validate(body(), $rules);
                    // La voiture indiquée doit exister (clé étrangère)
                    if (!exists('voiture', '#id_voiture', $d['id_voiture'])) error('Voiture inexistante', 422);
                    $pdo->prepare('INSERT INTO crashtest (choc_frontal, choc_lateral, choc_arriere, id_voiture) VALUES (?, ?, ?, ?)')
                        ->execute([$d['choc_frontal'], $d['choc_lateral'], $d['choc_arriere'], $d['id_voiture']]);
                    respond(getCrashtest((int) $pdo->lastInsertId()), 201);
                }
                break;
            }
            if ($sub !== null) error('Route introuvable', 404);

            // GET /crashtests/{id} -> un crash-test
            if ($method === 'GET') respond(getCrashtest($id));

            // PUT /crashtests/{id} -> modification partielle des notes
            if ($method === 'PUT') {
                getCrashtest($id);
                $d = validate(body(), $rules, true);
                if (isset($d['id_voiture']) && !exists('voiture', '#id_voiture', $d['id_voiture'])) error('Voiture inexistante', 422);
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($d)));
                $pdo->prepare("UPDATE crashtest SET $set WHERE `#id_crashtest` = ?")->execute([...array_values($d), $id]);
                respond(getCrashtest($id));
            }

            // DELETE /crashtests/{id} -> suppression
            if ($method === 'DELETE') {
                getCrashtest($id);
                $pdo->prepare('DELETE FROM crashtest WHERE `#id_crashtest` = ?')->execute([$id]);
                respond(null, 204);
            }
            break;

        // Ressource inconnue (ex : /avions)
        default:
            error('Route introuvable', 404);
    }

    // Si on arrive ici, la route existe mais pas avec cette méthode HTTP
    error('Méthode non autorisée', 405);
} catch (PDOException $e) {
    // Erreur SQL : on annule une éventuelle transaction en cours, puis on renvoie une erreur 500
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Erreur base de données : ' . $e->getMessage(), 500);
}
