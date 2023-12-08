<?php
/**
 * Users Model for eMundus Component
 *
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class EmundusModelGroups extends JModelList
{
	private $app;
	private $db;

	private $_total = null;
	private $_pagination = null;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();
		global $option;

		$this->app = Factory::getApplication();
		$this->db  = Factory::getContainer()->get('DatabaseDriver');

		// Get pagination request variables
		$limit      = $this->app->getUserStateFromRequest('global.list.limit', 'limit', $this->app->get('list_limit'), 'int');
		$limitstart = $this->app->getInput()->get('limitstart', 0, '', 'int');

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$filter_order     = $this->app->getUserStateFromRequest($option . 'filter_order', 'filter_order', 'lastname', 'cmd');
		$filter_order_Dir = $this->app->getUserStateFromRequest($option . 'filter_order_Dir', 'filter_order_Dir', 'asc', 'word');

		$this->setState('filter_order', $filter_order);
		$this->setState('filter_order_Dir', $filter_order_Dir);

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
	}


	function _buildContentOrderBy()
	{
		$orderby          = '';
		$filter_order     = $this->getState('filter_order');
		$filter_order_Dir = $this->getState('filter_order_Dir');

		$can_be_ordering = array('user', 'id', 'lastname', 'nationality', 'time_date', 'profile');
		if (!empty($filter_order) && !empty($filter_order_Dir) && in_array($filter_order, $can_be_ordering)) {
			$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;
		}

		return $orderby;
	}

	function getCampaign()
	{
		$query = $this->db->getQuery(true);

		$query->select('year as schoolyear')
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);
		$syear = $this->db->loadRow();

		return $syear[0];
	}

	function getProfileAcces($user)
	{
		$query = $this->db->getQuery(true);
		
		$query->select('esg.profile_id')
			->from($this->db->quoteName('#__emundus_setup_groups', 'esg'))
			->leftJoin($this->db->quoteName('#__emundus_groups', 'eg') . ' ON esg.id=eg.group_id')
			->where($this->db->quoteName('eg.user_id') . ' = ' . $user)
			->where($this->db->quoteName('esg.published') . ' = 1');
		$this->db->setQuery($query);
		return $this->db->loadAssocList();
	}

	function _buildQuery()
	{
		$gid           = $this->app->getInput()->getInt('groups', 0);
		$profile       = $this->app->getInput()->getInt('profile', 0);
		$uid           = $this->app->getInput()->getInt('user', 0);
		$quick_search  = $this->app->getInput()->getString('s', '');
		$search        = $this->app->getInput()->get('elements', []);
		$search_values = $this->app->getInput()->get('elements_values', []);
		$schoolyears   = $this->app->getInput()->getString('schoolyears', '');

		// Starting a session.
		$session           = $this->app->getSession();
		$s_elements        = $session->get('s_elements');
		$s_elements_values = $session->get('s_elements_values');
		if (empty($schoolyears) && $session->has('schoolyears')) $schoolyears = $session->get('schoolyears');

		if (count($search) == 0) {
			$search        = $s_elements;
			$search_values = $s_elements_values;
		}
		$user  = Factory::getApplication()->getIdentity();

		$query = $this->db->getQuery(true);

		$query->select('ed.user,ed.time_date,ed.validated,
					eu.firstname, eu.lastname, eu.profile, eu.schoolyear,
					u.id, u.name, u.email, u.username, u.usertype, u.registerDate, u.block,
					epd.nationality, epd.gender')
			->from($this->db->quoteName('#__emundus_declaration', 'ed'))
			->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ed.user=eu.user_id')
			->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ed.user=u.id')
			->leftJoin($this->db->quoteName('#__emundus_personal_detail', 'epd') . ' ON ed.user=epd.user');

		if (!empty($gid) || !empty($uid)) {
			$query->leftJoin($this->db->quoteName('#__emundus_groups_eval', 'ege') . ' ON ege.applicant_id=epd.user');
		}

		if (!empty($search)) {
			$i = 0;
			foreach ($search as $s) {
				$tab = explode('.', $s);
				if (count($tab) > 1) {
					$query->leftJoin($this->db->quoteName($tab[0], 'j' . $i) . ' ON j' . $i . '.user=ed.user');
					$i++;
				}
			}

		}

		$query->where($this->db->quoteName('ed.validated') . ' = 1');
		if (empty($schoolyears)) {
			$query->where($this->db->quoteName('eu.schoolyear') . ' LIKE ' . $this->db->quote('%'.$this->getCampaign().'%'));
		}

		if (!empty($profile)) {
			$query->where($this->db->quoteName('eu.user_id') . ' IN (' . implode(',', $this->getApplicantsByProfile($profile)) . ')');
		}

		$no_filter = array("Super Users", "Administrator");
		if (!in_array($user->usertype, $no_filter)) {
			$query->where($this->db->quoteName('eu.user_id') . ' IN (select user_id from #__emundus_users_profiles where profile_id in (' . implode(',', $this->getProfileAcces($user->id)) . '))');
		}

		if (!empty($search)) {
			$i = 0;
			foreach ($search as $s) {
				$tab = explode('.', $s);
				if (count($tab) > 1) {
					$query->where('j' . $i . '.' . $tab[1] . ' LIKE "%' . $search_values[$i] . '%"');
					$i++;
				}
			}

		}
		if (!empty($quick_search)) {
			if (is_numeric($quick_search)) {
				$query->where($this->db->quoteName('u.id') . ' = ' . $quick_search);
			}
			else {
				$query->where($this->db->quoteName('eu.lastname') . ' LIKE ' . $this->db->quote('%' . $quick_search . '%') . '
							OR ' . $this->db->quoteName('eu.firstname') . ' LIKE ' . $this->db->quote('%' . $quick_search . '%') . '
							OR ' . $this->db->quoteName('u.email') . ' LIKE ' . $this->db->quote('%' . $quick_search . '%') . '
							OR ' . $this->db->quoteName('u.username') . ' LIKE ' . $this->db->quote('%' . $quick_search . '%')
				);
			}
		}

		if (!empty($gid)) {
			$query->where($this->db->quoteName('ege.group_id') . ' = ' . $gid)
				->orWhere($this->db->quoteName('ege.user_id') . ' IN (select user_id FROM #__emundus_groups WHERE group_id=' . $this->db->quote($gid).')');
		}

		if (!empty($uid)) {
			$query->where($this->db->quoteName('ege.user_id') . ' = ' . $uid)
				->orWhere($this->db->quoteName('ege.group_id') . ' IN (select group_id FROM #__emundus_groups WHERE user_id=' . $this->db->quote($uid).')');
		}

		if (!empty($schoolyears)) {
			$query->where($this->db->quoteName('eu.schoolyear') . ' = ' . $this->db->quote($schoolyears));
		}

		return $query->__toString();
	}

	function getUsers()
	{
		$query = $this->_buildQuery();
		$query .= $this->_buildContentOrderBy();

		return $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
	}


	function getProfiles()
	{
		$query = $this->db->getQuery(true);

		$query->select('esp.id, esp.label, esp.acl_aro_groups, caag.lft')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->innerJoin($this->db->quoteName('#__usergroups', 'caag') . ' ON esp.acl_aro_groups=caag.id')
			->order('caag.lft, esp.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getProfilesByIDs($ids)
	{
		$query = $this->db->getQuery(true);

		$query->select('esp.id, esp.label, esp.acl_aro_groups, caag.lft')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->innerJoin($this->db->quoteName('#__usergroups', 'caag') . ' ON esp.acl_aro_groups=caag.id')
			->where($this->db->quoteName('esp.id') . ' IN (' . implode(',', $ids) . ')')
			->order('caag.lft, esp.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getAuthorProfiles()
	{
		$query = $this->db->getQuery(true);

		$query->select('esp.id,esp.label,esp.acl_aro_groups,esp.evaluation_start,esp.evaluation_end,caag.lft')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->innerJoin($this->db->quoteName('#__usergroups', 'caag') . ' ON esp.acl_aro_groups=caag.id')
			->where($this->db->quoteName('esp.acl_aro_groups') . ' = 19')
			->order('caag.lft, esp.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getEvaluators()
	{
		$query = 'SELECT u.id, u.name
		FROM #__users u, #__emundus_users_profiles eup , #__emundus_setup_profiles esp
		WHERE u.id=eup.user_id AND esp.id=eup.profile_id AND esp.is_evaluator=1';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getApplicantsProfiles()
	{
		$query = $this->db->getQuery(true);

		$query->select('esp.id,esp.label')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->where($this->db->quoteName('esp.published') . ' = 1')
			->order('esp.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	function getApplicantsByProfile($profile)
	{
		$query = $this->db->getQuery(true);

		$query->select('eup.user_id')
			->from($this->db->quoteName('#__emundus_users_profiles', 'eup'))
			->where($this->db->quoteName('eup.profile_id') . ' = ' . $profile);
		$this->db->setQuery($query);

		return $this->db->loadAssocList();
	}

	function getGroups()
	{
		$query = $this->db->getQuery(true);

		$query->select('esg.id,esg.label')
			->from($this->db->quoteName('#__emundus_setup_groups', 'esg'))
			->where($this->db->quoteName('esg.published') . ' = 1')
			->order('esg.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getGroupsByCourse($course)
	{
		$query = $this->db->getQuery(true);

		$query->select('esg.id,esg.label')
			->from($this->db->quoteName('#__emundus_setup_groups', 'esg'))
			->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc') . ' ON esg.id = esgrc.parent_id')
			->where($this->db->quoteName('esg.published') . ' = 1')
			->where($this->db->quoteName('esgrc.course') . ' = ' . $this->db->quote($course))
			->order('esg.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getGroupsIdByCourse($course)
	{
		$query = $this->db->getQuery(true);

		$query->select('esg.id')
			->from($this->db->quoteName('#__emundus_setup_groups', 'esg'))
			->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc') . ' ON esg.id = esgrc.parent_id')
			->where($this->db->quoteName('esg.published') . ' = 1')
			->where($this->db->quoteName('esgrc.course') . ' = ' . $this->db->quote($course))
			->order('esg.label');
		$this->db->setQuery($query);

		return $this->db->loadAssocList();
	}

	function getGroupsEval()
	{
		$query = $this->db->getQuery(true);

		$query->select('ege.id,ege.applicant_id,ege.user_id,ege.group_id')
			->from($this->db->quoteName('#__emundus_groups_eval', 'ege'));
		$this->db->setQuery($query);

		return $this->db->loadObjectList('applicant_id');
	}

	function getUsersGroups()
	{
		$query = $this->db->getQuery(true);

		$query->slect('eg.user_id, eg.group_id')
			->from($this->db->quoteName('#__emundus_groups', 'eg'));
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	function getUsersByGroup($gid)
	{
		$query = $this->db->getQuery(true);

		$query->select('eg.user_id, eg.group_id')
			->from($this->db->quoteName('#__emundus_groups', 'eg'))
			->where($this->db->quoteName('eg.group_id') . ' = ' . $gid);
		$this->db->setQuery($query);

		return $this->db->loadAssocList();
	}

	function getUsersByGroups($gids)
	{
		$query = $this->db->getQuery(true);

		$query->select('eg.user_id, eg.group_id')
			->from($this->db->quoteName('#__emundus_groups', 'eg'))
			->where($this->db->quoteName('eg.group_id') . ' IN (' . implode(',', $gids) . ')');
		$this->db->setQuery($query);

		return $this->db->loadAssocList();
	}

	function affectEvaluatorsGroups($groups, $aid)
	{
		$query = $this->db->getQuery(true);

		foreach ($groups as $group) {

			$query->insert($this->db->quoteName('#__emundus_groups_eval'))
				->columns($this->db->quoteName(array('applicant_id', 'group_id')))
				->values($this->db->quote($aid) . ',' . $this->db->quote($group));

			$this->db->setQuery($query);
			try {
				$this->db->execute();
			}
			catch (Exception $e) {
				// catch any database errors.
			}
		}

	}

	function getAuthorUsers()
	{
		$query = $this->db->getQuery(true);

		$query->select('u.id,u.gid,u.name')
			->from($this->db->quoteName('#__users', 'u'))
			->where($this->db->quoteName('u.gid') . ' = 19');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getMobility()
	{
		$query = $this->db->getQuery(true);

		$query->select('esm.id, esm.label, esm.value')
			->from($this->db->quoteName('#__emundus_setup_mobility', 'esm'))
			->order('esm.ordering');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	function getElements()
	{
		$query = 'SELECT element.id, element.name AS element_name, element.label AS element_label, element.plugin AS element_plugin,
				 groupe.label AS group_label, INSTR(groupe.params,\'"repeat_group_button":"1"\') AS group_repeated,
				 tab.db_table_name AS table_name, tab.label AS table_label
			FROM jos_fabrik_elements element
				 INNER JOIN jos_fabrik_groups AS groupe ON element.group_id = groupe.id
				 INNER JOIN jos_fabrik_formgroup AS formgroup ON groupe.id = formgroup.group_id
				 INNER JOIN jos_fabrik_lists AS tab ON tab.form_id = formgroup.form_id
				 INNER JOIN jos_menu AS menu ON tab.id = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("listid=",menu.link)+7, 4), "&", 1)
				 INNER JOIN jos_emundus_setup_profiles AS profile ON profile.menutype = menu.menutype
			WHERE tab.published = 1 AND profile.id =9 AND tab.created_by_alias = "form" AND element.published=1 AND element.hidden=0 AND element.label!=" " AND element.label!=""
			ORDER BY menu.ordering, formgroup.ordering, element.ordering';
		$this->db->setQuery($query);

		//die(print_r($db->loadObjectList('id')));
		return $this->db->loadObjectList('id');
	}

	function getTotal()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_total)) {
			$query        = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination($this->getTotal(), $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_pagination;
	}

	function getSchoolyears()
	{
		$query = $this->db->getQuery(true);

		$query->select('DISTINCT(schoolyear) as schoolyear')
			->from($this->db->quoteName('#__emundus_users'))
			->where($this->db->quoteName('schoolyear') . ' IS NOT NULL AND ' . $this->db->quoteName('schoolyear') . ' != ""')
			->order('schoolyear');
		$this->db->setQuery($query);

		return $this->db->loadAssocList();
	}

	/**
	 * @param   array  $programmes  the programme newly added that should be affected to groups
	 *
	 * @return boolean
	 * Add new groups
	 */
	public function addGroupsByProgrammes($programmes)
	{

		if (count($programmes) > 0) {

			$query = $this->db->getQuery(true);

			try {
				foreach ($programmes as $v) {
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_groups'))
						->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('%'.$v['organisation'].'%'))
						->orWhere($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('%'.$v['organisation_code'].'%'))
						->orWhere($this->db->quoteName('description') . ' LIKE ' . $this->db->quote('%'.$v['organisation_code'].'%'));
					$this->db->setQuery($query);
					$groups = $this->db->loadObjectList();

					if (count($groups) > 0) {
						foreach ($groups as $group) {
							$query->clear()
								->delete($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
								->where($this->db->quoteName('parent_id') . ' = ' . $group->id)
								->where($this->db->quoteName('course') . ' LIKE ' . $this->db->quote($v['code']));
							$this->db->setQuery($query);
							$this->db->execute();

							$query->clear()
								->insert($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
								->columns($this->db->quoteName(array('parent_id', 'course')))
								->values($this->db->quote($group->id) . ',' . $this->db->quote($v['code']));
							$this->db->setQuery($query);
							$this->db->execute();
						}
					}
					else {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_setup_groups'))
							->columns($this->db->quoteName(array('label', 'description', 'published')))
							->values($this->db->quote($v['organisation'] . ' [' . $v['organisation_code'] . ']') . ',' . $this->db->quote($v['organisation_code']) . ',1');
						$this->db->setQuery($query);
						$this->db->execute();
						$lastid = $this->db->insertid();

						$query->clear()
							->insert($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
							->columns($this->db->quoteName(array('parent_id', 'course')))
							->values($this->db->quote($lastid) . ',' . $this->db->quote($v['code']));
						$this->db->setQuery($query);
						$this->db->execute();

						// define default access right for group
						$params             = ComponentHelper::getParams('com_emundus');
						$default_actions    = $params->get('default_actions', 0);
						$actions_evaluators = json_decode($default_actions);

						$values = array();
						foreach ($actions_evaluators as $action) {
							$values[] = $lastid . ', ' . implode(',', (array) $action);
						}

						$query->clear()
							->insert($this->db->quoteName('#__emundus_acl'))
							->columns($this->db->quoteName(array('group_id', 'action_id', 'c', 'r', 'u', 'd')))
							->values($values);
						$this->db->setQuery($query);
						$this->db->execute();
					}

					$query->clear()
						->insert($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
						->columns($this->db->quoteName(array('parent_id', 'course')))
						->values('1,' . $this->db->quote($v['code']));
					$this->db->setQuery($query);
					$this->db->execute();

				}
			}
			catch (Exception $e) {
				JLog::add($e->getMessage(), JLog::ERROR, 'com_emundus');

				return $e->getMessage();
			}

		}
		else {
			return Text::_('NO_GROUP_TO_ADD');
		}

		return true;
	}


	/**
	 * @param $group_ids
	 *
	 * @return array|bool
	 *
	 * @since version
	 */
	public function getFabrikGroupsAssignedToEmundusGroups($group_ids)
	{

		if (!is_array($group_ids)) {
			$group_ids = [$group_ids];
		}

		$query = $this->db->getQuery(true);

		$result = [];
		foreach ($group_ids as $group_id) {
			$query
				->clear()
				->select($this->db->quoteName('fabrik_group_link'))
				->from($this->db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
				->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($group_id));
			$this->db->setQuery($query);

			try {
				$f_groups = $this->db->loadColumn();

				// In the case of a group having no assigned Fabrik groups, it can get them all.
				if (empty($f_groups)) {
					return true;
				}

				$result = array_merge($result, $f_groups);
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
}
