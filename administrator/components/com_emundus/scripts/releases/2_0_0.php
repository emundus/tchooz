<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Scheduler\Administrator\Model\TaskModel;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Symfony\Component\Yaml\Yaml;

class Release2_0_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			$disabled = EmundusHelperUpdate::disableEmundusPlugins('webauthn');
			if ($disabled)
			{
				EmundusHelperUpdate::displayMessage('Le plugin WebAuthn a été désactivé.', 'success');
			}

			$query->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('eval') . ' = 0')
				->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('fnum'))
				->where($this->db->quoteName('eval') . ' = 1');
			$this->db->setQuery($query);
			if ($this->db->execute())
			{
				EmundusHelperUpdate::displayMessage('Les valeurs par défaut des champs fnums ont été retirées, ces valeurs sont désormais pré-remplis via le plugin emundus_events.', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de la modification des champs fnums');
			}

			$column_added = EmundusHelperUpdate::addColumn('jos_emundus_setup_attachments', 'max_filesize', 'DOUBLE(6,2)');
			if ($column_added['status'])
			{
				EmundusHelperUpdate::displayMessage('La colonne max_filesize a été ajoutée à la table jos_emundus_setup_attachments.', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de l\'ajout de la colonne max_filesize à la table jos_emundus_setup_attachments.');
			}

			// Install colorpicker
			EmundusHelperUpdate::installExtension('plg_fabrik_element_emundus_colorpicker', 'emundus_colorpicker', '{"name":"plg_fabrik_element_emundus_colorpicker","type":"plugin","creationDate":"November 2023","author":"Media A-Team, Inc.","copyright":"Copyright (C) 2005-2023 Media A-Team, Inc. - All rights reserved.","authorEmail":"brice.hubinet@emundus.fr","authorUrl":"www.emundus.fr","version":"4.0Zeta","description":"PLG_ELEMENT_COLOURPICKER_DESCRIPTION","group":"","filename":"emundus_colorpicker"}', 'plugin', 1, 'fabrik_element');

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('ffg.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->set($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote('emundus_colorpicker'))
				->set($this->db->quoteName('fe.params') . ' = ' . $this->db->quote('{"rgaa":"1","save_label":"1","show_in_rss_feed":"0","show_label_in_rss_feed":"0","use_as_rss_enclosure":"0","rollover":"","tipseval":"0","tiplocation":"top-left","labelindetails":"0","labelinlist":"0","comment":"","edit_access":"1","edit_access_user":"","view_access":"1","view_access_user":"","list_view_access":"1","encrypt":"0","store_in_db":"1","default_on_copy":"0","can_order":"0","alt_list_heading":"","custom_link":"","custom_link_target":"","custom_link_indetails":"1","use_as_row_class":"0","include_in_list_query":"1","always_render":"0","icon_folder":"0","icon_hovertext":"1","icon_file":"","icon_subdir":"","filter_length":"20","filter_access":"1","full_words_only":"0","filter_required":"0","filter_build_method":"0","filter_groupby":"text","inc_in_adv_search":"1","filter_class":"input-medium","filter_responsive_class":"","tablecss_header_class":"","tablecss_header":"","tablecss_cell_class":"","tablecss_cell":"","sum_on":"0","sum_label":"Sum","sum_access":"8","sum_split":"","avg_on":"0","avg_label":"Average","avg_access":"8","avg_round":"0","avg_split":"","median_on":"0","median_label":"Median","median_access":"8","median_split":"","count_on":"0","count_label":"Count","count_condition":"","count_access":"8","count_split":"","custom_calc_on":"0","custom_calc_label":"Custom","custom_calc_query":"","custom_calc_access":"1","custom_calc_split":"","custom_calc_php":"","validations":[]}'))
				->where($this->db->quoteName('fe.plugin') . ' LIKE ' . $this->db->quote('dropdown'))
				->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('class'))
				->where($this->db->quoteName('fl.db_table_name') . ' != ' . $this->db->quote('jos_emundus_setup_status'));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('ffg.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->set($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote('emundus_colorpicker'))
				->set($this->db->quoteName('fe.params') . ' = ' . $this->db->quote('{"rgaa":"1","save_label":"0","show_in_rss_feed":"0","show_label_in_rss_feed":"0","use_as_rss_enclosure":"0","rollover":"","tipseval":"0","tiplocation":"top-left","labelindetails":"0","labelinlist":"0","comment":"","edit_access":"1","edit_access_user":"","view_access":"1","view_access_user":"","list_view_access":"1","encrypt":"0","store_in_db":"1","default_on_copy":"0","can_order":"0","alt_list_heading":"","custom_link":"","custom_link_target":"","custom_link_indetails":"1","use_as_row_class":"0","include_in_list_query":"1","always_render":"0","icon_folder":"0","icon_hovertext":"1","icon_file":"","icon_subdir":"","filter_length":"20","filter_access":"1","full_words_only":"0","filter_required":"0","filter_build_method":"0","filter_groupby":"text","inc_in_adv_search":"1","filter_class":"input-medium","filter_responsive_class":"","tablecss_header_class":"","tablecss_header":"","tablecss_cell_class":"","tablecss_cell":"","sum_on":"0","sum_label":"Sum","sum_access":"8","sum_split":"","avg_on":"0","avg_label":"Average","avg_access":"8","avg_round":"0","avg_split":"","median_on":"0","median_label":"Median","median_access":"8","median_split":"","count_on":"0","count_label":"Count","count_condition":"","count_access":"8","count_split":"","custom_calc_on":"0","custom_calc_label":"Custom","custom_calc_query":"","custom_calc_access":"1","custom_calc_split":"","custom_calc_php":"","validations":[]}'))
				->where($this->db->quoteName('fe.plugin') . ' LIKE ' . $this->db->quote('dropdown'))
				->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('class'))
				->where($this->db->quoteName('fl.db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_status'));
			$this->db->setQuery($query);
			$this->db->execute();

			// JPluginHelper::importPlugin('emundus', 'custom_event_handler');
			//\Joomla\CMS\Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterProgramCreate', ['formModel' => $formModel, 'data' => $formModel->data]]);

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_forms'))
				->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('SETUP_PROGRAM'));
			$this->db->setQuery($query);
			$setup_program = $this->db->loadObject();

			if (!empty($setup_program))
			{
				$params                = json_decode($setup_program->params, true);
				$params['curl_code']   = 'JPluginHelper::importPlugin(\'emundus\', \'custom_event_handler\');\Joomla\CMS\Factory::getApplication()->triggerEvent(\'onCallEventHandler\', [\'onAfterProgramCreate\', [\'formModel\' => $formModel, \'data\' => $formModel->data]]);';
				$setup_program->params = json_encode($params);
				$this->db->updateObject('#__fabrik_forms', $setup_program, 'id');
			}

			// Add custom events
			$get_attachments_for_profile_event_added = EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAfterGetAttachmentsForProfile', 'category' => 'Files']
			]);
			if ($get_attachments_for_profile_event_added)
			{
				EmundusHelperUpdate::displayMessage('L\'événement onAfterGetAttachmentsForProfile a été ajouté.', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de l\'ajout de l\'événement onAfterGetAttachmentsForProfile.');
			}
			//

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_EMAIL_PLACEHOLDER', 'exemple@domaine.com');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_EMAIL_PLACEHOLDER', 'example@domain.com', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::addColumn('jos_emundus_widgets_repeat_access', 'access_level', 'INT', 11);

			$datas       = [
				'menutype'     => 'actions-users',
				'title'        => 'Exporter',
				'alias'        => 'export',
				'link'         => '',
				'type'         => 'heading',
				'component_id' => 0,
			];
			$export_menu = EmundusHelperUpdate::addJoomlaMenu($datas);

			if ($export_menu['status'])
			{
				EmundusHelperUpdate::insertFalangTranslation(1, $export_menu['id'], 'menu', 'title', 'Export');

				$datas         = [
					'menutype'     => 'actions-users',
					'title'        => 'Exporter vers Excel',
					'alias'        => 'export-excel',
					'type'         => 'url',
					'link'         => 'index.php?option=com_emundus&view=users&format=raw&layout=export&Itemid={Itemid}',
					'component_id' => 0,
					'note'         => '12|r|1|6'
				];
				$export_action = EmundusHelperUpdate::addJoomlaMenu($datas, $export_menu['id']);

				if ($export_action['status'])
				{
					EmundusHelperUpdate::insertFalangTranslation(1, $export_action['id'], 'menu', 'title', 'Export to Excel');
				}
			}

			if (!class_exists('EmundusModelAdministratorCampaign'))
			{
				require_once(JPATH_ROOT . '/administrator/components/com_emundus/models/campaign.php');
			}
			$m_admin_campaign = new \EmundusModelAdministratorCampaign();
			if (!$m_admin_campaign->installCampaignMore())
			{
				throw new \Exception('Erreur lors de l\'installation de la table jos_emundus_setup_campaigns_more');
			}

			EmundusHelperUpdate::installExtension('plg_emundus_system', 'emundus', null, 'plugin', 1, 'system');
			EmundusHelperUpdate::installExtension('eMundus - Update profile', 'emundusupdateprofile', null, 'plugin', 1, 'fabrik_form');

			if (!EmundusHelperUpdate::addColumn('jos_messages', 'email_cc', 'TEXT')['status'])
			{
				throw new \Exception('Erreur lors de l\'ajout de la colonne email_cc à la table jos_messages.');
			}

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('textarea'));
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach ($elements as $element)
			{
				$params                          = json_decode($element->params, true);
				$params['wysiwyg_extra_buttons'] = 0;
				$element->params                 = json_encode($params);
				$this->db->updateObject('#__fabrik_elements', $element, 'id');
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('form_template') . ' = ' . $this->db->quote('emundus'))
				->set($this->db->quoteName('view_only_template') . ' = ' . $this->db->quote('emundus'))
				->where($this->db->quoteName('form_template') . ' IN (' . implode(',', $this->db->quote(['', 'bootstrap'])) . ')')
				->orWhere($this->db->quoteName('view_only_template') . ' IN (' . implode(',', $this->db->quote(['', 'bootstrap'])) . ')');
			$this->db->setQuery($query);
			if ($this->db->execute())
			{
				EmundusHelperUpdate::displayMessage('Les templates par défaut des formulaires ont été changés pour emundus.', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de la modification des templates des formulaires.');
			}

			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_CSV', 'Export');
			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_CSV', 'Export', 'override', 0, null, null, 'en-GB');

			// 1.38.10 : Set default params for checklist menu
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=checklist'));
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();

			foreach ($menus as $menu)
			{
				$params                     = json_decode($menu->params);
				$params->show_info_panel    = 0;
				$params->show_info_legend   = 1;
				$params->show_browse_button = 0;
				$params->show_nb_column     = 1;

				$update = [
					'id'     => $menu->id,
					'params' => json_encode($params)
				];
				$update = (object) $update;
				$this->db->updateObject('#__menu', $update, 'id');
			}

			// 1.39.0 : Add evaluation form to program
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__fabrik_groups'))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('GROUP_PROGRAM_DETAIL'));
			$this->db->setQuery($query);
			$group_program_detail = $this->db->loadResult();

			if (!empty($group_program_detail))
			{
				EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'evaluation_form', 'INT', 11, 1);

				EmundusHelperUpdate::insertTranslationsTag('ELEMENT_PROGRAM_FORM_EVALUATION', 'Formulaire d\'évaluation');
				EmundusHelperUpdate::insertTranslationsTag('ELEMENT_PROGRAM_FORM_EVALUATION', 'Evaluation form', 'override', 0, null, null, 'en-GB');

				$datas  = [
					'name'     => 'evaluation_form',
					'group_id' => $group_program_detail,
					'plugin'   => 'databasejoin',
					'label'    => 'ELEMENT_PROGRAM_FORM_EVALUATION',
				];
				$params = [
					'join_db_name'            => 'jos_fabrik_lists',
					'join_key_column'         => 'form_id',
					'join_val_column'         => "label",
					'join_val_column_concat'  => "{thistable}.label",
					'database_join_where_sql' => "WHERE {thistable}.db_table_name = 'jos_emundus_evaluations'"
				];
				$eid    = EmundusHelperUpdate::addFabrikElement($datas, $params, false)['id'];

				if (!empty($eid))
				{
					$datas  = [
						'element_id'      => $eid,
						'join_from_table' => '',
						'table_join'      => 'jos_fabrik_lists',
						'table_key'       => 'evaluation_form',
						'table_join_key'  => 'form_id',
						'join_type'       => 'left',
						'group_id'        => $group_program_detail
					];
					$params = [
						'join-label' => 'label',
						'type'       => 'element',
						'pk'         => "`jos_fabrik_lists`.`id`"
					];
					EmundusHelperUpdate::addFabrikJoin($datas, $params);

					$query->clear()
						->update($this->db->quoteName('#__fabrik_elements'))
						->set($this->db->quoteName('hidden') . ' = 1')
						->where($this->db->quoteName('name') . ' = ' . $this->db->quote('fabrik_group_id'));
					$this->db->setQuery($query);
					$this->db->execute();

					$query->clear()
						->select('id,fabrik_group_id')
						->from($this->db->quoteName('#__emundus_setup_programmes'))
						->where($this->db->quoteName('fabrik_group_id') . ' IS NOT NULL');
					$this->db->setQuery($query);
					$programs = $this->db->loadAssocList();

					foreach ($programs as $program)
					{
						if (!empty($program['fabrik_group_id']))
						{
							$fabrik_groups = explode(',', $program['fabrik_group_id']);

							$query->clear()
								->select('form_id')
								->from($this->db->quoteName('#__fabrik_formgroup'))
								->where($this->db->quoteName('group_id') . ' IN (' . implode(',', $this->db->quote($fabrik_groups)) . ')');
							$this->db->setQuery($query);
							$evaluation_form_id = $this->db->loadResult();

							if (!empty($evaluation_form_id))
							{
								$query->clear()
									->update($this->db->quoteName('#__emundus_setup_programmes'))
									->set($this->db->quoteName('evaluation_form') . ' = ' . $this->db->quote($evaluation_form_id))
									->where($this->db->quoteName('id') . ' = ' . $this->db->quote($program['id']));
								$this->db->setQuery($query);
								$this->db->execute();
							}
						}
					}
				}
			}
			//

			// 1.39.0 : Add cron to purge logs
			$query->clear()
				->select('extension_id,params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('plg_system_logrotation'));
			$this->db->setQuery($query);
			$logrotation = $this->db->loadObject();

			if (!empty($logrotation->extension_id))
			{
				$params = json_decode($logrotation->params, true);

				$params['cachetimeout'] = 7;
				$params['logstokeep']   = 4;

				$query->clear()
					->update($this->db->quoteName('#__extensions'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('extension_id') . ' = ' . $logrotation->extension_id);
				$this->db->setQuery($query);
				$this->db->execute();
			}

			EmundusHelperUpdate::installExtension('plg_cron_logspurge', 'emunduslogsandmessagespurge', '{"name":"plg_cron_logspurge","type":"plugin","creationDate":"May 2024","author":"eMundus","copyright":"Copyright (C) 2024 emundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"1.39.0","description":"PLG_CRON_LOGSPURGE_DESC","group":"","filename":"emunduslogsandmessagespurge"}', 'plugin', 1, 'fabrik_cron', '{"amount_time":"1","unit_time":"year","export_zip":"1", "amount_time_tmp":"1","unit_time_tmp":"week"}');

			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('jos_fabrik_cron'))
				->where($this->db->quoteName('plugin') . ' = ' . $this->db->quote('emunduslogsandmessagespurge'));

			$this->db->setQuery($query);
			$existing_cron = $this->db->loadResult();

			if ($existing_cron !== null)
			{
				EmundusHelperUpdate::displayMessage('Plugin cron already created.');
			}
			else
			{
				$current_hour = date('G');
				if ($current_hour < 4)
				{
					$last_four_hour = date('Y-m-d 04:00:00', strtotime('yesterday'));
				}
				else
				{
					$last_four_hour = date('Y-m-d 04:00:00');
				}

				$inserted = [
					'label'            => 'Logs and messages purge',
					'frequency'        => 1,
					'unit'             => 'day',
					'created'          => date('0000-00-00 00:00:00'),
					'modified'         => date('0000-00-00 00:00:00'),
					'checked_out_time' => date('0000-00-00 00:00:00'),
					'plugin'           => 'emunduslogsandmessagespurge',
					'published'        => 1,
					'lastrun'          => date($last_four_hour),
					'params'           => '{"connection":"1","table":"","cron_row_limit":"100","log":"0","log_email":"","require_qs":"0","require_qs_secret":"","cron_rungate":"1","cron_reschedule_manual":"0","amount_time":"1","unit_time":"year","export_zip":"1", "amount_time_tmp":"1","unit_time_tmp":"week"}'
				];
				$inserted = (object) $inserted;
				$this->db->insertObject('jos_fabrik_cron', $inserted);
			}
			//

			$query->clear()
				->delete($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=samples'));
			$this->db->setQuery($query);
			$this->db->execute();

			EmundusHelperUpdate::installExtension('plg_sampledata_emundus', 'emundus', null, 'plugin', 1, 'sampledata');

			// 1.38.11
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_contact'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$this->db->execute();
			//

			// Add missing falang tableinfo
			$table_names = [
				'emundus_plugin_events'              => 'id',
				'emundus_setup_action_tag'           => 'id',
				'emundus_setup_attachments'          => 'id',
				'emundus_setup_campaigns'            => 'id',
				'emundus_setup_checklist'            => 'id',
				'emundus_setup_emails'               => 'id',
				'emundus_setup_groups'               => 'id',
				'emundus_setup_mobility'             => 'id',
				'emundus_setup_profiles'             => 'id',
				'emundus_setup_programmes'           => 'id',
				'emundus_setup_status'               => 'step',
				'emundus_setup_tags,emundus_widgets' => 'id'
			];
			foreach ($table_names as $table_name => $primary_key)
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__falang_tableinfo'))
					->where($this->db->quoteName('joomlatablename') . ' LIKE ' . $this->db->quote($table_name));
				$this->db->setQuery($query);
				$existing = $this->db->loadResult();

				if (empty($existing))
				{
					$inserted = [
						'joomlatablename' => $table_name,
						'tablepkID'       => $primary_key
					];
					$inserted = (object) $inserted;
					$this->db->insertObject('#__falang_tableinfo', $inserted);
				}
			}
			//

			// Add autocomplete filter to fields
			$query->clear()
				->select('id,filter_type,filter_exact_match')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('field'))
				->where($this->db->quoteName('filter_type') . ' NOT LIKE ' . $this->db->quote(''));
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach ($elements as $element)
			{
				$element->filter_type        = 'auto-complete';
				$element->filter_exact_match = 0;
				$this->db->updateObject('#__fabrik_elements', $element, 'id');
			}
			//

			// Install new emundusattachment plugin
			EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_EMUNDUSATTACHMENT', 'emundusattachment', null, 'plugin', 1, 'fabrik_form');

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_forms'))
				->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('SETUP_UPLOAD_FILE_FOR_APPLICANT'));
			$this->db->setQuery($query);
			$setup_upload_file_for_applicant = $this->db->loadObject();

			if (!empty($setup_upload_file_for_applicant))
			{
				$params                                  = json_decode($setup_upload_file_for_applicant->params, true);
				$params['curl_code']                     = '';
				$params['plugin_state']                  = [1];
				$params['plugins']                       = ['emundusattachment'];
				$params['plugin_locations']              = ['front'];
				$params['plugin_events']                 = ['both'];
				$params['plugin_description']            = ['upload_attachment'];
				$setup_upload_file_for_applicant->params = json_encode($params);
				$this->db->updateObject('#__fabrik_forms', $setup_upload_file_for_applicant, 'id');
			}

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ATTACHMENT_ADDED', 'Le fichier a été ajouté avec succès.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ATTACHMENT_ADDED', 'The file has been successfully added.', 'override', 0, null, null, 'en-GB');
			//

			// Install new copy/move plugin
			EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_EMUNDUSCOPYFILE', 'emunduscopyfile', null, 'plugin', 1, 'fabrik_form');

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_forms'))
				->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('COPY_MOVE_FILE'));
			$this->db->setQuery($query);
			$copy_form = $this->db->loadObject();

			if (!empty($copy_form))
			{
				$params                       = json_decode($copy_form->params, true);
				$params['curl_code']          = '';
				$params['plugin_state']       = [1];
				$params['plugins']            = ['emunduscopyfile'];
				$params['plugin_locations']   = ['front'];
				$params['plugin_events']      = ['both'];
				$params['plugin_description'] = ['Copy file'];
				$copy_form->params            = json_encode($params);
				$this->db->updateObject('#__fabrik_forms', $copy_form, 'id');
			}

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COPIED_SUCCESSFULLY', 'Le dossier a été copié avec succès.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COPIED_SUCCESSFULLY', 'The application file has been successfully copied.', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_MOVED_SUCCESSFULLY', 'Le dossier a été déplacé avec succès.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_MOVED_SUCCESSFULLY', 'The application file has been successfully moved.', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COPIED_SUCCESSFULLY_PLURAL', 'Les dossiers ont été copiés avec succès.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COPIED_SUCCESSFULLY_PLURAL', 'The application files have been successfully copied.', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_MOVED_SUCCESSFULLY_PLURAL', 'Les dossiers ont été déplacés avec succès.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_MOVED_SUCCESSFULLY_PLURAL', 'The application files have been successfully moved.', 'override', 0, null, null, 'en-GB');
			//

			// Install new module for back button
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('title') . ' LIKE ' . $this->db->quote('%Back button%'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_custom'));
			$this->db->setQuery($query);
			$old_back_button = $this->db->loadResult();

			if (!empty($old_back_button))
			{
				$query->clear()
					->delete($this->db->quoteName('#__modules_menu'))
					->where($this->db->quoteName('moduleid') . ' = ' . $this->db->quote($old_back_button));
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->delete($this->db->quoteName('#__modules'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($old_back_button));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			EmundusHelperUpdate::installExtension('mod_emundus_back', 'mod_emundus_back', '{"name":"mod_emundus_back","type":"module","creationDate":"2024-06","author":"eMundus","copyright":"Copyright (C) 2022 eMundus. All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.0.0","description":"MOD_EMUNDUS_BACK_XML_DESCRIPTION","group":"","namespace":"Emundus\\Module\\BackButton","filename":"mod_emundus_back"}', 'module');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_back'));
			$this->db->setQuery($query);
			$back_button = $this->db->loadResult();

			if (empty($back_button))
			{
				$params        = [
					'module_tag'     => 'div',
					'bootstrap_size' => 0,
					'header_tag'     => 'h3',
					'header_class'   => '',
					'style'          => 0
				];
				$insert_module = [
					'title'     => '[GUEST] Back button',
					'note'      => 'Back button available on login and register views',
					'ordering'  => 0,
					'position'  => 'header-a',
					'published' => 1,
					'module'    => 'mod_emundus_back',
					'access'    => 9,
					'showtitle' => 0,
					'params'    => json_encode($params),
					'client_id' => 0,
					'language'  => '*',
				];
				$insert_module = (object) $insert_module;
				$this->db->insertObject('#__modules', $insert_module);

				$back_button = $this->db->insertid();
			}

			$query->clear()
				->select('moduleid')
				->from($this->db->quoteName('#__modules_menu'))
				->where($this->db->quoteName('moduleid') . ' = ' . $this->db->quote($back_button));
			$this->db->setQuery($query);
			$back_button_menu = $this->db->loadResult();

			if (empty($back_button_menu))
			{
				$link_to_menu = [
					'index.php?option=com_users&view=login',
					'index.php?option=com_fabrik&view=form&formid=307',
					'index.php?option=com_users&view=reset'
				];
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('link') . ' IN (' . implode(',', $this->db->quote($link_to_menu)) . ')');
				$this->db->setQuery($query);
				$menus = $this->db->loadColumn();

				foreach ($menus as $menu)
				{
					$insert_menu = [
						'moduleid' => $back_button,
						'menuid'   => $menu,
					];
					$insert_menu = (object) $insert_menu;
					$this->db->insertObject('#__modules_menu', $insert_menu);
				}
			}
			//

			$column_added = EmundusHelperUpdate::addColumn('jos_emundus_setup_profiles', 'display_description', 'TINYINT(1)');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__fabrik_groups'))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('GROUPE_SETUP_PROFILE'));
			$this->db->setQuery($query);
			$group_setup_profile = $this->db->loadResult();

			if (!empty($group_setup_profile))
			{
				EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_SETUP_PROFILE_DISPLAY_DESCRIPTION', 'Afficher cette description sur le tableau de bord des utilisateurs ayant ce profil.');
				EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_SETUP_PROFILE_DISPLAY_DESCRIPTION', 'Display this description on the dashboard of users with this profile.', 'override', 0, null, null, 'en-GB');

				$datas                = [
					'name'     => 'display_description',
					'label'    => 'COM_EMUNDUS_SETUP_PROFILE_DISPLAY_DESCRIPTION',
					'group_id' => $group_setup_profile,
					'plugin'   => 'yesno',
				];
				$display_desc_element = EmundusHelperUpdate::addFabrikElement($datas, [], false);

				if (!empty($display_desc_element['id']))
				{
					$query->clear()
						->update($this->db->quoteName('#__fabrik_elements'))
						->set($this->db->quoteName('ordering') . ' = 4')
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($display_desc_element['id']));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			$query->clear()
				->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('profile'));
			$this->db->setQuery($query);
			$profile_form = $this->db->loadResult();

			if (!empty($profile_form))
			{
				$query->clear()
					->select('id,params')
					->from($this->db->quoteName('#__fabrik_forms'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($profile_form));
				$this->db->setQuery($query);
				$profile_form = $this->db->loadObject();

				if (!empty($profile_form->id))
				{
					$params                       = json_decode($profile_form->params, true);
					$params['plugins']            = ['emundusupdateprofile'];
					$params['plugin_locations']   = ['both'];
					$params['plugin_events']      = ['both'];
					$params['plugin_description'] = ['Update eMundus session'];
					$profile_form->params         = json_encode($params);
					$this->db->updateObject('#__fabrik_forms', $profile_form, 'id');
				}
			}

			// Update email activation
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_email_templates'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('registration'));
			$this->db->setQuery($query);
			$registration_tmpl = $this->db->loadResult();

			if (empty($registration_tmpl))
			{
				$registration_email_tmpl = file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/registration_template.html');
				$tmpl_insert             = [
					'date_time' => date('Y-m-d H:i:s'),
					'lbl'       => 'registration',
					'Template'  => $registration_email_tmpl,
					'type'      => 1,
					'published' => 1
				];
				$tmpl_insert             = (object) $tmpl_insert;
				$this->db->insertObject('#__emundus_email_templates', $tmpl_insert);

				$registration_tmpl = $this->db->insertid();
			}

			$registration_email_content = '<p>[USER_NAME],</p><p>Vous venez de créer un compte <a href="[SITE_URL]" target="_blank">sur cette plateforme</a>.</p>
<p>Pour l\'activer veuillez cliquer sur le lien ci-dessous :&nbsp;</p><p></p><hr/><p>[USER_NAME],</p><p>You have just created an account <a href="[SITE_URL]"
                                                                                                      target="_blank">on
    this platform</a>.</p><p>To activate it, please click on the link below:&nbsp;</p>';
			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_emails'))
				->set($this->db->quoteName('message') . ' = ' . $this->db->quote($registration_email_content))
				->set($this->db->quoteName('email_tmpl') . ' = ' . $this->db->quote($registration_tmpl))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('registration_email'));
			$this->db->setQuery($query);
			$this->db->execute();

			$old_values = [
				'fr-FR' => 'Cet utilisateur et/ou ce mot de passe est incorrect',
				'en-GB' => 'This user and/or password is incorrect'
			];
			$new_values = [
				'fr-FR' => 'Cette combinaison adresse email/mot de passe est incorrecte',
				'en-GB' => 'This email address/password combination is incorrect'
			];
			EmundusHelperUpdate::updateOverrideTag('JGLOBAL_AUTH_INVALID_PASS', $old_values, $new_values);
			EmundusHelperUpdate::updateOverrideTag('JGLOBAL_AUTH_NO_USER', $old_values, $new_values);

			EmundusHelperUpdate::installExtension('Overrides Quick Icon', 'overrides', null, 'plugin', 1, 'quickicon');
			EmundusHelperUpdate::installExtension('mod_emundus_version', 'mod_emundus_version', null, 'module', 1, '', '[]', true);
			EmundusHelperUpdate::installExtension('mod_emundus_notes', 'mod_emundus_notes', null, 'module', 1, '', '[]', true);

			EmundusHelperUpdate::createModule('Version eMundus', 'status', 'mod_emundus_version', '{"layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}', 1, 1, 3, 0, 1);
			EmundusHelperUpdate::createModule('Ressources et Notes', 'cpanel', 'mod_emundus_notes', '{"layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}', 1, 1, 1, 1, 1);

			EmundusHelperUpdate::installExtension('Menu Quick Icon', 'menu', null, 'plugin', 1, 'quickicon', '{"context":"mod_quickicon","menutype":"coordinatormenu"}');

			EmundusHelperUpdate::insertTranslationsTag('YOUR_FILE_HAS_BEEN_SENT', 'Votre candidature a été envoyée');
			EmundusHelperUpdate::insertTranslationsTag('YOUR_FILE_HAS_BEEN_SENT', 'Your application has been sent', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('CONGRATULATIONS', 'Félicitations');
			EmundusHelperUpdate::insertTranslationsTag('CONGRATULATIONS', 'Congratulations', 'override', 0, null, null, 'en-GB');

			// Use new emundusimportcsv plugin
			$query->clear()
				->select('ff.id,ff.params')
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->leftJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('ff.id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->where($this->db->quoteName('fl.db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_csv_import'));
			$this->db->setQuery($query);
			$import_csv_form = $this->db->loadObject();

			if (!empty($import_csv_form->id))
			{
				$params                       = json_decode($import_csv_form->params, true);
				$params['plugins']            = ['emundusimportcsv'];
				$params['plugin_locations']   = ['both'];
				$params['plugin_events']      = ['both'];
				$params['plugin_description'] = ['Import'];
				$import_csv_form->params      = json_encode($params);
				$this->db->updateObject('#__fabrik_forms', $import_csv_form, 'id');
			}

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('import_file_model'));
			$this->db->setQuery($query);
			$import_file_model_elt = $this->db->loadObject();

			if (!empty($import_file_model_elt->id))
			{
				$params                        = json_decode($import_file_model_elt->params, true);
				$params['calc_calculation']    = str_replace(['jos_emundus_users___email', 'jos_emundus_users___firstname', 'jos_emundus_users___lastname'], ['email', 'firstname', 'lastname'], $params['calc_calculation']);
				$import_file_model_elt->params = json_encode($params);
				$this->db->updateObject('#__fabrik_elements', $import_file_model_elt, 'id');
			}
			//

			$query->clear()
				->select('ff.id,ff.params')
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('fl.db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_setup_letters'));
			$this->db->setQuery($query);
			$form_letters = $this->db->loadObject();

			if (!empty($form_letters->id))
			{
				$params                  = json_decode($form_letters->params, true);
				$params['plugin_events'] = ['both'];
				$query->clear()
					->update($this->db->quoteName('#__fabrik_forms'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form_letters->id));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			// Translate 404 page
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_404', 'La page que vous cherchez semble introuvable...');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_404', 'The page you\'re looking for doesn\'t seem to be there...', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_404_BUTTON', 'Retour à la page d\'accueil');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_404_BUTTON', 'Back to home page', 'override', 0, null, null, 'en-GB');

			$error_particle = file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/error.yaml');
			if ($error_particle == 'null')
			{
				$error_particle = "enabled: '1'
title: 'Oups !'
image: /media/com_emundus/images/tchoozy/complex-illustrations/page-not-found.svg
description: COM_EMUNDUS_ERROR_404
css:
  class: ''
button: COM_EMUNDUS_ERROR_404_BUTTON";
				file_put_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/error.yaml', $error_particle);
			}
			EmundusHelperUpdate::updateYamlVariable('description', 'COM_EMUNDUS_ERROR_404', JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/error.yaml');
			EmundusHelperUpdate::updateYamlVariable('button', 'COM_EMUNDUS_ERROR_404_BUTTON', JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/error.yaml');
			//

			// Status restriction in groups
			EmundusHelperUpdate::addColumn('jos_emundus_setup_groups', 'filter_status', 'INT', 1, 1, '0');
			EmundusHelperUpdate::addColumn('jos_emundus_setup_groups', 'status', 'INT', 11, 1);

			$columns       = [
				[
					'name'   => 'parent_id',
					'type'   => 'int',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'status',
					'type'   => 'int',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'params',
					'type'   => 'varchar',
					'length' => 255,
					'null'   => 1,
				]
			];
			$repeat_status = EmundusHelperUpdate::createTable('jos_emundus_setup_groups_repeat_status', $columns);

			$query->clear()
				->select('ffg.group_id,fl.id')
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->where($this->db->quoteName('fl.db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_setup_groups'))
				->where($this->db->quoteName('fl.label') . ' LIKE ' . $this->db->quote('TABLE_SETUP_GROUPS'));
			$this->db->setQuery($query);
			$setup_groups = $this->db->loadAssoc();

			if (!empty($setup_groups['group_id']))
			{
				$datas             = [
					'name'                 => 'filter_status',
					'group_id'             => $setup_groups['group_id'],
					'plugin'               => 'yesno',
					'label'                => 'SETUP_GROUPS_FILTER_STATUS',
					'show_in_list_summary' => 1
				];
				$filter_status_elt = EmundusHelperUpdate::addFabrikElement($datas)['id'];

				$datas      = [
					'name'                 => 'status',
					'group_id'             => $setup_groups['group_id'],
					'plugin'               => 'databasejoin',
					'label'                => 'SETUP_GROUPS_AVAILABLE_STATUS',
					'show_in_list_summary' => 1
				];
				$params     = [
					'database_join_display_type' => 'multilist',
					'join_db_name'               => 'jos_emundus_setup_status',
					'join_key_column'            => 'step',
					'join_val_column'            => 'value',
					'advanced_behavior'          => 1
				];
				$status_elt = EmundusHelperUpdate::addFabrikElement($datas, $params, false)['id'];

				$datas  = [
					'list_id'         => $setup_groups['id'],
					'element_id'      => $status_elt,
					'join_from_table' => 'jos_emundus_setup_groups',
					'table_join'      => 'jos_emundus_setup_groups_repeat_status',
					'table_key'       => 'status',
					'table_join_key'  => 'parent_id',
					'join_type'       => 'left',
					'group_id'        => 0,
				];
				$params = [
					'type' => 'repeatElement',
					'pk'   => '`jos_emundus_setup_groups_repeat_status`.`id`'
				];
				EmundusHelperUpdate::addFabrikJoin($datas, $params);

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__fabrik_jsactions'))
					->where($this->db->quoteName('element_id') . ' = ' . $filter_status_elt)
					->where($this->db->quoteName('action') . ' = ' . $this->db->quote('load'));
				$this->db->setQuery($query);
				$js_action_load = $this->db->loadResult();

				if (empty($js_action_load))
				{
					$status_load_jsaction = [
						'action' => 'load',
						'params' => '{"js_e_event":"","js_e_trigger":"fabrik_trigger_group_group139","js_e_condition":"","js_e_value":"","js_published":"1"}',
						'code'   => "var value = this.get(&#039;value&#039;);
const fab = this.form.elements;
let {
    jos_emundus_setup_groups___status
} = fab;

if(value == 1) {
  showFabrikElt(jos_emundus_setup_groups___status);
} else {
  hideFabrikElt(jos_emundus_setup_groups___status);
}"
					];

					$query->clear()
						->insert($this->db->quoteName('#__fabrik_jsactions'))
						->set($this->db->quoteName('element_id') . ' = ' . $filter_status_elt)
						->set($this->db->quoteName('action') . ' = ' . $this->db->quote($status_load_jsaction['action']))
						->set($this->db->quoteName('code') . ' = ' . $this->db->quote($status_load_jsaction['code']))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($status_load_jsaction['params']));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__fabrik_jsactions'))
					->where($this->db->quoteName('element_id') . ' = ' . $filter_status_elt)
					->where($this->db->quoteName('action') . ' = ' . $this->db->quote('change'));
				$this->db->setQuery($query);
				$js_action_change = $this->db->loadResult();

				if (empty($js_action_change))
				{
					$status_change_jsaction = [
						'action' => 'change',
						'params' => '{"js_e_event":"","js_e_trigger":"fabrik_trigger_group_group139","js_e_condition":"","js_e_value":"","js_published":"1"}',
						'code'   => "var value = this.get(&#039;value&#039;);
const fab = this.form.elements;
let {
    jos_emundus_setup_groups___status
} = fab;

if(value == 1) {
  showFabrikElt(jos_emundus_setup_groups___status);
} else {
  hideFabrikElt(jos_emundus_setup_groups___status,true);
}"
					];

					$query->clear()
						->insert($this->db->quoteName('#__fabrik_jsactions'))
						->set($this->db->quoteName('element_id') . ' = ' . $filter_status_elt)
						->set($this->db->quoteName('action') . ' = ' . $this->db->quote($status_change_jsaction['action']))
						->set($this->db->quoteName('code') . ' = ' . $this->db->quote($status_change_jsaction['code']))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($status_change_jsaction['params']));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				EmundusHelperUpdate::insertTranslationsTag('SETUP_GROUPS_FILTER_STATUS', 'Restreindre le changement de statut');
				EmundusHelperUpdate::insertTranslationsTag('SETUP_GROUPS_FILTER_STATUS', 'Restricting changes of status', 'override', 0, null, null, 'en-GB');

				EmundusHelperUpdate::insertTranslationsTag('SETUP_GROUPS_AVAILABLE_STATUS', 'Statuts');
				EmundusHelperUpdate::insertTranslationsTag('SETUP_GROUPS_AVAILABLE_STATUS', 'Statuses', 'override', 0, null, null, 'en-GB');
			}
			//

			// Translate users menu
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=users'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('coordinatormenu'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$users_menu = $this->db->loadResult();

			if (!empty($users_menu))
			{
				EmundusHelperUpdate::insertFalangTranslation(1, $users_menu, 'menu', 'title', 'Users', true);
			}
			//

			// References translations
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_SEND_REFERENCE_REQUEST', 'Envoyer la demande de référence');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_SEND_REFERENCE_REQUEST', 'Send the request for individual assessment', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_EMAIL_REFERENCE_TIP', 'La demande de recommandation sera envoyée à l\'adresse suivante');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_EMAIL_REFERENCE_TIP', 'The request for recommendation should be sent to the following address', 'override', 0, null, null, 'en-GB');

			$query->clear()
				->select('ff.id,ff.submit_button_label')
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->leftJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('ff.id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->where($this->db->quoteName('fl.db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_references'));
			$this->db->setQuery($query);
			$reference_forms = $this->db->loadObjectList();

			foreach ($reference_forms as $reference_form)
			{
				if ($reference_form->submit_button_label == 'Send the request for individual assessment')
				{
					$query->clear()
						->update($this->db->quoteName('#__fabrik_forms'))
						->set($this->db->quoteName('submit_button_label') . ' = ' . $this->db->quote('COM_EMUNDUS_SEND_REFERENCE_REQUEST'))
						->where($this->db->quoteName('id') . ' = ' . $reference_form->id);
					$this->db->setQuery($query);
					$this->db->execute();
				}

				$query->clear()
					->select('fe.id,fe.params')
					->from($this->db->quoteName('#__fabrik_formgroup', 'ffg'))
					->leftJoin($this->db->quoteName('#__fabrik_elements', 'fe') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('ffg.group_id'))
					->where($this->db->quoteName('ffg.form_id') . ' = ' . $reference_form->id)
					->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('Email_1'));
				$this->db->setQuery($query);
				$email_1 = $this->db->loadObject();

				if (!empty($email_1->id))
				{
					$params = json_decode($email_1->params, true);
					if ($params['rollover'] == 'The recommendation letter will be sent to this address.')
					{
						$params['rollover'] = 'COM_EMUNDUS_EMAIL_REFERENCE_TIP';

						$query->clear()
							->update($this->db->quoteName('#__fabrik_elements'))
							->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
							->where($this->db->quoteName('id') . ' = ' . $email_1->id);
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
			}

			$query->clear()
				->select('fe.id,fe.label,fe.params')
				->from($this->db->quoteName('#__fabrik_formgroup', 'ffg'))
				->leftJoin($this->db->quoteName('#__fabrik_elements', 'fe') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('ffg.group_id'))
				->where($this->db->quoteName('ffg.form_id') . ' = 68')
				->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('filename'));
			$this->db->setQuery($query);
			$fileupload_referent = $this->db->loadObject();

			if (!empty($fileupload_referent->id))
			{
				$query->clear();
				$params = json_decode($fileupload_referent->params, true);
				if (empty($params['rollover']))
				{
					$params['rollover'] = '.pdf';
					$query->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)));
				}
				if ($fileupload_referent->label == 'FILE (.pdf)')
				{
					$query->set($this->db->quoteName('label') . ' = ' . $this->db->quote('FILE'));
				}
				if (empty($params['rollover']) || $fileupload_referent->label == 'FILE (.pdf)')
				{
					$query->update($this->db->quoteName('#__fabrik_elements'))
						->where($this->db->quoteName('id') . ' = ' . $fileupload_referent->id);
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('submit_button_label') . ' = ' . $this->db->quote('SUBMIT'))
				->where($this->db->quoteName('id') . ' = 68')
				->where($this->db->quoteName('submit_button_label') . ' = ' . $this->db->quote('Upload'));
			$this->db->setQuery($query);
			$this->db->execute();
			//

			// Add page column to jos_messages
			EmundusHelperUpdate::addColumn('jos_messages', 'page', 'INT', 11);
			EmundusHelperUpdate::addColumn('jos_messages', 'ip', 'VARCHAR', 50);
			EmundusHelperUpdate::addColumn('jos_messages', 'site_name', 'VARCHAR', 255);
			EmundusHelperUpdate::addColumn('jos_messages', 'email_from', 'VARCHAR', 255);
			EmundusHelperUpdate::addColumn('jos_messages', 'email_to', 'VARCHAR', 255);
			//


			// Create mail_tester emails
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('mail_tester'));
			$this->db->setQuery($query);
			$mail_tester_tmpl = $this->db->loadResult();

			if (empty($mail_tester_tmpl))
			{
				$subject = 'E-mail de test provenant de [SITE_NAME]';
				$message = '<img src="[SITE_URL]/media/com_emundus/images/tchoozy/complex-illustrations/message-sent.svg" alt="Tchoozy message sent" style="width: 180px; display: block; margin-left: auto; margin-right: auto; margin-bottom: 20px;"><p>Bonjour,</p><p>Ceci est un test d\'e-mail envoyé par [SITE_NAME]. Si vous le recevez, vos paramètres e-mail sont corrects !</p><hr/><p>Hello,</p><p>This is a test e-mail sent by [SITE_NAME]. If you receive it, your e-mail settings are correct!</p>';
				$insert  = [
					'lbl'        => 'mail_tester',
					'subject'    => $subject,
					'message'    => $message,
					'type'       => 1,
					'published'  => 0,
					'email_tmpl' => 1,
					'category'   => 'Système'
				];
				$insert  = (object) $insert;
				$this->db->insertObject('#__emundus_setup_emails', $insert);
			}
			//

			$emConfig = ComponentHelper::getComponent('com_emundus')->getParams();

			if (empty($emConfig->get('default_email_smtphost', '')))
			{
				EmundusHelperUpdate::updateExtensionParam('custom_email_conf', '1');
				EmundusHelperUpdate::updateExtensionParam('custom_email_mailfrom', $this->app->get('mailfrom'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_fromname', $this->app->get('fromname'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtphost', $this->app->get('smtphost'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtpport', $this->app->get('smtpport'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtpsecure', $this->app->get('smtpsecure'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtpauth', $this->app->get('smtpauth'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtpuser', $this->app->get('smtpuser'));
				EmundusHelperUpdate::updateExtensionParam('custom_email_smtppass', $this->app->get('smtppass'));
			}

			if ($this->app->get('replyto') === null || $this->app->get('replyto') === '')
			{
				EmundusHelperUpdate::updateExtensionParam('custom_email_replyto', '');
			}
			else
			{
				EmundusHelperUpdate::updateExtensionParam('custom_email_replyto', $this->app->get('replyto'));
			}
			if ($this->app->get('replytoname') === null || $this->app->get('replyto') === '')
			{
				EmundusHelperUpdate::updateExtensionParam('custom_email_replytoname', '');
			}
			else
			{
				EmundusHelperUpdate::updateExtensionParam('custom_email_replytoname', $this->app->get('replytoname'));
			}

			EmundusHelperUpdate::displayMessage('Configuration des serveurs de mails réussis', 'success');
			//

			// Disable darkmode
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__template_styles'))
				->where($this->db->quoteName('template') . ' LIKE ' . $this->db->quote('atum'))
				->where($this->db->quoteName('client_id') . ' = 1');
			$this->db->setQuery($query);
			$atum_tmpl = $this->db->loadObject();

			if (!empty($atum_tmpl->id))
			{
				$params                = json_decode($atum_tmpl->params, true);
				$params['colorScheme'] = 0;

				$atum_tmpl->params = json_encode($params);
				$this->db->updateObject('#__template_styles', $atum_tmpl, 'id');
			}
			//

			// Condition builder
			EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'alias', 'VARCHAR', 255);

			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name' => 'created_by',
					'type' => 'INT',
					'null' => 0,
				],
				[
					'name' => 'updated',
					'type' => 'DATETIME',
					'null' => 1,
				],
				[
					'name' => 'updated_by',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name'   => 'label',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1,
				],
				[
					'name'   => 'type',
					'type'   => 'VARCHAR',
					'length' => 20,
					'null'   => 0,
				],
				[
					'name'   => 'group',
					'type'   => 'VARCHAR',
					'length' => 20,
					'null'   => 0,
				],
				[
					'name' => 'form_id',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name'    => 'published',
					'type'    => 'TINYINT',
					'length'  => 3,
					'default' => 1,
					'null'    => 0,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_form_rules', $columns, [], 'Rules for formbuilder');

			$columns = [
				[
					'name' => 'parent_id',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name'   => 'action',
					'type'   => 'VARCHAR',
					'length' => 100,
					'null'   => 0,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_form_rules_js_actions', $columns, [], 'Action rules for formbuilder');

			$columns = [
				[
					'name' => 'parent_id',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name' => 'fields',
					'type' => 'TEXT',
					'null' => 1,
				],
				[
					'name' => 'params',
					'type' => 'TEXT',
					'null' => 1,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_form_rules_js_actions_fields', $columns, [], 'Action rules for formbuilder');

			$columns = [
				[
					'name' => 'parent_id',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name'   => 'field',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1,
				],
				[
					'name'   => 'state',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 1,
				],
				[
					'name' => 'values',
					'type' => 'TEXT',
					'null' => 1,
				],
				[
					'name' => 'group',
					'type' => 'INT',
					'null' => 1,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_form_rules_js_conditions', $columns, [], 'Action rules for formbuilder');

			$columns = [
				[
					'name'    => 'group_type',
					'type'    => 'VARCHAR',
					'length'  => 10,
					'default' => 'AND',
					'null'    => 0,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_form_rules_js_conditions_group', $columns, [], 'Action rules for formbuilder');
			//

			EmundusHelperUpdate::installExtension('plg_editors_tiptap', 'tiptap', null, 'plugin', 1, 'editors');

			if (file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml'))
			{
				$content = file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml');
				$content = str_replace('gantry-assets://custom/scss/quill.scss', 'gantry-assets://custom/scss/editor.scss', $content);
				$content = str_replace('Quill', 'Editor', $content);

				file_put_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml', $content);
			}
			if (file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/_error/page/assets.yaml'))
			{
				$content = file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/_error/page/assets.yaml');
				$content = str_replace('gantry-assets://custom/scss/quill.scss', 'gantry-assets://custom/scss/editor.scss', $content);
				$content = str_replace('Quill', 'Editor', $content);

				file_put_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/_error/page/assets.yaml', $content);
			}

			EmundusHelperUpdate::installExtension('plg_fabrik_element_iban', 'iban', null, 'plugin', 1, 'fabrik_element');

			EmundusHelperUpdate::removeYamlVariable('structure', JPATH_ROOT . '/templates/g5_helium/custom/config/_error/layout.yaml', 'navigation');
			EmundusHelperUpdate::removeYamlVariable('structure', JPATH_ROOT . '/templates/g5_helium/custom/config/_error/layout.yaml', 'footer');
			EmundusHelperUpdate::removeYamlVariable('structure', JPATH_ROOT . '/templates/g5_helium/custom/config/_error/layout.yaml', 'top');
			EmundusHelperUpdate::removeYamlVariable('structure', JPATH_ROOT . '/templates/g5_helium/custom/config/_error/layout.yaml', 'sidebar');
			EmundusHelperUpdate::removeYamlVariable('structure', JPATH_ROOT . '/templates/g5_helium/custom/config/_error/layout.yaml', 'bottom');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_MINIMUM_REQUIREMENTS', 'Minimum %s');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_MINIMUM_REQUIREMENTS', 'Minimum %s', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_CHARACTERS', '%d caractères');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_CHARACTERS', '%d characters', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_DIGITS', '%d chiffre(s)');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_DIGITS', '%d number(s)', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_SYMBOLS', '%d symbole(s)');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_SYMBOLS', '%d symbol(s)', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_UPPERCASE', '%d lettre(s) majuscule');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_UPPERCASE', '%d uppercase letter(s)', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_LOWERCASE', '%d lettre(s) minuscule');
			EmundusHelperUpdate::insertTranslationsTag('JFIELD_PASSWORD_RULES_LOWERCASE', '%d lowercase letter(s)', 'override', 0, null, null, 'en-GB');

			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('dropfilesbtn'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' LIKE ' . $this->db->quote('editors-xtd'));
			$this->db->setQuery($query);
			$this->db->execute();

			$datas     = [
				'menutype'     => 'coordinatormenu',
				'title'        => 'Liste des balises',
				'alias'        => 'export-tags',
				'link'         => 'index.php?option=com_emundus&view=export_select_columns&layout=allprograms',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => [
					'menu_show' => 0
				]
			];
			$tags_menu = EmundusHelperUpdate::addJoomlaMenu($datas);

			EmundusHelperUpdate::installExtension('plg_task_checkgantrymode', 'checkgantrymode', null, 'plugin', 1, 'task');

			// Create scheduler task for gantry mode
			$execution_rules = [
				'rule-type'     => 'interval-days',
				'interval-days' => '1',
				'exec-day'      => date('d'),
				'exec-time'     => '23:00',
			];
			$cron_rules      = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];
			EmundusHelperUpdate::createSchedulerTask('Check Gantry Production Mode', 'plg_task_checkgantrymode_task_get', $execution_rules, $cron_rules);
			//


			// Create scheduler task for global checkin
			$execution_rules = [
				'rule-type'     => 'interval-minutes',
				'interval-minutes' => '5',
				'exec-day'      => date('d'),
				'exec-time'     => '23:00',
			];
			$cron_rules      = [
				'type' => 'interval',
				'exp'  => 'PT5M',
			];
			$params = [
				'delay' => 1
			];
			EmundusHelperUpdate::createSchedulerTask('Unlock checked elements', 'plg_task_globalcheckin_task_get', $execution_rules, $cron_rules, $params);
			//

			$query->clear()
				->update($this->db->quoteName('#__hikashop_config'))
				->set($this->db->quoteName('config_value') . ' = 0')
				->where($this->db->quoteName('config_namekey') . ' LIKE ' . $this->db->quote('dark_mode'));
			$this->db->setQuery($query);
			$this->db->execute();

			EmundusHelperUpdate::installExtension('plg_extension_emundus', 'emundus', null, 'plugin', 1, 'extension');

			EmundusHelperUpdate::addColumn('jos_emundus_setup_actions', 'description', 'VARCHAR', 255);
			$query->clear()
				->select('id,label,description')
				->from($this->db->quoteName('#__emundus_setup_actions'))
				->where($this->db->quoteName('description') . ' IS NULL')
				->orWhere($this->db->quoteName('description') . ' = ' . $this->db->quote(''));
			$this->db->setQuery($query);
			$actions = $this->db->loadObjectList();
			foreach ($actions as $action)
			{
				$action->description = $action->label . '_DESC';
				$this->db->updateObject('#__emundus_setup_actions', $action, 'id');
			}

			// Update jos_fabrik_form_sessions for emundus_fileupload
			EmundusHelperUpdate::addColumn('jos_fabrik_form_sessions', 'fnum', 'VARCHAR', 28, 1);
			$query = 'ALTER TABLE `jos_fabrik_form_sessions` MODIFY `referring_url` VARCHAR(255) NULL';
			$this->db->setQuery($query);
			$this->db->execute();

			$query = 'ALTER TABLE `jos_fabrik_form_sessions` MODIFY `last_page` INT(11) NULL';
			$this->db->setQuery($query);
			$this->db->execute();

			$query = 'ALTER TABLE `jos_fabrik_form_sessions` MODIFY `hash` VARCHAR(255) NULL';
			$this->db->setQuery($query);
			$this->db->execute();
			//

			$query = $this->db->getQuery(true);
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('element') . ' = ' . $this->db->quote('pkg_fabrikbase'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('pkg_fabrik'));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('editors-xtd'));
			$this->db->setQuery($query);
			$this->db->execute();

			if (!class_exists('EmundusAdministratorModelComments'))
			{
				require_once(JPATH_ROOT . '/administrator/components/com_emundus/models/comments.php');
			}
			$m_comments         = new \EmundusAdministratorModelComments();
			$comments_installed = $m_comments->install();
			if ($comments_installed)
			{
				EmundusHelperUpdate::displayMessage('Le nouveau composant pour les commentaires a été installé avec succès', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de l\'installation du nouveau module commentaire');
			}

			EmundusHelperUpdate::installExtension('plg_fabrik_element_emundus_geolocalisation', 'emundus_geolocalisation', null, 'plugin', 1, 'fabrik_element');

			EmundusHelperUpdate::addColumn('jos_emundus_setup_action_tag', 'ordering', 'INT', null, 1, 0);

			EmundusHelperUpdate::addColumn('jos_emundus_chatroom', 'status', 'INT');

			// Sharing files feature
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/src/SharingFilesInstall.php';
			$sharing_files_install   = new \scripts\src\SharingFilesInstall();
			$sharing_files_installed = $sharing_files_install->install();
			if ($sharing_files_installed['status'])
			{
				EmundusHelperUpdate::displayMessage('La fonctionnalité de partage de dossier a été installée avec succès', 'success');
			}
			else
			{
				EmundusHelperUpdate::displayMessage($sharing_files_installed['message'], 'error');
			}

			// Fix error layout
			EmundusHelperUpdate::insertTranslationsTag('JERROR_PAGE_NOT_FOUND', 'La page que vous cherchez semble introuvable...');
			EmundusHelperUpdate::insertTranslationsTag('JERROR_PAGE_NOT_FOUND', 'The page you are looking for cannot be found...', 'override', 0, null, null, 'en-GB');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_TITLE', 'Oups !');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ERROR_TITLE', 'Oops!', 'override', 0, null, null, 'en-GB');

			$error_directory      = JPATH_ROOT . '/templates/g5_helium/custom/config/_error';
			$error_page_directory = JPATH_ROOT . '/templates/g5_helium/custom/config/_error/page';

			$error_files = glob($error_directory . '/*');
			foreach ($error_files as $file)
			{
				if (is_file($file))
				{
					unlink($file);
				}
			}

			$error_page_files = glob($error_page_directory . '/*');
			foreach ($error_page_files as $file)
			{
				if (is_file($file))
				{
					unlink($file);
				}
			}

			$new_error_files = glob(JPATH_ROOT . '/.docker/installation/templates/g5_helium/custom/config/_error/*');
			foreach ($new_error_files as $file)
			{
				if (is_file($file))
				{
					copy($file, $error_directory . '/' . basename($file));
				}
			}

			$new_error_page_files = glob(JPATH_ROOT . '/.docker/installation/templates/g5_helium/custom/config/_error/page/*');
			foreach ($new_error_page_files as $file)
			{
				if (is_file($file))
				{
					copy($file, $error_page_directory . '/' . basename($file));
				}
			}
			//

			EmundusHelperUpdate::updateExtensionParam('log_forms_update', 0);

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_lists'));
			$this->db->setQuery($query);
			$fabrik_lists = $this->db->loadObjectList();

			foreach ($fabrik_lists as $fabrik_list)
			{
				$params = json_decode($fabrik_list->params, true);

				if (!empty($params['list_copy_image_name']))
				{
					$params['list_copy_image_name'] = 'content_copy';
					$fabrik_list->params            = json_encode($params);
					$this->db->updateObject('#__fabrik_lists', $fabrik_list, 'id');
				}
			}

			$old_values = [
				'fr-FR' => 'Copier ou déplacer le dossier',
				'en-GB' => 'Copy or move file',
			];
			$new_values = [
				'fr-FR' => 'Modifier la campagne',
				'en-GB' => 'Edit campaign',
			];
			EmundusHelperUpdate::updateOverrideTag('COPY_MOVE_FILE', $old_values, $new_values);

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote('ACTION'))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('copied'))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote('254'));
			$this->db->setQuery($query);
			$this->db->execute();

			$datas      = [
				'menutype'     => 'mainmenu',
				'title'        => 'Error',
				'alias'        => 'error',
				'link'         => 'index.php?option=com_emundus&view=error',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => [
					'menu_show'     => 0,
					'pageclass_sfx' => 'error-page'
				]
			];
			$error_menu = EmundusHelperUpdate::addJoomlaMenu($datas);

			$query->clear()
				->select('ff.id,ff.params')
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('fl.db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_setup_programmes'));
			$this->db->setQuery($query);
			$form_program = $this->db->loadObject();

			if (!empty($form_program->id))
			{
				$params                  = json_decode($form_program->params, true);
				$params['plugin_events'] = ['both'];
				$query->clear()
					->update($this->db->quoteName('#__fabrik_forms'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form_program->id));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			EmundusHelperUpdate::installExtension('System - OAuth 2 routing', 'oauth2', null, 'plugin', 1, 'system');
			EmundusHelperUpdate::enableEmundusPlugins('emundus_oauth2', 'authentication');

			EmundusHelperUpdate::installExtension('mod_emundus_oauth2', 'mod_emundus_oauth2', null, 'module', 1, '', '[]', true);
			EmundusHelperUpdate::createModule('External login', 'login', 'mod_emundus_oauth2', '{"layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}', 1, 1, 1, 0, 1);

			EmundusHelperUpdate::insertTranslationsTag('EMUNDUS_GROUPS', 'Groupe(s) de droits par défaut');
			EmundusHelperUpdate::insertTranslationsTag('EMUNDUS_GROUPS', 'Default rights group(s)', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::addColumn('jos_emundus_setup_profiles', 'emundus_groups', 'INT');

			$columns = [
				[
					'name'   => 'parent_id',
					'type'   => 'int',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'emundus_groups',
					'type'   => 'int',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'params',
					'type'   => 'varchar',
					'length' => 255,
					'null'   => 1,
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_profiles_repeat_emundus_groups', $columns);

			$aliases = [
				'mentions-legales',
				'politique-de-confidentialite-des-donnees',
				'gestion-des-cookies',
				'gestion-des-droits',
				'accessibilite'
			];

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('alias') . ' in (' . implode(',', $this->db->quote($aliases)) . ')');
			$this->db->setQuery($query);
			$rgpd_ids = $this->db->loadColumn();

			EmundusHelperUpdate::createModule('[GUEST] Back button - RGPD', 'content-top-a', 'mod_emundus_back', '{"layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0", "back_type":"homepage", "button_text":""}', 1, $rgpd_ids);

			$datas                 = [
				'menutype'     => 'mainmenu',
				'title'        => 'Création de compte',
				'alias'        => 'finalisation-creation-compte',
				'link'         => 'index.php?option=com_users&view=reset',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => [
					'menu_show' => 0
				]
			];
			$account_creation_menu = EmundusHelperUpdate::addJoomlaMenu($datas);
			if ($account_creation_menu['status'])
			{
				EmundusHelperUpdate::insertFalangTranslation(2, $account_creation_menu['id'], 'menu', 'title', 'Account creation');

				EmundusHelperUpdate::updateExtensionParam('account_creation_link', $account_creation_menu['id']);
			}

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_ACCOUNT_CREATION_PASSWORD', 'Création d\'un mot de passe');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_ACCOUNT_CREATION_PASSWORD', 'Password creation', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_RESET_COMPLETE_SUCCESS', 'Nouveau mot de passe défini avec succès. Vous pouvez maintenant vous connecter au site.');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_RESET_COMPLETE_SUCCESS', 'New password set successfully. You can now connect to the site.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_RESET_CONFIRM_FAILED', 'La réinitialisation de votre mot de passe est impossible car le code de vérification n\'est pas valide.');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_RESET_CONFIRM_FAILED', 'Your password reset confirmation failed because the verification code was invalid.', 'override', 0, null, null, 'en-GB');

			$query->clear()
				->select('id,plugin,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('profile'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('calc'))
				->where($this->db->quoteName('group_id') . ' = 640');
			$this->db->setQuery($query);
			$profile_calc = $this->db->loadObject();

			if (!empty($profile_calc))
			{
				$params                     = json_decode($profile_calc->params, true);
				$params['calc_calculation'] = '';
				$profile_calc->params       = json_encode($params);
				$profile_calc->plugin       = 'field';

				$this->db->updateObject('#__fabrik_elements', $profile_calc, 'id');
			}

			$query->clear()
				->select('id,rules')
				->from($this->db->quoteName('#__assets'))
				->where($this->db->quoteName('id') . ' = 1');
			$this->db->setQuery($query);
			$assets_rules = $this->db->loadObject();

			if (!empty($assets_rules->id))
			{
				$rules = json_decode($assets_rules->rules, true);

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__usergroups'));
				$this->db->setQuery($query);
				$usergroups = $this->db->loadColumn();

				// All usersgroups except public and guest can login on site
				foreach ($usergroups as $usergroup)
				{
					if (!in_array($usergroup, array_keys($rules['core.login.site'])) && !in_array($usergroup, [1, 9]))
					{
						$rules['core.login.site'][$usergroup] = 1;
					}
					elseif (in_array($usergroup, array_keys($rules['core.login.site'])) && in_array($usergroup, [1, 9]))
					{
						unset($rules['core.login.site'][$usergroup]);
					}
				}

				// Nobody (except sysadmin) can login on admin
				unset($rules['core.login.admin']);

				$assets_rules->rules = json_encode($rules);
				$this->db->updateObject('#__assets', $assets_rules, 'id');
			}

			// Create emundus_group element-
			$datas  = [
				'name'                 => 'emundus_groups',
				'group_id'             => $group_setup_profile,
				'plugin'               => 'databasejoin',
				'label'                => 'EMUNDUS_GROUPS',
				'show_in_list_summary' => 1
			];
			$params = [
				'database_join_display_type' => 'multilist',
				'join_db_name'               => 'jos_emundus_setup_groups',
				'join_key_column'            => 'id',
				'join_val_column'            => "label",
				'join_val_column_concat'     => "{thistable}.label",
				'advanced_behavior'          => 1
			];
			$eid    = EmundusHelperUpdate::addFabrikElement($datas, $params, false)['id'];
			if (!empty($eid))
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__fabrik_joins'))
					->where($this->db->quoteName('element_id') . ' = ' . $this->db->quote($eid));
				$this->db->setQuery($query);
				$join_id = $this->db->loadResult();

				if (empty($join_id))
				{
					$join_params = [
						'type' => 'repeatElement',
						'pk'   => $this->db->quoteName('jos_emundus_setup_profiles_repeat_emundus_groups') . '.' . $this->db->quoteName('id')
					];
					$insert      = [
						'element_id'      => $eid,
						'join_from_table' => 'jos_emundus_setup_profiles',
						'table_join'      => 'jos_emundus_setup_profiles_repeat_emundus_groups',
						'table_key'       => 'emundus_groups',
						'table_join_key'  => 'parent_id',
						'join_type'       => 'left',
						'group_id'        => 0,
						'params'          => json_encode($join_params)
					];
					$insert      = (object) $insert;
					$this->db->insertObject('#__fabrik_joins', $insert);
				}
			}

			$insert = [
				'parent_id'      => 1,
				'emundus_groups' => 1
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_profiles_repeat_emundus_groups', $insert);

			$insert = [
				'parent_id'      => 2,
				'emundus_groups' => 1
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_profiles_repeat_emundus_groups', $insert);

			$columns      = [
				['name' => 'campaign_id', 'type' => 'INT', 'null' => 0],
				['name' => 'lang_id', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_campaigns_languages_campaign_id_fk',
					'from_column'    => 'campaign_id',
					'ref_table'      => 'jos_emundus_setup_campaigns',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_campaigns_languages', $columns, $foreign_keys, 'Campaigns languages');

			$columns      = [
				['name' => 'program_id', 'type' => 'INT', 'null' => 0],
				['name' => 'lang_id', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_programs_languages_pid_fk',
					'from_column'    => 'program_id',
					'ref_table'      => 'jos_emundus_setup_programmes',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_programs_languages', $columns, $foreign_keys, 'Programs languages');

			EmundusHelperUpdate::installExtension('plg_actionlog_emundus', 'emundus', null, 'plugin', 1, 'actionlog');
			EmundusHelperUpdate::updateComponentParameter('com_actionlogs', 'ip_logging', 1);

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('web_security_request'));
			$this->db->setQuery($query);
			$web_security_email_id = $this->db->loadResult();

			if (empty($web_security_email_id))
			{
				$web_security_email = [
					'lbl'        => 'web_security_request',
					'subject'    => 'Demande de modification adresse web/SSL',
					'message'    => '<p>La plateforme <a href="[SITE_URL]" target="_blank">[SITE_URL]</a> souhaiterait apporter les modifications suivantes : [WEB_SECURITY_REQUESTS]</p>',
					'type'       => 1,
					'published'  => 0,
					'email_tmpl' => 1,
					'category'   => 'Système'
				];
				$web_security_email = (object) $web_security_email;
				$this->db->insertObject('#__emundus_setup_emails', $web_security_email);
			}

			// Create scheduler task for delete action logs
			$execution_rules = [
				'rule-type'     => 'interval-days',
				'interval-days' => '1',
				'exec-day'      => date('d'),
				'exec-time'     => '23:00',
			];
			$cron_rules      = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];
			$params          = [
				'logDeletePeriod' => '30',
			];
			EmundusHelperUpdate::createSchedulerTask('Delete old action logs', 'delete.actionlogs', $execution_rules, $cron_rules, $params);
			//

			// RGAA
			$default_g5_layout = JPATH_ROOT . '/templates/g5_helium/custom/config/default/layout.yaml';
			$default22_g5_layout = JPATH_ROOT . '/templates/g5_helium/custom/config/22/layout.yaml';
			$default24_g5_layout = JPATH_ROOT . '/templates/g5_helium/custom/config/24/layout.yaml';

			if(file_exists($default_g5_layout)) {
				$default_g5_layout_yaml = Yaml::parse(file_get_contents($default_g5_layout));
				$yaml_updated = false;
				if(empty($default_g5_layout_yaml['layout']['/header/']))
				{
					$default_g5_layout_yaml['layout'] = $this->change_key($default_g5_layout_yaml['layout'], '/navigation/', '/header/');
					$yaml_updated = true;
				}
				if(empty($default_g5_layout_yaml['structure']['header']))
				{
					$default_g5_layout_yaml['structure'] = $this->change_key($default_g5_layout_yaml['structure'], 'navigation', 'header');
					$yaml_updated = true;
				}
				if(empty($default_g5_layout_yaml['structure']['header']['attributes']['extra'])) {
					$default_g5_layout_yaml['structure']['header']['attributes']['extra'] = [];
					$default_g5_layout_yaml['structure']['header']['attributes']['extra'][0]['role'] = 'banner';
					$yaml_updated = true;
				}
				if(empty($default_g5_layout_yaml['structure']['main-mainbody']['attributes'])) {
					$default_g5_layout_yaml['structure']['main-mainbody']['attributes'] = [];
					$default_g5_layout_yaml['structure']['main-mainbody']['attributes']['extra'] = [];
					$default_g5_layout_yaml['structure']['main-mainbody']['attributes']['extra'][0]['role'] = 'main';
					$yaml_updated = true;
				}
				if(empty($default_g5_layout_yaml['structure']['footer']['attributes']['extra'])) {
					$default_g5_layout_yaml['structure']['footer']['attributes']['extra'] = [];
					$default_g5_layout_yaml['structure']['footer']['attributes']['extra'][0]['role'] = 'contentinfo';
					$yaml_updated = true;
				}
				if($yaml_updated) {
					$new_default_g5_layout = Yaml::dump($default_g5_layout_yaml, 10, 2);
					file_put_contents($default_g5_layout, $new_default_g5_layout);
				}

				$default22_g5_layout_yaml = Yaml::parse(file_get_contents($default22_g5_layout));
				$yaml_updated = false;
				if(empty($default22_g5_layout_yaml['layout']['header']))
				{
					$default22_g5_layout_yaml['layout'] = $this->change_key($default22_g5_layout_yaml['layout'], 'navigation', 'header');
				}
				if(empty($default22_g5_layout_yaml['structure']['header']))
				{
					$default22_g5_layout_yaml['structure'] = $this->change_key($default22_g5_layout_yaml['structure'], 'navigation', 'header');
					$yaml_updated = true;
				}
				if($yaml_updated) {
					$new_default22_g5_layout = Yaml::dump($default22_g5_layout_yaml, 10, 2);
					file_put_contents($default22_g5_layout, $new_default22_g5_layout);
				}

				$default24_g5_layout_yaml = Yaml::parse(file_get_contents($default24_g5_layout));
				$yaml_updated = false;
				if(empty($default24_g5_layout_yaml['layout']['header']))
				{
					$default24_g5_layout_yaml['layout'] = $this->change_key($default24_g5_layout_yaml['layout'], 'navigation', 'header');
					$yaml_updated = true;
				}
				if(empty($default24_g5_layout_yaml['structure']['header']))
				{
					$default24_g5_layout_yaml['structure'] = $this->change_key($default24_g5_layout_yaml['structure'], 'navigation', 'header');
					$yaml_updated = true;
				}
				if($yaml_updated) {
					$new_default24_g5_layout = Yaml::dump($default24_g5_layout_yaml, 10, 2);
					file_put_contents($default24_g5_layout, $new_default24_g5_layout);
				}
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where('alias = ' . $this->db->quote('forms'))
				->where('menutype = ' . $this->db->quote('onboardingmenu'));

			$this->db->setQuery($query);
			$forms_menu = $this->db->loadResult();

			$datas     = [
				'menutype'     => 'onboardingmenu',
				'title'        => 'Prévisulation de formulaire',
				'alias'        => 'preview',
				'link'         => 'index.php?option=com_fabrik&view=form',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
				'params'       => ['menu_show' => 0]
			];
			$preview_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $forms_menu);

			if ($preview_menu['status'])
			{
				EmundusHelperUpdate::displayMessage('Le menu de prévisualisation de formulaire a été créé', 'success');
			}
			else
			{
				throw new \Exception('Erreur lors de la création du menu de prévisualisation.');
			}

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}

	private function change_key( $array, $old_key, $new_key ) {

		if( ! array_key_exists( $old_key, $array ) )
			return $array;

		$keys = array_keys( $array );
		$keys[ array_search( $old_key, $keys ) ] = $new_key;

		return array_combine( $keys, $array );
	}
}