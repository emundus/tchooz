# Summary

Module **Ressources** : bibliothèque de documents partagés aux utilisateurs de la plateforme, découplée des candidatures. Permet de lister les documents (vue tableau/grille), les organiser en dossiers, importer des fichiers (glisser-déposer), gérer les accès par rôle/groupe/utilisateur, configurer les espaces d'affichage (formulaires, campagnes, programmes, pages publiques) et générer des liens de partage protégés (date d'expiration + mot de passe).

## Context

eMundus gère aujourd'hui des fichiers liés aux candidatures (`jos_emundus_uploads`, attachés à un `fnum`) et des liens publics de candidature à jeton (`jos_emundus_file_access`). Il n'existe **aucune bibliothèque documentaire partagée** indépendante des candidatures, ni modèle d'accès fin (rôle/groupe/utilisateur) par document, ni notion de dossier, d'espace d'affichage ou de lien de partage avec mot de passe. Le module Ressources comble ce manque : un coordinateur doit pouvoir centraliser des documents (informations candidats, déroulés de formation, tarifs, questionnaires…) et en contrôler la diffusion. Basé sur les maquettes Figma « Module "Ressources" — version Paris Cité ».

## Proposal

Nouveau domaine backend `Tchooz\…\Resource` (architecture en couches) + page Vue 3 autonome.

