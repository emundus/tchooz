SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE jos_action_logs;
TRUNCATE TABLE jos_dropfiles;
TRUNCATE TABLE jos_dropfiles_files;
TRUNCATE TABLE jos_emundus_9_00;
TRUNCATE TABLE jos_emundus_admission;
TRUNCATE TABLE jos_emundus_campaign_candidature;
TRUNCATE TABLE jos_emundus_campaign_candidature_links;
TRUNCATE TABLE jos_emundus_campaign_candidature_tabs;
TRUNCATE TABLE jos_emundus_campaign_workflow;
TRUNCATE TABLE jos_emundus_campaign_workflow_repeat_campaign;
TRUNCATE TABLE jos_emundus_campaign_workflow_repeat_entry_status;
TRUNCATE TABLE jos_emundus_campaign_workflow_repeat_programs;
TRUNCATE TABLE jos_emundus_chatroom;
TRUNCATE TABLE jos_emundus_chatroom_users;
TRUNCATE TABLE jos_emundus_cifre_links;
TRUNCATE TABLE jos_emundus_comments;
TRUNCATE TABLE jos_emundus_cv;
TRUNCATE TABLE jos_emundus_cv_690_repeat;
TRUNCATE TABLE jos_emundus_declaration;
TRUNCATE TABLE jos_emundus_evaluations;
TRUNCATE TABLE jos_emundus_favorite_programmes;
TRUNCATE TABLE jos_emundus_files_request;
TRUNCATE TABLE jos_emundus_filters;
TRUNCATE TABLE jos_emundus_final_grade;
TRUNCATE TABLE jos_emundus_gre;
TRUNCATE TABLE jos_emundus_groups;
TRUNCATE TABLE jos_emundus_group_assoc;
TRUNCATE TABLE jos_emundus_hikashop;
TRUNCATE TABLE jos_emundus_hikashop_programs;
TRUNCATE TABLE jos_emundus_hikashop_programs_repeat_code_prog;
TRUNCATE TABLE jos_emundus_languages;
TRUNCATE TABLE jos_emundus_languages_692_repeat;
TRUNCATE TABLE jos_emundus_learning_agreement;
TRUNCATE TABLE jos_emundus_learning_agreement_status;
TRUNCATE TABLE jos_emundus_logs;
TRUNCATE TABLE jos_emundus_mobility;
TRUNCATE TABLE jos_emundus_personal_detail;
TRUNCATE TABLE jos_emundus_qcm;
TRUNCATE TABLE jos_emundus_qualifications;
TRUNCATE TABLE jos_emundus_qualifications_686_repeat;
TRUNCATE TABLE jos_emundus_qualifications_689_repeat;
TRUNCATE TABLE jos_emundus_reference_letter;
TRUNCATE TABLE jos_emundus_references;
TRUNCATE TABLE jos_emundus_scholarship;
TRUNCATE TABLE jos_emundus_setup_campaigns;
TRUNCATE TABLE jos_emundus_setup_campaigns_repeat_limit_status;
TRUNCATE TABLE jos_emundus_setup_dashboard;
TRUNCATE TABLE jos_emundus_setup_dashbord_repeat_widgets;
TRUNCATE TABLE jos_emundus_setup_exceptions;
TRUNCATE TABLE jos_emundus_setup_programmes;
TRUNCATE TABLE jos_emundus_setup_emails_trigger;
TRUNCATE TABLE jos_emundus_setup_emails_trigger_repeat_group_id;
TRUNCATE TABLE jos_emundus_setup_emails_trigger_repeat_profile_id;
TRUNCATE TABLE jos_emundus_setup_emails_trigger_repeat_programme_id;
TRUNCATE TABLE jos_emundus_setup_emails_trigger_repeat_user_id;
TRUNCATE TABLE jos_emundus_setup_groups_repeat_course;
TRUNCATE TABLE jos_emundus_setup_letters;
TRUNCATE TABLE jos_emundus_setup_letters_repeat_training;
TRUNCATE TABLE jos_emundus_setup_teaching_unity;
TRUNCATE TABLE jos_emundus_tag_assoc;
TRUNCATE TABLE jos_emundus_uploads;
TRUNCATE TABLE jos_emundus_uploads_repeat_filename;
TRUNCATE TABLE jos_emundus_uploads_sync;
TRUNCATE TABLE jos_emundus_users;
TRUNCATE TABLE jos_emundus_users_institutions;
TRUNCATE TABLE jos_emundus_users_assoc;
TRUNCATE TABLE jos_emundus_users_attachments;
TRUNCATE TABLE jos_emundus_users_profiles;
TRUNCATE TABLE jos_emundus_version;
TRUNCATE TABLE jos_fabrik_connections;
TRUNCATE TABLE jos_finder_links;
TRUNCATE TABLE jos_finder_links_terms;
TRUNCATE TABLE jos_finder_logging;
TRUNCATE TABLE jos_finder_taxonomy;
TRUNCATE TABLE jos_finder_taxonomy_map;
TRUNCATE TABLE jos_finder_terms;
TRUNCATE TABLE jos_finder_terms_common;
TRUNCATE TABLE jos_finder_tokens;
TRUNCATE TABLE jos_finder_tokens_aggregate;
TRUNCATE TABLE jos_hikashop_order;
TRUNCATE TABLE jos_hikashop_user;
TRUNCATE TABLE `jos_hikashop_payment` ;
TRUNCATE TABLE `jos_hikashop_config` ;
TRUNCATE TABLE `jos_content_frontpage` ;
TRUNCATE TABLE `jos_content_rating` ;
TRUNCATE TABLE jos_messages;
TRUNCATE TABLE jos_redirect_links;
TRUNCATE TABLE jos_securitycheckpro_blacklist;
TRUNCATE TABLE jos_securitycheckpro_dynamic_blacklist;
TRUNCATE TABLE jos_securitycheckpro_logs;
TRUNCATE TABLE jos_securitycheckpro_sessions;
TRUNCATE TABLE jos_securitycheckpro_whitelist;
TRUNCATE TABLE jos_session;
TRUNCATE TABLE jos_updates;
TRUNCATE TABLE jos_user_profiles;
TRUNCATE TABLE jos_user_usergroup_map;
TRUNCATE TABLE jos_users;
TRUNCATE TABLE jos_overrider;
TRUNCATE TABLE jos_fabrik_log;
TRUNCATE TABLE jos_fabrik_form_sessions;

