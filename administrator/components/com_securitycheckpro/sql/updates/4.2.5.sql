DROP TABLE IF EXISTS `#__securitycheckpro_db`;
CREATE TABLE `#__securitycheckpro_db` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`Product` VARCHAR(35) NOT NULL,
`vuln_type` VARCHAR(35),
`Vulnerableversion` VARCHAR(40) DEFAULT '---',
`modvulnversion` VARCHAR(2) DEFAULT '==',
`Joomlaversion` VARCHAR(30) DEFAULT 'Notdefined',
`modvulnjoomla` VARCHAR(20) DEFAULT '==',
`description` VARCHAR(90),
`vuln_class` VARCHAR(70),
`published` VARCHAR(35),
`vulnerable` VARCHAR(70),
`solution_type` VARCHAR(35) DEFAULT '???',
`solution` VARCHAR(70),
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `#__securitycheckpro_db` (`product`,`vuln_type`,`vulnerableversion`,`modvulnversion`,`Joomlaversion`,
`modvulnjoomla`,`description`,`vuln_class`,`published`,`vulnerable`,`solution_type`,`solution`) VALUES 
('Joomla!','core','4.0.0','<=','4','>=','Joomla! core','One vulnerability','Aug 24 2021','Joomla 4.0.0','update','4.0.1'),
('Joomla!','core','4.1.2','<=','4','>=','Joomla! core','Seven low vulnerabilities','Mar 31 2022','Joomla 4.0.0 through 4.1.0','update','4.1.2'),
('Joomla!','core','4.2.0','==','4','>=','Joomla! core','One low vulnerability','Aug 30 2022','Joomla 4.2.0','update','4.2.1'),
('Joomla!','core','4.2.3','<=','4','>=','Joomla! core','Two low vulnerabilities','Oct 26 2022','Joomla 4.0.0 to 4.2.3','update','4.2.4'),
('com_eshop','component','3.6.0','==','4','>=','Eshop Component','Xss vulnerability','Oct 31 2022','Version 3.6.0','update','3.6.1'),
('com_edocman','component','1.23.3','==','4','>=','Edocman Component','Xss vulnerability','Oct 31 2022','Version 1.23.3','update','1.23.4'),
('com_joomrecipe','component','4.2.2','==','4','>=','Joomrecipe Component','Xss vulnerability','Oct 31 2022','Version 4.2.2','update','4.2.4'),
('com_opencart','component','3.0.3.19','==','4','>=','Jcart for Opencart Component','Xss vulnerability','Oct 31 2022','Version 3.0.3.19','update','3.0.3.25'),
('com_vikappointments','component','1.7.3','==','4','>=','Vik Appointments Component','Xss vulnerability','Oct 31 2022','Version 1.7.3 and maybe lower','none','No details'),
('com_vikrentcar','component','1.14','==','4','>=','Vik Rent Car Component','Xss vulnerability','Oct 31 2022','Version 1.14 and maybe lower','none','No details'),
('com_career','component','3.3.0','==','4','>=','JoomBri Careers Component','Xss vulnerability','Oct 31 2022','Version 3.3.0 and maybe lower','none','No details'),
('com_solidres','component','2.12.9','==','4','>=','SolidRes Component','Xss vulnerability','Oct 31 2022','Version 2.12.9 and maybe lower','none','No details'),
('com_rentalotplus','component','19.05','==','4','>=','Rentalot Plus Component','Xss vulnerability','Oct 31 2022','Version 19.05 and maybe lower','none','No details'),
('com_easyshop','component','1.4.1','==','4','>=','Easy Shop Component','Xss vulnerability','Oct 31 2022','Version 1.4.1 and maybe lower','none','No details'),
('com_jsjobs','component','1.3.6','==','4','>=','Js Jobs Pro Component','Sql injection vulnerability','Oct 31 2022','Version 1.3.6 and maybe lower','none','No details'),
('Joomla!','core','4.2.4','<=','4','>=','Joomla! core','One low vulnerability','Nov 09 2022','Joomla 4.0.0 to 4.2.4','update','4.2.5'),
('com_kunena','component','6.0.4','<=','4','>=','Kunena Component','Not defined vulnerability','Nov 23 2022','Version 6.0.4 and maybe lower','update','6.0.5'),
('Joomla!','core','4.2.6','<=','4','>=','Joomla! core','Two low vulnerabilities','Jan 31 2023','Joomla 4.0.0 to 4.2.6','update','4.2.7'),
('Joomla!','core','4.2.7','<=','4','>=','Joomla! core','One critical vulnerability','Feb 16 2023','Joomla 4.0.0 to 4.2.7','update','4.2.8'),
('Joomla!','core','4.3.1','<=','4','>=','Joomla! core','Two moderate vulnerabilities','May 31 2023','Joomla 4.2.0 to 4.3.1','update','4.3.2'),
('com_jbusinessdirectory','component','5.7.7','>=','4','>=','JBusiness Directory Component','Other vulnerability','Jul 14 2023','Version 5.7.7 and lower','update','5.8.8'),
('com_hikashop','component','4.7.2','<=','4','>=','Hikashop Component','Sql Injection vulnerability','Jul 14 2023','Version 4.4.1 to 4.7.2','update','4.7.4'),
('com_jchoptimize','component','8.0.4','<=','4','>=','Jchoptimize Component','Other vulnerability','Aug 04 2023','Version 8.0.4 and maybe lower','update','8.0.5'),
('com_jlexreview','component','6.0.1','<=','4','>=','JLex Review Component','Cross Site Scripting vulnerability','Nov 17 2023','Version 6.0.1 and maybe lower','none','No details'),
('com_jlexguestbook','component','1.6.4','<=','4','>=','JLex GuestBook Component','Cross Site Scripting vulnerability','Nov 17 2023','Version 1.6.4 and maybe lower','none','No details'),
('Joomla!','core','4.4.0','<=','4','>=','Joomla! core','One high vulnerability','Nov 30 2023','Joomla 1.6.0 to 4.4.0','update','4.4.1'),
('Joomla!','core','5.0.0','<=','5','>=','Joomla! core','One high vulnerability','Nov 30 2023','Joomla 5.0.0','update','5.0.1'),
('Joomla!','core','4.0.1','<=','4','>=','Joomla! core','Five vulnerabilities','Jul 10 2024','Joomla 4.0.0 to 4.4.5','update','4.4.6'),
('Joomla!','core','5.1.1','<=','5','>=','Joomla! core','Five vulnerabilities','Jul 10 2024','Joomla 5.0.0 to 5.1.1','update','5.1.2'),
('Joomla!','core','5.1.2','<=','5','>=','Joomla! core','Five vulnerabilities','Aug 20 2024','Joomla 5.0.0 to 5.1.2','update','5.1.3'),
('Joomla!','core','4.4.9','<=','4','>=','Joomla! core','Three vulnerabilities','Jan 08 2025','Joomla 4.0.0 to 4.4.9','update','4.4.10'),
('Joomla!','core','5.2.2','<=','5','>=','Joomla! core','Three vulnerabilities','Jan 08 2025','Joomla 5.0.0 to 5.2.2','update','5.2.3'),
('Joomla!','core','5.2.3','<=','5','>=','Joomla! core','One vulnerability','Feb 19 2025','Joomla 5.0.0 to 5.2.3','update','5.2.4'),
('com_convertforms','component','4.4.7','<=','4','>=','Convert Forms Component','File upload and XSS vulnerabilities','Feb 25 2025','Version 1.0.0 to 4.4.7','update','4.4.9'),
('com_convertforms','component','4.4.7','<=','5','>=','Convert Forms Component','File upload and XSS vulnerabilities','Feb 25 2025','Version 1.0.0 to 4.4.7','update','4.4.9'),
('com_kunena','component','6.4.2','<=','5','>=','Kunena Component','Not defined high vulnerability','May 31 2025','Version 6.4.2 and maybe lower','update','6.4.3');

