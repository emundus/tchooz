<?php
/**
 * @package            Joomla
 * @subpackage         eMundus
 * @link               http://www.emundus.fr
 * @copyright          Copyright (C) 2018 eMundus. All rights reserved.
 * @license            GNU/GPL
 * @author             Benjamin Rivalland - Yoan Durand
 */

// No direct access

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Joomla\CMS\Language\Text;
use Dompdf\Dompdf;
use Dompdf\Options;

defined('_JEXEC') or die('Restricted access');
define('R_MD5_MATCH', '/^[a-f0-9]{32}$/i');

jimport('joomla.application.component.model');
require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/list.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
require_once(JPATH_SITE . '/components/com_emundus/models/files.php');

use Joomla\CMS\Factory;
use Tchooz\Entities\Emails\TagEntity;
use Tchooz\Enums\Emails\TagType;
use Tchooz\Traits\TraitDispatcher;

class EmundusModelEvaluation extends JModelList
{
	private $app;
	private $db;

	private $_total = null;
	private $_pagination = null;
	private $_applicants = array();
	private $subquery = array();
	private $_elements_default;
	private $_elements;
	private $_files;
	public $fnum_assoc;
	public $code;

	private $use_module_filters = false;

	use TraitDispatcher;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	public function __construct()
	{

		parent::__construct();

		if (!class_exists('EmundusModelFiles'))
		{
			include_once(JPATH_BASE . '/components/com_emundus/models/files.php');
		}

		$this->_files = new EmundusModelFiles;

		$this->app = Factory::getApplication();
		$this->db     = Factory::getContainer()->get('DatabaseDriver');
		$current_user = $this->app->getIdentity();
		$session      = $this->app->getSession();

		$menu = method_exists($this->app, 'getMenu') ? $this->app->getMenu() : null;
		if (!empty($menu))
		{
			$current_menu = $menu->getActive();
			if (empty($current_menu))
			{
				return false;
			}
		}
		else
		{
			return false;
		}

		$menu_params              = $menu->getParams($current_menu->id);
		$this->use_module_filters = boolval($menu_params->get('em_use_module_for_filters', false));

		if (!$session->has('filter_order'))
		{
			$session->set('filter_order', 'jos_emundus_campaign_candidature.fnum');
			$session->set('filter_order_Dir', 'desc');
		}

		if (!$session->has('limit'))
		{
			$limit      = $this->app->get('list_limit');
			$limitstart = 0;
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$session->set('limit', $limit);
			$session->set('limitstart', $limitstart);
		}
		else
		{
			$limit      = intval($session->get('limit'));
			$limitstart = intval($session->get('limitstart'));
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$session->set('limit', $limit);
			$session->set('limitstart', $limitstart);
		}

		$col_elt   = $this->getState('elements');
		$col_other = $this->getState('elements_other');

		$this->elements_id = $menu_params->get('em_elements_id');
		$this->elements_id = rtrim($this->elements_id, ',');

		// get evaluation element
		$show_in_list_summary = 1;
		$hidden               = 0;
		$fnums   = $this->app->input->getString('cfnums', null);
		$elements_eval = $this->getEvaluationElements($show_in_list_summary, $hidden, $fnums);
		if (is_array($elements_eval) && count($elements_eval))
		{
			$this->elements_id .= ',' . implode(',', $elements_eval);
		}

		if ($session->has('adv_cols'))
		{
			$adv = $session->get('adv_cols');
			if (!empty($adv))
			{
				$this->elements_id .= ',' . implode(',', $adv);
			}
		}
		$this->elements_values = explode(',', $menu_params->get('em_elements_values'));

		$this->_elements_default = array();

		if (!is_null($this->elements_id))
		{
			$this->_elements = @EmundusHelperFiles::getElementsName($this->elements_id);
		}

		if (!empty($this->_elements))
		{
			foreach ($this->_elements as $def_elmt)
			{
				$group_params = json_decode($def_elmt->group_attribs);

				$already_joined_tables = [
					'jecc' => 'jos_emundus_campaign_candidature',
					'ss'   => 'jos_emundus_setup_status',
					'esc'  => 'jos_emundus_setup_campaigns',
					'escm'  => 'jos_emundus_setup_campaigns_more',
					'sp'   => 'jos_emundus_setup_programmes',
					'u'    => 'jos_users',
					'eu'   => 'jos_emundus_users',
					'eta'  => 'jos_emundus_tag_assoc',
				];
				foreach ($already_joined_tables as $alias => $table)
				{
					if ($def_elmt->tab_name === $table)
					{
						$def_elmt->tab_name = $alias;
					}

					if ($def_elmt->join_from_table === $table)
					{
						$def_elmt->join_from_table = $alias;
					}
				}

				if (in_array($def_elmt->element_plugin,['date','jdate']))
				{
					if ($group_params->repeat_group_button == 1)
					{
						$this->_elements_default[] = '(
                                                        SELECT  GROUP_CONCAT(DATE_FORMAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ', "%d/%m/%Y %H:%i:%m") SEPARATOR ", ")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$this->_elements_default[] = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
					}
				}
				elseif ($def_elmt->element_plugin == 'databasejoin')
				{
					$attribs                = json_decode($def_elmt->element_attribs);
					$join_val_column_concat = str_replace('{thistable}', $attribs->join_db_name, $attribs->join_val_column_concat);
					$join_val_column_concat = str_replace('{shortlang}', substr(JFactory::getLanguage()->getTag(), 0, 2), $join_val_column_concat);
					$column                 = (!empty($join_val_column_concat) && $join_val_column_concat != '') ? 'CONCAT(' . $join_val_column_concat . ')' : $attribs->join_val_column;

					// Check if the db table has a published column. So we don't get the unpublished value
					$this->db->setQuery("SHOW COLUMNS FROM $attribs->join_db_name LIKE 'published'");
					$publish_query = ($this->db->loadResult()) ? " AND $attribs->join_db_name.published = 1 " : '';

					if ($group_params->repeat_group_button == 1)
					{
						$query = '(
                                    select GROUP_CONCAT(' . $column . ' SEPARATOR ", ")
                                    from ' . $attribs->join_db_name . '
                                    where ' . $attribs->join_db_name . '.' . $attribs->join_key_column . ' IN
                                        ( select ' . $def_elmt->table_join . '.' . $def_elmt->element_name . '
                                          from ' . $def_elmt->table_join . '
                                          where ' . $def_elmt->table_join . '.' . $def_elmt->table_join_key . '=' . $def_elmt->join_from_table . '.id' . '
                                        )
                                    ' . $publish_query . '
                                  ) AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						if ($attribs->database_join_display_type == "checkbox")
						{

							$t     = $def_elmt->tab_name . '_repeat_' . $def_elmt->element_name;
							$query = '(
                                SELECT GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ' SEPARATOR ", ")
                                FROM ' . $def_elmt->table_join . '
                                WHERE ' . $def_elmt->table_join . '.parent_id=' . $def_elmt->tab_name . '.id
                                ' . $publish_query . '
                              ) AS `' . $t . '`';
						}
						else if ($attribs->database_join_display_type == 'multilist')
						{
							$t     = $def_elmt->tab_name . '_repeat_' . $def_elmt->element_name;
							$query = '(
                                select DISTINCT ' . $column . '
                                from ' . $attribs->join_db_name . '
                                where `' . $attribs->join_db_name . '`.`' . $attribs->join_key_column . '`=`' . $t . '`.`' . $def_elmt->element_name . '`
                                ' . $publish_query . '
                            ) AS `' . $t . '`';
						}
						else
						{
							$query = '(
                                select DISTINCT ' . $column . '
                                from ' . $attribs->join_db_name . '
                                where `' . $attribs->join_db_name . '`.`' . $attribs->join_key_column . '`=`' . $def_elmt->tab_name . '`.`' . $def_elmt->element_name . '`
                                ' . $publish_query . '
                                ) AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
						}
					}
					$this->_elements_default[] = $query;
				}
				elseif ($def_elmt->element_plugin == 'cascadingdropdown')
				{
					$attribs                 = json_decode($def_elmt->element_attribs);
					$cascadingdropdown_id    = $attribs->cascadingdropdown_id;
					$r1                      = explode('___', $cascadingdropdown_id);
					$cascadingdropdown_label = $attribs->cascadingdropdown_label;
					$r2                      = explode('___', $cascadingdropdown_label);
					$select                  = !empty($attribs->cascadingdropdown_label_concat) ? "CONCAT(" . $attribs->cascadingdropdown_label_concat . ")" : $r2[1];
					$from                    = $r2[0];
					$where                   = $r1[1];

					if (@$group_params->repeat_group_button == 1)
					{
						$query = '(
                                    select GROUP_CONCAT(' . $select . ' SEPARATOR ", ")
                                    from ' . $from . '
                                    where ' . $where . ' IN
                                        ( select ' . $def_elmt->table_join . '.' . $def_elmt->element_name . '
                                          from ' . $def_elmt->table_join . '
                                          where ' . $def_elmt->table_join . '.parent_id=' . $def_elmt->tab_name . '.id
                                        )
                                  ) AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$query = "(SELECT DISTINCT(" . $select . ") FROM " . $from . " WHERE " . $where . "=" . $def_elmt->element_name . " LIMIT 0,1) AS `" . $def_elmt->tab_name . "___" . $def_elmt->element_name . "`";
					}