#FABRIK cleanup
DELETE FROM `jos_fabrik_lists` WHERE `published` = -2;
DELETE FROM `jos_fabrik_forms` WHERE `published`=-2;
DELETE FROM `jos_fabrik_groups` WHERE `published`=-2;
DELETE FROM `jos_fabrik_elements` WHERE `published` = -2;
DELETE FROM `jos_fabrik_groups` WHERE `id` not in (select group_id from jos_fabrik_formgroup);
DELETE FROM `jos_fabrik_formgroup` WHERE `form_id` not in (SELECT id FROM jos_fabrik_forms);
DELETE FROM `jos_fabrik_formgroup` WHERE `group_id` not in (SELECT id FROM jos_fabrik_groups);
DELETE FROM `jos_fabrik_elements` WHERE `group_id` not in (SELECT id FROM jos_fabrik_groups);
DELETE FROM `jos_fabrik_joins` WHERE `group_id`!=0 AND `group_id` not in (SELECT id FROM jos_fabrik_groups) ;
DELETE FROM `jos_fabrik_joins` WHERE `element_id`!=0 AND `element_id` not in (SELECT id FROM jos_fabrik_elements);
DELETE FROM `jos_fabrik_joins` WHERE `list_id`!=0 AND `list_id` not in (SELECT id FROM jos_fabrik_lists);
DELETE FROM `jos_fabrik_jsactions` WHERE `element_id` not in (SELECT id FROM jos_fabrik_elements);

UPDATE jos_fabrik_forms SET created_by = 1, created_by_alias = 'admin' WHERE 1;
UPDATE jos_fabrik_groups SET created_by = 1, created_by_alias = 'admin' WHERE 1;
UPDATE jos_fabrik_lists SET created_by = 1, created_by_alias = 'admin' WHERE 1;