**Backend (`components/com_emundus/classes/`)**
- **Enums** `Resource/` : `ResourcePermissionEnum` (view/edit/manage), `ResourceAccessTypeEnum` (role/group/user), `DisplaySpaceTypeEnum` (form/campaign/program/public_page).
- **Entities** `Resource/` : `ResourceEntity` (document), `ResourceFolderEntity` (dossier), `ResourceAccessEntity` (accès), `ResourceDisplaySpaceEntity` (espace d'affichage), `ResourceShareEntity` (lien de partage).
- **Repositories** `Resource/` : un par entité (`Resource`, `ResourceFolder`, `ResourceAccess`, `ResourceDisplaySpace`, `ResourceShare`), `extends EmundusRepository` avec `#[TableAttribute]`.
- **Factory** `Resource/ResourceFactory` : hydrate un `ResourceEntity` complet (relations + libellés des cibles).
- **Services** `Resource/` : `ResourceService` (import/validation via `UploadService` réutilisé, dossiers, accès, espaces, suppression, download + compteur) et `ResourceShareService` (génération code, hash mot de passe, validation expiration/mot de passe, révocation).
- **Controller** `controllers/resource.php` (`EmundusControllerResource`) : endpoints `getresources`, `getresource`, `getfolders`, `createfolder`, `import`, `rename`, `delete`, `getaccess`, `saveaccess`, `getdisplayspaces`, `savedisplayspaces`, `getsharelink`, `savesharelink`, `revokesharelink`, `download`, `viewshared`. Réponses `EmundusResponse`.

**Frontend (`components/com_emundus/src/`)**
- **Service** `services/resource.js` (via `FetchClient('resource')`).
- **Store** Pinia `stores/resource.js` (id `'resource'`).
- **View** `views/Resource/RessourcesView.vue` montée par `main.js` sur `#em-resources-vue` + entrée navbar « Ressources ».
- **Composants** `components/Resource/` : `ResourceList.vue`, `AddFolderModal.vue`, `ImportDocumentModal.vue`, `AccessShareModal.vue` + onglets `tabs/AccessRolesUsersTab.vue`, `tabs/DisplaySpacesTab.vue`, `tabs/ShareLinkTab.vue`. Réutilisation de `Modal.vue`, du dropzone de `addDocumentsDropfiles.vue`, de l'atom `Button.vue`.

**Tables DB** (préfixe littéral `jos_emundus_`) : `jos_emundus_resource_folders`, `jos_emundus_resources`, `jos_emundus_resource_access`, `jos_emundus_resource_display_spaces`, `jos_emundus_resource_shares`. Migration intégrée à `tchooz:update`.

**Ordre de création** : Enums → Tables → Entities → Repositories → Factory → Services → Controller → service Vue → store → composants → View → montage navbar.

## Acceptance Criteria checklist

- [ ] La page « Ressources » liste les documents en tableau (Nom, Format, Date de création, Taille, Nombre de téléchargements) avec icône de type de fichier.
- [ ] Bascule entre vue liste et vue grille ; choix persistant.
- [ ] Recherche par nom de document filtrant la liste.
- [ ] Ruban de filtres avec ajout/suppression de filtres.
- [ ] Sélection multiple (case globale + par ligne) et menu « Actions » groupées (au minimum supprimer/télécharger).
- [ ] Menu `more_vert` par ligne (renommer, supprimer, télécharger, gérer accès & partage).
- [ ] Création d'un dossier ; les documents peuvent être rangés dans un dossier.
- [ ] Import d'un document par glisser-déposer ou sélection ; bouton « Importer » désactivé tant qu'aucun fichier n'est choisi.
- [ ] Prévisualisation du fichier sélectionné (nom + vignette) avec possibilité de le retirer avant validation.
- [ ] L'import valide format et taille (réutilisation `UploadService`) et stocke le fichier hors arborescence candidature.
- [ ] Modale « Accès et partage » à 3 onglets fonctionnels.
- [ ] Onglet « Rôles et utilisateurs » : ajout/suppression de rôles, groupes et utilisateurs avec un niveau de permission (Consulter par défaut).
- [ ] Onglet « Espaces d'affichage » : configuration Formulaires / Campagnes / Programmes / Pages publiques.
- [ ] Onglet « Lien de partage » : date d'expiration, mot de passe optionnel, code de lien en lecture seule + bouton « Copier » ; génération d'un code unique.
- [ ] Bouton « Enregistrer » persiste accès, espaces et lien.
- [ ] Accès public à une ressource via le code de lien, validé par mot de passe et date d'expiration.
- [ ] Le compteur de téléchargements s'incrémente à chaque téléchargement.
- [ ] La liste affichée respecte les droits de l'utilisateur connecté.
- [ ] Toutes les chaînes UI sont traduites via `Joomla.Text._('COM_EMUNDUS_…')`.

## Dependencies

**Prérequis / réutilisation**
- `UploadService` (validation mime/taille, fichier temporaire) — réutilisé par `ResourceService::importDocument`.
- `AccessLevelEnum` + `#[AccessAttribute]` — contrôle d'accès du controller.
- `GroupEntity` / `GroupRepository` — cibles d'accès de type `group`.
- `Modal.vue`, dropzone de `addDocumentsDropfiles.vue`, infra `List`/`Navigation`, atom `Button.vue` — côté Vue.
- `FetchClient` — communication front/back.
- Mécanisme de migration `php cli/joomla.php tchooz:update` — création des tables.

**Points d'intégration**
- Entrée navbar « Ressources » à ajouter (aucun stub existant).
- Espaces d'affichage : référence aux entités Formulaires / Campagnes / Programmes / Pages publiques existantes (résolution des `target_id` → libellés).

**Domaines proches (ne pas confondre)**
- `Upload`/`ApplicationFile` (fichiers liés à `fnum`) — domaine distinct, non étendu.
- `ApplicationFileAccess` (jeton + expiration) — concept proche ; s'en inspirer sans le réutiliser.

## Cybersecurity Analysis

### Access
- [ ] CRUD réservé via `#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]` ; lecture éventuellement ouverte au Manager selon droits.
- [ ] Endpoint `viewshared` (consultation par lien) en `AccessLevelEnum::PUBLIC`, protégé par `ResourceShareService::validate` (code + mot de passe + expiration).
- [ ] La liste renvoyée est filtrée selon les droits effectifs de l'utilisateur (rôle/groupe/utilisateur) — pas d'exposition de documents non autorisés.
- [ ] Le téléchargement vérifie le droit d'accès avant de servir le fichier (pas d'accès direct par chemin/id devinable).

