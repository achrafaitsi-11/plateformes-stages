-- ============================================================================
-- Données de démonstration (M1). À importer APRÈS schema.sql (et seed_referentiels.sql de M5).
-- Mot de passe de TOUS les comptes ci-dessous : Demo1234!   (développement local uniquement)
-- Peut être réimporté : INSERT IGNORE sur les lignes de base.
-- ============================================================================
USE stages;

INSERT IGNORE INTO etablissements (id, nom, ville) VALUES (1, 'ENSA El Jadida', 'El Jadida');
INSERT IGNORE INTO filieres (id, etablissement_id, code, nom) VALUES
  (1, 1, 'GI',  'Génie Informatique'),
  (2, 1, 'CSI', 'Cybersécurité et Systèmes d''Information');
INSERT IGNORE INTO categories (id, nom) VALUES (1, 'Informatique'), (2, 'Cybersécurité');

SET @pwd = '$2y$10$dadC..5w3j1I.jGmXj0vturN3WB9OFOdcyxAKj7bhz1BJHe5CfTmu';

-- Comptes (statut actif, email vérifié)
INSERT INTO users (id, email, password_hash, role, statut, email_verifie_at) VALUES
  (1, 'admin@stages.test',     @pwd, 'admin',      'actif', NOW()),
  (2, 'etudiant1@stages.test', @pwd, 'etudiant',   'actif', NOW()),
  (3, 'etudiant2@stages.test', @pwd, 'etudiant',   'actif', NOW()),
  (4, 'entreprise1@stages.test', @pwd, 'entreprise', 'actif', NOW()),
  (5, 'entreprise2@stages.test', @pwd, 'entreprise', 'actif', NOW()),
  (6, 'pedago@stages.test',    @pwd, 'pedago',     'actif', NOW())
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO responsables_pedago (id, user_id, nom, prenom, etablissement_id, departement, grade) VALUES
  (1, 6, 'Alami', 'Karim', 1, 'Génie Informatique', 'Professeur')
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO etudiants (id, user_id, nom, prenom, cne, ville, etablissement_id, filiere_id, niveau, annee_universitaire, encadrant_pedago_id) VALUES
  (1, 2, 'Benani',  'Salma', 'R130000001', 'El Jadida',  1, 1, '2eme annee', '2026-2027', 1),
  (2, 3, 'Idrissi', 'Omar',  'R130000002', 'Casablanca', 1, 2, '2eme annee', '2026-2027', 1)
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO entreprises (id, user_id, raison_sociale, secteur, ice, rc, ville, rep_nom, rep_prenom, rep_fonction, rep_email, statut_validation, valide_par, valide_le) VALUES
  (1, 4, 'TechAtlas SARL', 'Informatique', '001234567000011', 'RC-CASA-1001', 'Casablanca', 'Tazi',   'Nadia', 'DRH', 'rh@techatlas.test', 'validee', 1, NOW()),
  (2, 5, 'SecuMaroc SA',   'Cybersécurité', '001234567000022', 'RC-RABAT-2002', 'Rabat',      'Fassi',  'Youssef', 'Directeur', 'contact@secumaroc.test', 'validee', 1, NOW())
ON DUPLICATE KEY UPDATE user_id = user_id;
