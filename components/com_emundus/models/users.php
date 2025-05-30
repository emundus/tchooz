<?php
/**
 * Created by eMundus.
 * User: brivalland
 * Date: 23/05/14
 * Time: 11:39
 * @package        Joomla
 * @subpackage     eMundus
 * @link           http://www.emundus.fr
 * @copyright      Copyright (C) 2016 eMundus. All rights reserved.
 * @license        GNU/GPL
 * @author         Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'fabrik.php');
require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Site\Model\ResetModel;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use \Joomla\CMS\User\User;

/**
 * Emundus Component Users Model
 *
 * @since  1.0.0
 */
class EmundusModelUsers extends ListModel
{
	/**
	 * @var   int  The total number of items
	 * @since version 1.0.0
	 */
	private $_total;

	/**
	 * @var  object  The pagination object
	 * @since version 1.0.0
	 */
	private $_pagination;

	/**
	 * @var object  The data
	 * @since version 1.0.0
	 */
	protected $data;

	/**
	 * @var JDatabaseDriver|\Joomla\Database\DatabaseDriver|mixed|null
	 * @since version 1.0.0
	 */
	private $db;

	/**
	 * @var \Joomla\CMS\Application\CMSApplication|\Joomla\CMS\Application\CMSApplicationInterface|null
	 * @since version 1.0.0
	 */
	private $app;

