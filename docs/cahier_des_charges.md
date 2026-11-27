# Projet 10 — Plateforme de gestion des offres de stages

**Technologies imposées :** PHP · HTML · CSS (ou Bootstrap) · MySQL
**Module :** Développement Web — ENSA El Jadida, CCN2

---

## Table des matières

1. [Vision générale](#1-vision-générale)
2. [Les 4 acteurs et leurs droits](#2-les-4-acteurs-et-leurs-droits)
3. [Parcours d'entrée : accueil, inscription, connexion](#3-parcours-dentrée--accueil-inscription-connexion)
4. [Vérification d'authenticité des comptes](#4-vérification-dauthenticité-des-comptes)
5. [Espace Entreprise](#5-espace-entreprise)
6. [Espace Étudiant](#6-espace-étudiant)
7. [Espace Responsable pédagogique](#7-espace-responsable-pédagogique)
8. [Espace Administrateur](#8-espace-administrateur)
9. [Système de matchmaking](#9-système-de-matchmaking)
10. [Cycle de vie : candidature et stage](#10-cycle-de-vie--candidature-et-stage)
11. [Notifications et messagerie](#11-notifications-et-messagerie)
12. [Modèle de données MySQL](#12-modèle-de-données-mysql)
13. [Sécurité technique](#13-sécurité-technique)
14. [Arborescence des pages et des fichiers](#14-arborescence-des-pages-et-des-fichiers)
15. [Plan de réalisation](#15-plan-de-réalisation)
16. [Points à valider](#16-points-à-valider)

---

## 1. Vision générale

### 1.1 Problème

Les stages sont une étape clé de la formation, mais leur gestion manuelle pose des problèmes :

- manque de visibilité des offres ;
- difficulté pour les étudiants à trouver et à postuler ;
- difficulté pour les entreprises à gérer les candidatures ;
- absence de suivi du stage par l'établissement.

### 1.2 Solution

Une plateforme web qui met en relation **entreprises**, **étudiants** et **établissement** (responsables pédagogiques), et qui couvre tout le cycle :

```
Inscription vérifiée → Publication d'offre → Recherche / Matching → Candidature
→ Sélection → Stage en cours → Suivi → Rapport → Validation finale
```

### 1.3 Points forts pour la soutenance

La professeure valorise les fonctionnalités, les filtres et la sécurité. La plateforme se distingue par :

| Atout | Description |
|---|---|
| Inscription différenciée | Un formulaire différent par profil |
| Vérification d'authenticité | Vrai étudiant, vraie entreprise, vrai professeur |
| Filtres avancés | Sur les offres, les candidatures et les étudiants |
| Matchmaking | Score de compatibilité étudiant ↔ offre |
| Suivi complet du stage | Activités, rapport, validation |
| Messagerie et notifications | Communication intégrée |
| Tableau de bord statistique | Graphiques par acteur |

---

## 2. Les 4 acteurs et leurs droits

| Acteur | Rôle | Création du compte |
|---|---|---|
| **Visiteur** | Non connecté : voit l'accueil, un aperçu public des offres, les formulaires | — |
| **Étudiant** | Cherche et postule à des stages | Auto-inscription + vérification |
| **Entreprise** | Publie des offres, gère les candidatures | Auto-inscription + validation admin |
| **Responsable pédagogique** | Suit et valide les stages des étudiants | Invitation ou validation admin |
| **Administrateur** | Supervise toute la plateforme | Créé directement en base (seed) |

### Matrice des permissions

| Action | Visiteur | Étudiant | Entreprise | Resp. péda | Admin |
|---|:-:|:-:|:-:|:-:|:-:|
| Voir l'aperçu public des offres | ✅ | ✅ | ✅ | ✅ | ✅ |
| Voir le détail complet d'une offre | ❌ | ✅ | ✅ (les siennes) | ✅ | ✅ |
| Publier / modifier / supprimer une offre | ❌ | ❌ | ✅ (les siennes) | ❌ | ✅ (modération) |
| Postuler | ❌ | ✅ | ❌ | ❌ | ❌ |
| Voir les candidatures reçues | ❌ | ❌ | ✅ (ses offres) | ✅ (ses étudiants) | ✅ |
| Voir le profil complet et le CV d'un candidat | ❌ | ❌ | ✅ (candidats de ses offres uniquement) | ✅ | ✅ |
| Valider une entreprise | ❌ | ❌ | ❌ | ❌ | ✅ |
| Valider un stage | ❌ | ❌ | ❌ | ✅ | ✅ |
| Gérer les utilisateurs et catégories | ❌ | ❌ | ❌ | ❌ | ✅ |
| Voir les statistiques globales | ❌ | ❌ | partielles | partielles | ✅ |

> **Règle de confidentialité :** une entreprise ne voit le profil complet d'un étudiant que si celui-ci a **postulé à l'une de ses offres**. Elle ne peut pas parcourir librement la base d'étudiants.

---

## 3. Parcours d'entrée : accueil, inscription, connexion

### 3.1 Page d'accueil (`index.php`)

Première page vue par un visiteur :

- Bannière : nom de la plateforme et slogan.
- **Deux grands boutons : « Se connecter » et « S'inscrire ».**
- Section « Qui êtes-vous ? » avec **3 cartes de choix de profil** :
  - 🎓 **Je suis étudiant**
  - 🏢 **Je suis une entreprise**
  - 👨‍🏫 **Je suis responsable pédagogique**
- Aperçu de quelques offres récentes (titre, ville, durée, sans les coordonnées de l'entreprise).
- Compteurs : nombre d'offres, d'entreprises et d'étudiants.
- Pied de page : contact, mentions légales.

### 3.2 Parcours d'inscription

```
Accueil → choix du profil → formulaire d'inscription dédié
→ vérification de l'email (lien ou code OTP)
→ [étudiant] compte actif, éventuellement après validation des justificatifs
→ [entreprise / resp. péda] compte « en attente de validation admin »
→ email de confirmation d'activation
```

Il y a **trois formulaires distincts**, car les informations demandées diffèrent.

#### A. Formulaire d'inscription — Étudiant (`inscription_etudiant.php`)

| Champ | Type | Obligatoire | Contrôle |
|---|---|:-:|---|
| Nom | texte | ✅ | lettres uniquement |
| Prénom | texte | ✅ | lettres uniquement |
| Email universitaire | email | ✅ | **domaine dans la liste blanche** (voir §4) |
| Mot de passe | password | ✅ | min. 8 caractères, majuscule, chiffre, symbole |
| Confirmation du mot de passe | password | ✅ | identique |
| Téléphone | tel | ✅ | format marocain |
| Numéro d'étudiant ou CNE (Massar) | texte | ✅ | unique en base |
| Établissement | liste | ✅ | ex. ENSA El Jadida |
| Filière | liste | ✅ | ex. CCN, GINF, GSTR… |
| Niveau d'études | liste | ✅ | 1ère, 2ème, 3ème année… |
| Date de naissance | date | ❌ | |
| Certificat de scolarité (PDF) | fichier | ✅ | PDF, max 2 Mo |
| Case « J'accepte les conditions d'utilisation » | checkbox | ✅ | |

#### B. Formulaire d'inscription — Entreprise (`inscription_entreprise.php`)

| Champ | Type | Obligatoire | Contrôle |
|---|---|:-:|---|
| Nom de l'entreprise | texte | ✅ | |
| Secteur d'activité | liste | ✅ | informatique, finance, industrie… |
| Forme juridique | liste | ✅ | SARL, SA, SAS, auto-entrepreneur… |
| **ICE** (Identifiant Commun de l'Entreprise) | texte | ✅ | 15 chiffres, unique |
| **RC** (Registre de Commerce) | texte | ✅ | unique |
| Adresse complète et ville | texte | ✅ | |
| Site web | url | ✅ | doit être valide |
| **Email professionnel** | email | ✅ | **adresse gratuite refusée** (gmail, yahoo, hotmail…) ; le domaine doit correspondre au site web |
| Téléphone | tel | ✅ | |
| Nom et prénom du représentant | texte | ✅ | |
| Fonction du représentant | texte | ✅ | RH, directeur, responsable… |
| Email du représentant | email | ✅ | |
| Logo | image | ❌ | JPG/PNG, max 1 Mo |
| Description de l'entreprise | textarea | ✅ | |
| Justificatif d'existence (extrait RC ou attestation ICE, PDF) | fichier | ✅ | PDF, max 3 Mo |
| Mot de passe et confirmation | password | ✅ | |

#### C. Formulaire d'inscription — Responsable pédagogique (`inscription_pedago.php`)

| Champ | Type | Obligatoire | Contrôle |
|---|---|:-:|---|
| Nom et prénom | texte | ✅ | |
| Email institutionnel | email | ✅ | **domaine institutionnel autorisé** |
| Téléphone | tel | ✅ | |
| Établissement | liste | ✅ | |
| Département / filière encadrée | liste | ✅ | |
| Grade / fonction | liste | ✅ | professeur, enseignant, chef de filière… |
| **Code d'invitation** fourni par l'administration | texte | ✅ | usage unique, avec date d'expiration |
| Pièce justificative (attestation de fonction, carte professionnelle) | fichier | ❌ | PDF ou image |
| Mot de passe et confirmation | password | ✅ | |

### 3.3 Connexion avec domaines distincts (`login.php`)

Chaque profil a **son propre espace de connexion**, avec **son domaine d'email**. Il y a deux manières de l'implémenter ; la première est recommandée.

**Option 1 (recommandée) : une page de connexion avec un onglet par profil.**

```
[ 🎓 Étudiant ] [ 🏢 Entreprise ] [ 👨‍🏫 Resp. pédagogique ]
        Email : ____________   Mot de passe : ____________
                    [ Se connecter ]   Mot de passe oublié ?
```

**Option 2 : trois URL séparées.** `/etudiant/login.php`, `/entreprise/login.php`, `/pedago/login.php`. L'administrateur a une page cachée, `/admin/login.php`.

**Règles de connexion :**

1. Le domaine de l'email est vérifié **avant** d'interroger la base :
   - Étudiant → domaine universitaire autorisé uniquement (ex. `@etu.ucd.ac.ma`) ;
   - Responsable pédagogique → domaine institutionnel (ex. `@ucd.ac.ma`) ;
   - Entreprise → email professionnel, domaine non gratuit.
2. Le rôle du compte doit correspondre à l'onglet utilisé. Un étudiant ne peut pas se connecter via l'onglet « Entreprise ».
3. Le compte doit être **actif** : email vérifié et validation faite si nécessaire. Sinon, un message explicite s'affiche (« compte en attente de validation »).
4. Après 5 échecs, le compte est bloqué 15 minutes.
5. Après connexion, redirection vers le tableau de bord du rôle.
6. Les erreurs sont génériques (« Identifiants incorrects »), sans révéler si l'email existe.

### 3.4 Mot de passe oublié

Email contenant un lien avec un token aléatoire (valable 30 min, usage unique), puis un formulaire de nouveau mot de passe.

---

## 4. Vérification d'authenticité des comptes

L'objectif est que **seul un vrai étudiant, une vraie entreprise ou un vrai professeur** puisse s'inscrire. On combine plusieurs couches.

### 4.1 Étudiant

| Couche | Mécanisme |
|---|---|
| 1. Domaine email | Liste blanche en base (`domaines_autorises`) : seuls les emails universitaires sont acceptés, ex. `@etu.ucd.ac.ma`. Les adresses gmail, yahoo… sont **refusées**. |
| 2. Vérification de l'email | Envoi d'un lien de confirmation avec token ou d'un code OTP à 6 chiffres. Le compte reste inactif tant que non confirmé. |
| 3. Identifiant unique | Le CNE ou numéro d'étudiant est unique (empêche les doublons). |
| 4. Justificatif | Certificat de scolarité en PDF, consultable par l'admin. |
| 5. Validation | **Mode strict** : l'admin ou le resp. péda valide chaque compte. **Mode souple** : activation automatique après vérification de l'email, avec contrôle a posteriori. |
| 6. Liste officielle (bonus) | L'admin importe un fichier CSV des étudiants inscrits. L'inscription est refusée si l'email ou le CNE n'y figure pas. |

### 4.2 Entreprise

| Couche | Mécanisme |
|---|---|
| 1. Email professionnel | Domaines gratuits refusés (liste noire : gmail, yahoo, hotmail, outlook, live…). |
| 2. Cohérence domaine / site web | Le domaine de l'email doit correspondre au domaine du site web saisi (`contact@societe.ma` et `www.societe.ma`). |
| 3. Vérification de l'email | Lien de confirmation. |
| 4. Identifiants légaux | ICE (15 chiffres) et RC, uniques en base. |
| 5. Justificatif | Extrait de RC ou attestation ICE en PDF. |
| 6. **Validation manuelle par l'admin** | Statut `en_attente` → l'admin consulte les infos et les documents, puis **valide** ou **rejette** avec un motif. **Aucune offre ne peut être publiée tant que l'entreprise n'est pas validée.** |
| 7. Signalement | Étudiants et resp. péda peuvent signaler une entreprise suspecte. L'admin peut la suspendre. |

### 4.3 Responsable pédagogique

| Couche | Mécanisme |
|---|---|
| 1. Email institutionnel | Domaine de l'établissement uniquement. |
| 2. Code d'invitation | Généré par l'admin, à usage unique, avec expiration. Sans code valide, pas d'inscription. |
| 3. Vérification de l'email | Lien de confirmation. |
| 4. Validation admin | Obligatoire. |

> **Alternative plus simple :** les comptes des responsables pédagogiques sont **créés par l'admin**, qui leur envoie un mot de passe temporaire à changer à la première connexion.

### 4.4 Administrateur

- Compte inséré directement en base (script d'installation), jamais via un formulaire public.
- Page de connexion non référencée.
- Option : double authentification par OTP email.

### 4.5 Gestion de l'état d'un compte

```
non_verifie → (email confirmé) → en_attente_validation → actif
                                        ↓                    ↓
                                     rejete             suspendu / banni
```

---

## 5. Espace Entreprise

### 5.1 Tableau de bord (`entreprise/dashboard.php`)

- Cartes : offres actives, candidatures reçues, candidatures non traitées, stagiaires en cours.
- Graphique : candidatures par offre.
- Dernières candidatures reçues.
- Notifications récentes.
- Bouton rapide **« + Publier une offre »**.

### 5.2 Publier une offre (`entreprise/offre_nouvelle.php`)

Un bouton « Publier une offre » ouvre une **page dédiée**, avec un formulaire structuré en sections.

**Section 1 : informations générales**

| Champ | Type |
|---|---|
| Titre du stage | texte |
| Domaine / catégorie | liste (informatique, réseaux, data, cybersécurité, finance, marketing, industrie…) |
| Type de stage | liste : **observation / technique / fin d'études (PFE)** |
| Description des missions | textarea |
| Objectifs pédagogiques | textarea |

**Section 2 : durée et dates**

| Champ | Type |
|---|---|
| Durée | liste ou nombre (en semaines ou mois) |
| Date de début | date |
| Date de fin | date (calculée ou saisie) |
| Date limite de candidature | date |
| Nombre de postes | nombre |

**Section 3 : conditions**

| Champ | Type |
|---|---|
| Mode | **Présentiel / À distance / Hybride** |
| Localisation (ville) | liste de villes + adresse |
| Rémunération | **Rémunéré / Non rémunéré** |
| Montant mensuel | nombre (si rémunéré, optionnel) |
| Avantages | cases : transport, repas, hébergement, possibilité d'embauche |

**Section 4 : profil recherché**

| Champ | Type |
|---|---|
| Filière(s) visée(s) | multi-sélection |
| Niveau d'études requis | multi-sélection |
| Compétences techniques requises | tags (PHP, Python, Réseaux, Cisco, SQL…), avec **niveau** : obligatoire ou souhaitée |
| Compétences transversales | tags |
| Langues exigées | liste avec niveau |
| Documents demandés | cases : CV, lettre de motivation, relevé de notes, portfolio |

**Section 5 : actions**

- 💾 Enregistrer en brouillon.
- 👁️ Aperçu.
- 📤 Publier (statut `publiée`).

### 5.3 Gérer ses offres (`entreprise/mes_offres.php`)

Tableau des offres avec **filtres** :

- statut : brouillon / publiée / clôturée / expirée ;
- type de stage, mode, période ;
- tri par date, par nombre de candidatures.

Actions par offre : **modifier**, **dupliquer**, **clôturer**, **supprimer**, **voir les candidatures**.

### 5.4 Consulter les candidatures (`entreprise/candidatures.php`)

Liste des candidatures reçues, avec **filtres** :

- par offre ;
- par statut ;
- par niveau d'études ou filière ;
- par compétences ;
- par score de matching (voir §9) ;
- par date de candidature.

Pour chaque candidature : nom, filière, niveau, score de compatibilité, date, statut, actions rapides.

### 5.5 Consulter le profil complet d'un candidat (`entreprise/candidat_profil.php`)

L'entreprise voit **le maximum d'informations**, uniquement pour les candidats de ses offres :

- identité : photo, nom, prénom, filière, niveau, établissement ;
- coordonnées : email, téléphone, ville ;
- **CV** : téléchargement et lecteur PDF intégré ;
- lettre de motivation de cette candidature ;
- compétences avec niveaux ;
- formations, expériences, projets, certifications ;
- langues ;
- liens : GitHub, LinkedIn, portfolio ;
- relevé de notes, s'il est partagé ;
- historique des candidatures à ses offres.

### 5.6 Traiter une candidature

Boutons d'action et transitions de statut :

```
Envoyée → En cours d'examen → Présélectionnée → Entretien planifié → Acceptée / Refusée
```

- **Refuser** : motif obligatoire ou choix parmi des motifs prédéfinis.
- **Planifier un entretien** : date, heure, lieu ou lien visio, message → l'étudiant est notifié.
- **Accepter** : crée automatiquement le **stage** (voir §10).
- Notes privées de l'entreprise sur chaque candidat.
- **Contacter le candidat** : messagerie intégrée et envoi d'email.
- Exporter la liste des candidats en CSV ou PDF.

### 5.7 Profil entreprise

Modifier le logo, la description, le secteur, le site web, l'adresse, les contacts. **Un changement d'ICE ou de RC redéclenche la validation admin.**

### 5.8 Gestion des stagiaires

Liste des stages acceptés : dates, encadrant entreprise, évaluation du stagiaire à la fin, attestation de stage.

---

## 6. Espace Étudiant

### 6.1 Tableau de bord (`etudiant/dashboard.php`)

- **Complétude du profil** (barre de progression en %).
- Offres recommandées (matching).
- Statut de mes candidatures.
- Entretiens à venir.
- Notifications.
- Stage en cours (si accepté).

### 6.2 Mon profil (`etudiant/profil.php`)

Plus le profil est complet, meilleur est le matching. Sections :

| Section | Contenu |
|---|---|
| Identité | photo, nom, prénom, date de naissance, ville |
| Contact | email, téléphone |
| Formation | établissement, filière, niveau, année universitaire, diplômes |
| **CV** | upload PDF (max 2 Mo), possibilité de versions multiples, CV principal |
| Compétences | tags avec niveau (débutant, intermédiaire, avancé) |
| Expériences | stages, jobs : poste, entreprise, période, description |
| Projets | titre, description, technologies, lien |
| Certifications | intitulé, organisme, date |
| Langues | langue et niveau |
| Liens | GitHub, LinkedIn, portfolio |
| **Préférences de stage** | domaine souhaité, type de stage, mode, villes acceptées, durée, rémunération minimale, disponibilité (date de début possible) |
| Visibilité | choisir quelles informations sont visibles par les entreprises |

### 6.3 Rechercher des stages (`etudiant/offres.php`)

Barre de recherche + **panneau de filtres** :

| Filtre | Type |
|---|---|
| Mot-clé | texte (titre, description, compétences) |
| Domaine / catégorie | liste |
| Localisation / ville | liste |
| Durée | curseur ou intervalle |
| Entreprise | liste / autocomplétion |
| Type de stage | cases (observation, technique, PFE) |
| Mode | présentiel, à distance, hybride |
| Rémunération | rémunéré / non rémunéré |
| Date de début | intervalle |
| Compétences | tags multiples |

Fonctions complémentaires :

- tri par date, pertinence, score de matching ;
- pagination ;
- ❤️ **favoris** (offres sauvegardées) ;
- alertes : « me prévenir des nouvelles offres correspondant à ces filtres » ;
- badge « Déjà postulé ».

### 6.4 Page « Matchmaking » (`etudiant/matching.php`)

Une page dédiée où l'étudiant ajuste ses critères (domaine, ville, durée, mode, compétences, rémunération) et **les offres sont classées par pourcentage de compatibilité** (voir §9). Pour chaque offre : score, compétences en commun, compétences manquantes.

### 6.5 Détail d'une offre et candidature

Page détail : tout le contenu de l'offre, profil de l'entreprise, offres similaires, bouton **« Postuler »**.

Formulaire de candidature :

- CV (sélection parmi les CV du profil ou upload) ;
- **lettre de motivation** (texte ou PDF) ;
- documents complémentaires demandés par l'offre ;
- message optionnel à l'entreprise.

Contrôles : une seule candidature par offre, date limite non dépassée, profil minimum complété, offre encore ouverte.

### 6.6 Suivi de mes candidatures (`etudiant/mes_candidatures.php`)

Tableau avec statut, date, offre, entreprise. Filtres par statut. Actions : **retirer sa candidature**, voir les messages, voir la date d'entretien, **confirmer ou décliner** une acceptation.

### 6.7 Mon stage (`etudiant/mon_stage.php`)

Disponible après acceptation (voir §10) :

- informations du stage, encadrant entreprise, encadrant pédagogique ;
- **journal d'activités** : ajout hebdomadaire de tâches réalisées ;
- **dépôt du rapport de stage** (PDF, versions successives) ;
- commentaires de l'encadrant ;
- statut de validation.

---

## 7. Espace Responsable pédagogique

### 7.1 Tableau de bord (`pedago/dashboard.php`)

- Nombre d'étudiants encadrés par statut : sans stage / en recherche / stage accepté / en cours / terminé / validé.
- Stages en retard (rapport non déposé).
- Alertes : étudiants sans candidature depuis X jours, stages à valider.
- Graphique : taux d'étudiants placés par filière.

### 7.2 Liste des étudiants encadrés (`pedago/etudiants.php`)

**Filtres :** filière, niveau, statut du stage, entreprise, période, nom. Pour chaque étudiant : statut de recherche, nombre de candidatures, stage en cours.

### 7.3 Fiche de suivi d'un étudiant (`pedago/etudiant_suivi.php`)

- profil et CV ;
- **historique des candidatures** et leurs statuts ;
- **stage** : entreprise, dates, encadrants, sujet ;
- **journal d'activités** de l'étudiant ;
- **rapport de stage** : téléchargement, versions ;
- barre d'avancement (% du temps écoulé) ;
- messages et commentaires.

### 7.4 Actions

| Action | Détail |
|---|---|
| Valider ou refuser un stage | Après acceptation par l'entreprise, vérifie la conformité (durée, entreprise, sujet). Motif si refus. |
| Désigner l'encadrant pédagogique | Lui-même ou un autre enseignant |
| Commenter le journal et le rapport | Retours visibles par l'étudiant |
| Valider le rapport | Statut : déposé → en révision → à corriger → validé |
| Évaluer le stage | Note ou appréciation finale |
| Contacter étudiant ou entreprise | Messagerie |
| Signaler une entreprise | Remonte à l'admin |
| Exporter | Liste de suivi en CSV ou PDF |

---

## 8. Espace Administrateur

### 8.1 Tableau de bord

- Totaux : utilisateurs par rôle, offres, candidatures, stages.
- **Comptes en attente de validation** (entreprises, resp. péda, étudiants), avec accès rapide.
- Signalements en cours.
- Graphiques : offres par catégorie, candidatures par mois, taux de placement, top entreprises.

### 8.2 Gestion

| Module | Fonctions |
|---|---|
| Utilisateurs | liste filtrable, voir, activer, suspendre, bannir, supprimer, réinitialiser le mot de passe |
| **Validation des entreprises** | voir ICE, RC et justificatif, valider ou rejeter avec motif |
| Validation des étudiants | consulter le certificat de scolarité, valider ou rejeter |
| Responsables pédagogiques | créer des comptes, générer des codes d'invitation |
| Offres | modérer, masquer, supprimer une offre non conforme |
| Catégories de stages | CRUD |
| Compétences | CRUD du référentiel de compétences |
| Domaines email autorisés | gérer la liste blanche (étudiants, resp. péda) et la liste noire (emails gratuits) |
| Import de la liste officielle | CSV des étudiants |
| Signalements | traiter et sanctionner |
| Journal d'audit | qui a fait quoi, quand |
| Paramètres | durée maximale de stage, taille maximale des fichiers, mode de validation des étudiants (strict ou souple) |

---

## 9. Système de matchmaking

### 9.1 Principe

Pour chaque couple **(étudiant, offre)**, on calcule un **score de compatibilité de 0 à 100 %**. Il sert à :

- recommander des offres aux étudiants ;
- classer les candidats pour les entreprises.

### 9.2 Formule proposée

```
Score = 40 % × compétences
      + 15 % × domaine / filière
      + 10 % × niveau d'études
      + 10 % × localisation
      + 10 % × mode (présentiel / distance)
      +  5 % × durée et disponibilité
      +  5 % × type de stage
      +  5 % × rémunération
```

| Critère | Calcul |
|---|---|
| Compétences | `(compétences requises possédées / total requises)`. Une compétence obligatoire pèse plus qu'une « souhaitée ». |
| Domaine | 100 % si le domaine de l'offre = domaine préféré ou filière ; sinon 0 |
| Niveau | 100 % si le niveau de l'étudiant est dans la liste visée |
| Localisation | 100 % même ville, 50 % ville acceptée, 0 sinon (100 % si l'offre est à distance) |
| Mode | 100 % si le mode correspond à la préférence |
| Durée / dispo | 100 % si la durée est compatible et la date de début ≥ disponibilité |
| Type | 100 % si le type correspond à la préférence |
| Rémunération | 100 % si les préférences sont respectées |

### 9.3 Implémentation PHP

```php
function calculerScore(array $etudiant, array $offre): int {
    $s = 0;
    $s += 40 * tauxCompetences($etudiant['competences'], $offre['competences']);
    $s += 15 * ($etudiant['domaine_pref'] == $offre['domaine'] ? 1 : 0);
    // ... autres critères
    return (int) round($s);
}
```

On recalcule le score à l'affichage (requête SQL ou PHP). Option : stocker dans une table `matching_scores`, mise à jour à chaque modification du profil ou de l'offre.

### 9.4 Affichage

- Badge coloré : ≥ 75 % vert, 50-74 % orange, < 50 % rouge.
- Détail : « Compétences en commun : PHP, SQL — Manquantes : Docker ».
- Seules les offres au-dessus d'un seuil (ex. 40 %) sont recommandées.

### 9.5 Bonus

Analyse du CV : extraction des mots-clés du PDF et comparaison avec les compétences requises (`pdftotext` ou une bibliothèque PHP).

---

## 10. Cycle de vie : candidature et stage

### 10.1 Statuts d'une candidature

| Statut | Déclenché par | Notification |
|---|---|---|
| `envoyée` | Étudiant | Entreprise |
| `en_cours_examen` | Entreprise | Étudiant |
| `présélectionnée` | Entreprise | Étudiant |
| `entretien_planifié` | Entreprise | Étudiant |
| `acceptée` | Entreprise | Étudiant, resp. péda |
| `refusée` | Entreprise | Étudiant |
| `retirée` | Étudiant | Entreprise |
| `confirmée` | Étudiant (accepte le stage) | Entreprise, resp. péda |

À la confirmation de l'étudiant, ses autres candidatures en cours sont proposées au retrait.

### 10.2 Statuts d'une offre

`brouillon` → `publiée` → `clôturée` / `expirée` (automatique après la date limite) / `masquée` (modération).

### 10.3 Statuts d'un stage

```
À valider (par resp. péda) → Validé → En cours → Rapport déposé
→ Rapport en révision → Rapport validé → Terminé
        ↘ Refusé / Annulé
```

### 10.4 Suivi du stage

Une fois la candidature acceptée et confirmée, le système crée un enregistrement **Stage** :

| Information | Source |
|---|---|
| Étudiant, entreprise, offre | candidature |
| Date de début, date de fin | offre (modifiables) |
| Encadrant entreprise | saisi par l'entreprise |
| Encadrant pédagogique | désigné par le resp. péda |
| Sujet du stage | offre |
| Journal d'activités | étudiant |
| Rapport de stage | étudiant (PDF) |
| Évaluation entreprise | entreprise (fin de stage) |
| Validation finale | resp. péda |

---

## 11. Notifications et messagerie

### 11.1 Notifications internes

Icône cloche avec compteur de non lues, liste, marquage « lu ».

| Événement | Destinataire |
|---|---|
| Compte validé ou rejeté | Utilisateur concerné |
| Nouvelle entreprise à valider | Admin |
| Nouvelle offre correspondant aux alertes | Étudiants concernés |
| Nouvelle candidature | Entreprise |
| Changement de statut d'une candidature | Étudiant |
| Entretien planifié | Étudiant |
| Stage à valider | Resp. péda |
| Rapport déposé | Resp. péda, entreprise |
| Date limite d'offre proche | Entreprise |
| Nouveau message | Destinataire |

### 11.2 Emails (PHPMailer)

Vérification d'email, mot de passe oublié, confirmation d'activation, changement de statut important.

### 11.3 Messagerie

Fil de discussion lié à une candidature ou à un stage :

- étudiant ↔ entreprise ;
- étudiant ↔ resp. péda ;
- entreprise ↔ resp. péda.

Règle : **un étudiant ne peut être contacté par une entreprise que s'il a postulé** à l'une de ses offres. Cela évite le spam et protège la vie privée.

---

## 12. Modèle de données MySQL

### 12.1 Schéma relationnel (vue d'ensemble)

```
users 1─1 etudiants        users 1─1 entreprises        users 1─1 responsables_pedago
etudiants 1─N competences_etudiant N─1 competences
etudiants 1─N experiences / projets / certifications / langues / cv
entreprises 1─N offres N─1 categories
offres N─N competences (offre_competences)
etudiants N─N offres via candidatures
candidatures 1─0..1 stages
stages 1─N activites_stage, 1─N rapports_stage
users 1─N notifications ; users N─N messages
```

### 12.2 Tables

**`users`** (table commune d'authentification)

| Colonne | Type | Remarque |
|---|---|---|
| id | INT PK AI | |
| email | VARCHAR(150) UNIQUE | |
| password_hash | VARCHAR(255) | `password_hash()` |
| role | ENUM('etudiant','entreprise','pedago','admin') | |
| statut | ENUM('non_verifie','en_attente','actif','rejete','suspendu') | |
| email_verifie_at | DATETIME NULL | |
| token_verification | VARCHAR(64) NULL | |
| tentatives_echec | TINYINT DEFAULT 0 | |
| bloque_jusqua | DATETIME NULL | |
| derniere_connexion | DATETIME NULL | |
| created_at | DATETIME | |

**`etudiants`**

| Colonne | Type |
|---|---|
| id PK, user_id FK UNIQUE | |
| nom, prenom | VARCHAR |
| cne | VARCHAR UNIQUE |
| telephone, ville | VARCHAR |
| date_naissance | DATE |
| photo | VARCHAR |
| etablissement_id FK, filiere_id FK | INT |
| niveau | VARCHAR |
| annee_universitaire | VARCHAR |
| bio | TEXT |
| github, linkedin, portfolio | VARCHAR |
| certificat_scolarite | VARCHAR (chemin) |
| disponibilite | DATE |
| pref_domaine_id, pref_type, pref_mode, pref_duree_min, pref_duree_max, pref_remuneration | préférences de matching |
| visibilite | JSON |
| encadrant_pedago_id | FK NULL |

**`entreprises`**

| Colonne | Type |
|---|---|
| id PK, user_id FK UNIQUE | |
| raison_sociale | VARCHAR |
| secteur | VARCHAR |
| forme_juridique | VARCHAR |
| ice | CHAR(15) UNIQUE |
| rc | VARCHAR UNIQUE |
| adresse, ville, site_web, telephone | VARCHAR |
| logo, description | VARCHAR / TEXT |
| rep_nom, rep_prenom, rep_fonction, rep_email | VARCHAR |
| justificatif | VARCHAR (chemin) |
| statut_validation | ENUM('en_attente','validee','rejetee','suspendue') |
| motif_rejet | TEXT NULL |
| valide_par, valide_le | FK admin, DATETIME |

**`responsables_pedago`**

| Colonne | Type |
|---|---|
| id PK, user_id FK UNIQUE | |
| nom, prenom, telephone | VARCHAR |
| etablissement_id, departement | |
| grade | VARCHAR |
| justificatif | VARCHAR NULL |

**Tables de référence :** `etablissements`, `filieres`, `categories` (domaines de stage), `competences` (id, nom, catégorie), `domaines_autorises` (domaine, type ENUM('etudiant','pedago','blacklist_entreprise')), `invitations_pedago` (code, expire_le, utilise, cree_par).

**Tables du profil étudiant :**

| Table | Colonnes principales |
|---|---|
| `cv` | id, etudiant_id, fichier, nom, principal (bool), uploaded_at |
| `competences_etudiant` | etudiant_id, competence_id, niveau ENUM('debutant','intermediaire','avance') |
| `experiences` | id, etudiant_id, poste, entreprise, date_debut, date_fin, description |
| `projets` | id, etudiant_id, titre, description, technologies, lien |
| `certifications` | id, etudiant_id, intitule, organisme, date_obtention |
| `langues_etudiant` | etudiant_id, langue, niveau |

**`offres`**

| Colonne | Type |
|---|---|
| id PK, entreprise_id FK | |
| titre | VARCHAR |
| categorie_id | FK |
| type_stage | ENUM('observation','technique','fin_etudes') |
| description, objectifs | TEXT |
| duree_semaines | INT |
| date_debut, date_fin, date_limite | DATE |
| nb_postes | INT |
| mode | ENUM('presentiel','distance','hybride') |
| ville, adresse | VARCHAR |
| remunere | BOOL |
| montant | DECIMAL NULL |
| avantages | JSON |
| filieres_visees, niveaux_vises | JSON |
| langues_exigees | JSON |
| documents_demandes | JSON |
| statut | ENUM('brouillon','publiee','cloturee','expiree','masquee') |
| vues | INT DEFAULT 0 |
| created_at, updated_at | DATETIME |

**`offre_competences`** : offre_id, competence_id, obligatoire (BOOL).

**`candidatures`**

| Colonne | Type |
|---|---|
| id PK | |
| etudiant_id, offre_id | FK, **UNIQUE (etudiant_id, offre_id)** |
| cv_id | FK |
| lettre_motivation | TEXT / fichier |
| message | TEXT |
| documents_supp | JSON |
| statut | ENUM (voir §10.1) |
| motif_refus | TEXT NULL |
| note_entreprise | TEXT NULL (privée) |
| score_matching | TINYINT |
| date_entretien, lieu_entretien | DATETIME, VARCHAR NULL |
| created_at, updated_at | DATETIME |

**`historique_candidature`** : id, candidature_id, ancien_statut, nouveau_statut, par_user_id, date.

**`stages`**

| Colonne | Type |
|---|---|
| id PK | |
| candidature_id FK UNIQUE | |
| etudiant_id, entreprise_id, offre_id | FK |
| encadrant_entreprise_nom, encadrant_entreprise_email | VARCHAR |
| encadrant_pedago_id | FK |
| sujet | VARCHAR |
| date_debut, date_fin | DATE |
| statut | ENUM (voir §10.3) |
| evaluation_entreprise | TEXT / note |
| evaluation_pedago | TEXT / note |
| attestation | VARCHAR |

**`activites_stage`** : id, stage_id, semaine (date), description, commentaire_encadrant.
**`rapports_stage`** : id, stage_id, fichier, version, statut ENUM('depose','en_revision','a_corriger','valide'), commentaire, deposed_at.

**Tables transverses :**

| Table | Colonnes principales |
|---|---|
| `favoris` | etudiant_id, offre_id |
| `alertes_offres` | id, etudiant_id, criteres JSON |
| `notifications` | id, user_id, type, message, lien, lue (bool), created_at |
| `messages` | id, expediteur_id, destinataire_id, candidature_id NULL, stage_id NULL, contenu, lu, created_at |
| `signalements` | id, auteur_id, cible_type, cible_id, motif, statut, created_at |
| `journal_audit` | id, user_id, action, detail, ip, created_at |
| `password_resets` | user_id, token_hash, expire_le, utilise |

### 12.3 Index recommandés

`users(email)`, `offres(statut, categorie_id, ville)`, `candidatures(offre_id, statut)`, `candidatures(etudiant_id)`, `competences_etudiant(competence_id)`, `notifications(user_id, lue)`. Utiliser `FULLTEXT` sur `offres(titre, description)` pour la recherche par mot-clé.

---

## 13. Sécurité technique

### 13.1 Checklist

| Menace | Contre-mesure |
|---|---|
| Mots de passe en clair | `password_hash($p, PASSWORD_ARGON2ID)` / `password_verify()`, jamais de MD5 ou SHA1 seul |
| **Injection SQL** | **PDO avec requêtes préparées** partout, jamais de concaténation |
| **XSS** | `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')` sur toute sortie ; en-tête `Content-Security-Policy` |
| **CSRF** | Token aléatoire par session dans chaque formulaire POST, vérifié côté serveur |
| Vol / fixation de session | `session_regenerate_id(true)` à la connexion ; cookies `HttpOnly`, `Secure`, `SameSite=Strict` ; expiration après inactivité |
| Force brute | Blocage temporaire après 5 échecs ; limitation par IP ; CAPTCHA optionnel |
| **Accès non autorisé (IDOR)** | Vérifier **le rôle ET la propriété** de la ressource à chaque requête (ex. une entreprise ne lit que les candidatures de ses offres) |
| Contrôle d'accès | Fonction `requireRole('entreprise')` en tête de chaque page protégée |
| **Upload malveillant** | Vérifier extension et type MIME réel (`finfo`), taille max, renommer en nom aléatoire, stocker **hors du dossier public**, servir via un script PHP qui contrôle les droits ; interdire l'exécution PHP dans le dossier d'uploads |
| Énumération d'utilisateurs | Messages d'erreur génériques en connexion et mot de passe oublié |
| Token de réinitialisation | Aléatoire (`random_bytes`), stocké haché, expire en 30 min, usage unique |
| Inscriptions frauduleuses | Vérification email, liste blanche de domaines, validation admin (voir §4) |
| Spam de candidatures | Contrainte UNIQUE, limite journalière |
| Fuite d'informations | `display_errors=Off` en production, erreurs écrites dans un log |
| Transport | HTTPS obligatoire, redirection HTTP → HTTPS |
| Traçabilité | `journal_audit` pour les actions sensibles (validation, suppression, connexion admin) |
| Données personnelles | Collecte minimale, visibilité contrôlée par l'étudiant, suppression de compte possible |

### 13.2 Exemples de code

```php
// Connexion PDO sécurisée
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Contrôle d'accès par rôle
function requireRole(string ...$roles): void {
    if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('Accès refusé');
    }
}

// Contrôle de propriété (anti-IDOR)
$stmt = $pdo->prepare(
  'SELECT c.* FROM candidatures c
   JOIN offres o ON o.id = c.offre_id
   WHERE c.id = ? AND o.entreprise_id = ?');
$stmt->execute([$id, $_SESSION['entreprise_id']]);

// Vérification du domaine d'email
function domaineAutorise(string $email, string $type, PDO $pdo): bool {
    $domaine = strtolower(substr(strrchr($email, '@'), 1));
    $st = $pdo->prepare('SELECT 1 FROM domaines_autorises WHERE domaine = ? AND type = ?');
    $st->execute([$domaine, $type]);
    return (bool) $st->fetchColumn();
}
```

---

## 14. Arborescence des pages et des fichiers

### 14.1 Carte des pages

```
PUBLIC
├─ index.php                    Accueil + choix du profil
├─ login.php                    Connexion (3 onglets)
├─ inscription_etudiant.php
├─ inscription_entreprise.php
├─ inscription_pedago.php
├─ verifier_email.php
├─ mot_de_passe_oublie.php / reinitialiser.php
├─ offres_publiques.php         Aperçu limité

ÉTUDIANT                         ENTREPRISE
├─ dashboard.php                 ├─ dashboard.php
├─ profil.php (+ CV…)            ├─ profil.php
├─ offres.php (filtres)          ├─ offre_nouvelle.php
├─ offre_detail.php              ├─ offre_modifier.php
├─ matching.php                  ├─ mes_offres.php
├─ postuler.php                  ├─ candidatures.php
├─ mes_candidatures.php          ├─ candidat_profil.php
├─ favoris.php                   ├─ entretiens.php
├─ mon_stage.php                 ├─ stagiaires.php
├─ messages.php                  ├─ messages.php
└─ notifications.php             └─ notifications.php

RESPONSABLE PÉDAGOGIQUE          ADMIN
├─ dashboard.php                 ├─ dashboard.php
├─ etudiants.php                 ├─ utilisateurs.php
├─ etudiant_suivi.php            ├─ validation_entreprises.php
├─ stages.php                    ├─ validation_etudiants.php
├─ valider_stage.php             ├─ pedagos.php (+ invitations)
├─ rapports.php                  ├─ offres_moderation.php
├─ messages.php                  ├─ categories.php / competences.php
└─ notifications.php             ├─ domaines.php
                                 ├─ signalements.php
                                 ├─ statistiques.php
                                 ├─ journal_audit.php
                                 └─ parametres.php
```

### 14.2 Structure du projet

```
stages/
├─ config/
│   ├─ database.php          (connexion PDO)
│   └─ config.php            (constantes : chemins, mail, taille max…)
├─ includes/
│   ├─ auth.php              (session, requireRole, login, logout)
│   ├─ csrf.php
│   ├─ functions.php         (helpers : e(), redirect(), flash())
│   ├─ matching.php          (calcul des scores)
│   ├─ upload.php            (validation et stockage sécurisé)
│   ├─ mailer.php
│   ├─ header.php / footer.php
│   └─ sidebar_{role}.php
├─ public/                   (racine web)
│   ├─ index.php, login.php, inscription_*.php …
│   ├─ etudiant/ entreprise/ pedago/ admin/
│   ├─ assets/ (css, js, img)
│   └─ download.php          (téléchargement contrôlé)
├─ storage/                  (HORS racine web)
│   ├─ cv/ lettres/ justificatifs/ rapports/ logos/ photos/
├─ sql/
│   ├─ schema.sql
│   └─ seed.sql              (admin, catégories, compétences, domaines)
└─ README.md
```

---

## 15. Plan de réalisation

### 15.1 Phases

| Phase | Contenu | Priorité |
|---|---|:-:|
| 1. Fondations | Base de données, connexion PDO, structure, authentification, rôles, CSRF | 🔴 |
| 2. Inscription et vérification | 3 formulaires, domaines, vérification email, validation admin | 🔴 |
| 3. Espace Entreprise | Publication d'offre, gestion des offres, candidatures, profil candidat | 🔴 |
| 4. Espace Étudiant | Profil, CV, recherche avec filtres, candidature, suivi | 🔴 |
| 5. Responsable péda et suivi du stage | Fiche de suivi, validation, rapport, journal | 🟠 |
| 6. Admin | Validations, utilisateurs, catégories, statistiques | 🟠 |
| 7. Matchmaking | Score, recommandations, page dédiée | 🟠 |
| 8. Notifications et messagerie | Cloche, emails, fils de discussion | 🟡 |
| 9. Finitions | Design Bootstrap, responsive, tests, jeu de données de démo | 🟡 |
| 10. Bonus | Alertes, favoris, export PDF/CSV, 2FA admin, analyse de CV | ⚪ |

### 15.2 Répartition possible (groupe de 4)

| Membre | Responsabilités |
|---|---|
| 1 | Auth, sécurité, inscription, vérification, espace admin |
| 2 | Espace entreprise (offres, candidatures) |
| 3 | Espace étudiant (profil, recherche, matchmaking) |
| 4 | Responsable péda, suivi du stage, notifications et messagerie |

Tous partagent la base de données (schéma validé ensemble au départ) et le design commun.

### 15.3 Jeu de données de démonstration

1 admin, 3 entreprises validées et 1 en attente, 6 étudiants, 2 responsables péda, 12 offres variées, 15 candidatures à des statuts différents, 2 stages en cours (dont un avec rapport). Cela rend la démo fluide devant la professeure.

### 15.4 Scénario de démonstration

1. Un visiteur arrive, choisit « Entreprise », s'inscrit → compte en attente.
2. L'admin se connecte, voit l'entreprise, consulte l'ICE et le justificatif, la **valide**.
3. L'entreprise se connecte et **publie une offre** (avec filtres et profil recherché).
4. Un étudiant s'inscrit avec son email universitaire, vérifie son email, complète son profil et son CV.
5. Il utilise la page **matchmaking** : l'offre apparaît avec un score de 82 %.
6. Il **postule** avec lettre de motivation.
7. L'entreprise voit la candidature, consulte son **profil complet et son CV**, planifie un entretien puis **accepte**.
8. L'étudiant confirme ; le **stage** est créé.
9. Le responsable pédagogique **valide le stage**, suit le journal et le rapport, puis valide le rapport.
10. L'admin consulte les **statistiques**.

---

## 16. Points à valider

Quelques décisions à prendre en groupe, voire avec la professeure :

1. **Domaines d'email réels** à autoriser (par ex. celui de l'ENSA El Jadida / UCD) : à confirmer, car ils servent à la liste blanche.
2. **Mode de validation des étudiants** : strict (admin valide chacun) ou souple (activation après vérification de l'email).
3. **Envoi réel d'emails** : PHPMailer avec un compte SMTP, ou simulation en local (affichage du lien de vérification à l'écran pour la démo) ?
4. **Responsables pédagogiques** : auto-inscription avec code d'invitation, ou comptes créés par l'admin ?
5. **Périmètre** : quelles fonctions bonus garde-t-on (alertes, 2FA, analyse de CV) selon le temps disponible ?
6. **Un seul établissement** (ENSA El Jadida) ou multi-établissements dès le départ ?
7. **Messagerie** : complète (fils de discussion) ou simplifiée (formulaire de contact + email) ?
8. **Date de rendu et de soutenance** : à noter pour calibrer le plan.