### Data Privacy
- [ ] Données personnelles stockées : `created_by` (id utilisateur), cibles d'accès utilisateur (`target_id`). Pas de stockage de contenu personnel hors fichiers importés.
- [ ] Le mot de passe du lien est stocké **hashé** (`password_hash`), jamais en clair ; jamais renvoyé par l'API.
- [ ] Les fichiers sont stockés hors webroot direct / non listables ; accès uniquement via endpoint contrôlé.
- [ ] Le code de lien est généré avec une source aléatoire sûre (entropie suffisante, non séquentiel).

### Guaranteed data integrity and consistency
- [ ] FK `ON DELETE CASCADE` sur `resource_access`, `resource_display_spaces`, `resource_shares` ; `folder_id` `ON DELETE SET NULL`.
- [ ] `saveAccess` / `saveDisplaySpaces` en transaction (delete + insert atomiques).
- [ ] Contrainte unique `(resource_id, type, target_id)` sur les accès ; `code` unique sur les liens.
- [ ] La suppression d'un document purge aussi le fichier physique et ses relations.

### Data update traceability
- [ ] `created_by` / `created_at` sur documents, dossiers et liens.
- [ ] Journalisation via `Log` (`com_emundus.resource`) des imports, suppressions, changements d'accès et générations/révocations de lien.
- [ ] (Optionnel post-MVP) Subscriber pour tracer les événements via le système d'events Joomla.

## Design Elements

Maquettes Figma « Module "Ressources" — version Paris Cité » :
- Liste des ressources (vue tableau) — node `2259-23081`
- Modale « Importer un document » (états Empty / Uploaded) — node `2259-23623`
- Modale « Accès et partage » (onglets Rôles et utilisateurs / Espaces d'affichage / Lien de partage) — node `2259-23352`

User stories détaillées : `docs/user-stories/module-ressources.md`.

## Test Scenarios

- **Liste & droits** : un coordinateur voit tous les documents ; un utilisateur sans droit ne voit pas un document restreint.
- **Import valide** : un fichier au format/taille autorisés est importé, listé, et rangé dans le dossier courant.
- **Import invalide** : un fichier trop volumineux ou de format interdit est rejeté avec message clair, sans persistance.
- **Retrait avant validation** : sélection d'un fichier puis suppression → retour à l'état vide, bouton « Importer » désactivé.
- **Dossier** : création d'un dossier, déplacement/rangement d'un document, suppression de dossier (documents → racine).
- **Accès rôle/groupe/utilisateur** : ajout puis suppression d'un rôle, d'un groupe et d'un utilisateur ; persistance après « Enregistrer » ; unicité respectée (pas de doublon).
- **Espaces d'affichage** : configuration d'un formulaire/campagne/programme/page et rechargement → valeurs conservées.
- **Lien de partage** : génération d'un code unique ; accès public correct avec bon mot de passe ; refus si mauvais mot de passe ou lien expiré ; révocation rend le lien inopérant.
- **Compteur** : chaque téléchargement incrémente le compteur affiché.
- **Sélection multiple** : action groupée (suppression/téléchargement) appliquée à la sélection ; action désactivée si sélection vide.
- **Sécurité** : tentative d'accès direct à un document non autorisé ou à un fichier par id/chemin → refusée ; mot de passe de lien jamais renvoyé par l'API.

## Success-story

Un coordinateur Paris Cité centralise dans « Ressources » les documents partagés (informations candidats, déroulés de formation, tarifs…), les organise en dossiers, en restreint l'accès aux bons rôles/groupes/utilisateurs, les expose sur les bons espaces d'affichage, et partage un document externe via un lien protégé par mot de passe et expirant à une date donnée — sans dépendre d'une candidature.

**Mesure du succès** : adoption (nombre de documents importés et de liens générés), réduction des partages hors plateforme (email), absence d'incident d'accès non autorisé, et compteurs de téléchargements reflétant l'usage réel.
