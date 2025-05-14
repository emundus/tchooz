<?php
/**
 * eMundus Campaign model
 *
 * @package        Joomla
 * @subpackage    eMundus
 * @link        http://www.emundus.fr
 * @copyright    Copyright (C) 2008 - 2013 Décision Publique. All rights reserved.
 * @license        GNU/GPL
 * @author        Decision Publique - Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class EmundusAdministrationModelExpert extends JModelList
{
	private DatabaseInterface $db;

    function __construct()
    {
        parent::__construct();

		$this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    /**
     * Install tables and add sysadmin default menu
     * @return bool
     */
    public function install($debug = false): bool
    {
        $installed = false;
        $tasks = [];

        require_once (JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
        $app = $debug ? Factory::getApplication() : null;
		$query = $this->db->getQuery(true);

		try {
			EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'setup', 'TEXT');

			// Create table jos_emundus_setup_files_request
			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name'   => 'created_by',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name' => 'updated',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name'   => 'updated_by',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'campaign',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name' => 'elements',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'is_numeric_sign',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'attachment_to_upload',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'attachment_to_sign',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name' => 'attachment_model',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'must_validate',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'notify_email',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'motif_refus',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'notify_refus',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_setup_files_request', $columns)['status'];

			$columns = [
				[
					'name'   => 'parent_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'elements',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'params',
					'type' => 'TEXT',
					'null' => 1
				],
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_setup_files_request_repeat_elements', $columns)['status'];

			$columns = [
				[
					'name'   => 'parent_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'motif_refus',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name' => 'params',
					'type' => 'TEXT',
					'null' => 1
				],
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_setup_files_request_repeat_motif_refus', $columns)['status'];

			$columns = [
				[
					'name'   => 'parent_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'nom_candidat_expertise',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'data_expertise',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'status_expertise',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'motif_expertise',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'refused_expertise',
					'type'   => 'VARCHAR',
					'length' => 100,
					'null'   => 1
				],
				[
					'name' => 'your_files',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'fnum_expertise',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'file_info',
					'type' => 'TEXT',
					'null' => 1
				],
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_files_request_1614_repeat', $columns)['status'];

			// Enable expert plugin
			EmundusHelperUpdate::enableEmundusPlugins('emundusexpertagreement');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where($this->db->quoteName('db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_files_request'));
			$this->db->setQuery($query);
			$already_exist = $this->db->loadResult();

			if (empty($already_exist))
			{
				$expert_form = EmundusHelperUpdate::addFabrikForm(
					[
						'label'              => 'SETUP_FORM_EXPERTS',
						'form_template'      => 'emundus',
						'view_only_template' => 'emundus',
					]
				)['id'];
				$tasks[]     = !empty($expert_form);

				$expert_list = EmundusHelperUpdate::addFabrikList(
					[
						'label'         => 'Experts - Configuration',
						'db_table_name' => 'jos_emundus_setup_files_request',
						'form_id'       => $expert_form
					]
				)['id'];
				$tasks[]     = !empty($expert_list);

				$expert_group = EmundusHelperUpdate::addFabrikGroup(['name' => 'SETUP_FORM_EXPERTS'], ['repeat_group_show_first' => 1], 1, true)['id'];
				$tasks[]      = !empty($expert_group);

				EmundusHelperUpdate::joinFormGroup($expert_form, [$expert_group]);

				$datas   = [
					'name'                 => 'id',
					'group_id'             => $expert_group,
					'plugin'               => 'internalid',
					'label'                => 'id',
					'show_in_list_summary' => 0,
					'hidden'               => 1
				];
				$tasks[] = EmundusHelperUpdate::addFabrikElement($datas)['status'];

				$datas   = [
					'name'                 => 'date_time',
					'group_id'             => $expert_group,
					'plugin'               => 'jdate',
					'label'                => 'created',
					'show_in_list_summary' => 0,
					'hidden'               => 1
				];
				$tasks[] = EmundusHelperUpdate::addFabrikElement($datas)['status'];

				$datas   = [
					'name'                 => 'created_by',
					'group_id'             => $expert_group,
					'plugin'               => 'user',
					'label'                => 'created_by',
					'show_in_list_summary' => 0,
					'hidden'               => 1
				];
				$tasks[] = EmundusHelperUpdate::addFabrikElement($datas)['status'];

				$datas   = [
					'name'                 => 'campaign',
					'group_id'             => $expert_group,
					'plugin'               => 'databasejoin',
					'label'                => 'SETUP_FORM_EXPERTS_CAMPAIGN',
					'show_in_list_summary' => 1,
					'hidden'               => 0
				];
				$params  = [
					'database_join_display_type' => 'dropdown',
					'join_db_name'               => 'jos_emundus_setup_campaigns',
					'join_key_column'            => 'id',
					'join_val_column'            => 'label',
				];
				$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, $params)['status'];

				$datas       = [
					'name'                 => 'elements',
					'group_id'             => $expert_group,
					'plugin'               => 'databasejoin',
					'label'                => 'SETUP_FORM_EXPERTS_ELEMENTS',
					'show_in_list_summary' => 0,
					'hidden'               => 0
				];
				$params      = [
					'advanced_behavior'          => 1,
					'database_join_display_type' => 'multilist',
					'join_db_name'               => 'jos_fabrik_elements',
					'join_key_column'            => 'name',
					'join_val_column'            => 'label',
					'databasejoin_where_ajax'    => 1,
					'database_join_where_sql'    => "WHERE {thistable}.id IN (select fe.id
from jos_emundus_setup_campaigns as esc
left join jos_emundus_setup_profiles as esp on esp.id = esc.profile_id
left join jos_menu as m on m.menutype = esp.menutype
left join jos_fabrik_forms as ff on ff.id = SUBSTRING_INDEX(SUBSTRING(m.link, LOCATE(\"formid=\",m.link)+7, 4), \"&\", 1)
left join jos_fabrik_formgroup as fg on fg.form_id = ff.id
left join jos_fabrik_elements as fe on fe.group_id = fg.group_id
where esc.id = '{jos_emundus_setup_files_request___campaign_raw}' and m.published = 1 and ff.id is not null and fe.hidden = 0)",
					'dabase_join_label_eval'     => '$db = JFactory::getDbo();
$query = $db->getQuery(true);

$query->select(\'fg.label\')
  ->from($db->quoteName(\'#__fabrik_elements\', \'fe\'))
	->leftJoin($db->quoteName(\'#__fabrik_groups\',\'fg\').\' ON \'.$db->quoteName(\'fg.id\').\' = \'.$db->quoteName(\'fe.group_id\'))
			->where($db->quoteName(\'fe.name\') . \' = \' . $db->quote($opt->value));
$db->setQuery($query);
$label = $db->loadResult();

if(!empty($label))
{
	return \'[\' . JText::_($label) . \'] - \' . JText::_($opt->text);
} else {
	return JText::_($opt->text);
}'
				];
				$elements_id = EmundusHelperUpdate::addFabrikElement($datas, $params)['id'];
				$tasks[]     = !empty($elements_id);

				$datas   = [
					'list_id'         => $expert_list,
					'element_id'      => $elements_id,
					'join_from_table' => 'jos_emundus_setup_files_request',
					'table_join'      => 'jos_emundus_setup_files_request_repeat_elements',
					'table_key'       => 'elements',
					'table_join_key'  => 'parent_id',
					'join_type'       => 'left',
					'group_id'        => 0
				];
				$params  = [
					'type' => 'repeatElement',
					'pk'   => "`jos_emundus_setup_files_request_repeat_elements`.`id`"
				];
				$tasks[] = EmundusHelperUpdate::addFabrikJoin($datas, $params);

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('adminmenu'))
					->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_fabrik&view=form&formid=' . $expert_form));
				$this->db->setQuery($query);
				$menu_id = $this->db->loadResult();

				if (empty($menu_id))
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('adminmenu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('modules-complementaires'));
					$this->db->setQuery($query);
					$parent_id = $this->db->loadResult();

					$datas   = [
						'title'        => 'Experts - Configuration',
						'menutype'     => 'adminmenu',
						'type'         => 'component',
						'link'         => 'index.php?option=com_fabrik&view=list&listid=' . $expert_list,
						'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
					];
					$menu_id = EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 1)['id'];
				}
				$tasks[] = !empty($menu_id);
			}

			$expert_parameter = ComponentHelper::getParams('com_emundus')->get('expert_fabrikformid');
			if (!empty((int) $expert_parameter))
			{
				EmundusHelperUpdate::updateComponentParameter('com_emundus', 'expert_fabrikformid', '{"accepted":' . $expert_parameter . ', "refused":328, "agreement": 0}');
			}

			// Update plugin of expert form
			$query->clear()
				->select('ff.id,ff.params,fl.id as list_id')
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('ff.id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->where($this->db->quoteName('ff.label') . ' = ' . $this->db->quote('SETUP_REQUEST_CONFIDENTIALITY_AGREEMENT'));
			$this->db->setQuery($query);
			$expert_form_request = $this->db->loadObject();

			if (!empty($expert_form_request->id))
			{
				$params                             = json_decode($expert_form_request->params, true);
				$params['plugins']                  = ['emundusexpertagreement'];
				$params['plugin_description']       = ['Invitation experts'];
				$params['plugin_state']             = ['1'];
				$params['plugin_events']            = ['both'];
				$params['plugin_locations']         = ['both'];
				$params['pick_fnums']               = 1;
				$params['keep_accepted_fnums']      = 0;
				$params['status_input']             = 'status_expertise';
				$params['accepted_value']           = 1;
				$params['firstname_input']          = 'jos_emundus_files_request___firstname';
				$params['lastname_input']           = 'jos_emundus_files_request___lastname';
				$params['fnum_input']               = 'fnum_expertise';
				$params['onBeforeLoadVerification'] = 1;
				$params['send_email_accept']        = 1;
				unset($params['curl_code']);
				unset($params['form_php_file']);
				unset($params['only_process_curl']);
				unset($params['form_php_require_once']);

				$expert_form_request->params = json_encode($params);
				$tasks[]                     = $this->db->updateObject('#__fabrik_forms', $expert_form_request, 'id');

				// Add missing fabrik elements
				$query->clear()
					->select('group_id')
					->from($this->db->quoteName('#__fabrik_formgroup'))
					->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($expert_form_request->id));
				$this->db->setQuery($query);
				$group = $this->db->loadResult();

				$datas   = [
					'name'                 => 'setup',
					'group_id'             => $group,
					'plugin'               => 'calc',
					'label'                => 'Configuration de l\'expertise',
					'show_in_list_summary' => 0,
					'hidden'               => 1,
					'published'            => 0
				];
				$params  = [
					'calc_ajax'        => 0,
					'calc_on_load'     => 1,
					'calc_calculation' => 'use Joomla\CMS\Factory;

$fnum = \'{jos_emundus_files_request_1614_repeat___fnum_expertise_raw}\';
$setup = new stdClass();

if(!empty($fnum))
{
  $db = Factory::getDbo();
	$query = $db->getQuery(true);

	require_once JPATH_SITE . \'/components/com_emundus/models/expert.php\';
	$m_expert = new EmundusModelExpert();

	$setup = $m_expert->getSetupByFnum($fnum);
}

return json_encode($setup);'
				];
				$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, $params)['status'];

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__fabrik_joins'))
					->where($this->db->quoteName('table_join') . ' = ' . $this->db->quote('jos_emundus_files_request_1614_repeat'));
				$this->db->setQuery($query);
				$join_id = $this->db->loadResult();

				if (empty($join_id))
				{
					$repeat_group = EmundusHelperUpdate::addFabrikGroup(['name' => 'Dossiers à expertiser', 'is_join' => 1], ['repeat_group_show_first' => 1, 'repeat_group_button' => 1, 'repeat_add_access' => 10], 0, true)['id'];
					$tasks[]      = !empty($repeat_group);

					if(!empty($repeat_group))
					{
						EmundusHelperUpdate::joinFormGroup($expert_form_request->id, [$repeat_group]);
						$datas   = [
							'list_id'         => $expert_form_request->list_id,
							'element_id'      => 0,
							'join_from_table' => 'jos_emundus_files_request',
							'table_join'      => 'jos_emundus_files_request_1614_repeat',
							'table_key'       => 'id',
							'table_join_key'  => 'parent_id',
							'join_type'       => 'left',
							'group_id'        => $repeat_group
						];
						$params  = [
							'type' => 'group',
							'pk' => "`jos_emundus_files_request_1614_repeat`.`id`"
						];
						$join_id = EmundusHelperUpdate::addFabrikJoin($datas, $params);
						$tasks[] = !empty($join_id);

						$datas   = [
							'name'                 => 'id',
							'group_id'             => $repeat_group,
							'plugin'               => 'internalid',
							'label'                => 'id',
							'show_in_list_summary' => 0,
							'hidden'               => 1
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, [],false)['status'];

						$datas   = [
							'name'                 => 'parent_id',
							'group_id'             => $repeat_group,
							'plugin'               => 'field',
							'label'                => 'parent_id',
							'show_in_list_summary' => 0,
							'hidden'               => 1
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, [], false)['status'];

						$datas   = [
							'name'                 => 'fnum_expertise',
							'group_id'             => $repeat_group,
							'plugin'               => 'field',
							'label'                => 'FNUM',
							'show_in_list_summary' => 0,
							'hidden'               => 0
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas)['status'];

						$datas   = [
							'name'                 => 'nom_candidat_expertise ',
							'group_id'             => $repeat_group,
							'plugin'               => 'field',
							'label'                => 'APPLICANT',
							'show_in_list_summary' => 0,
							'hidden'               => 0
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas)['status'];

						$datas   = [
							'name'                 => 'file_info',
							'group_id'             => $repeat_group,
							'plugin'               => 'calc',
							'label'                => 'EXPERT_INFORMATIONS_DOSS',
							'show_in_list_summary' => 0,
							'hidden'               => 0
						];
						$params  = [
							'calc_calculation' => 'use Joomla\CMS\Factory;

$fnum = \'{jos_emundus_files_request_1614_repeat___fnum_expertise_raw}\';

$elements = [];
$html = \'\';

if(!empty($fnum))
{
  $db = Factory::getDbo();
	$query = $db->getQuery(true);

	require_once JPATH_SITE . \'/components/com_emundus/models/expert.php\';
	require_once JPATH_SITE . \'/components/com_emundus/models/emails.php\';
	$m_expert = new EmundusModelExpert();
	$m_emails = new EmundusModelEmails();

	$setup = $m_expert->getSetupByFnum($fnum);

	if(!empty($setup->elements)) {
		$application_elements = explode(\',\',$setup->elements);

		foreach ($application_elements as $application_element) {
			$query->clear()
				->select(\'id,label\')
				->from($db->quoteName(\'#__fabrik_elements\'))
				->where($db->quoteName(\'name\') . \' = \' . $db->quote($application_element));
			$db->setQuery($query);
			$element = $db->loadObject();

			$element->value = $m_emails->setTagsFabrik(\'${\'.$element->id.\'}\',[$fnum]);
      
			$elements[] = $element;
		}

			}
		}

foreach ($elements as $element) {
	$html .= \'<div class="fabrikElementReadOnly mb-4"><label><b>\'.JText::_($element->label) . \'</b></label><p>\' . $element->value . \'</p></div>\';
}

return $html;',
							'calc_ajax'        => 1,
							'calc_on_load'     => 1,
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, $params, false)['status'];

						$datas   = [
							'name'                 => 'status_expertise ',
							'group_id'             => $repeat_group,
							'plugin'               => 'radiobutton',
							'label'                => 'ACCEPT_EXPERT_REQUEST',
							'show_in_list_summary' => 0,
							'hidden'               => 0
						];
						$params  = [
							'sub_options'     => [
								'sub_values' => [
									1,
									2
								],
								'sub_labels' => [
									'JYES',
									'JNO'
								]
							],
							'options_per_row' => 2
						];
						$tasks[] = EmundusHelperUpdate::addFabrikElement($datas, $params)['status'];
					}
				}
			}
		} catch (Exception $e) {
			$tasks[] = false;
		}

        if (!in_array(false, $tasks)) {
            $installed = true;
        }

        return $installed;
    }
}