					$query                     = preg_replace('#{thistable}#', $from, $query);
					$query                     = preg_replace('#{my->id}#', $current_user->id, $query);
					$query                     = preg_replace('{shortlang}', substr(JFactory::getLanguage()->getTag(), 0, 2), $query);
					$this->_elements_default[] = $query;
				}
				elseif ($def_elmt->element_plugin == 'dropdown' || $def_elmt->element_plugin == 'checkbox')
				{
					if ($group_params->repeat_group_button == 1)
					{
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value)
						{
							$label = addslashes($element_attribs->sub_options->sub_labels[$key]);
							if ($label === 'PLEASE_SELECT')
							{
								$label = '';
							}

							$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_($label) . '")';
						}
						$select = str_replace($def_elmt->tab_name . '.' . $def_elmt->element_name, 'GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ' SEPARATOR ", ")', $select);

						$this->_elements_default[] = '(
                                    SELECT ' . $select . '
                                    FROM ' . $def_elmt->table_join . '
                                    WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                  ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value)
						{
							$label = addslashes($element_attribs->sub_options->sub_labels[$key]);
							// If the label is PLEASE_SELECT, we don't want to display it
							if ($label === 'PLEASE_SELECT')
							{
								$label = '';
							}
							$select = 'REPLACE(' . $select . ', "' . $value . '", "' .
								Text::_($label) . '")';
						}
						$this->_elements_default[] = $select . ' AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
				elseif ($def_elmt->element_plugin == 'radiobutton')
				{
					if (!empty($group_params->repeat_group_button) && $group_params->repeat_group_button == 1)
					{
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value)
						{
							$label = addslashes($element_attribs->sub_options->sub_labels[$key]);
							if ($label === 'PLEASE_SELECT')
							{
								$label = '';
							}
							$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_($label) . '")';
						}
						$select                    = str_replace($def_elmt->tab_name . '.' . $def_elmt->element_name, 'GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ' SEPARATOR ", ")', $select);
						$this->_elements_default[] = '(
                                    SELECT ' . $select . '
                                    FROM ' . $def_elmt->table_join . '
                                    WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                  ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$element_attribs = json_decode($def_elmt->element_attribs);

						$element_replacement = $def_elmt->tab_name . '___' . $def_elmt->element_name;
						$select              = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS ' . $this->db->quote($element_replacement) . ', CASE ';
						foreach ($element_attribs->sub_options->sub_values as $key => $value)
						{
							$label = addslashes($element_attribs->sub_options->sub_labels[$key]);
							if ($label === 'PLEASE_SELECT')
							{
								$label = '';
							}
							$select .= ' WHEN ' . $def_elmt->tab_name . '.' . $def_elmt->element_name . ' = ' . $this->db->quote($value) . ' THEN ' . $this->db->quote(Text::_($label));
						}
						$select .= ' ELSE ' . $def_elmt->tab_name . '.' . $def_elmt->element_name;
						$select .= ' END AS ' . $this->db->quote($element_replacement);

						$this->_elements_default[] = $select;
					}
				}
				elseif ($def_elmt->element_plugin == 'yesno')
				{
					if ($group_params->repeat_group_button == 1)
					{
						$this->_elements_default[] = '(
                                                        SELECT REPLACE(REPLACE(GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . '  SEPARATOR ", "), "0", "' . JText::_('JNO') . '"), "1", "' . JText::_('JYES') . '")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$this->_elements_default[] = 'REPLACE(REPLACE(' . $def_elmt->tab_name . '.' . $def_elmt->element_name . ', "0", "' . JText::_('JNO') . '"), "1", "' . JText::_('JYES') . '")  AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
				else
				{
					if ($group_params->repeat_group_button == 1)
					{
						$this->_elements_default[] = '(
                                                        SELECT  GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . '  SEPARATOR ", ")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else
					{
						$this->_elements_default[] = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
			}
		}

		if (empty($col_elt))
		{
			$col_elt = array();
		}
		if (empty($col_other))
		{
			$col_other = array();
		}
		if (empty(@$this->_elements_default_name))
		{
			$this->_elements_default_name = array();
		}

		$this->col = array_merge($col_elt, $col_other, $this->_elements_default_name);

		if (count($this->col) > 0)
		{

			$elements_names = '"' . implode('", "', $this->col) . '"';

			$h_list  = new EmundusHelperList;
			$h_files = new EmundusHelperFiles();

			$result = $h_list->getElementsDetails($elements_names);
			$result = $h_files->insertValuesInQueryResult($result, array("sub_values", "sub_labels"));

			$this->details = new stdClass();
			foreach ($result as $res)
			{
				$this->details->{$res->tab_name . '___' . $res->element_name} = array('element_id' => $res->element_id,
				                                                                      'plugin'     => $res->element_plugin,
				                                                                      'attribs'    => $res->params,
				                                                                      'sub_values' => $res->sub_values,
				                                                                      'sub_labels' => $res->sub_labels,
				                                                                      'group_by'   => $res->tab_group_by);
			}
		}
	}

	public function getElementsVar()
	{
		return $this->_elements;
	}

	/**
	 * Get list of evaluation element
	 *
	 * @param int $show_in_list_summary in Fabrik List
	 * @param int $hidden get hidden elements
	 *
	 * @return    array list of Fabrik element ID used in evaluation form
	 **@throws Exception
	 */
	public function getEvaluationElements($show_in_list_summary = 1, $hidden = 0, $fnums = null)
	{
		$elements_id = [];
		$session = $this->app->getSession();
		$h_files = new EmundusHelperFiles;

		if ($session->has('filt_params'))
		{
			$element_id  = array();
			$filt_params = $session->get('filt_params');

			if (is_array($filt_params['programme']) && count(@$filt_params['programme']) > 0)
			{
				foreach ($filt_params['programme'] as $value)
				{
					if ($value !== '%')
					{
						$groups = $this->getGroupsEvalByProgramme($value);
						if (empty($groups))
						{
							$eval_elt_list = array();
						}
						else
						{
							$eval_elt_list = $this->getElementsByGroups($groups, $show_in_list_summary, $hidden);
							if (count($eval_elt_list) > 0)
							{
								foreach ($eval_elt_list as $eel)
								{
									if (!empty($eel->element_id))
									{
										$elements_id[] = $eel->element_id;
									}
								}
							}
						}
					}
				}
			}
			if (!empty($filt_params['campaign']) && is_array($filt_params['campaign']) && count(@$filt_params['campaign']) > 0)
			{
				foreach ($filt_params['campaign'] as $value)
				{
					if ($value !== '%')
					{
						$campaign = $h_files->getCampaignByID($value);
						$groups   = $this->getGroupsEvalByProgramme($campaign['training']);
						if (empty($groups))
						{
							$eval_elt_list = array();
						}
						else
						{
							$eval_elt_list = $this->getElementsByGroups($groups, $show_in_list_summary, $hidden);
							if (is_array($eval_elt_list) && count($eval_elt_list) > 0)
							{
								foreach ($eval_elt_list as $eel)
								{
									if (!empty($eel->element_id))
									{
										$elements_id[] = $eel->element_id;
									}
								}
							}
						}
					}
				}
			}
		}

		$applied_filters = $session->get('em-applied-filters', []);
		if (!empty($applied_filters)) {
			// check if filter workflow_steps has a value
			foreach ($applied_filters as $filter) {
				if ($filter['uid'] == 'workflow_steps') {
					if (!empty($filter['value'])) {
						$step_ids = !is_array($filter['value']) ? [$filter['value']] : $filter['value'];

						require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
						$m_workflow = new EmundusModelWorkflow();

						foreach($step_ids as $step_id) {
							$step_data = $m_workflow->getStepData($step_id);

							if (!empty($step_data->form_id))
							{
								$query = $this->db->createQuery();
								$query->clear()
									->select('group_id')
									->from($this->db->quoteName('#__fabrik_formgroup', 'ffg'))
									->leftJoin($this->db->quoteName('#__fabrik_groups', 'fg') . ' ON ffg.group_id = fg.id')
									->where('form_id = ' . $step_data->form_id);

								$this->db->setQuery($query);
								$groups = $this->db->loadColumn();

								if (!empty($groups))
								{
									$groups = implode(',', $groups);
									$eval_elt_list = $this->getElementsByGroups($groups, $show_in_list_summary, $hidden);
									if (is_array($eval_elt_list) && count($eval_elt_list) > 0)
									{
										foreach ($eval_elt_list as $eel)
										{
											if (!empty($eel->element_id))
											{
												$elements_id[] = $eel->element_id;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return $elements_id;
	}

	public function getEvaluationStepsElementsName($show_in_list_summary = 1, $hidden = 0, $codes = array()) {
		$elements = [];

		if (!empty($codes)) {
			$h_list  = new EmundusHelperList;
			$codes = array_unique($codes);
			$query = $this->db->getQuery(true);

			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_SITE . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new EmundusModelWorkflow();

			foreach($codes as $code) {
				$query->clear()
					->select('id')
					->from('#__emundus_setup_programmes')
					->where('code = ' . $this->db->quote($code));

				$this->db->setQuery($query);
				$programme_id = $this->db->loadResult();

				if (!empty($programme_id)) {
					$steps = $m_workflow->getEvaluatorStepsByProgram($programme_id);

					foreach($steps as $step) {
						if (EmundusHelperAccess::asAccessAction($step->action_id, 'r', Factory::getApplication()->getIdentity()->id)) {
							$query->clear()
								->select('group_id')
								->from('#__fabrik_formgroup')
								->where('form_id = ' . $step->form_id);

							$this->db->setQuery($query);
							$group_ids = $this->db->loadColumn();

							if (!empty($group_ids)) {
								$group_elements = $this->getElementsByGroups(implode(',', $group_ids), $show_in_list_summary, $hidden, ['panel', 'display']);

								foreach ($group_elements as $group_element)
								{
									if (!empty($group_element->element_id))
									{
										$step_element = $h_list->getElementsDetailsByID($group_element->element_id)[0];
										if(in_array($step_element->element_plugin, ['panel', 'display'])) {
											continue;
										}

										$step_element->table_label = Text::_($step_element->table_label);
										$step_element->label =  Text::_($step->label);
										$step_element->form_id = $step->form_id;
										$step_element->step_id = $step->id;
										$step_element->step_label =  Text::_($step->label);
										$elements[] = $step_element;
									}
								}
							}
						}
					}
				}
			}
		}

		return $elements;
	}

	/**
	 * Get list of evaluation elements
	 *
	 * @param   int show_in_list_summary get elements displayed in Fabrik List ; yes=1
	 * @param   int hidden get hidden elements ; yes=1
	 * @param   array code get elements from Evaluation form defined for programme list
	 *
	 * @return    array list of Fabrik element ID used in evaluation form
	 **@throws Exception
	 */
	public function getEvaluationElementsName($show_in_list_summary = 1, $hidden = 0, $code = array(), $all = null)
	{
		$session = Factory::getApplication()->getSession();
		$h_list  = new EmundusHelperList;

		$elements = array();
		if ($session->has('filt_params') || !empty($all))
		{

			$filt_params = $session->get('filt_params');

			if (!empty($code))
			{
				$programmes = array_unique($code);
			}
			elseif ($filt_params['programme'][0] !== '%' && is_array($filt_params['programme']) && !empty($filt_params['programme']))
			{
				$programmes = array_unique($filt_params['programme']);
			}
			else
			{
				return array();
			}
			foreach ($programmes as $value)
			{
				$groups = $this->getGroupsEvalByProgramme($value);

				if (empty($groups))
				{
					$eval_elt_list = array();
				}
				else
				{
					$eval_elt_list = $this->getElementsByGroups($groups, $show_in_list_summary, $hidden);

					if (!empty($eval_elt_list))
					{
						foreach ($eval_elt_list as $eel)
						{
							if (!empty($eel->element_id))
							{
								$elements[] = $h_list->getElementsDetailsByID($eel->element_id)[0];
							}
						}
					}
				}
			}
		}

		return $elements;
	}

	/**
	 * Get list of ALL evaluation element
	 *
	 * @param   int  $show_in_list_summary  1 is default value
	 * @param   string code of the programme
	 *
	 * @return array list of Fabrik element ID used in evaluation form
	 * @throws Exception
	 */
	public function getAllEvaluationElements($show_in_list_summary, $programme_code)
	{
		$session = JFactory::getSession();

		$jinput  = JFactory::getApplication()->input;
		$fnums   = $jinput->getString('cfnums', null);
		$get_all = false;

		if ($session->has('filt_params'))
		{
			$elements_id = array();
			$filt_params = $session->get('filt_params');

			if (is_array(@$filt_params['programme']) && $filt_params['programme'][0] != '%' && !empty(array_filter($filt_params['programme'])))
			{
				foreach ($filt_params['programme'] as $value)
				{
					if ($value == $programme_code)
					{
						$groups = $this->getGroupsEvalByProgramme($value);
						if (!empty($groups))
						{
							$eval_elt_list = $this->getAllElementsByGroups($groups); // $show_in_list_summary
							if (count($eval_elt_list) > 0)
							{
								foreach ($eval_elt_list as $eel)
								{
									$elements_id[] = $eel->element_id;
								}
							}
						}
					}
				}
			}
			else
			{
				$get_all = true;
			}
		}
		else
		{
			$get_all = true;
		}

		if ($get_all)
		{
			$groups = $this->getGroupsEvalByProgramme($programme_code);
			if (!empty($groups))
			{
				$eval_elt_list = $this->getAllElementsByGroups($groups); // $show_in_list_summary
				if (count($eval_elt_list) > 0)
				{
					foreach ($eval_elt_list as $eel)
					{
						$elements_id[] = $eel->element_id;
					}
				}
			}
		}

		return $elements_id;
	}

	/**
	 * Get list of ALL decision elements
	 *
	 * @param   int displayed in Fabrik List ; yes=1
	 * @param   string code of the programme
	 *
	 * @return    array list of Fabrik element ID used in evaluation form
	 **@throws Exception
	 */
	public function getAllDecisionElements($show_in_list_summary, $programme_code)
	{
		$session = JFactory::getSession();

		$jinput = JFactory::getApplication()->input;
		//$fnums = $jinput->getString('cfnums', null);

		if ($session->has('filt_params'))
		{
			$elements_id = array();
			$filt_params = $session->get('filt_params');

			if (is_array(@$filt_params['programme']) && $filt_params['programme'][0] != '%')
			{
				foreach ($filt_params['programme'] as $value)
				{
					if ($value == $programme_code)
					{
						$groups = $this->getGroupsDecisionByProgramme($value);
						if (!empty($groups))
						{
							$eval_elt_list = $this->getAllElementsByGroups($groups, $show_in_list_summary); // $show_in_list_summary
							if (count($eval_elt_list) > 0)
							{
								foreach ($eval_elt_list as $eel)
								{
									$elements_id[] = $eel->element_id;
								}
							}
						}
					}
				}
			}
			else
			{
				$groups = $this->getGroupsDecisionByProgramme($programme_code);
				if (!empty($groups))
				{
					$eval_elt_list = $this->getAllElementsByGroups($groups, $show_in_list_summary); // $show_in_list_summary
					if (count($eval_elt_list) > 0)
					{
						foreach ($eval_elt_list as $eel)
						{
							$elements_id[] = $eel->element_id;
						}
					}
				}
			}
		}

		return @$elements_id;
	}

	public function _buildContentOrderBy()
	{
		$filter_order     = JFactory::getSession()->get('filter_order');
		$filter_order_Dir = JFactory::getSession()->get('filter_order_Dir');

		$can_be_ordering = array();
		if (count($this->_elements) > 0)
		{
			foreach ($this->_elements as $element)
			{
				if (!empty($element->table_join))
				{
					$can_be_ordering[] = $element->table_join . '___' . $element->element_name;
					$can_be_ordering[] = $element->table_join . '.' . $element->element_name;
				}
				else
				{
					$can_be_ordering[] = $element->tab_name . '___' . $element->element_name;
					$can_be_ordering[] = $element->tab_name . '.' . $element->element_name;
				}
			}
		}

		$can_be_ordering[] = 'jecc.id';
		$can_be_ordering[] = 'jecc.fnum';
		$can_be_ordering[] = 'jecc.status';
		$can_be_ordering[] = 'fnum';
		$can_be_ordering[] = 'status';
		$can_be_ordering[] = 'jecc.status';
		$can_be_ordering[] = 'name';
		$can_be_ordering[] = 'eta.id_tag';
		$can_be_ordering[] = 'overall';

		if($filter_order === 'fnum') {
			$filter_order = 'name';
		}

		if (!empty($filter_order) && !empty($filter_order_Dir) && in_array($filter_order, $can_be_ordering))
			return ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;

		return '';
	}

	public function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC)
	{

		if (is_array($multi_array))
		{
			foreach ($multi_array as $key => $row_array)
			{

				if (is_array($row_array))
					@$key_array[$key] = $row_array[$sort_key];
				else
					return -1;

			}

		}
		else return -1;

		if (!empty($key_array))
			array_multisort($key_array, $sort, $multi_array);

		return $multi_array;
	}

	public function getCampaign()
	{
		return @EmundusHelperFiles::getCampaign();
	}

	public function getCurrentCampaign()
	{
		return @EmundusHelperFiles::getCurrentCampaign();
	}

	public function getCurrentCampaignsID()
	{
		return @EmundusHelperFiles::getCurrentCampaignsID();
	}

	public function getProfileAcces($user)
	{
		$query = 'SELECT esg.profile_id FROM #__emundus_setup_groups as esg
					LEFT JOIN #__emundus_groups as eg on esg.id=eg.group_id
					WHERE esg.published=1 AND eg.user_id=' . $user;
		$this->db->setQuery($query);

		return $this->db->loadResultArray();
	}

	public function setSubQuery($tab, $elem)
	{
		$search              = JFactory::getApplication()->input->get('elements', null, 'POST', 'array', 0);
		$search_values       = JFactory::getApplication()->input->get('elements_values', null, 'POST', 'array', 0);
		$search_other        = JFactory::getApplication()->input->get('elements_other', null, 'POST', 'array', 0);
		$search_values_other = JFactory::getApplication()->input->get('elements_values_other', null, 'POST', 'array', 0);

		$query = 'SELECT DISTINCT(#__emundus_users.user_id), ' . $tab . '.' . $elem . ' AS ' . $tab . '__' . $elem;
		$query .= '	FROM #__emundus_campaign_candidature
					LEFT JOIN #__emundus_users ON #__emundus_users.user_id=#__emundus_campaign_candidature.applicant_id
					LEFT JOIN #__users ON #__users.id=#__emundus_users.user_id';

		// subquery JOINS
		$joined = array('jos_emundus_users');
		$this->setJoins($search, $query, $joined);
		$this->setJoins($search_other, $query, $joined);
		$this->setJoins($this->_elements_default, $query, $joined);

		// subquery WHERE
		$query .= ' WHERE #__emundus_campaign_candidature.submitted=1 AND ' .
			$this->details->{$tab . '__' . $elem}['group_by'] . '=#__users.id';
		$query = @EmundusHelperFiles::setWhere($search, $search_values, $query);
		$query = @EmundusHelperFiles::setWhere($search_other, $search_values_other, $query);
		$query = @EmundusHelperFiles::setWhere($this->_elements_default, $this->elements_values, $query);

		$this->db->setQuery($query);
		$obj  = $this->db->loadObjectList();
		$list = array();
		$tmp  = '';
		foreach ($obj as $unit)
		{
			if ($tmp != $unit->user_id)
				$list[$unit->user_id] =
					@EmundusHelperList::getBoxValue($this->details->{$tab . '__' . $elem}, $unit->{$tab . '__' . $elem},
						$elem);
			else
				$list[$unit->user_id] .= ',' . @EmundusHelperList::getBoxValue($this->details->{$tab . '__' . $elem},
						$unit->{$tab . '__' . $elem}, $elem);
			$tmp = $unit->user_id;
		}

		return $list;
	}

	public function setSelect($search)
	{
		$cols = array();
		if (!empty($search))
		{
			asort($search);
			$i         = 0;
			$old_table = '';
			foreach ($search as $c)
			{
				if (!empty($c))
				{
					$tab = explode('.', $c);
					if ($tab[0] == 'jos_emundus_training')
					{
						$cols[] = ' search_' . $tab[0] . '.label as ' . $tab[1] . ' ';
					}
					else
					{
						if ($this->details->{$tab[0] . '__' . $tab[1]}['group_by'])
							$this->subquery[$tab[0] . '__' . $tab[1]] = $this->setSubQuery($tab[0], $tab[1]);
						else $cols[] = $c . ' AS ' . $tab[0] . '__' . $tab[1];
					}
				}
				$i++;
			}
			if (count($cols > 0) && !empty($cols))
				$cols = implode(', ', $cols);
		}

		return $cols;
	}

	public function isJoined($tab, $joined)
	{
		foreach ($joined as $j)
			if ($tab == $j) return true;

		return false;
	}

	public function setJoins($search, $query, $joined)
	{
		$tables_list = array();
		if (!empty($search))
		{
			$old_table = '';
			$i         = 0;
			foreach ($search as $s)
			{
				$tab = explode('.', $s);
				if (count($tab) > 1)
				{
					if ($tab[0] != $old_table && !$this->isJoined($tab[0], $joined))
					{
						if ($tab[0] == 'jos_emundus_groups_eval' || $tab[0] == 'jos_emundus_comments')
							$query .= ' LEFT JOIN ' . $tab[0] . ' ON ' . $tab[0] . '.applicant_id=#__users.id ';
						elseif ($tab[0] == 'jos_emundus_evaluations' || $tab[0] == 'jos_emundus_final_grade' ||
							$tab[0] == 'jos_emundus_academic_transcript'
							|| $tab[0] == 'jos_emundus_bank' || $tab[0] == 'jos_emundus_files_request' ||
							$tab[0] == 'jos_emundus_mobility'
						)
							$query .= ' LEFT JOIN ' . $tab[0] . ' ON ' . $tab[0] . '.student_id=#__users.id ';
						elseif ($tab[0] == "jos_emundus_training")
							$query .=
								' LEFT JOIN #__emundus_setup_teaching_unity AS search_' . $tab[0] . ' ON search_' .
								$tab[0] . '.code=#__emundus_setup_campaigns.training ';
						else
							$query .= ' LEFT JOIN ' . $tab[0] . ' ON ' . $tab[0] . '.user=#__users.id ';
						$joined[] = $tab[0];
					}
					$old_table = $tab[0];
				}
				$i++;
			}
		}

		return $tables_list;
	}

	public function _buildSelect(&$tables_list, &$tables_list_other, &$tables_list_default)
	{
		$current_user         = JFactory::getUser();
		$search               = $this->getState('elements');
		$search_other         = $this->getState('elements_other');
		$schoolyears          = $this->getState('schoolyear');
		$programmes           = $this->getState('programme');
		$gid                  = $this->getState('groups');
		$uid                  = $this->getState('user');
		$miss_doc             = $this->getState('missing_doc');
		$validate_application = $this->getState('validate');

		$menu                   = @JFactory::getApplication()->getMenu();
		$current_menu           = $menu->getActive();
		$menu_params            = $menu->getParams($current_menu->id);
		$this->validate_details = @EmundusHelperList::getElementsDetailsByID($menu_params->get('em_validate_id'));
		$col_validate           = "";
		foreach ($this->validate_details as $vd)
		{
			$col_validate .= $vd->tab_name . '.' . $vd->element_name . ',';
		}
		$col_validate = substr($col_validate, 0, strlen($col_validate) - 1);

		$cols         = $this->setSelect($search);
		$cols_other   = $this->setSelect($search_other);
		$cols_default = $this->setSelect($this->_elements_default);

		$joined = array('jos_emundus_users', 'jos_users',
			'jos_emundus_setup_profiles',
			'jos_emundus_final_grade',
			'jos_emundus_declaration');

		$query = 'SELECT #__emundus_users.user_id, #__emundus_users.user_id as user, #__emundus_users.user_id as id, #__emundus_users.lastname, #__emundus_users.firstname,
		#__users.name, #__users.registerDate, #__users.email, #__emundus_setup_profiles.id as profile, #__emundus_campaign_candidature.date_submitted,
		#__emundus_setup_campaigns.year as schoolyear, #__emundus_setup_campaigns.label, #__emundus_campaign_candidature.date_submitted, #__emundus_campaign_candidature.campaign_id';
		if (!empty($cols)) $query .= ', ' . $cols;
		if (!empty($cols_other)) $query .= ', ' . $cols_other;
		if (!empty($cols_default)) $query .= ', ' . $cols_default;
		if (!empty($col_validate)) $query .= ', ' . $col_validate;
		$query .= '	FROM #__emundus_campaign_candidature
					LEFT JOIN #__emundus_users ON #__emundus_declaration.user=#__emundus_users.user_id
					LEFT JOIN #__emundus_setup_campaigns ON #__emundus_setup_campaigns.id=#__emundus_campaign_candidature.campaign_id
					LEFT JOIN #__users ON #__users.id=#__emundus_users.user_id
					LEFT JOIN #__emundus_setup_profiles ON #__emundus_setup_profiles.id=#__emundus_users.profile
					LEFT JOIN #__emundus_final_grade ON #__emundus_final_grade.student_id=#__emundus_users.user_id';

		$this->setJoins($search, $query, $joined);
		$this->setJoins($search_other, $query, $joined);
		$this->setJoins($this->_elements_default, $query, $joined);

		if (((isset($gid) && !empty($gid)) || (isset($uid) && !empty($uid))) &&
			!$this->isJoined('jos_emundus_groups_eval', $joined)
		)
			$query .= ' LEFT JOIN #__emundus_groups_eval ON #__emundus_groups_eval.applicant_id=#__users.id ';

		if (!empty($miss_doc) && !$this->isJoined('jos_emundus_uploads', $joined))
			$query .= ' LEFT JOIN #__emundus_uploads ON #__emundus_uploads.user_id=#__users.id';

		if (!empty($validate_application) && !$this->isJoined('jos_emundus_declaration', $joined))
			$query .= ' LEFT JOIN #__emundus_declaration ON #__emundus_declaration.user=#__users.id';

		$query .= ' WHERE #__emundus_campaign_candidature.submitted = 1 AND #__users.block = 0 ';
		if (empty($schoolyears))
			$query .= ' AND #__emundus_campaign_candidature.year IN ("' . implode('","', $this->getCurrentCampaign()) . '")';

		if (!empty($programmes) && isset($programmes) && $programmes[0] != "%")
			$query .= ' AND #__emundus_setup_campaigns.training IN ("' . implode('","', $programmes) . '")';

		if (!EmundusHelperAccess::isAdministrator($current_user->id) &&
			!EmundusHelperAccess::isCoordinator($current_user->id)
		)
		{
			$pa    = EmundusHelperAccess::getProfileAccess($current_user->id);
			$query .= ' AND (#__emundus_users.user_id IN (
								SELECT user_id
								FROM #__emundus_users_profiles
								WHERE profile_id in (' . implode(',', $pa) . ')) OR #__emundus_users.user_id IN (
									SELECT user_id
									FROM #__emundus_users
									WHERE profile in (' . implode(',', $pa) . '))
							) ';
		}

		return $query;
	}

	/**
	 * @description : Generate values for array of data for all applicants
	 *
	 * @param   array   $search     filters elements
	 * @param   array   $eval_list  reference of result list
	 * @param   array   $head_val   header name
	 * @param   object  $applicant  array of applicants indexed by database column
	 **/
	public function setEvalList($search, &$eval_list, $head_val, $applicant)
	{
		if (!empty($search))
		{
			foreach ($search as $c)
			{
				if (!empty($c))
				{
					$name = explode('.', $c);
					if (!in_array($name[0] . '__' . $name[1], $head_val))
					{
						$print_val = '';
						if ($this->details->{$name[0] . '__' . $name[1]}['group_by']
							&& array_key_exists($name[0] . '__' . $name[1], $this->subquery)
							&& array_key_exists($applicant->user_id, $this->subquery[$name[0] . '__' . $name[1]])
						)
						{
							$eval_list[$name[0] . '__' . $name[1]] = @EmundusHelperList::createHtmlList(explode(",",
								$this->subquery[$name[0] . '__' . $name[1]][$applicant->user_id]));
						}
						elseif ($name[0] == 'jos_emundus_training')
						{
							$eval_list[$name[1]] = $applicant->{$name[1]};
						}
						elseif (!$this->details->{$name[0] . '__' . $name[1]}['group_by'])
						{
							$eval_list[$name[0] . '__' . $name[1]] =
								@EmundusHelperList::getBoxValue($this->details->{$name[0] . '__' . $name[1]},
									$applicant->{$name[0] . '__' . $name[1]}, $name[1]);
						}
						else
							$eval_list[$name[0] . '__' . $name[1]] = $applicant->{$name[0] . '__' . $name[1]};
					}
				}
			}
		}
	}


	private function _buildWhere($already_joined_tables = array(), $step_id = 0)
	{
		$h_files = new EmundusHelperFiles();

		$caller_params = array(
			'fnum_assoc' => $this->fnum_assoc,
			'code'       => $this->code,
			'eval'       => true,
		);

		if (!empty($step_id))
		{
			$caller_params['step_id'] = $step_id;
		}

		return $h_files->_moduleBuildWhere($already_joined_tables, 'files', $caller_params);
	}

	public function getUsers($current_fnum = null, $user_id = null, $all = false)
	{
		$list = [];

		if (empty($user_id)) {
			$user_id = $this->app->getIdentity()->id;
		}

		include_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		$m_users = new EmundusModelUsers;
		$this->code = $m_users->getUserGroupsProgrammeAssoc($user_id);
		$groups               = $m_users->getUserGroups($user_id, 'Column');
		$fnum_assoc_to_groups = $m_users->getApplicationsAssocToGroups($groups);
		$fnum_assoc_to_user   = $m_users->getApplicantsAssoc($user_id);
		$this->fnum_assoc     = array_merge($fnum_assoc_to_groups, $fnum_assoc_to_user);

		$session = $this->app->getSession();
		$applied_filters = $session->get('em-applied-filters', []);

		$step_ids = [];
		foreach ($applied_filters as $filter) {
			if ($filter['uid'] == 'workflow_steps') {
				if (!empty($filter['value']) && !in_array('all', $filter['value'])) {
					$step_ids = !is_array($filter['value']) ? [$filter['value']] : $filter['value'];
				}
			}
		}

		if (empty($step_ids)) {
			// get all steps user has access to
			if (!class_exists('EmundusFiltersFiles')) {
				require_once JPATH_SITE . '/components/com_emundus/classes/filters/EmundusFiltersFiles.php';
			}
			$c_filter_files = new EmundusFiltersFiles([], true);
			$user_programs = $c_filter_files->getUserProgrammes();
			$user_groups = $c_filter_files->getUserGroups();

			if (!empty($user_programs)) {
				if (!class_exists('EmundusModelWorkflow')) {
					require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
				}
				$m_workflow = new EmundusModelWorkflow();
				$workflows = $m_workflow->getWorkflows([], 0, 0, $user_programs);
				foreach($workflows as $workflow) {
					$workflow_data = $m_workflow->getWorkflow($workflow->id);

					if (!empty($workflow_data['steps'])) {
						foreach($workflow_data['steps'] as $step) {
							$action_id = $m_workflow->getStepAssocActionId($step->id);
							if ($m_workflow->isEvaluationStep($step->type) &&
								(EmundusHelperAccess::asAccessAction($action_id, 'r', $this->app->getIdentity()->id) || EmundusHelperAccess::asAccessAction($action_id, 'c', $this->app->getIdentity()->id))) {
								$step_ids[] = $step->id;
							}
						}
					}
				}
			}
		}

		$this->_applicants = [];
		if (!empty($step_ids))  {
			foreach ($step_ids as $step_id) {
				$list = array_merge($list, $this->getEvaluationsList($step_id, count($step_ids)));
			}
			$grouped_list = [];
			foreach($list as $item) {
				if (!isset($grouped_list[$item['fnum']])) {
					$grouped_list[$item['fnum']] = [];
				}
				$grouped_list[$item['fnum']][] = $item;
			}

			$list = [];
			foreach($grouped_list as $fnum => $items) {
				foreach ($items as $item) {
					$list[] = $item;
				}
			}

			if (!$all) {
				$session = $this->app->getSession();
				$limit = $session->get('limit', 0);
				$limitstart = $session->get('limitstart', 0);

				if (!empty($limit)) {
					$list = array_slice($list, $limitstart, $limit);
				}
			}
		} else {
			$list = $this->getEvaluationsList();
		}

		return $list;
	}

	function getEvaluationsList($step_id = 0, $nb_steps = 1, $user_id = null)
	{
		$evaluations_list = [];

		$session                       = $this->app->getSession();
		$evaluators_can_see_other_eval = false;

		if (empty($user_id)) {
			$user_id = $this->app->getIdentity()->id;
		}

		$query = 'select jecc.fnum, ss.step, ss.value as status, concat(upper(trim(eu.lastname))," ",eu.firstname) as name, ss.class as status_class, sp.code, eu.is_anonym';
		$group_by = 'GROUP BY jecc.fnum ';

		$already_joined_tables = [
			'jecc' => 'jos_emundus_campaign_candidature',
			'ss'   => 'jos_emundus_setup_status',
			'esc'  => 'jos_emundus_setup_campaigns',
			'escm'  => 'jos_emundus_setup_campaigns_more',
			'sp'   => 'jos_emundus_setup_programmes',
			'u'    => 'jos_users',
			'eu'   => 'jos_emundus_users',
			'eta'  => 'jos_emundus_tag_assoc',
		];

		if (!empty($step_id))
		{
			$m_worfklow = new EmundusModelWorkflow();
			$step_data  = $m_worfklow->getStepData($step_id);

			if (empty($step_data->table)) {
				throw new Exception(sprintf(Text::_('COM_EMUNDUS_BUILD_WHERE_STEP_CONFIGURATION_ERROR'), $step_data->label));
			}

			if (EmundusHelperAccess::asAccessAction($step_data->action_id, 'r', $user_id)) {
				$evaluators_can_see_other_eval = true;
			}

			$already_joined_tables[$step_data->table] = $step_data->table;

			$query .= ', ' . $step_data->table . '.id as evaluation_id,  CONCAT(eue.lastname," ",eue.firstname) AS evaluator, ' . $step_data->table . '.evaluator as evaluator_id ';
			$query .= ', ' . $this->db->quote($step_data->label) . ' AS evaluations_step_label, ' . $step_data->id . ' AS evaluations_step_id ';
			$group_by = 'GROUP BY IF(evaluation_id IS NULL, jecc.fnum, evaluation_id)';
		} else {
			$query .= ', 0 as evaluation_id, " " as evaluator, 0 as evaluator_id ';
			$query .= ', "" AS evaluations_step_label, 0 AS evaluations_step_id ';
		}

		$leftJoin = '';
		if (!empty($this->_elements))
		{
			$h_files = new EmundusHelperFiles();

			foreach ($this->_elements as $elt)
			{
				$table_to_join      = !empty($elt->table_join) ? $elt->table_join : $elt->tab_name;
				$already_join_alias = array_keys($already_joined_tables);

				if (!(in_array($table_to_join, $already_joined_tables)) && !(in_array($table_to_join, $already_join_alias, true)))
				{
					if ($h_files->isTableLinkedToCampaignCandidature($table_to_join))
					{
						$leftJoin .= ' LEFT JOIN ' . $table_to_join . ' ON ' . $table_to_join . '.fnum = jecc.fnum ';

						if (!empty($step_id) && str_starts_with($table_to_join, 'jos_emundus_evaluations_'))
						{
							$leftJoin .= ' AND ' . $table_to_join . '.step_id = ' . $step_id;
						}

						$already_joined_tables[] = $table_to_join;
					}
					else
					{
						$joined          = false;
						$query_find_join = $this->db->getQuery(true);
						foreach ($already_joined_tables as $already_join_alias => $already_joined_table_name)
						{
							$query_find_join->clear()
								->select('*')
								->from('#__fabrik_joins')
								->where('table_join = ' . $this->db->quote($already_joined_table_name))
								->andWhere('join_from_table = ' . $this->db->quote($table_to_join))
								->andWhere('table_key = ' . $this->db->quote('id'))
								->andWhere('list_id = ' . $this->db->quote($elt->table_list_id));

							$this->db->setQuery($query_find_join);
							$join_informations = $this->db->loadAssoc();

							if (!empty($join_informations))
							{
								$already_joined_tables[] = $table_to_join;

								$leftJoin .= ' LEFT JOIN ' . $this->db->quoteName($join_informations['join_from_table']) . ' ON ' . $this->db->quoteName($join_informations['join_from_table'] . '.' . $join_informations['table_key']) . ' = ' . $this->db->quoteName($already_join_alias . '.' . $join_informations['table_join_key']);
								$joined   = true;
								break;
							}
						}

						if (!$joined)
						{
							$element_joins = $h_files->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', $table_to_join);

							if (!empty($element_joins))
							{
								$leftJoin .= $h_files->writeJoins($element_joins, $already_joined_tables);
							}
						}
					}
				}
			}
		}

		if (!empty($this->_elements_default))
		{
			$query .= ', ' . implode(',', $this->_elements_default);
		}
		$query .= ' FROM #__emundus_campaign_candidature as jecc
					LEFT JOIN #__emundus_setup_status as ss on ss.step = jecc.status
					LEFT JOIN #__emundus_setup_campaigns as esc on esc.id = jecc.campaign_id
					LEFT JOIN #__emundus_setup_campaigns_more as escm on escm.campaign_id = esc.id
					LEFT JOIN #__emundus_setup_programmes as sp on sp.code = esc.training
					LEFT JOIN #__emundus_users as eu on eu.user_id = jecc.applicant_id
					LEFT JOIN #__users as u on u.id = jecc.applicant_id
                    LEFT JOIN #__emundus_tag_assoc as eta on eta.fnum = jecc.fnum ';

		if (!empty($step_id))
		{
			$query .= ' LEFT JOIN ' . $step_data->table . ' ON ' . $step_data->table . '.fnum = jecc.fnum AND ' . $step_data->table . '.step_id = ' . $step_id . ' ';

			if (!$evaluators_can_see_other_eval) {
				$query .= ' AND ' . $step_data->table . '.evaluator = ' . $user_id . ' ';
			}

			$query .= ' LEFT JOIN #__emundus_users as eue on eue.user_id = ' . $step_data->table . '.evaluator ';

			$already_joined_tables[$step_data->table] = $step_data->table;
		}

		if (!empty($this->_elements_default))
		{
			foreach($this->_elements_default as $element) {
				if (strpos($element, 'jos_emundus_evaluations_') !== false) {
					if (preg_match('/jos_emundus_evaluations_([0-9]+)\.id/', $element, $matches)) {
						$evaluation_id = $matches[1];
						$evaluation_table = 'jos_emundus_evaluations_' . $evaluation_id;

						if (!in_array($evaluation_table, $already_joined_tables)) {
							$query .= ' LEFT JOIN ' . $evaluation_table . ' ON ' . $evaluation_table . '.fnum = jecc.fnum ';
							$already_joined_tables[] = $evaluation_table;
						}
					}
				}
			}
		}

		$q = $this->_buildWhere($already_joined_tables, $step_id);

		if (!empty($leftJoin))
		{
			$query .= $leftJoin;
		}
		$query .= $q['join'];

		if (empty($current_fnum))
		{
			$query .= ' WHERE jecc.status > 0 ';
		}
		else
		{
			$query .= ' WHERE jecc.fnum like ' . $this->db->quote($current_fnum) . ' ';
		}

		$query .= ' AND esc.published = 1';

		$query .= $q['q'];
		$query .= ' ' . $group_by;

		$query .= $this->_buildContentOrderBy();
		try
		{
			$this->db->setQuery($query);
			$res               = $this->db->loadAssocList();
			$this->_applicants = array_merge($this->_applicants, $res);
			if (empty($current_fnum))
			{
				/**
				 * when step_ids are provided, we are doing multiple queries to get evaluations for each step, so we can not
				 * limit the results on each query, we will limit the results after merging all the results
				 * TODO: find a way to limit the results on each query
				 */
				if (empty($step_id)) {
					$limit = $session->get('limit');

					$limitStart = $session->get('limitstart');
					if ($limit > 0)
					{
						$query .= " limit $limitStart, $limit ";
					}
				}
			}

			$this->db->setQuery($query);

			$evaluations_list = $this->db->loadAssocList();
		}
		catch (Exception $e)
		{
			Log::add(JUri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . str_replace('#_', 'jos', $query), Log::ERROR, 'com_emundus');
		}

		return $evaluations_list;
	}

	// get elements by groups
	// @param 	  $show_in_list_summary int show item defined as displayed in Fabrik List ; yes=1
	// @param 	  $hidden int show item defined as hidden in Fabrik List  ; yes=1
	// @params 	  $groups string List of Fabrik groups comma separated
	function getElementsByGroups($groups, $show_in_list_summary = 1, $hidden = 0, $exclude_plugins = [])
	{
		return @EmundusHelperFilters::getElementsByGroups($groups, $show_in_list_summary, $hidden, $exclude_plugins);
	}

	// get ALL elements by groups
	// @params string List of Fabrik groups comma separated
	function getAllElementsByGroups($groups)
	{
		return @EmundusHelperFilters::getAllElementsByGroups($groups);
	}

	public function getActionsACL()
	{
		return $this->_files->getActionsACL();
	}

	public function getDefaultElements()
	{
		return $this->_elements;
	}

	public function getSelectList()
	{
		return $this->_files->getSelectList();
	}

	public function getProfiles()
	{
		return $this->_files->getProfiles();
	}

	public function getProfileByID($id)
	{
		return $this->_files->getProfileByID($id);
	}

	public function getProfilesByIDs($ids)
	{
		return $this->_files->getProfilesByIDs($ids);
	}

	public function getAuthorProfiles()
	{
		return $this->_files->getAuthorProfiles();
	}

	public function getApplicantsProfiles()
	{
		return $this->_files->getApplicantsProfiles();
	}

	public function getApplicantsByProfile($profile)
	{
		return $this->_files->getApplicantsByProfile($profile);
	}

	public function getAuthorUsers()
	{
		return $this->_files->getAuthorUsers();
	}

	public function getMobility()
	{
		return $this->_files->getMobility();
	}

	public function getElements()
	{
		return $this->_files->getElements();
	}

	public function getElementsName()
	{
		return $this->_files->getElementsName();
	}

	public function getTotal()
	{
		if (empty($this->_total))
			$this->_total = count($this->_applicants);

		return $this->_total;
	}

	public function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			jimport('joomla.html.pagination');
			$session           = JFactory::getSession();
			$this->_pagination = new JPagination($this->getTotal(), $session->get('limitstart'), $session->get('limit'));
		}

		return $this->_pagination;
	}

	public function getPageNavigation(): string
	{
		if ($this->getPagination()->pagesTotal <= 1)
		{
			return '';
		}

		$pageNavigation = "<div class='em-container-pagination-selectPage'>";
		$pageNavigation .= "<ul class='pagination pagination-sm'>";
		if($this->getPagination()->pagesCurrent == $this->getPagination()->pagesStart) {
			$pageNavigation .= "<li><a class='disabled tw-cursor-pointer'><span class='material-symbols-outlined'>navigate_before</span></a></li>";
		} else
		{
			$pageNavigation .= "<li><a href='#em-data' id='" . ($this->getPagination()->pagesCurrent - 1) . "'><span class='material-symbols-outlined'>navigate_before</span></a></li>";
		}

		if ($this->getPagination()->pagesTotal > 15)
		{
			for ($i = 1; $i <= 5; $i++)
			{
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i)
				{
					$pageNavigation .= "class='active'";
				}
				$pageNavigation .= "><a id='" . $i . "' href='#em-data'>" . $i . "</a></li>";
			}
			$pageNavigation .= "<li class='disabled'><span>...</span></li>";
			if ($this->getPagination()->pagesCurrent <= 5)
			{
				for ($i = 6; $i <= 10; $i++)
				{
					$pageNavigation .= "<li ";
					if ($this->getPagination()->pagesCurrent == $i)
					{
						$pageNavigation .= "class='active'";
					}
					$pageNavigation .= "><a id=" . $i . " href='#em-data'>" . $i . "</a></li>";
				}
			}
			else
			{
				for ($i = $this->getPagination()->pagesCurrent - 2; $i <= $this->getPagination()->pagesCurrent + 2; $i++)
				{
					if ($i <= $this->getPagination()->pagesTotal)
					{
						$pageNavigation .= "<li ";
						if ($this->getPagination()->pagesCurrent == $i)
						{
							$pageNavigation .= "class='active'";
						}
						$pageNavigation .= "><a id=" . $i . " href='#em-data'>" . $i . "</a></li>";
					}
				}
			}
			$pageNavigation .= "<li class='disabled'><span>...</span></li>";
			for ($i = $this->getPagination()->pagesTotal - 4; $i <= $this->getPagination()->pagesTotal; $i++)
			{
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i)
				{
					$pageNavigation .= "class='active'";
				}
				$pageNavigation .= "><a id='" . $i . "' href='#em-data'>" . $i . "</a></li>";
			}
		}
		else
		{
			for ($i = 1; $i <= $this->getPagination()->pagesStop; $i++)
			{
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i)
				{
					$pageNavigation .= "class='active'";
				}
				$pageNavigation .= "><a id='" . $i . "' href='#em-data'>" . $i . "</a></li>";
			}
		}

		if($this->getPagination()->pagesCurrent == $this->getPagination()->pagesStop) {
			$pageNavigation .= "<li><a class='disabled tw-cursor-pointer'><span class='material-symbols-outlined'>navigate_next</span></a></li></ul></div>";
		} else {
			$pageNavigation .= "<li><a href='#em-data' id='" . ($this->getPagination()->pagesCurrent + 1) . "'><span class='material-symbols-outlined'>navigate_next</span></a></li></ul></div>";
		}

		return $pageNavigation;
	}

	// get applicant columns
	public function getApplicantColumns()
	{
		$cols   = array();
		$cols[] = array('name' => 'user_id', 'label' => 'User id');
		$cols[] = array('name' => 'user', 'label' => 'User id');
		$cols[] = array('name' => 'name', 'label' => 'Name');
		$cols[] = array('name' => 'email', 'label' => 'Email');
		$cols[] = array('name' => 'profile', 'label' => 'Profile');

		return $cols;
	}

	// get string of fabrik group ID use for evaluation form
	public function getGroupsEvalByProgramme($code, $column = 'code', $user_id = 0)
	{
		$group_ids = [];

		if (!empty($code)) {
			if (empty($user_id)) {
				$user_id = $this->app->getIdentity()->id;
			}

			$query = $this->db->createQuery();
			$query->select('id')
				->from('#__emundus_setup_programmes')
				->where($this->db->quoteName($column) . ' = ' . $this->db->quote($code));

			$this->db->setQuery($query);
			$program_id = $this->db->loadResult();

			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$m_workflow = new EmundusModelWorkflow();
			$m_workflow->getEvaluatorStepsByProgram($program_id);
			$eval_steps = $m_workflow->getEvaluatorStepsByProgram($program_id);

			if (!empty($eval_steps))
			{
				$form_ids = array_map(function ($step) use ($user_id, $m_workflow) {
					if ($m_workflow->isEvaluationStep($step->type) && (EmundusHelperAccess::asAccessAction($step->action_id, 'r', $user_id) || EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id))) {
						return $step->form_id;
					}

					return null;
				}, $eval_steps);
				$form_ids = array_filter($form_ids);

				if (!empty($form_ids)) {
					$query->clear()
						->select('fg.id')
						->from('#__fabrik_groups AS fg')
						->leftJoin('#__fabrik_formgroup AS ffg ON fg.id = ffg.group_id')
						->where('ffg.form_id IN (' . implode(',', $form_ids) . ')')
						->andWhere('fg.published = 1');

					$this->db->setQuery($query);
					$group_ids = $this->db->loadColumn();
				}
			}
		}

		return implode(',', $group_ids);
	}


	// get string of fabrik group ID use for evaluation form
	public function getGroupsDecisionByProgramme($code)
	{
		$query = 'select fabrik_decision_group_id from #__emundus_setup_programmes where code like ' . $this->db->Quote($code);
		try
		{
			if (!empty($code))
			{
				$this->db->setQuery($query);

				return $this->db->loadResult();
			}
			else return null;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	public function getSchoolyears()
	{
		return $this->_files->getSchoolyears();
	}

	public function getAllActions()
	{
		return $this->_files->getAllActions();
	}

	public function getEvalGroups()
	{
		return $this->_files->getEvalGroups();
	}

	public function shareGroups($groups, $actions, $fnums)
	{
		return $this->_files->shareGroups($groups, $actions, $fnums);
	}

	public function shareUsers($users, $actions, $fnums)
	{
		return $this->_files->shareUsers($users, $actions, $fnums);
	}

	public function getAllTags()
	{
		return $this->_files->getAllTags();
	}

	public function getAllStatus()
	{
		return $this->_files->getAllStatus();
	}

	public function tagFile($fnums, $tag)
	{
		return $this->_files->tagFile($fnums, $tag);
	}

	public function getTaggedFile($tag = null)
	{
		return $this->_files->getTaggedFile($tag = null);
	}

	public function updateState($fnums, $state)
	{
		return $this->_files->updateState($fnums, $state);
	}

	public function getPhotos()
	{
		return $this->_files->getPhotos();
	}

	public function getEvaluatorsFromGroup()
	{
		return $this->_files->getEvaluatorsFromGroup();
	}

	public function getEvaluators()
	{
		return $this->_files->getEvaluators();
	}

	public function unlinkEvaluators($fnum, $id, $isGroup)
	{
		return $this->_files->unlinkEvaluators($fnum, $id, $isGroup);
	}

	public function getFnumInfos($fnum)
	{
		return $this->_files->getFnumInfos($fnum);
	}

	public function changePublished($fnum, $published = -1)
	{
		return $this->_files->changePublished($fnum, $published = -1);
	}

	public function getAllFnums(int $user_id = 0): array
	{
		$fnums = [];

		if (empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}
		$evaluations = $this->getUsers(null, $user_id, true);

		if (!empty($evaluations)) {
			foreach ($evaluations as $evaluation) {
				if (!in_array($evaluation['fnum'], $fnums)) {
					$fnums[] = $evaluation['fnum'];
				}
			}
		}

		return $fnums;
	}

	/*
    * 	Get values of elements by list of files numbers
    *	@param fnums 	List of application files numbers
    *	@param elements 	array of element to get value
    * 	@return array
    */
	public function getFnumArray($fnums, $elements)
	{
		return $this->_files->getFnumArray($fnums, $elements);
	}

	public function getEvalsByFnum($fnums)
	{
		return $this->_files->getEvalsByFnum($fnums);
	}

	public function getCommentsByFnum($fnums)
	{
		return $this->_files->getCommentsByFnum($fnums);
	}

	public function getFilesByFnums($fnums)
	{
		return $this->_files->getFilesByFnums($fnums);
	}

	/*
    * 	Get list of expert emails send by applicant for programme SU convergence
    * 	@return array
    */
	function getExperts()
	{
		$experts = [];

		$query = $this->db->getQuery(true);

		$query->select([$this->db->quoteName('jeu.lastname'), $this->db->quoteName('jeu.firstname'), $this->db->quoteName('ju.email')])
			->from($this->db->quoteName('#__emundus_files_request', 'efr'))
			->innerJoin($this->db->quoteName('#__users', 'ju') . ' ON ju.email = efr.email')
			->leftJoin($this->db->quoteName('#__emundus_users', 'jeu') . ' ON ju.id = jeu.user_id')
			->group($this->db->quoteName('ju.email'))
			->order('ju.email ASC');

		try {
			$this->db->setQuery($query);
			$experts = $this->db->loadAssocList();
		} catch (Exception $e) {
			JLog::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
		}

		return $experts;
	}

	/**
	 * Get list of documents generated for email attachment
	 *
	 * @param             $fnum
	 * @param             $campaign_id
	 * @param   int|null  $doc_to_attach
	 *
	 * @return array|mixed
	 */
	function getEvaluationDocuments($fnum, $campaign_id, $doc_to_attach = null)
	{
		$query = 'SELECT *, eu.id as id, esa.id as attachment_id
					FROM #__emundus_uploads eu
					LEFT JOIN #__emundus_setup_attachments esa ON esa.id=eu.attachment_id
					WHERE eu.fnum like ' . $this->db->Quote($fnum) . ' AND campaign_id=' . $campaign_id . '
					AND eu.attachment_id IN (
						SELECT DISTINCT(esl.attachment_id) FROM #__emundus_setup_letters esl
					)';

		if (!empty($doc_to_attach))
		{
			$query .= ' AND eu.attachment_id = ' . (int) $doc_to_attach;
		}

		$query .= ' AND eu.filename NOT LIKE "%lock%"
					ORDER BY eu.timedate';

		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	/*
    * 	Get evaluations for fnum
    *	@param fnum 		Application File number
    * 	@return array
    */
	function getEvaluationsFnum($fnum)
	{
		$query = 'SELECT *
					FROM #__emundus_evaluations ee
					WHERE ee.fnum like ' . $this->db->Quote($fnum);
//die(str_replace('#_', 'jos', $query));
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	/**
	 *    Get evaluations for fnum done by a user
	 *
	 * @param   fnum        Application File number
	 * @param   user        user
	 *
	 * @return array
	 **/
	function getEvaluationsFnumUser($fnum, $user)
	{

		try
		{

			$query = 'SELECT *
					FROM #__emundus_evaluations ee
					WHERE ee.fnum like ' . $this->db->Quote($fnum) . ' AND user = ' . $user;
//die(str_replace('#_', 'jos', $query));
			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}
	}

	/**
	 *    Get all evaluations accross all programs for a student
	 *
	 * @param   user        the student in question
	 *
	 * @return array
	 **/
	function getEvaluationsByStudent($user)
	{
		$query                  = $this->db->getQuery(true);
		$evaluations_by_student = array();

		try
		{
			$query->select('*')
				->from($this->db->quoteName('#__emundus_evaluations', 'ee'))
				->where($this->db->quoteName('ee.student_id') . ' = ' . $this->db->quote($user));
			$this->db->setQuery($query);
			$evaluations_by_student = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $evaluations_by_student;
	}

	/**
	 *    Get all evaluations accross all programs for a student application file
	 *
	 * @param   fnum        the file number in question
	 *
	 * @return array
	 **/
	function getEvaluationsByFnum($fnum)
	{
		try
		{

			$query = 'SELECT * FROM #__emundus_evaluations ee WHERE ee.fnum like ' . $fnum . ' ORDER BY ee.id DESC';
			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}
	}

	/**
	 *    Copy a line by ID from the evaluation table and use it to overrite or create another line
	 *
	 * @param $fromID        line to copy data from
	 * @param $toID          line to copy data to
	 *
	 * @return boolean
	 **/
	function copyEvaluation($fromID, $toID = null, $fnum = null, $student = null, $user = null)
	{

		try
		{

			$query = 'SELECT * FROM #__emundus_evaluations ee WHERE ee.id = ' . $this->db->Quote($fromID);
			$this->db->setQuery($query);
			$fromDATA = $this->db->loadAssoc();

			$query = 'SELECT count(*) as total FROM #__emundus_evaluations ee WHERE ee.id = ' . $this->db->Quote($toID);
			$this->db->setQuery($query);
			$toDATA = $this->db->loadAssoc();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		// Check if we are updating an existing evaluation or adding a new one
		if ($toDATA['total'] == 1)
		{

			$query = "UPDATE #__emundus_evaluations as ee SET ";
			foreach ($fromDATA as $fromK => $fromV)
			{
				if ($fromK != "id" && $fromK != "time_date" && $fromK != "user" && $fromK != "fnum" && $fromK != "student_id" && isset($fromV))
					$query .= $fromK . " = " . $this->db->Quote($fromV) . ", ";
			}
			$query = rtrim($query, ", ");
			$query .= " WHERE id = " . $this->db->Quote($toID);

		}
		else
		{

			$config   = JFactory::getConfig();
			$timezone = new DateTimeZone($config->get('offset'));
			$now      = JFactory::getDate()->setTimezone($timezone);


			$query = "INSERT INTO #__emundus_evaluations ( ";
			foreach ($fromDATA as $fromK => $fromV)
			{
				$query .= $fromK . ", ";
			}
			$query = rtrim($query, ", ");
			$query .= ") VALUES ( ";

			foreach ($fromDATA as $fromK => $fromV)
			{

				if ($fromK == "id" || empty($fromV))
					$query .= "'', ";
				elseif ($fromK == "time_date")
					$query .= $this->db->Quote($now) . ", ";
				elseif ($fromK == "user")
					$query .= $this->db->Quote($user) . ", ";
				elseif ($fromK == "fnum")
					$query .= $this->db->Quote($fnum) . ", ";
				elseif ($fromK == "student_id")
					$query .= $this->db->Quote($student) . ", ";
				else
					$query .= $this->db->Quote($fromV) . ", ";

			}
			$query = rtrim($query, ", ");
			$query .= " )";
		}

		try
		{

			$this->db->setQuery($query);
			$this->db->execute();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	/**
	 *    Get evaluations for fnum done by a user
	 *
	 * @param   fnum        Application File number
	 *
	 * @return array
	 **/
	function getDecisionFnum($fnum)
	{
		$query    = $this->db->getQuery(true);
		$decision = array();

		try
		{
			$query->select('*')
				->from($this->db->quoteName('#__emundus_final_grade', 'efg'))
				->where($this->db->quoteName('efg.fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$decision = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add(JUri::getInstance() . ' :: -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $decision;
	}

	function getLettersTemplate($eligibility, $training)
	{

		//$query = "SELECT * FROM #__emundus_setup_letters WHERE status=".$eligibility." AND training=".$this->db->Quote($training);
		$query = "SELECT `l`.`id`, `l`.`header`, `l`.`body`, `l`.`footer`, `l`.`title`, `l`.`attachment_id`, `l`.`template_type`, `l`.`file`, `lrs`.`status` , `lrt`.`training`
					FROM jos_emundus_setup_letters as l
					left join jos_emundus_setup_letters_repeat_status as lrs on lrs.parent_id=l.id
					left join jos_emundus_setup_letters_repeat_training as lrt on lrt.parent_id=l.id
					WHERE `lrs`.`status` IN (" . $eligibility . ") AND `lrt`.`training` in (" . $this->db->Quote($training) . ")
					GROUP BY l.id";
		try
		{
			$this->db->setQuery($query);

			return $this->db->loadAssocList();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

	}

	function getLettersTemplateByID($id = null)
	{

		try
		{
			$query = "SELECT l.*, GROUP_CONCAT( DISTINCT(`lrs`.`status`) SEPARATOR ',' ) as `status`, CONCAT('\"',GROUP_CONCAT( DISTINCT(`lrt`.`training`) SEPARATOR '\",\"' ), '\"') as `training`
            			FROM #__emundus_setup_letters as l
            			left join #__emundus_setup_letters_repeat_status as lrs on lrs.parent_id=l.id
						left join #__emundus_setup_letters_repeat_training as lrt on lrt.parent_id=l.id ";

			if (isset($id) && !empty($id))
				$query .= 'WHERE l.id=' . $id;

			$this->db->setQuery($query);

			return $this->db->loadAssocList();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}
	}

	function getEvaluationFormByProgramme($code = null)
	{
		$form     = 0;
		$group_id = 0;

		if ($code === null)
		{
			if (version_compare(JVERSION, '4.0', '>'))
			{
				$session = Factory::getApplication()->getSession();
			}
			else
			{
				$session = Factory::getSession();
			}

			if ($session->has('filt_params'))
			{
				$filt_params = $session->get('filt_params');
				if (!empty($filt_params['programme']))
				{
					$code = $filt_params['programme'][0];
				}
			}
		}

		$group_id = 0;
		$query    = $this->db->getQuery(true);

		$query->select('fabrik_group_id')
			->from($this->db->quoteName('#__emundus_setup_programmes'))
			->where($this->db->quoteName('code') . ' LIKE ' . $this->db->quote($code));
		$this->db->setQuery($query);

		try
		{
			$group_id = $this->db->loadResult();
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		if (!empty($group_id))
		{
			$query->clear()
				->select('ff.form_id')
				->from('#__fabrik_formgroup AS ff')
				->where('ff.group_id IN (' . $this->db->quote($group_id) . ')');

			$this->db->setQuery($query);

			try
			{
				$form = $this->db->loadResult();
			}
			catch (Exception $e)
			{
				echo $e->getMessage();
				Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $form;
	}

	/**
	 * @param   code        code of the programme
	 *
	 * @return int
	 * Get Old Decision form ID By programme code
	 */
	function getDecisionFormByProgramme(?string $code = null): int
	{
		$decision_form = 0;

		if ($code === NULL) {
			$session = $this->app->getSession();
			if ($session->has('filt_params')) {
				$filt_params = $session->get('filt_params');
				if (!empty($filt_params['programme'])) {
					$code = $filt_params['programme'][0];
				}
			}
		}

		if (!empty($code)) {
			try {
				$query = $this->db->createQuery();
				$query->select('ff.form_id')
					->from('#__fabrik_formgroup ff')
					->where('ff.group_id IN (SELECT fabrik_decision_group_id FROM #__emundus_setup_programmes WHERE code like ' . $this->_db->quote($code) . ')')
					->where('ff.group_id <> \'\'');

				$this->db->setQuery($query);
				$decision_form = (int)$this->db->loadResult();
			} catch (Exception $e) {
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $decision_form;
	}

	/**
	 * Get evaluations form ID By programme code
	 *
	 * @param   code        code of the programme
	 *
	 * @return int        The fabrik ID for the admission form
	 */
	function getAdmissionFormByProgramme(?string $code = null): int
	{
		$admission_form_id = 0;

		if ($code === null) {
			$session = $this->app->getSession();
			if ($session->has('filt_params')) {
				$filt_params = $session->get('filt_params', []);
				if (!empty($filt_params['programme'])) {
					$code = $filt_params['programme'][0];
				}
			}
		}

		try {
			$query = $this->db->getQuery(true);

			$query->select('fabrik_admission_group_id')
				->from('#__emundus_setup_programmes')
				->where('code LIKE ' . $this->db->quote($code));

			$this->db->setQuery($query);
			$group_ids = $this->db->loadResult();

			if (!empty($group_ids)) {
				$group_ids = explode(',', $group_ids);
				$query->clear()
					->select($this->db->quoteName('ff.form_id'))
					->from($this->db->quoteName('#__fabrik_formgroup', 'ff'))
					->leftJoin($this->db->quoteName('jos_fabrik_lists', 'l') . ' ON ' . $this->db->quoteName('l.form_id') . ' = ' . $this->db->quoteName('ff.form_id'))
					->where('ff.group_id IN (' . $group_ids[0] . ')');

				$this->db->setQuery($query);
				$admission_form_id = (int)$this->db->loadResult();
			}
		} catch (Exception $e) {
			Log::add(JUri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $admission_form_id;
	}

	/**
	 * @deprecated since 2.1.0
	 *
	 * @param $fnums
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getEvaluationAverageByFnum($fnums)
	{
		$evaluations_average = array();
		$query               = $this->db->getQuery(true);

		$query->select('ROUND(AVG(overall),2) AS overall, fnum')
			->from($this->db->quoteName('#__emundus_evaluations'))
			->where($this->db->quoteName('fnum') . ' IN (' . implode(',', $fnums) . ') AND overall IS NOT NULL')
			->group($this->db->quoteName('fnum'));

		try
		{
			$this->db->setQuery($query);
			$evaluations_average = $this->db->loadAssocList('fnum', 'overall');
		}
		catch (Exception $e)
		{
			Log::add('Error getting evaluation averages : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $evaluations_average;
	}


	/**
	 * @param   array  $fnums
	 * @param   array  $step_ids
	 *
	 * @return array
	 */
	public function getEvaluationAverageBySteps(array $fnums, int $user_id, array $step_ids = []): array
	{
		$evaluations_average = [];

		if (!empty($fnums)) {
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new EmundusModelWorkflow();

			$steps = [];
			if (empty($step_ids)) {
				$steps = $m_workflow->getEvaluatorSteps($user_id);
			} else {
				foreach ($step_ids as $step_id) {
					$steps[] = $m_workflow->getStepData($step_id);
				}
			}

			$query = $this->db->createQuery();

			foreach ($steps as $step) {
				$average_element = $this->getEvaluationFormAverageElement($step);

				if (!empty($average_element)) {
					$query->clear()
						->select('ROUND(AVG(COALESCE(' . $this->db->quoteName('eval_table.' . $average_element['name']) . ', 0.00)),2) AS average, ecc.fnum')
						->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
						->leftJoin($this->db->quoteName($step->table, 'eval_table') . ' on eval_table.ccid = ecc.id')
						->where($this->db->quoteName('ecc.fnum') . ' IN (' . implode(',', $fnums) . ')')
						->group($this->db->quoteName('ecc.fnum'));

					try {
						$this->db->setQuery($query);
						$evaluations_average[$step->id] = $this->db->loadAssocList('fnum', 'average');
					} catch (Exception $e) {
						Log::add('Error getting evaluation averages : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
					}
				}
			}
		}

		return $evaluations_average;
	}

	/**
	 * @param   object  $step
	 *
	 * @return array
	 */
	private function getEvaluationFormAverageElement(object $step): array
	{
		$element = [];

		if (!empty($step->form_id)) {
			$query = $this->db->createQuery();

			$query->select('jfe.*')
				->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->where('jffg.form_id = ' . $step->form_id)
				->andWhere('jfe.published = 1')
				->andWhere('jfe.plugin = ' . $this->db->quote('average'));

			try {
				$this->db->setQuery($query);
				$avg_elements = $this->db->loadAssocList();

				foreach ($avg_elements as $avg_element) {
					$params = json_decode($avg_element['params'], true);

					if ($params['used_as_total'] == 1) {
						$element = $avg_element;
						break;
					}
				}
			} catch (Exception $e) {
				Log::add('Error getting evaluation form average element : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $element;
	}

	function getEvalsByFnums($fnums)
	{
		try
		{

			$query = 'SELECT * FROM #__emundus_evaluations ee WHERE ee.fnum IN ("' . implode('","', $fnums) . '") ORDER BY ee.id DESC';
			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}
	}

	/**
	 * @deprecated
	 * @param $id
	 *
	 * @return bool
	 */
	public function delevaluation($id)
	{
		$deleted = false;

		try
		{
			$query = $this->db->getQuery(true);

			$query->delete($this->db->qn('#__emundus_evaluations'))
				->where($this->db->qn('id') . ' = ' . $id);
			$this->db->setQuery($query);

			$deleted = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error in model/evaluation at query: ' . $query->__toString(), Log::ERROR, 'com_emundus');
		}

		return $deleted;
	}

	/**
	 * @param   string  $fnum
	 * @param   object  $step_data
	 * @param   int     $row_id
	 *
	 * @return bool
	 */
	public function deleteEvaluation(string $fnum, object $step_data, int $row_id, int $user_id) : bool
	{
		$deleted = false;

		if (!empty($fnum) && !empty($step_data->id) && !empty($row_id)) {
			$query = $this->db->getQuery(true);

			$query->select('evaluator')
				->from($this->db->quoteName($step_data->table))
				->where($this->db->quoteName('id') . ' = ' . $row_id);
			$this->db->setQuery($query);
			$evaluator = $this->db->loadResult();

			$query->clear()
				->delete($this->db->quoteName($step_data->table))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
				->andWhere($this->db->quoteName('step_id') . ' = ' . $step_data->id)
				->andWhere($this->db->quoteName('id') . ' = ' . $row_id);

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();

				if ($deleted) {
					$m_files = new EmundusModelFiles();
					$fnum_infos = $m_files->getFnumInfos($fnum);

					EmundusModelLogs::log($user_id, $fnum_infos['applicant_id'], $fnum, $step_data->action_id, 'd', 'COM_EMUNDUS_EVALUATIONS_DELETE', json_encode(['row_id' => $row_id, 'evaluator' => $evaluator]));
				}
			} catch (Exception $e) {
				Log::add('Error in model/evaluation at query: ' . $query->__toString(), Log::ERROR, 'com_emundus');
			}
		}

		return $deleted;
	}

	function getEvaluationById($id)
	{
		try
		{
			$query = 'SELECT * FROM #__emundus_evaluations ee WHERE ee.id = ' . $id;
			$this->db->setQuery($query);

			return $this->db->loadObject();

		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}
	}


	/**
	 * @param $fnum
	 *
	 * @return array|bool
	 */
	function getScore($fnum)
	{

		$fnumInfos = $this->_files->getFnumInfos($fnum);
		$query     = 'SELECT fe.id, fe.name, fe.label, fe.params, fl.db_table_name FROM #__fabrik_elements AS fe
				LEFT JOIN #__fabrik_groups AS fg ON fg.id = fe.group_id
				LEFT JOIN #__fabrik_formgroup AS ffg ON ffg.group_id = fg.id
				LEFT JOIN #__fabrik_forms AS ff ON ffg.form_id = ff.id 
				LEFT JOIN #__fabrik_lists as fl ON fl.form_id = ff.id
				WHERE fg.id IN (
					SELECT p.fabrik_group_id FROM #__emundus_setup_programmes AS p WHERE p.code LIKE ' . $this->db->quote($fnumInfos['training']) . '
				) AND fe.published = 1 AND fe.params NOT LIKE ' . $this->db->quote('%"comment":""%');
		$this->db->setQuery($query);
		try
		{
			$elements = $this->db->loadObjectList('id');
		}
		catch (Exception $e)
		{
			return false;
		}

		$query  = $this->db->getQuery(true);
		$return = [];
		foreach ($elements as $element)
		{

			$query->clear()
				->select($this->db->quoteName($element->name))
				->from($this->db->quoteName($element->db_table_name))
				->where('fnum LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);

			try
			{
				$value = $this->db->loadResult();
			}
			catch (Exception $e)
			{
				continue;
			}

			if (empty($value))
			{
				$value = 0;
			}

			// The highest possible value of the evaluation criteria is hidden in the comment.
			$max = json_decode($element->params)->comment;

			// Make an array containing all element names brought to 10.
			$return[$element->label] = ($value / $max) * 10;

		}

		return $return;
	}

	/// get distinct attachment
	public function getAttachmentByIds(array $ids): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_attachments'))
			->where($this->db->quoteName('#__emundus_setup_attachments.id') . 'IN (' . implode(',', $ids) . ')');

		$this->db->setQuery($query);
		try
		{
			return $this->db->loadAssocList();
		}
		catch (Exception $e)
		{
			return [];
		}
	}

	/// get letters by fnums
	public function getLettersByFnums($fnums, $attachments = false)
	{

		if (empty($fnums))
		{
			return false;
		}

		try
		{
			/// first --> from fnum --> get fnum info
			$_mFile     = new EmundusModelFiles();
			$_fnumArray = explode(',', $fnums);

			$_fnumsInfo = $_mFile->getFnumsInfos($_fnumArray);

			$_programs  = [];
			$_campaigns = [];
			$_status    = [];

			foreach ($_fnumsInfo as $fnum)
			{
				$_programs[]  = $fnum['training'];
				$_campaigns[] = $fnum['campaign_id'];
				$_status[]    = $fnum['step'];
			}

			$_letters = $this->getLettersByProgrammesStatusCampaigns($_programs, $_status, $_campaigns);

			if (!empty($_letters))
			{
				if ($attachments == true)
				{
					/// from $_letters --> get distinct attachment_id
					$_letter_attachment_ids = [];
					foreach ($_letters as $letter)
					{
						$_letter_attachment_ids[$letter->attachment_id] = $letter->attachment_id;
					}

					return $this->getAttachmentByIds($_letter_attachment_ids);
				}
				else
				{
					/// from $_letters -> det distinct letter id
					$_letter_ids = [];
					foreach ($_letters as $letter)
					{
						$_letter_ids[] = $letter->id;
					}

					return $_letter_ids;
				}
			}
			else
			{
				return [];
			}
		}
		catch (Exception $e)
		{
			return [];
		}
	}

	/// get letters by traininng and status
	public function getLettersByProgrammesStatusCampaigns($programs = array(), $status = array(), $campaigns = array()): array
	{
		$letters = [];

		$query = $this->db->getQuery(true);
		try
		{
			$query->select('jesl.*')
				->from($this->db->quoteName('#__emundus_setup_letters', 'jesl'))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_status', 'jeslrs') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrs.parent_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'jeslrt') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrt.parent_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_campaign', 'jeslrc') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrc.parent_id'))
				->where($this->db->quoteName('jeslrs.status') . ' IN (' . implode(',', $status) . ')')
				->andWhere($this->db->quoteName('jeslrt.training') . ' IN (' . implode(',', $this->db->quote($programs)) . ') OR ' . $this->db->quoteName('jeslrc.campaign') . ' IN (' . implode(',', $this->db->quote($campaigns)) . ') OR jesl.for_all = 1');

			$this->db->setQuery($query);

			$letters = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error in getLettersByProgrammesStatusCampaigns: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$letters = [];
		}

		return $letters;
	}

	/// get exactly letter id by fnum and template (32,33,34)
	public function getLetterTemplateForFnum($fnum, $templates = array()): array
	{
		$letters = [];

		if (!empty($fnum) && !empty($templates))
		{
			$query = $this->db->getQuery(true);

			$_mFile     = new EmundusModelFiles;
			$fnum_infos = $_mFile->getFnumInfos($fnum);

			if (!empty($fnum_infos))
			{
				$query->select('jesl.*')
					->from($this->db->quoteName('#__emundus_setup_letters', 'jesl'))
					->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_status', 'jeslrs') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrs.parent_id'))
					->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'jeslrt') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrt.parent_id'))
					->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_campaign', 'jeslrc') . ' ON ' . $this->db->quoteName('jesl.id') . ' = ' . $this->db->quoteName('jeslrc.parent_id'))
					->where($this->db->quoteName('jeslrs.status') . ' = ' . $fnum_infos['status'])
					->andWhere($this->db->quoteName('jeslrt.training') . ' = ' . $this->db->quote($fnum_infos['training']) . ' OR ' . $this->db->quoteName('jeslrc.campaign') . ' = ' . $this->db->quote($fnum_infos['id']) .  ' OR jesl.for_all = 1')
					->andWhere($this->db->quoteName('jesl.attachment_id') . ' IN (' . implode(',', $templates) . ')')
					->group($this->db->quoteName('jesl.id'))
					->order('id ASC');

				try
				{
					$this->db->setQuery($query);
					$letters = $this->db->loadObjectList();
				}
				catch (Exception $e)
				{
					$letters = [];
					Log::add('Error in getLetterTemplateForFnum: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $letters;
	}

	/// get affected letters by [fnums] and [templates]
	public function getLettersByFnumsTemplates($fnums = array(), $templates = array())
	{
		if (empty($fnums) || empty($templates))
		{
			return [];
		}
		/// from each fnum --> get fnum infos
		$letter_ids = [];
		$fnum_Array = explode(',', $fnums);

		foreach ($fnum_Array as $fnum)
		{
			$letter_ids[] = $this->getLetterTemplateForFnum($fnum, $templates);
		}

		return $letter_ids;
	}

	/// get uploaded files by document type and fnums
	public function getFilesByAttachmentFnums($attachment, $fnums = array())
	{
		if (empty($fnums) || empty($attachment))
		{
			return [];
		}
		$query = $this->db->getQuery(true);

		$query
			->select('#__emundus_uploads.*, #__emundus_setup_attachments.lbl, #__emundus_setup_attachments.value')
			->from($this->db->quoteName('#__emundus_uploads'))
			->leftJoin($this->db->quoteName('#__emundus_setup_attachments') . 'ON' . $this->db->quoteName('#__emundus_setup_attachments.id') . ' = ' . $this->db->quoteName('#__emundus_uploads.attachment_id'))
			->where($this->db->quoteName('#__emundus_uploads.attachment_id') . ' = ' . (int) $attachment)
			->andWhere($this->db->quoteName('#__emundus_uploads.fnum') . ' IN (' . implode(', ', $this->db->quote($fnums)) . ')');
		$this->db->setQuery($query);

		try
		{
			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			return [];
		}
	}

	public function generateLetters($fnums, $templates, $canSee, $showMode = null, $mergeMode = null, $force_replace_document = false, $user_id = null)
	{
		$query = $this->db->getQuery(true);

        if (empty($user_id)) {
            $user = $this->app->getIdentity();
        } else {
            $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
        }

		$eMConfig             = ComponentHelper::getParams('com_emundus');
		$replace_document     = $eMConfig->get('export_replace_doc', 0);
		if ($force_replace_document) {
			$replace_document = 1;
		}
		$generated_doc_name   = $eMConfig->get('generated_doc_name', "");
		$gotenberg_activation = $eMConfig->get('gotenberg_activation', 0);
		$escape_ampersand     = $eMConfig->get('generate_letter_escape_ampersand', 0);
		$whitespace_textarea  = $eMConfig->get('generate_letter_whitespace_textarea', 0);

		$tmp_path = JPATH_SITE . DS . 'tmp' . DS;
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/evaluation.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/emails.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/users.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/helpers/fabrik.php');
		require_once(JPATH_LIBRARIES . DS . 'emundus/fpdi.php');
		require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');
		$_mEval  = new EmundusModelEvaluation();
		$_mFile  = new EmundusModelFiles();
		$_mEmail = new EmundusModelEmails();
		$_mUsers = new EmundusModelUsers();

		$anonymize_data = EmundusHelperAccess::isDataAnonymized($user->id);

		$fnum_array = explode(',', $fnums);

		$res         = new stdClass();
		$res->status = true;
		$res->files  = [];

		foreach ($fnum_array as $fnum)
		{
			$generated_letters = $_mEval->getLetterTemplateForFnum($fnum, $templates);

			PluginHelper::importPlugin('emundus');
			PluginHelper::importPlugin('actionlog');

			$dispatcher = Factory::getApplication()->getDispatcher();
			$onCallEventHandler = new GenericEvent(
				'onCallEventHandler',
				[
					'onBeforeGenerateLetters',
					[
						'letters' => &$generated_letters,
						'fnum' => $fnum,
					]
				]
			);
			$dispatcher->dispatch('onCallEventHandler', $onCallEventHandler);

			$fnumInfo          = $_mFile->getFnumsTagsInfos([$fnum]);

			$applicant_path     = EMUNDUS_PATH_ABS . $fnumInfo[$fnum]['applicant_id'];
			$applicant_url      = JURI::base() . EMUNDUS_PATH_REL . $fnumInfo[$fnum]['applicant_id'] . DS;
			$applicant_tmp_path = EMUNDUS_PATH_ABS . 'tmp' . DS . $fnumInfo[$fnum]['applicant_name'] . '_' . $fnumInfo[$fnum]['applicant_id'];
			if (!file_exists($applicant_path))
			{
				mkdir($applicant_path, 0755, true);
			}
			if (!file_exists($applicant_tmp_path))
			{
				mkdir($applicant_tmp_path, 0755, true);
			}

			$post    = [
				'TRAINING_CODE'      => $fnumInfo[$fnum]['campaign_code'],
				'TRAINING_PROGRAMME' => $fnumInfo[$fnum]['training_programme'],
				'CAMPAIGN_LABEL'     => $fnumInfo[$fnum]['campaign_label'],
				'CAMPAIGN_YEAR'      => $fnumInfo[$fnum]['campaign_year'],
				'USER_NAME'          => $fnumInfo[$fnum]['applicant_name'],
				'USER_EMAIL'         => $fnumInfo[$fnum]['applicant_email'],
				'FNUM'               => $fnum
			];
			$const   = array('user_id' => $user->id, 'user_email' => $user->email, 'user_name' => $user->name, 'current_date' => date('d/m/Y', time()));
			$special = ['user_dob_age', 'evaluation_radar'];

			foreach ($generated_letters as $key => $letter)
			{
				$attachInfo = $_mFile->getAttachmentInfos($letter->attachment_id);

				if ($replace_document == 1)
				{
					$refreshQuery = $this->db->getQuery(true);

					$refreshQuery->delete($this->db->quoteName('#__emundus_uploads'))
						// TODO: We have to check another param if this attachment_id is used for an applicant upload
						->where($this->db->quoteName('attachment_id') . ' = ' . $attachInfo['id'])
						->andWhere($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));

					if (!$force_replace_document) {
                        $refreshQuery->andWhere('DATE(' . $this->db->quoteName('timedate') . ') = CURRENT_DATE() OR ' . $this->db->quoteName('user_id') . ' <> ' . $this->db->quote($fnumInfo[$fnum]['applicant_id']));
					}

					$this->db->setQuery($refreshQuery);
					$this->db->execute();
				}

				$type        = $letter->template_type;
				$letter_file = JPATH_SITE . $letter->file;

				switch ((int) $type)
				{
					case 1:
						$ext = pathinfo($letter_file)['extension'];
						break;
					case 2:
						$ext = 'pdf';
						break;
					case 3:
						$ext = 'docx';
						break;
					case 4:
						$ext = 'xlsx';
						break;
					default:
						$ext = 'pdf';
				}

				$rand = rand(0, 1000000);
				if (!$anonymize_data)
				{
					if (!empty($generated_doc_name))
					{
						require_once(JPATH_SITE . DS . 'components/com_emundus/models/checklist.php');
						$m_checklist = new EmundusModelChecklist;
						$filename    = $m_checklist->formatFileName($generated_doc_name, $fnum, $post);
					}
					else
					{
						$filename = $this->sanitize_filename($fnumInfo[$fnum]['applicant_name']);
					}
					$filename = $filename . $attachInfo['lbl'] . '-' . md5($rand . time()) . '.' . $ext;
				}
				else
				{
					$filename = $this->sanitize_filename($fnum) . $attachInfo['lbl'] . '.' . $ext;
				}

				$dest     = $applicant_path . DS . $filename;
				$dest_tmp = $applicant_tmp_path . DS . $filename;
				if (file_exists($dest) || file_exists($dest_tmp))
				{
					unlink($dest);
					unlink($dest_tmp);

					$query->clear()
						->delete($this->db->quoteName('#__emundus_uploads'))
						->where($this->db->quoteName('filename') . ' LIKE ' . $this->db->quote($filename))
						->andWhere('DATE(timedate) = CURRENT_DATE()')
						->andWhere($this->db->quoteName('attachment_id') . ' = ' . $attachInfo['id'])
						->andWhere($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				/**
				 * 1: Generate simple file without conversion
				 * 2: Generate PDF file from HTML
				 * 3: Generate DOCX (can be converted to PDF with Gotenberg)
				 * 4: Generate XLSX file
				 */
				switch ((int) $type)
				{
					case 1:
						if (file_exists($letter_file))
						{
							if (copy($letter_file, $dest) && copy($letter_file, $dest_tmp))
							{
								$upId = $_mFile->addAttachment($fnum, $filename, $user->id, $fnumInfo[$fnum]['campaign_id'], $letter->attachment_id, '', $canSee);

								$res->files[] = array('filename' => $filename, 'upload' => $upId, 'url' => $applicant_url, 'type' => $attachInfo['id']);
							}
						}
						else
						{
							$res->status = false;
							$res->msg    = JText::_('COM_EMUNDUS_LETTERS_ERROR_CANNOT_GENERATE_FILE');
						}
						break;

					case 2:
						if (isset($fnumInfo))
						{
							$tags = $_mEmail->setTags($fnumInfo[$fnum]['applicant_id'], $post, $fnum, '', $letter->title . $letter->body . $letter->footer);

							$pdf_margins      = $eMConfig->get('generate_letter_pdf_margins', '5,20,5');
							$display_header   = $eMConfig->get('generate_letter_display_header', 1);
							$display_footer   = $eMConfig->get('generate_letter_display_footer', 1);
							$use_default_font = $eMConfig->get('generate_letter_use_default_font', 0);
							$font             = $eMConfig->get('generate_letter_font', 'helvetica');
							$font_size        = $eMConfig->get('generate_letter_font_size', 10);

							$htmldata = '';
							if ($display_header == 1)
							{
								$htmldata = $letter->header;
							}

							$htmldata .= $_mEmail->setTagsFabrik($letter->body, array($fnum));
							$htmldata = preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $htmldata))));

							$wrappedHtml = '<?xml encoding="UTF-8">' . $htmldata; // Force encoding
							$doc = new DOMDocument();
							$doc->loadHTML($wrappedHtml, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

							$images = $doc->getElementsByTagName('img');

							foreach ($images as $img) {
								$src = $img->getAttribute('src');

								// Only process if it's not already base64
								if (strpos($src, 'data:image') === false) {
									// Step 3: Get image data
									if (filter_var($src, FILTER_VALIDATE_URL)) {
										$imageData = @file_get_contents($src); // Remote image
									} else {
										$path = JPATH_SITE . '/' . ltrim($src, '/'); // Local image path
										$imageData = @file_get_contents($path);
									}

									if ($imageData !== false) {
										$finfo = new finfo(FILEINFO_MIME_TYPE);
										$mime = $finfo->buffer($imageData);

										$base64 = 'data:' . $mime . ';base64,' . base64_encode($imageData);
										$img->setAttribute('src', $base64);
									}
								}
							}

							$htmldata = $doc->saveHTML();
							$htmldata = html_entity_decode($htmldata, ENT_QUOTES | ENT_HTML5, 'UTF-8');

							$htmldata = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $htmldata);

							if ($display_footer == 1)
							{
								$htmldata .= $letter->footer;
							}

							// Save to an html tmp file
							$html_tmp = $applicant_tmp_path . DS . 'index.html';
							file_put_contents($html_tmp, $htmldata);
							// Save file

							require_once(JPATH_SITE . DS . 'components/com_emundus/models/export.php');
							$m_Export = new EmundusModelExport();

							$dest_tmp = str_replace('.pdf', '', $dest);

							try
							{
								if($gotenberg_activation == 1)
								{
									$gotenberg_results = $m_Export->toPdf($html_tmp, $dest_tmp, 'html', $fnum);
									if ($gotenberg_results->status)
									{
										copy($gotenberg_results->file, $dest);
									}
								}
								else {
									$options = new Options();
									//$options->set('defaultFont', 'helvetica');
									$options->set('isPhpEnabled', true);
									$dompdf = new Dompdf($options);
									$dompdf->loadHtml($htmldata);
									$dompdf->render();
									file_put_contents($dest, $dompdf->output());
								}
							}
							catch (Exception $e)
							{
								Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

								return false;
							}

							$upId = $_mFile->addAttachment($fnum, $filename, $user->id, $fnumInfo[$fnum]['campaign_id'], $letter->attachment_id, '', $canSee);         ////

							$res->files[] = array('filename' => $filename, 'upload' => $upId, 'url' => $applicant_url, 'type' => $attachInfo['id']);
						}
						break;

					case 3:
						if (file_exists($letter_file))
						{
							require_once(JPATH_SITE . DS . 'components/com_emundus/models/export.php');
							$m_Export = new EmundusModelExport();

							try
							{
								$phpWord = new PhpWord();
								if ($escape_ampersand)
								{
									Settings::setOutputEscapingEnabled(true);
								}
								$preprocess = new TemplateProcessor($letter_file);
								$tags       = $preprocess->getVariables();

								$fabrik_aliases = EmundusHelperFabrik::getAllFabrikAliases();

								$idFabrik  = [];
								$fabrikTags = array();
								$aliasFabrik  = [];
								$setupTags = [];
								foreach ($tags as $i => $val)
								{
									$tag = new TagEntity($val, null, [], TagType::FABRIK);
									if(!empty($tag->getName()))
									{
										if (is_numeric($tag->getName())) {
											$idFabrik[] = $tag->getName();
											$fabrikTags[] = $tag;
										}
										elseif(in_array($tag->getName(), $fabrik_aliases))
										{
											$elt = EmundusHelperFabrik::getElementsByAlias($tag->getName());

											if(!empty($elt[0]))
											{
												$idFabrik[] = $elt[0]->id;
												$aliasFabrik[$tag->getName()] = $elt[0]->id;
												$fabrikTags[] = $tag;
											}
										}
										else
										{
											if (str_contains($tag->getName(), 'IMG_'))
											{
												// If the tag is an image, we need to extract the image name
												$imgName = explode(':', $tag->getName());
												$tag->setName($imgName[0]);
											}

											$setupTags[] = $tag;
										}
									}
								}

								$fabrikElts = [];
								if (!empty($idFabrik))
								{
									$fabrikElts = $_mFile->getValueFabrikByIds($idFabrik);
								}

                                if (!empty($fabrikElts)) {
                                    $h_files = new EmundusHelperFiles();
                                    $encrypted_tables = $h_files->getEncryptedTables();
                                    $fabrikValues = [];
                                    $textarea_elements = [];

                                    // TODO: Move this to a global method by passing the fabrik element
                                    foreach ($fabrikElts as $elt)
                                    {
                                        $params      = json_decode($elt['params']);
                                        $groupParams = json_decode($elt['group_params']);

                                        if (!empty($groupParams) && $groupParams->repeat_group_button == 1)
                                        {
                                            $fabrikValues[$elt['id']] = $_mFile->getFabrikValueRepeat($elt, [$fnum], $params, true);
                                        }
                                        else if ($elt['plugin'] === 'databasejoin')
                                        {
                                            $fabrikValues[$elt['id']] = $_mFile->getFabrikValueRepeat($elt, [$fnum], $params, $groupParams->repeat_group_button == 1);
                                        }
                                        else if ($elt['plugin'] == 'date')
                                        {
                                            $fabrikValues[$elt['id']] = $_mFile->getFabrikValue([$fnum], $elt['db_table_name'], $elt['name'], $params->date_form_format);
                                        }
                                        else if ($elt['plugin'] == 'jdate')
                                        {
                                            $fabrikValues[$elt['id']] = $_mFile->getFabrikValue([$fnum], $elt['db_table_name'], $elt['name'], $params->jdate_form_format);
                                        }
                                        else {
                                            $fabrikValues[$elt['id']] = $_mFile->getFabrikValue([$fnum], $elt['db_table_name'], $elt['name']);
                                        }

                                        if ($elt['plugin'] == "checkbox" || $elt['plugin'] == "dropdown" || $elt['plugin'] == "radiobutton")
                                        {
                                            foreach ($fabrikValues[$elt['id']] as $fnum => $val)
                                            {
                                                if ($elt['plugin'] == "checkbox" || (!empty($params->multiple) && $params->multiple == 1))
                                                {
                                                    $val = json_decode($val['val']);
                                                }
                                                else
                                                {
                                                    $val = explode(',', $val['val']);
                                                }

                                                if (is_array($val) && count($val) > 0)
                                                {
                                                    foreach ($val as $k => $v)
                                                    {
                                                        $index   = array_search($v, $params->sub_options->sub_values);
	                                                    if ($index !== false)
	                                                    {
		                                                    $val[$k] = Text::_($params->sub_options->sub_labels[$index]);
	                                                    }
                                                    }
                                                    $fabrikValues[$elt['id']][$fnum]['val'] = implode("\n", $val);
                                                }
                                                else
                                                {
                                                    $fabrikValues[$elt['id']][$fnum]['val'] = "";
                                                }
                                            }

                                        }
                                        elseif ($elt['plugin'] == "birthday")
                                        {
                                            foreach ($fabrikValues[$elt['id']] as $fnum => $val)
                                            {
                                                $val = explode(',', $val['val']);
                                                foreach ($val as $k => $v)
                                                {
                                                    if (!empty($v))
                                                    {
                                                        $val[$k] = date($params->details_date_format, strtotime($v));
                                                    }
                                                }
                                                $fabrikValues[$elt['id']][$fnum]['val'] = implode(",", $val);
                                            }

                                        }
                                        elseif ($elt['plugin'] == 'textarea' && $whitespace_textarea == 1)
                                        {
                                            $formatted_text = explode('<br />', nl2br($fabrikValues[$elt['id']][$fnum]['val']));
                                            $inline = new \PhpOffice\PhpWord\Element\TextRun();
                                            foreach ($formatted_text as $key => $text) {
                                                if (!empty($text)) {
                                                    if ($key > 0) {
                                                        $inline->addTextBreak();
                                                    }
                                                    $inline->addText(trim($text), array('name' => 'Arial'));
                                                }
                                            }
                                            $fabrikValues[$elt['id']][$fnum]['val'] = $inline;
                                            $fabrikValues[$elt['id']][$fnum]['complex_data'] = true;
                                        }
                                        elseif ($elt['plugin'] == 'emundus_phonenumber')
                                        {
                                            $fabrikValues[$elt['id']][$fnum]['val'] = substr($fabrikValues[$elt['id']][$fnum]['val'], 2, strlen($fabrikValues[$elt['id']][$fnum]['val']));
                                        }
                                        elseif ($elt['plugin'] == 'yesno')
                                        {
                                            $fabrikValues[$elt['id']][$fnum]['val'] = $fabrikValues[$elt['id']][$fnum]['val'] == '1' ? JText::_('JYES') : JText::_('JNO');
                                        }
                                        elseif ($elt['plugin'] == 'cascadingdropdown')
                                        {
                                            foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
                                                $fabrikValues[$elt['id']][$fnum]['val'] = $_mEmail->getCddLabel($elt, $val['val']);
                                            }
                                        }
                                        elseif ($elt['plugin'] == 'iban')
                                        {
                                            if (is_array($fabrikValues[$elt['id']][$fnum]['val']))
                                            {
                                                foreach ($fabrikValues[$elt['id']][$fnum]['val'] as $key => $val)
                                                {
                                                    $fabrikValues[$elt['id']][$fnum]['val'][$key] = EmundusHelperFabrik::decryptDatas($val);
                                                }
                                            }
                                            else
                                            {
                                                $fabrikValues[$elt['id']][$fnum]['val'] = EmundusHelperFabrik::decryptDatas($fabrikValues[$elt['id']][$fnum]['val']);
                                            }
                                        }

                                        if (!isset($fabrikValues[$elt['id']][$fnum]['complex_data']))
                                        {
                                            $fabrikValues[$elt['id']][$fnum]['complex_data'] = false;
                                        }

                                        if (!empty($encrypted_tables)) {
                                            if (in_array($elt['db_table_name'], $encrypted_tables)) {
                                                if (is_array($fabrikValues[$elt['id']][$fnum]['val'])) {
                                                    foreach ($fabrikValues[$elt['id']][$fnum]['val'] as $key => $val) {
                                                        $fabrikValues[$elt['id']][$fnum]['val'][$key] = EmundusHelperFabrik::decryptDatas($val);
                                                    }
                                                } else {
                                                    $fabrikValues[$elt['id']][$fnum]['val'] = EmundusHelperFabrik::decryptDatas($fabrikValues[$elt['id']][$fnum]['val']);
                                                }
                                            }
                                        }
                                    }
                                }

                                $preprocess = new TemplateProcessor($letter_file);
                                if (isset($fnumInfo[$fnum]))
                                {
	                                foreach ($fabrikTags as $fabrikTag) {
		                                if(!empty($fabrikTag->getModifiers()))
		                                {
			                                $patternKey = array_search($fabrikTag->getName(), $fabrikValues);
			                                if(isset($fabrikValues[$fabrikTag->getName()][$fnum]['val']))
			                                {
				                                $fabrikTag->setValue($fabrikValues[$fabrikTag->getName()][$fnum]['val']);

				                                $fabrikValues[$fabrikTag->getFullName()][$fnum]['val'] = $fabrikTag->getValueModified();
			                                }
		                                }

										$fabrikTagFullName = $fabrikTag->getFullName();

										if (in_array($fabrikTagFullName, $textarea_elements) && isset($fabrikValues[$fabrikTagFullName][$fnum]['val'])) {
											$html = $fabrikValues[$fabrikTagFullName][$fnum]['val'];
											$section = $phpWord->addSection();
											\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
											$containers = $section->getElements();
											$clone = $preprocess->cloneBlock('textarea_' . $fabrikTagFullName, count($containers), true, true);

											for($i = 0; $i < count($containers); $i++) {
												$complex_block = $preprocess->setComplexBlock($fabrikTagFullName . '#' . ($i+1), $containers[$i]);
											}
										} else if(!empty($fabrikValues[$fabrikTagFullName][$fnum]['complex_data'])) {
											$preprocess->setComplexValue($fabrikTagFullName, $fabrikValues[$fabrikTagFullName][$fnum]['val']);
										} else {
											if(in_array($fabrikTagFullName, array_keys($aliasFabrik)) && isset($fabrikValues[$aliasFabrik[$fabrikTagFullName]][$fnum]['val']))
											{
												$value = str_replace('\n', ', ', $fabrikValues[$aliasFabrik[$fabrikTagFullName]][$fnum]['val']);
											}
											else if(isset($fabrikValues[$fabrikTagFullName][$fnum]['val'])) {
												$value = str_replace('\n', ', ', $fabrikValues[$fabrikTagFullName][$fnum]['val']);
											}
											else {
												$value = '';
											}

											$preprocess->setValue($fabrikTagFullName, $value);
										}
                                    }

                                    $tags = $_mEmail->setTagsWord($fnumInfo[$fnum]['applicant_id'], ['FNUM' => $fnum], $fnum, '');

                                    foreach ($setupTags as $tag)
                                    {
                                        $lowerTag = strtolower($tag->getName());

                                        if (str_starts_with($tag->getName(), 'CONTAINER_')) // this is used for html blocks later
                                        {
                                            continue;
                                        }

                                        if (array_key_exists($lowerTag, $const))
                                        {
											$tag->setValue($const[$lowerTag]);

	                                        $preprocess->setValue($tag->getFullName(), $tag->getValueModified());
                                        }
                                        elseif (in_array($lowerTag, $special))
                                        {
                                            switch ($lowerTag)
                                            {

                                                // dd-mm-YYYY (YY)
                                                case 'user_dob_age':
                                                    $birthday = $_mFile->getBirthdate($fnum, 'd/m/Y', true);
													$tag->setValue($birthday->date . ' (' . $birthday->age . ')');
                                                    break;

                                                default:
													$tag->setValue('');
                                                    break;
                                            }

	                                        $preprocess->setValue($tag->getFullName(), $tag->getValueModified());
                                        }
                                        elseif (!empty($fnumInfo[$fnum][$lowerTag]))
                                        {
											$tag->setValue($fnumInfo[$fnum][$lowerTag]);

											$preprocess->setValue($tag->getFullName(), $tag->getValueModified());
                                        }
                                        else
                                        {
                                            $i = 0;
                                            foreach ($tags['patterns'] as $value)
                                            {
                                                if ($value == $tag->getName())
                                                {
                                                    $tag->setValue($tags['replacements'][$i]);
                                                    break;
                                                }
                                                $i++;
                                            }

                                            if (str_ends_with($tag->getName(), '_BLOCK'))
                                            {
                                                $html    = $tag->getValue();
                                                $section = $phpWord->addSection();

                                                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
                                                $containers = $section->getElements();
                                                $clone      = $preprocess->cloneBlock('CONTAINER_' . $tag->getName(), count($containers), true, true);

                                                for ($j = 0; $j < count($containers); $j++)
                                                {
                                                    $preprocess->setComplexBlock($tag->getName() . '#' . ($j + 1), $containers[$j]);
                                                }
                                            }
                                            else
                                            {
                                                if (str_contains($tag->getName(), 'IMG_'))
                                                {
                                                    $preprocess->setImageValue($tag->getName(), $tag->getValue());
                                                }
                                                else
                                                {
                                                    $preprocess->setValue($tag->getFullName(), $tag->getValueModified());
                                                }
                                            }
                                        }
                                    }

                                    $preprocess->saveAs($dest);
                                    if ($gotenberg_activation == 1 && $letter->pdf == 1)
                                    {
                                        $dest_pdf     = str_replace('.docx', '', $dest);
                                        $dest_tmp_pdf = str_replace('.docx', '.pdf', $dest_tmp);
                                        $filename     = str_replace('.docx', '.pdf', $filename);

                                        try
                                        {
                                            $gotenberg_results = $m_Export->toPdf($dest, $dest_pdf, 'docx', $fnum);
                                            if ($gotenberg_results->status)
                                            {
                                                copy($gotenberg_results->file, $dest_tmp_pdf);
                                                unlink($dest);
                                            }
                                        }
                                        catch (Exception $e)
                                        {
                                            Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

                                            return false;
                                        }
                                    }
                                    else
                                    {
                                        copy($dest, $dest_tmp);
                                    }

                                    $upId         = $_mFile->addAttachment($fnum, $filename, $user->id, $fnumInfo[$fnum]['campaign_id'], $letter->attachment_id, '', $canSee);
                                    $res->files[] = array('filename' => $filename, 'upload' => $upId, 'url' => $applicant_url, 'type' => $attachInfo['id']);
                                }
							}
							catch (Exception $e)
							{
								$res->status = false;
								$res->msg    = Text::_("AN_ERROR_OCURRED") . ':' . $e->getMessage();
							}
						}
						else
						{
							$res->status = false;
							$res->msg    = Text::_('COM_EMUNDUS_LETTERS_ERROR_CANNOT_GENERATE_FILE');
						}
						break;

					case 4:
						if (file_exists($letter_file))
						{
							$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($letter_file);
							$reader        = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

							$reader->setIncludeCharts(true);
							$spreadsheet = $reader->load($letter_file);

							if (isset($fnumInfo[$fnum]))
							{
								$preprocess = $spreadsheet->getAllSheets(); //Search in each sheet of the workbook

								foreach ($preprocess as $sheet)
								{
									foreach ($sheet->getRowIterator() as $row)
									{
										$cellIterator = $row->getCellIterator();
										foreach ($cellIterator as $cell)
										{

											$cell->getValue();

											$regex = '/\$\{(.*?)}|\[(.*?)]/';
											preg_match_all($regex, $cell, $matches);

											$idFabrik  = array();
											$setupTags = array();

											foreach ($matches[1] as $i => $val)
											{
												$tag = strip_tags($val);

												if (is_numeric($tag))
												{
													$idFabrik[] = $tag;
												}
												else
												{
													$setupTags[] = $tag;
												}
											}

											if (!empty($idFabrik))
											{
												$fabrikElts = $_mFile->getValueFabrikByIds($idFabrik);
											}
											else
											{
												$fabrikElts = array();
											}

											require_once(JPATH_SITE . DS . 'components/com_emundus/controllers/files.php');
											$_cFiles = new EmundusControllerFiles;

											$fabrikValues = $_cFiles->getValueByFabrikElts($fabrikElts, [$fnum]);

											foreach ($setupTags as $tag)
											{
												$val      = "";
												$lowerTag = strtolower($tag);

												if (array_key_exists($lowerTag, $const))
												{
													$cell->setValue($const[$lowerTag]);
												}
												elseif (in_array($lowerTag, $special))
												{
													switch ($lowerTag)
													{
														case 'user_dob_age':
															$birthday = $_mFile->getBirthdate($fnum, 'd/m/Y', true);
															$cell->setValue($birthday->date . ' (' . $birthday->age . ')');
															break;
														default:
															$cell->setValue('');
															break;
													}
												}
												elseif (!empty(@$fnumInfo[$fnum][$lowerTag]))
												{
													$cell->setValue(@$fnumInfo[$fnum][$lowerTag]);
												}
												else
												{
													$tags = $_mEmail->setTagsWord(@$fnumInfo[$fnum]['applicant_id'], ['FNUM' => $fnum], $fnum, '');
													$i    = 0;
													foreach ($tags['patterns'] as $value)
													{
														if ($value == $tag)
														{
															$val = $tags['replacements'][$i];
															break;
														}
														$i++;
													}
													$cell->setValue(htmlspecialchars($val, ENT_NOQUOTES));
												}
											}
											foreach ($idFabrik as $id)
											{
												if (isset($fabrikValues[$id][$fnum]))
												{
													$value = str_replace('\n', ', ', $fabrikValues[$id][$fnum]['val']);
													$cell->setValue(htmlspecialchars($value, ENT_NOQUOTES));
												}
												else
												{
													$cell->setValue('');
												}
											}
										}
									}
								}

								$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
								$writer->setIncludeCharts(true);
								$writer->save($dest);

								copy($dest, $dest_tmp);
								$upId         = $_mFile->addAttachment($fnum, $filename, $user->id, $fnumInfo[$fnum]['campaign_id'], $letter->attachment_id, '', $canSee);
								$res->files[] = array('filename' => $filename, 'upload' => $upId, 'url' => $applicant_url, 'type' => $attachInfo['id']);
							}
						}
						else
						{
							$res->status = false;
							$res->msg    = JText::_('COM_EMUNDUS_LETTERS_ERROR_CANNOT_GENERATE_FILE');
						}
						break;
				}

				if ($res->status)
				{
					$logs_params = ['created' => ['filename' => $letter->title]];
					EmundusModelLogs::log($user->id, (int)$fnumInfo[$fnum]['applicant_id'], $fnum, 27, 'c', 'COM_EMUNDUS_ACCESS_LETTERS', json_encode($logs_params, JSON_UNESCAPED_UNICODE));
				}
			}
		}

		$fnumsInfos = $_mFile->getFnumsInfos($fnum_array);

		$res->zip_data_by_candidat     = [];
		$res->zip_all_data_by_candidat = [];
		$res->zip_all_data_by_document = [];

		$applicant_id = [];
		foreach ($fnumsInfos as $key => $value)
		{
			$applicant_id[] = $value['applicant_id'];
		}
		$applicant_id        = array_unique(array_filter($applicant_id));
		$res->affected_users = count($applicant_id);

		/**
		 * showMode
		 * 0: Group by applicants
		 * 1: Group by attachment type
		 *
		 * mergeMode
		 * 0: No merge
		 * 1: Merge letters into one PDF
		 */
		if ($showMode === 0)
		{
			foreach ($applicant_id as $uid)
			{
				$user_info = $_mUsers->getUsersById($uid);

				$applicant_tmp_path  = EMUNDUS_PATH_ABS . 'tmp' . DS . $user_info[0]->name . '_' . $user_info[0]->id;
				$applicant_tmp_files = glob($applicant_tmp_path . DS . '*');

				if ($mergeMode === 0)
				{
					$_zipName = $user_info[0]->name . '_' . $user_info[0]->id . '_' . date("Y-m-d") . '_' . '.zip';
					if (file_exists($tmp_path . $_zipName))
					{
						unlink($tmp_path . $_zipName);
					}

					$mergeZipAllName = date("Y-m-d_H-i") . '-total-by-applicants';
					$mergeZipAllPath = $tmp_path . $mergeZipAllName;
					if (!file_exists($mergeZipAllPath))
					{
						mkdir($mergeZipAllPath, 0755, true);
					}

					if (sizeof($applicant_tmp_files) > 0)
					{
						if ($replace_document == 0)
						{
							$keepFiles = [];
							foreach ($res->files as $_f)
							{
								$keepFiles[] = $applicant_tmp_path . DS . $_f['filename'];
								if (!file_exists($applicant_tmp_path . DS . $_f['filename']))
								{
									$index = array_search($applicant_tmp_path . DS . $_f['filename'], $keepFiles);
									unset($keepFiles[$index]);

									unlink($applicant_tmp_path . DS . $_f['filename']);
								}
							}

							$diffFiles = array_diff($applicant_tmp_files, $keepFiles);
							foreach ($diffFiles as $df)
							{
								unlink($df);
							}
						}

						$this->ZipLetter($applicant_tmp_path, $tmp_path . $_zipName, 'true');
						$this->copy_directory($applicant_tmp_path . DS, $mergeZipAllPath . DS . $user_info[0]->name . '_' . $user_info[0]->id);
					}

					$this->ZipLetter($mergeZipAllPath, $mergeZipAllPath . '.zip', true);                       // zip this new file

					if (sizeof($applicant_tmp_files) > 0)
					{
						$res->zip_data_by_candidat[] = array('applicant_id' => $uid, 'applicant_name' => $user_info[0]->name, 'zip_url' => DS . 'tmp/' . $_zipName);
					}
				}

				if ($mergeMode === 1)
				{
					$mergeDirName = $user_info[0]->name . '_' . $user_info[0]->id . '--merge';
					$mergeDirPath = $tmp_path . $mergeDirName;
					if (!file_exists($mergeDirPath))
					{
						mkdir($mergeDirPath, 0755, true);
					}

					$mergeZipAllName = date("Y-m-d_H-i") . '--merge-total-by-candidats';
					$mergeZipAllPath = $tmp_path . $mergeZipAllName;
					if (!file_exists($mergeZipAllPath))
					{
						mkdir($mergeZipAllPath, 0755, true);
					}

					$pdf_files = array();

					if (sizeof($applicant_tmp_files) > 0 and $replace_document == 0)
					{
						$keepFiles = [];
						foreach ($res->files as $_f)
						{
							$keepFiles[] = $applicant_tmp_path . DS . $_f['filename'];
							if (!file_exists($applicant_tmp_path . DS . $_f['filename']))
							{
								$index = array_search($applicant_tmp_path . DS . $_f['filename'], $keepFiles);
								unset($keepFiles[$index]);
								unlink($applicant_tmp_path . DS . $_f['filename']);     // remove fake files
							}
						}
						$diffFiles = array_diff($applicant_tmp_files, $keepFiles);
						foreach ($diffFiles as $df)
						{
							unlink($df);
						}
						// re-update $fileList
						$applicant_tmp_files = glob($applicant_tmp_path . DS . '*');
					}

					foreach ($applicant_tmp_files as $filename)
					{
						$_name           = explode('/', $filename);
						$_name           = end($_name);
						$_file_extension = pathinfo($filename)['extension'];

						if ($_file_extension == "pdf")
						{
							$pdf_files[] = $filename;
						}
						else
						{
							copy($filename, $mergeDirPath . DS . $_name);
						}
					}

					if (count($pdf_files) >= 1)
					{
						$mergePdfName = $mergeDirPath . DS . $user_info[0]->name . '--merge.pdf';
						if (file_exists($mergePdfName, 'F'))
						{
							unlink($mergePdfName);
						}
						$pdf = new ConcatPdf();
						$pdf->setFiles($pdf_files);
						$pdf->concat();
						$pdf->Output($mergePdfName, 'F');
					}

					$_mergeZipName = $mergeDirName . '_' . date("Y-m-d") . '.zip';

					$_mergeZipPath = $tmp_path . $_mergeZipName;
					$this->ZipLetter($mergeDirPath, $_mergeZipPath, true);

					$mergeFiles = glob($mergeDirPath . DS . '*');

					if (sizeof($mergeFiles) > 0)
					{
						$this->copy_directory($mergeDirPath, $mergeZipAllPath . DS . $mergeDirName);
					}
					$this->ZipLetter($mergeZipAllPath, $mergeZipAllPath . '.zip', true);

					$delete_untotal_files = glob($mergeDirPath . DS . '*');
					foreach ($delete_untotal_files as $_file)
					{
						if (is_file($_file))
						{
							unlink($_file);
						}
					}
					rmdir($mergeDirPath);

					if (sizeof($mergeFiles) > 0)
					{
						$res->zip_data_by_candidat[] = array('applicant_id' => $uid, 'applicant_name' => $user_info[0]->name, 'merge_zip_url' => DS . 'tmp/' . $_mergeZipName);
					}
				}
			}

			if (!empty($mergeZipAllPath))
			{
				$_deleteFolders = glob($mergeZipAllPath . DS . '*');
				foreach ($_deleteFolders as $_deleteFolder)
				{
					$this->deleteAll($_deleteFolder);
				}
				rmdir($mergeZipAllPath);
				$res->zip_all_data_by_candidat = DS . 'tmp/' . $mergeZipAllName . '.zip';
			}
		}
		elseif ($showMode === 1)
		{
			$raw = [];
			foreach ($res->files as $rf)
			{
				$raw[] = $rf['filename'];
			}

			$res->letter_dir = [];
			$zip_dir         = [];

			$zip_All_Name = date("Y-m-d_H-i") . '-total-by-documents';
			$zip_All_Path = $tmp_path . $zip_All_Name;

			$zip_All_Merge_Name = date("Y-m-d_H-i") . '--merge-total-by-documents';
			$zip_All_Merge_Path = $tmp_path . $zip_All_Merge_Name;

			if ($mergeMode === 0)
			{
				if (!file_exists($zip_All_Path))
				{
					mkdir($zip_All_Path, 0755, true);
				}
			}
			else
			{
				if (!file_exists($zip_All_Merge_Path))
				{
					mkdir($zip_All_Merge_Path, 0755, true);
				}
			}

			foreach ($templates as $template)
			{
				$attachInfos = $_mFile->getAttachmentInfos($template);

				$dir_Name      = $attachInfos['value'];
				$dir_Name_Path = $tmp_path . $dir_Name;

				$dir_Merge_Name = $dir_Name . '--merge';
				$dir_Merge_Path = $tmp_path . $dir_Merge_Name;

				$_mergeZipName = $dir_Merge_Name . '_' . date("Y-m-d_H-i") . '.zip';
				$_zipName      = $dir_Name . '_' . date("Y-m-d_H-i") . '.zip';

				if (!file_exists($dir_Name_Path))
				{
					mkdir($dir_Name_Path, 0755, true);
					if ($mergeMode === 1)
					{
						if (!file_exists($dir_Merge_Path))
						{
							mkdir($dir_Merge_Path, 0755, true);
						}
					}
				}

				$uploaded_Files = $_mEval->getFilesByAttachmentFnums($template, $fnum_array);

				foreach ($uploaded_Files as $file)
				{
					$_uRaw = $_mFile->getFnumInfos($file->fnum);

					$_uId   = $_uRaw['applicant_id'];
					$_uName = $_uRaw['name'];

					$source = EMUNDUS_PATH_ABS . 'tmp' . DS . $_uName . '_' . $_uId . DS . $file->filename;

					if (!in_array($file->filename, $raw))
					{
						unlink($source);
					}
					else
					{
						copy($source, $dir_Name_Path . DS . $file->filename);
					}

					$this->ZipLetter($dir_Name_Path, $tmp_path . $_zipName, 'true');
					$zip_dir = $tmp_path . $_zipName;

					if ($mergeMode === 1)
					{
						$pdf_files = array();
						$fileList  = glob($dir_Name_Path . DS . '*');
						foreach ($fileList as $filename)
						{
							$_name           = explode($dir_Name_Path . DS, $filename)[1];
							$_file_extension = pathinfo($filename)['extension'];
							if ($_file_extension == 'pdf')
							{
								$pdf_files[] = $filename;
							}
							else
							{
								copy($filename, $dir_Merge_Path . DS . $_name);
							}
						}

						if (count($pdf_files) >= 1)
						{
							$mergeFileName = $dir_Merge_Path . DS . $attachInfos['value'] . '.pdf';
							if (file_exists($mergeFileName))
							{
								unlink($mergeFileName);
							}
							$pdf = new ConcatPdf();
							$pdf->setFiles($pdf_files);
							$pdf->concat();
							$pdf->Output($mergeFileName, 'F');
						}

						$this->copy_directory($dir_Merge_Path, $zip_All_Merge_Path . DS . str_replace('__merge', '', $dir_Merge_Name));

						$this->ZipLetter($dir_Merge_Path, $tmp_path . $_mergeZipName, true);
						$this->ZipLetter($zip_All_Merge_Path, $zip_All_Merge_Path . '_' . '.zip', true);
					}
					else
					{
						$this->copy_directory($dir_Name_Path, $zip_All_Path . DS . $dir_Name);

						$this->ZipLetter($zip_All_Path, $zip_All_Path . '_' . '.zip', true);
					}
				}

				if ($mergeMode === 1)
				{
					$res->letter_dir[] = array('letter_name' => $attachInfos['value'], 'zip_merge_dir' => DS . 'tmp/' . $_mergeZipName);

					$delete_merge_files = glob($dir_Merge_Path . DS . '*');
					foreach ($delete_merge_files as $_file)
					{
						if (is_file($_file))
						{
							unlink($_file);
						}
					}
					rmdir($dir_Merge_Path);
					unlink($zip_dir);
				}
				else
				{
					$res->letter_dir[] = array('letter_name' => $attachInfos['value'], 'zip_dir' => DS . 'tmp/' . $_zipName);
				}

				$delete_files = glob($dir_Name_Path . DS . '*');

				foreach ($delete_files as $_file)
				{
					if (is_file($_file))
					{
						unlink($_file);
					}
				}
				rmdir($dir_Name_Path);
			}

			if ($mergeMode === 1)
			{
				if (!empty($zip_All_Merge_Path))
				{
					$_deleteFolders = glob($zip_All_Merge_Path . DS . '*');
					foreach ($_deleteFolders as $_deleteFolder)
					{
						$this->deleteAll($_deleteFolder);
					}
					$this->deleteAll($zip_All_Merge_Path);
					$res->zip_all_data_by_document = DS . 'tmp/' . $zip_All_Merge_Name . '_.zip';
				}
			}
			else
			{
				if (!empty($zip_All_Path))
				{
					$_deleteFolders = glob($zip_All_Path . DS . '*');
					foreach ($_deleteFolders as $_deleteFolder)
					{
						$this->deleteAll($_deleteFolder);
					}
					$this->deleteAll($zip_All_Path);
					$res->zip_all_data_by_document = DS . 'tmp/' . $zip_All_Name . '_.zip';
				}
			}
		}

		$_tmpFolders = glob(EMUNDUS_PATH_ABS . 'tmp/*');
		foreach ($_tmpFolders as $_tmpFolder)
		{
			$this->deleteAll($_tmpFolder);
		}

		$res->recapitulatif_count = [];
		$raw                      = [];

		foreach ($res->files as $file)
		{
			$raw[$file['type']] += 1;
		}

		foreach ($raw as $k => $v)
		{
			$query->clear()
				->select('value')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('id') . ' = ' . $k);
			$this->db->setQuery($query);
			$res->recapitulatif_count[] = array('document' => $this->db->loadResult(), 'count' => $v);
		}

		return $res;
	}

	public function ZipLetter($source, $destination, $include_dir = false)
	{
		if (!extension_loaded('zip') || !file_exists($source))
		{
			return false;
		}

		if (file_exists($destination))
		{
			unlink($destination);
		}

		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE))
		{
			return false;
		}
		$source = str_replace('\\', '/', realpath($source));

		if (is_dir($source) === true)
		{
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			if ($include_dir)
			{
				$arr     = explode("/", $source);
				$maindir = $arr[count($arr) - 1];

				$source = "";
				for ($i = 0; $i < count($arr) - 1; $i++)
				{
					$source .= '/' . $arr[$i];
				}

				$source = substr($source, 1);

				$zip->addEmptyDir($maindir);

			}

			foreach ($files as $file)
			{
				$file = str_replace('\\', '/', $file);

				// Ignore "." and ".." folders
				if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
					continue;

				$file = realpath($file);

				if (is_dir($file) === true)
				{
					$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
				}
				else if (is_file($file) === true)
				{
					$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
				}
			}
		}
		else if (is_file($source) === true)
		{
			$zip->addFromString(basename($source), file_get_contents($source));
		}

		return $zip->close();
	}

	private function sanitize_filename($name)
	{
		return strtolower(preg_replace(['([\40])', '([^a-zA-Z0-9-])', '(-{2,})'], ['_', '', '_'], preg_replace('/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/', '$1', htmlentities($name, ENT_NOQUOTES, 'UTF-8'))));
	}

	private function copy_directory($src, $dst)
	{
		$dir = opendir($src);

		if ($dir !== false)
		{
			mkdir($dst);
			while (false !== ($file = readdir($dir)))
			{
				if (($file != '.') && ($file != '..'))
				{
					if (is_dir($src . '/' . $file))
					{
						recurse_copy($src . '/' . $file, $dst . '/' . $file);
					}
					else
					{
						copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}
			closedir($dir);
		}

		return 0;
	}

	private function deleteAll($dir)
	{
		if (!empty($dir) && (strpos($dir, JPATH_BASE . DS . 'tmp/') !== false || strpos($dir, EMUNDUS_PATH_ABS . 'tmp/') !== false))
		{
			foreach (glob($dir . '/*') as $file)
			{
				if (is_dir($file))
				{
					$this->deleteAll($file);
				}
				else
				{
					unlink($file);
				}
			}
			rmdir($dir);

			return 0;
		}

		return 0;
	}

	public function getMyEvaluations($user, $campaign, $module)
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . '/components/com_emundus/models/application.php');
		$m_application = new EmundusModelApplication;

		require_once(JPATH_SITE . '/components/com_emundus/helpers/module.php');
		require_once(JPATH_SITE . '/components/com_emundus/helpers/array.php');
		$h_module = new EmundusHelperModule;
		$h_array  = new EmundusHelperArray;

		try
		{
			$params = $h_module->getParams($module);

			$fnums = array();
			$query->select('DISTINCT eua.fnum,ecc.applicant_id,ecc.campaign_id,u.name')
				->from($this->db->quoteName('#__emundus_users_assoc', 'eua'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('eua.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('ecc.applicant_id') . ' = ' . $this->db->quoteName('u.id'))
				->where($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('eua.action_id') . ' = ' . $this->db->quote(5) . ' AND ' . $this->db->quoteName('eua.c') . ' = ' . $this->db->quote(1))
				->andWhere($this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quote($campaign))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1');

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->order('ecc.date_submitted');
			$this->db->setQuery($query);
			$files_users_associated = $this->db->loadObjectList();

			$query->clear()
				->select('DISTINCT ega.fnum,ecc.applicant_id,ecc.campaign_id,u.name')
				->from($this->db->quoteName('#__emundus_groups', 'eg'))
				->leftJoin($this->db->quoteName('#__emundus_group_assoc', 'ega') . ' ON ' . $this->db->quoteName('ega.group_id') . ' = ' . $this->db->quoteName('eg.group_id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ega.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('ecc.applicant_id') . ' = ' . $this->db->quoteName('u.id'))
				->where($this->db->quoteName('eg.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quote($campaign))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1');

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->order('ecc.date_submitted');
			$this->db->setQuery($query);
			$files_groups_associated = $this->db->loadObjectList();

			/* */
			$query->clear()
				->select('DISTINCT ecc.fnum,ecc.applicant_id,ecc.campaign_id,u.name')
				->from($this->db->quoteName('#__emundus_groups', 'eg'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc') . ' ON ' . $this->db->quoteName('esgrc.parent_id') . ' = ' . $this->db->quoteName('eg.group_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esgrc.course'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('ecc.applicant_id') . ' = ' . $this->db->quoteName('u.id'))
				->where($this->db->quoteName('eg.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quote($campaign))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1');

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->order('ecc.date_submitted');
			$this->db->setQuery($query);
			$files_groups_programmes_associated = $this->db->loadObjectList();
			/* */

			$files_associated = array_merge($files_users_associated, $files_groups_associated, $files_groups_programmes_associated);

			foreach ($files_associated as $file)
			{
				if (!in_array($file->fnum, $fnums))
				{
					$fnums[] = $file->fnum;
				}
			}

			$query->clear()
				->select('esp.fabrik_group_id')
				->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->where($this->db->quoteName('ecc.fnum') . ' IN (' . implode(',', $this->db->quote($fnums)) . ')');
			$this->db->setQuery($query);
			$eval_groups = $this->db->loadColumn();

			$query->clear()
				->select('form_id')
				->from($this->db->quoteName('#__fabrik_formgroup'))
				->where($this->db->quoteName('group_id') . ' IN (' . implode(',', $eval_groups) . ')');
			$this->db->setQuery($query);
			$form_id = $this->db->loadResult();

			$query->clear()
				->select('fe.id,fe.name,fe.label,fe.show_in_list_summary,ffg.form_id')
				->from($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
				->where($this->db->quoteName('fe.group_id') . ' IN (' . implode(',', $eval_groups) . ')');
			if (isset($params->more_elements) && $params->more_elements !== '')
			{
				$query->orWhere($this->db->quoteName('fe.id') . ' IN (' . $params->more_elements . ')');
			}
			$query->andWhere($this->db->quoteName('fe.published') . ' = 1');
			$this->db->setQuery($query);
			$eval_elements = $this->db->loadObjectList('name');

			$evaluations               = array();
			$more_elements_by_campaign = new stdClass;
			if (isset($params->more_elements_campaign))
			{
				$more_elements_by_campaign = json_decode($params->more_elements_campaign);
			}

			foreach ($files_associated as $file)
			{
				$evaluation                 = new stdClass;
				$evaluation->fnum           = $file->fnum;
				$evaluation->student_id     = $file->applicant_id;
				$evaluation->campaign_id    = $file->campaign_id;
				$evaluation->applicant_name = $file->name;

				$key = false;
				if (!empty($more_elements_by_campaign->campaign))
				{
					$key = array_search($file->campaign_id, $more_elements_by_campaign->campaign);
				}

				if ($key !== false)
				{
					$query->clear()
						->select('fe.id,fe.name,fe.label,fe.show_in_list_summary,ffg.form_id')
						->from($this->db->quoteName('#__fabrik_elements', 'fe'))
						->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
						->where($this->db->quoteName('fe.id') . ' IN (' . $more_elements_by_campaign->elements[$key] . ')');
					$this->db->setQuery($query);
					$more_elements = $this->db->loadObjectList('name');

					$eval_elements = array_merge($eval_elements, $more_elements);
				}

				foreach ($eval_elements as $key => $elt)
				{
					$eval_elements[$key]->label = JText::_($elt->label);
					if (!in_array($elt->name, ['fnum', 'student_id', 'campaign_id']))
					{
						if (!EmundusHelperAccess::asAccessAction(5, 'r', $user))
						{
							$evaluation->{$elt->name} = $m_application->getValuesByElementAndFnum($file->fnum, $elt->id, $elt->form_id, 1, JFactory::getUser()->id);
						}
						else
						{
							$evaluation->{$elt->name} = $m_application->getValuesByElementAndFnum($file->fnum, $elt->id, $elt->form_id);
						}
					}
				}

				$evaluations[] = $evaluation;
			}

			$evaluations = $h_array->removeDuplicateObjectsByProperty($evaluations, 'fnum');

			return array('evaluations' => $evaluations, 'elements' => $eval_elements, 'evaluation_form' => $form_id);
		}
		catch (Exception $e)
		{
			Log::add('Problem to get files associated to user ' . $user . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return array('evaluations' => [], 'elements' => [], 'evaluation_form' => 0);
		}
	}

	public function getCampaignsToEvaluate($user, $module)
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . '/components/com_emundus/helpers/module.php');
		require_once(JPATH_SITE . '/components/com_emundus/helpers/array.php');
		$h_module = new EmundusHelperModule;
		$h_array  = new EmundusHelperArray;

		try
		{
			$params = $h_module->getParams($module);

			// Get files associated to me (emundus_users_assoc)
			$query->select('DISTINCT esc.id,esc.label,count(distinct eua.fnum) as files')
				->from($this->db->quoteName('#__emundus_users_assoc', 'eua'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('eua.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->where($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1')
				->andWhere($this->db->quoteName('eua.action_id') . ' = ' . $this->db->quote(5) . ' AND ' . $this->db->quoteName('eua.c') . ' = ' . $this->db->quote(1));

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->group('esc.id');
			$this->db->setQuery($query);
			$campaigns_users_assoc = $this->db->loadObjectList();

			// Get files associated to my groups
			$query->clear()
				->select('DISTINCT esc.id,esc.label,count(distinct ega.fnum) as files')
				->from($this->db->quoteName('#__emundus_groups', 'eg'))
				->leftJoin($this->db->quoteName('#__emundus_group_assoc', 'ega') . ' ON ' . $this->db->quoteName('ega.group_id') . ' = ' . $this->db->quoteName('eg.group_id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ega.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->where($this->db->quoteName('eg.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1');

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->group('esc.id');
			$this->db->setQuery($query);
			$campaigns_groups_assoc = $this->db->loadObjectList();

			// Get files associated to my groups +++ programmes
			$query->clear()
				->select('DISTINCT esc.id,esc.label,count(distinct ecc.fnum) as files')
				->from($this->db->quoteName('#__emundus_groups', 'eg'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc') . ' ON ' . $this->db->quoteName('esgrc.parent_id') . ' = ' . $this->db->quoteName('eg.group_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esgrc.course'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->where($this->db->quoteName('eg.user_id') . ' = ' . $this->db->quote($user))
				->andWhere($this->db->quoteName('ecc.published') . ' = 1');

			if (isset($params->status) && $params->status !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $params->status) . ')');
			}

			if (isset($params->tags) && $params->tags !== '')
			{
				$query->leftJoin($this->db->quoteName('#__emundus_tag_assoc', 'eta') . ' ON ' . $this->db->quoteName('eta.fnum') . ' = ' . $this->db->quoteName('ecc.fnum'))
					->andWhere($this->db->quoteName('eta.id_tag') . ' IN (' . implode(',', $params->tags) . ')');
			}

			if (isset($params->campaign_to_exclude) && $params->campaign_to_exclude !== '')
			{
				$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' NOT IN (' . $params->campaign_to_exclude . ')');
			}

			if (!empty($params->status_to_exclude))
			{
				$query->andWhere($this->db->quoteName('ecc.status') . ' NOT IN (' . implode(',', $params->status_to_exclude) . ')');
			}

			if (!empty($params->tags_to_exclude))
			{
				$exclude_query = $this->db->getQuery(true);

				$exclude_query->select('eta.fnum')
					->from('jos_emundus_tag_assoc eta')
					->where('eta.id_tag IN (' . implode(',', $params->tags_to_exclude) . ')');

				$this->db->setQuery($exclude_query);

				$fnums_to_exclude = $this->db->loadColumn();

				if (!empty($fnums_to_exclude))
				{
					$query->where('ecc.fnum NOT IN (' . implode(',', $fnums_to_exclude) . ')');
				}
			}

			$query->group('esc.id');
			$this->db->setQuery($query);
			$campaigns_groups_programmes = $this->db->loadObjectList();

			$campaigns = array_merge($campaigns_users_assoc, $campaigns_groups_assoc, $campaigns_groups_programmes);

			return $h_array->mergeAndSumPropertyOfSameObjects($campaigns, 'id', 'files');
		}
		catch (Exception $e)
		{
			Log::add('Problem to get campaigns to evaluate for user ' . $user . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return array();
		}
	}

	public function getEvaluationUrl($fnum, $formid, $rowid = 0, $student_id = 0, $redirect = 0, $view = 'form')
	{
		$url     = 'index.php';
		$message = '';

		try
		{
			$app   = JFactory::getApplication();
			$query = $this->db->getQuery(true);
			$user  = JFactory::getUser();

			if (is_array($fnum))
			{
				$fnum = $fnum['value'];
			}
			if (is_array($student_id))
			{
				$student_id = $student_id['value'];
			}

			$create_access = EmundusHelperAccess::asAccessAction(5, 'c', $user->id, $fnum);
			$update_access = EmundusHelperAccess::asAccessAction(5, 'u', $user->id, $fnum);
			$read_access   = EmundusHelperAccess::asAccessAction(5, 'r', $user->id, $fnum);

			$offset    = $app->get('offset', 'UTC');
			$date_time = new DateTime(gmdate('Y-m-d H:i:s'), new DateTimeZone('UTC'));
			$date_time = $date_time->setTimezone(new DateTimeZone($offset));
			$now       = $date_time->format('Y-m-d H:i:s');

			$params     = JComponentHelper::getParams('com_emundus');
			$multi_eval = $params->get('multi_eval', 0);

			$query->select('esc.eval_start_date,esc.eval_end_date,ecc.applicant_id as student_id')
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
				->where($this->db->quoteName('ecc.fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$eval_dates = $this->db->loadObject();
			if (empty($student_id))
			{
				$student_id = $eval_dates->student_id;
			}

			$passed  = false;
			$started = true;
			if (!empty($eval_dates->eval_end_date) && $eval_dates->eval_end_date !== '0000-00-00 00:00:00')
			{
				$passed = strtotime($now) > strtotime($eval_dates->eval_end_date);
			}
			if (!empty($eval_dates->eval_start_date) && $eval_dates->eval_start_date !== '0000-00-00 00:00:00')
			{
				$started = strtotime($now) > strtotime($eval_dates->eval_start_date);
			}

			// If we try to open an evaluation with rowid in url
			if (!empty($rowid))
			{
				// If we open an evaluation
				$query->clear()
					->select('id,user')
					->from($this->db->quoteName('#__emundus_evaluations'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($rowid));
			}
			// If multi evaluation is allowed we search for our evaluation
			elseif ($multi_eval == 1)
			{
				$query->clear()
					->select('id,user')
					->from($this->db->quoteName('#__emundus_evaluations'))
					->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum))
					->andWhere($this->db->quoteName('user') . ' = ' . $this->db->quote($user->id));
			}
			// If multi evaluation is not allowed we search for the evaluation of file
			else
			{
				$query->clear()
					->select('id,user')
					->from($this->db->quoteName('#__emundus_evaluations'))
					->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			}
			$this->db->setQuery($query);
			$evaluation = $this->db->loadObject();

			$form_url    = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&jos_emundus_evaluations___student_id=' . $student_id . '&jos_emundus_evaluations___fnum=' . $fnum . '&tmpl=component&iframe=1';
			$details_url = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&jos_emundus_evaluations___student_id=' . $student_id . '&jos_emundus_evaluations___fnum=' . $fnum . '&tmpl=component&iframe=1';

			if (!empty($evaluation))
			{
				$form_url    = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&jos_emundus_evaluations___student_id=' . $student_id . '&jos_emundus_evaluations___fnum=' . $fnum . '&tmpl=component&iframe=1&rowid=' . $evaluation->id;
				$details_url = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&jos_emundus_evaluations___student_id=' . $student_id . '&jos_emundus_evaluations___fnum=' . $fnum . '&rowid=' . $evaluation->id . '&tmpl=component&iframe=1';

				// If evaluation period is passed
				if ($passed)
				{
					$message = 'EVALUATION_PERIOD_PASSED';
					$url     = $details_url;
				}
				// If evaluation period started and not passed and we have update rights
				elseif ($update_access || ($create_access && $evaluation->user == $user->id))
				{
					$url = $view == 'form' ? $form_url : $details_url;
				}
				// If evaluation period started and not passed and we have read rights
				elseif ($read_access)
				{
					$url = $details_url;
				}
				// If we do not have any rights on evaluation
				else
				{
					$message = 'COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS';
					$url     = '';
				}
			}
			// If no evaluation found but period is not started or passed
			elseif (($passed || !$started) && $read_access)
			{
				if ($passed)
				{
					$message = 'EVALUATION_PERIOD_PASSED';
				}
				elseif (!$started)
				{
					$message = 'EVALUATION_PERIOD_NOT_STARTED';
				}
				$url = $details_url;
			}
			// If no evaluation and period is started and not passed and I have create rights
			elseif ((!$passed && $started) && $create_access)
			{
				$url = $view == 'form' ? $form_url : $details_url;
			}
			// I don't have rights to evaluate
			else
			{
				$message = 'COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS';
				$url     = '';
			}
		}
		catch (Exception $e)
		{
			$message = 'COM_EMUNDUS_ERROR';
			$url     = '';
			Log::add('Cannot get evaluation URL with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		if (!empty($url))
		{
			if ($redirect === 1)
			{
				$url .= '&r=1';
			}
		}

		return ['url' => $url, 'message' => $message];
	}

	public function getRowByFnum($fnum, $table_name)
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('id')
				->from($this->db->quoteName($table_name))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Problem to get row by fnum ' . $fnum . ' in table ' . $table_name . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return 0;
		}
	}

	public function getEvaluationReasons($eid)
	{
		$query = $this->db->getQuery(true);

		try
		{
			$tables = $this->db->setQuery('SHOW TABLES')->loadColumn();

			if (in_array('jos_emundus_evaluations_repeat_reason', $tables))
			{
				$query->select('esr.reason')
					->from($this->db->quoteName('#__emundus_evaluations', 'ee'))
					->leftJoin($this->db->quoteName('#__emundus_evaluations_repeat_reason', 'eerr') . ' ON ' . $this->db->quoteName('ee.id') . ' = ' . $this->db->quoteName('eerr.parent_id'))
					->leftJoin($this->db->quoteName('#__emundus_setup_reasons', 'est') . ' ON ' . $this->db->quoteName('esr.id') . ' = ' . $this->db->quoteName('eerr.reason'))
					->where($this->db->quoteName('ee.id') . ' = ' . $this->db->quote($eid));
				$this->db->setQuery($query);

				return $this->db->loadColumn();
			}
			else
			{
				return [];
			}
		}
		catch (Exception $e)
		{
			Log::add('Cannot get reasons for evaluation | ' . $eid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}
}
