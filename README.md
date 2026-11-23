# CrashTest – Notes crash-test par modèle de voiture

Site web + API REST qui permettent de choisir sa voiture (marque → modèle) et de voir
ses notes de crash-test (choc frontal, latéral, arrière, sur 5).

## Contenu du dépôt
| Dossier | Rôle |
|---|---|
| `database/projet.sql` | Base MySQL `projet` (tables `marque`, `voiture`, `crashtest`) |
| `database/utilisateur.sql` | Table `utilisateur` du système de connexion (à importer après `projet.sql`) |
| `api/` | API REST en PHP 8 + PDO (voir `api/README.md` pour toutes les routes) |
| `site/` | Site web en HTML / CSS / JavaScript qui appelle l'API |

## Installation locale (XAMPP)
1. Démarrer **Apache** et **MySQL** dans XAMPP.
2. Dans phpMyAdmin, créer la base `projet` puis importer `database/projet.sql`, puis `database/utilisateur.sql`.
3. Copier les dossiers `api/` et `site/` dans `C:\xampp\htdocs\`.
4. Ouvrir http://localhost/site/

Comptes utilisateurs : bouton « S'inscrire » en haut à droite. Une fois connecté, on peut enregistrer
« ma voiture » (bouton dans la fiche d'un modèle), et l'API autorise l'ajout / la modification / la suppression de données.

L'API est accessible sur http://localhost/api/ (ex : http://localhost/api/voitures?marque=Peugeot).

> Le site a besoin de PHP et MySQL : il ne fonctionne pas sur GitHub Pages (hébergement statique uniquement).