DROP TABLE IF EXISTS `#__securitycheckpro_sessions`;
CREATE TABLE IF NOT EXISTS `#__securitycheckpro_sessions` (
`userid` INT(4) UNSIGNED NOT NULL,
`session_id` VARCHAR(200) NOT NULL,
`username` VARCHAR(150) NOT NULL,
`ip` VARCHAR(26) NOT NULL,
`user_agent` VARCHAR(300) NOT NULL,
PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__securitycheckpro_update_database` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`version` VARCHAR(10),
`last_check` DATETIME,
`message` VARCHAR(300),
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `#__securitycheckpro_update_database` (`version`) VALUES ('1.3.30');

CREATE TABLE IF NOT EXISTS `#__securitycheckpro_url_inspector_logs` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ip` VARCHAR(35) NOT NULL,
`uri` VARCHAR(100),
`forbidden_words` VARCHAR(300) NOT NULL,
`date_added` DATETIME,
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__securitycheckpro_trackactions` (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`message` TEXT,
`log_date` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
`extension` VARCHAR(50) NOT NULL DEFAULT '',
`user_id` INT(11) NOT NULL DEFAULT '0',
`ip_address` VARCHAR(40) NOT NULL DEFAULT '0.0.0.0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__securitycheckpro_trackactions_extensions` (
 `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
 `extension` VARCHAR(100) NOT NULL DEFAULT '',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `#__securitycheckpro_trackactions_extensions` (`id`, `extension`) VALUES
(1, 'com_banners'),
(2, 'com_cache'),
(3, 'com_categories'),
(4, 'com_config'),
(5, 'com_contact'),
(6, 'com_content'),
(7, 'com_installer'),
(8, 'com_media'),
(9, 'com_menus'),
(10, 'com_messages'),
(11, 'com_modules'),
(12, 'com_newsfeeds'),
(13, 'com_plugins'),
(14, 'com_redirect'),
(15, 'com_tags'),
(16, 'com_templates'),
(17, 'com_users');

CREATE TABLE IF NOT EXISTS `#__securitycheckpro_trackactions_tables_data` (
`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`type_title` varchar(255) NOT NULL DEFAULT '',
`type_alias` varchar(255) NOT NULL DEFAULT '',
`title_holder` varchar(255) DEFAULT NULL,
`table_values` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `#__securitycheckpro_trackactions_tables_data` (`id`, `type_title`, `type_alias`, `title_holder`, `table_values`) VALUES
(1, 'article', 'com_content.article', 'title', '{"table_type":"Content","table_prefix":"JTable"}'),
(2, 'article', 'com_content.form', 'title', '{"table_type":"Content","table_prefix":"JTable"}'),
(3, 'banner', 'com_banners.banner', 'name', '{"table_type":"Banner","table_prefix":"BannersTable"}'),
(4, 'user_note', 'com_users.note', 'subject', '{"table_type":"Note","table_prefix":"UsersTable"}'),
(5, 'media', 'com_media.file', 'name', '{"table_type":"","table_prefix":""}'),
(6, 'category', 'com_categories.category', 'title', '{"table_type":"Category","table_prefix":"JTable"}'),
(7, 'menu', 'com_menus.menu', 'title', '{"table_type":"Menu","table_prefix":"JTable"}'),
(8, 'menu_item', 'com_menus.item', 'title', '{"table_type":"Menu","table_prefix":"JTable"}'),
(9, 'newsfeed', 'com_newsfeeds.newsfeed', 'name', '{"table_type":"Newsfeed","table_prefix":"NewsfeedsTable"}'),
(10, 'link', 'com_redirect.link', 'old_url', '{"table_type":"Link","table_prefix":"RedirectTable"}'),
(11, 'tag', 'com_tags.tag', 'title', '{"table_type":"Tag","table_prefix":"TagsTable"}'),
(12, 'style', 'com_templates.style', 'title', '{"table_type":"","table_prefix":""}'),
(13, 'plugin', 'com_plugins.plugin', 'name', '{"table_type":"Extension","table_prefix":"JTable"}'),
(14, 'component_config', 'com_config.component', 'name', '{"table_type":"","table_prefix":""}'),
(15, 'contact', 'com_contact.contact', 'name', '{"table_type":"Contact","table_prefix":"ContactTable"}'),
(16, 'module', 'com_modules.module', 'title', '{"table_type":"Module","table_prefix":"JTable"}'),
(17, 'access_level', 'com_users.level', 'title', '{"table_type":"Viewlevel","table_prefix":"JTable"}'),
(18, 'banner_client', 'com_banners.client', 'name', '{"table_type":"Client","table_prefix":"BannersTable"}');
