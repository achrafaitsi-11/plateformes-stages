-- ============================================================================
-- Plateforme de gestion des offres de stages : schéma v1 (brouillon à valider)
-- Compatible MySQL 8 et MariaDB 10.4+ (XAMPP). Encodage utf8mb4.
--
-- Réimporter ce fichier remet la base à zéro (DROP puis CREATE).
-- Toute modification ensuite passe par sql/migrations/NNN_description.sql
-- et une pull request relue par M1. Valeurs d'ENUM sans accents.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stages;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  password_resets, journal_audit, signalements, messages, notifications,
  alertes_offres, favoris, rapports_stage, activites_stage, stages,
  historique_candidature, candidatures, offre_competences, offres,
  langues_etudiant, certifications, projets, experiences, competences_etudiant, cv,
  invitations_pedago, entreprises, etudiants, responsables_pedago, users,
  parametres, domaines_autorises, competences, categories, filieres, etablissements;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Tables de référence
-- ---------------------------------------------------------------------------

CREATE TABLE etablissements (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom   VARCHAR(150) NOT NULL,
  ville VARCHAR(80)  NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE filieres (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etablissement_id INT UNSIGNED NOT NULL,
  code             VARCHAR(20)  NOT NULL,
  nom              VARCHAR(150) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_filieres (etablissement_id, code),
  CONSTRAINT fk_filieres_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domaines de stage (informatique, réseaux, data, cybersécurité, finance...)
CREATE TABLE categories (
  id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competences (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom       VARCHAR(100) NOT NULL,
  categorie VARCHAR(100) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_competences_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liste blanche (étudiants, resp. péda) et liste noire (emails gratuits côté entreprise)
CREATE TABLE domaines_autorises (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  domaine VARCHAR(150) NOT NULL,
  type    ENUM('etudiant','pedago','blacklist_entreprise') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_domaines (domaine, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres de l'admin (ajoutée par rapport au cahier des charges, section 8.2)
CREATE TABLE parametres (
  cle    VARCHAR(60)  NOT NULL,
  valeur VARCHAR(255) NOT NULL,
  PRIMARY KEY (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO parametres (cle, valeur) VALUES
  ('mode_validation_etudiant', 'souple'),   -- souple | strict
  ('duree_max_stage_semaines', '26'),
  ('taille_max_cv_mo', '2'),
  ('mode_email', 'simulation');              -- simulation | smtp

-- ---------------------------------------------------------------------------
-- Comptes
-- ---------------------------------------------------------------------------

CREATE TABLE users (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email              VARCHAR(150) NOT NULL,
  password_hash      VARCHAR(255) NOT NULL,
  role               ENUM('etudiant','entreprise','pedago','admin') NOT NULL,
  statut             ENUM('non_verifie','en_attente','actif','rejete','suspendu') NOT NULL DEFAULT 'non_verifie',
  email_verifie_at   DATETIME NULL,
  token_verification VARCHAR(64) NULL,
  tentatives_echec   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloque_jusqua      DATETIME NULL,
  derniere_connexion DATETIME NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_token (token_verification)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE responsables_pedago (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  nom              VARCHAR(80)  NOT NULL,
  prenom           VARCHAR(80)  NOT NULL,
  telephone        VARCHAR(20)  NULL,
  etablissement_id INT UNSIGNED NULL,
  departement      VARCHAR(100) NULL,
  grade            VARCHAR(60)  NULL,
  justificatif     VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pedago_user (user_id),
  CONSTRAINT fk_pedago_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_pedago_etab FOREIGN KEY (etablissement_id) REFERENCES etablissements (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE etudiants (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              INT UNSIGNED NOT NULL,
  nom                  VARCHAR(80)  NOT NULL,
  prenom               VARCHAR(80)  NOT NULL,
  cne                  VARCHAR(20)  NOT NULL,
  telephone            VARCHAR(20)  NULL,
  ville                VARCHAR(80)  NULL,
  date_naissance       DATE NULL,
  photo                VARCHAR(255) NULL,
  etablissement_id     INT UNSIGNED NULL,
  filiere_id           INT UNSIGNED NULL,
  niveau               VARCHAR(30)  NULL,
  annee_universitaire  VARCHAR(9)   NULL,
  bio                  TEXT NULL,
  github               VARCHAR(255) NULL,
  linkedin             VARCHAR(255) NULL,
  portfolio            VARCHAR(255) NULL,
  certificat_scolarite VARCHAR(255) NULL,
  disponibilite        DATE NULL,
  -- préférences de stage (matching)
  pref_domaine_id      INT UNSIGNED NULL,
  pref_type            ENUM('observation','technique','fin_etudes') NULL,
  pref_mode            ENUM('presentiel','distance','hybride') NULL,
  pref_duree_min       TINYINT UNSIGNED NULL,          -- en semaines
  pref_duree_max       TINYINT UNSIGNED NULL,          -- en semaines
  pref_remuneration    DECIMAL(8,2) NULL,              -- mensuel minimum souhaité, NULL = indifférent
  pref_villes          JSON NULL,                      -- villes acceptées
  visibilite           JSON NULL,                      -- infos visibles par les entreprises
  encadrant_pedago_id  INT UNSIGNED NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_etudiants_user (user_id),
  UNIQUE KEY uq_etudiants_cne (cne),
  CONSTRAINT fk_etudiants_user    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_etudiants_etab    FOREIGN KEY (etablissement_id) REFERENCES etablissements (id),
  CONSTRAINT fk_etudiants_filiere FOREIGN KEY (filiere_id) REFERENCES filieres (id),
  CONSTRAINT fk_etudiants_domaine FOREIGN KEY (pref_domaine_id) REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_etudiants_pedago  FOREIGN KEY (encadrant_pedago_id) REFERENCES responsables_pedago (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entreprises (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NOT NULL,
  raison_sociale    VARCHAR(150) NOT NULL,
  secteur           VARCHAR(100) NULL,
  forme_juridique   VARCHAR(50)  NULL,
  ice               CHAR(15)     NOT NULL,
  rc                VARCHAR(50)  NOT NULL,
  adresse           VARCHAR(255) NULL,
  ville             VARCHAR(80)  NULL,
  site_web          VARCHAR(255) NULL,
  telephone         VARCHAR(20)  NULL,
  logo              VARCHAR(255) NULL,
  description       TEXT NULL,
  rep_nom           VARCHAR(80)  NULL,
  rep_prenom        VARCHAR(80)  NULL,
  rep_fonction      VARCHAR(100) NULL,
  rep_email         VARCHAR(150) NULL,
  justificatif      VARCHAR(255) NULL,
  statut_validation ENUM('en_attente','validee','rejetee','suspendue') NOT NULL DEFAULT 'en_attente',
  motif_rejet       TEXT NULL,
  valide_par        INT UNSIGNED NULL,                 -- users.id de l'admin
  valide_le         DATETIME NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_entreprises_user (user_id),
  UNIQUE KEY uq_entreprises_ice (ice),
  UNIQUE KEY uq_entreprises_rc (rc),
  CONSTRAINT fk_entreprises_user  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_entreprises_admin FOREIGN KEY (valide_par) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invitations_pedago (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code      VARCHAR(32)  NOT NULL,
  expire_le DATETIME     NOT NULL,
  utilise   TINYINT(1)   NOT NULL DEFAULT 0,
  cree_par  INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invitations_code (code),
  CONSTRAINT fk_invitations_admin FOREIGN KEY (cree_par) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Profil étudiant
-- ---------------------------------------------------------------------------

CREATE TABLE cv (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id INT UNSIGNED NOT NULL,
  fichier     VARCHAR(255) NOT NULL,
  nom         VARCHAR(150) NULL,
  principal   TINYINT(1)   NOT NULL DEFAULT 0,
  uploaded_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cv_etudiant (etudiant_id),
  CONSTRAINT fk_cv_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competences_etudiant (
  etudiant_id   INT UNSIGNED NOT NULL,
  competence_id INT UNSIGNED NOT NULL,
  niveau        ENUM('debutant','intermediaire','avance') NOT NULL DEFAULT 'debutant',
  PRIMARY KEY (etudiant_id, competence_id),
  KEY idx_comp_etudiant_comp (competence_id),
  CONSTRAINT fk_ce_etudiant   FOREIGN KEY (etudiant_id)   REFERENCES etudiants (id)   ON DELETE CASCADE,
  CONSTRAINT fk_ce_competence FOREIGN KEY (competence_id) REFERENCES competences (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiences (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id INT UNSIGNED NOT NULL,
  poste       VARCHAR(150) NOT NULL,
  entreprise  VARCHAR(150) NULL,
  date_debut  DATE NULL,
  date_fin    DATE NULL,
  description TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_experiences_etudiant (etudiant_id),
  CONSTRAINT fk_experiences_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projets (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id  INT UNSIGNED NOT NULL,
  titre        VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  technologies VARCHAR(255) NULL,
  lien         VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_projets_etudiant (etudiant_id),
  CONSTRAINT fk_projets_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certifications (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id    INT UNSIGNED NOT NULL,
  intitule       VARCHAR(150) NOT NULL,
  organisme      VARCHAR(150) NULL,
  date_obtention DATE NULL,
  PRIMARY KEY (id),
  KEY idx_certifications_etudiant (etudiant_id),
  CONSTRAINT fk_certifications_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE langues_etudiant (
  etudiant_id INT UNSIGNED NOT NULL,
  langue      VARCHAR(50)  NOT NULL,
  niveau      VARCHAR(30)  NOT NULL,
  PRIMARY KEY (etudiant_id, langue),
  CONSTRAINT fk_langues_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Offres
-- ---------------------------------------------------------------------------

CREATE TABLE offres (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  entreprise_id      INT UNSIGNED NOT NULL,
  titre              VARCHAR(200) NOT NULL,
  categorie_id       INT UNSIGNED NULL,
  type_stage         ENUM('observation','technique','fin_etudes') NOT NULL,
  description        TEXT NOT NULL,
  objectifs          TEXT NULL,
  duree_semaines     SMALLINT UNSIGNED NULL,
  date_debut         DATE NULL,
  date_fin           DATE NULL,
  date_limite        DATE NULL,
  nb_postes          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  mode               ENUM('presentiel','distance','hybride') NOT NULL DEFAULT 'presentiel',
  ville              VARCHAR(80)  NULL,
  adresse            VARCHAR(255) NULL,
  remunere           TINYINT(1)   NOT NULL DEFAULT 0,
  montant            DECIMAL(8,2) NULL,
  avantages          JSON NULL,
  filieres_visees    JSON NULL,
  niveaux_vises      JSON NULL,
  langues_exigees    JSON NULL,
  documents_demandes JSON NULL,
  statut             ENUM('brouillon','publiee','cloturee','expiree','masquee') NOT NULL DEFAULT 'brouillon',
  vues               INT UNSIGNED NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_offres_filtre (statut, categorie_id, ville),
  KEY idx_offres_entreprise (entreprise_id),
  FULLTEXT KEY ft_offres (titre, description),
  CONSTRAINT fk_offres_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises (id) ON DELETE CASCADE,
  CONSTRAINT fk_offres_categorie  FOREIGN KEY (categorie_id)  REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offre_competences (
  offre_id      INT UNSIGNED NOT NULL,
  competence_id INT UNSIGNED NOT NULL,
  obligatoire   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (offre_id, competence_id),
  CONSTRAINT fk_oc_offre      FOREIGN KEY (offre_id)      REFERENCES offres (id)      ON DELETE CASCADE,
  CONSTRAINT fk_oc_competence FOREIGN KEY (competence_id) REFERENCES competences (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Candidatures
-- ---------------------------------------------------------------------------

CREATE TABLE candidatures (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id       INT UNSIGNED NOT NULL,
  offre_id          INT UNSIGNED NOT NULL,
  cv_id             INT UNSIGNED NULL,
  lettre_motivation TEXT NULL,
  lettre_fichier    VARCHAR(255) NULL,
  message           TEXT NULL,
  documents_supp    JSON NULL,
  statut            ENUM('envoyee','en_cours_examen','preselectionnee','entretien_planifie',
                         'acceptee','refusee','retiree','confirmee') NOT NULL DEFAULT 'envoyee',
  motif_refus       TEXT NULL,
  note_entreprise   TEXT NULL,                          -- privée à l'entreprise
  score_matching    TINYINT UNSIGNED NULL,
  date_entretien    DATETIME NULL,
  lieu_entretien    VARCHAR(255) NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_candidature (etudiant_id, offre_id),
  KEY idx_candidatures_offre (offre_id, statut),
  KEY idx_candidatures_etudiant (etudiant_id),
  CONSTRAINT fk_cand_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE,
  CONSTRAINT fk_cand_offre    FOREIGN KEY (offre_id)    REFERENCES offres (id)    ON DELETE CASCADE,
  CONSTRAINT fk_cand_cv       FOREIGN KEY (cv_id)       REFERENCES cv (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historique_candidature (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidature_id INT UNSIGNED NOT NULL,
  ancien_statut  VARCHAR(30)  NULL,
  nouveau_statut VARCHAR(30)  NOT NULL,
  par_user_id    INT UNSIGNED NULL,
  date           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_hist_candidature (candidature_id),
  CONSTRAINT fk_hist_candidature FOREIGN KEY (candidature_id) REFERENCES candidatures (id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_user        FOREIGN KEY (par_user_id)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Stages et suivi
-- ---------------------------------------------------------------------------

CREATE TABLE stages (
  id                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidature_id             INT UNSIGNED NOT NULL,
  etudiant_id                INT UNSIGNED NOT NULL,
  entreprise_id              INT UNSIGNED NOT NULL,
  offre_id                   INT UNSIGNED NOT NULL,
  encadrant_entreprise_nom   VARCHAR(150) NULL,
  encadrant_entreprise_email VARCHAR(150) NULL,
  encadrant_pedago_id        INT UNSIGNED NULL,
  sujet                      VARCHAR(255) NULL,
  date_debut                 DATE NULL,
  date_fin                   DATE NULL,
  statut                     ENUM('a_valider','valide','en_cours','rapport_depose','rapport_en_revision',
                                  'rapport_valide','termine','refuse','annule') NOT NULL DEFAULT 'a_valider',
  evaluation_entreprise      TEXT NULL,
  evaluation_pedago          TEXT NULL,
  attestation                VARCHAR(255) NULL,
  created_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stages_candidature (candidature_id),
  KEY idx_stages_etudiant (etudiant_id),
  KEY idx_stages_entreprise (entreprise_id),
  CONSTRAINT fk_stages_candidature FOREIGN KEY (candidature_id) REFERENCES candidatures (id),
  CONSTRAINT fk_stages_etudiant    FOREIGN KEY (etudiant_id)    REFERENCES etudiants (id),
  CONSTRAINT fk_stages_entreprise  FOREIGN KEY (entreprise_id)  REFERENCES entreprises (id),
  CONSTRAINT fk_stages_offre       FOREIGN KEY (offre_id)       REFERENCES offres (id),
  CONSTRAINT fk_stages_pedago      FOREIGN KEY (encadrant_pedago_id) REFERENCES responsables_pedago (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activites_stage (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  stage_id              INT UNSIGNED NOT NULL,
  semaine               DATE NOT NULL,                  -- lundi de la semaine concernée
  description           TEXT NOT NULL,
  commentaire_encadrant TEXT NULL,
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activites_stage (stage_id),
  CONSTRAINT fk_activites_stage FOREIGN KEY (stage_id) REFERENCES stages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rapports_stage (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  stage_id   INT UNSIGNED NOT NULL,
  fichier    VARCHAR(255) NOT NULL,
  version    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  statut     ENUM('depose','en_revision','a_corriger','valide') NOT NULL DEFAULT 'depose',
  commentaire TEXT NULL,
  depose_le  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rapports_stage (stage_id),
  CONSTRAINT fk_rapports_stage FOREIGN KEY (stage_id) REFERENCES stages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tables transverses
-- ---------------------------------------------------------------------------

CREATE TABLE favoris (
  etudiant_id INT UNSIGNED NOT NULL,
  offre_id    INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (etudiant_id, offre_id),
  CONSTRAINT fk_favoris_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE,
  CONSTRAINT fk_favoris_offre    FOREIGN KEY (offre_id)    REFERENCES offres (id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alertes_offres (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  etudiant_id INT UNSIGNED NOT NULL,
  criteres    JSON NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_alertes_etudiant (etudiant_id),
  CONSTRAINT fk_alertes_etudiant FOREIGN KEY (etudiant_id) REFERENCES etudiants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  type       VARCHAR(40)  NOT NULL,
  message    VARCHAR(255) NOT NULL,
  lien       VARCHAR(255) NULL,
  lue        TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, lue),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  expediteur_id   INT UNSIGNED NOT NULL,
  destinataire_id INT UNSIGNED NOT NULL,
  candidature_id  INT UNSIGNED NULL,
  stage_id        INT UNSIGNED NULL,
  contenu         TEXT NOT NULL,
  lu              TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_dest (destinataire_id, lu),
  KEY idx_messages_candidature (candidature_id),
  KEY idx_messages_stage (stage_id),
  CONSTRAINT fk_messages_exp         FOREIGN KEY (expediteur_id)   REFERENCES users (id)        ON DELETE CASCADE,
  CONSTRAINT fk_messages_dest        FOREIGN KEY (destinataire_id) REFERENCES users (id)        ON DELETE CASCADE,
  CONSTRAINT fk_messages_candidature FOREIGN KEY (candidature_id)  REFERENCES candidatures (id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_stage       FOREIGN KEY (stage_id)        REFERENCES stages (id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE signalements (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  auteur_id  INT UNSIGNED NOT NULL,
  cible_type ENUM('entreprise','offre','utilisateur') NOT NULL,
  cible_id   INT UNSIGNED NOT NULL,
  motif      TEXT NOT NULL,
  statut     ENUM('ouvert','traite','rejete') NOT NULL DEFAULT 'ouvert',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_signalements_statut (statut),
  CONSTRAINT fk_signalements_auteur FOREIGN KEY (auteur_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE journal_audit (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(80)  NOT NULL,
  detail     TEXT NULL,
  ip         VARCHAR(45)  NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_date (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,
  expire_le  DATETIME     NOT NULL,
  utilise    TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_resets_token (token_hash),
  KEY idx_resets_user (user_id),
  CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
