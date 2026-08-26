# User Stories — Module « Ressources »

> Base : maquettes Figma « Module "Ressources" — version Paris Cité ».
> Statut : **brouillon à compléter**. Les champs entre `[…]` et les sections _À compléter_ sont à préciser (règles métier, droits, validations, API, edge cases).

## Sources Figma

| Écran | node-id | Lien |
|-------|---------|------|
| Liste des ressources (vue tableau) | `2259-23081` | https://www.figma.com/design/YfGgyedaHf8ojlXcEW77jD/Module-%E2%80%9CRessources%E2%80%9D-version-Paris-Cit%C3%A9?node-id=2259-23081 |
| Modale « Importer un document » | `2259-23623` | https://www.figma.com/design/YfGgyedaHf8ojlXcEW77jD/Module-%E2%80%9CRessources%E2%80%9D-version-Paris-Cit%C3%A9?node-id=2259-23623 |
| Modale « Accès et partage » | `2259-23352` | https://www.figma.com/design/YfGgyedaHf8ojlXcEW77jD/Module-%E2%80%9CRessources%E2%80%9D-version-Paris-Cit%C3%A9?node-id=2259-23352 |

## Glossaire / Entités

- **Ressource / Document** : fichier partagé aux utilisateurs (pdf, docx, xls…). Attributs vus en maquette : nom, format, date de création, taille, nombre de téléchargements.
- **Dossier** : conteneur de regroupement de documents (action « Ajouter un dossier »).
- **Accès** : combinaison de rôles, groupes et utilisateurs avec un niveau de permission (« Consulter », …).
- **Espace d'affichage** : emplacement où la ressource est exposée (Formulaires, Campagnes, Programmes, Pages publiques).
- **Lien de partage** : lien public optionnel (date d'expiration, mot de passe, code de lien).

> _À compléter : modèle de données back (table `jos_emundus_…`), relation dossier ↔ document, niveaux de permission exacts._

---

## Épopée 1 — Liste & navigation des ressources
_Écran `2259-23081`_

### US-1.1 — Consulter la liste des ressources
**En tant que** [rôle — coordinateur ? gestionnaire ?]
**je veux** voir la liste des documents partagés sous forme de tableau
**afin de** retrouver et gérer les ressources de la plateforme.

Colonnes visibles en maquette : **Nom du document**, **Format du document**, **Date de création**, **Taille**, **Nombre de téléchargements**. Icône de type de fichier par ligne (pdf, docx, xls…).

**Critères d'acceptation**
- [ ] La page affiche le titre « Ressources » et le sous-titre « Retrouvez ici tous les documents partagés aux utilisateurs de la plateforme. »
- [ ] Chaque ligne montre les 5 colonnes + l'icône de format.
- [ ] Un menu d'actions par ligne (icône `more_vert`) est disponible. _À compléter : actions du menu (renommer, supprimer, télécharger, accès/partage…)._
- [ ] Fil d'ariane : Accueil › Ressources.
- [ ] _À compléter : pagination / scroll infini, tri par colonne, état vide._

> _À compléter : qui voit quoi (filtrage par droits), tri par défaut, format des dates/tailles._

### US-1.2 — Basculer entre vue liste et vue grille
**En tant que** utilisateur
**je veux** basculer entre l'affichage en liste et en grille (boutons `grid_view` / `format_list_bulleted`)
**afin d'** adapter l'affichage à mon usage.

**Critères d'acceptation**
- [ ] Deux boutons d'activation (grille / liste) ; un seul actif à la fois.
- [ ] _À compléter : rendu de la vue grille (cartes), persistance du choix._

### US-1.3 — Rechercher une ressource
**En tant que** utilisateur
**je veux** rechercher via le champ « Rechercher »
**afin de** filtrer rapidement les documents.

**Critères d'acceptation**
- [ ] La recherche filtre la liste. _À compléter : champs cherchés (nom seul ?), debounce, sensibilité casse/accents._

### US-1.4 — Filtrer les ressources
**En tant que** utilisateur
**je veux** ajouter des filtres (ruban de filtres + bouton `+`)
**afin de** restreindre la liste selon des critères.

**Critères d'acceptation**
- [ ] Bouton « ajouter un filtre » ouvre le choix des filtres.
- [ ] _À compléter : liste des filtres disponibles (format, date, dossier…), combinaison ET/OU, suppression d'un filtre._

### US-1.5 — Sélection multiple et actions groupées
**En tant que** utilisateur
**je veux** cocher plusieurs lignes (cases à cocher + sélecteur global) et appliquer une action via le menu « Actions »
**afin de** traiter plusieurs documents d'un coup.

**Critères d'acceptation**
- [ ] Case « tout sélectionner » + cases par ligne.
- [ ] Le menu déroulant « Actions » s'applique à la sélection. _À compléter : liste des actions groupées (supprimer, télécharger, déplacer dans un dossier…), comportement si sélection vide._

---

## Épopée 2 — Création de dossier & import de document

### US-2.1 — Ajouter un dossier
_Écran `2259-23081` (bouton « Ajouter un dossier »)_
**En tant que** utilisateur
**je veux** créer un dossier
**afin d'** organiser les ressources.

**Critères d'acceptation**
- [ ] Le bouton « Ajouter un dossier » lance la création.
- [ ] _À compléter : modale de création (nom, parent ?), validations, dossiers imbriqués ?_

### US-2.2 — Importer un document (sélection / drag & drop)
_Écran `2259-23623` — état « Empty »_
**En tant que** utilisateur
**je veux** importer un fichier par glisser-déposer ou en cliquant sur la zone
**afin d'** ajouter une ressource.

**Critères d'acceptation**
- [ ] Modale « Importer un document » avec zone drag & drop « Glissez un fichier ici ou cliquez pour le sélectionner ».
- [ ] Bouton « Importer » désactivé (grisé) tant qu'aucun fichier n'est sélectionné.
- [ ] Bouton de fermeture (croix) ferme la modale sans enregistrer.
- [ ] _À compléter : formats acceptés, taille max, mono ou multi-fichiers._

### US-2.3 — Prévisualiser et confirmer l'import
_Écran `2259-23623` — état « Uploaded »_
**En tant que** utilisateur
**je veux** voir le fichier sélectionné (vignette + nom, ex. `Trombinoscope-enseignants.pdf`) avant de valider
**afin de** vérifier que c'est le bon document.

**Critères d'acceptation**
- [ ] Le fichier sélectionné apparaît en carte « document-preview » avec son nom.
- [ ] Un bouton supprimer (rouge, icône `delete`) retire le fichier sélectionné → retour à l'état vide.
- [ ] Le bouton « Importer » devient actif (vert) et lance l'upload.
- [ ] _À compléter : barre de progression, gestion d'erreur upload, doublon de nom, où est rangé le document (dossier courant ?)._

---

## Épopée 3 — Accès & partage d'une ressource
_Écran `2259-23352` — modale « Accès et partage » à 3 onglets : « Rôles et utilisateurs », « Espaces d'affichage », « Lien de partage »_

### US-3.1 — Gérer les rôles ayant accès
_Onglet « Rôles et utilisateurs », section **Rôles**_
**En tant que** utilisateur
**je veux** définir quels rôles (ex. Juré, Gestionnaire) ont accès à la ressource et avec quel niveau (ex. « Consulter »)
**afin de** contrôler la visibilité par rôle.

**Critères d'acceptation**
- [ ] Liste des rôles avec leur niveau de permission et un bouton supprimer par ligne.
- [ ] Bouton « Ajouter un rôle ».
- [ ] _À compléter : liste des niveaux de permission disponibles (Consulter / Éditer / … ?), liste des rôles sélectionnables._

### US-3.2 — Gérer les groupes ayant accès
_Onglet « Rôles et utilisateurs », section **Groupes**_
**En tant que** utilisateur
**je veux** donner accès à des groupes
**afin de** partager à un ensemble d'utilisateurs.

**Critères d'acceptation**
- [ ] Bouton « Ajouter un groupe ».
- [ ] _À compléter : affichage d'un groupe ajouté (niveau de permission, suppression), source des groupes._

### US-3.3 — Gérer les utilisateurs ayant accès
_Onglet « Rôles et utilisateurs », section **Utilisateurs**_
**En tant que** utilisateur
**je veux** ajouter des utilisateurs nominatifs (chip avec avatar + nom, ex. John Doe, Robert Brown) avec un niveau de permission
**afin de** partager individuellement.

**Critères d'acceptation**
- [ ] Liste des utilisateurs (chip avatar + nom) + niveau « Consulter » + bouton supprimer.
- [ ] Bouton « Ajouter un utilisateur » ouvre une recherche/sélection. _(sous-états maquette : `users-access-add-user`, `users-acces-user-added`)_
- [ ] Après ajout, l'utilisateur apparaît avec un niveau de permission modifiable.
- [ ] _À compléter : recherche utilisateur (par nom/email ?), choix du niveau au moment de l'ajout, sous-état `users-profiles-folder` (accès par profil sur un dossier ?)._

### US-3.4 — Enregistrer les accès
**En tant que** utilisateur
**je veux** enregistrer la configuration des rôles/groupes/utilisateurs
**afin d'** appliquer les droits.

**Critères d'acceptation**
- [ ] Bouton « Enregistrer » persiste les changements et confirme.
- [ ] _À compléter : feedback succès/erreur, fermeture auto de la modale ?_

### US-3.5 — Configurer les espaces d'affichage
_Onglet « Espaces d'affichage »_
**En tant que** utilisateur
**je veux** choisir où la ressource est affichée (Formulaires, Campagnes, Programmes, Pages publiques)
**afin de** contrôler les emplacements d'exposition.

**Critères d'acceptation**
- [ ] **Formulaires** : liste de formulaires (ex. « Master économie », « Master environnement et société ») avec bouton d'édition par ligne.
- [ ] **Campagnes** : valeur / sélection. _(maquette : « - »)_
- [ ] **Programmes** : valeur / sélection. _(maquette : « - »)_
- [ ] **Pages publiques** : ex. « Accueil » avec bouton d'édition.
- [ ] _À compléter : sémantique de l'icône « edit » par ligne (configurer la visibilité où exactement ?), valeurs possibles, état « - » = non configuré ?_

### US-3.6 — Générer un lien de partage
_Onglet « Lien de partage »_
**En tant que** utilisateur
**je veux** générer un lien de partage avec date d'expiration, mot de passe optionnel et code de lien copiable
**afin de** partager la ressource hors plateforme.

**Critères d'acceptation**
- [ ] Champ **Date d'expiration** avec sélecteur de date (« Sélectionner une date » + bouton calendrier).
- [ ] Champ **Mot de passe** (saisie de texte, optionnel ?).
- [ ] Champ **Code du lien** en lecture seule (ex. `HDAE8YA!kzIdzHHG61b1?O7u`) + bouton « Copier ».
- [ ] Bouton « Enregistrer ».
- [ ] _À compléter : génération/régénération du code, URL complète vs code seul, comportement à l'expiration, mot de passe obligatoire ou non, révocation du lien._

---

## Transverses / Non-fonctionnel _(à compléter)_

- **Droits d'accès** : quels rôles eMundus peuvent voir/importer/partager/supprimer une ressource ? (`AccessAttribute` côté contrôleur).
- **i18n** : toutes les chaînes via `Joomla.Text._('COM_EMUNDUS_…')`.
- **Validations** : formats/taille de fichier, unicité du nom, dates.
- **API** : endpoints back (lister, importer, créer dossier, gérer accès, générer lien). Format `EmundusResponse`.
- **Journalisation** : log des imports/suppressions/partages.
- **Accessibilité** : labels, focus, navigation clavier des modales.
- **États** : chargement, vide, erreur réseau pour chaque écran.
