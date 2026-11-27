## Ce que fait cette PR
<!-- 1 à 3 lignes. Référence les tâches du tableau de suivi (ex. T12, T13). -->

## Comment tester
<!-- Compte à utiliser (voir seed.sql), pages à ouvrir, résultat attendu. -->

## Checklist
- [ ] Requêtes SQL préparées (PDO), aucune concaténation
- [ ] Toute sortie HTML passe par `e()`
- [ ] Formulaires POST : `csrf_field()` + `csrf_check()`
- [ ] Accès vérifié : `requireRole()` et propriétaire de la ressource (pas d'IDOR)
- [ ] Changement de schéma : fichier dans `sql/migrations/` ajouté
- [ ] Testé en local sur XAMPP, aucune erreur PHP affichée
- [ ] Ligne du tableau de suivi mise à jour
