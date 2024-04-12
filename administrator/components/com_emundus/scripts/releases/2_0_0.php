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