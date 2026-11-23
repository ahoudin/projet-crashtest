# API REST – projet voitures / crash-tests

PHP 8 + PDO, sans framework. Base : `projet` (tables `marque`, `voiture`, `crashtest`).

## Installation (WAMP)
1. Importer `projet Ancelinho akbar.sql` dans phpMyAdmin.
2. Copier le dossier `api/` dans `C:\wamp64\www\`.
3. Adapter `config.php` si besoin (utilisateur / mot de passe MySQL).
4. Tester : http://localhost/api/index.php/voitures
   (ou http://localhost/api/voitures si `rewrite_module` est activé dans Apache).

## Site web (dossier `site/`)
Copier aussi `site/` dans `C:\wamp64\www\` (à côté de `api/`), puis ouvrir http://localhost/site/.
Le site appelle `../api/index.php` : changer la constante `API` en haut de `site/app.js` si l'API est ailleurs.

## Routes
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

Codes : 200, 201 (créé), 204 (supprimé), 400, 404, 405, 409, 422 (validation), 500.

## Exemples (PowerShell)
```powershell
Invoke-RestMethod http://localhost/api/index.php/voitures?marque=Peugeot
Invoke-RestMethod -Method Post -Uri http://localhost/api/index.php/marques -ContentType 'application/json' -Body '{"nom_marque":"Renault"}'
```
