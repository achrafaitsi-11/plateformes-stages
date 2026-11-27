# Plateforme de gestion des offres de stages

Projet 10, ENSA El Jadida. PHP 8.1+, MySQL/MariaDB, Bootstrap 5. Remise : **samedi 12 décembre 2026**.

Cahier des charges : `docs/cahier_des_charges.md`. Plan et tableau de suivi : voir les liens partagés par M1.

## Installation locale (XAMPP)

1. Installer XAMPP avec PHP 8.1 ou plus (toute l'équipe la même version) et démarrer Apache + MySQL.
2. Cloner le dépôt dans `htdocs/` : `git clone <url> plateforme-stages`.
3. Copier `config/config.example.php` en `config/config.php` (ce fichier n'est jamais versionné) et vérifier que `BASE_URL` s'est bien détectée (affichée par healthcheck.php).
4. Dans phpMyAdmin, importer `sql/schema.sql` (crée la base `stages`), puis `sql/seed.sql` quand M1 l'aura ajouté.
5. Ouvrir `http://localhost/plateforme-stages/public/healthcheck.php` : tout doit afficher OK.

## Structure

| Dossier | Contenu |
|---|---|
| `public/` | Seules pages accessibles par le navigateur, un sous-dossier par rôle (`etudiant/`, `entreprise/`, `pedago/`, `admin/`) |
| `includes/` | Fonctions partagées (auth, csrf, upload, notifications, matching...) |
| `config/` | Configuration et connexion PDO |
| `sql/` | `schema.sql`, seeds, `migrations/` |
| `storage/` | Fichiers déposés (CV, justificatifs...), hors racine web |

## Règles d'équipe

- Une branche par tâche (`m3/offres-crud`), jamais de commit direct sur `main`. Pull request relue par au moins une personne.
- Schéma de base : on ne modifie pas `schema.sql` à la main une fois le socle fusionné. Ajouter `sql/migrations/NNN_description.sql` et le signaler dans la PR (relue par M1).
- Valeurs d'ENUM sans accents (`en_attente`, `presentiel`).
- Sécurité, non négociable : requêtes préparées PDO, `e()` sur toute sortie, `csrf_field()` + `csrf_check()` sur chaque POST, `requireRole()` puis vérification que la ressource appartient bien à l'utilisateur.
- Fonctions partagées : n'appeler que les contrats déjà définis dans `includes/` (`requireRole`, `notify`, `uploadFichier`, `changerStatutCandidature`, `creerStage`, `calculerScore`). Les stubs lèvent une exception ou renvoient une valeur neutre tant que leur propriétaire n'a pas livré.
- Aucun mot de passe, clé ou fichier utilisateur dans git.

## Comptes de démonstration

Importer `sql/seed.sql` après `schema.sql`. Mot de passe de tous les comptes : `Demo1234!` (local uniquement).

| Rôle | Email |
|---|---|
| Admin | admin@stages.test |
| Étudiants | etudiant1@stages.test, etudiant2@stages.test |
| Entreprises (validées) | entreprise1@stages.test, entreprise2@stages.test |
| Pédagogie | pedago@stages.test |
