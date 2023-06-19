CREATE TABLE IF NOT EXISTS `#__falang_content` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`language_id` int(11) NOT NULL default '0',
	`reference_id` int(11) NOT NULL default '0',
	`reference_table` varchar(100) NOT NULL default '',
	`reference_field` varchar(100) NOT NULL default '',
	`value` mediumtext  NOT NULL,
	`original_value` varchar(255) default NULL,
	`original_text` mediumtext default NULL,
	`modified` datetime NULL default NULL,
	`modified_by` int(11) unsigned NOT NULL default '0',
	`published` tinyint(1) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__falang_tableinfo` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`joomlatablename` varchar(100) NOT NULL default '',
	`tablepkID` varchar(100) NOT NULL default '',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;