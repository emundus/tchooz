<?php
/**
 * @package         Joomla
 * @subpackage      eMundus
 * @link            http://www.emundus.fr
 * @copyright       Copyright (C) 2018 eMundus. All rights reserved.
 * @license         GNU/GPL
 * @author          Benjamin Rivalland
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/list.php');
require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
require_once(JPATH_SITE . '/components/com_emundus/models/users.php');

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use \Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterStatusChangeDefinition;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterTagAddDefinition;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Enums\Export\ExportModeEnum;

/**
 * Class EmundusModelFiles
 */
class EmundusModelFiles extends JModelLegacy
{
	private $app;

	protected $_db;
	/**
	 * @var null
	 */
	private $_total = null;
	/**
	 * @var null
	 */
	private $_pagination = null;
	/**
	 * @var array
	 */
	private $_applicants = array();
	/**
	 * @var array
	 */
	private $subquery = array();
	/**
	 * @var array
	 */
	private $_elements_default;
	/**
	 * @var array
	 */
	private $_elements;

	public $fnum_assoc;

	public $code;

	public $use_module_filters = false;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	public function __construct()
	{
		parent::__construct();

		$this->app = Factory::getApplication();

		if (version_compare(JVERSION, '4.0', '>')) {
			$this->_db    = Factory::getContainer()->get('DatabaseDriver');
			$current_user = $this->app->getIdentity();
			$language     = $this->app->getLanguage();
			$session      = $this->app->getSession();
		}
		else {
			$this->_db    = Factory::getDbo();
			$current_user = Factory::getUser();
			$language     = Factory::getLanguage();
			$session      = JFactory::getSession();
		}

		if(!class_exists('EmundusHelperAccess')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		}
		if(!class_exists('EmundusHelperCache')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');
		}
		$h_cache = new EmundusHelperCache();

		$this->locales = substr($language->getTag(), 0, 2);

		JPluginHelper::importPlugin('emundus');

		if (!method_exists($this->app, 'getMenu')) {
			return false;
		}

		// Get current menu parameters
		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();
		if (empty($current_menu)) {
			return false;
		}

		$Itemid                   = $this->app->input->getInt('Itemid', $current_menu->id);
		$menu_params              = $menu->getParams($Itemid);
		$this->use_module_filters = boolval($menu_params->get('em_use_module_for_filters', false));

		$h_files = new EmundusHelperFiles;
		$m_users = new EmundusModelUsers;


		$em_other_columns = explode(',', $menu_params->get('em_other_columns'));

		if (!$session->has('filter_order') || $session->get('filter_order') == 'c.id') {
			$session->set('filter_order', 'c.id');
			$session->set('filter_order_Dir', 'desc');
		}

		if (!$session->has('limit')) {
			$limit      = $this->app->getCfg('list_limit');
			$limitstart = 0;
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$session->set('limit', $limit);
			$session->set('limitstart', $limitstart);
		}
		else {
			$limit      = intval($session->get('limit'));
			$limitstart = intval($session->get('limitstart'));
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$session->set('limit', $limit);
			$session->set('limitstart', $limitstart);
		}

		$col_elt   = $this->getState('elements');
		$col_other = $this->getState('elements_other');

		$this->elements_id = $menu_params->get('em_elements_id');
		if ($session->has('adv_cols')) {
			$adv = $session->get('adv_cols');
			if (!empty($adv)) {
				$this->elements_id .= ',' . implode(',', $adv);
			}

		}
		$this->elements_values = explode(',', $menu_params->get('em_elements_values'));

		$this->_elements_default = array();
		$this->_elements         = $h_files->getElementsName($this->elements_id);

		if (!empty($this->_elements)) {
			$groupProg = array_filter($m_users->getUserGroupsProgrammeAssoc($current_user->id));
			$groupAssoc = array_filter($this->getGroupsAssociatedProgrammes($current_user->id));
			$progAssoc  = array_filter($this->getAssociatedProgrammes($current_user->id));
			$this->code = array_merge($groupProg, $groupAssoc, $progAssoc);

			foreach ($this->_elements as $def_elmt) {
				$group_params = json_decode($def_elmt->group_attribs);

				$already_joined_tables = [
					'jecc' => 'jos_emundus_campaign_candidature',
					'ss'   => 'jos_emundus_setup_status',
					'esc'  => 'jos_emundus_setup_campaigns',
					'escm'  => 'jos_emundus_setup_campaigns_more',
					'sp'   => 'jos_emundus_setup_programmes',
					'u'    => 'jos_users',
					'eu'   => 'jos_emundus_users',
					'eta'  => 'jos_emundus_tag_assoc'
				];
				foreach ($already_joined_tables as $alias => $table) {
					if ($def_elmt->tab_name === $table) {
						$def_elmt->tab_name = $alias;
					}

					if ($def_elmt->join_from_table === $table) {
						$def_elmt->join_from_table = $alias;
					}
				}

				if (in_array($def_elmt->element_plugin,['date','jdate'])) {
					if ($group_params->repeat_group_button == 1) {
						$this->_elements_default[] = '(
                                                        SELECT  GROUP_CONCAT(DATE_FORMAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ', "%d/%m/%Y %H:%i:%m") SEPARATOR ", ")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.' . $def_elmt->table_join_key . ' = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else {
						$this->_elements_default[] = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
					}
				}
				elseif ($def_elmt->element_plugin == 'databasejoin') {
					$attribs                = json_decode($def_elmt->element_attribs);
					$join_val_column_concat = str_replace('{thistable}', $attribs->join_db_name, $attribs->join_val_column_concat);
					$join_val_column_concat = str_replace('{shortlang}', substr(JFactory::getLanguage()->getTag(), 0, 2), $join_val_column_concat);
					$column                 = (!empty($join_val_column_concat) && $join_val_column_concat != '') ? 'CONCAT(' . $join_val_column_concat . ')' : $attribs->join_val_column;

					$published_column = $h_cache->get('published_column_'.$attribs->join_db_name);
					if($published_column === false)
					{
						// Check if the db table has a published column. So we don't get the unpublished value
						$this->_db->setQuery("SHOW COLUMNS FROM $attribs->join_db_name LIKE 'published'");
						$published_column = $this->_db->loadResult();

						$h_cache->set('published_column_'.$attribs->join_db_name, $published_column);
					}
					$publish_query = ($published_column) ? " AND $attribs->join_db_name.published = 1 " : '';

					if ($group_params->repeat_group_button == 1 || $attribs->database_join_display_type == "multilist" || $attribs->database_join_display_type == "checkbox") {
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
					else {
						$query = '(
                                select DISTINCT ' . $column . '
                                from ' . $attribs->join_db_name . '
                                where `' . $attribs->join_db_name . '`.`' . $attribs->join_key_column . '`=`' . $def_elmt->tab_name . '`.`' . $def_elmt->element_name . '`
                                ' . $publish_query . '
                                ) AS `' . $def_elmt->tab_name . '___' . $def_elmt->element_name . '`';
					}
					$this->_elements_default[] = $query;
				}
				elseif ($def_elmt->element_plugin == 'cascadingdropdown') {
					$attribs                 = json_decode($def_elmt->element_attribs);
					$cascadingdropdown_id    = $attribs->cascadingdropdown_id;
					$r1                      = explode('___', $cascadingdropdown_id);
					$cascadingdropdown_label = $attribs->cascadingdropdown_label;
					$r2                      = explode('___', $cascadingdropdown_label);
					$select                  = !empty($attribs->cascadingdropdown_label_concat) ? "CONCAT(" . $attribs->cascadingdropdown_label_concat . ")" : $r2[1];
					$from                    = $r2[0];
					$where                   = $r1[1];

					if ($group_params->repeat_group_button == 1) {
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
					else {
						$query = "(SELECT DISTINCT(" . $select . ") FROM " . $from . " WHERE " . $where . "=" . $def_elmt->element_name . " LIMIT 0,1) AS `" . $def_elmt->tab_name . "___" . $def_elmt->element_name . "`";
					}

					$query                     = preg_replace('#{thistable}#', $from, $query);
					$query                     = preg_replace('#{my->id}#', $current_user->id, $query);
					$query                     = preg_replace('{shortlang}', substr(JFactory::getLanguage()->getTag(), 0, 2), $query);
					$this->_elements_default[] = $query;
				}
				elseif ($def_elmt->element_plugin == 'dropdown' || $def_elmt->element_plugin == 'checkbox') {
					if ($group_params->repeat_group_button == 1) {
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
						}
						$select = str_replace($def_elmt->tab_name . '.' . $def_elmt->element_name, 'GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ' SEPARATOR ", ")', $select);

						$this->_elements_default[] = '(
                                    SELECT ' . $select . '
                                    FROM ' . $def_elmt->table_join . '
                                    WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                  ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else {
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' .
								Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
						}
						$this->_elements_default[] = $select . ' AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
				elseif ($def_elmt->element_plugin == 'radiobutton') {
					if (!empty($group_params->repeat_group_button) && $group_params->repeat_group_button == 1) {
						$element_attribs = json_decode($def_elmt->element_attribs);
						$select          = $def_elmt->tab_name . '.' . $def_elmt->element_name;
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
						}
						$select                    = str_replace($def_elmt->tab_name . '.' . $def_elmt->element_name, 'GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . ' SEPARATOR ", ")', $select);
						$this->_elements_default[] = '(
                                    SELECT ' . $select . '
                                    FROM ' . $def_elmt->table_join . '
                                    WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                  ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else {
						$element_attribs = json_decode($def_elmt->element_attribs);

						$element_replacement = $def_elmt->tab_name . '___' . $def_elmt->element_name;
						$select              = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS ' . $this->_db->quote($element_replacement) . ', CASE ';
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							$select .= ' WHEN ' . $def_elmt->tab_name . '.' . $def_elmt->element_name . ' = ' . $this->_db->quote($value) . ' THEN ' . $this->_db->quote(Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])));
						}
						$select .= ' ELSE ' . $def_elmt->tab_name . '.' . $def_elmt->element_name;
						$select .= ' END AS ' . $this->_db->quote($element_replacement);

						$this->_elements_default[] = $select;
					}
				}
				elseif ($def_elmt->element_plugin == 'yesno') {
					if ($group_params->repeat_group_button == 1) {
						$this->_elements_default[] = '(
                                                        SELECT REPLACE(REPLACE(GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . '  SEPARATOR ", "), "0", "' . Text::_('JNO') . '"), "1", "' . Text::_('JYES') . '")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else {
						$this->_elements_default[] = 'REPLACE(REPLACE(' . $def_elmt->tab_name . '.' . $def_elmt->element_name . ', "0", "' . Text::_('JNO') . '"), "1", "' . Text::_('JYES') . '")  AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
				else {
					if ($group_params->repeat_group_button == 1) {
						$this->_elements_default[] = '(
                                                        SELECT  GROUP_CONCAT(' . $def_elmt->table_join . '.' . $def_elmt->element_name . '  SEPARATOR ", ")
                                                        FROM ' . $def_elmt->table_join . '
                                                        WHERE ' . $def_elmt->table_join . '.parent_id = ' . $def_elmt->tab_name . '.id
                                                      ) AS `' . $def_elmt->table_join . '___' . $def_elmt->element_name . '`';
					}
					else {
						$this->_elements_default[] = $def_elmt->tab_name . '.' . $def_elmt->element_name . ' AS ' . $def_elmt->tab_name . '___' . $def_elmt->element_name;
					}
				}
			}
		}

		if (in_array('unread_messages', $em_other_columns)) {
			$this->_elements_default[] = ' COUNT(`m`.`message_id`) AS `unread_messages` ';
		}
		if (in_array('commentaire', $em_other_columns)) {
			$this->_elements_default[] = ' COUNT(`ecom`.`id`) AS `commentaire` ';
		}

		if (in_array('tags', $em_other_columns)) {
			$this->_elements_default[] = ' GROUP_CONCAT(DISTINCT eta.id_tag) as id_tag ';
		}
		if (empty($col_elt)) {
			$col_elt = array();
		}
		if (empty($col_other)) {
			$col_other = array();
		}
		if (empty($this->_elements_default_name)) {
			$this->_elements_default_name = array();
		}

		$this->col = array_merge($col_elt, $col_other, $this->_elements_default_name);

		if (count($this->col) > 0) {

			$elements_names = '"' . implode('", "', $this->col) . '"';

			$h_list = new EmundusHelperList;

			$result = $h_list->getElementsDetails($elements_names);
			$result = $h_files->insertValuesInQueryResult($result, array("sub_values", "sub_labels"));

			$this->details = new stdClass();
			foreach ($result as $res) {
				$this->details->{$res->tab_name . '___' . $res->element_name} = array('element_id' => $res->element_id,
				                                                                      'plugin'     => $res->element_plugin,
				                                                                      'attribs'    => $res->params,
				                                                                      'sub_values' => $res->sub_values,
				                                                                      'sub_labels' => $res->sub_labels,
				                                                                      'group_by'   => $res->tab_group_by);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getElementsVar()
	{
		return $this->_elements;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function _buildContentOrderBy()
	{
		$order            = ' ORDER BY jecc.date_submitted DESC, jecc.date_time DESC';
		$app              = Factory::getApplication();
		$menu             = method_exists($app, 'getMenu') ? $app->getMenu() : null;
		if (!empty($menu)) {
			$current_menu     = $menu->getActive();
			$menu_params      = $menu->getParams(@$current_menu->id);
			$em_other_columns = explode(',', $menu_params->get('em_other_columns'));
		} else {
			$em_other_columns = array();
		}

		$session          = $this->app->getSession();
		$filter_order     = $session->get('filter_order');
		$filter_order_Dir = $session->get('filter_order_Dir');

		$can_be_ordering = array();
		if (!empty($this->_elements)) {
			foreach ($this->_elements as $element) {
				if (!empty($element->table_join)) {
					$can_be_ordering[] = $element->table_join . '___' . $element->element_name;
					$can_be_ordering[] = $element->table_join . '.' . $element->element_name;
				}
				else {
					$can_be_ordering[] = $element->tab_name . '___' . $element->element_name;
					$can_be_ordering[] = $element->tab_name . '.' . $element->element_name;
				}
			}
		}

		$can_be_ordering[] = 'jecc.id';
		$can_be_ordering[] = 'jecc.fnum';
		$can_be_ordering[] = 'jecc.status';
		$can_be_ordering[] = 'jecc.form_progress';
		$can_be_ordering[] = 'jecc.attachment_progress';
		$can_be_ordering[] = 'form_progress';
		$can_be_ordering[] = 'attachment_progress';
		$can_be_ordering[] = 'fnum';
		$can_be_ordering[] = 'status';
		$can_be_ordering[] = 'name';
		$can_be_ordering[] = 'eta.id_tag';

		if (in_array('unread_messages', $em_other_columns)) {
			$can_be_ordering[] = 'unread_messages';
		}
		$campaign_candidature_columns = [
			'form_progress',
			'attachment_progress',
			'status'
		];

		if (in_array('commentaire', $em_other_columns)) {
			$can_be_ordering[] = 'commentaire';
		}

		if($filter_order === 'fnum') {
			$filter_order = 'name';
		}

		if (!empty($filter_order) && !empty($filter_order_Dir) && in_array($filter_order, $can_be_ordering)) {
			if (in_array($filter_order, $campaign_candidature_columns)) {
				$filter_order = 'jecc.' . $filter_order;
			}
			$order = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;

			if (strpos($filter_order, 'date_submitted') === false) {
				$order .= ', jecc.date_submitted DESC ';
			}
			if (strpos($filter_order, 'date_time') === false) {
				$order .= ', jecc.date_time DESC ';
			}
		}

		return $order;
	}

	/**
	 * @param   array  $multi_array
	 * @param          $sort_key
	 * @param   int    $sort
	 *
	 * @return array|int
	 */
	public function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC)
	{
		if (is_array($multi_array)) {

			foreach ($multi_array as $key => $row_array) {
				if (is_array($row_array)) {
					@$key_array[$key] = $row_array[$sort_key];
				}
				else {
					return -1;
				}
			}

		}
		else {
			return -1;
		}

		if (!empty($key_array)) {
			array_multisort($key_array, $sort, $multi_array);
		}

		return $multi_array;
	}

	/**
	 * @return mixed
	 */
	public function getCampaign()
	{
		$h_files = new EmundusHelperFiles;

		return $h_files->getCampaign();
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getCurrentCampaign()
	{
		$h_files = new EmundusHelperFiles;

		return $h_files->getCurrentCampaign();
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getCurrentCampaignsID()
	{
		$h_files = new EmundusHelperFiles;

		return $h_files->getCurrentCampaignsID();
	}

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function getProfileAcces($user)
	{

		$query     = 'SELECT esg.profile_id FROM #__emundus_setup_groups as esg
                    LEFT JOIN #__emundus_groups as eg on esg.id=eg.group_id
                    WHERE esg.published=1 AND eg.user_id=' . $user;
		$this->_db->setQuery($query);

		return $this->_db->loadResultArray();
	}


	/**
	 * @param $tab
	 * @param $joined
	 *
	 * @return bool
	 */
	public function isJoined($tab, $joined)
	{

		foreach ($joined as $j) {
			if ($tab == $j) {
				return true;
			}
		}

		return false;
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
		$h_list = new EmundusHelperList;
		if (!empty($search)) {
			foreach ($search as $c) {
				if (!empty($c)) {
					$name = explode('.', $c);
					if (!in_array($name[0] . '___' . $name[1], $head_val)) {
						if ($this->details->{$name[0] . '___' . $name[1]}['group_by'] && array_key_exists($name[0] . '___' . $name[1], $this->subquery) && array_key_exists($applicant->user_id, $this->subquery[$name[0] . '___' . $name[1]])) {
							$eval_list[$name[0] . '___' . $name[1]] = $h_list->createHtmlList(explode(",",
								$this->subquery[$name[0] . '___' . $name[1]][$applicant->user_id]));
						}
						elseif ($name[0] == 'jos_emundus_training') {
							$eval_list[$name[1]] = $applicant->{$name[1]};
						}
						elseif (!$this->details->{$name[0] . '___' . $name[1]}['group_by']) {
							$eval_list[$name[0] . '___' . $name[1]] =
								$h_list->getBoxValue($this->details->{$name[0] . '___' . $name[1]},
									$applicant->{$name[0] . '___' . $name[1]}, $name[1]);
						}
						else {
							$eval_list[$name[0] . '___' . $name[1]] = $applicant->{$name[0] . '___' . $name[1]};
						}
					}
				}
			}
		}
	}


	/**
	 * @param   array  $tableAlias
	 *
	 * @return array
	 */
	private function _buildWhere($already_joined_tables = array())
	{
		$h_files = new EmundusHelperFiles();

		return $h_files->_moduleBuildWhere($already_joined_tables, 'files', array(
			'fnum_assoc' => $this->fnum_assoc,
			'code'       => $this->code
		));
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getUsers()
	{
		$session    = $this->app->getSession();
		$limitStart = $session->get('limitstart', 0);
		$limit      = $session->get('limit', 20);

		return $this->getAllUsers($limitStart, $limit);
	}

	/**
	 * @param $limitStart   int     request start
	 * @param $limit        int     request limit
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getAllUsers($limitStart = 0, $limit = 20, int $menu_id = 0)
	{
		$user_files = [];

		$current_menu = method_exists($this->app, 'getMenu') ? $this->app->getMenu()->getActive() : null;
		if (!empty($menu_id)) {
			$current_menu = $this->app->getMenu()->getItem($menu_id);
		}

		if (!empty($current_menu)) {
			$menu_params      = $current_menu->getParams();
			$em_other_columns = explode(',', $menu_params->get('em_other_columns'));
		}
		else {
			$em_other_columns = array();
		}

		$select = 'select jecc.fnum, ss.step, ss.value as status, ss.class as status_class, ' .
			'CASE WHEN eu.is_anonym = 1 THEN "' . Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') . '" ELSE concat(upper(trim(eu.lastname)), " ", eu.firstname) END AS name, ' .
			'jecc.applicant_id, jecc.campaign_id, eu.is_anonym ';

		// prevent double left join on query
		$already_joined_tables = [
			'jecc' => 'jos_emundus_campaign_candidature',
			'eccc' => 'jos_emundus_campaign_candidature_choices',
			'ss'   => 'jos_emundus_setup_status',
			'esc'  => 'jos_emundus_setup_campaigns',
			'escm'  => 'jos_emundus_setup_campaigns_more',
			'sp'   => 'jos_emundus_setup_programmes',
			'u'    => 'jos_users',
			'eu'   => 'jos_emundus_users',
			'eta'  => 'jos_emundus_tag_assoc'
		];

		if (in_array('unread_messages', $em_other_columns)) {
			$already_joined_tables['ec'] = 'jos_emundus_chatroom';
			$already_joined_tables['m']  = 'jos_messages';
		}
		if (in_array('commentaire', $em_other_columns)) {
			$lastTab[] = ['#__emundus_comments', 'jos_emundus_comments'];
		}

		if (!empty($this->_elements))
		{
			$h_files  = new EmundusHelperFiles();
			$leftJoin = '';

			foreach ($this->_elements as $elt)
			{
				$tables_to_join = [$elt->tab_name];

				if (!empty($elt->table_join))
				{
					$tables_to_join[] = $elt->table_join;
				}

				foreach ($tables_to_join as $table_to_join)
				{
					$already_join_alias = array_keys($already_joined_tables);

					if (!(in_array($table_to_join, $already_joined_tables)) && !(in_array($table_to_join, $already_join_alias, true)))
					{
						if ($h_files->isTableLinkedToCampaignCandidature($table_to_join))
						{
							$leftJoin                .= 'LEFT JOIN ' . $table_to_join . ' ON ' . $table_to_join . '.fnum = jecc.fnum ';
							$already_joined_tables[] = $table_to_join;
						}
						else
						{
							$joined          = false;
							$query_find_join = $this->_db->getQuery(true);
							foreach ($already_joined_tables as $already_join_alias => $already_joined_table_name)
							{
								$query_find_join->clear()
									->select('*')
									->from('#__fabrik_joins')
									->where('table_join = ' . $this->_db->quote($already_joined_table_name))
									->andWhere('join_from_table = ' . $this->_db->quote($table_to_join))
									->andWhere('table_key = ' . $this->_db->quote('id'))
									->andWhere('list_id = ' . $this->_db->quote($elt->table_list_id));

								$this->_db->setQuery($query_find_join);
								$join_informations = $this->_db->loadAssoc();

								if (!empty($join_informations))
								{
									$already_joined_tables[] = $table_to_join;

									$leftJoin .= ' LEFT JOIN ' . $this->_db->quoteName($join_informations['join_from_table']) . ' ON ' . $this->_db->quoteName($join_informations['join_from_table'] . '.' . $join_informations['table_key']) . ' = ' . $this->_db->quoteName($already_join_alias . '.' . $join_informations['table_join_key']);
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
		}

		if (!empty($this->_elements_default)) {
			$select .= ', ' . implode(',', $this->_elements_default);
		}

		$query = ' FROM #__emundus_campaign_candidature as jecc
                    LEFT JOIN #__emundus_campaign_candidature_choices as eccc on eccc.fnum = jecc.fnum
                    LEFT JOIN #__emundus_setup_status as ss on ss.step = jecc.status
                    LEFT JOIN #__emundus_setup_campaigns as esc on esc.id = jecc.campaign_id
                    LEFT JOIN #__emundus_setup_campaigns_more as escm on escm.campaign_id = esc.id
                    LEFT JOIN #__emundus_setup_programmes as sp on sp.code = esc.training
                    LEFT JOIN #__users as u on u.id = jecc.applicant_id
                    LEFT JOIN #__emundus_users as eu on eu.user_id = jecc.applicant_id
                    LEFT JOIN #__emundus_tag_assoc as eta on eta.fnum=jecc.fnum ';

		if (in_array('unread_messages', $em_other_columns)) {
			$query .= ' LEFT JOIN #__emundus_chatroom as ec on ec.fnum = jecc.fnum
            LEFT JOIN #__messages as m on m.page = ec.id AND m.state = 0 AND m.page IS NOT NULL ';
		}
		if (in_array('commentaire', $em_other_columns)) {
			$query .= ' LEFT JOIN #__emundus_comments as ecom on ecom.fnum = jecc.fnum ';
		}

		$q = $this->_buildWhere($already_joined_tables);
		if (!empty($leftJoin)) {
			$query .= $leftJoin;
		}
		$query .= $q['join'];
		$query .= ' WHERE u.block=0 ' . $q['q'];

		try {
			$this->_db->setQuery('SELECT COUNT(DISTINCT jecc.id) ' . $query);
			$this->_total = $this->_db->loadResult();

			$query .= ' GROUP BY jecc.fnum';
			$query .= $this->_buildContentOrderBy();

			if ($limit > 0) {
				$query .= " limit $limitStart, $limit ";
			}

			$this->_db->setQuery($select . ' ' . $query);
			$user_files = $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_GET_ALL_FILES_ERROR') . ' ' . $e->getMessage(), 'error');
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' ' . $e->getMessage() . ' -> ' . $query, Log::ERROR, 'com_emundus.error');
		}

		return $user_files;
	}


	// get emundus groups for user

	/**
	 * @param $uid
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getUserGroups($uid)
	{
		return EmundusHelperFiles::getUserGroups($uid);
	}

	/**
	 * @return array
	 */
	public function getDefaultElements()
	{
		return $this->_elements;
	}

	/**
	 * @return array|string
	 */
	public function getSelectList()
	{
		$lists = [];

		if (!empty($this->col)) {
			foreach ($this->col as $c) {
				if (!empty($c)) {
					$tab    = explode('.', $c);
					$names  = @$tab[1];
					$tables = $tab[0];

					$query = 'SELECT distinct(fe.name), fe.label, ft.db_table_name as table_name
                        FROM #__fabrik_elements fe
                        LEFT JOIN #__fabrik_formgroup ff ON ff.group_id = fe.group_id
                        LEFT JOIN #__fabrik_lists ft ON ft.form_id = ff.form_id
                        WHERE fe.name = "' . $names . '"
                        AND ft.db_table_name = "' . $tables . '"';
					$this->_db->setQuery($query);
					$cols[] = $this->_db->loadObject();
				}
			}
			if (!empty($cols)) {
				foreach ($cols as $c) {
					if (!empty($c)) {
						$list          = array();
						$list['name']  = @$c->table_name . '___' . $c->name;
						$list['label'] = @ucfirst($c->label);
						$lists[]       = $list;
					}
				}
			}
		}

		return $lists;
	}

	/**
	 * @return mixed
	 */
	public function getProfiles($ids = [])
	{
		$profiles = [];

		$query = $this->_db->getQuery(true);
		$query->select('esp.id, esp.label, esp.published, esp.acl_aro_groups, caag.lft, esp.menutype')
			->from($this->_db->quoteName('#__emundus_setup_profiles', 'esp'))
			->join('INNER', $this->_db->quoteName('#__usergroups', 'caag') . ' ON (' . $this->_db->quoteName('esp.acl_aro_groups') . ' = ' . $this->_db->quoteName('caag.id') . ')');

		if (!empty($ids)) {
			$query->where('esp.id IN (' . implode(',', $ids) . ')');
		}

		$query->order('caag.lft, esp.label');

		try {
			$this->_db->setQuery($query);
			$profiles = $this->_db->loadObjectList('id');
		} catch (Exception $e) {
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' ' . $e->getMessage() . ' -> ' . $query, Log::ERROR, 'com_emundus.error');
		}

		return $profiles;
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getProfileByID($id)
	{

		$query     = 'SELECT esp.* FROM jos_emundus_setup_profiles as esp
                LEFT JOIN jos_emundus_users as eu ON eu.profile=esp.id
                WHERE eu.user_id=' . $id;
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $ids
	 *
	 * @return mixed
	 */
	public function getProfilesByIDs($ids)
	{

		$query     = 'SELECT esp.id, esp.label, esp.acl_aro_groups, caag.lft
        FROM #__emundus_setup_profiles esp
        INNER JOIN #__usergroups caag on esp.acl_aro_groups=caag.id
        WHERE esp.id IN (' . implode(',', $ids) . ')
        ORDER BY caag.lft, esp.label';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList('id');
	}

	/**
	 * @return mixed
	 */
	public function getAuthorProfiles()
	{

		$query     = 'SELECT esp.id, esp.label, esp.acl_aro_groups, caag.lft
        FROM #__emundus_setup_profiles esp
        INNER JOIN #__usergroups caag on esp.acl_aro_groups=caag.id
        WHERE esp.acl_aro_groups=19';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList('id');
	}

	/**
	 * @return mixed
	 */
	public function getApplicantsProfiles()
	{
		$user      = $this->app->getIdentity();

		$query     = 'SELECT esp.id, esp.label FROM #__emundus_setup_profiles esp
                  WHERE esp.published=1 ';
		$no_filter = array("Super Users", "Administrator");
		if (!in_array($user->usertype, $no_filter))
			$query .= ' AND esp.id IN (select profile_id from #__emundus_users_profiles where profile_id in (' .
				implode(',', EmundusModelFiles::getProfileAcces($user->id)) . ')) ';
		$query .= ' ORDER BY esp.label';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $profile
	 *
	 * @return mixed
	 */
	public function getApplicantsByProfile($profile)
	{

		$query     = 'SELECT eup.user_id FROM #__emundus_users_profiles eup WHERE eup.profile_id=' . $profile;
		$this->_db->setQuery($query);

		return $this->_db->loadResultArray();
	}


	/**
	 * @return mixed
	 */
	public function getAuthorUsers()
	{

		$query     = 'SELECT u.id, u.gid, u.name
        FROM #__users u
        WHERE u.gid=19';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList('id');
	}

	/**
	 * @return mixed
	 */
	public function getMobility()
	{

		$query     = 'SELECT esm.id, esm.label, esm.value
        FROM #__emundus_setup_mobility esm
        ORDER BY ordering';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList('id');
	}

	/**
	 * @return mixed
	 */
	public function getElements()
	{
		$elements = [];


		$query     = 'SELECT element.id, element.name AS element_name, element.label AS element_label, element.plugin AS element_plugin,
                 groupe.label AS group_label, INSTR(groupe.params,\'"repeat_group_button":"1"\') AS group_repeated,
                 tab.db_table_name AS table_name, tab.label AS table_label
            FROM jos_fabrik_elements element
                 INNER JOIN jos_fabrik_groups AS groupe ON element.group_id = groupe.id
                 INNER JOIN jos_fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id
                 INNER JOIN jos_fabrik_lists AS tab ON tab.form_id = formgroup.form_id
                 INNER JOIN jos_menu AS menu ON tab.id = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("listid=",menu.link)+7, 4), "&", 1)
                 INNER JOIN jos_emundus_setup_profiles AS profile ON profile.menutype = menu.menutype
            WHERE tab.published = 1 AND element.published=1 AND element.hidden=0 AND element.label!=" " AND element.label!=""
            ORDER BY menu.ordering, formgroup.ordering, element.ordering';

		try {
			$this->_db->setQuery($query);
			$elements = $this->_db->loadObjectList('id');
		} catch (Exception $e) {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_GET_ELEMENTS_NAME_ERROR') . ' ' . $e->getMessage(), 'error');
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' ' . $e->getMessage() . ' -> ' . $query, Log::ERROR, 'com_emundus.error');
		}

		return $elements;
	}

	/**
	 * @return mixed
	 */
	public function getElementsName()
	{
		$elements_name = array();


		$query = 'SELECT element.id, element.name AS element_name, element.label AS element_label, element.plugin AS element_plugin,
                 groupe.label AS group_label, INSTR(groupe.params,\'"repeat_group_button":"1"\') AS group_repeated,
                 tab.db_table_name AS table_name, tab.label AS table_label
            FROM jos_fabrik_elements element
                 INNER JOIN jos_fabrik_groups AS groupe ON element.group_id = groupe.id
                 INNER JOIN jos_fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id
                 INNER JOIN jos_fabrik_lists AS tab ON tab.form_id = formgroup.form_id
                 INNER JOIN jos_menu AS menu ON tab.id = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("listid=",menu.link)+7, 4), "&", 1)
                 INNER JOIN jos_emundus_setup_profiles AS profile ON profile.menutype = menu.menutype
            WHERE tab.published = 1 AND element.published=1 AND element.hidden=0 AND element.label!=" " AND element.label!=""
            ORDER BY menu.ordering, formgroup.ordering, element.ordering';

		try {
			$this->_db->setQuery($query);
			$elements_name = $this->_db->loadObjectList('element_name');
		} catch (Exception $e) {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_GET_ELEMENTS_NAME_ERROR') . ' ' . $e->getMessage(), 'error');
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' ' . $e->getMessage() . ' -> ' . $query, Log::ERROR, 'com_emundus.error');
		}

		return $elements_name;
	}

	/**
	 * @return int|null
	 */
	public function getTotal()
	{
		if (empty($this->_total))
			$this->_total = count($this->_applicants);

		return $this->_total;
	}

	// get applicant columns

	/**
	 * @return array
	 */
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

	/**
	 * @return JPagination|null
	 */
	public function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$session           = $this->app->getSession();
			$this->_pagination = new JPagination($this->getTotal(), $session->get('limitstart'), $session->get('limit'));
		}

		return $this->_pagination;
	}

	public function getPageNavigation(): string
	{
		if ($this->getPagination()->pagesTotal <= 1) {
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

		if ($this->getPagination()->pagesTotal > 15) {
			$index = 5;
	        if($this->getPagination()->pagesCurrent > 5 && $this->getPagination()->pagesCurrent < 8)
	        {
		        $index = $this->getPagination()->pagesCurrent - 3;
	        }

			for ($i = 1; $i <= $index; $i++) {
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i) {
					$pageNavigation .= "class='active'";
				}
				$pageNavigation .= "><a id='" . $i . "' href='#em-data'>" . $i . "</a></li>";
			}
			if($this->getPagination()->pagesCurrent > 8)
	        {
				$pageNavigation .= "<li class='disabled'><span>...</span></li>";
			}
			if ($this->getPagination()->pagesCurrent <= 5) {
				for ($i = 6; $i <= 10; $i++) {
					$pageNavigation .= "<li ";
					if ($this->getPagination()->pagesCurrent == $i) {
						$pageNavigation .= "class='active'";
					}
					$pageNavigation .= "><a id=" . $i . " href='#em-data'>" . $i . "</a></li>";
				}
			}
			else {
				for ($i = $this->getPagination()->pagesCurrent - 2; $i <= $this->getPagination()->pagesCurrent + 2; $i++) {
					if ($i <= $this->getPagination()->pagesTotal) {
						$pageNavigation .= "<li ";
						if ($this->getPagination()->pagesCurrent == $i) {
							$pageNavigation .= "class='active'";
						}
						$pageNavigation .= "><a id=" . $i . " href='#em-data'>" . $i . "</a></li>";
					}
				}
			}

			// if total pages - current page is less than 5
			$index = 4;
	        if($this->getPagination()->pagesTotal - $this->getPagination()->pagesCurrent < 7)
	        {
				$index = $this->getPagination()->pagesTotal - ($this->getPagination()->pagesCurrent+3);
	        } else {
				$pageNavigation .= "<li class='disabled'><span>...</span></li>";
			}
			for ($i = $this->getPagination()->pagesTotal - $index; $i <= $this->getPagination()->pagesTotal; $i++) {
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i) {
					$pageNavigation .= "class='active'";
				}
				$pageNavigation .= "><a id='" . $i . "' href='#em-data'>" . $i . "</a></li>";
			}
		}
		else {
			for ($i = 1; $i <= $this->getPagination()->pagesStop; $i++) {
				$pageNavigation .= "<li ";
				if ($this->getPagination()->pagesCurrent == $i) {
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

	/**
	 * @return mixed
	 */
	public function getSchoolyears()
	{

		$query     = 'SELECT DISTINCT(schoolyear) as schoolyear
        FROM #__emundus_users
        WHERE schoolyear is not null AND schoolyear != ""
        ORDER BY schoolyear';
		$this->_db->setQuery($query);

		return $this->_db->loadResultArray();
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getAllActions()
	{
		$actions = [];
		$query   = $this->_db->getQuery(true);

		$query->select('distinct *')
			->from('#__emundus_setup_actions')
			->where('status = 1')
			->order('ordering');
		try {
			$this->_db->setQuery($query);
			$actions = $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}

		return $actions;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getEvalGroups()
	{
		$eval_groups = [
			'groups' => [],
			'users' => []
		];

		try {
			$query = $this->_db->getQuery(true);
			$query->select('*')
				->from('#__emundus_setup_groups')
				->where('published = 1');

			$this->_db->setQuery($query);
			$eval_groups['groups'] = $this->_db->loadAssocList();

			$query->clear()
				->select('id, label')
				->from('#__emundus_setup_profiles')
				->where('published != 1')
				->andWhere('id != 1');

			$this->_db->setQuery($query);
			$non_applicant_profiles = $this->_db->loadAssocList('id');

			if (!empty($non_applicant_profiles)) {
				$non_applicant_profiles_ids = array_keys($non_applicant_profiles);
				$query->clear()
					->select('DISTINCT(eu.user_id) as user_id, CONCAT(eu.lastname, " ", eu.firstname) as name, u.email, eu.profile as default_profile_id, GROUP_CONCAT(DISTINCT eup.profile_id) as profiles_ids')
					->from('#__emundus_users AS eu')
					->leftJoin('#__users AS u ON u.id = eu.user_id')
					->leftJoin('#__emundus_users_profiles AS eup ON eup.user_id = eu.user_id')
					->where('u.block = 0')
					->where('(eu.profile IN (' . implode(',', $non_applicant_profiles_ids) . ') OR eup.profile_id IN (' . implode(',', $non_applicant_profiles_ids) . '))')
					->group('eu.user_id');

				$this->_db->setQuery($query);
				$eval_groups['users'] = $this->_db->loadAssocList();

				foreach($eval_groups['users'] as $key => $user) {
					if (!in_array($user['default_profile_id'], $non_applicant_profiles_ids)) {
						$profile_ids = explode(',', $user['profiles_ids']);
						$eval_groups['users'][$key]['label'] = $non_applicant_profiles[$profile_ids[0]]['label'];
					} else {
						$eval_groups['users'][$key]['label'] = $non_applicant_profiles[$user['default_profile_id']]['label'];
					}
				}
			}
		} catch(Exception $e) {
			Log::add(Uri::getInstance(). ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $eval_groups;
	}

	/**
	 * @param $groups
	 * @param $actions
	 * @param $fnums
	 * @param $current_user
	 *
	 * @return bool
	 */
	public function shareGroups($groups, $actions, $fnums, $current_user = null)
	{
		$shared = false;

		if (!empty($groups) && !empty($fnums)) {
			if (empty($current_user))
			{
				$current_user = $this->app->getIdentity();
			}

			try {

				$insert = [];

				foreach ($fnums as $fnum) {
					foreach ($groups as $group) {
						foreach ($actions as $action) {
							$ac       = (array) $action;
							$insert[] = $group . ',' . $ac['id'] . ',' . $ac['c'] . ',' . $ac['r'] . ',' . $ac['u'] . ',' . $ac['d'] . ',' . $this->_db->quote($fnum);
						}
					}
				}

				$query = $this->_db->getQuery(true);
				$query->delete($this->_db->quoteName('#__emundus_group_assoc'))
					->where($this->_db->quoteName('group_id') . ' IN (' . implode(',', $groups) . ') AND ' . $this->_db->quoteName('fnum') . ' IN ("' . implode('","', $fnums) . '")');
				$this->_db->setQuery($query);
				$this->_db->execute();

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_group_assoc'))
					->columns($this->_db->quoteName(['group_id', 'action_id', 'c', 'r', 'u', 'd', 'fnum']))
					->values($insert);
				$this->_db->setQuery($query);
				$shared = $this->_db->execute();

				if ($shared) {
					// log
					$query->clear()
						->select('CONCAT("Groupe ", label)')
						->from($this->_db->quoteName('#__emundus_setup_groups'))
						->where($this->_db->quoteName('id') . ' IN (' . implode(',', $groups) . ')');
					$this->_db->setQuery($query);
					$group_labels = $this->_db->loadColumn();

					foreach ($fnums as $fnum) {
                        $fnumInfos = $this->getFnumInfos($fnum);
						$logsParams = array('created' => array_unique($group_labels, SORT_REGULAR));
						EmundusModelLogs::log($current_user->id, (int)$fnumInfos['applicant_id'], $fnum, 11, 'c', 'COM_EMUNDUS_ACCESS_ACCESS_FILE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}
				}
			}
			catch (Exception $e) {
				$error = Uri::getInstance() . ' :: USER ID : ' . $current_user->id . '\n -> ' . $e->getMessage();
				Log::add($error, Log::ERROR, 'com_emundus');
				$shared = false;
			}
		}

		return $shared;
	}

	/**
	 * @param $users
	 * @param $actions
	 * @param $fnums
	 *
	 * @return bool|string
	 */
	public function shareUsers($users, $actions, $fnums, $current_user = null)
	{
		$shared = false;
		$error = null;

		if(empty($current_user)) {
			$current_user = $this->app->getIdentity();
		}

		if (!empty($users) && !empty($fnums)) {
			try {

				$insert = [];

				foreach ($fnums as $fnum) {
					foreach ($users as $user) {
						foreach ($actions as $action) {
							$ac       = (array) $action;
							$insert[] = $user . ',' . $ac['id'] . ',' . $ac['c'] . ',' . $ac['r'] . ',' . $ac['u'] . ',' . $ac['d'] . ',' . $this->_db->quote($fnum);
						}
					}
				}

				$query = $this->_db->getQuery(true);
				$query->delete($this->_db->quoteName('#__emundus_users_assoc'))
					->where($this->_db->quoteName('user_id') . ' IN (' . implode(',', $users) . ') AND ' . $this->_db->quoteName('fnum') . ' IN ("' . implode('","', $fnums) . '")');
				$this->_db->setQuery($query);
				$this->_db->execute();

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_users_assoc'))
					->columns($this->_db->quoteName(['user_id', 'action_id', 'c', 'r', 'u', 'd', 'fnum']))
					->values($insert);
				$this->_db->setQuery($query);
				$shared = $this->_db->execute();

				if ($shared) {
					$query->clear()
						->select('name')
						->from($this->_db->quoteName('#__users'))
						->where($this->_db->quoteName('id') . ' IN (' . implode(',', $users) . ')');

					$this->_db->setQuery($query);
					$user_names = $this->_db->loadColumn();

					foreach ($fnums as $fnum) {
                        $fnumInfos = $this->getFnumInfos($fnum);
						$logsParams = array('created' => array_unique($user_names, SORT_REGULAR));
						EmundusModelLogs::log($current_user->id, $fnumInfos['applicant_id'], $fnum, 11, 'c', 'COM_EMUNDUS_ACCESS_ACCESS_FILE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}
				}
			}
			catch (Exception $e) {
				Log::add('Failed to share users' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				$shared = false;
			}
		}

		return $shared;
	}

	public function unshareUsers(array $users, array $fnums, ?User $current_user = null): bool
	{
		try
		{
			if(empty($users) || empty($fnums))
			{
				return false;
			}

			if(empty($current_user))
			{
				$current_user = $this->app->getIdentity();
			}

			$query = $this->_db->getQuery(true);

			$query->delete($this->_db->quoteName('#__emundus_users_assoc'))
				->where($this->_db->quoteName('user_id') . ' IN (' . implode(',', $users) . ')')
				->where($this->_db->quoteName('fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')');
			$this->_db->setQuery($query);

			if($unshared = $this->_db->execute())
			{
				$query->clear()
					->select('name')
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('id') . ' IN (' . implode(',', $users) . ')');
				$this->_db->setQuery($query);
				$user_names = $this->_db->loadColumn();

				foreach ($fnums as $fnum)
				{
					$fnumInfos  = $this->getFnumInfos($fnum);
					$logsParams = array('deleted' => array_unique($user_names, SORT_REGULAR));
					EmundusModelLogs::log($current_user->id, $fnumInfos['applicant_id'], $fnum, 11, 'd', 'COM_EMUNDUS_ACCESS_ACCESS_FILE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Failed to unshare users' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$unshared = false;
		}

		return $unshared;
	}

	/**
	 * Get all action tags
	 *
	 * @return array
	 *
	 * @since version 1.0.0
	 */
	public function getAllTags()
	{
		$tags = [];

		$query = $this->_db->getQuery(true);

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_setup_action_tag'))
			->order('ordering, label');

		try {
			$this->_db->setQuery($query);
			$tags = $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			Log::add('Failed to get all tags ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $tags;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getAllGroups()
	{
		$query = 'select * from #__emundus_setup_groups where published=1';


		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/** Gets the groups the user is a part of OR if the user has read access on groups, all groups.
	 * @return mixed
	 * @throws Exception
	 */
	public function getUserAssocGroups()
	{

		$user = $this->app->getIdentity();

		if (EmundusHelperAccess::asAccessAction(19, 'c', $user->id)) {
			$query = 'select * from #__emundus_setup_groups where published=1';
		}
		else {
			$query = 'SELECT * from #__emundus_setup_groups AS sg 
					WHERE sg.id IN (SELECT g.group_id FROM jos_emundus_groups AS g WHERE g.user_id = ' . $user->id . ') AND sg.published = 1';
		}


		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getAllInstitutions()
	{
		$query = 'select * from #__categories where extension="com_contact" order by lft';


		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getAllStatus($uid = null, $result_index = null)
	{
		$all_status = [];

		if (empty($uid)) {
			$uid = Factory::getApplication()->getIdentity()->id;
		}

		$query = $this->_db->getQuery(true);

		$status_by_groups = $this->getStatusByGroup($uid);

		try
		{
			$query->select('*')
				->from($this->_db->quoteName('#__emundus_setup_status'))
				->order('ordering');

			if (!empty($status_by_groups)) {
				$query->where($this->_db->quoteName('step'). ' IN ('.implode(',', $this->_db->quote($status_by_groups)).')');
			}
			$this->_db->setQuery($query);

			if (!empty($result_index) && in_array($result_index, ['id', 'step', 'value'])) {
				$all_status = $this->_db->loadAssocList($result_index);
			} else {
				$all_status = $this->_db->loadAssocList();
			}
		}
		catch(Exception $e)
		{
			Log::add('Failed to get all status with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $all_status;
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getStatusByID($id)
	{
		$query = 'select * from #__emundus_setup_status where id=' . $id;


		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getStatusByFnums($fnums)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		if(!is_array($fnums)) {
			$fnums = array($fnums);
		}

		$query->select('*')
			->from($db->quoteName('#__emundus_campaign_candidature','ecc'))
			->leftJoin($db->quoteName('#__emundus_setup_status','ess').' ON '.$db->quoteName('ess.step').' = '.$db->quoteName('ecc.status'))
			->where($db->quoteName('ecc.fnum').' IN ('.implode(',', $db->quote($fnums)).')');

		try
		{
			$db->setQuery($query);
			return $db->loadAssocList('fnum');
		}
		catch(Exception $e)
		{
			Log::add('Failed to get status by fnums with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}
	}

	/**
	 * @param $fnums
	 * @param $tag
	 *
	 * @return bool
	 */
	public function tagFile($fnums, $tags, $user_id = null, ?AutomationExecutionContext $executionContext = null)
	{
		$tagged = false;

		if (!empty($fnums) && !empty($tags)) {
			JPluginHelper::importPlugin('emundus');
			\Joomla\CMS\Factory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforeTagAdd', ['fnums' => $fnums, 'tag' => $tags]]);

			try {
				if(empty($user_id))
				{
					$user_id = $this->app->getIdentity()->id;
					if(empty($user_id)) {
						$eMConfig = ComponentHelper::getParams('com_emundus');
						$user_id = $eMConfig->get('automated_task_user', 62);
					}
				}

				$now = EmundusHelperDate::getNow();

				$query_associated_tags = $this->_db->getQuery(true);
				$query                 = "insert into #__emundus_tag_assoc (fnum, id_tag, date_time, user_id) VALUES ";

				$logger = array();
				$insert_tags = false;
				foreach ($fnums as $fnum) {
					// Get tags already associated to this fnum by the current user
					$query_associated_tags->clear()
						->select('id_tag')
						->from($this->_db->quoteName('#__emundus_tag_assoc'))
						->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum))
						->andWhere($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id));
					$this->_db->setQuery($query_associated_tags);
					$tags_already_associated = $this->_db->loadColumn();

					// Insert valid tags
					foreach ($tags as $tag) {
						if (!in_array($tag, $tags_already_associated)) {
	                        $insert_tags = true;

							$query     .= '("' . $fnum . '", ' . $tag . ',"' . $now . '",' . $user_id . '),';
							$query_log = 'SELECT label
                                FROM #__emundus_setup_action_tag
                                WHERE id =' . $tag;
							$this->_db->setQuery($query_log);
							$log_tag = $this->_db->loadResult();

							$logsStd          = new stdClass();
							$logsStd->details = $log_tag;
							$logger[]         = $logsStd;
						}
					}

					if (!empty($logger)) {
                        $fnumInfos = $this->getFnumInfos($fnum);
						$logsParams = array('created' => array_unique($logger, SORT_REGULAR));
						EmundusModelLogs::log($user_id, (int)$fnumInfos['applicant_id'], $fnum, 14, 'c', 'COM_EMUNDUS_ACCESS_TAGS_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}
				}

				if($insert_tags) {
					$query = substr_replace($query, ';', -1);
					$this->_db->setQuery($query);
					$tagged = $this->_db->execute();
				} else {
					$tagged = true;
				}
			}
			catch (Exception $e) {
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			$onAfterTagAddEvtHandler = new GenericEvent(
				'onCallEventHandler',
				[
					'onAfterTagAdd',
					[
						'fnums' => $fnums,
						'tag' => $tags,
						'tagged' => $tagged,
						'context' => new EventContextEntity(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id), $fnums, [], [
							onAfterTagAddDefinition::TAGS_KEY => $tags,
						]),
						'execution_context' => $executionContext
					]
				]
			);

			$dispatcher = Factory::getApplication()->getDispatcher();
			$dispatcher->dispatch('onCallEventHandler', $onAfterTagAddEvtHandler);
		}

		return $tagged;
	}


	/**
	 * @param   null  $tag
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getTaggedFile($tag = null)
	{

		$query = 'select t.fnum, sat.class from #__emundus_tag_assoc as t join #__emundus_setup_action_tag as sat on sat.id = t.id_tag where ';
		$user  = $this->app->getIdentity()->id;

		if (is_null($tag)) {
			$query .= ' t.user_id = ' . $user;
			try {
				$this->_db->setQuery($query);

				return $this->_db->loadAssocList('fnum');
			}
			catch (Exception $e) {
				throw $e;
			}

		}
		else {

			$user = $this->app->getIdentity()->id;

			if (is_array($tag))
				$query .= ' t.id_tag IN (' . implode(',', $tag) . ') and t.user_id = ' . $user;
			else
				$query .= ' t.id_tag = ' . $tag . ' and t.user_id = ' . $user;

			try {
				$this->_db->setQuery($query);

				return $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				throw $e;
			}
		}
	}

	/**
	 * @param $fnums
	 * @param $state
	 *
	 * @return bool|mixed
	 */
	public function updateState($fnums, $state, $user_id = null, ?AutomationExecutionContext $executionContext = null)
	{
		$res = false;

		if(empty($user_id))
		{
			$user = $this->app->getIdentity();
			if(!empty($user)) {
				$user_id = $user->id;
			} else {
				$eMConfig = ComponentHelper::getParams('com_emundus');
				$user_id = $eMConfig->get('automated_task_user', 62);
			}
		}

		if (!empty($fnums) && isset($state)) {
			$all_status = $this->getAllStatus($user_id, 'step');

			if (isset($all_status[$state])) {
				$query = $this->_db->getQuery(true);
				$fnums = is_array($fnums) ? $fnums : [$fnums];
				$students = [];

				try {
					$query->select($this->_db->quoteName('profile'))
						->from($this->_db->quoteName('#__emundus_setup_status'))
						->where($this->_db->quoteName('step') . ' = ' . $state);
					$this->_db->setQuery($query);
					$profile = $this->_db->loadResult();

					$this->app->triggerEvent('onBeforeMultipleStatusChange', [$fnums, $state]);
					$trigger = $this->app->triggerEvent('onCallEventHandler', ['onBeforeMultipleStatusChange', ['fnums' => $fnums, 'state' => $state]]);
					foreach ($trigger as $responses) {
						foreach ($responses as $response) {
							if (!empty($response) && isset($response['status']) && $response['status'] === false) {
								return $response;
							}
						}
					}

					foreach ($fnums as $fnum) {
						$query->clear()
							->select('status')
							->from('#__emundus_campaign_candidature')
							->where('fnum LIKE ' . $this->_db->quote($fnum));

						$this->_db->setQuery($query);
						$old_status_step = $this->_db->loadResult();

						$this->app->triggerEvent('onBeforeStatusChange', [$fnum, $state]);
						$trigger = $this->app->triggerEvent('onCallEventHandler', ['onBeforeStatusChange', ['fnum' => $fnum, 'state' => $state, 'old_state' => $old_status_step]]);
						foreach ($trigger as $responses) {
							foreach ($responses as $response) {
								if (!empty($response) && isset($response['status']) && $response['status'] === false) {
									return $response;
								}
							}
						}

						$query->clear()
							->update($this->_db->quoteName('#__emundus_campaign_candidature'))
							->set($this->_db->quoteName('status') . ' = ' . $state)
							->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->Quote($fnum));

						$this->_db->setQuery($query);
						$res = $this->_db->execute();

						$old_status_lbl = $all_status[$old_status_step]['value'];
						$new_status_lbl = $all_status[$state]['value'];

						$fnumInfos = $this->getFnumInfos($fnum, 0, false);
						if ($res) {
							$query->clear()
								->insert('#__emundus_fnums_status_date')
								->columns(['fnum', 'status', 'date_time'])
								->values($this->_db->quote($fnum) . ', ' . $this->_db->quote($state) . ', ' . $this->_db->quote(date('Y-m-d H:i:s')));

							$this->_db->setQuery($query);
							$this->_db->execute();

							$students[$fnum] = new stdClass();
							$students[$fnum]->id = $fnumInfos['applicant_id'];
							$students[$fnum]->name = $fnumInfos['name'];
							$students[$fnum]->email = $fnumInfos['email'];
							$students[$fnum]->fnum = $fnum;
							$students[$fnum]->campaign_id = $fnumInfos['campaign_id'];
							$students[$fnum]->code = $fnumInfos['training'];
							$students[$fnum]->is_anonym = $fnumInfos['is_anonym'];

							$logs_params = ['updated' => [['old' => $old_status_lbl, 'new' => $new_status_lbl, 'old_id' => $old_status_step, 'new_id' => $state]]];
							EmundusModelLogs::log($user_id, $fnumInfos['applicant_id'], $fnum, 13, 'u', 'COM_EMUNDUS_ACCESS_STATUS_UPDATE', json_encode($logs_params, JSON_UNESCAPED_UNICODE));

							PluginHelper::importPlugin('emundus'); // si event call event handler
							$dispatcher = Factory::getApplication()->getDispatcher();

							$onAfterStatusChangeEventHandler = new GenericEvent(
								'onCallEventHandler',
								['onAfterStatusChange',
									// Datas to pass to the event
									[
										'fnum' => $fnum,
									    'state' => $state,
									    'old_state' => $old_status_step,
										'context' => new EventContextEntity(
											Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id),
											[$fnum],
											[$fnumInfos['applicant_id']],
											[
												onAfterStatusChangeDefinition::STATUS_PARAMETER => $state,
												onAfterStatusChangeDefinition::OLD_STATUS_PARAMETER => $old_status_step
											]
										),
										'execution_context' => $executionContext
									]
								]
							);
							$onAfterStatusChange = new GenericEvent(
								'onAfterStatusChange',
								// Datas to pass to the event
								['fnum' => $fnum, 'state' => $state, 'old_state' => $old_status_step]
							);

							// Dispatch the event
							$dispatcher->dispatch('onCallEventHandler', $onAfterStatusChangeEventHandler);
							$dispatcher->dispatch('onAfterStatusChange', $onAfterStatusChange);

							if (!empty($profile)) {
								$query->clear()
									->update($this->_db->quoteName('#__emundus_users'))
									->set($this->_db->quoteName('profile') . ' = ' . $profile)
									->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
						else {
							$logs_params = ['updated' => [['old' => $old_status_lbl, 'new' => $new_status_lbl, 'old_id' => $old_status_step, 'new_id' => $state]]];
							EmundusModelLogs::log($user_id, $fnumInfos['applicant_id'], $fnum, 13, 'u', 'COM_EMUNDUS_ACCESS_STATUS_UPDATE_FAILED', json_encode($logs_params, JSON_UNESCAPED_UNICODE));
						}
					}

					$fnums_updated = array_keys($students);
					$this->makeAttachmentsEditableByApplicant($fnums_updated, $state);
				}
				catch (Exception $e) {
					echo $e->getMessage();
					Log::add('USER ID : ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}

				if (!empty($students)) {
					$res = [
						'status' => true,
						'msg'    => $this->sendEmailAfterUpdateState($state, $students, $user_id)
					];
				}
			}
		}

		return $res;
	}


	/**
	 * @param $fnums
	 * @param $publish
	 *
	 * @return bool|mixed
	 */
	public function updatePublish($fnums, $publish, $user_id = null)
	{


		foreach ($fnums as $fnum) {
			// Log the update in the eMundus logging system.
			// Get the old publish status
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('published'))
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' = ' . $fnum);
			$this->_db->setQuery($query);
			$old_publish = $this->_db->loadResult();
			// Before logging, translate the publish id to corresponding label
			// Old publish status
			switch ($old_publish) {
				case(1):
					$old_publish_lbl = Text::_('PUBLISHED');
					break;
				case(0):
					$old_publish_lbl = Text::_('ARCHIVED');
					break;
				case(-1):
					$old_publish_lbl = Text::_('TRASHED');
					break;
			}
			// New publish status
			switch ($publish) {
				case(1):
					$new_publish_lbl = Text::_('PUBLISHED');
					break;
				case(0):
					$new_publish_lbl = Text::_('ARCHIVED');
					break;
				case(-1):
					$new_publish_lbl = Text::_('TRASHED');
					break;
			}
			// Log the update
			$logsParams = ['updated' => [['old' => $old_publish_lbl, 'new' => $new_publish_lbl, 'old_id' => $old_publish, 'new_id' => $publish]]];

			if (empty($user_id)) {
				$user = $this->app->getIdentity();
				$user_id = !empty($user->id) ? $user->id : 62;
			}

			// get the applicant id
			$query->clear()
				->select($this->_db->quoteName('applicant_id'))
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);
			$applicant_id = $this->_db->loadResult();
			EmundusModelLogs::log($user_id, $applicant_id, $fnum, 28, 'u', 'COM_EMUNDUS_PUBLISH_UPDATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));

			// Update publish
			JFactory::getApplication()->triggerEvent('onBeforePublishChange', [$fnum, $publish]);
			JFactory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforePublishChange', ['fnum' => $fnum, 'publish' => $publish]]);
			$query->clear()
				->update($this->_db->quoteName('#__emundus_campaign_candidature'))
				->set($this->_db->quoteName('published') . ' = ' . $this->_db->quote($publish))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);
			try {
				$res = $this->_db->execute();
			}
			catch (Exception $e) {
				echo $e->getMessage();
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

				return false;
			}
			JFactory::getApplication()->triggerEvent('onAfterPublishChange', [$fnum, $publish]);
			JFactory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterPublishChange', ['fnum' => $fnum, 'publish' => $publish]]);
		}

		return $res;
	}

	/**
	 * @param   array  $fnums
	 *
	 * @return mixed|null
	 */
	public function getPhotos($fnums = array())
	{
		$photos = array();
		$query  = $this->_db->getQuery(true);

		$attachment_id = ComponentHelper::getParams('com_emundus')->get('photo_attachment', '');

		if(!empty($attachment_id)) {
			try {
				$query->select('emu.id, emu.user_id, c.fnum, emu.filename')
					->from($this->_db->quoteName('#__emundus_uploads', 'emu'))
					->join('LEFT', $this->_db->quoteName('#__emundus_campaign_candidature', 'c') . ' ON (' . $this->_db->quoteName('c.applicant_id') . ' = ' . $this->_db->quoteName('emu.user_id') . ')')
					->where($this->_db->quoteName('attachment_id') . ' = ' . $attachment_id);

				if (count($fnums) > 0) {
					$query->where($this->_db->quoteName('c.fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')')
						->group($this->_db->quoteName('emu.fnum'));
				}

				$this->_db->setQuery($query);
				$photos = $this->_db->loadAssocList('fnum');

			}
			catch (Exception $e) {
				echo $e->getMessage();
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $photos;
	}

	/**
	 * @return mixed|null
	 */
	public function getEvaluatorsFromGroup()
	{
		try {

			$query = 'select distinct ga.fnum, u.name, g.title, g.id  from #__emundus_group_assoc  as ga
						left join #__user_usergroup_map as uum on uum.group_id = ga.group_id
						left join #__users as u on u.id = uum.user_id
						left join #__usergroups as g on g.id = ga.group_id
						where 1 order by ga.fnum asc, g.title';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return null;
		}
	}

	/**
	 * @return mixed|null
	 */
	public function getEvaluators()
	{
		try {

			$query = 'select * from #__usergroups where title = "Evaluator"';
			$this->_db->setQuery($query);
			$eval = $this->_db->loadAssoc();

			$query = 'select distinct ua.fnum, u.name, u.id from #__emundus_users_assoc  as ua left join #__users as u on u.id = ua.user_id where 1';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return null;
		}
	}

	/**
	 * @param $fnum
	 * @param $id
	 * @param $isGroup
	 *
	 * @return bool|mixed
	 */
	public function unlinkEvaluators($fnum, $id, $isGroup)
	{
		try {


			if ($isGroup) {
				$query = 'delete from #__emundus_group_assoc where fnum like ' . $this->_db->Quote($fnum) . ' and group_id =' . $id;
			}
			else {
				$query = 'delete from #__emundus_users_assoc where fnum like ' . $this->_db->Quote($fnum) . ' and user_id =' . $id;
			}

			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getFnumInfos($fnum, $user_id = 0, bool $check_is_anonym = true)
	{
		$fnumInfos = [];

		if (empty($user_id)) {
			$user = $this->app->getIdentity();
			$user_id = !empty($user) && !empty($user->id) ? $user->id : 0;
		}

		try {
			$query = $this->_db->getQuery(true);
			$query->select('cc.id as ccid, u.name, u.email, u.username, cc.fnum, cc.date_submitted, cc.applicant_id, cc.status, cc.published as state, cc.form_progress, cc.attachment_progress, ss.value, ss.class, c.*, cc.campaign_id, eu.is_anonym')
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $this->_db->quoteName('c.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
				->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('cc.applicant_id'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->_db->quoteName('eu.user_id') . ' = ' . $this->_db->quoteName('cc.applicant_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'ss') . ' ON ' . $this->_db->quoteName('ss.step') . ' = ' . $this->_db->quoteName('cc.status'))
				->where($this->_db->quoteName('cc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);
			$fnumInfos = $this->_db->loadAssoc();

			$anonymize_data = EmundusHelperAccess::isDataAnonymized($user_id) || ($check_is_anonym && $fnumInfos['is_anonym'] == 1);
			if ($anonymize_data) {
				$fnumInfos['name']  = $fnum;
				$fnumInfos['email'] = $fnum;
			}
		}
		catch (Exception $e) {
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $fnumInfos;
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getFnumsInfos($fnums, $format = 'array')
	{
		try {

			$query = 'select u.name, u.email, cc.fnum, ss.step, ss.value, sc.label, sc.start_date, sc.end_date, sc.year, sc.id as campaign_id, sc.published, sc.training, cc.applicant_id, eu.is_anonym
                        from #__emundus_campaign_candidature as cc
                        left join #__users as u on u.id = cc.applicant_id
                        left join #__emundus_users as eu on eu.user_id = cc.applicant_id
                        left join #__emundus_setup_campaigns as sc on sc.id = cc.campaign_id
                        left join #__emundus_setup_status as ss on ss.step = cc.status
                        where cc.fnum in ("' . implode('","', $fnums) . '")';
			$this->_db->setQuery($query);

			if ($format == 'array') {
				return $this->_db->loadAssocList('fnum');
			}
			else {
				return $this->_db->loadObjectList('fnum');
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getFnumsTagsInfos($fnums)
	{
		try {

			$query = 'select u.name as applicant_name, u.email as applicant_email, u.username as username, cc.fnum, sc.id as campaign_id , sc.label as campaign_label, sc.training as campaign_code,  sc.start_date as campaign_start, sc.end_date as campaign_end, sc.year as campaign_year,  jesp.code as training_code, jesp.label as training_programme, cc.applicant_id as applicant_id, jess.value as application_status, group_concat(jesat.label) as application_tags
                        from jos_emundus_campaign_candidature as cc
                        left join jos_users as u on u.id = cc.applicant_id
                        left join jos_emundus_setup_campaigns as sc on sc.id = cc.campaign_id
                        left join jos_emundus_setup_programmes as jesp on jesp.code = sc.training
                        left join jos_emundus_setup_status as jess on jess.step = cc.status
                        left join jos_emundus_tag_assoc as jeta on jeta.fnum = cc.fnum
                        left join jos_emundus_setup_action_tag as jesat on jesat.id = jeta.id_tag
                        where cc.fnum in ("' . implode('","', $fnums) . '")
                        group by cc.fnum';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList('fnum');
		}
		catch (Exception $e) {
			return false;
		}
	}

	/** Gets info for Fabrik tags.
	 *
	 * @param   String  $fnum
	 *
	 * @return bool|mixed
	 */
	public function getFnumTagsInfos($fnum)
	{
		try {

			$query = 'select u.name as applicant_name, u.email as applicant_email, u.username as username, cc.fnum, sc.id as campaign_id , sc.label as campaign_label, sc.training as campaign_code,  sc.start_date as campaign_start, sc.end_date as campaign_end, sc.year as campaign_year,  jesp.code as training_code, jesp.label as training_programme, cc.applicant_id as applicant_id, jess.value as application_status, group_concat(jesat.label) as application_tags
                        from jos_emundus_campaign_candidature as cc
                        left join jos_users as u on u.id = cc.applicant_id
                        left join jos_emundus_setup_campaigns as sc on sc.id = cc.campaign_id
                        left join jos_emundus_setup_programmes as jesp on jesp.code = sc.training
                        left join jos_emundus_setup_status as jess on jess.step = cc.status
                        left join jos_emundus_tag_assoc as jeta on jeta.fnum = cc.fnum
                        left join jos_emundus_setup_action_tag as jesat on jesat.id = jeta.id_tag
                        where cc.fnum = ' . $fnum;
			$this->_db->setQuery($query);

			return $this->_db->loadAssoc();
		}
		catch (Exception $e) {
			return false;
		}
	}


	/** Gets applicant_id from fnum
	 *
	 * @param   String  $fnum
	 *
	 * @return int
	 */
	public function getApplicantIdByFnum($fnum)
	{

		$query = $this->_db->getQuery(true);

		try {
			$query->select($this->_db->quoteName('applicant_id'))
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);

			return $this->_db->loadResult();
		}
		catch (Exception $e) {
			return 0;
		}
	}


	/**
	 * @param        $fnum
	 * @param   int  $published
	 *
	 * @return bool|mixed
	 */
	public function changePublished($fnum, $published = -1)
	{
		try {

			$query = "update #__emundus_campaign_candidature set published = " . $published . " where fnum like " . $this->_db->quote($fnum);
			$this->_db->setQuery($query);
			$res = $this->_db->execute();

			return $res;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 *
	 * @return array
	 */
	public function getAllFnums($assoc_tab_fnums = false, $user_id = null, int $menu_id = 0): array
	{
		$fnums = array();
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

		try {
			$files = $this->getAllUsers(0, 0, $menu_id);

			if (!empty($files)) {
				if ($assoc_tab_fnums) {
					foreach ($files as $file) {
						if ($file['applicant_id'] > 0) {
							$fnums[] = array('fnum'         => $file['fnum'],
							                 'applicant_id' => $file['applicant_id'],
							                 'campaign_id'  => $file['campaign_id']
							);
						}
					}
				}
				else {
					foreach ($files as $file) {
						$fnums[] = $file['fnum'];
					}
				}
			}
		} catch (Exception $e) {
			Log::add('Error when get all fnums : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $fnums;
	}

	/*
    *   Get values of elements by list of files numbers
    *   @param fnums    List of application files numbers
    *   @param elements     array of element to get value
    *   @return array
    */
	public function getFnumArray($fnums, $elements, $methode = 0, $start = 0, $pas = 0, $raw = 0, $defaultElement = '',$user = null)
	{
		if(empty($user)) {
			$user = $this->app->getIdentity();
		}
		$locales = substr($this->app->getLanguage()->getTag(), 0, 2);

		$anonymize_data = EmundusHelperAccess::isDataAnonymized($user->id);

		if (empty($defaultElement)) {
			if ($anonymize_data) {
				$query = 'select jos_emundus_campaign_candidature.fnum, esc.label, sp.code, esc.id as campaign_id';
			}
			else {
				$query = 'select jos_emundus_campaign_candidature.fnum,' .
					'CASE WHEN eu.is_anonym = 0 THEN u.email ELSE ' . $this->_db->quote(Text::_('COM_EMUNDUS_ANONYM_ACCOUNT')) . ' END as u.email, '
				.', esc.label, sp.code, esc.id as campaign_id';
			}
		}
		else {
			$query = $defaultElement;
		}


		$leftJoin      = '';
		$leftJoinMulti = '';
		$tableAlias    = [
			'jos_emundus_setup_campaigns'      => 'esc',
			'jos_emundus_campaign_candidature' => 'jos_emundus_campaign_candidature',
			'jos_emundus_setup_programmes'     => 'sp',
			'jos_users'                        => 'u',
			'jos_emundus_users'                => 'eu',
			'jos_emundus_tag_assoc'            => 'eta'
		];
		$lastTab       = array();

		foreach ($elements as $elt) {
			$params_group = json_decode($elt->group_attribs);

			try {
				$query_isjoin = 'select is_join from jos_fabrik_groups where id = ' . $elt->group_id;
				$this->_db->setQuery($query_isjoin);
				$is_join = $this->_db->loadResult();
			}
			catch (Exception $e) {
				Log::add('Error when get param is_join from group : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}


			if (!array_key_exists($elt->tab_name, $tableAlias)) {

				$tableAlias[$elt->tab_name] = $elt->tab_name;

				if (!isset($lastTab)) {
					$lastTab = array();
				}
				if (!in_array($elt->tab_name, $lastTab)) {
					$leftJoin .= ' left join ' . $elt->tab_name . ' on ' . $elt->tab_name . '.fnum = jos_emundus_campaign_candidature.fnum ';
				}

				$lastTab[] = $elt->tab_name;
			}

			if ($params_group->repeat_group_button == 1 || $is_join == 1) {
				$if    = array();
				$endif = '';

				// Get the table repeat table name using this query
				$repeat_join_table_query = 'SELECT table_join FROM #__fabrik_joins WHERE group_id=' . $elt->group_id . ' AND table_join_key like "parent_id"';
				try {
					$this->_db->setQuery($repeat_join_table_query);
					$repeat_join_table = $this->_db->loadResult();
				}
				catch (Exception $e) {
					Log::add('Line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
					throw $e;
				}
				if ($methode == 1) {
					if ($elt->element_plugin == 'databasejoin') {
						$element_attribs = json_decode($elt->element_attribs);

						if ($element_attribs->database_join_display_type == "checkbox") {

							$t = $elt->table_join;

							$join_query = $this->_db->getQuery(true);

							$join_query
								->select($this->_db->quoteName('join_from_table'))
								->from($this->_db->quoteName('#__fabrik_joins'))
								->where($this->_db->quoteName('element_id') . ' = ' . $elt->id);

							$this->_db->setQuery($join_query);

							try {
								$join_table = $this->_db->loadResult();

								$join_val_column = !empty($element_attribs->join_val_column_concat)
									? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')'
									: 't.' . $element_attribs->join_val_column;

								$sub_select = '(
                                        SELECT ' . $t . '.parent_id AS pid, CONCAT("[\"",GROUP_CONCAT(' . $join_val_column . ' SEPARATOR ", \""), "\"]") AS vals
                                        FROM ' . $t . '
                                        LEFT JOIN ' . $element_attribs->join_db_name . ' AS  t ON ' . 't.' . $element_attribs->join_key_column . ' = ' . $t . '.' . $elt->element_name . '
                                        GROUP BY pid
                                  ) sub_select';

								$select = '(SELECT GROUP_CONCAT(vals) FROM ' . $sub_select . ' WHERE sub_select.pid = ' . $join_table . '.id)';

							}
							catch (Exception $e) {
								$error = Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage() . ' :: ' . preg_replace("/[\r\n]/", " ", $query);
								Log::add($error, Log::ERROR, 'com_emundus');
							}

						}
						else {

							$join_val_column = !empty($element_attribs->join_val_column_concat) ? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')' : 't.' . $element_attribs->join_val_column;

							$select .= 'FROM ' . $tableAlias[$elt->tab_name] . '
                                    LEFT JOIN ' . $repeat_join_table . ' ON ' . $repeat_join_table . '.parent_id = ' . $tableAlias[$elt->tab_name] . '.id
                                    LEFT JOIN ' . $element_attribs->join_db_name . ' as t ON t.' . $element_attribs->join_key_column . ' = ' . $repeat_join_table . '.' . $elt->element_name . '
                                    WHERE ' . $tableAlias[$elt->tab_name] . '.fnum=jos_emundus_campaign_candidature.fnum)';
						}

						$query .= ', ' . $select . ' AS ' . $elt->table_join . '___' . $elt->element_name;

					}
					elseif ($elt->element_plugin == 'cascadingdropdown') {
						$element_attribs         = json_decode($elt->element_attribs);
						$cascadingdropdown_id    = $element_attribs->cascadingdropdown_id;
						$r1                      = explode('___', $cascadingdropdown_id);
						$cascadingdropdown_label = $element_attribs->cascadingdropdown_label;
						$r2                      = explode('___', $cascadingdropdown_label);
						$select                  = !empty($element_attribs->cascadingdropdown_label_concat) ? "CONCAT(" . $element_attribs->cascadingdropdown_label_concat . ")" : $r2[1];
						$from                    = $r2[0];

						// Checkboxes behave like repeat groups and therefore need to be handled a second level of depth.
						if ($element_attribs->cdd_display_type == 'checkbox') {
							$select = !empty($element_attribs->cascadingdropdown_label_concat) ? " CONCAT(" . $element_attribs->cascadingdropdown_label_concat . ")" : 'GROUP_CONCAT(' . $r2[1] . ')';

							// Load the Fabrik join for the element to it's respective repeat_repeat table.
							$q = $this->_db->getQuery(true);
							$q->select([$this->_db->quoteName('join_from_table'), $this->_db->quoteName('table_key'), $this->_db->quoteName('table_join'), $this->_db->quoteName('table_join_key')])
								->from($this->_db->quoteName('#__fabrik_joins'))
								->where($this->_db->quoteName('element_id') . ' = ' . $elt->table_join . '.' . $elt->element_name);
							$this->_db->setQuery($q);
							$f_join = $this->_db->loadObject();

							$where = $r1[1] . ' IN (
                                SELECT ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_key) . '
                                FROM ' . $this->_db->quoteName($f_join->table_join) . ' 
                                WHERE ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_join_key) . ' = ' . $elt->id . ')';
						}
						else {
							$where = $r1[1] . '=' . $elt->table_join . '.' . $elt->element_name;
						}

						$sub_query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
						$sub_query = preg_replace('#{thistable}#', $from, $sub_query);
						$sub_query = preg_replace('#{shortlang}#', $locales, $sub_query);

						$query .= ', (' . $sub_query . ') AS ' . $elt->table_join . '___' . $elt->element_name;
					}
					elseif ($elt->element_plugin == 'dropdown' || $elt->element_plugin == 'radiobutton') {
						$select          = $elt->table_join . '.' . $elt->element_name;
						$element_attribs = json_decode($elt->element_attribs);
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							if (empty($first_replace)) {
								$select = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
							}
							else {
								$select .= ',REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
							}
						}
						$query .= ', ' . $select . ' AS ' . $elt->table_join . '___' . $elt->element_name;
						if (!in_array($elt->table_join, $lastTab)) {
							$leftJoinMulti .= ' left join ' . $elt->table_join . ' on ' . $elt->table_join . '.parent_id=' . $elt->tab_name . '.id ';
						}
					}
					else {
						$query .= ', ' . $elt->table_join . '.' . $elt->element_name . ' AS ' . $elt->table_join . '___' . $elt->element_name;
						if (!in_array($elt->table_join, $lastTab)) {
							$leftJoinMulti .= ' left join ' . $elt->table_join . ' on ' . $elt->table_join . '.parent_id=' . $elt->tab_name . '.id ';
						}
					}
					$lastTab[] = $elt->table_join;
				}
				else {
					if ($elt->element_plugin == 'databasejoin') {

						$element_attribs = json_decode($elt->element_attribs);

						if ($element_attribs->database_join_display_type == "checkbox") {

							$t = $elt->table_join;

							$join_query = $this->_db->getQuery(true);

							$join_query
								->select($this->_db->quoteName('join_from_table'))
								->from($this->_db->quoteName('#__fabrik_joins'))
								->where($this->_db->quoteName('element_id') . ' = ' . $elt->id);

							$this->_db->setQuery($join_query);

							try {
								$join_table = $this->_db->loadResult();

								$join_val_column = !empty($element_attribs->join_val_column_concat) ?
									'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')' :
									't.' . $element_attribs->join_val_column;

								$sub_select = '(
                                    SELECT ' . $t . '.parent_id AS pid, ' . $elt->tab_name . '_' . $elt->group_id . '_repeat.parent_id AS pid2, CONCAT("[\"",GROUP_CONCAT(' . $join_val_column . ' SEPARATOR ", \""), "\"]") AS vals
                                    FROM ' . $t . '
                                    LEFT JOIN ' . $element_attribs->join_db_name . ' AS  t ON ' . 't.' . $element_attribs->join_key_column . ' = ' . $t . '.' . $elt->element_name . '
                                    LEFT JOIN ' . $join_table . ' ON ' . $elt->table_join . '.parent_id = ' . $join_table . '.id
                                    WHERE ' . $t . '.parent_id=' . $elt->tab_name . '_' . $elt->group_id . '_repeat.id
                                        GROUP BY pid
                                  ) sub_select';

								$select = '(SELECT GROUP_CONCAT(vals) FROM ' . $sub_select . ' WHERE pid2 = ' . $elt->tab_name . '.id)';

							}
							catch (Exception $e) {
								$error = Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage() . ' :: ' . preg_replace("/[\r\n]/", " ", $query);
								Log::add($error, Log::ERROR, 'com_emundus');
							}

						}
						else {
							$join_val_column = !empty($element_attribs->join_val_column_concat) ? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')' : 't.' . $element_attribs->join_val_column;

							if ($methode == 2) {
								$select = '(SELECT GROUP_CONCAT(' . $join_val_column . ' SEPARATOR ", ") ';
							}
							else {
								$select = '(SELECT GROUP_CONCAT(DISTINCT(' . $join_val_column . ') SEPARATOR ", ") ';
							}

							$select .= 'FROM ' . $tableAlias[$elt->tab_name] . '
                                    LEFT JOIN ' . $repeat_join_table . ' ON ' . $repeat_join_table . '.parent_id = ' . $tableAlias[$elt->tab_name] . '.id
                                    LEFT JOIN ' . $element_attribs->join_db_name . ' as t ON t.' . $element_attribs->join_key_column . ' = ' . $repeat_join_table . '.' . $elt->element_name . '
                                    WHERE ' . $tableAlias[$elt->tab_name] . '.fnum=jos_emundus_campaign_candidature.fnum)';
						}

						$query .= ', ' . $select . ' AS ' . $elt->table_join . '___' . $elt->element_name;
					}
					elseif ($elt->element_plugin == 'cascadingdropdown') {

						$element_attribs = json_decode($elt->element_attribs);
						$from            = explode('___', $element_attribs->cascadingdropdown_label)[0];
						$where           = explode('___', $element_attribs->cascadingdropdown_id)[1] . '=' . $repeat_join_table . '.' . $elt->element_name;
						$join_val_column = !empty($element_attribs->cascadingdropdown_label_concat) ? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->cascadingdropdown_label_concat)) . ')' : 't.' . explode('___', $element_attribs->cascadingdropdown_label)[1];

                        if ($methode == 2) {
                            $select = '(SELECT GROUP_CONCAT(('.$join_val_column.') SEPARATOR ", ") ';
                        } else {
                            $select = '(SELECT GROUP_CONCAT(DISTINCT('.$join_val_column.') SEPARATOR ", ") ';
                        }

                        $select .= 'FROM '.$tableAlias[$elt->tab_name].'
                                LEFT JOIN ' . $repeat_join_table . ' ON ' . $repeat_join_table . '.parent_id = ' . $tableAlias[$elt->tab_name] . '.id
                                LEFT JOIN ' . $from . ' as t ON t.' . $where . '
                                WHERE ' . $tableAlias[$elt->tab_name] . '.fnum=jos_emundus_campaign_candidature.fnum)';

						$query .= ', ' . $select . ' AS ' . $elt->table_join . '___' . $elt->element_name;
					}
					elseif ($elt->element_plugin == 'dropdown' || $elt->element_plugin == 'radiobutton') {
						$select = 'REPLACE(`' . $elt->table_join . '`.`' . $elt->element_name . '`, "\t", "" )';
						if ($raw != 1) {
							$element_attribs = json_decode($elt->element_attribs);
							foreach ($element_attribs->sub_options->sub_values as $key => $value) {
								$if[]  = 'IF(' . $select . '="' . $value . '","' . Text::_($element_attribs->sub_options->sub_labels[$key]) . '"';
								$endif .= ')';
							}
							$select = implode(',', $if) . ',' . $select . $endif;
						}

                        if ($methode == 2) {
                            $select = '(SELECT GROUP_CONCAT(('.$select.') ORDER BY `'.$repeat_join_table.'`.`id` SEPARATOR ", ") ';
                        } else {
                            $select = '(SELECT GROUP_CONCAT(DISTINCT ('.$select.') ORDER BY `'.$repeat_join_table.'`.`id` SEPARATOR ", ") ';
                        }

                        $select .= 'FROM '.$tableAlias[$elt->tab_name].'
                                LEFT JOIN ' . $repeat_join_table . ' ON ' . $repeat_join_table . '.parent_id = ' . $tableAlias[$elt->tab_name] . '.id
                                WHERE ' . $tableAlias[$elt->tab_name] . '.fnum=jos_emundus_campaign_candidature.fnum)';
						$query  .= ', ' . $select . ' AS ' . $elt->table_join . '___' . $elt->element_name;
					}
                    elseif ($elt->element_plugin == 'checkbox') {
                        $element_table_alias = $elt->table_join;

                        $regexp_sub_query = $element_table_alias . '.' . $elt->element_name . ' '; // default value if no sub_options

                        $element_attribs = json_decode($elt->element_attribs);
                        if (!empty($element_attribs->sub_options->sub_values)) {
                            foreach ($element_attribs->sub_options->sub_values as $sub_key => $sub_value) {
                                $sub_label = JText::_($element_attribs->sub_options->sub_labels[$sub_key]);
                                $sub_label = $sub_label === '' ? $element_attribs->sub_options->sub_labels[$sub_key] : $sub_label;
                                $sub_label = str_replace("'", "\'", $sub_label); // escape sub label single quotes for SQL query
                                $sub_value = str_replace("'", "\'", $sub_value);
                                $sub_value = str_replace("*", "\\\*", $sub_value); // escape asterisk for SQL query, rare case but possible

                                if ($sub_key === 0) {
                                    $regexp_sub_query = 'REGEXP_REPLACE(' . $element_table_alias . '.' . $elt->element_name . ', \'"' . $sub_value . '"\', \'' . $sub_label . '\')';
                                } else {
                                    $regexp_sub_query = 'REGEXP_REPLACE(' . $regexp_sub_query . ', \'"' . $sub_value . '"\', \'' . $sub_label . '\')';
                                }
                            }

                            // we also want to remove the brackets
                            $regexp_sub_query = 'REPLACE(' . $regexp_sub_query . ', \'[\', \'\')';
                            $regexp_sub_query = 'REPLACE(' . $regexp_sub_query . ', \']\', \'\')';
                        }

                        $query .= ', (SELECT GROUP_CONCAT(CONCAT(\'"\',(' . $regexp_sub_query . '),\'"\')) FROM '.$elt->table_join.' WHERE '.$elt->table_join.'.parent_id='.$tableAlias[$elt->tab_name].'.id) AS '. $elt->table_join.'___'.$elt->element_name;
                    }
					elseif ($elt->element_plugin == 'yesno') {
						$select = 'REPLACE(`' . $elt->table_join . '`.`' . $elt->element_name . '`, "\t", "" )';
						if ($raw != 1) {
							$if[]   = 'IF(' . $select . '="0","' . Text::_('JNO') . '"';
							$endif  .= ')';
							$if[]   = 'IF(' . $select . '="1","' . Text::_('JYES') . '"';
							$endif  .= ')';
							$select = implode(',', $if) . ',' . $select . $endif;
							$query  .= ', ( SELECT GROUP_CONCAT(' . $select . ' SEPARATOR ", ") ';
						}

						$query .= ' FROM ' . $elt->table_join . '
                                        WHERE ' . $elt->table_join . '.parent_id=' . $tableAlias[$elt->tab_name] . '.id
                                      ) AS ' . $elt->table_join . '___' . $elt->element_name;

					}
					else {

						if ($methode == 2) {
							$query .= ', ( SELECT GROUP_CONCAT(' . $elt->table_join . '.' . $elt->element_name . ' SEPARATOR ", ") ';
						}
						else {
							$query .= ', ( SELECT GROUP_CONCAT(DISTINCT(' . $elt->table_join . '.' . $elt->element_name . ') SEPARATOR ", ") ';
						}

						$query .= ' FROM ' . $elt->table_join . '
                                        WHERE ' . $elt->table_join . '.parent_id=' . $tableAlias[$elt->tab_name] . '.id
                                      ) AS ' . $elt->table_join . '___' . $elt->element_name;
					}
				}
			}
			else {
				$select = 'REPLACE(`' . $tableAlias[$elt->tab_name] . '`.`' . $elt->element_name . '`, "\t", "" )';
				$if     = array();
				$endif  = '';


				if ($elt->element_plugin == 'dropdown' || $elt->element_plugin == 'radiobutton' || $elt->element_plugin == 'checkbox') {
					if ($raw == 1) {
						$select = 'REPLACE(`' . $tableAlias[$elt->tab_name] . '`.`' . $elt->element_name . '`, "\t", "" )';
					}
					else {
						$element_attribs = json_decode($elt->element_attribs);
						if ($elt->element_plugin == 'checkbox') {
							$if = '';
						}
						foreach ($element_attribs->sub_options->sub_values as $key => $value) {
							if ($elt->element_plugin == 'checkbox') {
								if (empty($if)) {
									$if = 'REGEXP_REPLACE(' . $select . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
								}
								else {
									$if = 'REGEXP_REPLACE(' . $if . ', "\\\b' . $value . '\\\b", "' . Text::_(addslashes($element_attribs->sub_options->sub_labels[$key])) . '")';
								}
							}
							else {
								$if[]  = 'IF(' . $select . '="' . $value . '","' . $element_attribs->sub_options->sub_labels[$key] . '"';
								$endif .= ')';
							}

						}
						if (is_array($if)) {
							$select = implode(',', $if) . ',' . $select . $endif;
						}
						else {
							$select = $if;
						}
					}
				}
				elseif ($elt->element_plugin == 'yesno') {
					if ($raw != 1) {
						$if[]   = 'IF(' . $select . '="0","' . Text::_('JNO') . '"';
						$endif  .= ')';
						$if[]   = 'IF(' . $select . '="1","' . Text::_('JYES') . '"';
						$endif  .= ')';
						$select = implode(',', $if) . ',' . $select . $endif;
					}
				}
				elseif ($elt->element_plugin == 'databasejoin') {

					$element_attribs = json_decode($elt->element_attribs);

					if ($element_attribs->database_join_display_type == "checkbox" || $element_attribs->database_join_display_type == "multilist") {
						$select_check = $element_attribs->join_val_column;
						if (!empty($element_attribs->join_val_column_concat)) {
							$select_check = $element_attribs->join_val_column_concat;
							$select_check = preg_replace('#{thistable}#', 'jd', $select_check);
							$select_check = preg_replace('#{shortlang}#', $this->locales, $select_check);
						}

						$t = $tableAlias[$elt->tab_name] . '_repeat_' . $elt->element_name;
						if ($raw == 1) {
							$select = '(
                                    SELECT GROUP_CONCAT(' . $elt->element_name . ' SEPARATOR ", ")
                                    FROM ' . $t . ' AS t
                                    WHERE ' . $tableAlias[$elt->tab_name] . '.id = t.parent_id
                                    )';
						}
						else {
							$select = '(
                                    SELECT GROUP_CONCAT(' . $select_check . ' SEPARATOR ", ")
                                    FROM ' . $t . ' AS t
                                    LEFT JOIN ' . $element_attribs->join_db_name . ' AS jd ON jd.' . $element_attribs->join_key_column . ' = t.' . $elt->element_name . '
                                    WHERE ' . $tableAlias[$elt->tab_name] . '.id = t.parent_id
                                    )';
						}
					}
					else {
						if ($raw == 1) {
							$query .= ', ' . $select . ' AS ' . $tableAlias[$elt->tab_name] . '___' . $elt->element_name;
						}
						else {
							$join_val_column = !empty($element_attribs->join_val_column_concat) ? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')' : 't.' . $element_attribs->join_val_column;

							$select = '(SELECT GROUP_CONCAT(DISTINCT(' . $join_val_column . ') SEPARATOR ", ")
                                FROM ' . $element_attribs->join_db_name . ' as t
                                WHERE t.' . $element_attribs->join_key_column . '=' . $tableAlias[$elt->tab_name] . '.' . $elt->element_name . ')';
						}
					}

				}
				elseif ($elt->element_plugin == 'cascadingdropdown') {

					$element_attribs = json_decode($elt->element_attribs);
					$from            = explode('___', $element_attribs->cascadingdropdown_label)[0];
					$where           = explode('___', $element_attribs->cascadingdropdown_id)[1] . '=' . $elt->tab_name . '.' . $elt->element_name;
					$join_val_column = !empty($element_attribs->cascadingdropdown_label_concat) ? 'CONCAT(' . str_replace('{thistable}', 't', str_replace('{shortlang}', $this->locales, $element_attribs->join_val_column_concat)) . ')' : 't.' . explode('___', $element_attribs->cascadingdropdown_label)[1];

					if ($raw == 1) {
						$select = '(SELECT ' . $elt->element_name . '
                            FROM ' . $tableAlias[$elt->tab_name] . ' as t
                            WHERE t.fnum LIKE ' . $this->_db->quote($fnums['fnum']) . ')';
					}
					else {
						$select = '(SELECT GROUP_CONCAT(DISTINCT(' . $join_val_column . ') SEPARATOR ", ")
                            FROM ' . $from . ' as t
                            WHERE t.' . $where . ')';
					}
				}

				$query .= ', ' . $select . ' AS ' . $tableAlias[$elt->tab_name] . '___' . $elt->element_name;

			}
		}

		$query .= ' from #__emundus_campaign_candidature as jos_emundus_campaign_candidature
                        left join #__users as u on u.id = jos_emundus_campaign_candidature.applicant_id
                        left join #__emundus_users as eu on u.id = eu.user_id
                        left join #__emundus_setup_campaigns as esc on esc.id = jos_emundus_campaign_candidature.campaign_id
                        left join #__emundus_setup_programmes as sp on sp.code = esc.training';

		if (!empty($defaultElement)) {
			$query .= ' LEFT JOIN #__emundus_tag_assoc as eta ON  eta.fnum = jos_emundus_campaign_candidature.fnum
                        LEFT JOIN #__emundus_setup_action_tag as esat ON esat.id= eta.id_tag
                        LEFT JOIN #__emundus_setup_status as ess ON ess.step = jos_emundus_campaign_candidature.status
                        LEFT JOIN #__emundus_declaration as ed ON ed.fnum = jos_emundus_campaign_candidature.fnum';
		}

		$query .= $leftJoin . ' ' . $leftJoinMulti;

		$query .= 'where u.block=0 AND jos_emundus_campaign_candidature.fnum in ("' . implode('","', $fnums) . '") ';
		if (preg_match("/emundus_evaluations/i", $query)) {

			$current_user = $this->app->getSession()->get('emundusUser');

			$eval_query = $this->_db->getQuery(true);
			$eval_query
				->select('id')
				->from($this->_db->quoteName('#__emundus_evaluations'))
				->where($this->_db->quoteName('fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')')
				->andWhere($this->_db->quoteName('user') . ' = ' . $this->app->getIdentity()->id);

			$this->_db->setQuery($eval_query);
			$eval = $this->_db->loadResult();

			if ((!EmundusHelperAccess::asAccessAction(5, 'r', $this->app->getIdentity()->id) && EmundusHelperAccess::asAccessAction(5, 'c', $this->app->getIdentity()->id))) {
				if ((!empty($current_user->fnums) && !empty(array_diff($fnums, array_keys($current_user->fnums)))) || ((@EmundusHelperAccess::isEvaluator($current_user->id) && !@EmundusHelperAccess::isCoordinator($current_user->id)))) {
					if ($eval) {
						$query .= ' AND jos_emundus_evaluations.user = ' . $this->app->getIdentity()->id;
					}
				}
			}
		}

		if ($pas != 0) {
			$query .= ' LIMIT ' . $pas . ' OFFSET ' . $start;
		}

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			$error = Uri::getInstance() . ' :: USER ID : ' . $this->app->getIdentity()->id . ' -> ' . $e->getMessage() . ' :: ' . preg_replace("/[\r\n]/", " ", $query);
			Log::add($error, Log::ERROR, 'com_emundus');
			JFactory::getApplication()->enqueueMessage($error, 'error');

			return false;
		}
	}


	/**
	 * @param $fnums
	 * @param $elements
	 * @param $start
	 * @param $limit
	 * @param $method  (0 : regroup all repeat elements in one row, and make values unique ; 1 : Don't regroup repeat elements, make a line for each repeat element ; 2 : regroup all repeat elements in one row, but write all values even if there are duplicates)
	 *
	 * @return array|false
	 */
	public function getFnumArray2($fnums, $elements, $start = 0, $limit = 0, $method = 0, $user_id = null)
	{
		$data = [];

		if (!empty($fnums) && !empty($elements)) {
			$fnums = !is_array($fnums) ? [$fnums] : $fnums;
			$fnums = array_unique($fnums);
			$method = (int) $method;

			$h_files      = new EmundusHelperFiles;
			$current_lang = substr(JFactory::getLanguage()->getTag(), 0, 2);
			if (empty($current_lang)) {
				$current_lang = 'fr';
			}
			$current_user = empty($user_id) ? Factory::getApplication()->getIdentity()->id : $user_id;

			if (!class_exists('EmundusHelperAccess')) {
				require_once JPATH_ROOT . '/components/com_emundus/helpers/access.php';
			}
			$anonymize_data = EmundusHelperAccess::isDataAnonymized($current_user);
			if ($anonymize_data) {
				$query = 'SELECT jecc.fnum, esc.label, sp.code, esc.id as campaign_id';
			}
			else {
				$query = 'SELECT jecc.fnum, '.
					'CASE WHEN eu.is_anonym != 1 THEN u.email ELSE ' . $this->_db->quote('COM_EMUNDUS_ANONYM_ACCOUNT') . ' END as email, '.
					' esc.label, sp.code, esc.id as campaign_id';
			}

			$already_joined = [
				'jecc' => 'jos_emundus_campaign_candidature',
				'esc'  => 'jos_emundus_setup_campaigns',
				'escm'  => 'jos_emundus_setup_campaigns_more',
				'sp'   => 'jos_emundus_setup_programmes',
				'estu' => 'jos_emundus_setup_teaching_unity',
				'u'    => 'jos_users',
				'eu'   => 'jos_emundus_users',
			];

			$from     = ' FROM #__emundus_campaign_candidature as jecc ';
			$leftJoin = ' LEFT JOIN #__emundus_setup_campaigns as esc ON esc.id = jecc.campaign_id ';
			$leftJoin .= ' LEFT JOIN #__emundus_setup_campaigns_more as escm ON escm.campaign_id = jecc.campaign_id ';
			$leftJoin .= ' LEFT JOIN #__emundus_setup_programmes as sp ON sp.code = esc.training ';
			$leftJoin .= ' LEFT JOIN #__emundus_setup_teaching_unity as estu ON estu.code = esc.training and estu.schoolyear = esc.year ';
			$leftJoin .= ' LEFT JOIN #__users as u ON u.id = jecc.applicant_id ';
			$leftJoin .= ' LEFT JOIN #__emundus_users as eu ON eu.user_id = u.id ';

			$elements_as = [];

			foreach ($elements as $element) {
				$saved_element_as = $element->tab_name . '___' . $element->element_name;
				$is_repeat        = false;

				if (in_array($element->tab_name, $already_joined)) {
					$element_table_alias = array_search($element->tab_name, $already_joined);
				}
				else if(!empty($element->tab_name)) {
					if ($h_files->isTableLinkedToCampaignCandidature($element->tab_name)) {
						$element_table_alias                  = 'table_join_' . sizeof($already_joined);
						$already_joined[$element_table_alias] = $element->tab_name;

						$leftJoin .= ' LEFT JOIN ' . $element->tab_name . ' as ' . $element_table_alias . ' ON ' . $element_table_alias . '.fnum = jecc.fnum ';
					}
					else {
						$joins = $h_files->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', $element->tab_name);

						if (!empty($joins)) {
							$leftJoin            .= $h_files->writeJoins($joins, $already_joined, true);
							$element_table_alias = array_search($element->tab_name, $already_joined);
						}
						else {
							continue; // If the element is not linked to the campaign candidature, we won't be able to get the data
						}
					}
				}

				$groups_params = json_decode($element->group_attribs, true);
				if ($groups_params['repeat_group_button'] == 1) {
					$is_repeat               = true;
					$group_join_informations = $h_files->getJoinInformations(0, $element->group_id);

					if (!in_array($group_join_informations['table_join'], $already_joined)) {
						$child_element_table_alias                  = 'table_join_' . sizeof($already_joined);
						$already_joined[$child_element_table_alias] = $group_join_informations['table_join'];

						$leftJoin .= ' LEFT JOIN ' . $group_join_informations['table_join'] . ' as ' . $child_element_table_alias . ' ON ' . $child_element_table_alias . '.' . $group_join_informations['table_join_key'] . ' = ' . $element_table_alias . '.' . $group_join_informations['table_key'];
					}
					else {
						$child_element_table_alias = array_search($group_join_informations['table_join'], $already_joined);
					}
				}

				switch ($element->element_plugin) {
					case 'cascadingdropdown': // cascading dropdown is really similar to databasejoin. We just need to rewrite parameters entries to match those of databasejoin
						$element_params = json_decode($element->element_attribs, true);

						list($table, $column) = explode('___', $element_params['cascadingdropdown_id']);
						$element_params['join_db_name']    = $table;
						$element_params['join_key_column'] = $column;
						list($table, $column) = explode('___', $element_params['cascadingdropdown_label']);
						$element_params['join_val_column']            = $column;
						$element_params['join_val_column_concat']     = $element_params['cascadingdropdown_label_concat'];
						$element_params['database_join_display_type'] = $element_params['cdd_display_type'];
						$element->element_attribs                     = json_encode($element_params);

					// we don't break here, we want to execute the code of databasejoin
					// ! DON'T ADD A BREAK HERE AND DON'T PUT ANYTHING BETWEEN THIS CASE AND DATABASEJOIN CASE ! //
					case 'databasejoin':
						$element_params  = json_decode($element->element_attribs, true);
						$is_multi        = $element_params['database_join_display_type'] === 'checkbox' || $element_params['database_join_display_type'] === 'multilist';
						$join_column     = !empty($element_params['join_val_column_concat']) ? 'CONCAT(' . $element_params['join_val_column_concat'] . ')' : $element_params['join_val_column'];
						$join_column     = str_replace('{thistable}', $element_params['join_db_name'], $join_column);
						$join_column     = str_replace('{shortlang}', $current_lang, $join_column);
						$join_column     = str_replace('{my->id}', $current_user, $join_column);
						$where_condition = '';

						if (!empty($element_params['database_join_where_sql']) && strpos($element_params['database_join_where_sql'], '{jos_') === false && strpos($element_params['database_join_where_sql'], '{rowid}') === false) {
							$where_condition = preg_replace('/WHERE/', '', $where_condition, 1);
							$where_condition = str_replace('{thistable}', $element_params['join_db_name'], $where_condition);
							$where_condition = str_replace('{my->id}', $current_user, $where_condition);
							foreach ($already_joined as $alias => $table) {
								str_replace($table . '.', $alias . '.', $where_condition);
							}
							if (stripos($where_condition, 'ORDER BY') !== false) {
								$where_condition = substr($where_condition, 0, strpos($where_condition, 'ORDER BY'));
							}

							if (!empty(trim($where_condition))) {
								$where_condition = ' AND (' . $where_condition . ')';
							}
						}

						$databasejoin_sub_query = '';

						if ($is_repeat && $is_multi) { // it is a special case, we are in a repeatable group, and the element itself has repeatable values
							$join_informations = $h_files->getJoinInformations($element->id);

							if (!empty($join_informations)) {

								if (empty($child_element_table_alias)) {
									$group_repeat_table = array_search($join_informations['join_from_table'], $already_joined);
								}
								else {
									$group_repeat_table = $child_element_table_alias;
								}

								$multi_element_repeat_table         = $join_informations['table_join'] . '_rand_' . rand(0, 1000);
								$multi_element_repeat_table_alias_2 = $join_informations['table_join'] . '_rand_' . rand(0, 1000);

								$leftJoin               .= ' LEFT JOIN (
									SELECT GROUP_CONCAT(' . $join_column . ') AS value, ' . $multi_element_repeat_table . '.parent_id
									FROM ' . $element_params['join_db_name'] . '
									LEFT JOIN ' . $join_informations['table_join'] . ' AS ' . $multi_element_repeat_table . ' ON ' . $multi_element_repeat_table . '.' . $element->element_name . ' = ' . $element_params['join_db_name'] . '.' . $element_params['join_key_column'] . '
									WHERE ' . $multi_element_repeat_table . '.parent_id IS NOT NULL
									GROUP BY ' . $multi_element_repeat_table . '.parent_id
								) AS ' . $multi_element_repeat_table_alias_2 . ' ON ' . $multi_element_repeat_table_alias_2 . '.parent_id = ' . $group_repeat_table . '.id';
								$databasejoin_sub_query = '(' . $multi_element_repeat_table_alias_2 . '.value) AS ' . $already_joined[$group_repeat_table] . '___' . $element->element_name;

								$saved_element_as = $already_joined[$group_repeat_table] . '___' . $element->element_name;
							}
						}
						else {
							$databasejoin_sub_query .= '(SELECT ' . $join_column;
							$databasejoin_sub_query .= ' FROM ' . $element_params['join_db_name'];

							// In case of checkbox or multilist, the values are stored in child table
							if ($is_multi) {
								$join_informations = $h_files->getJoinInformations($element->id);
								if (!empty($join_informations)) {
									if (!in_array($join_informations['table_join'], $already_joined)) {
										$child_table_alias                  = 'table_join_' . sizeof($already_joined);
										$already_joined[$child_table_alias] = $join_informations['table_join'];

										$leftJoin .= ' LEFT JOIN ' . $join_informations['table_join'] . ' as ' . $child_table_alias . ' ON ' . $child_table_alias . '.' . $join_informations['table_join_key'] . ' = ' . $element_table_alias . '.id';
									}
									else {
										$child_table_alias = array_search($join_informations['table_join'], $already_joined);
									}

									$databasejoin_sub_query = ' (' . $databasejoin_sub_query;
									$databasejoin_sub_query .= ' WHERE ' . $element_params['join_db_name'] . '.' . $element_params['join_key_column'] . ' = ' . $child_table_alias . '.' . $element->element_name . $where_condition . '))';
									$databasejoin_sub_query .= ' AS ' . $already_joined[$child_table_alias] . '___' . $element->element_name;
									$saved_element_as       = $already_joined[$child_table_alias] . '___' . $element->element_name;
								} else {
									// we should not be here, but just in case
									$databasejoin_sub_query .= ' WHERE ' . $element_params['join_db_name'] . '.' . $element_params['join_key_column'] . ' = ' . $element_table_alias . '.' . $element->element_name . $where_condition . ' LIMIT 1)';
								}
							}
							else {
								if ($is_repeat) {
									$databasejoin_sub_query .= ' WHERE ' . $element_params['join_db_name'] . '.' . $element_params['join_key_column'] . ' = ' . $child_element_table_alias . '.' . $element->element_name . $where_condition . ')';
									$databasejoin_sub_query .= ' AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
									$saved_element_as       = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
								}
								else {
									$databasejoin_sub_query .= ' WHERE ' . $element_params['join_db_name'] . '.' . $element_params['join_key_column'] . ' = ' . $element_table_alias . '.' . $element->element_name . $where_condition . ' LIMIT 1)';
									$databasejoin_sub_query .= ' AS ' . $element->tab_name . '___' . $element->element_name;
								}
							}
						}

						$query .= ', ' . $databasejoin_sub_query;

						break;
					case 'radiobutton':
						$element_params = json_decode($element->element_attribs, true);
						if (!empty($element_params['sub_options'])) {
							if ($is_repeat) {
								$query .= ', (CASE ' . $child_element_table_alias . '.' . $element->element_name . ' ';
							}
							else {
								$query .= ', (CASE ' . $element_table_alias . '.' . $element->element_name . ' ';
							}

							foreach ($element_params['sub_options']['sub_values'] as $sub_key => $sub_value) {
								$sub_label = Text::_($element_params['sub_options']['sub_labels'][$sub_key]);
								$sub_label = $sub_label === '' ? $element_params['sub_options']['sub_labels'][$sub_key] : $sub_label;
								$sub_label = str_replace("'", "\'", $sub_label); // escape sub label single quotes for SQL query
								$sub_value = str_replace("'", "\'", $sub_value);

								$query .= ' WHEN \'' . $sub_value . '\' THEN \'' . $sub_label . '\'';
							}

							if ($is_repeat) {
								$query            .= ' END) AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
								$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							}
							else {
								$query .= ' END) AS ' . $element->tab_name . '___' . $element->element_name;
							}
						}
						else {
							if ($is_repeat) {
								$query            .= ', ' . $child_element_table_alias . '.' . $element->element_name . ' AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
								$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							}
							else {
								$query .= ', ' . $element_table_alias . '.' . $element->element_name . ' AS ' . $element->tab_name . '___' . $element->element_name;
							}
						}

						break;
					case 'checkbox':
						if ($is_repeat) {
							$element_table_alias = array_search($element->table_join, $already_joined);
						}

						// value is saved as string '["value1", "value2"]' in the database
						$query            .= ', (';
						$regexp_sub_query = $element_table_alias . '.' . $element->element_name . ' '; // default value if no sub_options

						$element_params = json_decode($element->element_attribs, true);
						if (!empty($element_params['sub_options']['sub_values'])) {
							foreach ($element_params['sub_options']['sub_values'] as $sub_key => $sub_value) {
								$sub_label = Text::_($element_params['sub_options']['sub_labels'][$sub_key]);
								$sub_label = $sub_label === '' ? $element_params['sub_options']['sub_labels'][$sub_key] : $sub_label;
								$sub_label = str_replace("'", "\'", $sub_label); // escape sub label single quotes for SQL query
								$sub_value = str_replace("'", "\'", $sub_value);


								if ($sub_key === 0) {
									$regexp_sub_query = 'regexp_replace(' . $element_table_alias . '.' . $element->element_name . ', \'"' . $sub_value . '"\', \'' . $sub_label . '\')';
								}
								else {
									$regexp_sub_query = 'regexp_replace(' . $regexp_sub_query . ', \'"' . $sub_value . '"\', \'' . $sub_label . '\')';
								}
							}

							// we also want to remove the brackets
							$regexp_sub_query = 'replace(' . $regexp_sub_query . ', \'[\', \' \')';
							$regexp_sub_query = 'replace(' . $regexp_sub_query . ', \']\', \' \')';
						}

						$query .= $regexp_sub_query . ') AS ' . $element->tab_name . '___' . $element->element_name;
						break;
					case 'dropdown':
						$element_params = json_decode($element->element_attribs, true);

						if ($element_params['multiple'] === '0') {
							if (count($element_params['sub_options']['sub_values']) == 1 && empty($element_params['sub_options']['sub_values'][0])) {
								// If no value in dropdown, it is probably using dropdown_populate with PHP code, so return directly the value
								// TODO: possible to retrieve the label generated by the code of a dropdown_populate field?
								$query .= ', '.$element_table_alias . '.' . $element->element_name . ' AS ' . $element->tab_name . '___' . $element->element_name;
							} else
							{
								if ($is_repeat)
								{
									$query .= ', (CASE ' . $child_element_table_alias . '.' . $element->element_name . ' ';
								}
								else
								{
									$query .= ', (CASE ' . $element_table_alias . '.' . $element->element_name . ' ';
								}

								foreach ($element_params['sub_options']['sub_values'] as $sub_key => $sub_value)
								{
									$sub_label = Text::_($element_params['sub_options']['sub_labels'][$sub_key]);
									$sub_label = $sub_label === '' ? $element_params['sub_options']['sub_labels'][$sub_key] : $sub_label;
									$sub_label = str_replace("'", "\'", $sub_label); // escape sub label single quotes for SQL query
									$sub_value = str_replace("'", "\'", $sub_value);

									$query .= ' WHEN \'' . $sub_value . '\' THEN \'' . $sub_label . '\'';
								}

								if ($is_repeat)
								{
									$query            .= ' END) AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
									$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
								}
								else
								{
									$query .= ' END) AS ' . $element->tab_name . '___' . $element->element_name;
								}
							}
						}
						else {
							// value is saved as string '["value1", "value2"]' in the database
							$query .= ', (';

							if ($is_repeat) {
								$regexp_sub_query = $child_element_table_alias . '.' . $element->element_name . ' '; // default value if no sub_options
							}
							else {
								$regexp_sub_query = $element_table_alias . '.' . $element->element_name . ' '; // default value if no sub_options
							}

							if (!empty($element_params['sub_options']['sub_values'])) {
								foreach ($element_params['sub_options']['sub_values'] as $sub_key => $sub_value) {
									$sub_label = Text::_($element_params['sub_options']['sub_labels'][$sub_key]);
									$sub_label = $sub_label === '' ? $element_params['sub_options']['sub_labels'][$sub_key] : $sub_label;
									$sub_label = str_replace("'", "\'", $sub_label); // escape sub label single quotes for SQL query
									$sub_value = str_replace("'", "\'", $sub_value);

									if ($sub_key === 0) {
										if ($is_repeat) {
											$regexp_sub_query = 'regexp_replace(' . $child_element_table_alias . '.' . $element->element_name . ', \'([^0-9]|^)' . $sub_value . '([^0-9]|$)\', \'' . $sub_label . '\')';
										}
										else {
											$regexp_sub_query = 'regexp_replace(' . $element_table_alias . '.' . $element->element_name . ', \'([^0-9]|^)' . $sub_value . '([^0-9]|$)\', \'' . $sub_label . '\')';
										}
									}
									else {
										$regexp_sub_query = 'regexp_replace(' . $regexp_sub_query . ', \'([^0-9]|^)' . $sub_value . '([^0-9]|$)\', \'' . $sub_label . '\')';
									}
								}

								// we also want to remove the brackets
								$regexp_sub_query = 'replace(' . $regexp_sub_query . ', \'[\', \' \')';
								$regexp_sub_query = 'replace(' . $regexp_sub_query . ', \']\', \' \')';
							}

							if ($is_repeat) {
								$query            .= $regexp_sub_query . ') AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
								$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							}
							else {
								$query .= $regexp_sub_query . ') AS ' . $element->tab_name . '___' . $element->element_name;
							}
						}
						break;
					case 'birthday':
						if ($is_repeat) {
							$query            .= ', DATE_FORMAT(' . $child_element_table_alias . '.' . $element->element_name . ', \'%Y-%m-%d\') AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
						}
						else {
							$query .= ', DATE_FORMAT(' . $element_table_alias . '.' . $element->element_name . ', \'%Y-%m-%d\') AS ' . $element->tab_name . '___' . $element->element_name;
						}
						break;
					case 'date':
						if ($is_repeat) {
							$query            .= ', DATE_FORMAT(' . $child_element_table_alias . '.' . $element->element_name . ', \'%Y-%m-%d %H:%i:%s\') AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
						}
						else {
							$query .= ', DATE_FORMAT(' . $element_table_alias . '.' . $element->element_name . ', \'%Y-%m-%d %H:%i:%s\') AS ' . $element->tab_name . '___' . $element->element_name;
						}
						break;
					case 'yesno':
						if ($is_repeat) {
							$query            .= ', CASE ' . $child_element_table_alias . '.' . $element->element_name . ' WHEN 0 THEN \'' . Text::_('JNO') . '\' WHEN 1 THEN \'' . Text::_('JYES') . '\' ELSE ' . $child_element_table_alias . '.' . $element->element_name . ' END AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
						}
						else {
							$query .= ', CASE ' . $element_table_alias . '.' . $element->element_name . ' WHEN 0 THEN \'' . Text::_('JNO') . '\' WHEN 1 THEN \'' . Text::_('JYES') . '\' ELSE ' . $element_table_alias . '.' . $element->element_name . ' END AS ' . $element->tab_name . '___' . $element->element_name;
						}
						break;
					case 'booking':
						$config            = Factory::getApplication()->getConfig();
						$offset            = $config->get('offset');
						$booking_sub_query = "(SELECT CONCAT(
								    DATE_FORMAT(CONVERT_TZ(start_date, 'UTC', '" . $offset . "'), '%d.%m.%Y %H:%i'), 
								    ' - ', 
								    DATE_FORMAT(CONVERT_TZ(end_date, 'UTC', 'Europe/Paris'), '%d.%m.%Y %H:%i'))
								";
						$booking_sub_query .= ' FROM jos_emundus_setup_availabilities';

						if ($is_repeat)
						{
							$booking_sub_query .= ' WHERE jos_emundus_setup_availabilities.id = ' . $child_element_table_alias . '.' . $element->element_name . ')';
							$booking_sub_query .= ' AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							$saved_element_as  = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
						}
						else
						{
							$booking_sub_query .= ' WHERE jos_emundus_setup_availabilities.id = ' . $element_table_alias . '.' . $element->element_name . ')';
							$booking_sub_query .= ' AS ' . $element->tab_name . '___' . $element->element_name;
						}

						$query .= ', ' . $booking_sub_query;
						break;

					default:
						if ($is_repeat) {
							$query            .= ', ' . $child_element_table_alias . '.' . $element->element_name . ' AS ' . $already_joined[$child_element_table_alias] . '___' . $element->element_name;
							$saved_element_as = $already_joined[$child_element_table_alias] . '___' . $element->element_name;
						}
						else {
							$query .= ', ' . $element_table_alias . '.' . $element->element_name . ' AS ' . $element->tab_name . '___' . $element->element_name;
						}
						break;
				}

				$elements_as[$saved_element_as] = ['id' => $element->id, 'is_repeat' => $is_repeat];
			}

			$where = ' WHERE jecc.fnum IN ("' . implode('","', $fnums) . '") ORDER BY jecc.id';

			if (!empty($limit)) {
				$where .= ' LIMIT ' . $limit . ' OFFSET ' . $start;
			}

			try {
				$this->_db->setQuery($query . $from . $leftJoin . $where);
				$rows = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				error_log($query . $from . $leftJoin . $where);
				Log::add('Error trying to generate data for xlsx export ' . $e->getMessage(), Log::ERROR, 'com_emundus');

				return false;
			}

			if (!empty($rows)) {
				$data_by_fnums = [];

				if ($method === 1) { // one line per repeat
					$data_by_fnums = $rows;
				}
				else {
					foreach ($rows as $row) {
						if (!empty($row)) {
							if (!isset($data_by_fnums[$row['fnum']])) {
								$data_by_fnums[$row['fnum']] = $row;
							}
							else {
								foreach ($row as $key => $value) {
									if (!isset($data_by_fnums[$row['fnum']][$key])) {
										$data_by_fnums[$row['fnum']][$key] = $value;
									}
									else if (!is_array($data_by_fnums[$row['fnum']][$key])) {
										if (($method === 2 && $elements_as[$key]['is_repeat'] === true) || $value !== $data_by_fnums[$row['fnum']][$key]) {
											$data_by_fnums[$row['fnum']][$key] = [$data_by_fnums[$row['fnum']][$key], $value];
										}
									}
									else if (is_array($data_by_fnums[$row['fnum']][$key])) {
										if (($method === 2 && $elements_as[$key]['is_repeat'] === true) || !in_array($value, $data_by_fnums[$row['fnum']][$key])) {
											$data_by_fnums[$row['fnum']][$key][] = $value;
										}
									}
								}
							}
						}
					}
				}

				$data = $data_by_fnums;
				foreach ($data as $d_key => $row) {
					foreach ($row as $r_key => $value) {
						if (is_null($value)) {
							$data[$d_key][$r_key] = '';
						}

						if (is_array($value)) {
							$separator = ComponentHelper::getParams('com_emundus')->get('export_concat_separator', ', ');
							$data[$d_key][$r_key] = '"' . implode($separator, $value) . '"';
                        }
						/*
						else if (!empty($value) && is_string($value)) {
							$data[$d_key][$r_key] = str_replace('-', '\-', $value);
						} */
					}
				}

		        /**
		         * I made that in order to handle repeat lines that are not complete, because of the limit
		         * If we have a limit of 10, and we have 10 rows, but the last row is not complete, we need to retrieve the last row
		         * in order to have all the data
		         */
				if (!empty($limit) && count($rows) == $limit && (count($data) < $limit || $method === 1)) {
					// it means that we have repeated rows, so we need to retrieve last row all entries, because it may be incomplete (chunked by the limit)
					$last_row                = array_pop($rows);
					$last_row_data = $this->getFnumArray2([$last_row['fnum']], $elements, $start, 0, $method);

					if ($method !== 1) {
					$data[$last_row['fnum']] = $last_row_data[$last_row['fnum']];
					} else {
						// in methode 1, data is not an associative array, so we need to do some stuff
						// remove from $data all rows with the same fnum
						foreach($data as $d_key => $row) {
							if ($row['fnum'] === $last_row['fnum']) {
								unset($data[$d_key]);
							}
						}

						// add the last row array to the data
						$data = array_merge($data, $last_row_data);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getEvalsByFnum($fnums)
	{

		try {


			$query = 'select * from #__emundus_evaluations where fnum in ("' . implode('","', $fnums) . '")';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList('fnum');

		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getEvalByFnum($fnum)
	{
		try {

			$query = 'select * from #__emundus_evaluations where fnum in ("' . $fnum . '")';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList('fnum');
		}
		catch (Exception $e) {
			return false;
		}
	}

	/** Gets the evaluation of a user based on fnum and
	 *
	 * @param $fnum
	 * @param $evaluator_id
	 *
	 * @return bool|mixed
	 */
	public function getEvalByFnumAndEvaluator($fnum, $evaluator_id)
	{

		try {


			$query = 'SELECT * FROM #__emundus_evaluations WHERE fnum = ' . $fnum . ' AND user = ' . $evaluator_id;
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();

		}
		catch (Exception $e) {
			return false;
		}
	}


	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getCommentsByFnum($fnums)
	{
		try {

			$query = 'select * from #__emundus_comments where fnum in ("' . implode('","', $fnums) . '")';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getFilesByFnums($fnums, $attachment_ids = null, $return_as_object = false)
	{
		$files = false;

		if (!empty($fnums)) {
			$query = $this->_db->getQuery(true);

			$query->select('fu.*')
				->from($this->_db->quoteName('#__emundus_uploads', 'fu'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->_db->quoteName('esa.id') . ' = ' . $this->_db->quoteName('fu.attachment_id'))
				->where($this->_db->quoteName('fu.fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')');

			if (!empty($attachment_ids)) {
				$query->andWhere($this->_db->quoteName('fu.attachment_id') . ' IN (' . implode(',', $attachment_ids) . ')');
			}

			$query->order('fu.fnum, esa.ordering ASC');

			try {
				$this->_db->setQuery($query);

				if ($return_as_object) {
					$files = $this->_db->loadObjectList();
				} else {
					$files = $this->_db->loadAssocList();
				}
			} catch(Exception $e) {
				echo $e;
				Log::add('Failed to get files by fnum ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $files;
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getGroupsByFnums($fnums)
	{
		$query = 'select cc.fnum,  GROUP_CONCAT( DISTINCT esg.label ) as groupe
                    from #__emundus_campaign_candidature as cc
                    left join #__emundus_setup_campaigns as esc on esc.id = cc.campaign_id
                    left join #__emundus_setup_groups_repeat_course as esgrc on esgrc.course = esc.training
                    left join #__emundus_setup_groups as esg on esg.id = esgrc.parent_id
                    where cc.fnum in ("' . implode('","', $fnums) . '") group by cc.fnum';
		try {

			$this->_db->setQuery($query);

			return $this->_db->loadAssocList('fnum', 'groupe');
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return bool|mixed
	 */
	public function getAssessorsByFnums($fnums, $column = 'uname')
	{
		$query = 'select cc.fnum,  GROUP_CONCAT( DISTINCT u.name ) as uname, GROUP_CONCAT( DISTINCT u.id ) as uids
                  from #__emundus_campaign_candidature as cc
                  left join #__emundus_users_assoc as eua on eua.fnum = cc.fnum
                  left join #__users as u on u.id = eua.user_id
                  where cc.fnum in ("' . implode('","', $fnums) . '") group by cc.fnum';
		try {

			$this->_db->setQuery($query);

			return $this->_db->loadAssocList('fnum', $column);
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	/**
	 * @param $user
	 *
	 * @return array|false
	 * get list of programmes for associated files
	 */
	public function getAssociatedProgrammes($user)
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT sc.training')
			->from('#__emundus_users_assoc AS ua')
			->leftJoin('#__emundus_campaign_candidature AS cc ON cc.fnum = ua.fnum')
			->leftJoin('#__emundus_setup_campaigns AS sc ON sc.id = cc.campaign_id')
			->where('ua.user_id = '.$this->_db->quote($user));
		try
		{
			$this->_db->setQuery($query);
			return $this->_db->loadColumn();
		}
		catch(Exception $e)
		{
			error_log($e->getMessage(), 0);
			return false;
		}
	}

	/**
	 * @param $user
	 * @return array|false
	 * get list of programmes for groups associated files
	 */
	public function getGroupsAssociatedProgrammes($user)
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT sc.training')
			->from('#__emundus_groups AS g')
			->leftJoin('#__emundus_group_assoc AS ga ON ga.group_id = g.group_id AND ga.action_id = 1 AND ga.r = 1')
			->leftJoin('#__emundus_campaign_candidature AS cc ON cc.fnum = ga.fnum')
			->leftJoin('#__emundus_setup_campaigns AS sc ON sc.id = cc.campaign_id')
			->where('g.user_id = ' . $this->_db->quote($user));
		try
		{
			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
		catch(Exception $e)
		{
			error_log($e->getMessage(), 0);
			return false;
		}
	}

	/**
	 * @param $params
	 *
	 * @return array|mixed
	 */
	public function getMenuList($params)
	{
		$h_files = new EmundusHelperFiles;

		return $h_files->getMenuList($params);
	}

	/*
    *   Get evaluation Fabrik formid from fnum
    *   @param fnum     fnum to evaluate
    *   @return int     Fabrik formid
    */
	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getFormidByFnum($fnum)
	{
		try {

			$query = "SELECT form_id
                        FROM `#__fabrik_formgroup`
                        WHERE group_id IN (
                            SELECT esp.fabrik_group_id
                            FROM  `#__emundus_campaign_candidature` AS ecc
                            LEFT JOIN `#__emundus_setup_campaigns` AS esc ON esc.id = ecc.campaign_id
                            LEFT JOIN `#__emundus_setup_programmes` AS esp ON esp.code = esc.training
                            WHERE ecc.fnum LIKE  " . $this->_db->quote($fnum) . ")";
			$this->_db->setQuery($query);
			$res = $this->_db->loadResult();

			return $res;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/*
    *   Get Decision Fabrik formid from fnum
    *   @param fnum     fnum to evaluate
    *   @return int     Fabrik formid
    */
	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getDecisionFormidByFnum($fnum)
	{
		try {

			$query = "SELECT form_id
                        FROM `#__fabrik_formgroup`
                        WHERE group_id IN (
                            SELECT esp.fabrik_decision_group_id
                            FROM  `#__emundus_campaign_candidature` AS ecc
                            LEFT JOIN `#__emundus_setup_campaigns` AS esc ON esc.id = ecc.campaign_id
                            LEFT JOIN `#__emundus_setup_programmes` AS esp ON esp.code = esc.training
                            WHERE ecc.fnum LIKE  " . $this->_db->quote($fnum) . ")";
			$this->_db->setQuery($query);
			$res = $this->_db->loadResult();

			return $res;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/*
    *   Get admission Fabrik formid from fnum
    *   @param fnum     fnum to evaluate
    *   @return int     Fabrik formid
    */
	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getAdmissionFormidByFnum($fnum)
	{
		try {


			$query = $this->_db->getQuery(true);

			$query
				->select($this->_db->qn('esp.fabrik_applicant_admission_group_id'))
				->from($this->_db->qn('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->_db->qn('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->qn('esc.id') . ' = ' . $this->_db->qn('ecc.campaign_id'))
				->leftJoin($this->_db->qn('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->_db->qn('esp.code') . ' = ' . $this->_db->qn('esc.training'))
				->where($this->_db->qn('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));

			$this->_db->setQuery($query);

			$groups = $this->_db->loadColumn();

			$query
				->clear()
				->select($this->_db->qn('form_id'))
				->from($this->_db->qn('#__fabrik_formgroup'))
				->where($this->_db->qn('group_id') . ' IN (' . implode(',', $groups) . ')')
				->order('find_in_set( group_id, " ' . implode(", ", $groups) . '")');

			$this->_db->setQuery($query);
			$res = $this->_db->loadColumn();

			return $res;

		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @param $fnum
	 *
	 * @return bool|mixed
	 */
	public function getFormByFnum($fnum)
	{
		try {

			$query = "SELECT *
                        FROM `#__fabrik_formgroup`
                        WHERE group_id IN (
                            SELECT esp.fabrik_group_id
                            FROM  `#__emundus_campaign_candidature` AS ecc
                            LEFT JOIN `#__emundus_setup_campaigns` AS esc ON esc.id = ecc.campaign_id
                            LEFT JOIN `#__emundus_setup_programmes` AS esp ON esp.code = esc.training
                            WHERE ecc.fnum LIKE  " . $this->_db->quote($fnum) . ")";
			$this->_db->setQuery($query);
			$res = $this->_db->loadResult();

			return $res;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return array|bool
	 */
	public function getAccessorByFnums($fnums)
	{
		$access = array();
		$query  = $this->_db->getQuery(true);

		// Select all users associated directly to the file
		$query->select([$this->_db->quoteName('jeua.fnum'), $this->_db->quoteName('ju.name', 'uname'), $this->_db->quoteName('jesp.class')])
			->from($this->_db->quoteName('#__emundus_users_assoc', 'jeua'))
			->leftJoin($this->_db->quoteName('#__users', 'ju') . ' ON ' . $this->_db->quoteName('ju.id') . ' = ' . $this->_db->quoteName('jeua.user_id'))
			->leftJoin($this->_db->quoteName('#__emundus_users', 'jeu') . ' ON ' . $this->_db->quoteName('ju.id') . ' = ' . $this->_db->quoteName('jeu.user_id'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'jesp') . ' ON ' . $this->_db->quoteName('jeu.profile') . ' = ' . $this->_db->quoteName('jesp.id'))
			->where($this->_db->quoteName('jeua.action_id') . ' = 1 AND ' . $this->_db->quoteName('jeua.r') . ' = 1 AND ' . $this->_db->quoteName('jeua.fnum') . ' IN ("' . implode('","', $fnums) . '")');

		try {
			$this->_db->setQuery($query);
			$res = $this->_db->loadAssocList();

			// Write the code to show the results to the user
			foreach ($res as $r) {
				if (isset($access[$r['fnum']])) {
					$access[$r['fnum']] .= '<div class="tw-flex tw-items-center tw-gap-2"><span class="circle '.$r['class'].'"></span><span class="tw-truncate tw-w-[200px] tw-text-sm">'.$r['uname'].'</span></div>';
				}
				else {
					$access[$r['fnum']] = '<div class="tw-flex tw-items-center tw-gap-2"><span class="circle '.$r['class'].'"></span><span class="tw-truncate tw-w-[200px] tw-text-sm">'.$r['uname'].'</span></div>';
				}
			}

			// Then, select all groups associated directly to the file
			$query->clear()
				->select($this->_db->quoteName(array('jega.fnum', 'jesg.label', 'jesg.class')))
				->from($this->_db->quoteName('#__emundus_group_assoc', 'jega'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_groups', 'jesg') . ' ON ' . $this->_db->quoteName('jesg.id') . ' = ' . $this->_db->quoteName('jega.group_id'))
				->where($this->_db->quoteName('jega.action_id') . ' = 1')
				->andWhere($this->_db->quoteName('jega.r') . ' = 1')
				->andWhere($this->_db->quoteName('jega.fnum') . ' IN (\'' . implode('\',\'', $fnums) . '\')')
				->order($this->_db->quoteName('jesg.id') . ' DESC');

			$this->_db->setQuery($query);
			$res = $this->_db->loadAssocList();

			// Write the code to show the results to the user
			foreach ($res as $r) {
				$assocTaggroup = '<div class="tw-flex tw-items-center tw-gap-2"><span class="circle '.$r['class'].'"></span><span id="'.$r['id'].'" class="tw-truncate tw-w-[200px] tw-text-sm">'.$r['label'].'</span></div>';
				if (isset($access[$r['fnum']])) {
					$access[$r['fnum']] .= '' . $assocTaggroup;
				}
				else {
					$access[$r['fnum']] .= $assocTaggroup;
				}
			}

			// Finally, select all groups associated to the file by its program
			$query->clear()
				->select(array($this->_db->quoteName('jecc.fnum'), $this->_db->quoteName('jesg.id'), 'GROUP_CONCAT(' . $this->_db->quoteName('jesg.label') . ' ORDER BY ' . $this->_db->quoteName('jesg.id') . ' DESC SEPARATOR "|") as label', 'GROUP_CONCAT(' . $this->_db->quoteName('jesg.class') . ' ORDER BY ' . $this->_db->quoteName('jesg.id') . ' DESC SEPARATOR "|") as class'))
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'jecc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'jesc') . ' ON ' . $this->_db->quoteName('jesc.id') . ' = ' . $this->_db->quoteName('jecc.campaign_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'jesp') . ' ON ' . $this->_db->quoteName('jesp.code') . ' = ' . $this->_db->quoteName('jesc.training'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_groups_repeat_course', 'jesgrc') . ' ON ' . $this->_db->quoteName('jesgrc.course') . ' = ' . $this->_db->quoteName('jesp.code'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_groups', 'jesg') . ' ON ' . $this->_db->quoteName('jesg.id') . ' = ' . $this->_db->quoteName('jesgrc.parent_id'))
				->leftJoin($this->_db->quoteName('#__emundus_acl', 'jea') . ' ON ' . $this->_db->quoteName('jea.group_id') . ' = ' . $this->_db->quoteName('jesg.id'))
				->where($this->_db->quoteName('jea.action_id') . ' = 1')
				->andWhere($this->_db->quoteName('jea.r') . ' = 1')
				->andWhere($this->_db->quoteName('jecc.fnum') . ' IN (\'' . implode('\',\'', $fnums) . '\')')
				->group($this->_db->quoteName('jecc.fnum'));

			$this->_db->setQuery($query);
			$res = $this->_db->loadAssocList();

			// Write the code to show the results to the user
			foreach ($res as $r) {
				$group_labels = explode('|', $r['label']);
				$class_labels = explode('|', $r['class']);
				foreach ($group_labels as $key => $g_label) {
					$assocTagcampaign = '<div class="tw-flex tw-items-center tw-gap-2"><span class="circle '.$class_labels[$key].'" id="'.$r['id'].'"></span><span id="'.$r['id'].'" class="tw-truncate tw-w-[200px] tw-text-sm">'.$g_label.'</span></div>';
					$access[$r['fnum']] .= $assocTagcampaign;
				}
			}

			return $access;
		}
		catch (Exception $e) {
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Gets the associated groups, users, or both, for an array of fnums. Used for XLS exports.
	 *
	 * @param         $fnums
	 * @param   bool  $groups  Should we get associated groups ?
	 * @param   bool  $users
	 *
	 * @return array|bool
	 */
	public function getAssocByFnums($fnums, $groups = true, $users = true)
	{

		$access = [];


		if ($groups) {

			$query = "SELECT jecc.fnum, group_concat(jesg.label) AS label
					  FROM #__emundus_campaign_candidature as jecc
	                  LEFT JOIN #__emundus_setup_campaigns as jesc on jesc.id = jecc.campaign_id
	                  LEFT JOIN #__emundus_setup_programmes as jesp on jesp.code = jesc.training
	                  LEFT JOIN #__emundus_setup_groups_repeat_course as jesgrc on jesgrc.course = jesp.code
	                  LEFT JOIN #__emundus_setup_groups as jesg on jesg.id = jesgrc.parent_id
	                  LEFT JOIN #__emundus_acl as jea on jea.group_id = jesg.id
	                  WHERE jea.action_id = 1 and jea.r = 1 and jecc.fnum in ('" . implode("','", $fnums) . "')
	                  GROUP BY jecc.fnum";
			try {
				$this->_db->setQuery($query);
				$access = $this->_db->loadAssocList('fnum', 'label');
			}
			catch (Exception $e) {
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus');

				return false;
			}

			$query = "SELECT jega.fnum, group_concat(jesg.label) AS label
					  FROM #__emundus_group_assoc as jega
                      LEFT JOIN #__emundus_setup_groups as jesg on jesg.id = jega.group_id
                      WHERE jega.action_id = 1 and jega.r = 1  and jega.fnum in ('" . implode("','", $fnums) . "')
                      GROUP BY jega.fnum ";

			try {
				$this->_db->setQuery($query);
				$res = $this->_db->loadAssocList('fnum', 'label');
			}
			catch (Exception $e) {
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus');

				return false;
			}

			foreach ($res as $k => $r) {
				if (isset($access[$k])) {
					$access[$k] .= ',' . $r;
				}
				else {
					$access[$k] = $r;
				}
			}
		}


		if ($users) {

			$query = $this->_db->getQuery(true);
			$query->select([$this->_db->quoteName('jeua.fnum'), 'group_concat(' . $this->_db->quoteName('ju.name') . ') AS name'])
				->from($this->_db->quoteName('#__emundus_users_assoc', 'jeua'))
				->leftJoin($this->_db->quoteName('#__users', 'ju') . ' ON ' . $this->_db->quoteName('ju.id') . ' = ' . $this->_db->quoteName('jeua.user_id'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'jeu') . ' ON ' . $this->_db->quoteName('ju.id') . ' = ' . $this->_db->quoteName('jeu.user_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'jesp') . ' ON ' . $this->_db->quoteName('jeu.profile') . ' = ' . $this->_db->quoteName('jesp.id'))
				->where($this->_db->quoteName('jeua.action_id') . ' = 1 AND ' . $this->_db->quoteName('jeua.r') . ' = 1 AND ' . $this->_db->quoteName('jeua.fnum') . ' IN ("' . implode('","', $fnums) . '")')
				->group($this->_db->quoteName('jeua.fnum'));

			try {
				$this->_db->setQuery($query);
				$res = $this->_db->loadAssocList('fnum', 'name');
			}
			catch (Exception $e) {
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus');

				return false;
			}


			foreach ($res as $k => $r) {
				if (isset($access[$k])) {
					$access[$k] .= ',' . $r;
				}
				else {
					$access[$k] = $r;
				}
			}
		}

		return $access;
	}


	/**
	 * @param $fnums
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getTagsByFnum($fnums)
	{
		$tags  = array();
		$query = $this->_db->getQuery(true);

		$query->select('eta.*, esat.*, u.name')
			->from($this->_db->quoteName('#__emundus_tag_assoc', 'eta'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_action_tag', 'esat') . ' ON ' . $this->_db->quoteName('esat.id') . ' = ' . $this->_db->quoteName('eta.id_tag'))
			->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('eta.user_id'))
			->where($this->_db->quoteName('eta.fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')')
			->order($this->_db->quoteName('esat.label'));

		try {
			$this->_db->setQuery($query);
			$tags = $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}

		return $tags;
	}

	public function getTagsByIdFnumUser($tid, $fnum, $user_id)
	{
		$query = 'SELECT * FROM #__emundus_tag_assoc 
                    WHERE id_tag = ' . $tid . ' AND fnum LIKE "' . $fnum . '" AND user_id = ' . $user_id;
		try {
			$this->_db->setQuery($query);
			$res = $this->_db->loadAssocList();
			if (count($res) > 0)
				return true;
			else
				return false;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param $fnums
	 *
	 * @return array
	 */
	public function getProgByFnums(array $fnums): array
	{
		$programs = [];

		if (!empty($fnums)) {
			try {
				$query = $this->_db->createQuery();

				$query->select('jesp.code, jesp.label')
					->from($this->_db->quoteName('#__emundus_campaign_candidature', 'jecc'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'jesc') . ' ON ' . $this->_db->quoteName('jesc.id') . ' = ' . $this->_db->quoteName('jecc.campaign_id'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'jesp') . ' ON ' . $this->_db->quoteName('jesp.code') . ' = ' . $this->_db->quoteName('jesc.training'))
					->where($this->_db->quoteName('jecc.fnum') . ' IN (' . implode(',', $this->_db->quote($fnums)) . ')');

				$this->_db->setQuery($query);
				$programs = $this->_db->loadAssocList('code', 'label');
			}
			catch (Exception $e) {
				Log::add('Failed to getProgByFnums' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $programs;
	}

	/**
	 * @param $code
	 *
	 * @return Exception|mixed|Exception
	 */
	public function getDocsByProg($code)
	{

		try {
			$query = 'select jesl.title, jesl.template_type, jesl.id as file_id 
                        from #__emundus_setup_letters as jesl
                        left join #__emundus_setup_letters_repeat_training as jeslrt on jeslrt.parent_id = jesl.id
                        where jeslrt.training = ' . $this->_db->quote($code) . ' ORDER BY jesl.title';
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			return $e;
		}
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getAttachmentInfos($id)
	{

		$query = "select * from jos_emundus_setup_attachments where id = {$id}";
		$this->_db->setQuery($query);

		return $this->_db->loadAssoc();
	}

	/**
	 * @param $fnum
	 * @param $name
	 * @param $uid
	 * @param $cid
	 * @param $attachment_id
	 * @param $desc
	 *
	 * @return int
	 */
	public function addAttachment($fnum, $name, $uid, $cid, $attachment_id, $desc, $canSee = 0)
	{
		$now   = EmundusHelperDate::getNow();
		$query = $this->_db->getQuery(true);
		$query->insert($this->_db->quoteName('#__emundus_uploads'))
			->columns($this->_db->quoteName(array('timedate', 'user_id', 'fnum', 'attachment_id', 'filename', 'description', 'can_be_deleted', 'can_be_viewed', 'campaign_id')))
			->values($this->_db->quote($now) . ', ' . $this->_db->quote($uid) . ', ' . $this->_db->quote($fnum) . ', ' . $this->_db->quote($attachment_id) . ', ' . $this->_db->quote($name) . ', ' . $this->_db->quote($desc) . ', 0, ' . $this->_db->quote($canSee) . ', ' . $this->_db->quote($cid));
		$this->_db->setQuery($query);
		$this->_db->execute();

		return $this->_db->insertid();
	}

	/**
	 * @param $code
	 * @param $fnums
	 *
	 * @return mixed
	 */
	public function checkFnumsDoc($code, $fnums)
	{

		$query = "select distinct (jecc.fnum) from jos_emundus_campaign_candidature as jecc
                    left join jos_emundus_setup_letters_repeat_status as jeslrs on jeslrs.status = jecc.status
                    left join jos_emundus_setup_letters as jesl on jesl.id = jeslrs.parent_id
                    left join jos_emundus_setup_letters_repeat_training as jeslrt on jeslrt.training like {$this->_db->quote($code)}
                    WHERE jecc.fnum in ('" . implode("','", $fnums) . "') group by jecc.fnum";
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
	}

	/**
	 * @param $ids
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getAttachmentsById($ids)
	{
		$attachments = [];

		if (!empty($ids)) {

			$query = $this->_db->getQuery(true);
			$query->select('jeu.fnum, jeu.filename, jeu.id, jecc.applicant_id, jeu.attachment_id')
				->from('#__emundus_uploads AS jeu')
				->leftJoin('#__emundus_campaign_candidature AS jecc ON jecc.fnum = jeu.fnum')
				->where('jeu.id IN (' . implode(',', $ids) . ')');
			try {
				$this->_db->setQuery($query);
				$attachments = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add('Failed to get attachment by ids ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $attachments;
	}

	public function getSetupAttachmentsById($ids)
	{
		$setup_attachments = [];

		if (!empty($ids)) {
			$query = $this->_db->getQuery(true);
			$query->select('*')
				->from($this->_db->quoteName('#__emundus_setup_attachments'))
				->where($this->_db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

			try {
				$this->_db->setQuery($query);
				$setup_attachments = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add('Failed to get setup attachment by ids ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $setup_attachments;
	}

	/**
	 * @param $idFabrik
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getValueFabrikByIds($idFabrik)
	{

		if (empty($idFabrik)) {
			return [];
		}


		$query = $this->_db->getQuery(true);
		$query->select('jfe.id, jfe.name, jfe.plugin, jfe.params, jfg.params as group_params, jfg.id as group_id, jfl.db_table_name, jfj.table_join')
			->from($this->_db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($this->_db->quoteName('#__fabrik_formgroup', 'jff') . ' ON ' . $this->_db->quoteName('jff.group_id') . ' = ' . $this->_db->quoteName('jfe.group_id'))
			->leftJoin($this->_db->quoteName('#__fabrik_groups', 'jfg') . ' ON ' . $this->_db->quoteName('jfg.id') . ' = ' . $this->_db->quoteName('jff.group_id'))
			->leftJoin($this->_db->quoteName('#__fabrik_forms', 'jff2') . ' ON ' . $this->_db->quoteName('jff2.id') . ' = ' . $this->_db->quoteName('jff.form_id'))
			->leftJoin($this->_db->quoteName('#__fabrik_lists', 'jfl') . ' ON ' . $this->_db->quoteName('jfl.form_id') . ' = ' . $this->_db->quoteName('jff2.id'))
			->leftJoin($this->_db->quoteName('#__fabrik_joins', 'jfj') . ' ON ' . $this->_db->quoteName('jfl.id') . ' = ' . $this->_db->quoteName('jfj.list_id') . ' AND ' . $this->_db->quoteName('jfg.id') . ' = ' . $this->_db->quoteName('jfj.group_id'))
			->where($this->_db->quoteName('jfe.id') . ' IN (' . implode(',', $idFabrik) . ')');
		try {
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Find all variables like ${var} or [var] in string.
	 *
	 * @param   string  $str
	 * @param   int     $type  type of bracket default CURLY else SQUARE
	 *
	 * @return string[]
	 */
	public function getVariables($str, $type = 'CURLY')
	{
		$variables = [];

		if (!empty($str)) {
			if ($type == 'CURLY') {
				preg_match_all('/\$\{(.*?)}/i', $str, $matches);
			}
			elseif ($type == 'SQUARE') {
				preg_match_all('/\[(.*?)]/i', $str, $matches);
			}
			else {
				preg_match_all('/\{(.*?)}/i', $str, $matches);
			}

			$variables = $matches[1];
		}

		return $variables;
	}


	/**
	 * Return a date format from php to MySQL.
	 *
	 * @param   string  $date_format
	 *
	 * @return string
	 */
	public function dateFormatToMysql($date_format)
	{
		$date_format = str_replace('D', '%D', $date_format);
		$date_format = str_replace('d', '%d', $date_format);
		$date_format = str_replace('M', '%M', $date_format);
		$date_format = str_replace('m', '%m', $date_format);
		$date_format = str_replace('Y', '%Y', $date_format);
		$date_format = str_replace('y', '%y', $date_format);
		$date_format = str_replace('H', '%H', $date_format);
		$date_format = str_replace('h', '%h', $date_format);
		$date_format = str_replace('I', '%I', $date_format);
		$date_format = str_replace('i', '%i', $date_format);
		$date_format = str_replace('S', '%S', $date_format);

		return str_replace('s', '%s', $date_format);
	}


	/**
	 * @param         $elt
	 * @param   null  $fnums
	 * @param         $params
	 * @param         $groupRepeat
	 * @param   int   $parent_row_id
	 * @param   bool  $rawValue if true, return the raw value from the database, else format it according to the element type
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getFabrikValueRepeat($elt, $fnums, $params, $groupRepeat, $parent_row_id = 0, bool $rawValue = false)
	{
		if (!class_exists('EmundusHelperFabrik')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
		}
		$helper = new EmundusHelperFabrik();

		$format = ValueFormatEnum::FORMATTED;
		if ($rawValue) {
			$format = ValueFormatEnum::RAW;
		}
		return $helper->getFabrikValueRepeat($elt, $fnums, $params, $groupRepeat, $parent_row_id, $format);
	}

	/**
	 * @param $fnums
	 * @param $tableName
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getFabrikValue($fnums, $tableName, $name, $dateFormat = null, $row_id = 0, bool $rawValue = false): array
	{
		if (!class_exists('EmundusHelperFabrik')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
		}
		$helper = new EmundusHelperFabrik();

		$format = ValueFormatEnum::FORMATTED;
		if ($rawValue) {
			$format = ValueFormatEnum::RAW;
		}
		return $helper->getFabrikValue($fnums, $tableName, $name, $dateFormat, $row_id, $format);
	}

	/**
	 * @deprecated use getAllStatus instead
	 * @return array|mixed
	 */
	public function getStatus($user_id = null) {
		if (empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		return $this->getAllStatus($user_id, 'step');
	}

	/**
	 * @param        $fnum
	 * @param   int  $user_id
	 *
	 * @return bool
	 */
	public function deleteFile($fnum, int $user_id = 0): bool
	{
		$deleted = false;

		if (!empty($fnum))
		{
			if (empty($user_id))
			{
				$user_id = Factory::getApplication()->getIdentity()->id;
			}

			$this->app->triggerEvent('onBeforeDeleteFile', ['fnum' => $fnum]);
			$this->app->triggerEvent('onCallEventHandler', ['onBeforeDeleteFile', ['fnum' => $fnum]]);

			$query = $this->_db->createQuery();

			$query->select($this->_db->quoteName('filename'))
				->from($this->_db->quoteName('#__emundus_uploads'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

			try {
				$this->_db->setQuery($query);
				$files = $this->_db->loadColumn();
			}
			catch (Exception $e) {
				// Do not hard fail, delete file data anyways.
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}


			// Remove all files linked to the fnum.
			$fileUserId = EmundusHelperFiles::getApplicantIdFromFnum($fnum);
			if (!empty($fileUserId))
			{
				$dir     = EMUNDUS_PATH_ABS . $fileUserId . DS;
				if ($dh = opendir($dir)) {

					while (false !== ($obj = readdir($dh))) {
						if (in_array($obj, $files)) {
							if (!unlink($dir . $obj)) {
								Log::add(Uri::getInstance() . ' :: Could not delete file -> ' . $obj . ' for fnum -> ' . $fnum, Log::ERROR, 'com_emundus');
							}
						}
					}

					closedir($dh);
				}
			}

			// remove hikashop orders linked to this file
			$query->clear()
				->select($this->_db->quoteName('order_id'))
				->from($this->_db->quoteName('#__emundus_hikashop'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

			$this->_db->setQuery($query);
			$order_ids = $this->_db->loadColumn();
			if (!empty($order_ids)) {
				$query->clear()
					->delete($this->_db->quoteName('#__hikashop_order'))
					->where($this->_db->quoteName('order_id') . ' IN (' . implode(',', $this->_db->quote($order_ids)) . ')');

				try {
					$this->_db->setQuery($query);
					$this->_db->execute();
				}
				catch (Exception $e) {
					Log::add(Uri::getInstance() . ' :: USER ID : ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}
			}

			$query->clear()
				->delete($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

			try {
				$this->_db->setQuery($query);
				$deleted = $this->_db->execute();

				$this->app->triggerEvent('onAfterDeleteFile', ['fnum' => $fnum]);

				$onAfterDeleteFile = new GenericEvent('onCallEventHandler', [
					'onAfterDeleteFile',
					[
						'fnum' => $fnum,
						'context' => new EventContextEntity(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id), [$fnum], [$fileUserId], [])
					]
				]);
				$this->app->getDispatcher()->dispatch('onCallEventHandler', $onAfterDeleteFile);
			}
			catch (Exception $e) {
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}

		}

		return $deleted;
	}


	/*
     * CCIRS functions
     * function to get all sessions linked to a program
     *
     */
	public function programSessions($program)
	{
		try {


			$query = $this->_db->getQuery(true);
			$query->select('DISTINCT(t.session_code) AS sc, t.*')
				->from($this->_db->quoteName('#__emundus_setup_programmes', 'p'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $this->_db->quoteName('c.training') . ' = ' . $this->_db->quoteName('p.code'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_teaching_unity', 't') . ' ON ' . $this->_db->quoteName('t.session_code') . ' = ' . $this->_db->quoteName('c.session_code'))
				->where($this->_db->quoteName('p.id') . ' = ' . $program .
					' AND ' . $this->_db->quoteName('t.published') . ' = ' . 1 .
					' AND ' . $this->_db->quoteName('t.date_end') . ' >= NOW()')
				->order('date_start ASC');
			$this->_db->setQuery($query);

			return $this->_db->loadAssocList();
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function getAppliedSessions($program)
	{
		try {
			$current_user = $this->app->getIdentity();

			$query = $this->_db->getQuery(true);
			$query->select('esc.session_code')
				->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
				->where($this->_db->quoteName('esc.training') . ' LIKE ' . $this->_db->quote($program) . 'and' . $this->_db->quoteName('ecc.applicant_id') . ' = ' . $current_user->id);

			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Gets the user's birthdate.
	 *
	 * @param   null    $fnum    The file number to get the birth date from.
	 * @param   string  $format  See php.net/date
	 * @param   bool    $age     If true then we also return the current age.
	 *
	 * @return null
	 */
	public function getBirthdate($fnum = null, $format = 'd-m-Y', $age = false)
	{


		$query     = $this->_db->getQuery(true);

		$query->select($this->_db->quoteName('birth_date'))->from($this->_db->quoteName('#__emundus_personal_detail'))->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);

		try {
			$datetime = new DateTime($this->_db->loadResult());
			if (!$age) {
				$birthdate = $datetime->format($format);
			}
			else {

				$birthdate       = new stdClass();
				$birthdate->date = $datetime->format($format);

				$now            = new DateTime();
				$interval       = $now->diff($datetime);
				$birthdate->age = $interval->y;
			}
		}
		catch (Exception $e) {
			return null;
		}

		return $birthdate;
	}

	public function getDocumentCategory()
	{

		$query     = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('esa.*'))
			->from($this->_db->quoteName('#__emundus_setup_attachments', 'esa'))
			->order($this->_db->quoteName('esa.category') . 'ASC');

		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	public function getParamsCategory($idCategory)
	{

		$query     = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('fe.params'))
			->from($this->_db->quoteName('#__fabrik_elements', 'fe'))
			->where($this->_db->quoteName('fe.group_id') . ' = 47');

		$this->_db->setQuery($query);
		$elements = $this->_db->loadObjectList();

		foreach ($elements as $element) {
			$params = json_decode($element->params);
		}

		return $params->sub_options->sub_labels[$idCategory];
	}

	/** Gets the category names for the different attachment types.
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	public function getAttachmentCategories()
	{


		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('fe.params'))
			->from($this->_db->quoteName('#__fabrik_elements', 'fe'))
			->where($this->_db->quoteName('fe.published') . ' = 1 AND ' . $this->_db->quoteName('fe.group_id') . ' = 47 AND ' . $this->_db->quoteName('fe.name') . ' LIKE ' . $this->_db->quote('category'));
		$this->_db->setQuery($query);
		$element = $this->_db->loadColumn();

		$return = [];

		if (isset($element[0])) {
			$params = json_decode($element[0]);
			if (!empty($params->sub_options->sub_values)) {
				foreach ($params->sub_options->sub_values as $key => $value) {
					$return[$value] = $params->sub_options->sub_labels[$key];
				}
			}
		}

		return $return;
	}

	public function selectCity($insee)
	{

		$query     = $this->_db->getQuery(true);

		$conditions = $this->_db->quoteName('insee_code') . ' LIKE ' . $this->_db->quote($insee);

		$query->select($this->_db->quoteName('name'))
			->from($this->_db->quoteName('#__emundus_french_cities'))
			->where($conditions);

		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	public function selectNameCity($name)
	{

		$query     = $this->_db->getQuery(true);

		$conditions = $this->_db->quoteName('name') . ' LIKE ' . $this->_db->quote($name);

		$query->select($this->_db->quoteName('insee_code'))
			->from($this->_db->quoteName('#__emundus_french_cities'))
			->where($conditions);

		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	public function selectMultiplePayment($fnum)
	{

		$query     = $this->_db->getQuery(true);

		$conditions = $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum);

		$query->select('multiple_payment, method_payment, sampling_mode')
			->from($this->_db->quoteName('#__emundus_declaration'))
			->where($conditions);

		$this->_db->setQuery($query);

		return $this->_db->loadObject();
	}


	/**
	 * @param $group_ids
	 *
	 * @return array|bool
	 *
	 * @since version
	 */
	public function getAttachmentsAssignedToEmundusGroups($group_ids)
	{

		if (!is_array($group_ids)) {
			$group_ids = [$group_ids];
		}

		$query = $this->_db->getQuery(true);

		$result = [];
		foreach ($group_ids as $group_id) {
			$query->clear()
				->select($this->_db->quoteName('attachment_id_link'))
				->from($this->_db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
				->where($this->_db->quoteName('parent_id') . ' = ' . $group_id);

			try {
				$this->_db->setQuery($query);
				$attachments = $this->_db->loadColumn();

				// In the case of a group having no assigned Fabrik groups, it can get them all.
				if (empty($attachments)) {
					return true;
				}

				$result = array_merge($result, $attachments);
			}
			catch (Exception $e) {
				return false;
			}
		}

		if (empty($result)) {
			return true;
		}
		else {
			return array_keys(array_flip($result));
		}
	}

	/**
	 * @param $fnums
	 * @return array
	 */
	public function getFormProgress($fnums): array
	{
		$fnums_progress = [];

		if (!empty($fnums))
		{
			$query        = $this->_db->createQuery();
			$fnums_string = implode(',', $this->_db->quote($fnums));

			$query->select('fnum,form_progress')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' IN (' . $fnums_string . ')');

			try
			{
				$this->_db->setQuery($query);
				$fnums_progress = $this->_db->loadAssocList();
			}
			catch (Exception $e)
			{
				JLog::add('component/com_emundus/models/files | Error when try to get forms progress with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), JLog::ERROR, 'com_emundus');
			}
		}

		return $fnums_progress;
	}

	/**
	 * @param $fnums
	 * @return array
	 */
	public function getAttachmentProgress($fnums): array
	{
		$attachment_progress = [];

		if (!empty($fnums)) {
			$query = $this->_db->createQuery();
			$fnums_string = implode(',', $this->_db->quote($fnums));

			$query->select('fnum,attachment_progress')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' IN (' . $fnums_string . ')');

			try {
				$this->_db->setQuery($query);
				$attachment_progress = $this->_db->loadAssocList();
			} catch (Exception $e) {
				JLog::add('component/com_emundus/models/files | Error when try to get attachment progress with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), JLog::ERROR, 'com_emundus');
			}
		}

		return $attachment_progress;
	}

	/**
	 * @param int current_user_id, if given, user messages won't be counted
	 */
	public function getUnreadMessages($current_user_id = null) {
		$unread_messages = [];

		$query = $this->_db->getQuery(true);

		try {
			// Get count of messages since last reply from an other user that applicant
			$query->select('ecc.fnum, COUNT(m.message_id) as nb')
				->from($this->_db->quoteName('#__emundus_campaign_candidature','ecc'))
				->leftJoin($this->_db->quoteName('#__emundus_chatroom','ec').' ON '.$this->_db->quoteName('ec.fnum').' = '.$this->_db->quoteName('ecc.fnum'))
				->leftJoin($this->_db->quoteName('#__messages','m').' ON '.$this->_db->quoteName('m.page').' = '.$this->_db->quoteName('ec.id'))
				->where($this->_db->quoteName('ec.status') . ' <> 0')
				->andWhere($this->_db->quoteName('m.user_id_from').' = '.$this->_db->quoteName('ecc.applicant_id'))
				->andWhere($this->_db->quoteName('m.date_time') . ' > COALESCE((SELECT MAX(date_time) FROM jos_messages WHERE page = ec.id AND user_id_from <> ecc.applicant_id),"1970-01-01 00:00:00")')
				->andWhere($this->_db->quoteName('m.date_time') . ' <= NOW()')
				->group('ecc.fnum');
			$this->_db->setQuery($query);
			$unread_messages = $this->_db->loadAssocList();
		} catch (Exception $e) {
			$user = $this->app->getIdentity();
			JLog::add('component/com_emundus_messages/models/messages | Error when try to get messages associated to user : '. $user->id . ' with query : ' . preg_replace("/[\r\n]/"," ",$query->__toString().' -> '.$e->getMessage()), JLog::ERROR, 'com_emundus');
		}

		return $unread_messages;
	}


	public function getTagsAssocStatus($status)
	{

		$query     = $this->_db->getQuery(true);

		$conditions = $this->_db->quoteName('ss.step') . ' = ' . $this->_db->quote($status);

		$query->select('ssrt.tags')
			->from($this->_db->quoteName('#__emundus_setup_status_repeat_tags', 'ssrt'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'ss') . ' ON ' . $this->_db->quoteName('ss.id') . ' = ' . $this->_db->quoteName('ssrt.parent_id'))
			->where($conditions);

		$this->_db->setQuery($query);

		try {
			return $this->_db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/files | Error when get tags by status ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}
	}

	public function checkIfSomeoneElseIsEditing($fnum)
	{
		$result       = false;
		$user         = $this->app->getIdentity();
		$config       = ComponentHelper::getParams('com_emundus');
		$editing_time = $config->get('alert_editing_time', 2);

		$actions   = array(1, 4, 5, 10, 11, 12, 13, 14);

		$query     = $this->_db->getQuery(true);

		$query->select('DISTINCT ju.id, ju.name')
			->from('#__users as ju')
			->leftJoin('#__emundus_logs as jel ON ju.id = jel.user_id_from')
			->where($this->_db->quoteName('jel.fnum_to') . ' = ' . $this->_db->quote($fnum))
			->andWhere('action_id IN (' . implode(',', $actions) . ')')
			->andWhere('jel.timestamp > ' . $this->_db->quote(date('Y-m-d H:i:s', strtotime('-' . $editing_time . ' minutes'))))
			->andWhere('jel.user_id_from != ' . $user->id);

		$this->_db->setQuery($query);

		try {
			$result = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/files | Error when check if someone else is editing ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $result;
	}

	public function getStatusByStep($step)
	{

		$query = $this->_db->getQuery(true);

		try {
			$query->clear()
				->select('id')
				->from($this->_db->quoteName('#__emundus_setup_status', 'jess'))
				->where($this->_db->quoteName('jess.step') . ' = ' . $step);

			$this->_db->setQuery($query);
			$status_id = $this->_db->loadResult();

			return $this->getStatusByID($status_id);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/files | Error when get status by step ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getAllLogActions()
	{
		$logs = [];


		$query     = $this->_db->getQuery(true);
		$query->clear()->select('*')->from($this->_db->quoteName('#__emundus_setup_actions', 'jesa'))->order('jesa.id ASC');

		try {
			$this->_db->setQuery($query);
			$logs = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/files | Error when get all logs' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $logs;
	}

	/**
	 * Copy given fnums and all data with it to another user
	 *
	 * @param $fnums
	 * @param $user_to
	 *
	 * @return bool
	 */
	public function bindFilesToUser($fnums, $user_to)
	{
		$bound_fnums = [false];

		if (!empty($fnums) && !empty($user_to)) {

			$query     = $this->_db->getQuery(true);

			$query->select('id')
				->from('#__emundus_users')
				->where('user_id = ' . $user_to);

			try {
				$this->_db->setQuery($query);
				$exists = $this->_db->loadResult();
			}
			catch (Exception $e) {
				$exists = false;
				Log::add('Failed to check if user exists before binding fnum to him ' . $user_to . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if ($exists) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				$m_application = new EmundusModelApplication();

				foreach ($fnums as $i => $fnum) {
					$bound_fnums[$i] = false;
					$campaign_id     = 0;
					$query->clear()
						->select('campaign_id')
						->from('#__emundus_campaign_candidature')
						->where('fnum LIKE ' . $this->_db->q($fnum));

					try {
						$this->_db->setQuery($query);
						$campaign_id = $this->_db->loadResult();
					}
					catch (Exception $e) {
						Log::add('Failed to retrieve campaign from fnum' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					if (!empty($campaign_id)) {
						$fnum_to = $this->createFile($campaign_id, $user_to, time() + $i);

						if (!empty($fnum_to)) {
							$query->clear()
								->select('*')
								->from('#__emundus_campaign_candidature')
								->where('fnum LIKE ' . $this->_db->quote($fnum));
							$this->_db->setQuery($query);
							$result = $this->_db->loadObject();

							if (!empty($result)) {
								$query->clear()
									->update('#__emundus_campaign_candidature')
									->set('user_id = ' . $result->user_id) // keep track of original user
									->set('submitted = ' . $this->_db->quote($result->submitted))
									->set('date_submitted = ' . $this->_db->quote($result->date_submitted))
									->set('status = ' . $this->_db->quote($result->status))
									->set('copied = 1')
									->set('form_progress = ' . $this->_db->quote($result->form_progress))
									->set('attachment_progress = ' . $this->_db->quote($result->attachment_progress))
									->where('fnum LIKE ' . $this->_db->quote($fnum_to));

								$this->_db->setQuery($query);
								$updated = $this->_db->execute();

								if ($updated) {
									$copied = $m_application->copyApplication($fnum, $fnum_to, [], 1, $campaign_id, 1, 1, 0);

									if (!$copied) {
										Log::add("Failed to copy fnum $fnum to user $user_to account on fnum $fnum_to", Log::WARNING, 'com_emundus.logs');
									}
									else {
										$bound_fnums[$i] = true;
										Log::add("Succeed to copy fnum $fnum to user $user_to account on fnum $fnum_to", Log::INFO, 'com_emundus.logs');
									}
								}
							}
						}
					}
				}
			}
			else {
				Log::add('User ' . $user_to . ' seems to not exists', Log::WARNING, 'com_emundus.logs');
			}
		}

		return !in_array(false, $bound_fnums, true);
	}

	/**
	 * Create file for applicant
	 *
	 * @param $campaign_id
	 * @param $user_id If not given, default to Current User
	 * @param $time
	 *
	 * @return string
	 */
	public function createFile($campaign_id, $user_id = 0, $time = null)
	{
		$fnum = '';

		if (!empty($campaign_id)) {

			if (empty($user_id)) {
				$current_user = $this->app->getIdentity();
				if ($current_user->guest == 1) {
					Log::add('Error, trying to create file for guest user. Action unauthorized', Log::WARNING, 'com_emundus.logs');

					return '';
				}

				$user_id = $current_user->id;
			}

			if ($time == null) {
				$time = time();
			}

			require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
			$fnum    = EmundusHelperFiles::createFnum($campaign_id, $user_id);

			if (!empty($fnum)) {
				$timezone = new DateTimeZone($this->app->get('offset'));
				$now      = Factory::getDate()->setTimezone($timezone);

				$query     = $this->_db->getQuery(true);

				$insert = [
					'date_time'   => $now->toSql(),
					'applicant_id' => $user_id,
					'user_id'     => $user_id,
					'campaign_id' => $campaign_id,
					'fnum'        => $fnum
				];
				$insert = (object) $insert;

				try {
					$inserted = $this->_db->insertObject('#__emundus_campaign_candidature', $insert);
				}
				catch (Exception $e) {
					$fnum     = '';
					$inserted = false;
					Log::add("Failed to create file $fnum - $user_id" . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}

				if (!$inserted) {
					$fnum = '';
				}
			}
		}

		return $fnum;
	}

	private function sendEmailAfterUpdateState($state, $students, $user_id = null)
	{
		$msg = '';

		$app = Factory::getApplication();
		$email_from_sys = $app->get('mailfrom');
		$status = $this->getStatus($user_id);

		if(empty($user_id))
		{
			$current_user = $app->getIdentity();
			if(!empty($current_user)) {
				$user_from = $current_user->id;
			} else {
				$eMConfig = ComponentHelper::getParams('com_emundus');
				$user_from = $eMConfig->get('automated_task_user', 62);
			}
		} else {
			$user_from = $user_id;
		}

		// Get all codes from fnum
		$codes = array();
		foreach($students as $student) {
			$codes[] = $student->code;
		}
		$codes = array_unique($codes);

		//*********************************************************************
		// Get email triggers
		include_once(JPATH_SITE.'/components/com_emundus/models/emails.php');
		$m_emails = new EmundusModelEmails;

		$triggers = $m_emails->getEmailTrigger($state, $codes, '0,1');

		if (!empty($triggers)) {
			// If the trigger does not have the applicant as recipient for a manager action AND has no other recipients, given the context is a manager action,
			// we therefore remove the trigger from the list.
			foreach ($triggers as $key => $trigger) {
				foreach ($trigger as $code => $data) {
					if ($data['to']['to_applicant'] == 0 && empty($data['to']['recipients'])) {
						unset($triggers[$key][$code]);
					}
				}

				if (empty($triggers[$key])) {
					unset($triggers[$key]);
				}
			}

			if (!empty($triggers)) {
				foreach($students as $student) {
					$emails_sent = $m_emails->sendEmailTrigger($state, [$student->code], '0,1', $student, null, $triggers);

					if (empty($emails_sent)) {
						$msg .= '<div class="alert alert-dismissable alert-danger">'.JText::_('COM_EMUNDUS_MAILS_EMAIL_NOT_SENT').' : '.$student->fnum.'</div>';
						JLog::add('Email trigger not sent for file ' . $student->fnum . ' on status ' . $state, JLog::ERROR, 'com_emundus.email');
					} else if (count($emails_sent) > 0) {
                        if ($emails_sent[0] === false) {
                            continue;
                        } else {
                            foreach(array_unique($emails_sent) as $recipient) {
								if ($student->is_anonym == 1) {
									$msg .= Text::_('COM_EMUNDUS_MAILS_EMAIL_SENT').' : '.$student->fnum.'<br>';
								} else {
									$msg .= Text::_('COM_EMUNDUS_MAILS_EMAIL_SENT').' : '.$recipient.'<br>';
								}
							}
                        }
					}
				}
			}
		}

		try {
			require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');
			$m_sms = new EmundusModelSMS();
			if ($m_sms->activated) {
				$fnums = array_map(function($student) {
					return $student->fnum;
				}, $students);
				$stored = $m_sms->triggerSMS($fnums, $state, $codes, false, $user_id);

				if (!$stored) {
					$msg .= '<div class="alert alert-dismissable alert-danger">'.Text::_('COM_EMUNDUS_SMS_FAILED').'</div>';
				}
			}
		} catch (Exception $e) {
			Log::add('Error trying to send SMS after update state: ' . $e->getMessage(), Log::ERROR, 'com_emundus.email');
		}


		return $msg;
	}

	public function saveFilters($user_id, $name, $filters, $item_id)
	{
		$saved = false;

		if (!empty($user_id) && !empty($name) && !empty($filters)) {

			$query     = $this->_db->getQuery(true);
			$query->insert($this->_db->quoteName('#__emundus_filters'))
				->columns(['user', 'name', 'constraints', 'mode', 'item_id'])
				->values($user_id . ', ' . $this->_db->quote($name) . ', ' . $this->_db->quote($filters) . ', ' . $this->_db->quote('search') . ', ' . $item_id);

			try {
				$this->_db->setQuery($query);
				$saved = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Error saving filter: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $saved;
	}

	public function getSavedFilters($user_id, $item_id = null, $filter_id = null)
	{
		$filters = array();

		if (!empty($user_id)) {
			$query     = $this->_db->createQuery();
			$query->select('ef.id, ef.name, ef.constraints, eff.id as favorite')
				->from($this->_db->quoteName('#__emundus_filters', 'ef'))
				->leftJoin($this->_db->quoteName('#__emundus_filters_favorites', 'eff') . ' ON ' . $this->_db->quoteName('ef.id') . ' = ' . $this->_db->quoteName('eff.filter_id') . ' AND ' . $this->_db->quoteName('eff.user_id') . ' = ' . $user_id)
				->where('ef.user = ' . $user_id)
				->where('ef.mode = ' . $this->_db->quote('search'));

			if (!empty($item_id)) {
				$query->where('ef.item_id = ' . $item_id);
			}

			if (!empty($filter_id)) {
				$query->where('ef.id = ' . $filter_id);
			}

			try {
				$this->_db->setQuery($query);
				$filters = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add('Error getting saved filters: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $filters;
	}

	/**
	 * Get filters that are shared with the user, by his group or by his id directly
	 * @param $user_id
	 * @param $item_id
	 *
	 * @return array
	 */
	public function getSharedFilters($user_id, $item_id)
	{
		$filters = [];

		if (!empty($user_id)) {
			$user_groups = [];
			if (!class_exists('EmundusModelUsers')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			}
			$m_users = new EmundusModelUsers();
			$user_groups = $m_users->getUserGroups($user_id, 'Column');

			if (is_null($this->_db)) {
				$this->_db = Factory::getContainer()->get('DatabaseDriver');
			}

			$query = $this->_db->createQuery();

			$query->select('ef.id, ef.name, ef.constraints, efa.shared_by, eff.id as favorite')
				->from($this->_db->quoteName('#__emundus_filters', 'ef'))
				->leftJoin($this->_db->quoteName('#__emundus_filters_assoc', 'efa') . ' ON ' . $this->_db->quoteName('efa') . '.filter_id = ' . $this->_db->quoteName('ef') . '.id')
				->leftJoin($this->_db->quoteName('#__emundus_filters_favorites', 'eff') . ' ON ' . $this->_db->quoteName('ef.id') . ' = ' . $this->_db->quoteName('eff.filter_id') . ' AND ' . $this->_db->quoteName('eff.user_id') . ' = ' . $user_id)
				->where('mode = ' . $this->_db->quote('search'));

			if (!empty($user_groups)) {
				$query->where('(' . $this->_db->quoteName('efa') . '.user_id = ' . $user_id . ' OR ' . $this->_db->quoteName('efa') . '.group_id IN (' . implode(',', $user_groups) . '))');
			} else {
				$query->where($this->_db->quoteName('efa') . '.user_id = ' . $user_id);
			}

			$query->where($this->_db->quoteName('ef') . '.item_id = ' . $item_id);

			try {
				$this->_db->setQuery($query);
				$filters = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				JLog::add('Error getting shared filters: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
			}
		}

		return $filters;
	}

	public function getDefaultFilterId($user_id) {
		$filter_id = 0;

		if (!empty($user_id)) {
			$query = $this->_db->createQuery();
			$query->select('filter_id')
				->from($this->_db->quoteName('#__emundus_filters_user_default_filter'))
				->where('user_id = ' . $user_id);

			try {
				$this->_db->setQuery($query);
				$filter_id = $this->_db->loadResult();
			}
			catch (Exception $e) {
				Log::add('Error getting default filter: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $filter_id;
	}

	public function getDefaultFilter($filter_id, $item_id = null, $user_id = null) {
		$filter = [];

		if (!empty($filter_id) || !empty($user_id)) {
			$query = $this->_db->createQuery();
			$query->select('ef.id, ef.name, ef.constraints')
				->from($this->_db->quoteName('#__emundus_filters', 'ef'))
				->where('ef.mode = ' . $this->_db->quote('search'));

			if (!empty($filter_id)) {
				$query->where('ef.id = ' . $filter_id);
			}

			if (!empty($user_id)) {
				$query->leftJoin($this->_db->quoteName('#__emundus_filters_user_default_filter', 'default') . ' ON default.filter_id = ef.id');
				$query->where('default.user_id = ' . $user_id);
			}

			if (!empty($item_id)) {
				$query->where('ef.item_id = ' . $item_id);
			}

			try {
				$this->_db->setQuery($query);
				$filter = $this->_db->loadAssoc();
			}
			catch (Exception $e) {
				Log::add('Error getting saved filters: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $filter;

	}

	/**
	 * @param $filter_id
	 *
	 * @return array
	 */
	public function getAlreadySharedTo($user_id, $filter_id)
	{
		$already_shared_to = [
			'users' => [],
			'groups' => []
		];

		if (!empty($filter_id)) {
			$query = $this->_db->createQuery();
			$query->select('user_id, group_id')
				->from($this->_db->quoteName('#__emundus_filters_assoc'))
				->where('filter_id = ' . $filter_id);

			try {
				$this->_db->setQuery($query);
				$associations = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				JLog::add('Error getting already shared to: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
			}

			if (!empty($associations)) {
				$users = [];
				$groups = [];

				foreach($associations as $association) {
					if (!empty($association['user_id'])) {
						$users[] = $association['user_id'];
					}
					if (!empty($association['group_id'])) {
						$groups[] = $association['group_id'];
					}
				}

				if (!empty($users)) {
					$query->clear()
						->select('id, name as label')
						->from($this->_db->quoteName('#__users'))
						->where('id IN (' . implode(',', $users) . ')');

					try {
						$this->_db->setQuery($query);
						$already_shared_to['users'] = $this->_db->loadAssocList();
					}
					catch (Exception $e) {
						JLog::add('Error getting already shared to users: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
					}
				}

				if (!empty($groups)) {
					$query->clear()
						->select('id, label')
						->from($this->_db->quoteName('#__emundus_setup_groups'))
						->where('id IN (' . implode(',', $groups) . ')');

					try {
						$this->_db->setQuery($query);
						$already_shared_to['groups'] = $this->_db->loadAssocList();
					}
					catch (Exception $e) {
						JLog::add('Error getting already shared to groups: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
					}
				}
			}
		}

		return $already_shared_to;
	}

	public function shareFilter($filter_id, $user_ids, $group_ids, $shared_by)
	{
		$shared = false;

		if (!empty($filter_id) && (!empty($user_ids) || !empty($group_ids))) {
			$query = $this->_db->createQuery();

			$sharings = [];
			foreach($user_ids as $user_id) {
				$query->clear()
					->insert($this->_db->quoteName('#__emundus_filters_assoc'))
					->columns(['filter_id', 'user_id', 'shared_by', 'shared_date'])
					->values($filter_id . ', ' . $user_id . ', ' . $shared_by . ', ' . $this->_db->quote(date('Y-m-d H:i:s')));

				try {
					$this->_db->setQuery($query);
					$sharings[] = $this->_db->execute();
				}
				catch (Exception $e) {
					JLog::add('Error sharing filter: ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
					$sharings[] = false;
				}
			}

			foreach($group_ids as $group_id) {
				$query->clear()
					->insert($this->_db->quoteName('#__emundus_filters_assoc'))
					->columns(['filter_id', 'group_id', 'shared_by', 'shared_date'])
					->values($filter_id . ', ' . $group_id . ', ' . $shared_by . ', ' . $this->_db->quote(date('Y-m-d H:i:s')));

				try {
					$this->_db->setQuery($query);
					$sharings[] = $this->_db->execute();
				}
				catch (Exception $e) {
					JLog::add('Error sharing filter: ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
					$sharings[] = false;
				}
			}

			$shared = !in_array(false, $sharings);
		}

		return $shared;
	}

	/**
	 * @param $filter_id int
	 * @param $share_to_id int user_id or group_id
	 * @param $column string user_id or group_id
	 * @param $shared_by int you are the owner of the filter, you can delete the sharing
	 *
	 * @return false
	 */
	public function deleteSharing($filter_id, $share_to_id, $column, $shared_by)
	{
		$deleted = false;

		if (!empty($filter_id) && !empty($share_to_id) && !empty($column) && !empty($shared_by)) {
			if ($column === 'user_id' || $column === 'group_id') {
				$query = $this->_db->createQuery();
				$query->delete($this->_db->quoteName('#__emundus_filters_assoc'))
					->where('filter_id = ' . $filter_id)
					->where($column . ' = ' . $share_to_id)
					->where('shared_by = ' . $shared_by);

				try {
					$this->_db->setQuery($query);
					$deleted = $this->_db->execute();
				}
				catch (Exception $e) {
					JLog::add('Error deleting sharing: ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
				}
			}
		}

		return $deleted;
	}

	public function updateFilter($user_id, $filter_id, $filters, $item_id)
	{
		$updated = false;

		if (!empty($user_id) && !empty($filter_id) && !empty($filters)) {

			$query     = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__emundus_filters'))
				->set('constraints = ' . $this->_db->quote($filters))
				->where('user = ' . $user_id)
				->where('id = ' . $filter_id)
				->where('item_id = ' . $item_id)
				->where('mode = ' . $this->_db->quote('search'));

			try {
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Error updating filter: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	public function renameFilter($user_id, $filter_id, $name)
	{
		$updated = false;

		if (!empty($user_id) && !empty($filter_id) && !empty($name)) {

			$query     = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__emundus_filters'))
				->set('name = ' . $this->_db->quote($name))
				->where('user = ' . $user_id)
				->where('id = ' . $filter_id);

			try {
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Error updating filter name: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	/**
	 * @param $user_id
	 * @param $filter_id
	 * @param $set_favorite
	 *
	 * @return bool
	 */
	public function toggleFilterFavorite($user_id, $filter_id, $set_favorite = 0): bool
	{
		$updated = false;

		if (!empty($user_id) && !empty($filter_id)) {
			$query = $this->_db->createQuery();

			if (!$set_favorite) {
				$query->delete($this->_db->quoteName('#__emundus_filters_favorites'))
					->where('user_id = ' . $user_id)
					->where('filter_id = ' . $filter_id);
			} else {
				$query->insert($this->_db->quoteName('#__emundus_filters_favorites'))
					->columns(['user_id', 'filter_id'])
					->values($user_id . ', ' . $filter_id);
			}

			try {
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			} catch (Exception $e) {
				Log::add('Error updating filter favorite: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}

		}

		return $updated;
	}

	public function defineAsDefaultFilter($user_id, $filter_id) {
		$set_default = false;

		if (!empty($user_id) && !empty($filter_id)) {
			$query = $this->_db->createQuery();
			$query->select('id')
				->from('#__emundus_filters_user_default_filter')
				->where('user_id = ' . $user_id);

			try {
				$this->_db->setQuery($query);
				$default_filter_row_id = $this->_db->loadResult();

				if (!empty($default_filter_row_id)) {
					$query->clear()
						->update('#__emundus_filters_user_default_filter')
						->set('filter_id = ' . $filter_id)
						->where('id = ' . $default_filter_row_id);

					$this->_db->setQuery($query);
					$set_default = $this->_db->execute();
				} else {
					$query->clear()
						->insert('#__emundus_filters_user_default_filter')
						->columns(['user_id', 'filter_id'])
						->values($user_id . ', ' . $filter_id);

					$this->_db->setQuery($query);
					$set_default = $this->_db->execute();
				}
			} catch (Exception $e) {
				Log::add('Define filter ' . $filter_id . ' as default for user ' . $user_id . ' failed', Log::ERROR, 'com_emundus.error');
			}
		}

		return $set_default;
	}

	public function getStatusByGroup($uid = null)
	{
		$status = array();

		if(empty($uid)) {
			$uid = Factory::getApplication()->getIdentity()->id;
		}

		try
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
			$m_users = new EmundusModelUsers();
			$groups = $m_users->getUserGroups($uid, 'Column');

			if(!empty($groups)) {
				$query = $this->_db->getQuery(true);

				$query->clear()
					->select('COUNT(id)')
					->from($this->_db->quoteName('#__emundus_setup_groups'))
					->where($this->_db->quoteName('id') . ' IN (' . implode(',',$this->_db->quote($groups)) . ')')
					->where($this->_db->quoteName('filter_status') . ' = 0');
				$this->_db->setQuery($query);
				$is_filter = $this->_db->loadResult();

				if ($is_filter == 0)
				{
					$query->clear()
						->select('DISTINCT status')
						->from($this->_db->quoteName('#__emundus_setup_groups_repeat_status'))
						->where($this->_db->quoteName('parent_id') . ' IN (' . implode(',', $this->_db->quote($groups)) . ')');
					$this->_db->setQuery($query);
					$status = $this->_db->loadColumn();
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error when get status by group ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $status;
	}

	public function exportZip($fnums, $form_post = 1, $attachment = 1, $eval_steps = [], $form_ids = null, $attachids = null, $options = null, $acl_override = false, $current_user = null, $params = []) {
		$eMConfig = ComponentHelper::getParams('com_emundus');

		require_once(JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'access.php');
		require_once(JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'export.php');
		require_once(JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');

		$m_emails = new EmundusModelEmails;

		$zip = new ZipArchive();
		$nom = date("Y-m-d").'_'.rand(1000,9999).'_x'.(count($fnums)).'.zip';

		$path = JPATH_SITE.DS.'tmp'.DS.$nom;

		$fnumsInfo = $this->getFnumsInfos($fnums);

		if (file_exists($path)) {
			unlink($path);
		}

		$concat_attachments_with_form = $params['concat_attachments_with_form'] ?? false;
		$convert_docx_to_pdf = $params['convert_docx_to_pdf'] ?? false;

		foreach ($fnums as $fnum) {

			if ($zip->open($path, ZipArchive::CREATE) == TRUE) {

				$dossier = EMUNDUS_PATH_ABS.$fnumsInfo[$fnum]['applicant_id'].DS;

				/// Build filename from tags, we are using helper functions found in the email model, not sending emails ;)
				$post = array(
					'FNUM' => $fnum,
					'CAMPAIGN_YEAR' => $fnumsInfo[$fnum]['year']
				);
				$application_form_name = $eMConfig->get('application_form_name', "application_form_pdf");
				if ($fnumsInfo[$fnum]['is_anonym'] == 1) {
					$application_form_name = 'anonym_file_' . $fnum; // tags could contain user name
				} else {
					$tags = $m_emails->setTags($fnumsInfo[$fnum]['applicant_id'], $post, $fnum, '', $application_form_name);
					$application_form_name = preg_replace($tags['patterns'], $tags['replacements'], $application_form_name);
					$application_form_name = $m_emails->setTagsFabrik($application_form_name, array($fnum));

					if ($application_form_name == "application_form_pdf") {
						$application_form_name = $fnumsInfo[$fnum]['name'].'_'.$fnum;
					}
				}

				// Format filename
				$application_form_name = $m_emails->stripAccents($application_form_name);
				$application_form_name = preg_replace('/[^A-Za-z0-9 _.-]/','', $application_form_name);
				$application_form_name = preg_replace('/\s/', '', $application_form_name);
				$application_form_name = strtolower($application_form_name);

				$application_pdf = $application_form_name . '_applications.pdf';

				$files_list = array();

				if (isset($form_post)) {
					$forms_to_export = array();
					if (!empty($form_ids)) {
						foreach ($form_ids as $fids) {
							$detail = explode("|", $fids);
							if ($detail[1] == $fnumsInfo[$fnum]['training'] && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
								$forms_to_export[] = $detail[0];
							}
						}
					}

					if ($form_post || !empty($forms_to_export)) {
						if ($concat_attachments_with_form) {
							$files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum],$fnumsInfo[$fnum]['applicant_id'], $fnum, $form_post, $forms_to_export, $options, null, null, false);
						} else {
							$files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $form_post, $forms_to_export, $options);
						}
					}
				}



				if (!empty($eval_steps) && (!empty($eval_steps['tables']) || !empty($eval_steps['groups']) || !empty($eval_steps['elements']))) {
					$elements = [
						['fids' => $eval_steps['tables'], 'gids' => $eval_steps['groups'], 'eids' => $eval_steps['elements']]
					];
					$options[] = 'eval_steps';

					$eval_pdf_filename = '_evaluations';
					$files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, 0, $eval_steps['tables'], $options, null, $elements, false, $eval_pdf_filename);
				}

				if ($concat_attachments_with_form) {
					if ($attachment || !empty($attachids)) {
						$attachment_to_export = array();
						if (!empty($attachids)) {
							foreach ($attachids as $aids) {
								$detail = explode("|", $aids);
								if ($detail[1] == $fnumsInfo[$fnum]['training'] && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
									$attachment_to_export[] = $detail[0];
								}
							}
						}

						if ($attachment || !empty($attachment_to_export)) {
							$files = $this->getFilesByFnums([$fnum], $attachment_to_export, true);
						}

						$tmpArray = [];
						EmundusHelperExport::getAttachmentPDF($files_list, $tmpArray, $files, $fnumsInfo[$fnum]['applicant_id'], $convert_docx_to_pdf);
					}
				}

				if (!empty($files_list)) {
					foreach ($files_list as $key => $file_list){
						if(empty($file_list)){
							unset($files_list[$key]);
						}
					}

					$gotenberg_merge_activation = $eMConfig->get('gotenberg_merge_activation', 0);

					if(!$gotenberg_merge_activation || count($files_list) == 1) {
						require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');

						$pdf = new ConcatPdf();
						$pdf->setFiles($files_list);
						$pdf->concat();

						if (isset($tmpArray)) {
							foreach ($tmpArray as $fn) {
								unlink($fn);
							}
						}
						$pdf->Output($dossier . $application_pdf, 'F');
					} else {
						$gotenberg_url = $eMConfig->get('gotenberg_url', '');

						if (!empty($gotenberg_url)) {
							$got_files = [];
							foreach ($files_list as $item) {
								$got_files[] = Stream::path($item);
							}
							$request  = Gotenberg::pdfEngines($gotenberg_url)
								->merge(...$got_files);
							$response = Gotenberg::send($request);
							$content = $response->getBody()->getContents();

							$filename = $dossier . $application_pdf;
							$fp       = fopen($filename, 'w');
							$pieces   = str_split($content, 1024 * 16);
							if ($fp)
							{
								foreach ($pieces as $piece) {
									fwrite($fp, $piece, strlen($piece));
								}
							}
						}
					}

					$filename = $application_form_name . DS . $application_pdf;
					if (!$zip->addFile($dossier . $application_pdf, $filename)) {
						continue;
					}
				}

				if (($attachment || !empty($attachids)) && !$concat_attachments_with_form) {
					$attachment_to_export = array();
					if (!empty($attachids)) {
						foreach($attachids as $aids){
							$detail = explode("|", $aids);
							if ($detail[1] == $fnumsInfo[$fnum]['training'] && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
								$attachment_to_export[] = $detail[0];
							}
						}
					}

					$fnum = explode(',', $fnum);
					if ($attachment || !empty($attachment_to_export)) {
						$files = $this->getFilesByFnums($fnum, $attachment_to_export);
						$file_ids = array();

						foreach($files as $file) {
							if (!empty($file['attachment_id'])) {
								$file_ids[] = $file['attachment_id'];
							}
						}

						// TODO: weird to use attachment_to_export here, should be $file_ids instead ? it has been like this for a long time, so I'm not sure
						$setup_attachments = $this->getSetupAttachmentsById($attachment_to_export);
						if (!empty($setup_attachments) && !empty($files)) {
							foreach($setup_attachments as $att) {
								if (!empty($files)) {
									foreach ($files as $file) {
										if ($file['attachment_id'] == $att['id']) {
											$filename = $application_form_name . DS . $file['filename'];
											$dossier = EMUNDUS_PATH_ABS . $fnumsInfo[$file['fnum']]['applicant_id'] . DS;
											if (file_exists($dossier . $file['filename'])) {
												if (!$zip->addFile($dossier . $file['filename'], $filename)) {
													continue;
												}
											} else {
												$zip->addFromString($filename."-missing.txt", '');
											}
										} elseif (!in_array($att['id'], $file_ids)) {
											$zip->addFromString($application_form_name.DS.str_replace('_', "", $att['lbl'])."-notfound.txt", '');
										}
									}
								} elseif (empty($files)) {
									foreach ($setup_attachments as $att) {
										$zip->addFromString($application_form_name . DS .str_replace('_', "", $att['lbl']) ."-notfound.txt", '');
									}
								}
							}
						} elseif (!empty($files)) {
							foreach ($files as $file) {
								$filename = $application_form_name . DS . $file['filename'];
								$dossier = EMUNDUS_PATH_ABS . $fnumsInfo[$file['fnum']]['applicant_id'] . DS;
								if (file_exists($dossier . $file['filename'])) {
									if (!$zip->addFile($dossier . $file['filename'], $filename)) {
										continue;
									}
								} else {
									$zip->addFromString($filename."-missing.txt", '');
								}
							}
						} elseif (empty($files)) {
							foreach ($setup_attachments as $att) {
								$zip->addFromString($application_form_name . DS .str_replace('_', "", $att['lbl']) ."-notfound.txt", '');
							}
						}
					}
				}
				$zip->close();

			} else {
				die("ERROR");
			}
		}

		return $nom;
	}

	/**
	 * @param $validFnums
	 * @param $file
	 * @param $totalfile
	 * @param $start
	 * @param $forms
	 * @param $attachment
	 * @param $assessment @deprecated, evaluation steps are now in forms
	 * @param $decision @deprecated, evaluation steps are now in forms
	 * @param $admission @deprecated, evaluation steps are now in forms
	 * @param $ids
	 * @param $formid
	 * @param $attachids
	 * @param $options
	 * @param $pdf_data
	 *
	 * @return array
	 * @throws \Gotenberg\Exceptions\GotenbergApiErrored
	 */
	public function generatePDF($validFnums, $file, $totalfile = 1, $start = 0, $forms = 0, $attachment = 0, $assessment = 0, $decision = 0, $admission = 0, $ids = null, $formid = null, $attachids = null, $options = null, $pdf_data = [])
	{
		$response_status = false;
		$dataresult = array();

		if (!empty($validFnums) && !empty($file)) {
			$eMConfig = ComponentHelper::getParams('com_emundus');

			$formids    = explode(',', $formid);
			if (!is_array($attachids)) {
				$attachids = explode(',', $attachids);
			}
			if(!is_array($options)) {
				$options = explode(',', $options);
			}

			$fnumsInfo = $this->getFnumsInfos($validFnums);

			if (count($validFnums) == 1) {
				$application_form_name = empty($admission) ? $eMConfig->get('application_form_name', "application_form_pdf") : $eMConfig->get('application_admission_name', "application_form_pdf");

				if ($application_form_name != "application_form_pdf") {

					require_once(JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'checklist.php');
					$m_checklist = new EmundusModelChecklist;

					$fnum = $validFnums[0];
					$post = array(
						'FNUM' => $fnum,
						'CAMPAIGN_YEAR' => $fnumsInfo[$fnum]['year']
					);

					// Format filename
					$application_form_name = $m_checklist->formatFileName($application_form_name, $fnum, $post);

					if ($file != $application_form_name.'.pdf' && file_exists(JPATH_SITE.DS.'tmp'.DS.$application_form_name.'.pdf')) {
						unlink(JPATH_SITE.DS.'tmp'.DS.$application_form_name.'.pdf');
					}

					$file = $application_form_name.'.pdf';
				}
			}

			if (file_exists(JPATH_SITE . DS . 'tmp' . DS . $file)) {
				$files_list = array(JPATH_SITE.DS.'tmp'.DS.$file);
			} else {
				$files_list = array();
			}

			for ($i = $start; $i <= $totalfile; $i++) {
				$fnum = $validFnums[$i];
				if (is_numeric($fnum) && !empty($fnum)) {
					if (isset($forms)) {
						$forms_to_export = array();
						if (!empty($formids)) {
							foreach ($formids as $fids) {

								if (strpos('|', $fids) !== false) {
									$fids = explode("|", $fids);
									if ((!empty($detail[1]) && $detail[1] == $fnumsInfo[$fnum]['training']) && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
										$forms_to_export[] = $detail[0];
									}
								} else if (is_numeric($fids)) {
									$forms_to_export[] = $fids;
								}
							}
						}

						if ($forms || !empty($forms_to_export)) {
							require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'profile.php');
							$m_profile = new EmundusModelProfile;
							$infos = $m_profile->getFnumDetails($fnum);
							$campaign_id = $infos['campaign_id'];

							$files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $forms, $forms_to_export, $options, null, $pdf_data, in_array("upload", $options));
						}
					}

					if ($attachment || !empty($attachids)) {
						$tmpArray = array();
						$m_application = new EmundusModelApplication();
						$attachment_to_export = array();
						foreach ($attachids as $aids) {
							$detail = explode("|", $aids);
							if ((!empty($detail[1]) && $detail[1] == $fnumsInfo[$fnum]['training']) && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
								$attachment_to_export[] = $detail[0];
							}
						}
						if ($attachment || !empty($attachment_to_export)) {
							$files = $m_application->getAttachmentsByFnum($fnum, $ids, $attachment_to_export);
							$files_export = EmundusHelperExport::getAttachmentPDF($files_list, $tmpArray, $files, $fnumsInfo[$fnum]['applicant_id']);
						}
					}

					EmundusModelLogs::log($this->app->getIdentity()->id, (int) $fnumsInfo[$fnum]['applicant_id'], $fnum, 8, 'c', 'COM_EMUNDUS_ACCESS_EXPORT_PDF');
				}

			}
			$start = $i;


			if (count($files_list) === 1 && !empty($files_list[0]))
			{
				copy($files_list[0], JPATH_SITE . DS . 'tmp' . DS . $file);

				$start = $i;

				$dataresult = [
					'start' => $start, 'totalfile' => $totalfile, 'forms' => $forms, 'formids' => $formid, 'attachids' => $attachids,
					'options' => $options, 'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
					'admission' => $admission, 'file' => $file, 'ids' => $ids, 'path'=>JURI::base(), 'msg' => JText::_('COM_EMUNDUS_EXPORTS_FILES_ADDED')//.' : '.$fnum
				];
				$response_status = true;
			}
			elseif (count($files_list) > 1)
			{
				foreach ($files_list as $key => $file_list){
					if(empty($file_list)){
						unset($files_list[$key]);
					}
				}

				$gotenberg_merge_activation = $eMConfig->get('gotenberg_merge_activation', 0);

				if(!$gotenberg_merge_activation) {
					require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');

					$pdf = new ConcatPdf();
					$pdf->setFiles($files_list);
					$pdf->concat();

					if (isset($tmpArray)) {
						foreach ($tmpArray as $fn) {
							unlink($fn);
						}
					}
					$pdf->Output(JPATH_SITE . DS . 'tmp' . DS . $file, 'F');
				} else {
					$gotenberg_url = $eMConfig->get('gotenberg_url', 'http://localhost:3000');

					if (!empty($gotenberg_url)) {
						$got_files = [];
						foreach ($files_list as $item) {
							$got_files[] = Stream::path($item);
						}
						$request  = Gotenberg::pdfEngines($gotenberg_url)
							->merge(...$got_files);
						$response = Gotenberg::send($request);
						$content = $response->getBody()->getContents();

						$filename = JPATH_SITE . DS . 'tmp' . DS . $file;
						$fp       = fopen($filename, 'w');
						$pieces   = str_split($content, 1024 * 16);
						if ($fp)
						{
							foreach ($pieces as $piece) {
								fwrite($fp, $piece, strlen($piece));
							}
						}
					}
				}

				$start = $i;

				$dataresult = [
					'start' => $start, 'totalfile' => $totalfile, 'forms' => $forms, 'formids' => $formid, 'attachids' => $attachids,
					'options' => $options, 'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
					'admission' => $admission, 'file' => $file, 'ids' => $ids, 'path'=>JURI::base(), 'msg' => JText::_('COM_EMUNDUS_EXPORTS_FILES_ADDED')//.' : '.$fnum
				];
				$response_status = true;
			}
			else
			{
				$response_status = false;
				$dataresult = [
					'start' => $start, 'totalfile' => $totalfile, 'forms' => $forms, 'formids' => $formid, 'attachids' => $attachids,
					'options' => $options, 'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
					'admission' => $admission, 'file' => $file, 'ids' => $ids, 'path'=>JURI::base(), 'msg' => JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND')
				];
			}
		}

		return [
			'status' => $response_status,
			'json' => $dataresult
		];
	}

	/**
	 * @param $fnums
	 * @param $state
	 * @return bool|mixed
	 */
	public function makeAttachmentsEditableByApplicant($fnums, $state)
	{
		$updated = false;

		if (!empty($fnums) && isset($state)) {
			$fnums = is_array($fnums) ? $fnums : [$fnums];
			$emundus_config = ComponentHelper::getParams('com_emundus');
			$can_edit_back_attachments = $emundus_config->get('can_edit_back_attachments', 0);
			if ($can_edit_back_attachments) {
				$attachment_to_keep_non_deletable = $emundus_config->get('attachment_to_keep_non_deletable', []);

				$status_for_send = $emundus_config->get('status_for_send', '0');
				$status_for_send = explode(',', $status_for_send);

				$edit_status = array_unique(array_merge(['0'], $status_for_send));
				$m_profile = new EmundusModelProfile();

				$tasks = [];
				foreach ($fnums as $fnum) {
					$current_profile = $m_profile->getProfileByStatus($fnum);

					if (empty($current_profile)) {
						continue;
					}

					if (empty($current_profile['workflow_id'])) {
						if (!in_array($state, $edit_status)) {
							continue;
						}
					}

					$fnumInfos = $this->getFnumInfos($fnum);

					$query = $this->_db->getQuery(true);

					$query->select('attachment_id')
						->from('#__emundus_setup_attachment_profiles')
						->where('profile_id = ' . $this->_db->quote($current_profile['profile']));

					if (!empty($attachment_to_keep_non_deletable)) {
						$query->andWhere('attachment_id NOT IN (' . implode(',', $this->_db->quote($attachment_to_keep_non_deletable)) . ')');
					}

					$this->_db->setQuery($query);
					$attachments = $this->_db->loadColumn();

					if (!empty($attachments)) {
						try {
							$query->clear()
								->update('#__emundus_uploads')
								->set('can_be_deleted = 1')
								->where('fnum LIKE ' . $this->_db->quote($fnum))
								->andWhere('attachment_id IN (' . implode(',', $attachments) . ')')
								->andWhere('user_id = ' . $fnumInfos['applicant_id']);
							$this->_db->setQuery($query);
							$tasks[] = $this->_db->execute();
						} catch (Exception $e) {
							Log::add('Error making attachments editable by applicant for fnum: ' . $fnum . ' and profile: ' . $current_profile['profile'] . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
						}
					} else {
						$tasks[] = true;
					}
				}

				$updated = !in_array(false, $tasks, true);
			}
		}

		return $updated;
	}

	public function deleteFilter($filter_id,$user_id = 0)
	{
		$deleted   = false;

		try
		{
			if(empty($user_id)) {
				$user_id = Factory::getApplication()->getIdentity()->id;
			}

			if (!empty($filter_id) && !empty($user_id)) {
				$query = $this->_db->getQuery(true);
				$query->delete('#__emundus_filters')
					->bind(':filterId', $filter_id, ParameterType::INTEGER)
					->bind(':userId', $user_id, ParameterType::INTEGER)
					->where('id = :filterId')
					->where('user = :userId');
				$this->_db->setQuery($query);
				$result  = $this->_db->execute();

				$deleted = $result == 1;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error deleting filter: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $deleted;
	}

	public function getReferentEmail($keyid,$fnum)
	{
		$referent_email = '';

		if(!empty($keyid) && !empty($fnum))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				$query->select('email')
					->from($this->_db->quoteName('#__emundus_files_request'))
					->where($this->_db->quoteName('keyid') . ' = ' . $this->_db->quote($keyid))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
				$this->_db->setQuery($query);
				$referent_email = $this->_db->loadResult();
			}
			catch (Exception $e)
			{
				Log::add('Error getting referent email: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $referent_email;
	}

	public function getAssociatedDate(string $fnum, int $user_id): string
	{
		$associated_date = '';
		$query = $this->_db->getQuery(true);

		try
		{
			$query->select('time_date')
				->from($this->_db->quoteName('#__emundus_users_assoc'))
				->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum))
				->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id))
				->where($this->_db->quoteName('action_id') . ' = 1')
				->where($this->_db->quoteName('r') . ' = 1');
			$this->_db->setQuery($query);
			$associated_date = $this->_db->loadResult();

			if (empty($associated_date)) {
				$associated_date = '';
			}
		}
		catch (Exception $e)
		{
			Log::add('Error getting associated date: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $associated_date;
	}


	public function getEvaluationsArray(array $fnums, array $steps_elements, ExportModeEnum $exportMode = ExportModeEnum::GROUP_CONCAT_DISTINCT): array
	{
		$data_by_fnum = [];

		if (!empty($fnums) && !empty($steps_elements)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$m_workflow = new EmundusModelWorkflow();
			foreach($fnums as $fnum) {
				foreach ($steps_elements as $step_id => $step_elements_array) {
					if (!empty($step_id) && !empty($step_elements_array)) {
						$data_by_fnum[$fnum][$step_id] = $m_workflow->getEvaluationStepDataForFnum($fnum, $step_id, $step_elements_array, $exportMode);
					}
				}
			}
		}

		return $data_by_fnum;
	}

	/**
	 * @param   array   $fabrik_element
	 * @param   string  $fnum
	 * @param   int     $row_id
	 *
	 * @return array
	 */
	public function getFabrikElementValue(array $fabrik_element, string $fnum, int $row_id = 0): array
	{
		if (!class_exists('EmundusHelperFabrik')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
		}

		$helper = new EmundusHelperFabrik();
		return $helper->getFabrikElementValue($fabrik_element, $fnum, $row_id);
	}

	/**
	 * @param   array           $fnums_data
	 * @param   array           $evaluations_by_fnum_by_step
	 * @param   array           $columns
	 * @param   ExportModeEnum  $exportMode
	 *
	 * @return array
	 */
	public function mergeEvaluations(array $fnums_data, array $evaluations_by_fnum_by_step, array $columns, ExportModeEnum $exportMode = ExportModeEnum::GROUP_CONCAT_DISTINCT): array
	{
		$new_fnums_data = [];

		$temporary_fnums_data = [];
		if (!empty($fnums_data))
		{
			foreach ($fnums_data as $data)
			{
				if (isset($evaluations_by_fnum_by_step[$data['fnum']]))
				{
					// each fnum can have multiple evaluations $evaluations_by_fnums[$fnum] is an array of evaluations
					foreach ($evaluations_by_fnum_by_step[$data['fnum']] as $evaluations_by_steps)
					{
						foreach ($evaluations_by_steps as $evaluation)
						{
							$temporary_fnums_data[] = array_merge($data, $evaluation);
						}
					}
				}
				else
				{
					$temporary_fnums_data[] = $data;
				}
			}
		}
		else
		{
			foreach ($evaluations_by_fnum_by_step as $fnum => $evaluations_by_steps)
			{
				foreach ($evaluations_by_steps as $evaluations)
				{
					foreach ($evaluations as $evaluation)
					{
						$temporary_fnums_data[] = array_merge([
							'fnum'        => $fnum,
							'email'       => '',
							'label'       => '',
							'campaign_id' => '',
						], $evaluation);
					}
				}
			}
		}

		foreach ($temporary_fnums_data as $key => $fnum_data)
		{
			foreach ($columns as $column)
			{
				$column_name = !empty($column->table_join) ? $column->table_join . '___' . $column->element_name : $column->tab_name . '___' . $column->element_name;

				if (!isset($fnum_data[$column_name]))
				{
					$temporary_fnums_data[$key][$column_name] = '';
				}
			}
		}

		// columns must be in the same order as the columns in the table
		if (!empty($temporary_fnums_data))
		{
			$default_fnums_data_columns = [];
			if (!empty($fnums_data))
			{
				foreach ($fnums_data as $fnum_data)
				{
					$default_fnums_data_columns = array_merge($default_fnums_data_columns, array_keys($fnum_data));
				}
			}
			else
			{
				$default_fnums_data_columns = ['fnum', 'email', 'label', 'campaign_id'];
			}

			$evaluation_columns_in_order = [
				'step_id',
				'evaluation_id',
				'evaluator_name',
			];
			if (!empty($columns))
			{
				foreach ($columns as $column)
				{
					$column_name                   = !empty($column->table_join) ? $column->table_join . '___' . $column->element_name : $column->tab_name . '___' . $column->element_name;
					$evaluation_columns_in_order[] = $column_name;
				}
			}

			$columns_in_order = array_merge($default_fnums_data_columns, $evaluation_columns_in_order);

			switch($exportMode)
			{
				case ExportModeEnum::GROUP_CONCAT_DISTINCT:
				case ExportModeEnum::GROUP_CONCAT:
					// only one line per fnum with distinct values concatenated
					$fnum_grouped_data = [];
					foreach ($temporary_fnums_data as $fnum_data)
					{
						$fnum = $fnum_data['fnum'];

						if (!isset($fnum_grouped_data[$fnum]))
						{
							$fnum_grouped_data[$fnum] = [];
							foreach ($columns_in_order as $column_name)
							{
								$fnum_grouped_data[$fnum][$column_name] = [];
							}
						}

						foreach ($columns_in_order as $column_name)
						{
							$value = $fnum_data[$column_name];
							if ($value !== '')
							{
								if ($exportMode === ExportModeEnum::GROUP_CONCAT_DISTINCT)
								{
									if (!in_array($value, $fnum_grouped_data[$fnum][$column_name], true))
									{
										$fnum_grouped_data[$fnum][$column_name][] = $value;
									}
								}
								else
								{
									$fnum_grouped_data[$fnum][$column_name][] = $value;
								}
							}
						}
					}

					// build final array
					foreach ($fnum_grouped_data as $fnum => $columns_data)
					{
						$final_array = [];
						foreach ($columns_in_order as $column_name)
						{
							$final_array[$column_name] = implode(', ', $columns_data[$column_name]);
						}

						$new_fnums_data[] = $final_array;
					}

					break;

				case ExportModeEnum::LEFT_JOIN:
					// todo: repeat group data is concatenated instead of having multiple lines
					// if a column has multiple values, it is an array
					// if so, we have to make multiple rows for as much entries as there is ?

					foreach ($temporary_fnums_data as $fnum_data)
					{
						$max_index = null;

						foreach ($columns_in_order as $column_name)
						{
							if (is_array($fnum_data[$column_name]))
							{
								$max_index = max($max_index, count($fnum_data[$column_name]));
							}
						}

						$final_array = [];

						if (empty($max_index))
						{
							foreach ($columns_in_order as $column_name)
							{
								$final_array[$column_name] = $fnum_data[$column_name];
							}

							$new_fnums_data[] = $final_array;
						}
						else
						{
							for ($i = 0; $i < $max_index; $i++)
							{
								foreach ($columns_in_order as $column_name)
								{
									if (is_array($fnum_data[$column_name]))
									{
										$final_array[$column_name] = $fnum_data[$column_name][$i] ?? '';
									}
									else
									{
										$final_array[$column_name] = $fnum_data[$column_name];
									}
								}

								$new_fnums_data[] = $final_array;
							}
						}
					}

					break;
			}
		}

		return $new_fnums_data;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return string
	 */
	public function getFileSynthesis(string $fnum): string
	{
		$synthesis = '';

		if (!empty($fnum))
		{
			$query = $this->_db->createQuery();
			$query->select('ecc.applicant_id, esp.synthesis')
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'), 'ecc.campaign_id = esc.id')
				->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'esp'), 'esc.training = esp.code')
				->where($this->_db->quoteName('ecc.fnum') . ' = ' . $this->_db->quote($fnum));

			$this->_db->setQuery($query);
			$file = $this->_db->loadAssoc();

			if (!empty($file['synthesis'])) {
				if (!class_exists('EmundusModelEmails')) {
					require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
				}

				try {
					$m_emails = new EmundusModelEmails();
					$tags = $m_emails->setTags($file['applicant_id'], null, $fnum, '', $file['synthesis'], false, true);
					$synthesis = preg_replace($tags['patterns'], $tags['replacements'], $file['synthesis']);
					$synthesis = $m_emails->setTagsFabrik($synthesis, [$fnum]);
				} catch (Exception $e) {
					Log::add('Error getting synthesis: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}
			}
		}

		if (!empty($synthesis)) {
			$sanitizer = HtmlSanitizerSingleton::getInstance();
			$synthesis = $sanitizer->sanitize($synthesis);
		}

		return $synthesis;
	}
}
