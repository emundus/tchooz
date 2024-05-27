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
use Joomla\CMS\Factory;

class Release2_0_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			$disabled = EmundusHelperUpdate::disableEmundusPlugins('webauthn');
			if($disabled) {
				EmundusHelperUpdate::displayMessage('Le plugin WebAuthn a été désactivé.', 'success');
			}

			$query->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('eval') . ' = 0')
				->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
				->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('fnum'))
				->where($this->db->quoteName('eval') . ' = 1');
			$this->db->setQuery($query);
			if($this->db->execute()) {
				EmundusHelperUpdate::displayMessage('Les valeurs par défaut des champs fnums ont été retirées, ces valeurs sont désormais pré-remplis via le plugin emundus_events.', 'success');
			}
			else {
				throw new \Exception('Erreur lors de la modification des champs fnums');
			}

			$column_added = EmundusHelperUpdate::addColumn('jos_emundus_setup_attachments', 'max_filesize', 'DOUBLE(6,2)');
			if($column_added['status']) {
				EmundusHelperUpdate::displayMessage('La colonne max_filesize a été ajoutée à la table jos_emundus_setup_attachments.', 'success');
			}
			else {
				throw new \Exception('Erreur lors de l\'ajout de la colonne max_filesize à la table jos_emundus_setup_attachments.');
			}

			// Install colorpicker
			EmundusHelperUpdate::installExtension('plg_fabrik_element_emundus_colorpicker','emundus_colorpicker','{"name":"plg_fabrik_element_emundus_colorpicker","type":"plugin","creationDate":"November 2023","author":"Media A-Team, Inc.","copyright":"Copyright (C) 2005-2023 Media A-Team, Inc. - All rights reserved.","authorEmail":"brice.hubinet@emundus.fr","authorUrl":"www.emundus.fr","version":"4.0Zeta","description":"PLG_ELEMENT_COLOURPICKER_DESCRIPTION","group":"","filename":"emundus_colorpicker"}','plugin',1,'fabrik_element');

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements','fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('fe.group_id').' = '.$this->db->quoteName('ffg.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists','fl').' ON '.$this->db->quoteName('ffg.form_id').' = '.$this->db->quoteName('fl.form_id'))
				->set($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote('emundus_colorpicker'))
				->set($this->db->quoteName('fe.params') . ' = ' . $this->db->quote('{"rgaa":"1","save_label":"1","show_in_rss_feed":"0","show_label_in_rss_feed":"0","use_as_rss_enclosure":"0","rollover":"","tipseval":"0","tiplocation":"top-left","labelindetails":"0","labelinlist":"0","comment":"","edit_access":"1","edit_access_user":"","view_access":"1","view_access_user":"","list_view_access":"1","encrypt":"0","store_in_db":"1","default_on_copy":"0","can_order":"0","alt_list_heading":"","custom_link":"","custom_link_target":"","custom_link_indetails":"1","use_as_row_class":"0","include_in_list_query":"1","always_render":"0","icon_folder":"0","icon_hovertext":"1","icon_file":"","icon_subdir":"","filter_length":"20","filter_access":"1","full_words_only":"0","filter_required":"0","filter_build_method":"0","filter_groupby":"text","inc_in_adv_search":"1","filter_class":"input-medium","filter_responsive_class":"","tablecss_header_class":"","tablecss_header":"","tablecss_cell_class":"","tablecss_cell":"","sum_on":"0","sum_label":"Sum","sum_access":"8","sum_split":"","avg_on":"0","avg_label":"Average","avg_access":"8","avg_round":"0","avg_split":"","median_on":"0","median_label":"Median","median_access":"8","median_split":"","count_on":"0","count_label":"Count","count_condition":"","count_access":"8","count_split":"","custom_calc_on":"0","custom_calc_label":"Custom","custom_calc_query":"","custom_calc_access":"1","custom_calc_split":"","custom_calc_php":"","validations":[]}'))
				->where($this->db->quoteName('fe.plugin') . ' LIKE ' . $this->db->quote('dropdown'))
				->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('class'))
				->where($this->db->quoteName('fl.db_table_name') . ' != ' . $this->db->quote('jos_emundus_setup_status'));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements','fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('fe.group_id').' = '.$this->db->quoteName('ffg.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists','fl').' ON '.$this->db->quoteName('ffg.form_id').' = '.$this->db->quoteName('fl.form_id'))
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

			if(!empty($setup_program)) {
				$params = json_decode($setup_program->params, true);
				$params['curl_code'] = 'JPluginHelper::importPlugin(\'emundus\', \'custom_event_handler\');\Joomla\CMS\Factory::getApplication()->triggerEvent(\'onCallEventHandler\', [\'onAfterProgramCreate\', [\'formModel\' => $formModel, \'data\' => $formModel->data]]);';
				$setup_program->params = json_encode($params);
				$this->db->updateObject('#__fabrik_forms', $setup_program, 'id');
			}

			// Add custom events
			$get_attachments_for_profile_event_added = EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAfterGetAttachmentsForProfile', 'category' => 'Files']
			]);
			if($get_attachments_for_profile_event_added) {
				EmundusHelperUpdate::displayMessage('L\'événement onAfterGetAttachmentsForProfile a été ajouté.', 'success');
			}
			else {
				throw new \Exception('Erreur lors de l\'ajout de l\'événement onAfterGetAttachmentsForProfile.');
			}
			//

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_EMAIL_PLACEHOLDER','exemple@domaine.com');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_EMAIL_PLACEHOLDER','example@domain.com', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::addColumn('jos_emundus_widgets_repeat_access', 'access_level', 'INT', 11);

			$datas = [
				'menutype'     => 'actions-users',
				'title'        => 'Exporter',
				'alias'        => 'export',
				'link'         => '',
				'type'         => 'heading',
				'component_id' => 0,
			];
			$export_menu = EmundusHelperUpdate::addJoomlaMenu($datas);

			if($export_menu['status'])
			{
				EmundusHelperUpdate::insertFalangTranslation(1, $export_menu['id'], 'menu', 'title', 'Export');

				$datas = [
					'menutype'     => 'actions-users',
					'title'        => 'Exporter vers Excel',
					'alias'        => 'export-excel',
					'type'         => 'url',
					'link'         => 'index.php?option=com_emundus&view=users&format=raw&layout=export&Itemid={Itemid}',
					'component_id' => 0,
					'note'         => '12|r|1|6'
				];
				$export_action = EmundusHelperUpdate::addJoomlaMenu($datas,$export_menu['id']);

				if($export_action['status'])
				{
					EmundusHelperUpdate::insertFalangTranslation(1, $export_action['id'], 'menu', 'title', 'Export to Excel');
				}
			}

			if (!class_exists('EmundusModelAdministratorCampaign')) {
				require_once(JPATH_ROOT . '/administrator/components/com_emundus/models/campaign.php');
			}
			$m_admin_campaign = new \EmundusModelAdministratorCampaign();
			if(!$m_admin_campaign->installCampaignMore()) {
				throw new \Exception('Erreur lors de l\'installation de la table jos_emundus_setup_campaigns_more');
			}

			EmundusHelperUpdate::installExtension('plg_emundus_system', 'emundus',null,'plugin',1,'system');
			EmundusHelperUpdate::installExtension('eMundus - Update profile', 'emundusupdateprofile',null,'plugin',1,'fabrik_form');

			if(!EmundusHelperUpdate::addColumn('jos_messages', 'email_cc', 'TEXT')['status']) {
				throw new \Exception('Erreur lors de l\'ajout de la colonne email_cc à la table jos_messages.');
			}

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('textarea'));
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach($elements as $element) {
				$params = json_decode($element->params, true);
				$params['wysiwyg_extra_buttons'] = 0;
				$element->params = json_encode($params);
				$this->db->updateObject('#__fabrik_elements', $element, 'id');
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('form_template') . ' = ' . $this->db->quote('emundus'))
				->set($this->db->quoteName('view_only_template') . ' = ' . $this->db->quote('emundus'))
				->where($this->db->quoteName('form_template') . ' IN (' . implode(',', $this->db->quote(['','bootstrap'])) . ')')
				->orWhere($this->db->quoteName('view_only_template') . ' IN (' . implode(',', $this->db->quote(['','bootstrap'])) . ')');
			$this->db->setQuery($query);
			if($this->db->execute()) {
				EmundusHelperUpdate::displayMessage('Les templates par défaut des formulaires ont été changés pour emundus.', 'success');
			}
			else {
				throw new \Exception('Erreur lors de la modification des templates des formulaires.');
			}

			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_CSV','Export');
			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_CSV','Export', 'override', 0, null, null, 'en-GB');

			// 1.38.10 : Set default params for checklist menu
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=checklist'));
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();

			foreach ($menus as $menu) {
				$params = json_decode($menu->params);
				$params->show_info_panel = 0;
				$params->show_info_legend = 1;
				$params->show_browse_button = 0;
				$params->show_nb_column = 1;

				$update = [
					'id' => $menu->id,
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

			if(!empty($group_program_detail)){
				EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'evaluation_form', 'INT',11,1);

				EmundusHelperUpdate::insertTranslationsTag('ELEMENT_PROGRAM_FORM_EVALUATION', 'Formulaire d\'évaluation');
				EmundusHelperUpdate::insertTranslationsTag('ELEMENT_PROGRAM_FORM_EVALUATION', 'Evaluation form', 'override', 0, null, null, 'en-GB');

				$datas = [
					'name' => 'evaluation_form',
					'group_id' => $group_program_detail,
					'plugin' => 'databasejoin',
					'label' => 'ELEMENT_PROGRAM_FORM_EVALUATION',
				];
				$params = [
					'join_db_name' => 'jos_fabrik_lists',
					'join_key_column' => 'form_id',
					'join_val_column' => "label",
					'join_val_column_concat' => "{thistable}.label",
					'database_join_where_sql' => "WHERE {thistable}.db_table_name = 'jos_emundus_evaluations'"
				];
				$eid = EmundusHelperUpdate::addFabrikElement($datas,$params,false)['id'];

				if(!empty($eid))
				{
					$datas = [
						'element_id' => $eid,
						'join_from_table' => '',
						'table_join' => 'jos_fabrik_lists',
						'table_key' => 'evaluation_form',
						'table_join_key' => 'form_id',
						'join_type' => 'left',
						'group_id' => $group_program_detail
					];
					$params = [
						'join-label' => 'label',
						'type' => 'element',
						'pk' => "`jos_fabrik_lists`.`id`"
					];
					EmundusHelperUpdate::addFabrikJoin($datas,$params);

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

					foreach ($programs as $program) {
						if(!empty($program['fabrik_group_id']))
						{
							$fabrik_groups = explode(',', $program['fabrik_group_id']);

							$query->clear()
								->select('form_id')
								->from($this->db->quoteName('#__fabrik_formgroup'))
								->where($this->db->quoteName('group_id') . ' IN (' . implode(',',$this->db->quote($fabrik_groups)) .')');
							$this->db->setQuery($query);
							$evaluation_form_id = $this->db->loadResult();

							if(!empty($evaluation_form_id))
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

			if(!empty($logrotation->extension_id))
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

			EmundusHelperUpdate::installExtension('plg_cron_logspurge','emunduslogsandmessagespurge','{"name":"plg_cron_logspurge","type":"plugin","creationDate":"May 2024","author":"eMundus","copyright":"Copyright (C) 2024 emundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"1.39.0","description":"PLG_CRON_LOGSPURGE_DESC","group":"","filename":"emunduslogsandmessagespurge"}','plugin',1,'fabrik_cron', '{"amount_time":"1","unit_time":"year","export_zip":"1", "amount_time_tmp":"1","unit_time_tmp":"week"}');

			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('jos_fabrik_cron'))
				->where($this->db->quoteName('plugin') . ' = ' . $this->db->quote('emunduslogsandmessagespurge'));

			$this->db->setQuery($query);
			$existing_cron = $this->db->loadResult();

			if ($existing_cron !== null)
			{
				echo "Plugin cron already created.";
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
					'label' => 'Logs and messages purge',
					'frequency' => 1,
					'unit' => 'day',
					'created' => date('0000-00-00 00:00:00'),
					'modified' => date('0000-00-00 00:00:00'),
					'checked_out_time' => date('0000-00-00 00:00:00'),
					'plugin' => 'emunduslogsandmessagespurge',
					'published' => 1,
					'lastrun' => date($last_four_hour),
					'params' => '{"connection":"1","table":"","cron_row_limit":"100","log":"0","log_email":"","require_qs":"0","require_qs_secret":"","cron_rungate":"1","cron_reschedule_manual":"0","amount_time":"1","unit_time":"year","export_zip":"1", "amount_time_tmp":"1","unit_time_tmp":"week"}'
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

			EmundusHelperUpdate::installExtension('plg_sampledata_emundus','emundus',null,'plugin',1,'sampledata');

			// 1.38.11
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_contact'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$this->db->execute();
			//

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();
			return $result;
		}


		return $result;
	}
}