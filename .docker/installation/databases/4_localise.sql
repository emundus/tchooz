--
-- French overwrites
--

--
-- Table `jos_extensions`
--
UPDATE IGNORE `jos_extensions` SET `params` = REPLACE(`params`, 'en-GB', 'fr-FR') WHERE `extension_id` = 10;

UPDATE IGNORE `jos_menu_types` SET `description` = REPLACE(`description`, 'The main menu for the site', 'Le menu principal du site') WHERE `id` = 1;
UPDATE IGNORE `jos_menu_types` SET `title` = REPLACE(`title`, 'Main Menu', 'Menu principal') WHERE `id` = 1;

UPDATE IGNORE `jos_menu` SET `title` = REPLACE(`title`, 'Home', 'Accueil') WHERE `id` = 101;

INSERT INTO `jos_extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`) VALUES
(0, 'French (fr-FR) Language pack', 'package', 'pkg_fr-FR', '', 0, 1, 1, 0, 0, '', '', '', 0, 0);

INSERT INTO `jos_extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT `extension_id`, 'French (fr-FR)', 'language', 'fr-FR', '', 0, 1, 1, 0, 0, '', '', '', 0, 0 FROM `jos_extensions` WHERE `name` = 'French (fr-FR) Language pack';
INSERT INTO `jos_extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT `extension_id`, 'French (fr-FR))', 'language', 'fr-FR', '', 1, 1, 1, 0, 0, '', '', '', 0, 0 FROM `jos_extensions` WHERE `name` = 'French (fr-FR) Language pack';
INSERT INTO `jos_extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT `extension_id`, 'French (fr-FR)', 'language', 'fr-FR', '', 3, 1, 1, 0, 0, '', '', '', 0, 0 FROM `jos_extensions` WHERE `name` = 'French (fr-FR) Language pack';

--
-- Table `jos_languages`
--
INSERT INTO `jos_languages` (`lang_id`, `lang_code`, `title`, `title_native`, `sef`, `image`, `description`, `metadesc`, `published`, `access`, `ordering`) VALUES
(2, 'fr-FR', 'French (fr-FR)', 'French (fr-FR)', 'fr', 'fr_fr', '', '', 1, 1, 2);

--
-- Table `jos_assets`
--
INSERT INTO `jos_assets` (`parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`) VALUES
(11, 46, 47, 2, 'com_languages.language.2', 'French (fr-FR)', '{}');

UPDATE IGNORE `jos_assets` SET `rgt` = 48 WHERE `id` = 11;

--
-- Table `jos_update_sites_extensions`
--
INSERT INTO `jos_update_sites_extensions` VALUES (2, 247);

--
-- Table `jos_usergroups`
--
UPDATE IGNORE `jos_usergroups` SET `title` = 'Public' WHERE `id` = 1;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Enregistré' WHERE `id` = 2;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Auteur' WHERE `id` = 3;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Rédacteur' WHERE `id` = 4;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Éditeur' WHERE `id` = 5;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Gestionnaire' WHERE `id` = 6;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Administrateur' WHERE `id` = 7;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Super utilisateur' WHERE `id` = 8;
UPDATE IGNORE `jos_usergroups` SET `title` = 'Invité' WHERE `id` = 9;


--
-- Table `jos_viewlevels`
--
UPDATE IGNORE `jos_viewlevels` SET `title` = 'Public' WHERE `id` = 1;
UPDATE IGNORE `jos_viewlevels` SET `title` = 'Enregistré' WHERE `id` = 2;
UPDATE IGNORE `jos_viewlevels` SET `title` = 'Spécial' WHERE `id` = 3;
UPDATE IGNORE `jos_viewlevels` SET `title` = 'Invité' WHERE `id` = 5;
UPDATE IGNORE `jos_viewlevels` SET `title` = 'Super utilisateur' WHERE `id` = 6;

--
-- Table `jos_modules`
--
UPDATE IGNORE `jos_modules` SET `title` = 'Menu principal' WHERE `id` = 1;
UPDATE IGNORE `jos_modules` SET `title` = 'Connexion' WHERE `id` = 2;
UPDATE IGNORE `jos_modules` SET `title` = 'Articles populaires' WHERE `id` = 3;
UPDATE IGNORE `jos_modules` SET `title` = 'Derniers articles' WHERE `id` = 4;
UPDATE IGNORE `jos_modules` SET `title` = 'Barre d\'outils' WHERE `id` = 8;
UPDATE IGNORE `jos_modules` SET `title` = 'Notifications' WHERE `id` = 9;
UPDATE IGNORE `jos_modules` SET `title` = 'Utilisateurs connectés' WHERE `id` = 10;
UPDATE IGNORE `jos_modules` SET `title` = 'Menu d\'administration' WHERE `id` = 12;
UPDATE IGNORE `jos_modules` SET `title` = 'Titre' WHERE `id` = 15;
UPDATE IGNORE `jos_modules` SET `title` = 'Connexion' WHERE `id` = 16;
UPDATE IGNORE `jos_modules` SET `title` = 'Fil de navigation' WHERE `id` = 17;
UPDATE IGNORE `jos_modules` SET `title` = 'Statut multilangue' WHERE `id` = 79;
UPDATE IGNORE `jos_modules` SET `title` = 'Version Joomla' WHERE `id` = 86;
UPDATE IGNORE `jos_modules` SET `title` = 'Données exemple' WHERE `id` = 87;
UPDATE IGNORE `jos_modules` SET `title` = 'Dernières actions' WHERE `id` = 88;
UPDATE IGNORE `jos_modules` SET `title` = 'Demande de confidentialité' WHERE `id` = 89;
UPDATE IGNORE `jos_modules` SET `title` = 'Aide à la connexion' WHERE `id` = 90;
UPDATE IGNORE `jos_modules` SET `title` = 'Système' WHERE `id` = 91;
UPDATE IGNORE `jos_modules` SET `title` = 'Contenus' WHERE `id` = 92;
UPDATE IGNORE `jos_modules` SET `title` = 'Menus' WHERE `id` = 93;
UPDATE IGNORE `jos_modules` SET `title` = 'Composants' WHERE `id` = 94;
UPDATE IGNORE `jos_modules` SET `title` = 'Utilisateurs' WHERE `id` = 95;
UPDATE IGNORE `jos_modules` SET `title` = 'Articles populaires' WHERE `id` = 96;
UPDATE IGNORE `jos_modules` SET `title` = 'Derniers articles' WHERE `id` = 97;
UPDATE IGNORE `jos_modules` SET `title` = 'Utilisateurs identifiés' WHERE `id` = 98;
UPDATE IGNORE `jos_modules` SET `title` = 'Lien vers le site' WHERE `id` = 99;
UPDATE IGNORE `jos_modules` SET `title` = 'Messages' WHERE `id` = 100;
UPDATE IGNORE `jos_modules` SET `title` = 'Messages de post-installation' WHERE `id` = 101;
UPDATE IGNORE `jos_modules` SET `title` = 'Statut Utilisateur' WHERE `id` = 102;
UPDATE IGNORE `jos_modules` SET `title` = 'Site' WHERE `id` = 103;
UPDATE IGNORE `jos_modules` SET `title` = 'Système' WHERE `id` = 104;
UPDATE IGNORE `jos_modules` SET `title` = 'Extensions tierces' WHERE `id` = 105;
UPDATE IGNORE `jos_modules` SET `title` = 'Aide' WHERE `id` = 106;
UPDATE IGNORE `jos_modules` SET `title` = 'Demande de confidentialité' WHERE `id` = 107;
UPDATE IGNORE `jos_modules` SET `title` = 'Statut de confidentialité' WHERE `id` = 108;
UPDATE IGNORE `jos_modules` SET `title` = 'Visites guidées' WHERE `id` = 109;
UPDATE IGNORE `jos_modules` SET `title` = 'Menu principal blog' WHERE `id` = 110;
UPDATE IGNORE `jos_modules` SET `title` = 'Menu spécial' WHERE `id` = 111;
UPDATE IGNORE `jos_modules` SET `title` = 'Liens de flux RSS' WHERE `id` = 112;
UPDATE IGNORE `jos_modules` SET `title` = 'Articles archivés' WHERE `id` = 113;
UPDATE IGNORE `jos_modules` SET `title` = 'Derniers messages' WHERE `id` = 114;
UPDATE IGNORE `jos_modules` SET `title` = 'Anciens articles' WHERE `id` = 115;
UPDATE IGNORE `jos_modules` SET `title` = 'Menu bas' WHERE `id` = 116;
UPDATE IGNORE `jos_modules` SET `title` = 'Rechercher' WHERE `id` = 117;
UPDATE IGNORE `jos_modules` SET `title` = 'Image' WHERE `id` = 118;
UPDATE IGNORE `jos_modules` SET `title` = 'Tags populaires' WHERE `id` = 119;
UPDATE IGNORE `jos_modules` SET `title` = 'Articles similaires' WHERE `id` = 120;
UPDATE IGNORE `jos_modules` SET `title` = 'Informations du site ' WHERE `id` = 121;

--
-- Table `jos_scheduler_tasks`
--
UPDATE IGNORE `jos_scheduler_tasks` SET `title` = 'Faire pivoter les fichiers journaux' WHERE `id` = 1;
UPDATE IGNORE `jos_scheduler_tasks` SET `title` = 'Nettoyer les données de la session' WHERE `id` = 2;
UPDATE IGNORE `jos_scheduler_tasks` SET `title` = 'Envoyer un message de mise à jour Joomla' WHERE `id` = 3;

--
-- Table `jos_categories`
--

UPDATE IGNORE `jos_categories` SET `path` = 'non-classe' WHERE `id` = 2;
UPDATE IGNORE `jos_categories` SET `path` = 'non-classe' WHERE `id` = 3;
UPDATE IGNORE `jos_categories` SET `path` = 'non-classe' WHERE `id` = 4;
UPDATE IGNORE `jos_categories` SET `path` = 'non-classe' WHERE `id` = 5;
UPDATE IGNORE `jos_categories` SET `path` = 'non-classe' WHERE `id` = 7;

UPDATE IGNORE `jos_categories` SET `title` = 'Non classé' WHERE `id` = 2;
UPDATE IGNORE `jos_categories` SET `title` = 'Non classé' WHERE `id` = 3;
UPDATE IGNORE `jos_categories` SET `title` = 'Non classé' WHERE `id` = 4;
UPDATE IGNORE `jos_categories` SET `title` = 'Non classé' WHERE `id` = 5;
UPDATE IGNORE `jos_categories` SET `title` = 'Non classé' WHERE `id` = 7;

UPDATE IGNORE `jos_categories` SET `alias` = 'non-classe' WHERE `id` = 2;
UPDATE IGNORE `jos_categories` SET `alias` = 'non-classe' WHERE `id` = 3;
UPDATE IGNORE `jos_categories` SET `alias` = 'non-classe' WHERE `id` = 4;
UPDATE IGNORE `jos_categories` SET `alias` = 'non-classe' WHERE `id` = 5;
UPDATE IGNORE `jos_categories` SET `alias` = 'non-classe' WHERE `id` = 7;