DELETE FROM `jos_emundus_acl` WHERE `group_id` not in (SELECT id from jos_emundus_setup_groups);

DELETE FROM `jos_falang_content` WHERE `reference_id` not in (select id from jos_menu) AND `reference_table` like 'menu';
DELETE FROM `jos_falang_content` WHERE `reference_id` not in (select id from jos_content) AND `reference_table` like 'content';
DELETE FROM `jos_falang_content` WHERE `reference_id` not in (select id from jos_contact_details) AND `reference_table` like 'contact_details';
DELETE FROM `jos_falang_content` WHERE `reference_id` not in (select id from jos_modules) AND `reference_table` like 'modules';
DELETE FROM `jos_falang_content` WHERE `reference_id` not in (select id from jos_emundus_setup_tags) AND `reference_table` like 'emundus_setup_tags';

UPDATE `jos_fabrik_elements` set hidden=1 WHERE `plugin` LIKE 'fabrikinternalid' OR `plugin` LIKE 'internalid';

UPDATE `jos_content` SET `hits`=0, `created`=NOW(), `modified`=NOW() WHERE 1;
UPDATE `jos_falang_content` SET `modified_by`=62, `modified`=NOW() WHERE 1;
UPDATE `jos_emundus_acl` SET `time_date`=NOW() WHERE 1;

UPDATE `jos_modules` SET `content` = REPLACE(`content`, 'https://candidature.sorbonne-universites.fr/', '') WHERE `content` like '%https://candidature.sorbonne-universites.fr/%';
UPDATE `jos_modules` SET `content` = REPLACE(`content`, 'http://candidature.sorbonne-universites.fr/', '') WHERE `content` like '%http://candidature.sorbonne-universites.fr/%';
UPDATE `jos_content` SET `introtext` = REPLACE(`introtext`, 'http://candidature.sorbonne-universites.fr/', '') WHERE `introtext` like '%http://candidature.sorbonne-universites.fr/%';
UPDATE `jos_content` SET `introtext` = REPLACE(`introtext`, 'http://claroline.emundus.fr/', '') WHERE `introtext` like '%http://claroline.emundus.fr/%';
UPDATE `jos_emundus_email_templates` SET `Template` = REPLACE(`Template`, 'https://candidature.sorbonne-universites.fr/', '') WHERE `Template` like '%https://candidature.sorbonne-universites.fr/%' ;

UPDATE `jos_emundus_setup_checklist` SET `text` = REPLACE(`text`, 'https://candidature.sorbonne-universites.fr/', '') WHERE `text` like '%https://candidature.sorbonne-universites.fr/%';
UPDATE `jos_content` SET `fulltext` = REPLACE(`fulltext`, 'https://candidature.sorbonne-universites.fr/', '') WHERE `fulltext` like '%https://candidature.sorbonne-universites.fr/%';
UPDATE `jos_fabrik_elements` SET `label` = REPLACE(`label`, 'https://candidature.sorbonne-universites.fr/', '') WHERE `label` like '%https://candidature.sorbonne-universites.fr/%';

UPDATE `jos_emundus_setup_emails` SET `message` = REPLACE(`message`, '[TO_SEARCH]', '[TO_REPLACE]') WHERE `message` like '%[TO_SEARCH]%' ;
UPDATE `jos_emundus_setup_emails` SET `subject`=REPLACE(subject, "Sorbonne Université", "[SITE_NAME]");
UPDATE `jos_emundus_setup_emails` SET `message`=REPLACE(message, "Sorbonne Université", "[SITE_NAME]") ;

DELETE FROM `jos_categories` WHERE `extension` like 'com_dropfiles';
DELETE FROM `jos_assets` WHERE `name` LIKE 'com_dropfiles.category.%';

DELETE FROM `jos_emundus_setup_action_tag` WHERE `id` NOT IN (1,2,3);

ALTER TABLE `jos_users` auto_increment = 100;
ALTER TABLE `jos_emundus_users` auto_increment = 100 ROW_FORMAT = COMPACT;

TRUNCATE TABLE `jos_emundus_users_profiles`;

SET FOREIGN_KEY_CHECKS=1;
