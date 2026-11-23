# API REST – projet voitures / crash-tests

PHP 8 + PDO, sans framework. Base : `projet` (tables `marque`, `voiture`, `crashtest`, `utilisateur`).

## Installation (XAMPP)
1. Dans phpMyAdmin, créer la base `projet`, puis importer `database/projet.sql` et ensuite `database/utilisateur.sql`.
2. Copier le dossier `api/` dans `C:\xampp\htdocs\`.
3. Adapter `config.php` si besoin (utilisateur / mot de passe MySQL).
4. Tester : http://localhost/api/voitures (ou http://localhost/api/index.php/voitures si mod_rewrite est désactivé).

## Site web (dossier `site/`)
Copier aussi `site/` dans `C:\xampp\htdocs\` (à côté de `api/`), puis ouvrir http://localhost/site/.
La connexion utilise un cookie de session : ouvrir le site via `http://localhost/site/` (pas en double-cliquant sur le fichier).

## Authentification
Les lectures (GET) sont publiques. **POST, PUT et DELETE sur marques / voitures / crashtests nécessitent d'être connecté** (sinon 401).
La session est gérée par PHP (cookie `PHPSESSID`, HttpOnly, SameSite=Lax). Les mots de passe sont hachés avec `password_hash()` (bcrypt).

| Méthode | Route | Description |
|---|---|---|
| POST | /auth/inscription | `{"pseudo": "jean", "email": "jean@exemple.fr", "mot_de_passe": "8 caractères min"}` → crée le compte et connecte (201) |
| POST | /auth/connexion | `{"identifiant": "pseudo ou e-mail", "mot_de_passe": "..."}` → connecte (401 si incorrect) |
| POST | /auth/deconnexion | Déconnecte (204) |
| GET | /auth/moi | `{"utilisateur": {...}}` si connecté, `{"utilisateur": null}` sinon |
| PUT | /auth/moi | `{"id_voiture": 12}` enregistre « ma voiture » (`null` pour la retirer) |

## Routes des données
| Méthode | Route | Description |
|---|---|---|
| GET | /marques | Liste des marques |
| POST | /marques | Créer `{"nom_marque": "Renault"}` |
| GET / PUT / DELETE | /marques/{id} | Lire / modifier / supprimer (409 si voitures liées) |
| GET | /marques/{id}/voitures | Voitures d'une marque |
| GET | /voitures | Liste (filtres : `?marque=BMW` ou `?marque=1`, `annee`, `annee_min`, `annee_max`, `q=golf`) |
| POST | /voitures | Créer `{"modele": "Clio V", "annee": 2019, "id_marque": 4}` |
| GET / PUT / DELETE | /voitures/{id} | PUT partiel accepté ; DELETE supprime aussi le crash-test |
| GET | /voitures/{id}/crashtest | Crash-test de la voiture |
| GET | /crashtests | Liste des crash-tests |
| POST | /crashtests | Créer `{"choc_frontal":"5","choc_lateral":"5","choc_arriere":"4","id_voiture":12}` |
| GET / PUT / DELETE | /crashtests/{id} | Lire / modifier / supprimer |

Codes : 200, 201 (créé), 204 (supprimé), 400, 401 (non connecté / identifiants faux), 404, 405, 409 (conflit), 422 (validation), 500.

## Exemples (PowerShell)
```powershell
Invoke-RestMethod http://localhost/api/voitures?marque=Peugeot
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
Invoke-RestMethod -Method Post -Uri http://localhost/api/auth/connexion -WebSession $s -ContentType 'application/json' -Body '{"identifiant":"jean","mot_de_passe":"..."}'
Invoke-RestMethod -Method Post -Uri http://localhost/api/marques -WebSession $s -ContentType 'application/json' -Body '{"nom_marque":"Renault"}'
```