	/**
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $user;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->app = Factory::getApplication();
		$this->db   = $this->getDatabase();
		$this->user = $this->app->getIdentity();
		$session    = $this->app->getSession();

		if (!$session->has('filter_order')) {
			$session->set('filter_order', 'id');
			$session->set('filter_order_Dir', 'desc');
		}

		if (!$session->has('limit')) {
			$limit      = $this->app->get('list_limit');
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
	}

	public function _buildContentOrderBy()
	{
		$session          = $this->app->getSession();
		$params           = $session->get('filt_params');
		$filter_order     = $params['filter_order'];
		$filter_order_Dir = $params['filter_order_Dir'];

		$can_be_ordering = array('user', 'id', 'lastname', 'firstname', 'username', 'email', 'profile', 'block', 'lastvisitDate', 'registerDate', 'newsletter', 'groupe', 'university');

		if (!empty($filter_order) && !empty($filter_order_Dir) && in_array($filter_order, $can_be_ordering))
			$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;
		else
			$orderby = ' ORDER BY u.id DESC';

		return $orderby;
	}

	public function _buildQuery()
	{
		$session = $this->app->getSession();
		$params  = $session->get('filt_params');
		

		$final_grade  = $params['finalgrade'];
		$search       = $params['s'];
		$programme    = $params['programme'];
		$campaigns    = $params['campaign'];
		$schoolyears  = $params['schoolyears'];
		$groupEval    = $params['evaluator_group'];
		$spam_suspect = $params['spam_suspect'];
		$profile      = $params['profile'];
		$oprofiles    = $params['o_profiles'];
		$newsletter   = $params['newsletter'];
		$group        = $params['group'];
		$jgroup       = $params['joomla_group'];
		$institution  = $params['institution'];

		$uid       = $this->app->input->get('rowid', null, 'GET', 'none', 0);
		$edit      = $this->app->input->get('edit', 0, 'GET', 'none', 0);
		$list_user = "";

		if (!empty($schoolyears) && (empty($campaigns) || $campaigns[0] == '%') && $schoolyears[0] != '%') {
			$list_user             = "";
			$applicant_schoolyears = $this->getUserListWithSchoolyear($schoolyears);
			$i                     = 0;
			$nb_element            = count($applicant_schoolyears);
			if ($nb_element == 0) {
				$list_user .= "EMPTY";
			}
			else {
				foreach ($applicant_schoolyears as $applicant) {
					if (++$i === $nb_element)
						$list_user .= $applicant;
					elseif ($applicant != null)
						$list_user .= $applicant . ", ";
				}
			}
		}
		elseif (!empty($campaigns) && $campaigns[0] != '%' && (empty($schoolyears) || $schoolyears[0] == '%')) {

			$list_user           = "";
			$applicant_campaigns = $this->getUserListWithCampaign($campaigns);
			$i                   = 0;
			$nb_element          = count($applicant_campaigns);
			if ($nb_element == 0) {
				$list_user .= "EMPTY";
			}
			else {
				foreach ($applicant_campaigns as $applicant) {
					if (++$i === $nb_element)
						$list_user .= $applicant;
					else if ($applicant != null)
						$list_user .= $applicant . ", ";
				}
			}
		}
		elseif (!empty($campaigns) && $campaigns[0] != '%' && !empty($schoolyears) && $schoolyears[0] != '%') {
			$list_user = '';
			foreach ($schoolyears as $schoolyear) {
				foreach ($campaigns as $campaign) {
					$compare = $this->compareCampaignANDSchoolyear($campaign, $schoolyear);
					if ($compare != 0) {
						$applicant_campaigns = $this->getUserListWithCampaign($campaign);
						foreach ($applicant_campaigns as $applicant) {
							$list_user .= $applicant . ", ";
						}
					}
				}
			}
			if ($list_user == '') {
				$list_user = 'EMPTY';
			}
			else {
				$taille    = strlen($list_user);
				$list_user = substr($list_user, 0, $taille - 2);
			}
		}

		if (!empty($groupEval)) {
			$list_user           = "";
			$applicant_groupEval = $this->getUserListWithGroupsEval($groupEval);
			$i                   = 0;
			$nb_element          = count($applicant_groupEval);
			if ($nb_element == 0) {
				$list_user .= "EMPTY";
			}
			else {
				foreach ($applicant_groupEval as $applicant) {
					if (++$i === $nb_element)
						$list_user .= $applicant;
					elseif ($applicant != null)
						$list_user .= $applicant . ", ";
				}
			}
		}

		$eMConfig         = ComponentHelper::getParams('com_emundus');
		$showUniversities = $eMConfig->get('showUniversities');
		$showJoomlagroups = $eMConfig->get('showJoomlagroups', 0);
		$showNewsletter   = $eMConfig->get('showNewsletter');

        $query = 'SELECT DISTINCT(u.id), e.lastname, e.firstname, u.email, u.username,  espr.label as profile,group_concat(DISTINCT eup.profile_id) as o_profiles, espr.published as is_applicant_profile, ';

		if ($showNewsletter == 1)
			$query .= 'up.profile_value as newsletter, ';

		$query .= 'u.registerDate, u.lastvisitDate,  GROUP_CONCAT( DISTINCT esgr.label SEPARATOR "<br>") as groupe, ';

		if ($showUniversities == 1) {
			$query .= 'cat.title as university,';
		}
		if ($showJoomlagroups == 1) {
			$query .= 'GROUP_CONCAT( DISTINCT usg.title SEPARATOR "<br>") as joomla_groupe,';
		}

		$query .= 'u.activation as active,u.block as block,mfa.method as mfa_method
                    FROM #__users AS u
                    LEFT JOIN #__emundus_users AS e ON u.id = e.user_id
                    LEFT JOIN #__emundus_users_profiles AS eup ON e.user_id = eup.user_id and eup.profile_id != e.profile
                    LEFT JOIN #__emundus_groups AS egr ON egr.user_id = u.id
                    LEFT JOIN #__emundus_setup_groups AS esgr ON esgr.id = egr.group_id
                    LEFT JOIN #__emundus_setup_profiles AS espr ON espr.id = e.profile
                    LEFT JOIN #__emundus_personal_detail AS epd ON u.id = epd.user
                    LEFT JOIN #__categories AS cat ON cat.id = e.university_id
                    LEFT JOIN #__user_profiles AS up ON ( u.id = up.user_id AND up.profile_key like "emundus_profiles.newsletter")
                    LEFT JOIN #__user_mfa as mfa ON mfa.user_id = u.id AND mfa.default = 1';
		if ($showJoomlagroups == 1) {
			$query .= 'LEFT JOIN #__user_usergroup_map AS um ON ( u.id = um.user_id AND um.group_id != 2)
                    LEFT JOIN jos_usergroups AS usg ON ( um.group_id = usg.id)';
		}

		if (!empty($programme) && $programme[0] != '%') {
			$query .= ' LEFT JOIN #__emundus_campaign_candidature AS ecc ON u.id = ecc.applicant_id
                        LEFT JOIN #__emundus_setup_campaigns as esc ON ecc.campaign_id=esc.id ';
		}

		if (!empty($final_grade)) {
			$query .= 'LEFT JOIN #__emundus_final_grade AS efg ON u.id = efg.student_id ';
		}

		// Exclude sysadmin users
		$automated_task_user = $eMConfig->get('automated_task_user',0);
		$display_sysadmin_users = $eMConfig->get('display_sysadmin_users',0);

		if(!$display_sysadmin_users)
		{
			$exclude_users_query = $this->db->getQuery(true);
			$exclude_users_query->select('eu.user_id')
				->from($this->db->quoteName('#__emundus_users', 'eu'))
				->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'eup') . ' ON ' . $this->db->quoteName('eup.user_id') . ' = ' . $this->db->quoteName('eu.user_id'))
				->where('eup.profile_id = 1')
				->orWhere('eu.profile = 1');
			$this->db->setQuery($exclude_users_query);
			$exclude_users = $this->db->loadColumn();
			if (!empty($automated_task_user))
			{
				$exclude_users[] = $automated_task_user;
			}
			$query .= ' where 1=1 AND u.id NOT IN (' . implode(',', $exclude_users) . ') ';
		} else {
			$query .= ' where 1=1 ';
		}
		//

		if (!empty($programme) && $programme[0] != '%') {
			$query .= ' AND ( esc.training IN ("' . implode('","', $programme) . '")
                            OR u.id IN (
                                select _eg.user_id
                                from #__emundus_groups as _eg
                                left join #__emundus_setup_groups_repeat_course as _esgr on _esgr.parent_id=_eg.group_id
                                where _esgr.course IN ("' . implode('","', $programme) . '")
                                )
                            )';
		}

		if (!empty($group) && $group[0] != '%')
			$query .= ' AND u.id IN( SELECT jeg.user_id FROM #__emundus_groups as jeg WHERE jeg.group_id IN (' . implode(',', $group) . ')) ';

		if (isset($jgroup) && !empty($jgroup) && $jgroup[0] != '%')
			$query .= ' AND u.id IN (SELECT juum.user_id FROM #__user_usergroup_map as juum WHERE juum.group_id IN ('.implode(',', $jgroup).')) ';

		if (!empty($institution) && $institution[0] != '%')
			$query .= ' AND u.id IN( SELECT jeu.user_id FROM #__emundus_users as jeu WHERE jeu.university_id IN (' . implode(',', $institution) . ')) ';

		if ($edit == 1) {
			$query .= ' u.id=' . (int) $uid;
		}
		else {
			$and = true;

			if (!empty($profile)) {
				if (is_numeric($profile)) {
					$query.= ' AND e.profile = '.$profile;
				} else if ($profile === 'applicant') {
					$query.= ' AND espr.published = 1';
				}
			}
			if (!empty($oprofiles)) {
				if (in_array('applicant', $oprofiles)) {
					$query.= 'AND (eup.profile_id IN ("'.implode('","', $oprofiles).'") OR espr.published = 1)';

				} else {
					$query.= ' AND eup.profile_id IN ("'.implode('","', $oprofiles).'")';
				}

				$and   = true;
			}
			if (!empty($final_grade)) {
				if ($and) $query .= ' AND ';
				else {
					$query .= 'WHERE ';
				}

				$query .= 'efg.Final_grade = "' . $final_grade . '"';
				$and   = true;
			}
			if (!empty($search)) {
				$q = '';
				foreach ($search as $str) {
					if (strpos($str, ':') !== false) {
						$val = explode(': ', $str);

						if ($val[0] == "ALL") {
							$q .= ' OR e.lastname LIKE ' . $this->db->Quote('%' . $val[1] . '%') . '
                        OR e.firstname LIKE ' . $this->db->Quote('%' . $val[1] . '%') . '
                        OR u.email LIKE ' . $this->db->Quote('%' . $val[1] . '%') . '
                        OR e.schoolyear LIKE ' . $this->db->Quote('%' . $val[1] . '%') . '
                        OR u.username LIKE ' . $this->db->Quote('%' . $val[1] . '%') . '
                        OR u.id = ' . $this->db->Quote($val[1]);
						}

						if ($val[0] == "ID")
							$q .= ' OR u.id = ' . $this->db->Quote($val[1]);

						if ($val[0] == "EMAIL")
							$q .= ' OR u.email LIKE ' . $this->db->Quote('%' . $val[1] . '%');

						if ($val[0] == "USERNAME")
							$q .= ' OR u.username LIKE ' . $this->db->Quote('%' . $val[1] . '%');

						if ($val[0] == "LAST_NAME")
							$q .= ' OR e.lastname LIKE ' . $this->db->Quote('%' . $val[1] . '%');

						if ($val[0] == "FIRST_NAME")
							$q .= ' OR e.firstname LIKE ' . $this->db->Quote('%' . $val[1] . '%');

					} else {
						$q .= ' OR e.lastname LIKE ' . $this->db->Quote('%' . $str . '%') . '
						OR e.firstname LIKE ' . $this->db->Quote('%' . $str . '%') . '
						OR u.email LIKE ' . $this->db->Quote('%' . $str . '%') . '
						OR e.schoolyear LIKE ' . $this->db->Quote('%' . $str . '%') . '
						OR u.username LIKE ' . $this->db->Quote('%' . $str . '%') . '
						OR u.id = ' . $this->db->Quote($str);
					}
				}

				if (!empty($q)) {
					if ($and) {
						$query .= ' AND ';
					}
					else {
						$and   = true;
						$query .= ' ';
					}

					$q     = substr($q, 3);
					$query .= '(' . $q . ')';
				}
			}
			if (!empty($spam_suspect) && $spam_suspect == 1) {
				if ($and) {
					$query .= ' AND ';
				}
				else {
					$and   = true;
					$query .= ' ';
				}

				$query .= 'u.lastvisitDate="0000-00-00 00:00:00" AND TO_DAYS(NOW()) - TO_DAYS(u.registerDate) > 7';
			}

			if (!empty($list_user)) {
				if ($and) {
					$query .= ' AND ';
				}
				else {
					$and   = true;
					$query .= ' ';
				}

				if ($list_user == 'EMPTY')
					$query .= 'u.id IN (null) ';
				else
					$query .= 'u.id IN ( ' . $list_user . ' )';
			}

			if (!empty($newsletter)) {
				if ($and) {
					$query .= ' AND ';
				}
				else {
					$query .= ' ';
				}

				$query .= 'profile_value like "%' . $newsletter . '%"';
			}
		}

		$query .= " GROUP BY u.id ";

		return $query;
	}

	public function getUsers($limit_start = null, $limit = null)
	{
		$session = $this->app->getSession();

		if ($limit_start === null) {
			$limit_start = $session->get('limitstart');
		}
		if ($limit === null) {
			$limit = $session->get('limit');
		}

		// Lets load the data if it doesn't already exist
		try {

			$query = $this->_buildQuery();
			$query .= $this->_buildContentOrderBy();

			return $this->_getList($query, $limit_start, $limit);
		}
		catch (Exception $e) {
			throw new $e;
		}
	}

	public function getProfiles()
	{
		$query = $this->db->getQuery(true);

		$query->select('esp.id, esp.label, esp.acl_aro_groups, esp.published, caag.lft')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'esp'))
			->innerJoin($this->db->quoteName('#__usergroups', 'caag') . ' on esp.acl_aro_groups=caag.id')
			->where('esp.status=1 AND esp.id > 1')
			->order('esp.acl_aro_groups, esp.label');
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	/**
	 * @param $ids
	 *
	 * @return mixed
	 */
	public function getProfilesByIDs($ids)
	{
		
		$query = 'SELECT esp.id, esp.label, esp.acl_aro_groups, esp.published, caag.lft
        FROM #__emundus_setup_profiles esp
        INNER JOIN #__usergroups caag on esp.acl_aro_groups=caag.id
        WHERE esp.status=1 AND esp.id IN (' . implode(',', $ids) . ')
        ORDER BY caag.lft, esp.label';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	public function getEditProfiles()
	{
		
		$current_user  = JFactory::getUser();
		$current_group = 0;
		foreach ($current_user->groups as $group) {
			if ($group > $current_group) $current_group = $group;
		}
		$query = 'SELECT id, label FROM #__emundus_setup_profiles WHERE ' . $current_group . ' >= acl_aro_groups GROUP BY id';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	public function getApplicantProfiles()
	{
		
		$query = 'SELECT * FROM #__emundus_setup_profiles WHERE published=1';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getUsersProfiles()
	{
		$user = JFactory::getUser();
		$uid  = $this->app->input->get('rowid', $user->id, 'get', 'int');

		$query = 'SELECT eup.profile_id FROM #__emundus_users_profiles eup WHERE eup.user_id=' . $uid;
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getUserByEmail($email)
	{

		$query = 'SELECT * FROM #__users WHERE email like "' . $email . '"';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getEmundusUserByEmail($email)
	{

		$query = 'SELECT * FROM #__emundus_users WHERE email like "' . $email . '"';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getProfileIDByCampaignID($cid)
	{
		$query = $this->db->getQuery(true);

		$query->select('profile_id')
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($cid));
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	public function getCurrentUserProfile($uid)
	{

		$query = 'SELECT eu.profile FROM #__emundus_users eu WHERE eu.user_id=' . $uid;
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	function getProfileDetails($profile_id)
	{
		$profile_info = null;

		if (!empty($profile_id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$h_cache = new EmundusHelperCache();
			$profile_info = $h_cache->get('profile_details_'.$profile_id);

			if (empty($profile_info)) {
				$query = $this->_db->getQuery(true);

				$query->select('id,label,description,class,published,display_description')
					->from($this->_db->quoteName('#__emundus_setup_profiles'))
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($profile_id));

				try {
					$this->_db->setQuery($query);
					$profile_info = $this->_db->loadObject();
					$h_cache->set('profile_details_'.$profile_id, $profile_info);
				} catch (Exception $e){
					Log::add('component/com_emundus/models/users | Error when try to get profile details : ' . preg_replace("/[\r\n]/"," ",$query->__toString().' -> '.$e->getMessage()), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $profile_info;
	}

	public function changeCurrentUserProfile($uid, $pid)
	{
		
		$query = 'UPDATE #__emundus_users SET profile ="' . (int) $pid . '" WHERE user_id=' . (int) $uid;
		$this->db->setQuery($query);
		$this->db->execute() or die($this->db->getErrorMsg());
	}

	public function getUniversities()
	{
		
		$query = 'SELECT c.id, c.title
        FROM #__categories as c
        WHERE c.published=1 AND c.extension like "com_contact"
        order by note desc,lft asc';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	public function getGroups()
	{

		$query = 'SELECT esg.id, esg.label
        FROM #__emundus_setup_groups esg
        WHERE esg.published=1
        ORDER BY esg.label';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('id');
	}

	public function getUsersIntranetGroups($uid, $return = 'AssocList')
	{
		try {
			$query = "SELECT ug.id, ug.title
                      from #__usergroups as ug
                      left join #__user_usergroup_map as um on um.group_id = ug.id
                      where um.user_id = " . $uid;

			$this->db->setQuery($query);
			if ($return == 'Column') {
				return $this->db->loadColumn();
			}
			else {
				return $this->db->loadAssocList('id', 'label');
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function getLascalaIntranetGroups($uid = null)
	{


		$query = 'SELECT esg.group_id, esg.category_label
        FROM #__emundus_intranet_categories esg 
        WHERE esg.published=1 
        ORDER BY esg.category_label';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('group_id');
	}

	public function getCampaigns()
	{

		$query = 'SELECT sc.id, cc.applicant_id, sc.start_date, sc.end_date, sc.label, sc.year
        FROM #__emundus_setup_campaigns AS sc
        LEFT JOIN #__emundus_campaign_candidature AS cc ON cc.campaign_id = sc.id
        WHERE sc.published=1';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getCampaignsPublished()
	{

		$query = 'SELECT * FROM #__emundus_setup_campaigns AS sc WHERE sc.published=1 ORDER BY sc.start_date DESC, sc.label ASC';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getAllCampaigns()
	{
		$campaigns = [];


		$query = $this->db->getQuery(true);

		$query->select('sc.*, esp.label as programme, sc.id as campaign_id')
			->from($this->db->quoteName('#__emundus_setup_campaigns', 'sc'))
			->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON sc.training = esp.code')
			->order('sc.start_date DESC')
			->order('sc.label ASC');

		try {
			$this->db->setQuery($query);
			$campaigns = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Failed to list all campaigns ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $campaigns;
	}

	public function getCampaignsCandidature($aid = 0)
	{

		$uid   = ($aid != 0) ? $aid : $this->app->input->get('rowid', null, 'GET', 'none', 0);
		$query = 'SELECT * FROM #__emundus_campaign_candidature AS cc  WHERE applicant_id=' . $uid;
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getUserListWithSchoolyear($schoolyears)
	{
		$year = is_string($schoolyears) ? $schoolyears : "'" . implode("','", $schoolyears) . "'";

		$query = 'SELECT cc.applicant_id
        FROM #__emundus_campaign_candidature AS cc
        LEFT JOIN #__emundus_setup_campaigns AS sc ON cc.campaign_id = sc.id
        WHERE sc.published=1 AND sc.year IN (' . $year . ') ORDER BY sc.year DESC';
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getUserListWithCampaign($campaign)
	{


		if (!is_array($campaign)) {
			$query = 'SELECT cc.applicant_id
            FROM #__emundus_campaign_candidature AS cc
            LEFT JOIN #__emundus_setup_campaigns AS sc ON cc.campaign_id = sc.id
            WHERE sc.published=1 AND sc.id IN (' . $campaign . ')';
		}
		else {
			$query = 'SELECT cc.applicant_id
            FROM #__emundus_campaign_candidature AS cc
            LEFT JOIN #__emundus_setup_campaigns AS sc ON cc.campaign_id = sc.id
            WHERE sc.published=1 AND sc.id IN (' . implode(",", $campaign) . ')';
		}
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function compareCampaignANDSchoolyear($campaign, $schoolyear)
	{

		$query = 'SELECT COUNT(*)
        FROM #__emundus_setup_campaigns AS sc
        WHERE id=' . $campaign . ' AND year="' . $schoolyear . '"';
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	public function getCurrentCampaign()
	{
		return EmundusHelperFilters::getCurrentCampaign();
	}

	public function getCurrentCampaignsID()
	{
		return EmundusHelperFilters::getCurrentCampaignsID();
	}

	public function getCurrentCampaigns()
	{
		$config = JFactory::getConfig();

		$timezone = new DateTimeZone($config->get('offset'));
		$now      = JFactory::getDate()->setTimezone($timezone);


		$query = 'SELECT sc.id, sc;label
        FROM #__emundus_setup_campaigns AS sc
        WHERE sc.published=1 AND end_date > "' . $now . '"';
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getProgramme()
	{
		try {

			$query = 'SELECT sp.code, sp.label FROM #__emundus_setup_programmes AS sp ORDER BY sp.label ASC';
			$this->db->setQuery($query);

			return $this->db->loadAssocList();

		}
		catch (Exception $e) {
			return null;
		}
	}

	public function getNewsletter()
	{

		$query = 'SELECT user_id, profile_value
        FROM #__user_profiles
        WHERE profile_key = "emundus_profile.newsletter"';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getGroupEval()
	{

		$query = 'SELECT esg.id, eu.user_id, eu.firstname, eu.lastname, u.email, esg.label
                FROM #__emundus_setup_groups as esg
                LEFT JOIN #__emundus_groups as eg on esg.id=eg.group_id
                LEFT JOIN #__emundus_users as eu on eu.user_id=eg.user_id
                LEFT JOIN #__users as u on u.id=eu.user_id
                WHERE esg.published=1';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getGroupsEval()
	{

		$query = 'SELECT ege.id, ege.applicant_id, ege.user_id, ege.group_id, esg.label
        FROM #__emundus_groups_eval as ege
        LEFT JOIN #__emundus_setup_groups as esg ON esg.id = ege.group_id
        WHERE esg.published=1';
		$this->db->setQuery($query);

		return $this->db->loadObjectList('applicant_id');
	}

	public function getUserListWithGroupsEval($groups)
	{

		$query = 'SELECT eg.user_id
        FROM #__emundus_groups as eg
        LEFT JOIN #__emundus_setup_groups as esg ON esg.id=eg.group_id
        WHERE esg.published=1 AND eg.group_id=' . $groups;
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getUsersGroups()
	{

		$query = 'SELECT eg.user_id, eg.group_id
        FROM #__emundus_groups eg';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getSchoolyears()
	{

		$query = 'SELECT year as schoolyear FROM #__emundus_setup_campaigns WHERE published=1 GROUP BY schoolyear';
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getTotal()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_total)) {
			$query        = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	public function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$session           = JFactory::getSession();
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
	 * Method to get the registration form.
	 *
	 * The base form is loaded from XML and then an event is fired
	 * for users plugins to extend the form with extra fields.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|false   A JForm object on success, false on failure
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = JForm::getInstance('com_emundus.registration', JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'forms' . DS . 'registration.xml', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed   The data for the form.
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		return $this->getData();
	}

	/**
	 * Method to get the registration form data.
	 *
	 * The base form data is loaded and then an event is fired
	 * for users plugins to extend the data.
	 *
	 * @return  mixed       Data object on success, false on failure.
	 * @since   1.6
	 */
	public function getData()
	{
		if ($this->data === null) {

			$this->data = new stdClass();
			$app        = $this->app;
			$params     = JComponentHelper::getParams('com_users');

			// Override the base user data with any data in the session.
			$temp = (array) $app->getUserState('com_users.registration.data', array());
			foreach ($temp as $k => $v) {
				$this->data->$k = $v;
			}

			// Get the groups the user should be added to after registration.
			$this->data->groups = array();

			// Get the default new user group, Registered if not specified.
			$system = $params->get('new_usertype', 2);

			$this->data->groups[] = $system;

			// Unset the passwords.
			unset($this->data->password1);
			unset($this->data->password2);

			// Get the dispatcher and load the users plugins.
			JPluginHelper::importPlugin('user');

			// Trigger the data preparation event.
			$results = $this->app->triggerEvent('onContentPrepareData', array('com_users.registration', $this->data));

			// Check for errors encountered while preparing the data.
			if (count($results) && in_array(false, $results, true)) {
				$this->data = false;
			}
		}

		return $this->data;
	}

	/** Adds a user to Joomla as well as the eMundus tables.
	 *
	 * @param $user
	 * @param $other_params
	 *
	 * @return int user_id, 0 if failed
	 */
	public function adduser($user, $other_params, $testing_account = 0)
	{
		$new_user_id = 0;

		try {
			if (!$user->save()) {
				$this->app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_CAN_NOT_SAVE_USER') . '<BR />' . $user->getError(), 'error');
				Log::add('Failed to create user ' . $user->getError(), Log::ERROR, 'com_emundus.error');
			}
			else {
				$query = $this->db->getQuery(true);

				$query->clear()
					->update('#__users')
					->set('block = 0');

				if($testing_account == 1) {
					$query_params = $this->db->getQuery(true);
					$query_params->clear()
						->select('params')
						->from('#__users')
						->where('id = ' . $user->id);
					$this->db->setQuery($query_params);
					$params = $this->db->loadResult();
					$params = json_decode($params, true);
					$params['testing_account'] = 1;

					$query->set('params = ' . $this->db->quote(json_encode($params)));
				}

				$query->where($this->db->quoteName('id') . ' = ' . $user->id);
				$this->db->setQuery($query);
				$this->db->execute();

				$this->addEmundusUser($user->id, $other_params);
				$new_user_id = $user->id;
			}
		}
		catch (Exception $e) {
			$this->app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_CAN_NOT_SAVE_USER') . '<br />' . $e->getMessage(), 'error');
			Log::add('Failed to create user : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$new_user_id = 0;
		}

		return $new_user_id;
	}

	public function addEmundusUser($user_id, $params)
	{

		try {
			if (version_compare(JVERSION, '4.0', '>')) {
				$config = $this->app->getConfig();
			}
			else {
				$config = Factory::getConfig();
			}
			
			$config_offset = $config->get('offset');
			$offset        = $config_offset ?: 'Europe/Paris';
			$timezone      = new DateTimeZone($offset);
			$now           = Factory::getDate()->setTimezone($timezone);

			PluginHelper::importPlugin('emundus');

			$firstname = $params['firstname'];
			$lastname  = $params['lastname'];
			$profile   = $params['profile'];
			$oprofiles = $params['em_oprofiles'];
			$groups    = $params['em_groups'];
			$campaigns = $params['em_campaigns'];
			$news      = $params['news'];
			$univ_id   = $params['univ_id'];

			if (!empty($params['id_ehesp'])) {
				$id_ehesp = $params['id_ehesp'];
			}

			$this->app->triggerEvent('onBeforeSaveEmundusUser', [$user_id, $params]);
			$this->app->triggerEvent('onCallEventHandler', ['onBeforeSaveEmundusUser', ['user_id' => $user_id, 'params' => $params]]);

			$query = $this->db->getQuery(true);

			$columns = array('user_id', 'registerDate', 'firstname', 'lastname', 'profile', 'schoolyear', 'disabled', 'disabled_date');
			$values  = array($user_id, $this->db->quote($now), $this->db->quote($firstname), $this->db->quote($lastname), $profile, $this->db->quote(''), 0, $this->db->quote('0000-00-00 00:00:00'));

			if (!empty($id_ehesp)) {
				$columns[] = 'id_ehesp';
				$values[]  = $id_ehesp;
			}
			if (!empty($univ_id)) {
				$columns[] = 'university_id';
				$values[]  = $univ_id;
			}

			$query->insert($this->db->quoteName('#__emundus_users'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $values));
			$this->db->setQuery($query);
			$this->db->execute();

			$this->app->triggerEvent('onAfterSaveEmundusUser', [$user_id, $params]);
			$this->app->triggerEvent('onCallEventHandler', ['onAfterSaveEmundusUser', ['user_id' => $user_id, 'params' => $params]]);


			if (!empty($groups)) {
				foreach ($groups as $group) {
					$this->app->triggerEvent('onBeforeAddUserToGroup', [$user_id, $group]);
					$this->app->triggerEvent('onCallEventHandler', ['onBeforeAddUserToGroup', ['user_id' => $user_id, 'group' => $group]]);

					$query->clear()
						->insert($this->db->quoteName('#__emundus_groups'))
						->columns($this->db->quoteName(array('user_id', 'group_id')))
						->values($user_id . ',' . $group);
					$this->db->setQuery($query);
					$this->db->execute();

					$this->app->triggerEvent('onAfterAddUserToGroup', [$user_id, $group]);
					$this->app->triggerEvent('onCallEventHandler', ['onAfterAddUserToGroup', ['user_id' => $user_id, 'group' => $group]]);
				}
			}

			if (!empty($campaigns) && is_array($campaigns)) {
				foreach ($campaigns as $campaign) {
					$this->app->triggerEvent('onBeforeCampaignCandidature', [$user_id, $this->user->id, $campaign]);
					$this->app->triggerEvent('onCallEventHandler', ['onBeforeCampaignCandidature', ['user_id' => $user_id, 'connected' => $this->user->id, 'campaign' => $campaign]]);

					$fnum = EmundusHelperFiles::createFnum($campaign, $user_id);

					if(!empty($fnum)) {
						$columns = array('applicant_id', 'user_id', 'campaign_id', 'fnum');
						$values  = array($user_id, $this->user->id, $campaign, $fnum);
						$query->clear()
							->insert($this->db->quoteName('#__emundus_campaign_candidature'))
							->columns($this->db->quoteName($columns))
							->values(implode(',', $this->db->quote($values)));
						$this->db->setQuery($query);
						$this->db->execute();

						$this->app->triggerEvent('onAfterCampaignCandidature', [$user_id, $this->user->id, $campaign]);
						$this->app->triggerEvent('onCallEventHandler', ['onAfterCampaignCandidature', ['user_id' => $user_id, 'connected' => $this->user->id, 'campaign' => $campaign]]);
					}
				}
			}

			$this->app->triggerEvent('onBeforeAddUserProfile', [$user_id, $profile]);
			$this->app->triggerEvent('onCallEventHandler', ['onBeforeAddUserProfile', ['user_id' => $user_id, 'profile' => $profile]]);

			$query->clear()
				->insert($this->db->quoteName('#__emundus_users_profiles'))
				->columns($this->db->quoteName(array('date_time', 'user_id', 'profile_id', 'start_date', 'end_date')))
				->values($this->db->quote($now) . ',' . $user_id . ',' . $profile . ',' . $this->db->quote('0000-00-00 00:00:00') . ',' . $this->db->quote('0000-00-00 00:00:00'));
			$this->db->setQuery($query);
			$this->db->execute() or die($this->db->getErrorMsg());

			$this->app->triggerEvent('onAfterAddUserProfile', [$user_id, $profile]);
			$this->app->triggerEvent('onCallEventHandler', ['onAfterAddUserProfile', ['user_id' => $user_id, 'profile' => $profile]]);

			if (!empty($oprofiles)) {
				foreach ($oprofiles as $profile) {
					$this->app->triggerEvent('onBeforeAddUserProfile', [$user_id, $profile]);
					$this->app->triggerEvent('onCallEventHandler', ['onBeforeAddUserProfile', ['user_id' => $user_id, 'profile' => $profile]]);

					$query->clear()
						->insert($this->db->quoteName('#__emundus_users_profiles'))
						->columns($this->db->quoteName(array('date_time', 'user_id', 'profile_id', 'start_date', 'end_date')))
						->values($this->db->quote($now) . ',' . $user_id . ',' . $profile . ',' . $this->db->quote('0000-00-00 00:00:00') . ',' . $this->db->quote('0000-00-00 00:00:00'));
					$this->db->setQuery($query);
					$this->db->execute();

					$this->app->triggerEvent('onAfterAddUserProfile', [$user_id, $profile]);
					$this->app->triggerEvent('onCallEventHandler', ['onAfterAddUserProfile', ['user_id' => $user_id, 'profile' => $profile]]);

					$query->clear()
						->select('acl_aro_groups')
						->from($this->db->quoteName('#__emundus_setup_profiles'))
						->where('id = ' . (int) $profile);
					$this->db->setQuery($query);
					$group = $this->db->loadColumn();

					JUserHelper::addUserToGroup($user_id, $group[0]);
				}
			}

			if ($news == 1) {
				$query->clear()
					->insert($this->db->quoteName('#__user_profiles'))
					->columns($this->db->quoteName(array('user_id', 'profile_key', 'profile_value', 'ordering')))
					->values($user_id . ',' . $this->db->quote('emundus_profile.newsletter') . ',' . $this->db->quote('1') . ',4');
				$this->db->setQuery($query);
				$this->db->execute() or die($this->db->getErrorMsg());
			}

			$query->clear()
				->select('user_id')
				->from($this->db->quoteName('#__user_profiles'))
				->where('profile_key = ' . $this->db->quote('emundus_profile.firstname') . ' AND user_id = ' . $user_id);
			$this->db->setQuery($query);
			$profile_firstname = $this->db->loadResult();

			if(empty($profile_firstname))
			{
				$query->clear()
					->insert($this->db->quoteName('#__user_profiles'))
					->columns($this->db->quoteName(array('user_id', 'profile_key', 'profile_value', 'ordering')))
					->values($user_id . ',' . $this->db->quote('emundus_profile.firstname') . ',' . $this->db->quote($firstname) . ',2');
				$this->db->setQuery($query);
				$this->db->execute() or die($this->db->getErrorMsg());
			}
			else {
				$query->clear()
					->update($this->db->quoteName('#__user_profiles'))
					->set('profile_value = ' . $this->db->quote($firstname))
					->where('profile_key = ' . $this->db->quote('emundus_profile.firstname') . ' AND user_id = ' . $user_id);
				$this->db->setQuery($query);
				$this->db->execute() or die($this->db->getErrorMsg());
			}

			$query->clear()
				->select('user_id')
				->from($this->db->quoteName('#__user_profiles'))
				->where('profile_key = ' . $this->db->quote('emundus_profile.lastname') . ' AND user_id = ' . $user_id);
			$this->db->setQuery($query);
			$profile_lastname = $this->db->loadResult();

			if(empty($profile_lastname))
			{
				$query->clear()
					->insert($this->db->quoteName('#__user_profiles'))
					->columns($this->db->quoteName(array('user_id', 'profile_key', 'profile_value', 'ordering')))
					->values($user_id . ',' . $this->db->quote('emundus_profile.lastname') . ',' . $this->db->quote($lastname) . ',1');
				$this->db->setQuery($query);
				$this->db->execute() or die($this->db->getErrorMsg());
			} else {
				$query->clear()
					->update($this->db->quoteName('#__user_profiles'))
					->set('profile_value = ' . $this->db->quote($lastname))
					->where('profile_key = ' . $this->db->quote('emundus_profile.lastname') . ' AND user_id = ' . $user_id);
				$this->db->setQuery($query);
				$this->db->execute() or die($this->db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());


			$this->app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_CAN_NOT_SAVE_USER') . '<br />' . $e->getMessage(), 'error');
			Log::add('Failed to create user : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}
	}

	public function found_usertype($acl_aro_groups)
	{

		$query = "SELECT title FROM #__usergroups WHERE id=" . $acl_aro_groups;
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	public function getDefaultGroup($pid)
	{

		$query = "SELECT acl_aro_groups FROM #__emundus_setup_profiles WHERE id=" . $pid;
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function login($uid) {
		$app     = Factory::getApplication();
		$db      = Factory::getDBO();
		$session = Factory::getSession();

		$instance   = Factory::getUser($uid);

		$instance->set('guest', 0);

		// Register the needed session variables
		$session->set('user', $instance);

		// Check to see the the session already exists.
		$app->checkSession();

		// Update the user related fields for the Joomla sessions table.
		$query = 'UPDATE #__session
                    SET guest='.$db->quote($instance->get('guest')).',
                        username = '.$db->quote($instance->get('username')).',
                        userid = '.(int) $instance->get('id').'
                        WHERE session_id like '.$db->quote($session->getId());
		$db->setQuery($query);
		$db->execute();

		// Hit the user last visit field
		$instance->setLastVisit();

		// Trigger OnUserLogin
		PluginHelper::importPlugin('user');
		PluginHelper::importPlugin('emundus');

		$options = array();
		$options['action'] = 'core.login.site';

		$response['username'] = $instance->get('username');
		$app->triggerEvent('onUserLogin', array($response, $options));
		$app->triggerEvent('onCallEventHandler', ['onUserLogin', ['user_id' => $uid]]);

		return $instance;
	}


	/**
	 *
	 * PLAIN LOGIN
	 *
	 * @param        $credentials
	 * @param   int  $redirect
	 *
	 * @return bool|JException
	 * @throws Exception
	 */
	public function plainLogin($credentials, $redirect = 1)
	{
		// Get the application object.
		$app = $this->app;

		// Get the log in credentials.
		/*   $credentials = array();
        $credentials['username'] = $this->_user;
        $credentials['password'] = $this->_passw;*/

		$options             = array();
		$options['redirect'] = $redirect;

		return $app->login($credentials, $options);

	}

	/**
	 *
	 * ENCRYPT LOGIN
	 *
	 * @param        $credentials
	 * @param   int  $redirect
	 *
	 * @throws Exception
	 */
	public function encryptLogin($credentials, $redirect = 1)
	{
		// Get the application object.
		$app = $this->app;


		$query = 'SELECT `id`, `username`, `password`'
			. ' FROM `#__users`'
			. ' WHERE username=' . $this->db->Quote($credentials['username'])
			. '   AND password=' . $this->db->Quote($credentials['password']);
		$this->db->setQuery($query);
		$result = $this->db->loadObject();

		if ($result) {
			JPluginHelper::importPlugin('user');

			$options             = array();
			$options['action']   = 'core.login.site';
			$options['redirect'] = $redirect;

			$response['username'] = $result->username;
			$app->triggerEvent('onUserLogin', array((array) $response, $options));
		}

	}

	/*
     * Function to get fnums associated to users groups or user
     * @param   $action_id  int     Allowed action ID
     * @param   $crud       array   Array of type access (create, read, update, delete)
     */
	public function getFnumsAssoc($action_id, $crud = array())
	{
		$current_user = JFactory::getUser();
		$crud_where   = '';
		foreach ($crud as $key => $value) {
			$crud_where .= ' AND ' . $key . '=' . $value;
		}

		try {

			$query = 'SELECT DISTINCT fnum
                        FROM #__emundus_group_assoc
                        WHERE action_id = ' . $action_id . ' ' . $crud_where . ' AND group_id IN (' . implode(',', $current_user->groups) . ')';
			$this->db->setQuery($query);
			$fnum_1 = $this->db->loadColumn();

			$query = 'SELECT DISTINCT fnum
                        FROM #__emundus_users_assoc
                        WHERE action_id = ' . $action_id . ' ' . $crud_where . ' AND user_id = ' . $current_user->id;
			$this->db->setQuery($query);
			$fnum_2 = $this->db->loadColumn();

			return (count($fnum_1 > 0) && count($fnum_2)) ? array_merge($fnum_1, $fnum_2) : ((count($fnum_1) > 0) ? $fnum_1 : $fnum_2);
		}
		catch (Exception $e) {
			throw $e;
		}
	}


	/*
     * Function to get fnums associated to group
     * @param   $group_id   int     Allowed action ID
     * @param   $action_id  int     Allowed action ID
     * @param   $crud       array   Array of type access (create, read, update, delete)
     */
	public function getFnumsGroupAssoc($group_id, $action_id, $crud = array())
	{
		$crud_where = '';
		foreach ($crud as $key => $value) {
			$crud_where .= ' AND ' . $key . '=' . $value;
		}

		try {

			$query = 'SELECT DISTINCT fnum
                        FROM #__emundus_group_assoc
                        WHERE action_id = ' . $action_id . ' ' . $crud_where . ' AND group_id =' . $group_id;
			$this->db->setQuery($query);
			$fnum = $this->db->loadColumn();

			return $fnum;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/*
 * Function to get fnums associated to user
 * @param   $action_id  int     Allowed action ID
 * @param   $crud       array   Array of type access (create, read, update, delete)
 */
	public function getFnumsUserAssoc($action_id, $crud = array())
	{
		$current_user = JFactory::getUser();
		$crud_where   = '';
		foreach ($crud as $key => $value) {
			$crud_where .= ' AND ' . $key . '=' . $value;
		}

		try {

			$query = 'SELECT DISTINCT fnum
                        FROM #__emundus_users_assoc
                        WHERE action_id = ' . $action_id . ' ' . $crud_where . ' AND user_id = ' . $current_user->id;
			$this->db->setQuery($query);
			$fnum = $this->db->loadColumn();

			return $fnum;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/*
     * Function to get Evaluators Infos for the mailing evaluators
     */
	public function getEvalutorByFnums($fnums)
	{
		include_once(JPATH_SITE . '/components/com_emundus/models/files.php');
		$files = new EmundusModelFiles;

		$fnums_info = $files->getFnumsInfos($fnums);

		$training = array();
		foreach ($fnums_info as $key => $value) {
			$training[] = $value['training'];
		}
		try {

			// All manually associated applicant to user with action evaluation set to create=1
			$query = 'SELECT DISTINCT u.id, u.name, u.email
                        FROM #__emundus_users_assoc eua
                        LEFT JOIN #__users u on u.id=eua.user_id
                        WHERE eua.action_id=5 AND eua.c=1 AND eua.fnum in ("' . implode('","', $fnums) . '")';

			$this->db->setQuery($query);
			$user_assoc = $this->db->loadAssocList();

			// get group with evaluation access
			$query = 'SELECT DISTINCT group_id
                        FROM #__emundus_group_assoc
                        WHERE action_id=5 AND c=1 AND fnum IN ("' . implode('","', $fnums) . '")';
			$this->db->setQuery($query);
			$groups_1 = $this->db->loadColumn();

			$groups_2 = array();
			if (count($training) > 0) {
				// get group with evaluation access
				$query = 'SELECT DISTINCT ea.group_id
                            FROM #__emundus_acl ea
                            LEFT JOIN #__emundus_setup_groups esg ON esg.id = ea.group_id
                            LEFT JOIN #__emundus_setup_groups_repeat_course esgrc ON esgrc.parent_id=esg.id
                            WHERE ea.action_id=5 AND ea.c=1 AND esgrc.course IN ("' . implode('","', $training) . '")';
				$this->db->setQuery($query);
				$groups_2 = $this->db->loadColumn();
			}

			$groups = (count($groups_1 > 0) && count($groups_2)) ? array_merge($groups_1, $groups_2) : ((count($groups_1) > 0) ? $groups_1 : $groups_2);

			$group_assoc = array();
			if (count($groups) > 0) {
				// All user from groups
				$query = 'SELECT DISTINCT u.id, u.name, u.email
                            FROM #__emundus_groups eg
                            LEFT JOIN #__users u on u.id=eg.user_id
                            WHERE eg.group_id in ("' . implode('","', $groups) . '")';

				$this->db->setQuery($query);
				$group_assoc = $this->db->loadAssocList();
			}

			return (count($user_assoc > 0) && count($group_assoc)) ? array_merge($user_assoc, $group_assoc) : ((count($user_assoc) > 0) ? $user_assoc : $group_assoc);
		}
		catch (Exception $e) {
			return $e->getMessage();
		}
	}

	public function getActions($actions = '')
	{
		//$usersGroups = JFactory::getUser()->groups;
		$usersGroups = $this->getUserGroups(JFactory::getUser()->id);

		$groups = array();
		foreach ($usersGroups as $key => $value) {
			$groups[] = $key;
		}

		$query = 'SELECT distinct act.*
                    FROM #__emundus_setup_actions as act
                    LEFT JOIN #__emundus_acl as acl on acl.action_id = act.id
                    WHERE act.status >= 1
                    AND acl.group_id in (' . implode(',', $groups) . ') AND ((acl.c = 1) OR (acl.u = 1))
                    ORDER BY act.ordering';

		try {
			$this->db->setQuery($query);

			return $this->db->loadAssocList();
		}
		catch (Exception $e) {
			return $e->getMessage();
		}
	}

	// update actions rights for a group
	public function setGroupRight($id, $action, $value)
	{

		try {
			$query = 'UPDATE `#__emundus_acl` SET `' . $action . '`=' . $value . ' WHERE `id`=' . $id;
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * @param $gname
	 * @param $gdesc
	 * @param $actions
	 * @param $progs
	 *
	 * @return bool|mixed|null
	 *
	 * @since version
	 */
	public function addGroup($gname, $gdesc, $actions, $progs, $returnGid = false)
	{

		$query = "insert into #__emundus_setup_groups (`label`,`description`, `published`) values (" . $this->db->quote($gname) . ", " . $this->db->quote($gdesc) . ", 1)";

		try {
			$this->db->setQuery($query);
			$this->db->execute();
			$gid = $this->db->insertid();
			$str = "";
		}
		catch (Exception $e) {
			Log::add('Error on adding group: ' . $e->getMessage() . ' at query -> ' . $query, Log::ERROR, 'com_emundus');

			return null;
		}

		foreach ($progs as $prog) {
			$str .= "($gid, '$prog'),";
		}
		$str   = rtrim($str, ",");
		$query = "insert into #__emundus_setup_groups_repeat_course (`parent_id`, `course`) values $str";

		try {
			$this->db->setQuery($query);
			$this->db->execute();
			$str = "";
		}
		catch (Exception $e) {
			Log::add('Error on adding group: ' . $e->getMessage() . ' at query -> ' . $query, Log::ERROR, 'com_emundus');

			return null;
		}

		if (!empty($actions)) {
			foreach ($actions as $action) {
				$act = (array) $action;
				$str .= "($gid, " . implode(',', $act) . "),";
			}
			$str   = rtrim($str, ",");
			$query = "insert into #__emundus_acl (`group_id`, `action_id`, `c`, `r`, `u`, `d`) values $str";
			$this->db->setQuery($query);

			try {
				if (!$returnGid) {
					return $this->db->execute();
				}
			}
			catch (Exception $e) {
				Log::add('Error on adding group: ' . $e->getMessage() . ' at query -> ' . $query, Log::ERROR, 'com_emundus');

				return null;
			}
		}

		require_once(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'actions.php');
		$m_actions = new EmundusModelActions();
		$m_actions->syncAllActions(false);

		return $gid;
	}

	public function changeBlock($users, $state)
	{

		try {

			foreach ($users as $uid) {
				$uid   = intval($uid);
				$query = "UPDATE #__users SET block = " . $state . " WHERE id =" . $uid;

				$this->db->setQuery($query);
				$this->db->execute();
				if ($state == 0) {
					$this->db->setQuery('UPDATE #__emundus_users SET disabled  = ' . $state . ' WHERE user_id = ' . $uid);
				}
				else {
					$this->db->setQuery('UPDATE #__emundus_users SET disabled  = ' . $state . ', disabled_date = NOW() WHERE user_id = ' . $uid);
				}

				$res = $this->db->execute();
				EmundusModelLogs::log(JFactory::getUser()->id, $uid, null, 20, 'u', 'COM_EMUNDUS_ADD_USER_UPDATE');
			}

			return $res;

		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	public function changeActivation($users, $state)
	{

		try {

			foreach ($users as $uid) {
				$uid   = intval($uid);
				$query = "UPDATE #__users SET activation = " . $state . " WHERE id =" . $uid;

				$this->db->setQuery($query);
				$res = $this->db->execute();

				EmundusModelLogs::log(JFactory::getUser()->id, $uid, null, 20, 'u', 'COM_EMUNDUS_ADD_USER_UPDATE');
			}

			return $res;

		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	/**
	 * @param         $param String The param to be saved in the user account.
	 *
	 * @param   null  $user_id
	 *
	 * @return bool
	 * @since version
	 */
	public function createParam($param, $user_id)
	{

		$user = JFactory::getUser($user_id);

		$table = JTable::getInstance('user', 'JTable');
		$table->load($user->id);

		// Check if the param exists but is false, this avoids accidetally resetting a param.
		$params = $user->getParameters();
		if (!$params->get($param, true)) {
			return true;
		}

		// Store token in User's Parameters
		$user->setParam($param, true);

		// Get the raw User Parameters
		$params = $user->getParameters();

		// Set the user table instance to include the new token.
		$table->params = $params->toString();

		// Save user data
		if (!$table->store()) {
			Log::add('Error saving params : ' . $table->getError(), Log::ERROR, 'mod_emundus.hesam');

			return false;
		}

		return true;
	}

	/**
	 * @param $users
	 *
	 * @return array
	 */
	public function getNonApplicantId($users): array
	{
		$ids = [];

		if (!empty($users)) {
			$users = !is_array($users) ? [$users] : $users;


			$query = $this->db->getQuery(true);
			$query->select('DISTINCT user_id')
				->from('#__emundus_users_profiles')
				->where('user_id IN (' . implode(',', $users) . ')')
				->where('profile_id IN (SELECT id FROM #__emundus_setup_profiles WHERE published != 1)');

			try {
				$this->db->setQuery($query);
				$ids = $this->db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add('Error on getting non-applicant users: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $ids;
	}

	public function affectToGroups($users, $groups)
	{
		$affected = 0;

		if (!empty($users) && !empty($groups)) {

			$query = $this->db->getQuery(true);

			$values = [];
			foreach ($users as $user) {
				foreach ($groups as $gid) {
					$values[] = $user['user_id'] . ", $gid";
				}
			}

			if (!empty($values)) {
				$query->insert('#__emundus_groups')
					->columns([$this->db->quoteName('user_id'), $this->db->quoteName('group_id')])
					->values($values);

				try {
					$this->db->setQuery($query);
					$affected = $this->db->execute();
				}
				catch (Exception $e) {
					Log::add('Error on affecting users to groups: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					$affected = false;
				}
			}
		}

		return $affected;
	}

	public function affectToJoomlaGroups($users, $groups)
	{
		$affected = false;

		try {
			if (!empty($users)) {
				$query = $this->db->getQuery(true);
				$str = "";
				foreach ($users as $user) {
					$query->clear()
						->select('group_id')
						->from($this->db->quoteName('#__user_usergroup_map'))
						->where($this->db->quoteName('user_id') . ' = ' . $user);
					$this->db->setQuery($query);
					$usergroups = $this->db->loadColumn();

					foreach ($groups as $gid) {
						if (!in_array($gid, $usergroups))
						{
							$str .= "($user, $gid),";
						}
					}
				}
				$str = rtrim($str, ",");

				$query = "INSERT INTO #__user_usergroup_map(`user_id`, `group_id`) values $str";
				$this->db->setQuery($query);
				$affected = $this->db->query();

			}
		}
		catch (Exception $e) {
			Log::add('Error on affecting users to Joomla groups: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $affected;
	}

	public function removeJoomlaGroups($users, $groups) {
		$removed = false;

		try
		{
			// Remove groups that are not in jos_emundus_intranet_categories
			if(!empty($users)) {
				$query = $this->db->getQuery(true);

				$query->select('group_id')
					->from($this->db->quoteName('#__emundus_intranet_categories'))
					->where('group_id IN ('.implode(',', $groups).')');
				$this->db->setQuery($query);
				$groups = $this->db->loadColumn();

				if(!empty($groups)) {
					$query = $this->db->getQuery(true);

					$query->delete($this->db->quoteName('#__user_usergroup_map'))
						->where('user_id IN ('.implode(',', $users).')')
						->where('group_id IN ('.implode(',', $groups).')');
					$this->db->setQuery($query);
					$removed = $this->db->execute();
				}
			}
		}
		catch (Exception $e)
		{
			error_log($e->getMessage(), 0);
			return false;
		}

		return $removed;
	}

	public function getUserInfos($uid)
	{
		$user_infos = [];
		$query = $this->db->getQuery(true);

		try {
			$columns = [
				'u.username as login',
				'u.email',
				'eu.firstname',
				'eu.lastname',
				'eu.profile',
				'eu.university_id',
				'up.profile_value as newsletter',
				'IF(JSON_VALID(u.params), json_extract(u.params,"$.testing_account"),0) as testing_account',
				'u.authProvider'
			];

			$query->select($columns)
				->from($this->db->quoteName('#__users', 'u'))
				->leftJoin($this->db->quoteName('#__emundus_users','eu').' ON '.$this->db->quoteName('eu.user_id').' = '.$this->db->quoteName('u.id'))
				->leftJoin($this->db->quoteName('#__user_profiles','up').' ON '.$this->db->quoteName('up.user_id').' = '.$this->db->quoteName('u.id') . ' AND ' . $this->db->quoteName('up.profile_key') . ' LIKE "emundus_profile.newsletter"')
				->where($this->db->quoteName('u.id') . ' = ' . $uid);
			$this->db->setQuery($query);
			$user_infos = $this->db->loadAssoc();
		}
		catch (Exception $e) {
			Log::add('Error getting user infos in model/users at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
		}

		return $user_infos;
	}


	// Get a list of user IDs that are currently connected
	public function getOnlineUsers()
	{

		$query = $this->db->getQuery(true);

		$query
			->select("userid")
			->from("#__session");

		$this->db->setQuery($query);
		try {
			return $this->db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('Error getting online users in model/users at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}

	/**
	 * Returns user's groups. If AssocList then associative array with id and label, if column, only an array of ids
	 *
	 * @param $uid int
	 * @param $return string (AssocList|Column)
	 * @return array
	 *
	 * @since version 1.40.0
	 */
	public function getUserGroups($uid, $return = 'AssocList', $current_profile = null)
	{
		$user_groups = [];

		if (!empty($uid)) {
			try {
				$query = $this->db->getQuery(true);

				$query->select('esg.id, esg.label')
					->from($this->db->quoteName('#__emundus_groups', 'g'))
					->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'esg').' ON '.$this->db->quoteName('g.group_id').' = '.$this->db->quoteName('esg.id'))
					->where($this->db->quoteName('g.user_id') . ' = ' . $uid);
				$this->db->setQuery($query);

				if ($return == 'Column') {
					$user_groups = $this->db->loadColumn();

					if(!empty($current_profile)) {
						$groups_mapping = $this->getGroupsMapping();
						if(!empty($groups_mapping)) {
							foreach ($user_groups as $user_group) {
								if(in_array($user_group,array_keys($groups_mapping))) {
									$profiles_group = explode(',', $groups_mapping[$user_group]['profile_id']);
									if(!in_array($current_profile, $profiles_group)) {
										unset($user_groups[array_search($user_group, $user_groups)]);
									}
								}
							}
						}

						$user_groups = array_values($user_groups);
					}
				} else {
					$user_groups = $this->db->loadAssocList('id', 'label');
				}
			} catch(Exception $e) {
				Log::add('Failed to get user groups ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $user_groups;
	}

	/**
	 * getUserGroupsProgramme
	 *
	 * @param   mixed  $uid
	 *
	 * @return array
	 */
	public function getUserGroupsProgramme(int $uid): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select($this->db->quoteName('esgc.course'))
			->from($this->db->quoteName('#__emundus_groups', 'g'))
			->innerJoin($this->db->quoteName('#__emundus_setup_groups', 'esg') . ' ON ' . $this->db->quoteName('g.group_id') . ' = ' . $this->db->quoteName('esg.id'))
			->innerJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgc') . ' ON ' . $this->db->quoteName('esgc.parent_id') . ' = ' . $this->db->quoteName('esg.id'))
			->where($this->db->quoteName('g.user_id') . ' = ' . $uid);

		$this->db->setQuery($query);
		try {
			return $this->db->loadColumn();
		}
		catch (Exception $e) {
			return [];
		}
	}

	// get user ACL
	public function getUserACL($uid = null, $fnum = null)
	{
		try {
			$user = JFactory::getSession()->get('emundusUser');
			if (is_null($uid)) {
				$uid = $user->id;
			}
			$acl = array();
			if ($fnum === null) {
				$query = "SELECT esc.training as course, eua.action_id, eua.c, eua.r, eua.u, eua.d, g.group_id
                        FROM #__emundus_users_assoc AS eua
                        LEFT JOIN #__emundus_campaign_candidature AS ecc on ecc.fnum = eua.fnum
                        LEFT JOIN #__emundus_setup_campaigns AS esc on esc.id = ecc.campaign_id
                        LEFT JOIN #__emundus_groups as g on g.user_id = eua.user_id
                        WHERE eua.user_id = " . $uid;

				$this->db->setQuery($query);
				$userACL = $this->db->loadAssocList();

				if (count($userACL) > 0) {
					foreach ($userACL as $value) {
						if (isset($acl[$value['action_id']])) {
							$acl[$value['action_id']]['c'] = max($acl[$value['action_id']]['c'], $value['c']);
							$acl[$value['action_id']]['r'] = max($acl[$value['action_id']]['r'], $value['r']);
							$acl[$value['action_id']]['u'] = max($acl[$value['action_id']]['d'], $value['u']);
							$acl[$value['action_id']]['d'] = max($acl[$value['action_id']]['d'], $value['d']);
						}
						else {
							$acl[$value['action_id']]['c'] = $value['c'];
							$acl[$value['action_id']]['r'] = $value['r'];
							$acl[$value['action_id']]['u'] = $value['u'];
							$acl[$value['action_id']]['d'] = $value['d'];
						}
					}
				}
				if (!empty($user->emGroups)) {
					$query = "SELECT esgc.course, acl.action_id, acl.c, acl.r, acl.u, acl.d, acl.group_id
                        FROM #__emundus_setup_groups_repeat_course AS esgc
                        LEFT JOIN #__emundus_acl AS acl ON acl.group_id = esgc.parent_id
                        WHERE acl.group_id in (" . implode(',', $user->emGroups) . ")";

					$this->db->setQuery($query);
					$userACL = $this->db->loadAssocList();
					if (count($userACL) > 0) {
						foreach ($userACL as $value) {
							if (isset($acl[$value['action_id']])) {
								$acl[$value['action_id']]['c'] = max($acl[$value['action_id']]['c'], $value['c']);
								$acl[$value['action_id']]['r'] = max($acl[$value['action_id']]['r'], $value['r']);
								$acl[$value['action_id']]['u'] = max($acl[$value['action_id']]['d'], $value['u']);
								$acl[$value['action_id']]['d'] = max($acl[$value['action_id']]['d'], $value['d']);
							}
							else {
								$acl[$value['action_id']]['c'] = $value['c'];
								$acl[$value['action_id']]['r'] = $value['r'];
								$acl[$value['action_id']]['u'] = $value['u'];
								$acl[$value['action_id']]['d'] = $value['d'];
							}
						}
					}
				}

				return $acl;
			}
			else {

				$query = "SELECT eua.action_id, eua.c, eua.r, eua.u, eua.d
                        FROM #__emundus_users_assoc AS eua
                        WHERE fnum like " . $this->db->quote($fnum) . "  and  eua.user_id = " . $uid;
				$this->db->setQuery($query);

				return $this->db->loadAssocList();
			}

		}
		catch (Exception $e) {
			return false;
		}
	}

	public function getUserGroupsProgrammeAssoc($uid, $select = 'jesgrc.course', $groups = [])
	{
		$program_ids = [];

		$user_id = empty($uid) ? $this->user->id : $uid;

		if (!empty($user_id)) {
			$query = $this->db->getQuery(true);

			$query->select('DISTINCT ' . $this->db->quoteName($select))
				->from($this->db->quoteName('#__emundus_setup_programmes', 'jesp'))
				->innerJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'jesgrc') . ' ON ' . $this->db->quoteName('jesp.code') . ' = ' . $this->db->quoteName('jesgrc.course'))
				->innerJoin($this->db->quoteName('#__emundus_groups', 'jeg') . ' ON ' . $this->db->quoteName('jeg.group_id') . ' = ' . $this->db->quoteName('jesgrc.parent_id'));
			if(!empty($groups)) {
				$query->where($this->db->quoteName('jeg.group_id') . ' IN (' . implode(',', $groups) . ')');
			} else
			{
				$query->where($this->db->quoteName('jeg.user_id') . ' = ' . $user_id);
			}
			$query->where($this->db->quoteName('jesp.published') . ' = 1');

			try {
				$this->db->setQuery($query);
				$program_ids = $this->db->loadColumn();
			}
			catch (Exception $e) {
				Log::add('Error getting all profiles associated to user in model/access at query : ' . $query->__toString(), Log::ERROR, 'com_emundus');
			}
		}

		return $program_ids;
	}

	public function getAllCampaignsAssociatedToUser($user_id)
	{
		$campaign_ids = [];

		$user_id = empty($user_id) ? JFactory::getUser()->id : $user_id;

		if (!empty($user_id)) {

			$query = $this->db->getQuery(true);

			$query->select('DISTINCT jesc.id')
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'jesc'))
				->innerJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'jesgrc') . ' ON ' . $this->db->quoteName('jesc.training') . ' = ' . $this->db->quoteName('jesgrc.course'))
				->innerJoin($this->db->quoteName('#__emundus_groups', 'jeg') . ' ON ' . $this->db->quoteName('jeg.group_id') . ' = ' . $this->db->quoteName('jesgrc.parent_id'))
				->where($this->db->quoteName('jeg.user_id') . ' = ' . $user_id . ' AND ' . $this->db->quoteName('jesc.published') . ' = 1');

			$this->db->setQuery($query);
			try {
				$campaign_ids = $this->db->loadColumn();
			}
			catch (Exception $e) {
				Log::add('Error getting all profiles associated to user in model/access at query : ' . $query->__toString(), Log::ERROR, 'com_emundus');
			}
		}

		return $campaign_ids;
	}

	/*
     *   Get application fnums associated to a groups
     *   @param gid     array of groups ID
     *   @return array
    */
	public function getApplicationsAssocToGroups($gid)
	{
		$applications = [];

		if (!empty($gid)) {

			$query = $this->db->getQuery(true);

			$query->select('DISTINCT ga.fnum')
				->from($this->db->quoteName('#__emundus_group_assoc', 'ga'))
				->where('ga.group_id IN (' . implode(',', $gid) . ')');

			try {
				$this->db->setQuery($query);
				$applications = $this->db->loadColumn();
			}
			catch (Exception $e) {
				Log::add('Failed to get applications assoc to group ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $applications;
	}


	// get applicants associated to a user
	public function getApplicantsAssoc($uid)
	{
		$applications = [];

		if (!empty($uid)) {
			$query = $this->db->getQuery(true);

			$query->select('DISTINCT eua.fnum')
				->from($this->db->quoteName('#__emundus_users_assoc', 'eua'))
				->where('eua.user_id = ' . $uid);

			try {
				$this->db->setQuery($query);
				$applications = $this->db->loadColumn();
			}
			catch (Exception $e) {
				Log::add('Failed to get applications assoc to user ' . $uid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $applications;
	}

	public function getUserCampaigns($uid)
	{
		try {
			$query = "select esc.id, esc.label
                      from #__emundus_setup_campaigns as esc
                      left join #__emundus_campaign_candidature as ecc on ecc.campaign_id = esc.id
                      where ecc.applicant_id = " . $uid;

			$this->db->setQuery($query);

			return $this->db->loadAssocList('id', 'label');
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function getUserOprofiles($uid)
	{
		$o_profiles = [];

		try {
			$query = $this->db->getQuery(true);

			$query->select('esp.id,esp.label')
				->from($this->db->quoteName('#__emundus_setup_profiles','esp'))
				->leftJoin($this->db->quoteName('#__emundus_users_profiles','eup').' ON '.$this->db->quoteName('eup.profile_id').' = '.$this->db->quoteName('esp.id'))
				->where($this->db->quoteName('eup.user_id').' = '.$uid);

			$this->db->setQuery($query);

			$o_profiles =  $this->db->loadAssocList('id', 'label');
		}
		catch (Exception $e) {
			Log::add('Failed to get user o-profiles ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $o_profiles;
	}

	public function countUserEvaluations($uid)
	{
		try {
			$query = $this->db->getQuery(true);

			$query->select('COUNT(*)')
				->from($this->db->quoteName('#__emundus_evaluations'))
				->where($this->db->quoteName('user') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return 0;
		}
	}

	public function countUserDecisions($uid)
	{
		try {
			$query = $this->db->getQuery(true);

			$query->select('COUNT(*)')
				->from($this->db->quoteName('#__emundus_final_grade'))
				->where($this->db->quoteName('user') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return 0;
		}
	}

	/**
	 * @param $uid Int User id
	 * @param $pid Int Profile id
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function addProfileToUser($uid, $pid)
	{
		$result = true;
		$config = $this->app->getConfig();

		$timezone = new DateTimeZone($config->get('offset'));
		$now      = Factory::getDate()->setTimezone($timezone);

		try
		{
			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_users_profiles'))
				->where($this->db->quoteName('user_id') . ' = ' . $uid . ' AND ' . $this->db->quoteName('profile_id') . ' = ' . $pid);
			$this->db->setQuery($query);

			if(empty($this->db->loadResult()))
			{
				$columns = array('date_time', 'user_id', 'profile_id');
				$values  = array($now, $uid, $pid);
				$query->clear()
					->insert($this->db->quoteName('#__emundus_users_profiles'))
					->columns($this->db->quoteName($columns))
					->values(implode(',', $this->db->quote($values)));
				$this->db->setQuery($query);
				$result = $this->db->execute();

				// Associate Joomla group
				$this->addProfileAclToUser($uid, $pid);
				//

				// Associate Emundus default groups
				$this->addProfileGroupsToUser($uid, $pid);
				//

			}
		}
		catch (Exception $e)
		{
			Log::add('Error on adding profile to user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$result = false;
		}


		return $result;
	}

	/**
	 * Remove emundus profile from a user
	 *
	 * @param $uid
	 * @param $pid
	 *
	 * @return bool|mixed
	 *
	 * @since version 2.0.0
	 */
	public function removeProfileToUser($uid,$pid)
	{
		$removed = false;

		try
		{
			$query = $this->db->getQuery(true);

			// First we delete the profile from the user
			$query->delete($this->db->quoteName('#__emundus_users_profiles'))
				->where($this->db->quoteName('profile_id') . ' = :profileId')
				->where($this->db->quoteName('user_id') . ' = :uid')
				->bind(':profileId', $pid, ParameterType::INTEGER)
				->bind(':uid', $uid, ParameterType::INTEGER);
			$this->db->setQuery($query);

			if($removed = $this->db->execute()) {
				$query->clear()
					->select('profile_id')
					->from($this->db->quoteName('#__emundus_users_profiles'))
					->where($this->db->quoteName('user_id') . ' = :uid')
					->bind(':uid', $uid, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$users_profiles = $this->db->loadColumn();

				// If we remove the default profile, we set the first profile as default
				$query->clear()
					->select('profile')
					->from($this->db->quoteName('#__emundus_users'))
					->where($this->db->quoteName('user_id') . ' = :uid')
					->bind(':uid', $uid, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$default_profile = $this->db->loadResult();

				if($default_profile == $pid) {
					if(!empty($users_profiles)) {
						$new_default_profile = $users_profiles[0];
					}
					else {
						// If user does not have other profiles we set an applicant profile by default
						$query->clear()
							->select('id')
							->from($this->db->quoteName('#__emundus_setup_profiles'))
							->where($this->db->quoteName('published') . ' = 1');
						$this->db->setQuery($query);
						$new_default_profile = $this->db->loadResult();

						$this->addProfileToUser($uid, $new_default_profile);
					}

					$query->clear()
						->update($this->db->quoteName('#__emundus_users'))
						->set('profile = ' . $this->db->quote($new_default_profile))
						->where($this->db->quoteName('user_id') . ' = :uid')
						->bind(':uid', $uid, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$this->db->execute();
				}

				// Remove ACL groups if the profile is associated to a group and other profiles from user does not need this ACL
				$query->clear()
					->select('acl_aro_groups')
					->from($this->db->quoteName('#__emundus_setup_profiles'))
					->where($this->db->quoteName('id') . ' = :profileId')
					->bind(':profileId', $pid, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$acl_group_to_remove = $this->db->loadResult();

				$query->clear()
					->select('acl_aro_groups')
					->from($this->db->quoteName('#__emundus_setup_profiles'))
					->where($this->db->quoteName('id') . ' IN (' . implode(',', $users_profiles) . ')');
				$this->db->setQuery($query);
				$acl_groups = $this->db->loadColumn();

				if(!in_array($acl_group_to_remove, $acl_groups)) {
					$query->clear()
						->delete($this->db->quoteName('#__user_usergroup_map'))
						->where($this->db->quoteName('user_id') . ' = :uid')
						->where($this->db->quoteName('group_id') . ' = :group_id')
						->bind(':uid', $uid, ParameterType::INTEGER)
						->bind(':group_id', $acl_group_to_remove, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Remove eMundus groups if the profile is associated to a group and other profiles from user does not need this group
				$query->clear()
					->select($this->db->quoteName('emundus_groups'))
					->from($this->db->quoteName('#__emundus_setup_profiles_repeat_emundus_groups'))
					->where($this->db->quoteName('parent_id') . ' = ' . $pid);
				$this->db->setQuery($query);
				$emundus_groups_to_remove = $this->db->loadColumn();

				$query->clear()
					->select($this->db->quoteName('emundus_groups'))
					->from($this->db->quoteName('#__emundus_setup_profiles_repeat_emundus_groups'))
					->where($this->db->quoteName('parent_id') . ' IN (' . implode(',', $users_profiles) . ')');
				$this->db->setQuery($query);
				$emundus_groups = $this->db->loadColumn();

				if(!in_array($emundus_groups_to_remove, $emundus_groups)) {
					$query->clear()
						->delete($this->db->quoteName('#__emundus_groups'))
						->where($this->db->quoteName('user_id') . ' = :uid')
						->where($this->db->quoteName('group_id') . ' = :group_id')
						->bind(':uid', $uid, ParameterType::INTEGER)
						->bind(':group_id', $emundus_groups_to_remove, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//
			}
		}
		catch (Exception $e)
		{
			Log::add('Failed to remove profile to user ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $removed;
	}

	public function checkProfilesUser($uid)
	{
		$checked = true;
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('eu.profile')
				->from($this->db->quoteName('#__emundus_users', 'eu'))
				->where($this->db->quoteName('eu.user_id') . ' = ' . $uid);
			$this->db->setQuery($query);
			$default_profile = $this->db->loadResult();

			$query->clear()
				->select('eup.profile_id')
				->from($this->db->quoteName('#__emundus_users_profiles', 'eup'))
				->where($this->db->quoteName('eup.user_id') . ' = ' . $uid);
			$this->db->setQuery($query);
			$profiles = $this->db->loadColumn();

			$profiles = array_merge([$default_profile], $profiles);
			$profiles = array_unique($profiles);

			foreach ($profiles as $profile) {
				// Check Joomla ACL
				$acl_added = $this->addProfileAclToUser($uid, $profile);

				// Check emundus groups
				$groups_added = $this->addProfileGroupsToUser($uid, $profile);

				$checked = $acl_added && $groups_added;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error on checking ACL of user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$checked = false;
		}

		return $checked;
	}

	public function addProfileAclToUser($uid, $pid)
	{
		$added = true;
		$query = $this->db->getQuery(true);

		try
		{
			// Add ACL to user
			$query->clear()
				->select($this->db->quoteName('acl_aro_groups'))
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = ' . $pid);
			$this->db->setQuery($query);
			$joomla_group = $this->db->loadResult();

			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__usergroups'))
				->where($this->db->quoteName('id') . ' = :groupId')
				->bind(':groupId', $joomla_group, ParameterType::INTEGER);
			$this->db->setQuery($query);
			$exist = $this->db->loadResult();

			if ($exist)
			{
				$query = $this->db->getQuery(true)
					->select($this->db->quoteName('user_id'))
					->from($this->db->quoteName('#__user_usergroup_map'))
					->where($this->db->quoteName('group_id') . ' = :groupId')
					->where($this->db->quoteName('user_id') . ' = :uid')
					->bind(':groupId', $joomla_group, ParameterType::INTEGER)
					->bind(':uid', $uid, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$mapping_exist = $this->db->loadResult();

				if (empty($mapping_exist))
				{
					$inserted = [
						'user_id'  => $uid,
						'group_id' => $joomla_group
					];
					$inserted = (object) $inserted;
					$added   = $this->db->insertObject('#__user_usergroup_map', $inserted);
				}
			}
			//
		}
		catch (Exception $e)
		{
			Log::add('Error on adding profile to user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$added = false;
		}


		return $added;
	}

	public function addProfileGroupsToUser($uid, $pid)
	{
		$added = true;
		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select($this->db->quoteName('emundus_groups'))
				->from($this->db->quoteName('#__emundus_setup_profiles_repeat_emundus_groups'))
				->where($this->db->quoteName('parent_id') . ' = ' . $pid);
			$this->db->setQuery($query);
			$emundus_groups = $this->db->loadColumn();

			foreach ($emundus_groups as $emundusGroup) {
				$query->clear()
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__emundus_setup_groups'))
					->where($this->db->quoteName('id') . ' = :groupId')
					->bind(':groupId', $emundusGroup, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$exist = $this->db->loadResult();

				if ($exist)
				{
					$query->clear()
						->select($this->db->quoteName('user_id'))
						->from($this->db->quoteName('#__emundus_groups'))
						->where($this->db->quoteName('group_id') . ' = :groupId')
						->where($this->db->quoteName('user_id') . ' = :uid')
						->bind(':groupId', $emundusGroup, ParameterType::INTEGER)
						->bind(':uid', $uid, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$mapping_exist = $this->db->loadResult();

					if (empty($mapping_exist))
					{
						$inserted = [
							'user_id'  => $uid,
							'group_id' => $emundusGroup
						];
						$inserted = (object) $inserted;
						$added   = $this->db->insertObject('#__emundus_groups', $inserted);
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error on adding profile to user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			$added = false;
		}

		return $added;
	}


	public function editUser($user, $current_user = null)
	{
		if (empty($current_user)) {
			$current_user = Factory::getApplication()->getIdentity();
		}

		$eMConfig = ComponentHelper::getParams('com_emundus');
		$u        = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user['id']);

		if (isset($user['same_login_email']) && $user['same_login_email'] === 1) {
			$user['username'] = $user['email'];
			$u->setProperties(['username' => $user['username']]);
			unset($user['same_login_email']);
		}

		if(isset($user['testing_account']) && EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$u->setParam('testing_account', $user['testing_account']);
			unset($user['testing_account']);
		}

		if($user['authProvider'] == 1) {
			$user['authProvider'] = 'sso';
		} else {
			$user['authProvider'] = '';
		}

		if (!$u->bind($user)) {
			return array('msg' => $u->getError());
		}
		if (!$u->save()) {
			return array('msg' => $u->getError());
		}

		$query = $this->db->getQuery(true);

		$query->update($this->db->quoteName('#__emundus_users'))
			->set('firstname = ' . $this->db->quote($user['firstname']))
			->set('lastname = ' . $this->db->quote($user['lastname']));

		if (!empty($user['university_id'])) {
			$query->set('university_id = ' . $this->db->quote($user['university_id']));
		}

		$query->where('user_id = ' . $this->db->quote($user['id']));
		$this->db->setQuery($query);

		try {
			$this->db->execute();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}

		$query->clear()
			->update($this->db->quoteName('#__user_profiles'))
			->set('profile_value = ' . $this->db->quote($user['firstname']))
			->where('user_id = ' . $this->db->quote($user['id']) . ' and profile_key like "emundus_profile.firstname"');
		$this->db->setQuery($query);
		try {
			$this->db->execute();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}

		$query->clear()
			->update($this->db->quoteName('#__user_profiles'))
			->set('profile_value = ' . $this->db->quote($user['lastname']))
			->where('user_id = ' . $this->db->quote($user['id']) . ' and profile_key like "emundus_profile.lastname"');
		$this->db->setQuery($query);
		try {
			$this->db->execute();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}

		if ($eMConfig->get('showJoomlagroups', 0) == 1) {
			if (!empty($user['j_groups'])) {
				$groups = explode(',', $user['j_groups']);
				foreach ($groups as $group) {
					$query->clear()
						->insert($this->db->quoteName('#__user_usergroup_map'))
						->columns($this->db->quoteName(array('user_id', 'group_id')))
						->values(implode(',', $this->db->quote(array($user['id'], $group))));
					$this->db->setQuery($query);
					try {
						$this->db->execute();
					}
					catch (Exception $e) {
						error_log($e->getMessage(), 0);

						return false;
					}
				}
			}
		}

		if (!empty($user['em_campaigns'])) {
			$campaigns = explode(',', $user['em_campaigns']);

			$query->clear()
				->select('campaign_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where('applicant_id = ' . $user['id']);
			$this->db->setQuery($query);
			$campaigns_id = $this->db->loadColumn();

			$query->clear()
				->select('profile_id')
				->from($this->db->quoteName('#__emundus_users_profiles'))
				->where('user_id = ' . $user['id']);
			$this->db->setQuery($query);
			$profiles_id = $this->db->loadColumn();

			foreach ($campaigns as $campaign) {
				$profile = $this->getProfileIDByCampaignID($campaign);
				if (!in_array($profile, $profiles_id)) {
					$this->addProfileToUser($user['id'], $profile);
				}

				if (!in_array($campaign, $campaigns_id)) {
					$fnum = EmundusHelperFiles::createFnum($campaign, $user['id']);

					if(!empty($fnum)) {
						$columns = array('applicant_id', 'user_id', 'campaign_id', 'fnum');
						$values  = array($user['id'], $this->user->id, $campaign, $fnum);

						$query->clear()
							->insert($this->db->quoteName('#__emundus_campaign_candidature'))
							->columns($this->db->quoteName($columns))
							->values(implode(',', $this->db->quote($values)));
						$this->db->setQuery($query);
						try {
							$this->db->execute();

							//TODO: Add log to know who created the application for this user
						}
						catch (Exception $e) {
							error_log($e->getMessage(), 0);

							return false;
						}
					}
				}
			}
		}

		if ($user['news'] == "1") {
			$columns = array('user_id', 'profile_key', 'profile_value', 'ordering');
			$values  = array($user['id'], 'emundus_profile.newsletter', '"1"', 4);
			$query->clear()
				->insert($this->db->quoteName('#__user_profiles'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $this->db->quote($values)));
			$this->db->setQuery($query);
			try {
				$this->db->execute();
			}
			catch (Exception $e) {
				error_log($e->getMessage(), 0);

				return false;
			}
		}

		return true;
	}

	public function editUserProfiles(int $user_id, int $profile_id, array $other_profiles = [], array $groups = []): bool
	{
		$updated = false;

		if (!empty($user_id) && !empty($profile_id)) {
			$update_tasks = [];

			try
			{
				$query = $this->db->createQuery();

				$query->clear()
					->delete($this->db->quoteName('#__user_profiles'))
					->where('user_id = ' . $this->db->quote($user_id) . ' and profile_key like "emundus_profile.newsletter"');
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->delete($this->db->quoteName('#__emundus_users_profiles'))
					->where('user_id = ' . $this->db->quote($user_id));

				$this->db->setQuery($query);
				$update_tasks[] = $this->db->execute();

				$query->clear()
					->update($this->db->quoteName('#__emundus_users'))
					->set('profile = ' . $this->db->quote($profile_id))
					->where('user_id = ' . $this->db->quote($user_id));

				$this->db->setQuery($query);
				$update_tasks[] = $this->db->execute();

				$update_tasks[] = $this->addProfileToUser($user_id, $profile_id);

				if (!empty($other_profiles)) {
					$query->clear()
						->select('profile_id')
						->from($this->db->quoteName('#__emundus_users_profiles'))
						->where('user_id = ' . $user_id);
					$this->db->setQuery($query);
					$profiles_id = $this->db->loadColumn();

					foreach ($other_profiles as $profile) {
						if (!in_array($profile, $profiles_id)) {
							$update_tasks[] = $this->addProfileToUser($user_id, $profile);
						}
					}
				}

				try {
					$query->clear()
						->delete($this->db->quoteName('#__emundus_groups'))
						->where('user_id = ' . $this->db->quote($user_id));

					$this->db->setQuery($query);
					$this->db->execute();
				}
				catch (Exception $e) {
					Log::add('Error deleting user groups ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}

				if (!empty($groups)) {
					foreach ($groups as $group) {
						$columns = array('user_id', 'group_id');
						$values  = array($user_id, $group);
						$query->clear()
							->insert($this->db->quoteName('#__emundus_groups'))
							->columns($this->db->quoteName($columns))
							->values(implode(',', $this->db->quote($values)));
						try {
							$this->db->setQuery($query);
							$update_tasks[] = $this->db->execute();
						}
						catch (Exception $e) {
							Log::add('Error adding user groups ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error editing user ' . $user_id . ' profiles ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			$updated = !in_array(false, $update_tasks);
		}

		return $updated;
	}

	public function getGroupDetails($gid)
	{
		$group = [];

		try {
			$query = $this->db->getQuery(true);
			$query->select('id,label,description,published,class,anonymize')
				->from($this->db->quoteName('#__emundus_setup_groups'))
				->where($this->db->quoteName('id') . ' = ' . $gid);
			$this->db->setQuery($query);
			$group = $this->db->loadAssoc();
		}
		catch (Exception $e) {
			Log::add('Error getting group programs ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $group;
	}

	/**
	 * @param $gid
	 *
	 * @return bool|mixed
	 *
	 * @since version
	 */
	public function getGroupProgs($gid)
	{
		$programs = [];

		try {
			$query = $this->db->getQuery(true);
			$query->select('prg.id, prg.label, esg.label as group_label')
				->from($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'esg') . ' ON ' . $this->db->quoteName('esgrc.parent_id') . ' = ' . $this->db->quoteName('esg.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'prg') . ' ON ' . $this->db->quoteName('prg.code') . ' = ' . $this->db->quoteName('esgrc.course'))
				->where($this->db->quoteName('esg.id') . ' = ' . $gid);
			$this->db->setQuery($query);
			$programs = $this->db->loadAssocList();
		}
		catch (Exception $e) {
			Log::add('Error getting group programs ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $programs;
	}

	/**
	 * @param $gids
	 *
	 * @return array|bool|mixed
	 *
	 * @since version
	 */
	public function getGroupsAcl($gids)
	{
		$groups_acl = [];

		if (!empty($gids)) {

			$query = $this->db->getQuery(true);

			if (!is_array($gids)) {
				$gids = [$gids];
			}

			$query->select('esa.label, ea.*, esa.c as is_c, esa.r as is_r, esa.u as is_u, esa.d as is_d, esa.description as action_description')
				->from($this->db->quoteName('#__emundus_acl', 'ea'))
				->leftJoin($this->db->quoteName('#__emundus_setup_actions', 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('ea.action_id'))
				->where($this->db->quoteName('ea.group_id') . ' IN (' . implode(',', $gids) . ')')
				->where($this->db->quoteName('esa.status') . ' != 0')
				->order($this->db->quoteName('esa.ordering') . ' ASC, ' . $this->db->quoteName('esa.name') . ' ASC');

			try {
				$this->db->setQuery($query);
				$groups_acl = $this->db->loadAssocList();
			}
			catch (Exception $e) {
				error_log($e->getMessage(), 0);
				$groups_acl = false;
			}
		}

		return $groups_acl;
	}

	/** This function returns the groups which are linked to the fnum's program OR NO PROGRAM AT ALL.
	 *
	 * @param $group_ids array
	 * @param $fnum      string
	 * @param $strict    bool if true, only the groups linked to the fnum's program are returned
	 *
	 * @return bool|mixed
	 *
	 * @since version
	 */
	public function getEffectiveGroupsForFnum($group_ids, $fnum, $strict = false)
	{

		$groups = [];

		
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('sg.id'))
			->from($this->db->quoteName('#__emundus_setup_groups', 'sg'))
			->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'grc') . ' ON ' . $this->db->quoteName('grc.parent_id') . ' = ' . $this->db->quoteName('sg.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sp.code') . ' = ' . $this->db->quoteName('grc.course'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->db->quoteName('sp.code') . ' = ' . $this->db->quoteName('sc.training'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.campaign_id') . ' = ' . $this->db->quoteName('sc.id'))
			->where($this->db->quoteName('sg.id') . ' IN (' . implode(',', $group_ids) . ') AND (' . $this->db->quoteName('cc.fnum') . ' LIKE ' . $this->db->quote($fnum) . ' OR ' . $this->db->quoteName('sp.code') . ' IS NULL)');

		if ($strict) {
			$query->where($this->db->quoteName('sg.id') . ' IN (' . implode(',', $group_ids) . ') AND (' . $this->db->quoteName('cc.fnum') . ' LIKE ' . $this->db->quote($fnum) . ')');
		}

		try {
			$this->db->setQuery($query);
			$groups = $this->db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('Error getting effective groups for fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $groups;
	}

	/**
	 * @param $gid
	 *
	 * @return bool|mixed
	 *
	 * @since version
	 */
	public function getGroupUsers($gid)
	{
		try {
			$query = "select eu.*
                      from #__emundus_groups as eg
                      left join #__emundus_users as eu on eu.user_id = eg.user_id
                      where eg.group_id = " . $gid . " order by eu.lastname asc";

			$this->db->setQuery($query);

			return $this->db->loadAssocList();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	public function getMenuList($params)
	{
		return EmundusHelperFiles::getMenuList($params);
	}

	/**
	 * @param $aid
	 * @param $fnum
	 * @param $uid
	 * @param $crud
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	public function getUserActionByFnum($aid, $fnum, $uid, $crud)
	{
		$action = false;

		if (!empty($aid) && !empty($fnum) && !empty($uid) && !empty($crud)) {
			$query = $this->db->getQuery(true);

			$query->select($crud)
				->from($this->db->quoteName('#__emundus_users_assoc'))
				->where($this->db->quoteName('action_id') . ' = ' . $this->db->quote($aid))
				->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($uid))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$action = $this->db->loadResult();
			}
			catch (Exception $e) {
				Log::add("Error from getUserActionByFnum aid $aid, fnum $fnum, uid $uid, crud $crud", Log::ERROR, 'com_emundus.error');
			}
		}

		return $action;
	}

	/**
	 * @param $gids
	 * @param $fnum
	 * @param $aid
	 * @param $crud
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	public function getGroupActions($gids, $fnum, $aid, $crud)
	{
		$groupActions = [];

		if (!empty($gids) && !empty($fnum) && !empty($aid) && !empty($crud)) {
			
			$query = "select " . $crud . " from #__emundus_group_assoc where action_id = " . $aid . " and group_id in (" . implode(',', $gids) . ") and fnum like " . $this->db->quote($fnum);
			$this->db->setQuery($query);

			try {
				$groupActions = $this->db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add("Error from getGroupActions gids " . implode(',', $gids) . ", fnum $fnum, aid $aid, crud $crud", Log::ERROR, 'com_emundus.error');
			}
		}

		return $groupActions;
	}

	/**
	 * @param $uid      int user id
	 * @param $passwd   string  password to set
	 *
	 * @return mixed
	 */
	public function setNewPasswd($uid, $passwd)
	{
		
		$query = 'UPDATE #__users SET password = ' . $this->db->Quote($passwd) . ' WHERE id=' . $uid;
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	/**
	 * Connect to LDAP
	 * @return mixed
	 */
	public function searchLDAP($search)
	{
		// Create LDAP object using params entered in plugin
		$plugin = JPluginHelper::getPlugin('authentication', 'ldap');
		$params = new JRegistry($plugin->params);
		$ldap   = new JLDAP($params);

		if (!$ldap->connect())
			return false;

		if (!$ldap->bind())
			return false;

		// filters are different based on the LDAP tree, therefore we put them in the eMundus params.
		$params      = JComponentHelper::getParams('com_emundus');
		$ldapFilters = $params->get('ldapFilters');

		// Filters come in a list separated by commas, but are fed into the LDAP object as an array.
		// The area to put the search term is defined as [SEARCH] in the param.
		$filters = explode(',', str_replace('[SEARCH]', $search, $ldapFilters));

		$ret         = new stdClass();
		$ret->status = true;
		$ret->users  = $ldap->search($filters);

		return $ret;
	}

	public function getUserById($uid)
	{ // user of emundus
		$users = [];

		if (!empty($uid)) {
			
			$query = $this->db->getQuery(true);
			$query->select('eu.*,u.email, u.username, u.registerDate, u.lastvisitDate, case when u.password = ' . $this->db->quote('') . ' then ' . $this->db->quote('external') . ' else ' . $this->db->quote('internal') . ' end as login_type,u.block,u.activation,u.params')
				->from('#__emundus_users as eu')
				->leftJoin('#__users as u on u.id = eu.user_id')
				->where('eu.user_id = ' . $uid);

			try {
				$this->db->setQuery($query);
				$users = $this->db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('Failed to get user by id ' . $uid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $users;
	}

	public function getUserNameById($id)
	{
		$username = [];

		if (!empty($id)) {
			
			$query = $this->db->getQuery(true);

			$query->select('eu.firstname, eu.lastname, eu.user_id')
				->from('#__emundus_users as eu')
				->where('eu.user_id = ' . $id);

			try {
				$this->db->setQuery($query);
				$username = $this->db->loadAssoc();
			}
			catch (Exception $e) {
				Log::add('Failed to get username by id ' . $id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $username;
	}

	public function getUsersById($id)
	{ //user of application
		
		$query = 'SELECT * FROM #__users WHERE id = ' . $id;
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getUsersByIds($ids)
	{
		$users = [];

		if (!empty($ids)) {
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from('#__users')
				->where('id IN (' . implode(',', $ids) . ')');

			try {
				$this->db->setQuery($query);
				$users = $this->db->loadObjectList();

				foreach ($users as $user)
				{
					unset($user->password);
				}
			}
			catch (Exception $e) {
				Log::add('Failed to get users by ids ' . implode(',', $ids) . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $users;
	}


	/**
	 * Method to start the password reset process. Taken from Joomla and modified to send email using template.
	 *
	 * @param   array  $data  The data expected for the form.
	 *
	 * @return  Object
	 *
	 * @throws Exception
	 * @since  3.9.11
	 */
	public function passwordReset($data, $subject = 'COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT', $body = 'COM_USERS_EMAIL_PASSWORD_RESET_BODY', $new_account = false, $email_tmpl = null, $current_user = null)
	{
		if(empty($current_user))
		{
			$current_user = Factory::getApplication()->getIdentity();
		}

		$config = Factory::getApplication()->getConfig();

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		$m_emails = new EmundusModelEmails();

		// Load the com_users language tags in order to call the Joomla user JText.
		$language     = Factory::getApplication()->getLanguage();
		$extension    = 'com_users';
		$base_dir     = JPATH_SITE;
		$language_tag = $language->getTag(); // loads the current language-tag
		$language->load($extension, $base_dir, $language_tag, true);

		$return = new stdClass();

		$data['email'] = filter_var(JStringPunycode::emailToPunycode($data['email']), FILTER_VALIDATE_EMAIL);

		// Check the validation results.
		if (empty($data['email'])) {
			$return->message = JText::_('COM_USERS_DESIRED_USERNAME');
			$return->status  = false;

			return $return;
		}

		// Find the user id for the given email address.
		
		$query = $this->db->getQuery(true)
			->select('id')
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('email') . ' = ' . $this->db->quote($data['email']));

		// Get the user object.
		$this->db->setQuery($query);

		try {
			$userId = $this->db->loadResult();
		}
		catch (RuntimeException $e) {
			$return->message = Text::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage());
			$return->status  = false;

			return $return;
		}

		// Check for a user.
		if (empty($userId)) {
			$return->message = Text::_('COM_USERS_INVALID_EMAIL');
			$return->status  = false;

			return $return;
		}

		// Get the user object.
		$user  = Factory::getUser($userId);
		$table = Table::getInstance('user', 'JTable');
		$table->load($user->id);

		// Make sure the user isn't blocked.
		if ($user->block) {
			$return->message = Text::_('COM_USERS_USER_BLOCKED');
			$return->status  = false;

			return $return;
		}

		if(!empty($user->params))
		{
			$user_params = json_decode($user->params);

			if(!empty($user_params->OAuth2) && $user_params->OAuth2 == 'openid') {
				$return->message = Text::_('COM_USERS_INVALID_EMAIL');
				$return->status  = false;

				return $return;
			}
		}

		if(!empty($user->params))
		{
			$user_params = json_decode($user->params);

			if(!empty($user_params->OAuth2) && $user_params->OAuth2 == 'openid') {
				$return->message = Text::_('COM_USERS_INVALID_EMAIL');
				$return->status  = false;

				return $return;
			}
		}

		// Make sure the user isn't a Super Admin.
		if ($user->authorise('core.admin')) {
			$return->message = Text::_('COM_USERS_REMIND_SUPERADMIN_ERROR');
			$return->status  = false;

			return $return;
		}

		$m_juser_reset = new ResetModel();

		// Make sure the user has not exceeded the reset limit
		if (!$m_juser_reset->checkResetLimit($user)) {
			$resetLimit      = (int) $this->app->getParams()->get('reset_time');
			$return->message = Text::plural('COM_USERS_REMIND_LIMIT_ERROR_N_HOURS', $resetLimit);
			$return->status  = false;

			return $return;
		}

		// Set the confirmation token.
		$token       = ApplicationHelper::getHash(UserHelper::genRandomPassword());
		$hashedToken = UserHelper::hashPassword($token);

		$table->activation = $hashedToken;

		// Save the user to the database.
		if (!$table->store()) {
			throw new Exception(Text::sprintf('COM_USERS_USER_SAVE_FAILED', $user->getError()), 500);
		}

		// Assemble the password reset confirmation link.
		$mode = $config->get('force_ssl', 0) == 2 ? 1 : (-1);

		$menu_item = Factory::getApplication()->getMenu()->getItems('link', 'index.php?option=com_users&view=reset', true);
		if($new_account)
		{
			$account_menu_id = ComponentHelper::getComponent('com_emundus')->getParams()->get('account_creation_link',0);
			if(!empty($account_menu_id)) {
				$menu_item = Factory::getApplication()->getMenu()->getItem($account_menu_id);
			}
		}

		if(!empty($menu_item)) {
			$link = $menu_item->alias.'?layout=confirm&token=' . $token . '&username=' . $user->get('username');
		}
		else {
			$link = 'index.php?option=com_users&view=reset&layout=confirm&token=' . $token . '&username=' . $user->get('username');
		}
		if($new_account) {
			$link .= '&new_account=1';
		}
		$link = str_replace('+', '%2B', $link);

		// Put together the email template data.
		$data              = $user->getProperties();
		$data['sitename']  = $config->get('sitename');
		$data['link_text'] = Uri::base() . $link;
		$data['link_html'] = '<a href=' . Uri::base() . $link . '> ' . Uri::base() . $link . '</a>';
		$data['token']     = $token;


		$post = [
			'USER_NAME'  => $user->name,
			'SITE_URL'   => Uri::base(),
			'SITE_NAME'  => $config->get('sitename'),
			'USER_EMAIL' => $user->email,
			'ACCOUNT_CREATION_URL' => Uri::base() . $link,
		];

		if(!empty($email_tmpl))
		{
			$m_emails->sendEmailNoFnum($user->email, $email_tmpl, $post);
		}
		else
		{

			$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

			// Build the translated email.
			$subject = Text::sprintf($subject, $data['sitename']);
			$body    = Text::sprintf($body, $data['sitename'], $data['token'], $data['link_html']);

			$tags = $m_emails->setTags($user->id, $post, null, '', $subject . $body);

			$subject = preg_replace($tags['patterns'], $tags['replacements'], $subject);

			// Get and apply the template.
			$query->clear()
				->select($this->db->quoteName('Template'))
				->from($this->db->quoteName('#__emundus_email_templates'))
				->where($this->db->quoteName('id') . ' = 1')
				->orWhere($this->db->quoteName('lbl').' LIKE '.$this->db->quote('default'));
			$this->db->setQuery($query);

			try
			{
				$template = $this->db->loadResult();
			}
			catch (RuntimeException $e)
			{
				$return->message = Text::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage());
				$return->status  = false;

				return $return;
			}

			$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/", "/\[SITE_NAME\]/"], [$subject, $body, $data['sitename']], $template);
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);

			// Set sender
			$sender = [
				$config->get('mailfrom'),
				$config->get('fromname')
			];

			$mailer->setSender($sender);
			$mailer->addReplyTo($config->get('mailfrom'), $config->get('fromname'));
			$mailer->addRecipient($user->email);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);

			// Send the password reset request email.

			require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
			$custom_email_tag = EmundusHelperEmails::getCustomHeader();
			if (!empty($custom_email_tag))
			{
				$mailer->addCustomHeader($custom_email_tag);
			}

			$send = $mailer->Send();

			// Check for an error.
			if ($send !== true)
			{
				throw new Exception(Text::_('COM_USERS_MAIL_FAILED'), 500);
			}
		}

		$return->status = true;

		return $return;
	}

	public function getProfileForm()
	{
		$form_id = 0;

		$query = $this->db->getQuery(true);

		try {
			$query->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('profile'))
				->andWhere($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);

			$form_id = $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot get profile form for edit user : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $form_id;
	}

	public function getProfileGroups($formid)
	{
		
		$query = $this->db->getQuery(true);

		try {
			$query->select('fg.*')
				->from($this->db->quoteName('#__fabrik_groups', 'fg'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ' . $this->db->quoteName('ff.group_id') . ' = ' . $this->db->quoteName('fg.id'))
				->where($this->db->quoteName('ff.form_id') . ' = ' . $this->db->quote($formid))
				->andWhere($this->db->quoteName('fg.published') . ' = 1');
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot get profile groups with formid : ' . $formid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}

	public function getProfileElements($group)
	{
		
		$query = $this->db->getQuery(true);

		try {
			$query->select('fe.*,fj.action,fj.code,fj.params as jsparams')
				->from($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_jsactions', 'fj') . ' ON ' . $this->db->quoteName('fj.element_id') . ' = ' . $this->db->quoteName('fe.id'));
			if (is_array($group)) {
				$query->where($this->db->quoteName('fe.group_id') . ' IN (' . implode(',', $group) . ')');
			}
			else {
				$query->where($this->db->quoteName('fe.group_id') . ' = ' . $this->db->quote($group));
			}
			$query->andWhere($this->db->quoteName('fe.published') . ' = 1')
				->order($this->db->quoteName('fe.ordering'));
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach ($elements as $element) {
				$params = json_decode($element->params);

				$element->label   = JText::_($element->label);
				$params->rollover = JText::_($params->rollover);

				$element->params = json_encode($params);

				if ($element->plugin == 'calc') {
					$element->value = eval($params->calc_calculation);
				}
			}

			return $elements;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot get elements of group ' . $group . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}

	public function saveUser($user, $uid)
	{
		$saved = false;
		
		$query = $this->db->getQuery(true);

		$columns = array();

		$formid     = $this->getProfileForm();
		$groups     = $this->getProfileGroups($formid);
		$ids_groups = array_map(function ($group) {
			return $group->id;
		}, $groups);
		$elements   = $this->getProfileElements($ids_groups);

		$user_keys = array_keys(get_object_vars($user));
		foreach ($elements as $element) {
			if (in_array($element->name, $user_keys) && $element->name != 'id') {
				$columns[] = $element->name;
			}
		}

		$fullname = $user->firstname . ' ' . $user->lastname;

		try {
			$query->update($this->db->quoteName('#__users'))
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote($fullname))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__emundus_users'));
			foreach ($columns as $column) {
				$query->set($this->db->quoteName($column) . ' = ' . $this->db->quote($user->{$column}));
			}
			$query->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);
			$saved = $this->db->execute();
			if ($saved) {
				JPluginHelper::importPlugin('emundus');
				\Joomla\CMS\Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterSaveUserProfile', ['user' => $uid, 'data' => $user, 'columns' => $columns]]);
			}

		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot update user ' . $uid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $saved;
	}

	public function getProfileAttachments($user_id, $fnum = null)
	{
		
		$query = $this->db->getQuery(true);

		try {
			$query->select('esa.*,eua.expires_date,eua.date_time,eua.filename,eua.id as default_id,eua.validation')
				->from($this->db->quoteName('#__emundus_users_attachments', 'eua'))
				->leftJoin($this->db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('eua.attachment_id'));
			if (!empty($fnum)) {
				$query->where($this->db->quoteName('eua.attachment_id') . ' NOT IN (SELECT distinct attachment_id FROM #__emundus_uploads WHERE fnum = ' . $this->db->quote($fnum) . ')');
			}
			$query->where($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($user_id))
				->andWhere($this->db->quoteName('eua.published') . ' = 1');
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot get default attachments uploaded by the applicant : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}

	public function getProfileAttachmentsAllowed()
	{
		
		$query = $this->db->getQuery(true);

		try {
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('default_attachment') . ' = 1');
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot get attachments allowed to default values : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}

	public function addDefaultAttachment($user_id, $attachment_id, $filename)
	{
		
		$query = $this->db->getQuery(true);

		$six_month_in_future = strtotime(date('Y-m-d') . "+6 month");

		try {
			$query->insert($this->db->quoteName('#__emundus_users_attachments'))
				->set($this->db->quoteName('date_time') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id))
				->set($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($attachment_id))
				->set($this->db->quoteName('filename') . ' = ' . $this->db->quote($filename));
			$this->db->setQuery($query);
			$result = $this->db->execute();

			JPluginHelper::importPlugin('emundus');

			$this->app->triggerEvent('onAfterProfileAttachmentUpload', [$user_id, (int) $attachment_id, $filename]);
			$this->app->triggerEvent('onCallEventHandler', ['onAfterProfileAttachmentUpload', ['user_id' => $user_id, 'attachment_id' => (int) $attachment_id, 'file' => $filename]]);

			return $result;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot insert default documents : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function deleteProfileAttachment($id, $user_id)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('attachment_id,filename')
				->from($this->db->quoteName('#__emundus_users_attachments'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
			$this->db->setQuery($query);
			$default_attachment = $this->db->loadObject();

			$query->clear()
				->delete($this->db->quoteName('#__emundus_users_attachments'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
			$this->db->setQuery($query);
			$result = $this->db->execute();

			JPluginHelper::importPlugin('emundus');

			$this->app->triggerEvent('onAfterProfileAttachmentDelete', [$user_id, (int) $default_attachment->attachment_id]);
			$this->app->triggerEvent('onCallEventHandler', ['onAfterProfileAttachmentDelete', ['user_id' => $user_id, 'attachment_id' => (int) $default_attachment->attachment_id, 'filename' => $default_attachment->filename]]);

			return $result;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot delete document from jos_emundus_users_attachments with id ' . $id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function uploadProfileAttachmentToFile($fnum, $aids, $uid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('eua.*')
				->from($this->db->quoteName('#__emundus_users_attachments', 'eua'))
				->where($this->db->quoteName('eua.attachment_id') . ' IN (' . $aids . ')')
				->andWhere($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);
			$attachments_to_copy = $this->db->loadObjectList();

			foreach ($attachments_to_copy as $attachment) {
				$query->clear()
					->select('count(eu.id)')
					->from($this->db->quoteName('#__emundus_uploads', 'eu'))
					->where($this->db->quoteName('eu.attachment_id') . ' = ' . $this->db->quote($attachment->attachment_id))
					->andWhere($this->db->quoteName('eu.fnum') . ' = ' . $this->db->quote($fnum));
				$this->db->setQuery($query);
				$attachment_already_copy = $this->db->loadResult();

				if ($attachment_already_copy == 0) {
					$root_dir = "images/emundus/files/" . $uid;

					if (!file_exists($root_dir)) {
						mkdir($root_dir);
					}

					$file = explode('/', $attachment->filename);
					$file = explode('.', end($file));
					$ext  = end($file);
					$file = $file[0];
					$pos  = strrpos($file, '-');
					$file = substr($file, 0, $pos);

					$target_file = $file . '-' . time() . '.' . $ext;

					copy($attachment->filename, $root_dir . '/' . $target_file);
					$columns = array('user_id', 'fnum', 'attachment_id', 'filename');
					$values  = array($uid, $fnum, $attachment->attachment_id, $target_file);

					$query->clear()
						->insert($this->db->quoteName('#__emundus_uploads'))
						->columns(implode(',', $this->db->quoteName($columns)))
						->values(implode(',', $this->db->quote($values)));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			return true;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot copy profile document ' . json_encode($aids) . ' to fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function uploadFileAttachmentToProfile($fnum, $aid, $uid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('eu.*')
				->from($this->db->quoteName('#__emundus_uploads', 'eu'))
				->where($this->db->quoteName('eu.attachment_id') . ' = ' . $this->db->quote($aid))
				->andWhere($this->db->quoteName('eu.fnum') . ' = ' . $this->db->quote($fnum))
				->order('eu.id DESC');
			$this->db->setQuery($query);
			$attachment_to_copy = $this->db->loadObject();

			if (!empty($attachment_to_copy)) {
				$query->clear()
					->select('count(eua.id)')
					->from($this->db->quoteName('#__emundus_users_attachments', 'eua'))
					->where($this->db->quoteName('eua.attachment_id') . ' = ' . $this->db->quote($attachment_to_copy->attachment_id))
					->andWhere($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($uid));
				$this->db->setQuery($query);
				$attachment_already_copy = $this->db->loadResult();

				if ($attachment_already_copy == 0) {
					$file_dir = "images/emundus/files/" . $uid;
					$root_dir = "images/emundus/files/" . $uid . '/default_attachments';

					if (!file_exists($root_dir)) {
						mkdir($root_dir);
					}

					$file = explode('/', $attachment_to_copy->filename);
					$file = explode('.', end($file));
					$ext  = end($file);
					$file = $file[0];
					$pos  = strrpos($file, '-');
					$file = substr($file, 0, $pos);

					$target_file = $file . '-' . time() . '.' . $ext;

					copy($file_dir . '/' . $attachment_to_copy->filename, $root_dir . '/' . $target_file);
					$columns = array('date_time', 'user_id', 'attachment_id', 'filename', 'published');
					$values  = array(date('Y-m-d H:i:s'), $uid, $attachment_to_copy->attachment_id, $root_dir . '/' . $target_file, 1);

					$query->clear()
						->insert($this->db->quoteName('#__emundus_users_attachments'))
						->columns(implode(',', $this->db->quoteName($columns)))
						->values(implode(',', $this->db->quote($values)));
					$this->db->setQuery($query);
					$this->db->execute();

					JPluginHelper::importPlugin('emundus');

					$this->app->triggerEvent('onAfterProfileAttachmentUpload', [$uid, (int) $attachment_to_copy->attachment_id, $root_dir . '/' . $target_file]);
					$this->app->triggerEvent('onCallEventHandler', ['onAfterProfileAttachmentUpload', ['user_id' => $uid, 'attachment_id' => (int) $attachment_to_copy->attachment_id, 'file' => $root_dir . '/' . $target_file]]);
				}
			}

			return true;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot copy profile document ' . $aid . ' to fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function updateProfilePicture($user_id, $target_file)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update('#__emundus_users')
				->set('profile_picture = ' . $this->db->quote($target_file))
				->where('user_id = ' . $this->db->quote($user_id));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add("com_emundus/models/users.php | Cannot update profile picture for user $user_id :" . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function addApplicantProfile($user_id)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('id,label,published,status')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$app_profile = $this->db->loadResult();

			$query->clear()
				->insert('#__emundus_users_profiles')
				->set($this->db->quoteName('date_time') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id))
				->set($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($app_profile->id));
			$this->db->setQuery($query);
			$this->db->execute();

			return $app_profile;
		}
		catch (Exception $e) {
			Log::add(' com_emundus/models/users.php | Cannot add applicant profile for user ' . $user_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $data must give user_id, email, is_anonym and token
	 * @param $campaign_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function onAfterAnonymUserMapping($data, $campaign_id = 0, $program_code = ''): array
	{
		$message            = '';
		$eMConfig           = JComponentHelper::getParams('com_emundus');
		$allow_anonym_files = $eMConfig->get('allow_anonym_files', false);

		if ($allow_anonym_files) {
			$app     = $this->app;
			$user_id = $data['user_id'];

			if (!empty($user_id)) {
				$profile_id = !empty($data['profile_id']) ? $data['profile_id'] : 1000;


				$query = $this->db->getQuery(true);
				$query->update($this->db->quoteName('#__emundus_users'))
					->set($this->db->quoteName('profile') . ' = ' . $profile_id)
					->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

				try {
					$this->db->setQuery($query);
					$updated = $this->db->execute();
				}
				catch (Exception $e) {
					$updated = false;
					Log::add('Failed to update emundus user profile from user_id ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}

				if ($updated) {
					$app_profile = $this->addApplicantProfile($user_id);

					$query->clear()
						->update('#__user_usergroup_map')
						->set('group_id = ' . 2)
						->where('user_id = ' . $user_id);
					try {
						$this->db->setQuery($query);
						$this->db->execute();
					}
					catch (Exception $e) {
						// catch any database errors.
						Log::add('Failed to update user joomla group ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					$query->clear()
						->update('#__users')
						->set('activation = ' . $this->db->quote(''))
						->set('block = 0')
						->set('params = ' . $this->db->quote(json_encode(array('send_mail' => false))))
						->where('id = ' . $user_id);

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					}
					catch (Exception $e) {
						// catch any database errors.
						Log::add('Failed to update user ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					if (empty($campaign_id)) {
						if (!empty($program_code)) {
							$query->clear()
								->select('id, MAX(year) AS max_year')
								->from('#__emundus_setup_campaigns')
								->where('training = ' . $this->db->quote($program_code))
								->andWhere('published = 1')
								->group('id')
								->order('max_year DESC')
								->setLimit(1);
						}
						else {
							$query->clear()
								->select('id, MAX(year) AS max_year')
								->from('#__emundus_setup_campaigns')
								->where('published = 1')
								->group('id')
								->order('max_year DESC')
								->setLimit(1);
						}

						$this->db->setQuery($query);

						try {
							$result      = $this->db->loadObject();
							$campaign_id = $result->id;
						}
						catch (Exception $e) {
							$campaign_id = 0;
							Log::add('Failed to get campaign ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
							$message = JText::_('COM_EMUNDUS_ANONYM_USERS_ERROR_TRYING_TO_FIND_CAMPAIGN');
						}
					}
					else {
						$query->clear()
							->select('id')
							->from('#__emundus_setup_campaigns')
							->where('id = ' . $campaign_id)
							->andWhere('published = 1');

						try {
							$campaign_id = $this->db->loadResult();
						}
						catch (Exception $e) {
							$campaign_id = 0;
							Log::add('Failed to check campaign existence ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}
					}

					if (!empty($campaign_id)) {
						require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
						$m_files = new EmundusModelFiles();
						$fnum    = $m_files->createFile($campaign_id, $user_id);

						if (!empty($fnum)) {
							$email = $data['email'];

							if (!$data['is_anonym'] && !empty($data['token']) && !empty($email)) {
								$template = $this->app->getTemplate(true);
								$params   = $template->params;
								$config   = JFactory::getConfig();

								if (!empty($params->get('logo')->custom->image)) {
									$logo = json_decode(str_replace("'", "\"", $params->get('logo')->custom->image), true);
									$logo = !empty($logo['path']) ? JURI::base() . $logo['path'] : "";
								}
								else {
									$logo_module = JModuleHelper::getModuleById('90');
									preg_match('#src="(.*?)"#i', $logo_module->content, $tab);
									$pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
                                        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
                                        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
                                        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

									if ((bool) preg_match($pattern, $tab[1])) {
										$tab[1] = parse_url($tab[1], PHP_URL_PATH);
									}

									$logo = JURI::base() . $tab[1];
								}

								require_once(JPATH_ROOT . '/components/com_emundus/controllers/messages.php');
								$c_messages = new EmundusControllerMessages();
								$sent       = $c_messages->sendEmailNoFnum($email, 'anonym_token_email', [
									'SITE_URL'              => JURI::base(),
									'ACTIVATION_ANONYM_URL' => JURI::base() . 'index.php?option=com_emundus&controller=users&task=activation_anonym_user&token=' . $data['token'] . '&user_id=' . $user_id,
									'TOKEN'                 => $data['token'],
									'LOGO'                  => $logo,
									'USER_ID'               => $user_id,
									'PASSWORD'              => $data['password'],
									'SITE_NAME'             => JFactory::getConfig()->get('sitename')
								]);

								if (!$sent) {
									Log::add('Failed to send email to anonym user' . $user_id . ' campaign id :' . $campaign_id, Log::WARNING, 'com_emundus.error');
								}
							}

							$this->login($user_id);
							$user_session         = JFactory::getSession()->get('emundusUser');
							$user_session->id     = $user_id;
							$user_session->anonym = true;
							JFactory::getSession()->set('emundusUser', $user_session);

							if (!empty($user_session->id)) {
								return [
									'status' => true,
									'data'   => [
										'redirect_url' => '/component/emundus/?task=openfile&fnum=' . $fnum,
										'fnum'         => $fnum
									]
								];
							}
							else {
								Log::add('Failed to open session for anonym user' . $user_id . ' campaign id :' . $campaign_id, Log::WARNING, 'com_emundus.error');
								$message = JText::_('COM_EMUNDUS_ANONYM_USERS_CREATE_ANONYM_SESSION_ERROR');
							}
						}
						else {
							Log::add('Failed to create file for anonym user' . $user_id . ' campaign id :' . $campaign_id, Log::WARNING, 'com_emundus.error');
							$message = 'Une erreur est survenue au cours de la création d\'un dossier.';
						}
					}
					else {
						Log::add('Failed to retrieve campaign for anonym user' . $user_id, Log::WARNING, 'com_emundus.error');
						$message = JText::_('COM_EMUNDUS_ANONYM_USERS_NO_CAMPAIGN_FOUND');
					}
				}
				else {
					$message = JText::_('COM_EMUNDUS_ANONYM_USERS_CREATE_ANONYM_SESSION_ERROR');
				}
			}
		}
		else {
			$message = JText::_('ANONYM_FILES_ARE_FORBIDDEN');
			Log::add('Attempt to deposit an anonym file but emundus configuration forbid it.', Log::INFO, 'com_emundus.users');
		}

		return [
			'status'  => false,
			'message' => $message,
			'data'    => []
		];
	}

	/**
	 * Login user from token
	 * Rule: token must have an expiration date
	 *
	 * @param $token
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function connectUserFromToken($token): bool
	{
		$connected = false;
		$app       = $this->app;
		$message   = 'COM_EMUNDUS_USERS_ANONYM_INVALID_KEY';

		if (!empty($token)) {

			$query = $this->db->getQuery(true);

			$query->select('ju.*, jeu.token_expiration')
				->from('#__emundus_users AS jeu')
				->leftJoin('#__users AS ju ON ju.id = jeu.user_id')
				->where('jeu.token = ' . $this->db->quote($token));

			try {
				$this->db->setQuery($query);
				$result = $this->db->loadObject();
			}
			catch (Exception $e) {
				Log::add('Failed to get key from token ' . $token . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				$message = 'COM_EMUNDUS_USERS_ANONYM_FAILED_TO_FOUND_KEY';
			}

			if (!empty($result) && !empty($result->id)) {
				$date = strtotime($result->token_expiration);
				if (time() > $date) {
					$message = 'COM_EMUNDUS_USERS_ANONYM_OUTDATED_KEY ' . date('d/m/Y H/hs', $date);
				}
				else {
					$connected = $this->connectUserFromId($result->id);

					if (!$connected) {
						$message = 'COM_EMUNDUS_USERS_ANONYM_USER_CONNECTION_FAILED';
					}
				}
			}
			else {
				$this->assertNotMaliciousAttemptsUsingConnectViaToken();
				$message = 'COM_EMUNDUS_USERS_ANONYM_UNEXISTING_KEY';
			}
		}

		if (!$connected && !empty($message)) {
			$app->enqueueMessage(JText::_($message), 'error');
		}

		return $connected;
	}

	private function connectUserFromId($user_id): bool
	{
		$connected = false;
		$app       = $this->app;

		$query = $this->db->getQuery(true);

		$jUser    = JFactory::getUser($user_id);
		$instance = $jUser;
		$session  = JFactory::getSession();
		$session->set('user', $jUser);
		$app->checkSession();

		$query->clear()
			->update('#__session')
			->set('guest = 0')
			->set('username = ' . $this->db->quote($instance->get('username')))
			->set('userid = ' . $this->db->quote($instance->get('id')))
			->where('session_id = ' . $this->db->quote($session->getId()));

		$updated = false;
		try {
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('Failed to connect from valid key ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		if ($updated) {
			include_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
			$m_profile = new EmundusModelProfile;
			$m_profile->initEmundusSession();
			$connected = true;
		}

		return $connected;
	}

	/**
	 * Assert user_id and token are related
	 *
	 * @param $token
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function checkTokenCorrespondToUser($token, $user_id): bool
	{
		$correspond = false;

		if (!empty($token) && !empty($user_id)) {

			$query = $this->db->getQuery(true);

			$query->select('id')
				->from('#__emundus_users')
				->where('token = ' . $this->db->quote($token))
				->andWhere('user_id = ' . $user_id)
				->andWhere('token_expiration > NOW()');

			try {
				$this->db->setQuery($query);
				$result = $this->db->loadResult();
			}
			catch (Exception $e) {
				$result = 0;
				Log::add('Failed to retrieve emundus user from token ' . $token . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if (!empty($result)) {
				$correspond = true;
			}
		}

		return $correspond;
	}

	/**
	 * Activate anonym user
	 * Use email_anonym column from emundus_users found from token and user_id
	 * If user with this email already exists, bind files to this existing user
	 * Else update current user anonym infos
	 *
	 * @param $token
	 * @param $user_id
	 *
	 * @return bool updated
	 */
	public function updateAnonymUserAccount($token, $user_id): bool
	{
		$updated = false;

		if (!empty($token) && !empty($user_id)) {

			$query = $this->db->getQuery(true);

			$query->select('*')
				->from('#__emundus_users')
				->where('token = ' . $this->db->quote($token))
				->andWhere('user_id = ' . $user_id)
				->andWhere('token_expiration > NOW()');

			try {
				$this->db->setQuery($query);
				$emundusUser = $this->db->loadObject();
			}
			catch (Exception $e) {
				Log::add('Failed to retrieve emundus user from token ' . $token . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if (!empty($emundusUser) && !empty($emundusUser->user_id)) {
				if ($emundusUser->is_anonym == 0) {
					$query->clear()
						->select('id')
						->from('#__users')
						->where('username = ' . $this->db->quote($emundusUser->email_anonym));

					try {
						$this->db->setQuery($query);
						$existing_user = $this->db->loadResult();
					}
					catch (Exception $e) {
						Log::add('Failed to check if user with same username already exists ' . $emundusUser->email_anonym . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					if (!empty($existing_user)) {
						if ($existing_user == $user_id) {
							$this->app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_ANONYM_NOTHING_TO_UPDATE'));
						}
						else {
							// Copy files to existing user, log to this user and block current anonym user
							$query->clear()
								->select('fnum')
								->from('#__emundus_campaign_candidature')
								->where('applicant_id = ' . $emundusUser->user_id);

							$this->db->setQuery($query);
							$fnums = $this->db->loadColumn();

							if (!empty($fnums)) {
								require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
								$m_files = new EmundusModelFiles();
								$updated = $m_files->bindFilesToUser($fnums, $existing_user);

								if ($updated) {
									$connected = $this->connectUserFromId($existing_user);

									if ($connected) {
										$query->clear()
											->update('#__users')
											->set('block = 1')
											->set('activation = -1')
											->where('id = ' . $user_id);
										$this->db->setQuery($query);
										$this->db->execute();
									}
								}
							}
							else {
								$this->app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_ANONYM_NOTHING_TO_BIND'));
							}
						}
					}
					else {
						$query->clear()
							->update('#__users')
							->set('name = ' . $this->db->quote($emundusUser->lastname_anonym . ' ' . $emundusUser->firstname_anonym))
							->set('username = ' . $this->db->quote($emundusUser->email_anonym))
							->set('email = ' . $this->db->quote($emundusUser->email_anonym))
							->where('id = ' . $emundusUser->user_id);

						try {
							$this->db->setQuery($query);
							$user_updated = $this->db->execute();
						}
						catch (Exception $e) {
							$user_updated = false;
							Log::add('Failed to update user data ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}


						if ($user_updated) {
							$query->clear()
								->update('#__emundus_users')
								->set('lastname = ' . $this->db->quote($emundusUser->lastname_anonym))
								->set('firstname = ' . $this->db->quote($emundusUser->firstname_anonym))
								->where('id = ' . $emundusUser->id);

							try {
								$this->db->setQuery($query);
								$emundus_user_updated = $this->db->execute();
							}
							catch (Exception $e) {
								$emundus_user_updated = false;
								Log::add('Failed to update emundus user data ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
							}

							if ($emundus_user_updated) {
								$updated = true;

								if (JFactory::getUser()->id == $user_id) {
									include_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
									$m_profile = new EmundusModelProfile;
									$m_profile->initEmundusSession();
								}
								else if (JFactory::getUser()->guest == 1) {
									$connected = $this->connectUserFromId($user_id);
								}
							}
						}
					}
				}
				else {
					Log::add('User choose to create file anonymously, can not update without necessary info (at least email)', Log::WARNING, 'com_emundus.error');
				}
			}
		}

		return $updated;
	}

	/**
	 * Retrieve token from user_id
	 * Must stay private to make sure it used in correct context
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	private function getUserToken($user_id): string
	{
		$token = '';

		if (!empty($user_id)) {

			$query = $this->db->getQuery(true);

			$query->select('token')
				->from('#__emundus_users')
				->where('user_id = ' . $user_id);

			try {
				$this->db->setQuery($query);
				$token = $this->db->loadResult();

				if (empty($token)) {
					$token = '';
					Log::add('Existing user does not have token ' . $user_id, Log::INFO, 'com_emundus.anonym');
				}
			}
			catch (Exception $e) {
				$token = '';
				Log::add('Failed to find token from user id ' . $user_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $token;
	}

	/**
	 * Make sure no one can brute force via testing token again and again
	 * Is there too much wrong attempts from same IP in last 24h
	 * If so, block adress IP
	 * @return void
	 */
	private function assertNotMaliciousAttemptsUsingConnectViaToken(): void
	{
		$app        = $this->app;
		$current_ip = $_SERVER['REMOTE_ADDR'];

		if (!empty($current_ip)) {

			$query = $this->db->getQuery(true);

			$query->select('*')
				->from('#__emundus_token_auth_attempts')
				->where('ip = ' . $this->db->quote($current_ip))
				->andWhere('succeed = 0')
				->andWhere('date_time > NOW() - interval 1 day ');

			$this->db->setQuery($query);
			try {
				$failed_attempts = $this->db->loadObjectList();
			}
			catch (Exception $e) {
				$failed_attempts = [];
				Log::add('Failed to detect if wrong attempts already occured with ip ' . $current_ip . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if (sizeof($failed_attempts) > 4) {
				$query->clear()
					->insert('#__securitycheckpro_blacklist')
					->columns('ip')
					->values($this->db->quote($current_ip));

				$this->db->setQuery($query);
				$this->db->execute();

				$app->enqueueMessage(JText::_('COM_EMUNDUS_USERS_TOO_MANY_WRONG_ATTEMPTS'), 'error');
				$app->redirect('/');
			}
			else {

				$app->enqueueMessage(JText::_('COM_EMUNDUS_ANONYM_USERS_ATTEMPTS_BEGIN') . (5 - sizeof($failed_attempts)) . JText::_('COM_EMUNDUS_ANONYM_USERS_ATTEMPTS_END'), 'error');
			}
		}
	}

	/**
	 * Generate a new token for current user
	 * @return string the new token generated, or empty string if failed
	 */
	public function generateUserToken(): string
	{
		$new_token = '';

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/users.php');
		$h_users = new EmundusHelperUsers();
		$token   = $h_users->generateToken();
		$user_id = JFactory::getUser()->id;

		if (!empty($token)) {

			$query = $this->db->getQuery(true);

			$query->update('#__emundus_users')
				->set('token = ' . $this->db->quote($token))
				->set('token_expiration = ' . $this->db->quote(date('Y-m-d H:i:s', strtotime("+1 week"))))
				->where('user_id = ' . $user_id);

			$this->db->setQuery($query);
			try {
				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to generate new token for user ' . $user_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if ($updated) {
				$new_token = $token;
			}
		}

		return $new_token;
	}

	public function isSamlUser($user_id)
	{
		$isSamlUser = false;

		if (!empty($user_id)) {

			$query = $this->db->getQuery(true);

			$query->select('params')
				->from('#__users')
				->where('id = ' . $user_id);

			try {
				$params = $this->db->loadResult();
			}
			catch (Exception $e) {
				$params = '';
				Log::add(' com_emundus/models/users.php | Failed to check if is saml users : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if (!empty($params)) {
				$params = json_decode($params, true);

				$isSamlUser = !empty($params['saml']) && $params['saml'] == 1;
			}
		}

		return $isSamlUser;
	}

	public function getIdentityPhoto($fnum, $applicant_id)
	{
		$attachment_id = ComponentHelper::getParams('com_emundus')->get('photo_attachment', '');

		if(!empty($attachment_id)) {
			try {
				$query = $this->db->getQuery(true);

				$query->select('filename')
					->from($this->db->quoteName('#__emundus_uploads'))
					->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum))
					->andWhere($this->db->quoteName('attachment_id') . ' = ' . $attachment_id);
				$this->db->setQuery($query);
				$filename = $this->db->loadResult();

				if (!empty($filename)) {
					return EMUNDUS_PATH_REL . $applicant_id . '/' . $filename;
				}
			}
			catch (Exception $e) {
				Log::add(' com_emundus/models/users.php | Failed to get identity photo : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

				return '';
			}
		} else {
			return '';
		}
	}

	function randomPassword($len = 8)
	{

		//enforce min length 8
		if ($len < 8)
			$len = 8;

		//define character libraries - remove ambiguous characters like iIl|1 0oO
		$sets   = array();
		$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		$sets[] = '23456789';
		$sets[] = '~!@#$%^&*(){}[],./?';

		$password = '';

		//append a character from each set - gets first 4 characters
		foreach ($sets as $set) {
			$password .= $set[array_rand(str_split($set))];
		}

		//use all characters to fill up to $len
		while (strlen($password) < $len) {
			//get a random set
			$randomSet = $sets[array_rand($sets)];

			//add a random char from the random set
			$password .= $randomSet[array_rand(str_split($randomSet))];
		}

		//shuffle the password string before returning!
		return str_shuffle($password);
	}

	/**
	 * @param $uid
	 * @description Return the label of group(s)'s user in jos_emundus_setup_groupes table
	 * @return array|mixed
	 */
	public function getUserGroupsLabelById($uid)
	{
		$groups_label = [];

		if (!empty($uid)) {
			$query = $this->db->getQuery(true);

			$query->select('esg.label')
				->from($this->db->quoteName('#__emundus_setup_groups', 'esg'))
				->leftJoin($this->db->quoteName('#__emundus_groups', 'eg') . ' ON eg.group_id = esg.id')
				->where($this->db->quoteName('eg.user_id') . ' = ' . $uid);

			try {
				$this->db->setQuery($query);
				$groups_label = $this->db->loadColumn();

			} catch (Exception $e) {
				Log::add('component/com_emundus/models/users | Error when try to get group(s) label : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}
		return $groups_label;
	}

	/**
	 * @description Return all columns of a profile form
	 * @return array|mixed|null
	 */
	public function getColumnsFromProfileForm($ids = []) {

		$columns = [];

		$query = $this->db->getQuery(true);

		$profile_form = $this->getProfileForm();

		if(!empty($profile_form))
		{
			$query->select('fe.id as id, fe.name as name, fe.group_id as group_id, fe.plugin as plugin, fe.label as label, fe.params as params')
				->from($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ff.group_id = fe.group_id')
				->where($this->db->quoteName('ff.form_id') . ' = ' . $profile_form)
				->andWhere($this->db->quoteName('fe.hidden') . ' = ' . '0')
				->andWhere($this->db->quoteName('fe.published') . ' = ' . '1')
				->andWhere($this->db->quoteName('fe.name') . ' <> ' . $this->db->quote('id'));
			if(!empty($ids))
			{
				$query->andWhere($this->db->quoteName('fe.id') . 'IN (' . implode(',', $ids) . ')');
			}

			try
			{
				$this->db->setQuery($query);
				$columns = $this->db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/models/users | Error when try to get form\'s columns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $columns;
	}

	/**
	 * @return object[]
	 * @description Return all the columns of the joomla columns wanted to be exported
	 */
	public function getJoomlaUserColumns()
	{
		return array(
			'id' => (object)array(
				'id' => null,
				'name' => 'id',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_ID',
			),
			'lastname' => (object)array(
				'id' => null,
				'name' => 'lastname',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_LASTNAME',
			),
			'firstname' => (object)array(
				'id' => null,
				'name' => 'firstname',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_FIRSTNAME',
			),
			'email' => (object)array(
				'id' => null,
				'name' => 'email',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_EMAIL',
			),
			'username' => (object)array(
				'id' => null,
				'name' => 'username',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_USERNAME',
			),
			'profile' => (object)array(
				'id' => null,
				'name' => 'profile',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_PROFILE',
			),
			'oprofiles' => (object)array(
				'id' => null,
				'name' => 'oprofiles',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_O_PROFILES',
			),
			'registerDate' => (object)array(
				'id' => null,
				'name' => 'registerDate',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_REGISTERDATE',
			),
			'lastvisitDate' => (object)array(
				'id' => null,
				'name' => 'lastvisitDate',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_LASTVISITDATE',
			),
			'groups' => (object)array(
				'id' => null,
				'name' => 'groups',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_GROUPE',
			),
			'block' => (object)array(
				'id' => null,
				'name' => 'block',
				'plugin' => null,
				'label' => 'COM_EMUNDUS_BLOCK',
			),
		);
	}

	/**
	 * @param $uid
	 * @return array
	 * @description Return an array of 2 elements
	 *  First element concerns the columns form profile
	 *  Second element concerns Joomla user columns (email, etc...)
	 * @throws Exception
	 */
	public function getAllInformationsToExport($uid)
	{
		$data = [];

		if (!empty($uid)) {
			$columns = $this->getColumnsFromProfileForm();

			// Configure the hour according to the location
			if (version_compare(JVERSION, '4.0', '>=')) {
				$config = Factory::getApplication()->getConfig();
			} else {
				$config = Factory::getConfig();
			}
			$offset = $config->get('offset', 'Europe/Paris');
			$timezone = new DateTimeZone($offset);

			// Necessary to retrieve data not available in the profile form
			// (email, username, registerDate of the user, last connexion date of a user, profile and groups)
			$user = $this->getUserById($uid);
			if (!empty($user)) {
				$user = $user[0];

				$user_profile_details = $this->getProfileDetails($user->profile);
				if ($user_profile_details->published == 0) {
					$user_profile = $user_profile_details->label;
				} else {
					$user_profile = Text::_('COM_EMUNDUS_APPLICANT');
				}

				$user_groups = $this->getUserGroupsLabelById($uid);
				$oprofiles = $this->getUserOprofiles($uid);

				if (empty($user_groups)) {
					$user_groups = array();
				}

				$register_date = $user->registerDate ?? '';
				$lastvisit_date = $user->lastvisitDate ?? '';

				// Set the right format date according to the location
				if (!empty($register_date)) {
					$register_date = EmundusHelperDate::displayDate($register_date, 'DATE_FORMAT_LC5', $timezone === 'UTC' ? 1 : 0);
				}
				if (!empty($lastvisit_date)) {
					$lastvisit_date = EmundusHelperDate::displayDate($lastvisit_date, 'DATE_FORMAT_LC5', $timezone === 'UTC' ? 1 : 0);
				}

				// Create an array with array of each data not in the profile form
				// Necessary to be coherent with the data in the profile form
				// We indeed need id, name, plugin, and label at least for each
				$j_columns = $this->getJoomlaUserColumns();

				foreach ($j_columns as $j_column) {
					switch ($j_column->name) {
						case 'id':
							$j_column->value = $user->id ?? '';
							break;
						case 'lastname' :
							$j_column->value = $user->lastname ?? '';
							break;
						case 'firstname' :
							$j_column->value = $user->firstname ?? '';
							break;
						case 'email':
							$j_column->value = $user->email ?? '';
							break;
						case 'username':
							$j_column->value = $user->username ?? '';
							break;
						case 'registerDate':
							$j_column->value = $register_date;
							break;
						case 'lastvisitDate':
							$j_column->value = $lastvisit_date;
							break;
						case 'groups':
							$j_column->value = implode(', ', $user_groups);
							break;
						case 'profile':
							$j_column->value = $user_profile ?? '';
							break;
						case 'oprofiles':
							$j_column->value = implode(', ', $oprofiles);
							break;
						case 'block':
							$j_column->value = Text::_('COM_EMUNDUS_ONBOARD_ACTIVATED');

							if($user->block == 1) {
								$j_column->value = Text::_('COM_EMUNDUS_ONBOARD_BLOCKED');
							}
							elseif($user->activation != 1 && !empty($user->params)) {
								$params = json_decode($user->params, true);
								if(!empty($params['emailactivation_token'])) {
									$j_column->value = Text::_('COM_EMUNDUS_USERS_ACTIVATE_WAITING');
								}
							}
							break;
						default :
							$j_column->value = '';
							break;
					}
				}

				// Create a complete array with all the data we want
				$data = array(
					'columns' => $columns,
					'j_columns' => $j_columns
				);
			}
		}
		return $data;
	}

	/**
	 * @param $uid
	 * @description Return all the details of a user to export in a csv file (profile form and emundus informations)
	 * @return array
	 */
	public function getUserDetails($uid) {
		$user_details = [];

		if(!empty($uid)) {
			$columns = $this->getAllInformationsToExport($uid);
			$user = $this->getUserById($uid);

			if(!empty($user))
			{
				$user = $user[0];

				foreach ($columns['j_columns'] as $field)
				{
					$user_details[$field->label] = $field->value;
				}

				foreach ($columns['columns'] as $column) {
					if (isset($column->value)) {
						$user_details[$column->label] = $column->value;
					} else {
						$user_details[$column->label] = EmundusHelperFabrik::formatElementValue($column->name, $user->{$column->name}, $column->group_id, $uid);
					}
				}
			}
		}
		return $user_details;
	}

	/**
	 * Check if user is already registered in emundus_users, if not, create it
	 * @param $user_id
	 * @return bool true if user is already registered or if we are able to create it, false otherwise
	 */
	public function repairEmundusUser($user_id)
	{
		$repaired = false;

		if (!empty($user_id)) {
			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_users'))
				->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));
			$this->db->setQuery($query);
			$user = $this->db->loadResult();

			if (empty($user)) {
				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__users'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($user_id));
				$this->db->setQuery($query);
				$user_details = $this->db->loadObject();

				if (!empty($user_details)) {
					list($firstname, $lastname) = !empty($user_details->name) ? explode(' ', $user_details->name, 2) : ['',''];

					$no_profile = null;

					$query->clear()
						->select('id')
						->from('#__emundus_setup_profiles')
						->where('label like ' . $this->db->quote('noprofile'));

					$this->db->setQuery($query);
					$no_profile = $this->db->loadResult();

					if (empty($no_profile)) {
						// select first applicant profile
						$query->clear()
							->select('id')
							->from('#__emundus_setup_profiles')
							->where('published = 1');

						$this->db->setQuery($query);
						$no_profile = $this->db->loadResult();
					}

					$query->clear()
						->insert($this->db->quoteName('#__emundus_users'))
						->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id))
						->set($this->db->quoteName('firstname') . ' = ' . $this->db->quote($firstname))
						->set($this->db->quoteName('lastname') . ' = ' . $this->db->quote($lastname))
						->set($this->db->quoteName('profile') . ' = ' . $no_profile)
						->set($this->db->quoteName('schoolyear') . ' = ' . $this->db->quote(''))
						->set($this->db->quoteName('disabled') . ' = 0')
						->set($this->db->quoteName('university_id') . ' = 0')
						->set($this->db->quoteName('email') . ' = ' . $this->db->quote($user_details->email))
						->set($this->db->quoteName('registerDate') . ' = ' . $this->db->quote($user_details->registerDate))
						->set($this->db->quoteName('name') . ' = ' . $this->db->quote($user_details->name));

					try {
						$this->db->setQuery($query);
						$inserted = $this->db->execute();

						if ($inserted) {
							Log::add('com_emundus/models/users.php | reconstruction of user ' . $user_id, Log::INFO, 'com_emundus.error');
							$repaired = true;

							require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
							$m_profile = new EmundusModelProfile;
							$m_profile->initEmundusSession();
						} else {
							Log::add('com_emundus/models/users.php | failed to repair user ' . $user_id, Log::ERROR, 'com_emundus.error');
						}
					} catch (Exception $e) {
						Log::add('com_emundus/models/users.php | error while trying to repair user ' . $user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}
				}
			} else {
				$repaired = true;
			}
		}

		return $repaired;
	}

	public function convertCsvToXls($csv,$nb_cols,$nb_rows,$excel_file_name,$separator = '\t')
	{
		$xls_file = '';

		/** PHPExcel */
		require_once (JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

		$objReader =\PhpOffice\PhpSpreadsheet\IOFactory::createReader("Csv");
		$objReader->setDelimiter($separator);
		$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		// Excel colonne
		$colonne_by_id = array();
		for ($i = ord("A"); $i <= ord("Z"); $i++) {
			$colonne_by_id[]=chr($i);
		}

		for ($i = ord("A"); $i <= ord("Z"); $i++) {
			for ($j = ord("A"); $j <= ord("Z"); $j++) {
				$colonne_by_id[]=chr($i).chr($j);
				if (count($colonne_by_id) == $nb_rows) break;
			}
		}

		// Set properties
		$objPHPExcel->getProperties()->setCreator("eMundus SAS : http://www.emundus.fr/");
		$objPHPExcel->getProperties()->setLastModifiedBy("eMundus SAS");
		$objPHPExcel->getProperties()->setTitle("eMmundus Report");
		$objPHPExcel->getProperties()->setSubject("eMmundus Report");
		$objPHPExcel->getProperties()->setDescription("Report from open source eMundus plateform : http://www.emundus.fr/");
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('Extraction');
		$objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

		$objPHPExcel->getActiveSheet()->freezePane('A2');

		$objReader->loadIntoExisting(JPATH_SITE.DS."tmp".DS.$csv, $objPHPExcel);

		$objConditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
		$objConditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
			->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
			->addCondition('0');
		$objConditional1->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

		$objConditional2 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
		$objConditional2->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
			->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
			->addCondition('100');
		$objConditional2->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF00FF00');

		$objConditional3 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
		$objConditional3->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
			->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
			->addCondition('50');
		$objConditional3->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

		for ($i = 0; $i<$nb_cols; $i++) {
			$value = $objPHPExcel->getActiveSheet()->getCell(Coordinate::stringFromColumnIndex($i) . '1')->getValue();

			if ($value=="forms(%)" || $value=="attachment(%)") {
				$conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i].'1')->getConditionalStyles();
				array_push($conditionalStyles, $objConditional1);
				array_push($conditionalStyles, $objConditional2);
				array_push($conditionalStyles, $objConditional3);
				$objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i].'1')->setConditionalStyles($conditionalStyles);
				$objPHPExcel->getActiveSheet()->duplicateConditionalStyle($conditionalStyles,$colonne_by_id[$i].'1:'.$colonne_by_id[$i].($nb_rows+ 1));
			}
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
		}

		$randomString = UserHelper::genRandomPassword(20);
		$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xlsx");
		$objWriter->save(JPATH_SITE . DS . 'tmp' . DS . $excel_file_name . '_' . $nb_rows . 'rows_' . $randomString . '.xlsx');
		$objPHPExcel->disconnectWorksheets();
		unset($objPHPExcel);

		return $excel_file_name.'_'.$nb_rows.'rows_'.$randomString.'.xlsx';
	}

	public function getGroupsMapping()
	{
		$groups_mapping = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('emundus_groups as id,group_concat(parent_id) as profile_id')
				->from($this->db->quoteName('#__emundus_setup_profiles_repeat_emundus_groups'))
				->group('emundus_groups');
			$this->db->setQuery($query);
			$groups_mapping = $this->db->loadAssocList('id');
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/users | Error when try to get group(s) label : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
		}

		return $groups_mapping;
	}

	public function addUserFromParams($params, $current_user, $send_email = 1) {
		$created = false;

		if (!empty($params['username']) && !empty($params['email'])) {
			$user = clone(JFactory::getUser(0));

			if (preg_match('/^[0-9a-zA-Z\_\@\+\-\.]+$/', $params['username']) !== 1) {
				throw new Exception(JText::_('COM_EMUNDUS_USERS_ERROR_USERNAME_NOT_GOOD'));
			}

			require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
			$h_emails = new EmundusHelperEmails();
			if (!$h_emails->correctEmail($params['email'])) {
				throw new Exception(JText::_('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_EMAIL'));
			}

			$user->name = $params['name'];
			$user->username = $params['username'];
			$user->email = $params['email'];
			if ($params['ldap'] == 0) {
				// If we are creating a new user from the LDAP system, he does not have a password.
				include_once(JPATH_SITE.'/components/com_emundus/helpers/users.php');
				$h_users = new EmundusHelperUsers;
				$password = $h_users->generateStrongPassword();
				$user->password = md5($password);
			}
			$now = EmundusHelperDate::getNow();
			$user->registerDate = $now;
			$user->lastvisitDate = null;
			$user->groups = array($params['jgr']);
			$user->block = 0;

			$other_param['firstname'] 		= $params['firstname'];
			$other_param['lastname'] 		= $params['lastname'];
			$other_param['profile'] 		= $params['profile'];
			$other_param['em_oprofiles'] 	= !empty($params['oprofiles']) ? explode(',', $params['oprofiles']): $params['oprofiles'];
			$other_param['univ_id'] 		= $params['univ_id'];
			$other_param['em_groups'] 		= !empty($params['groups']) ? explode(',', $params['groups']): $params['groups'];
			$other_param['em_campaigns'] 	= !empty($params['campaigns']) ? explode(',', $params['campaigns']): $params['campaigns'];
			$other_param['news'] 			= $params['news'];

			$acl_aro_groups = $this->getDefaultGroup($params['profile']);
			$user->groups = $acl_aro_groups;

			$usertype = $this->found_usertype($acl_aro_groups[0]);
			$user->usertype = $usertype;

			$uid = $this->adduser($user, $other_param);

			if (is_array($uid)) {
				throw new Exception(JText::_('COM_EMUNDUS_USERS_ERROR'));
			} else if (empty($uid)) {
				throw new Exception($user->getError());
			}

			// If index.html does not exist, create the file otherwise the process will stop with the next step
			if (!file_exists(EMUNDUS_PATH_ABS.'index.html')) {
				$filename = EMUNDUS_PATH_ABS.'index.html';
				$file = fopen($filename, 'w');
				fwrite($file, '');
				fclose($file);
			}

			if (!mkdir(EMUNDUS_PATH_ABS.$uid, 0755) || !copy(EMUNDUS_PATH_ABS.'index.html', EMUNDUS_PATH_ABS.$uid.DS.'index.html')) {
				throw new Exception(JText::_('COM_EMUNDUS_USERS_CANT_CREATE_USER_FOLDER_CONTACT_ADMIN'));
			}

			if ($send_email) {
				// Envoi de la confirmation de création de compte par email
				if (!class_exists('EmundusModelEmails')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
				}
				$m_emails = new EmundusModelEmails();

				$email = $params['ldap'] == 1 ? 'new_ldap_account' : 'new_account';
				$pswd = $params['ldap'] == 0 ? $password : null;
				$post = $params['ldap'] == 0 ? array('PASSWORD' => $pswd) : array();

				$sent = $m_emails->sendEmailNoFnum($user->email, $email, $post, $user->id, [], null, false);

				if (!$sent) {
					throw new Exception(JText::_('COM_EMUNDUS_MAILS_EMAIL_NOT_SENT'));
				}
			}

			$created = true;
		}

		return $created;
	}
}
