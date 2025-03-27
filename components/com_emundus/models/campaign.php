<?php
/**
 * @package        Joomla
 * @subpackage     eMundus
 * @link           http://www.emundus.fr
 * @copyright      Copyright (C) 2018 eMundus. All rights reserved.
 * @license        GNU/GPL
 * @author         Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;

require_once(JPATH_SITE. '/components/com_emundus/helpers/menu.php');

/**
 * Emundus Component Campaign Model
 *
 * @since  1.0.0
 */
class EmundusModelCampaign extends ListModel
{
	/**
	 * @var \Joomla\CMS\Application\CMSApplication|\Joomla\CMS\Application\CMSApplicationInterface|null
	 * @since version 2.0.0
	 */
	private $app;

	/**
	 * @var mixed
	 * @since version 1.0.0
	 */
	private $_em_user;

	/**
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $_user;

	/**
	 * @var JDatabaseDriver|\Joomla\Database\DatabaseDriver|null
	 * @since version 1.0.0
	 */
	protected $_db;

	/**
	 * @var \Joomla\Registry\Registry
	 * @since version 2.0.0
	 */
	private $config;

	function __construct()
	{
		parent::__construct();
		global $option;

		Log::addLogger([
			'text_file'         => 'com_emundus.campaign.error.php',
			'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
		],
			Log::ALL,
			array('com_emundus')
		);

		$this->app = Factory::getApplication();

		$this->_db      = $this->getDatabase();
		$this->_em_user = $this->app->getSession()->get('emundusUser');
		$this->_user    = $this->app->getIdentity();
		$this->config   = $this->app->getConfig();

		// Get pagination request variables
		$filter_order     = $this->app->getUserStateFromRequest($option . 'filter_order', 'filter_order', 'label', 'cmd');
		$filter_order_Dir = $this->app->getUserStateFromRequest($option . 'filter_order_Dir', 'filter_order_Dir', 'desc', 'word');
		$limit            = $this->app->getUserStateFromRequest('global.list.limit', 'limit', $this->app->get('list_limit'), 'int');
		$limitstart       = $this->app->getUserStateFromRequest('global.list.limitstart', 'limitstart', 0, 'int');
		$limitstart       = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('filter_order', $filter_order);
		$this->setState('filter_order_Dir', $filter_order_Dir);
		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);

		if (!class_exists('HtmlSanitizerSingleton')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
	}

	/**
	 * Get active campaign
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getActiveCampaign()
	{
		$query = $this->_buildQuery();
		$query .= $this->_buildContentOrderBy();

		return $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
	}

	/**
	 * Build query to get campaign
	 *
	 * @return string
	 *
	 * @since version v6
	 */
	function _buildQuery()
	{
		$timezone = new DateTimeZone($this->config->get('offset'));
		$now      = Factory::getDate()->setTimezone($timezone);

		return 'SELECT id, label, year, description, start_date, end_date
		FROM #__emundus_setup_campaigns
		WHERE published = 1 AND ' . $this->_db->Quote($now) . '>=start_date AND ' . $this->_db->Quote($now) . '<=end_date';
	}

	/**
	 * Build Content with order by
	 *
	 * @return string
	 *
	 * @since version v6
	 */
	function _buildContentOrderBy()
	{
		$orderby          = '';
		$filter_order     = $this->getState('filter_order');
		$filter_order_Dir = $this->getState('filter_order_Dir');

		$can_be_ordering = array('id', 'label', 'year', 'start_date', 'end_date');
		if (!empty($filter_order) && !empty($filter_order_Dir) && in_array($filter_order, $can_be_ordering)) {
			$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;
		}

		return $orderby;
	}

	/**
	 * Get allowed campaigns by user and depending of eMundus params
	 *
	 * @param $uid
	 *
	 * @return array
	 *
	 * @since version v6
	 */
	function getAllowedCampaign($uid = null): array
	{
		$allowed_campaigns = [];

		if (empty($uid)) {
			$uid = $this->_user->id;
		}

		$query = $this->_buildQuery();

		if (!empty($uid)) {
			require_once(JPATH_SITE. '/components/com_emundus/models/profile.php');
			$m_profile           = new EmundusModelProfile();
			$userProfiles        = $m_profile->getUserProfiles($uid);
			$userEmundusProfiles = $m_profile->getProfileByApplicant($uid);

			$newObjectProfiles = (object) array(
				'id'        => $userEmundusProfiles['profile'],
				'label'     => $userEmundusProfiles['profile_label'],
				'published' => $userEmundusProfiles['published'],
				'status'    => $userEmundusProfiles['status'],
			);

			$userProfiles[] = $newObjectProfiles;

			$eMConfig            = JComponentHelper::getParams('com_emundus');
			$applicant_can_renew = $eMConfig->get('applicant_can_renew', '0');
			$id_profiles         = $eMConfig->get('id_profiles', '0');
			$id_profiles         = explode(',', $id_profiles);

			foreach ($userProfiles as $profile) {
				if (in_array($profile->id, $id_profiles)) {
					$applicant_can_renew = 1;
					break;
				}
			}

			switch ($applicant_can_renew) {
				// Applicant can only have one file per campaign.
				case 2:
					$query .= ' AND id NOT IN (
								select campaign_id
								from #__emundus_campaign_candidature
								where applicant_id=' . $uid . ' and published <> -1
							)';
					break;
				// Applicant can only have one file per year.
				case 3:
					$query .= ' AND year NOT IN (
								select sc.year
								from #__emundus_campaign_candidature as cc
								LEFT JOIN #__emundus_setup_campaigns as sc ON sc.id = cc.campaign_id
								where cc.applicant_id=' . $uid . ' and cc.published <> -1
							)';
					break;
			}
		}

		try {
			$this->_db->setQuery($query);
			$allowed_campaigns = array_column($this->_db->loadAssocList(), 'id');
		}
		catch (Exception $e) {
			Log::add('Error at model/campaign -> query: ' . $query, Log::ERROR, 'com_emundus.error');
			$allowed_campaigns = [];
		}

		if (!empty($allowed_campaigns)) {
			foreach ($allowed_campaigns as $cid => $campaign) {
				if ($this->isLimitObtained($cid)) {
					unset($allowed_campaigns[$cid]);
				}
			}
		}

		return $allowed_campaigns;
	}

	/**
	 * Get campaigns by my applicant_id
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getMyCampaign()
	{
		$query = $this->_db->getQuery(true);

		$query->select('esc.*')
			->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('ecc.campaign_id'))
			->where($this->_db->quoteName('ecc.applicant_id') . ' = ' . $this->_db->quote($this->_em_user->id))
			->order('ecc.date_submitted DESC');
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $campaign_id
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignByID($campaign_id)
	{
		$campaign = [];

		if (!empty($campaign_id)) {
			$query = $this->_db->getQuery(true);
			$query->select('*')
				->from('#__emundus_setup_campaigns AS esc')
				->where('esc.id = ' . $campaign_id)
				->order('esc.end_date DESC');

			$this->_db->setQuery($query);

			try {
				$campaign = $this->_db->loadAssoc();
			}
			catch (Exception $e) {
				Log::add('Failed to retrieve campaign from id ' . $campaign_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $campaign;
	}

	/**
	 * @param   bool  $published
	 *
	 * @return array|mixed
	 *
	 * @since version v6
	 */
	function getAllCampaigns($published = true)
	{
		$all_campaigns = [];

		$query = $this->_db->getQuery(true);
		$query->select(['tu.*'])
			->from($this->_db->quoteName('#__emundus_setup_teaching_unity', 'tu'));

		if ($published) {
			$query->where($this->_db->quoteName('tu.published') . ' = 1');
		}

		try {
			$this->_db->setQuery($query);
			$all_campaigns = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting campaigns at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
		}

		return $all_campaigns;
	}

	/**
	 * @param $campaign_id
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getProgrammeByCampaignID($campaign_id)
	{
		$program = [];

		if (!empty($campaign_id)) {
			$campaign = $this->getCampaignByID($campaign_id);

			if (!empty($campaign)) {
				$query = 'SELECT esp.*
					FROM #__emundus_setup_programmes AS esp
					WHERE esp.code like "' . $campaign['training'] . '"';
				$this->_db->setQuery($query);
				$program = $this->_db->loadAssoc();
			}
		}

		return $program;
	}

	/**
	 * @param $training
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getProgrammeByTraining($training)
	{
		$program = null;

		if (!empty($training)) {
			$query = 'SELECT esp.*
					FROM #__emundus_setup_programmes AS esp
					WHERE esp.code like "' . $training . '"';
			$this->_db->setQuery($query);

			$program = $this->_db->loadObject();
		}

		return $program;
	}

	/**
	 * @param $course
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignsByCourse($course)
	{
		$query = 'SELECT esc.*
					FROM #__emundus_setup_campaigns AS esc
					WHERE esc.training like ' . $this->_db->Quote($course) . ' ORDER BY esc.end_date DESC';
		$this->_db->setQuery($query);

		return $this->_db->loadAssoc();
	}

	/**
	 * @param $code
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignsByProgram($code)
	{
		$campaigns = [];

		if (!empty($code)) {
			$query = $this->_db->createQuery();

			$query->select('esc.*')
				->from('#__emundus_setup_campaigns AS esc')
				->where('esc.training = ' . $this->_db->quote($code))
				->order('esc.end_date DESC');

			try {
				$this->_db->setQuery($query);
				$campaigns = $this->_db->loadObjectList();
			} catch (Exception $e) {
				Log::add('Error getting campaigns by program at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.campaign.error');
			}
		}

		return $campaigns;
	}

	/**
	 * @param $course
	 * @param $camp
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignsByCourseCampaign($course, $camp)
	{
		$query = 'SELECT esc.*
				FROM #__emundus_setup_campaigns AS esc
				WHERE esc.training like ' . $this->_db->Quote($course) . ' AND esc.id like ' . $this->_db->Quote($camp);

		$this->_db->setQuery($query);

		return $this->_db->loadAssoc();
	}

	/**
	 * @param $course
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	static function getLastCampaignByCourse($course)
	{
		$db = JFactory::getDBO();

		$query = 'SELECT esc.*
					FROM #__emundus_setup_campaigns AS esc
					WHERE published=1 AND esc.training like ' . $db->Quote($course) . ' ORDER BY esc.end_date DESC';
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getMySubmittedCampaign()
	{
		$query = 'SELECT esc.*
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = ecc.campaign_id
					WHERE esc.applicant_id=' . $this->_em_user->id . 'AND ecc.submitted=1
					ORDER BY ecc.date_submitted DESC';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $aid
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignByApplicant($aid)
	{
		$query = 'SELECT esc.*,ecc.fnum, esp.menutype, esp.label as profile_label
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = ecc.campaign_id
					LEFT JOIN #__emundus_setup_profiles AS esp ON esp.id = esc.profile_id
					WHERE ecc.applicant_id=' . $aid . '
					ORDER BY ecc.date_time DESC';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $fnum
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignByFnum($fnum)
	{
		$query = $this->_db->getQuery(true);

		$query->clear()
			->select('esc.*,ecc.fnum, esp.menutype, esp.label as profile_label')
			->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('ecc.campaign_id'))
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('esc.profile_id'))
			->where($this->_db->quoteName('ecc.fnum') . ' = ' . $this->_db->quote($fnum))
			->order('ecc.date_time DESC');
		$this->_db->setQuery($query);

		return $this->_db->loadObject();
	}

	/**
	 * @param $aid
	 *
	 * @return mixed
	 *
	 * @since version v6
	 */
	function getCampaignSubmittedByApplicant($aid)
	{
		$query = 'SELECT esc.*
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = ecc.campaign_id
					WHERE esc.applicant_id=' . $aid . 'AND submitted=1
					ORDER BY ecc.date_submitted DESC';
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * @param $cid
	 * @param $aid
	 *
	 *
	 * @since version v6
	 */
	function setSelectedCampaign($cid, $aid)
	{

		$query = 'INSERT INTO `#__emundus_campaign_candidature` (`applicant_id`, `campaign_id`, `fnum`)
		VALUES (' . $aid . ', ' . $cid . ', CONCAT(DATE_FORMAT(NOW(),\'%Y%m%d%H%i%s\'),LPAD(`campaign_id`, 7, \'0\'),LPAD(`applicant_id`, 7, \'0\')))';
		$this->_db->setQuery($query);
		try {
			$this->_db->Query();
		}
		catch (Exception $e) {
			Log::add('Error getting selected campaign ' . $cid . ' at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query), Log::ERROR, 'com_emundus.error');
		}
	}

	/**
	 * @param $aid
	 * @param $campaign_id
	 *
	 *
	 * @since version v6
	 */
	function setResultLetterSent($aid, $campaign_id)
	{
		$query = 'UPDATE #__emundus_final_grade SET result_sent=1, date_result_sent=NOW() WHERE student_id=' . $aid . ' AND campaign_id=' . $campaign_id;
		$this->_db->setQuery($query);
		try {
			$this->_db->Query();
		}
		catch (Exception $e) {
			// catch any database errors.
		}
	}

	/**
	 * @param $aid
	 *
	 * @return bool
	 *
	 * @since version v6
	 */
	function isOtherActiveCampaign($aid)
	{
		$query = 'SELECT count(id) as cpt
				FROM #__emundus_setup_campaigns
				WHERE id NOT IN (
								select campaign_id FROM #__emundus_campaign_candidature WHERE applicant_id=' . $aid . '
								)';
		$this->_db->setQuery($query);
		$cpt = $this->_db->loadResult();

		return $cpt > 0;
	}

	/**
	 *
	 * @return JPagination
	 *
	 * @since version v6
	 */
	function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination($this->getTotal(), $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_pagination;
	}

	/**
	 *
	 * @return false|int
	 *
	 * @since version v6
	 */
	function getTotal()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_total)) {
			$query        = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	/**
	 *
	 * @return array|mixed
	 *
	 * @since version v6
	 */
	function getCampaignsXLS()
	{
		$db    = JFactory::getDBO();
		$query = 'SELECT cc.id, cc.applicant_id, sc.start_date, sc.end_date, sc.label, sc.year
		FROM #__emundus_setup_campaigns AS sc
		LEFT JOIN #__emundus_campaign_candidature AS cc ON cc.campaign_id = sc.id
		WHERE sc.published=1';

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Method to create a new compaign for all active programmes.
	 *
	 * @param   array  $data        The data to use as campaign definition.
	 * @param   array  $programmes  The list of programmes who need a new campaign.
	 *
	 * @return  String  Does it work.
	 * @since version v6
	 */
	public function addCampaignsForProgrammes($data, $programmes)
	{
		$data['date_time'] = date("Y-m-d H:i:s");
		$data['user']      = $this->_user->id;
		$data['label']     = '';
		$data['training']  = '';
		$data['published'] = 1;

		if (!empty($data) && !empty($programmes)) {
			$column = array_keys($data);

			$values       = array();
			$values_unity = array();
			$result       = '';
			foreach ($programmes as $v) {
				try {
					$query = 'SELECT count(id) FROM `#__emundus_setup_campaigns` WHERE year LIKE ' . $this->_db->Quote($data['year']) . ' AND  training LIKE ' . $this->_db->Quote($v['code']);
					$this->_db->setQuery($query);
					$cpt = $this->_db->loadResult();

					if ($cpt == 0) {
						$values[]       = '(' . $this->_db->Quote($data['start_date']) . ', ' . $this->_db->Quote($data['end_date']) . ', ' . $data['profile_id'] . ', ' . $this->_db->Quote($data['year']) . ', ' . $this->_db->Quote($data['short_description']) . ', ' . $this->_db->Quote($data['date_time']) . ', ' . $data['user'] . ', ' . $this->_db->Quote($v['label']) . ', ' . $this->_db->Quote($v['code']) . ', ' . $data['published'] . ')';
						$values_unity[] = '(' . $this->_db->Quote($v['code']) . ', ' . $this->_db->Quote($v['label']) . ', ' . $this->_db->Quote($data['year']) . ', ' . $data['profile_id'] . ', ' . $this->_db->Quote($v['programmes']) . ')';

						$result .= '<i class="green check circle outline icon"></i> ' . $v['label'] . ' [' . $data['year'] . '] [' . $v['code'] . '] ' . JText::_('CREATED') . '<br>';
					}
					else {
						$result .= '<i class="orange remove circle outline icon"></i> ' . $v['label'] . ' [' . $data['year'] . '] [' . $v['code'] . '] ' . JText::_('ALREADY_EXIST') . '<br>';
					}
				}
				catch (Exception $e) {
					Log::add($e->getMessage(), Log::ERROR, 'com_emundus.error');

					return $e->getMessage();
				}
			}

			try {
				if (!empty($values)) {
					$query = 'INSERT INTO `#__emundus_setup_campaigns` (`' . implode('`, `', $column) . '`) VALUES ' . implode(',', $values);
					$this->_db->setQuery($query);
					$this->_db->execute();

					$query = 'INSERT INTO `#__emundus_setup_teaching_unity` (`code`, `label`, `schoolyear`, `profile_id`, `programmes`) VALUES ' . implode(',', $values_unity);
					$this->_db->setQuery($query);
					$this->_db->execute();
				}
			}
			catch (Exception $e) {
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.error');

				return $e->getMessage();
			}
		}
		else {
			return false;
		}

		return $result;
	}

	/**
	 * Gets the most recent campaign programme code.
	 * @return string The most recent campaign programme in the DB.
	 *
	 * @since version v6
	 */
	function getLatestCampaign()
	{
		$latestCampaign = '';

		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('training'))
			->from($this->_db->quoteName('#__emundus_setup_campaigns'))
			->order('id DESC')
			->setLimit('1');

		try {
			$this->_db->setQuery($query);
			$latestCampaign = $this->_db->loadResult();
		}
		catch (Exception $e) {
			Log::add('Error getting latest programme at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
		}

		return $latestCampaign;
	}


	/**
	 * Gets all elements in teaching unity table
	 * @return array
	 *
	 * @since version v6
	 */
	function getCCITU()
	{
		$query = $this->_db->getQuery(true);

		$query->select(['tu.*', 'p.prerequisite', 'p.audience', 'p.objectives', 'p.content', 'p.manager_firstname', 'p.manager_lastname', 'p.pedagogie', 't.label AS thematique', 'p.id AS row_id'])
			->from($this->_db->quoteName('#__emundus_setup_teaching_unity', 'tu'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'p') . ' ON ' . $this->_db->quoteName('tu.code') . ' LIKE ' . $this->_db->quoteName('p.code'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_thematiques', 't') . ' ON ' . $this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('p.programmes'))
			->where($this->_db->quoteName('tu.published') . ' = 1 AND ' . $this->_db->quoteName('p.published') . ' = 1');

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting latest programme at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');

			return [];
		}
	}

	/**
	 * @param   null  $id
	 *
	 * @return array|mixed
	 *
	 * @since version v6
	 */
	function getTeachingUnity($id = null)
	{
		$response = [];

		$query = $this->_db->getQuery(true);
		$query->select(['tu.*'])
			->from($this->_db->quoteName('#__emundus_setup_teaching_unity', 'tu'));

		if (!empty($id) && is_numeric($id)) {
			$query->where($this->_db->quoteName('tu.id') . ' = ' . $id);
		}

		try {
			$this->_db->setQuery($query);
			$response = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting latest programme at model/campaign at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
		}

		return $response;
	}

	/**
	 * Get campaign limit params
	 *
	 * @param $id
	 *
	 * @return Object|mixed
	 *
	 * @since 1.2.0
	 *
	 */
	public function getLimit($id)
	{
		$query = $this->_db->getQuery(true);

		$query
			->select([$this->_db->quoteName('esc.is_limited'), $this->_db->quoteName('esc.limit'), 'GROUP_CONCAT(escrl.limit_status) AS steps'])
			->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns_repeat_limit_status', 'escrl') . ' ON ' . $this->_db->quoteName('escrl.parent_id') . ' = ' . $this->_db->quoteName('esc.id'))
			->where($this->_db->quoteName('esc.id') . ' = ' . $id);

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $exception) {
			Log::add('Error getting campaign limit at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');

			return null;
		}

	}

	/**
	 * Check if campaign's limit is obtained
	 *
	 * @param int $campaign_id
	 * @param string fnum, if not empty, check if fnum is in the list of candidature defined in the limit steps
	 *               if it is, return true
	 *
	 * @return bool
	 *
	 * @since 1.2.0
	 *
	 */
	public function isLimitObtained($campaign_id, string $fnum = ''): bool
	{
		$is_limit_obtained = false;

		if (EmundusHelperAccess::isApplicant($this->_user->id) && !empty($campaign_id)) {
			$limit = $this->getLimit($campaign_id);

			if (!empty($limit->is_limited) && !empty($limit->limit)) {
				$query = $this->_db->getQuery(true);

				$query->select('COUNT(id)')
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('status') . ' IN (' . $limit->steps . ')')
					->andWhere($this->_db->quoteName('campaign_id') . ' = ' . $campaign_id)
					->andWhere($this->_db->quoteName('published').' = 1');

				try {
					$this->_db->setQuery($query);
					$is_limit_obtained = ($limit->limit <= $this->_db->loadResult());
				}
				catch (Exception $exception) {
					Log::add('Error checking obtained limit at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
				}

				if (!empty($fnum)) {
					// is fnum in the list of candidature defined in the limit steps ?
					$query = $this->_db->getQuery(true);
					$query->clear()
						->select('id')
						->from($this->_db->quoteName('#__emundus_campaign_candidature'))
						->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum))
						->andWhere($this->_db->quoteName('campaign_id') . ' = ' . $campaign_id)
						->andWhere($this->_db->quoteName('status') . ' IN (' . $limit->steps . ')')
						->andWhere($this->_db->quoteName('published').' = 1');

					try {
						$this->_db->setQuery($query);
						$is_limit_obtained = ($this->_db->loadResult() > 0) ? false : $is_limit_obtained;
					}
					catch (Exception $exception) {
						Log::add('Error checking if fnum is in limit status ' . $exception->getMessage(), Log::ERROR, 'com_emundus.error');
					}
				}
			}
		}

		return $is_limit_obtained;
	}

	/**
	 * Get associated campaigns
	 *
	 * @param $filter
	 * @param $sort
	 * @param $recherche
	 * @param $lim
	 * @param $page
	 * @param $program
	 *
	 * @return array|mixed|stdClass
	 *
	 * @since version 1.0
	 */
	function getAssociatedCampaigns($filter = '', $sort = 'DESC', $recherche = '', $lim = 25, $page = 0, $program = 'all', $session = 'all', $order_by = 'sc.id')
	{
		$associated_campaigns = [];

		$query = $this->_db->getQuery(true);

		if (empty($lim) || $lim == 'all') {
			$limit = '';
		} else {
			$limit = $lim;
		}

		if (empty($page) || empty($limit)) {
			$offset = 0;
		}
		else {
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort)) {
			$sort = 'DESC';
		}
		$date = new Date();

		// Get affected programs
		require_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
		$m_programme = new EmundusModelProgramme;
		$programs    = $m_programme->getUserPrograms($this->_user->id);

		if (!empty($programs)) {
			if ($program != "all") {
				$programs = array_filter($programs, function ($value) use ($program) {
					return $value == $program;
				});
			}
			//

			$filterDate = null;
			if ($filter == 'yettocome') {
				$filterDate = 'Date(' . $this->_db->quoteName('sc.start_date') . ') > ' . $this->_db->quote($date);
			}
			elseif ($filter == 'ongoing') {
				$filterDate =
					'(Date(' .
					$this->_db->quoteName('sc.end_date') .
					')' .
					' >= ' .
					$this->_db->quote($date) .
					' OR end_date = "0000-00-00 00:00:00") AND ' .
					$this->_db->quoteName('sc.start_date') .
					' <= ' .
					$this->_db->quote($date);
			}
			elseif ($filter == 'Terminated') {
				$filterDate =
					'Date(' .
					$this->_db->quoteName('sc.end_date') .
					')' .
					' <= ' .
					$this->_db->quote($date) .
					' AND end_date != "0000-00-00 00:00:00"';
			}
			elseif ($filter == 'Publish') {
				$filterDate = $this->_db->quoteName('sc.published') . ' = 1';
			}
			elseif ($filter == 'Unpublish') {
				$filterDate = $this->_db->quoteName('sc.published') . ' = 0';
			}

			$fullRecherche = null;
			if (!empty($recherche)) {
				$fullRecherche = '(' .
					$this->_db->quoteName('sc.label') .
					' LIKE ' .
					$this->_db->quote('%' . $recherche . '%') . ')';
				$fullRecherche .= ' OR (' .
					$this->_db->quoteName('sp.label') .
					' LIKE ' .
					$this->_db->quote('%' . $recherche . '%') . ')';
			}

			$query->select([
				'sc.*',
				'COUNT(cc.id) as nb_files',
				'sp.label AS program_label',
				'sp.id AS program_id',
				'sp.published AS published_prog'
			])
				->from($this->_db->quoteName('#__emundus_setup_campaigns', 'sc'))
				->leftJoin(
					$this->_db->quoteName('#__emundus_campaign_candidature', 'cc') .
					' ON ' .
					$this->_db->quoteName('cc.campaign_id') .
					' = ' .
					$this->_db->quoteName('sc.id') .
					' AND ' .
					$this->_db->quoteName('cc.published') . ' = 1'
				)
				->leftJoin(
					$this->_db->quoteName('#__emundus_setup_programmes', 'sp') .
					' ON ' .
					$this->_db->quoteName('sp.code') .
					' LIKE ' .
					$this->_db->quoteName('sc.training')
				)
				->leftJoin(
					$this->_db->quoteName('#__users', 'u') .
					' ON ' .
					$this->_db->quoteName('u.id') .
					' = ' .
					$this->_db->quoteName('cc.applicant_id') .
					' AND ' .
					$this->_db->quoteName('u.block') . ' = 0'
				);

			$query->where($this->_db->quoteName('sc.training') . ' IN (' . implode(',', $this->_db->quote($programs)) . ')');

			if (!empty($filterDate)) {
				$query->andWhere($filterDate);
			}
			if (!empty($fullRecherche)) {
				$query->andWhere($fullRecherche);
			}
			if ($session !== 'all') {
				$query->andWhere($this->_db->quoteName('year') . ' = ' . $this->_db->quote($session));
			}
			$query->group('sc.id')
				->order($order_by . ' ' . $sort);

			try {
				$this->_db->setQuery($query);
				$campaigns_count = sizeof($this->_db->loadObjectList());

				$this->_db->setQuery($query, $offset, $limit);
				$campaigns = $this->_db->loadObjectList();

				if (empty($campaigns) && $offset != 0) {
					return $this->getAssociatedCampaigns($filter, $sort, $recherche, $lim, 0, $program, $session);
				}
				$associated_campaigns = array('datas' => $campaigns, 'count' => $campaigns_count);
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when try to get list of campaigns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $associated_campaigns;
	}

	/**
	 * Get campaigns by program id
	 *
	 * @param $program
	 *
	 * @return array|mixed|stdClass
	 *
	 * @since version 1.0
	 */
	function getCampaignsByProgramId($program)
	{
		$campaigns = [];

		if (!empty($program)) {
			$query = $this->_db->getQuery(true);
			$date  = new Date();

			$query->select('sc.*')
				->from($this->_db->quoteName('#__emundus_setup_programmes', 'sp'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->_db->quoteName('sp.code') . ' LIKE ' . $this->_db->quoteName('sc.training'))
				->where($this->_db->quoteName('sp.id') . ' = ' . $this->_db->quote($program))
				->andWhere($this->_db->quoteName('sc.end_date') . ' >= ' . $this->_db->quote($date));

			try {
				$this->_db->setQuery($query);
				$campaigns = $this->_db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when try to get campaigns associated to programs : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $campaigns;
	}

	/**
	 * Delete a campaign
	 *
	 * @param         $data
	 * @param   bool  $force_delete  - if true, delete campaign even if it has files, and delete files too
	 *                               Force delete is only available for super admin users because it can be dangerous
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function deleteCampaign($data, $force_delete = false)
	{
		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		$deleted = false;

		if (!empty($data)) {
			$data = !is_array($data) ? [$data] : $data;

			require_once(JPATH_ROOT . '/components/com_emundus/models/falang.php');
			$falang = new EmundusModelFalang();

			$onBeforeCampaignDeleteEventHandler = new GenericEvent(
				'onCallEventHandler',
				['onBeforeCampaignDelete',
					// Datas to pass to the event
					['campaign' => $data]
				]
			);
			$onBeforeCampaignDelete = new GenericEvent(
				'onBeforeCampaignDelete',
				// Datas to pass to the event
				['campaign' => $data]
			);
			$dispatcher->dispatch('onCallEventHandler', $onBeforeCampaignDeleteEventHandler);
			$dispatcher->dispatch('onBeforeCampaignDelete', $onBeforeCampaignDelete);

			$query = $this->_db->getQuery(true);

			try {

				foreach (array_values($data) as $id) {
					$falang->deleteFalang($id, 'emundus_setup_campaigns', 'label');
				}

				if ($force_delete === true) {
					$query->delete($this->_db->quoteName('#__emundus_campaign_candidature'))
						->where($this->_db->quoteName('campaign_id') . ' IN (' . implode(", ", array_values($data)) . ')');

					$this->_db->setQuery($query);
					$this->_db->execute();

					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_campaigns'))
						->where($this->_db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')');

					$this->_db->setQuery($query);
					$deleted = $this->_db->execute();

					if($deleted) {
						foreach ($data as $key => $val) {
							$details_menu = $this->getCampaignDetailsMenu($val);
							if(!empty($details_menu)) {
								$query->clear()
									->delete($this->_db->quoteName('#__menu'))
									->where($this->_db->quoteName('id').' = '.$details_menu->id);
								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}

					Log::add('User ' . JFactory::getUser()->id . ' deleted campaign(s) ' . implode(", ", array_values($data)) . ' ' . date('d/m/Y H:i:s'), Log::INFO, 'com_emundus');
				}
				else {
					// delete only if there are no files attached to the campaign
					$query->clear()
						->select('count(*)')
						->from($this->_db->quoteName('#__emundus_campaign_candidature'))
						->where($this->_db->quoteName('campaign_id') . ' IN (' . implode(", ", array_values($data)) . ')');

					$this->_db->setQuery($query);
					$nb_files = $this->_db->loadResult();

					if ($nb_files < 1) {
						$query->clear()
							->update($this->_db->quoteName('#__emundus_setup_campaigns'))
							->set($this->_db->quoteName('published') . ' = 0')
							->where($this->_db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')');

						$this->_db->setQuery($query);
						$deleted = $this->_db->execute();
					}
				}

				if ($deleted) {
					$onAfterCampaignDeleteEventHandler = new GenericEvent(
						'onCallEventHandler',
						['onAfterCampaignDelete',
							// Datas to pass to the event
							['campaign' => $data]
						]
					);
					$onAfterCampaignDelete = new GenericEvent(
						'onAfterCampaignDelete',
						// Datas to pass to the event
						['campaign' => $data]
					);
					$dispatcher->dispatch('onCallEventHandler', $onAfterCampaignDeleteEventHandler);
					$dispatcher->dispatch('onAfterCampaignDelete', $onAfterCampaignDelete);
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when delete campaigns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $deleted;
	}

	public function unpublishCampaign(string|array $data): bool
	{
		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		$unpublished = false;

		if (!empty($data)) {
			if (!is_array($data)) {
				$data = [$data];
			}

			$query = $this->_db->getQuery(true);
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($val);
			}

			$onBeforeCampaignUnpublishEventHandler = new GenericEvent(
				'onCallEventHandler',
				['onBeforeCampaignUnpublish',
					// Datas to pass to the event
					['campaign' => $data]
				]
			);
			$onBeforeCampaignUnpublish = new GenericEvent(
				'onBeforeCampaignUnpublish',
				// Datas to pass to the event
				['campaign' => $data]
			);
			$dispatcher->dispatch('onCallEventHandler', $onBeforeCampaignUnpublishEventHandler);
			$dispatcher->dispatch('onBeforeCampaignUnpublish', $onBeforeCampaignUnpublish);

			try {
				$fields        = [
					$this->_db->quoteName('published') . ' = 0'
				];
				$sc_conditions = [
					$this->_db->quoteName('id') . ' IN (' . implode(',', array_values($data)) . ')'
				];

				$query->update($this->_db->quoteName('#__emundus_setup_campaigns'))
					->set($fields)
					->where($sc_conditions);

				$this->_db->setQuery($query);
				$unpublished = $this->_db->execute();

				if ($unpublished) {
					foreach ($data as $key => $val) {
						$details_menu = $this->getCampaignDetailsMenu($val);

						if (!empty($details_menu)) {
							$update = [
								'id'        => $details_menu->id,
								'published' => 0
							];
							$update = (object) $update;
							$this->_db->updateObject('#__menu', $update, 'id');
						}
					}

					$onAfterCampaignUnpublishEventHandler = new GenericEvent(
						'onCallEventHandler',
						['onAfterCampaignUnpublish',
							// Datas to pass to the event
							['campaign' => $data]
						]
					);
					$onAfterCampaignUnpublish = new GenericEvent(
						'onAfterCampaignUnpublish',
						// Datas to pass to the event
						['campaign' => $data]
					);
					$dispatcher->dispatch('onCallEventHandler', $onAfterCampaignUnpublishEventHandler);
					$dispatcher->dispatch('onAfterCampaignUnpublish', $onAfterCampaignUnpublish);
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when unpublish campaigns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $unpublished;
	}

	/**
	 *
	 * @param $data
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function publishCampaign($data)
	{
		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		$published = false;

		if (!empty($data)) {
			if (!is_array($data)) {
				$data = [$data];
			}

			$query = $this->_db->getQuery(true);
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($val);
			}

			$onBeforeCampaignPublishEventHandler = new GenericEvent(
				'onCallEventHandler',
				['onBeforeCampaignPublish',
					// Datas to pass to the event
					['campaign' => $data]
				]
			);
			$onBeforeCampaignPublish = new GenericEvent(
				'onBeforeCampaignPublish',
				// Datas to pass to the event
				['campaign' => $data]
			);
			$dispatcher->dispatch('onCallEventHandler', $onBeforeCampaignPublishEventHandler);
			$dispatcher->dispatch('onBeforeCampaignPublish', $onBeforeCampaignPublish);

			try {
				$fields        = [$this->_db->quoteName('published') . ' = 1'];
				$sc_conditions = [$this->_db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')'];

				$query->update($this->_db->quoteName('#__emundus_setup_campaigns'))
					->set($fields)
					->where($sc_conditions);

				$this->_db->setQuery($query);
				$published = $this->_db->execute();

				if ($published) {
					foreach ($data as $key => $val) {
						$details_menu = $this->getCampaignDetailsMenu($val);

						if(!empty($details_menu)) {
							$update = [
								'id' => $details_menu->id,
								'published' => 1
							];
							$update = (object) $update;
							$this->_db->updateObject('#__menu', $update, 'id');
						}
					}

					$onAfterCampaignPublishEventHandler = new GenericEvent(
						'onCallEventHandler',
						['onAfterCampaignPublish',
							// Datas to pass to the event
							['campaign' => $data]
						]
					);
					$onAfterCampaignPublish = new GenericEvent(
						'onAfterCampaignPublish',
						// Datas to pass to the event
						['campaign' => $data]
					);
					$dispatcher->dispatch('onCallEventHandler', $onAfterCampaignPublishEventHandler);
					$dispatcher->dispatch('onAfterCampaignPublish', $onAfterCampaignPublish);
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when publish campaigns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $published;
	}

	/**
	 * @param $data
	 *
	 * @return false|mixed|string
	 *
	 * @since version 1.0
	 */
	public function duplicateCampaign($id)
	{
		$duplicated = false;

		if (!empty($id)) {
			$query = $this->_db->getQuery(true);

			try {
				$columns = array_keys(
					$this->_db->getTableColumns('#__emundus_setup_campaigns')
				);

				$columns = array_filter($columns, function ($k) {
					return $k != 'id' && $k != 'date_time' && $k != 'pinned';
				});

				$query->clear()
					->select(implode(',', $this->_db->qn($columns)))
					->from($this->_db->quoteName('#__emundus_setup_campaigns'))
					->where($this->_db->quoteName('id') . ' = ' . $id);

				$this->_db->setQuery($query);
				$values = $this->_db->loadAssoc();

				foreach($values as $key => $value) {
					if ($value == '') {
						unset($values[$key]);
						$columns = array_diff($columns, [$key]);
					}
				}

				$values = implode(',', $this->_db->q(array_values($values)));

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_setup_campaigns'))
					->columns(implode(',', $this->_db->quoteName($columns)))
					->values($values);

				$this->_db->setQuery($query);
				$duplicated = $this->_db->execute();

				if ($duplicated) {
					$new_campaign_id = $this->_db->insertid();

					if (!empty($new_campaign_id)) {
						$new_category_id = $this->getCampaignCategory($new_campaign_id);

						if (!empty($new_category_id)) {
							if (mkdir(JPATH_ROOT.'/media/com_dropfiles/' . $new_category_id, 0755)) {
								$old_category_id = $this->getCampaignCategory($id);
								$old_campaign_documents = $this->getCampaignDropfilesDocuments($old_category_id);

								if (!empty($old_campaign_documents)) {
									foreach($old_campaign_documents as $document) {
										$document->catid = $new_category_id;
										$document->author = $this->_user->id;

										$columns = array_keys($this->_db->getTableColumns('#__dropfiles_files'));
										$columns = array_filter($columns, function ($k) {return $k != 'id';});

										$values = '';
										foreach ($columns as $column) {
											$values .= $this->_db->quote($document->$column) . ', ';
										}
										$values = rtrim($values, ', ');

										$query->clear()
											->insert($this->_db->quoteName('#__dropfiles_files'))
											->columns(implode(',', $this->_db->quoteName($columns)))
											->values($values);

										$this->_db->setQuery($query);
										$this->_db->execute();

										// Copy documents on server
										$old_path = JPATH_ROOT.'/media/com_dropfiles/' . $old_category_id . '/' . $document->file;
										$new_path = JPATH_ROOT.'/media/com_dropfiles/' . $new_category_id . '/' . $document->file;
										copy($old_path, $new_path);
									}
								}
							}
						}
					}
				}
			} catch (Exception $e) {
				error_log($query->__toString() . $e->getMessage());
				Log::add('component/com_emundus/models/campaign | Error when duplicate campaigns : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $duplicated;
	}

	//TODO Throw in the years model

	/**
	 *
	 * @return array|mixed
	 *
	 * @since version 1.0
	 */
	function getYears()
	{
		$years = [];

		$query = $this->_db->getQuery(true);
		$query->select('DISTINCT(tu.schoolyear)')
			->from($this->_db->quoteName('#__emundus_setup_teaching_unity', 'tu'))
			->order('tu.id DESC');

		try {
			$this->_db->setQuery($query);
			$years = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add(preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
		}

		return $years;
	}

	/**
	 * @param $data
	 *
	 * @return int campaign_id, 0 if failed
	 *
	 * @since version 1.0
	 */
	public function createCampaign($data, $user_id = 0)
	{
		$campaign_id = 0;

		if (!empty($data) && !empty($data['label'])) {
			if(empty($user_id)) {
				$user_id = $this->app->getIdentity()->id;
			}

			require_once(JPATH_SITE . '/components/com_emundus/models/settings.php');
			require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
			require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
			$m_settings = new EmundusModelSettings;
			$m_emails   = new EmundusModelEmails;
			$m_form   = new EmundusModelForm;

			if (version_compare(JVERSION, '4.0', '>')) {
				$lang = $this->app->getLanguage();
			}
			else {
				$lang = Factory::getLanguage();
			}

			$actualLanguage = !empty($lang->getTag()) ? substr($lang->getTag(), 0, 2) : 'fr';

			$eMConfig = ComponentHelper::getParams('com_emundus');
			$create_default_program_trigger = $eMConfig->get('create_default_program_trigger', 1);

			$i            = 0;
			$labels       = new stdClass();
			$limit_status = [];

			$query = "SELECT DISTINCT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'jos_emundus_setup_campaigns'";
			$this->_db->setQuery($query);
			$campaign_columns = $this->_db->loadColumn();

			$data['label'] = is_string($data['label']) ? json_decode($data['label'], true) : $data['label'];

			$this->app->triggerEvent('onBeforeCampaignCreate', $data);
			$this->app->triggerEvent('onCallEventHandler', ['onBeforeCampaignCreate', ['campaign' => $data]]);

			$query = $this->_db->getQuery(true);

			$campaign_languages = [];
			foreach ($data as $key => $val) {
				if (!in_array($key, $campaign_columns)) {
					if ($key == 'languages') {
						$campaign_languages = $val;
					}

					unset($data[$key]);
				}
				else {
					if ($key == 'profileLabel') {
						unset($data['profileLabel']);
					}
					if ($key == 'label') {
						$labels->fr    = !empty($data['label']['fr']) ? $data['label']['fr'] : '';
						$labels->en    = !empty($data['label']['en']) ? $data['label']['en'] : '';
						$data['label'] = $data['label'][$actualLanguage];
					}
					if ($key == 'description' && $data['description'] == 'null') {
						$data['description'] = '';
					}
					if ($key == 'limit_status') {
						$limit_status = $data['limit_status'];
						unset($data['limit_status']);
					}
					if ($key == 'profile_id' && empty($data['profile_id'])) {
						$forms = $m_form->getAllFormsPublished($user_id, 'id', SORT_DESC);

						if(!empty($forms)) {
							$data['profile_id'] = $forms[0]->id;
						}

						if (empty($data['profile_id'])) {
							$data['profile_id'] = 1000;
						}
					}
					if($key == 'start_date' || $key == 'end_date'){
						$dateStr = str_replace(' ', 'T', $val);
						$date = new DateTime($dateStr);
						$data[$key] = $date->format('Y-m-d H:i:s');
					}
				}
			}

			$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
			if (isset($data['description'])) {
				$data['description'] = $htmlSanitizer->sanitizeFor('section', $data['description']);
			}
			if (isset($data['short_description'])) {
				$data['short_description'] = $htmlSanitizer->sanitizeFor('section', $data['short_description']);
			}

			if (!empty($data['label'])) {
				$query->clear()
					->insert($this->_db->quoteName('#__emundus_setup_campaigns'))
					->columns($this->_db->quoteName(array_keys($data)))
					->values(implode(',', $this->_db->Quote(array_values($data))));

				try {
					$this->_db->setQuery($query);
					$inserted = $this->_db->execute();

					if ($inserted) {
						$campaign_id = $this->_db->insertid();
						if (!empty($campaign_id)) {
							if ($data['is_limited'] == 1) {
								foreach ($limit_status as $key => $limit_statu) {
									if ($limit_statu == 'true') {
										$query->clear()
											->insert($this->_db->quoteName('#__emundus_setup_campaigns_repeat_limit_status'));
										$query->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($campaign_id))
											->set($this->_db->quoteName('limit_status') . ' = ' . $this->_db->quote($key));
										$this->_db->setQuery($query);
										$this->_db->execute();
									}
								}
							}

							if (!empty($campaign_languages))
							{
								foreach($campaign_languages as $language) {
									$query->clear()
										->insert($this->_db->quoteName('#__emundus_setup_campaigns_languages'))
										->set($this->_db->quoteName('campaign_id') . ' = ' . $this->_db->quote($campaign_id))
										->set($this->_db->quoteName('lang_id') . ' = ' . $this->_db->quote($language));

									$this->_db->setQuery($query);
									$this->_db->execute();
								}
							}

							$m_settings->onAfterCreateCampaign();

							// Create a default trigger
							if (!empty($data['training']) && !empty($create_default_program_trigger)) {
								$query->clear()
									->select('id')
									->from($this->_db->quoteName('#__emundus_setup_programmes'))
									->where($this->_db->quoteName('code') . ' LIKE ' . $this->_db->quote($data['training']));
								$this->_db->setQuery($query);
								$pid = $this->_db->loadResult();

								if (!empty($pid)) {
									$emails = $m_emails->getTriggersByProgramId($pid);

									if (empty($emails)) {
										$trigger = array(
											'status'        => 1,
											'model'         => 1,
											'action_status' => 'to_current_user',
											'target'        => -1,
											'program'       => $pid,
										);
										$m_emails->createTrigger($trigger, $this->_user);
									}
								}
							}

							// Create teaching unity
							$this->createYear($data);

							// Create menu item with alias
							if(!empty($data['alias']))
							{
								$this->createCampaignAlias($campaign_id, $data['alias'], $data['label']);
							}


							PluginHelper::importPlugin('emundus');

							JFactory::getApplication()->triggerEvent('onAfterCampaignCreate', ['campaign_id' => $campaign_id]);
							JFactory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterCampaignCreate', ['campaign' => $campaign_id]]);
						}
					}
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/campaign | Error when create the campaign : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

					error_log($query->__toString() . ' -> ' . $e->getMessage());
					return $e->getMessage();
				}
			}
		}

		return $campaign_id;
	}

	/**
	 * @param $data
	 * @param $cid
	 *
	 * @return bool|string
	 *
	 * @since version 1.0
	 */
	public function updateCampaign($data, $cid)
	{
		$updated = false;

		if (!empty($data) && !empty($cid)) {
			if (empty($data['start_date']) || empty($data['end_date'])) {
				return $updated;
			}

			$query = $this->_db->getQuery(true);

			require_once(JPATH_ROOT . '/components/com_emundus/models/falang.php');
			require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');

			$app = JFactory::getApplication();
			$m_falang       = new EmundusModelFalang;
			$lang           = $app->getLanguage();
			$actualLanguage = substr($lang->getTag(), 0, 2);

			$limit_status = [];
			$fields       = [];
			$columns      = [];
			$keys_to_unset = ['limit_status', 'profileLabel', 'progid', 'status', 'languages'];
			$labels       = new stdClass;

			$app->triggerEvent('onBeforeCampaignUpdate', $data);
			$app->triggerEvent('onCallEventHandler', ['onBeforeCampaignUpdate', ['campaign' => $cid]]);

			foreach ($data as $key => $val) {
				if ($val === '' || is_null($val)) {
					$keys_to_unset[] = $key;
					continue;
				}

				if (!in_array($key, $keys_to_unset))
				{
					$columns[] = $this->_db->quoteName($key);
				} else {
					continue;
				}

				switch ($key) {
					case 'label':
						$labels        = $data['label'];
						$data['label'] = $data['label'][$actualLanguage];
						$fields[]      = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($data['label']);
						break;
					case 'limit_status':
						$limit_status = $data['limit_status'];
						break;
					case 'eval_start_date':
					case 'eval_end_date':
					case 'admission_start_date':
					case 'admission_end_date':
						if (empty($val)) {
							$val = '0000-00-00 00:00:00';
						}
						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
						break;
					case 'profileLabel':
					case 'progid':
					case 'status':
					case 'languages':
						// do nothing
						break;
					case 'alias':
						$details_menu = $this->getCampaignDetailsMenu($cid);
						if(!empty($details_menu)) {
							$query->clear()
								->update($this->_db->quoteName('#__menu'))
								->set($this->_db->quoteName('alias') . ' = ' . $this->_db->quote($val))
								->set($this->_db->quoteName('path') . ' = ' . $this->_db->quote($val))
								->where($this->_db->quoteName('id') . ' = ' . $details_menu->id);
							$this->_db->setQuery($query);
							$this->_db->execute();
						} else {
							$this->createCampaignAlias($cid, $val, $data['label']);
						}

						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
						break;
					case 'profile_id':
						if (empty($val)) {
							$val = 1000;
						}

						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
						break;
					case 'limit':
					case 'pinned':
					case 'is_limited':
						if (!isset($val) || $val == '') {
							$val = 0;
						}
						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
						break;
					case 'description':
					case 'short_description':
						$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
						$val = $htmlSanitizer->sanitizeFor('section', $val);

						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);

						break;
					default:
						$fields[] = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
						break;
				}
			}

			$query->clear()
				->select(implode(',',$columns))
				->from($this->_db->quoteName('#__emundus_setup_campaigns'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($cid));
			$this->_db->setQuery($query);
			$old_data = $this->_db->loadAssoc();

			if (!empty($data['label'])) {
				$m_falang->updateFalang($labels, $cid, 'emundus_setup_campaigns', 'label');
			}

			$query->clear()
				->update($this->_db->quoteName('#__emundus_setup_campaigns'))
				->set($fields)
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($cid));

			try {
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();

				if ($updated) {
					Log::add('User ' . Factory::getApplication()->getIdentity()->id . ' updated campaign ' . $cid . ' ' . date('d/m/Y H:i:s') . ' query ' . $query->__toString(), Log::INFO, 'com_emundus.campaign');

					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_campaigns_repeat_limit_status'))
						->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($cid));
					$this->_db->setQuery($query);
					$this->_db->execute();

					if ($data['is_limited'] == 1) {
						foreach ($limit_status as $key => $limit_statu) {
							if ($limit_statu == 'true') {
								$query->clear()
									->insert($this->_db->quoteName('#__emundus_setup_campaigns_repeat_limit_status'))
									->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($cid))
									->set($this->_db->quoteName('limit_status') . ' = ' . $this->_db->quote($key));

								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}

					// update campaign languages
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_campaigns_languages'))
						->where($this->_db->quoteName('campaign_id') . ' = ' . $this->_db->quote($cid));

					$this->_db->setQuery($query);
					$this->_db->execute();

					if(!empty($data['languages'])) {
						foreach ($data['languages'] as $lang_id) {
							$query->clear()
								->insert('#__emundus_setup_campaigns_languages')
								->set('campaign_id = ' . $cid)
								->set('lang_id = ' . $lang_id);

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}

					// update campaign languages
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_campaigns_languages'))
						->where($this->_db->quoteName('campaign_id') . ' = ' . $this->_db->quote($cid));

					$this->_db->setQuery($query);
					$this->_db->execute();

					if(!empty($data['languages'])) {
						foreach ($data['languages'] as $lang_id) {
							$query->clear()
								->insert('#__emundus_setup_campaigns_languages')
								->set('campaign_id = ' . $cid)
								->set('lang_id = ' . $lang_id);

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}

					$this->createYear($data);

					foreach ($keys_to_unset as $key) {
						unset($data[$key]);
					}

					$dispatcher = Factory::getApplication()->getDispatcher();
					$onAfterCampaignUpdateEventHandler = new GenericEvent(
						'onCallEventHandler',
						['onAfterCampaignUpdate',
							// Datas to pass to the event
							['campaign' => $cid]
						]
					);
					$onAfterCampaignUpdate             = new GenericEvent(
						'onAfterCampaignUpdate',
						// Datas to pass to the event
						['data' => $data, 'old_data' => $old_data]
					);
					$dispatcher->dispatch('onCallEventHandler', $onAfterCampaignUpdateEventHandler);
					$dispatcher->dispatch('onAfterCampaignUpdate', $onAfterCampaignUpdate);
				}
				else {
					Log::add('Attempt to update $campaign ' . $cid . ' with data ' . json_encode($data) . ' failed.', Log::WARNING, 'com_emundus.error');
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error when update the campaign : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	/**
	 * @param $data
	 * @param $profile
	 *
	 * @return bool|string
	 *
	 * @since version 1.0
	 */
	public function createYear($data, $profile = null)
	{
		$created = false;

		$prid = !empty($profile) ? $profile : $data['profile_id'];

		if (!empty($prid)) {
			$query = $this->_db->getQuery(true);

			try {
				// Check if teaching unity does not already exists
				$query->select('count(id)')
					->from($this->_db->quoteName('#__emundus_setup_teaching_unity'))
					->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($prid))
					->andWhere($this->_db->quoteName('schoolyear') . ' = ' . $this->_db->quote($data['year']))
					->andWhere($this->_db->quoteName('code') . ' = ' . $this->_db->quote($data['training']));
				$this->_db->setQuery($query);
				$teaching_unity_exist = $this->_db->loadResult();

				if ($teaching_unity_exist == 0) {
					$query->clear()
						->insert($this->_db->quoteName('#__emundus_setup_teaching_unity'))
						->set($this->_db->quoteName('code') . ' = ' . $this->_db->quote($data['training']))
						->set($this->_db->quoteName('label') . ' = ' . $this->_db->quote($data['label']))
						->set($this->_db->quoteName('schoolyear') . ' = ' . $this->_db->quote($data['year']))
						->set($this->_db->quoteName('published') . ' = 1')
						->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($prid))
						->set($this->_db->quoteName('date_start') . ' = ' . $this->_db->quote($data['start_date']))
						->set($this->_db->quoteName('date_end') . ' = ' . $this->_db->quote($data['end_date']));
					$this->_db->setQuery($query);
					$created = $this->_db->execute();
				}
				else {
					$created = true;
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error at year creation : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $created;
	}

	/**
	 * @param $id
	 *
	 * @return false|stdClass
	 *
	 * @since version 1.0
	 */
	public function getCampaignDetailsById($id)
	{
		if (empty($id)) {
			return false;
		}

		require_once(JPATH_ROOT . '/components/com_emundus/models/falang.php');
		$m_falang = new EmundusModelFalang;

		$query = $this->_db->getQuery(true);

		$results = new stdClass();

		try {
			$query->select(['sc.*', 'spr.label AS profileLabel', 'sp.id as progid'])
				->from($this->_db->quoteName('#__emundus_setup_campaigns', 'sc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'spr') . ' ON ' . $this->_db->quoteName('spr.id') . ' = ' . $this->_db->quoteName('sc.profile_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->_db->quoteName('sp.code') . ' = ' . $this->_db->quoteName('sc.training'))
				->where($this->_db->quoteName('sc.id') . ' = ' . $id);

			$this->_db->setQuery($query);
			$results->campaign = $this->_db->loadObject();
			$results->label    = $m_falang->getFalang($id, 'emundus_setup_campaigns', 'label');

			if ($results->campaign->is_limited == 1) {
				$query->clear()
					->select('limit_status')
					->from($this->_db->quoteName('#__emundus_setup_campaigns_repeat_limit_status'))
					->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($results->campaign->id));
				$this->_db->setQuery($query);
				$results->campaign->status = $this->_db->loadObjectList();
			}

			$query->clear()
				->select('*')
				->from($this->_db->quoteName('#__emundus_setup_programmes'))
				->where($this->_db->quoteName('code') . ' LIKE ' . $this->_db->quote($results->campaign->training));
			$this->_db->setQuery($query);
			$results->program = $this->_db->loadObject();

			return $results;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Error at getting the campaign by id ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 *
	 * @return false|mixed|null
	 *
	 * @since version 1.0
	 */
	public function getCreatedCampaign()
	{
		$query = $this->_db->getQuery(true);

		$currentDate = date('Y-m-d H:i:s');

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_setup_campaigns'))
			->where($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote($currentDate));

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Error at getting the campaign created today : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $profile
	 * @param $campaign
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function updateProfile($profile, $campaign)
	{
		$updated = false;

		if (!empty($profile) && !empty($campaign)) {
			$query = $this->_db->getQuery(true);
			$query->select('id, label, year, training, profile_id')
				->from($this->_db->quoteName('#__emundus_setup_campaigns'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($campaign));

			try {
				$this->_db->setQuery($query);
				$old_data = $this->_db->loadAssoc();

				$query->clear()
					->update($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
					->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($profile))
					->where($this->_db->quoteName('campaign_id') . ' = ' . $this->_db->quote($campaign));
				$this->_db->setQuery($query);
				$this->_db->execute();

				// Create checklist menu if documents are asked
				$query->clear()
					->select('*')
					->from($this->_db->quoteName('#__menu'))
					->where($this->_db->quoteName('alias') . ' = ' . $this->_db->quote('checklist-' . $profile));
				$this->_db->setQuery($query);
				$checklist = $this->_db->loadObject();

				if ($checklist == null) {
					require_once(JPATH_SITE. '/components/com_emundus/models/form.php');
					$m_form = new EmundusModelForm;
					$m_form->addChecklistMenu($profile);
				}

				$query = $this->_db->getQuery(true);
				$query->update($this->_db->quoteName('#__emundus_setup_campaigns'))
					->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($profile))
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($campaign));

				$this->_db->setQuery($query);
				$this->_db->execute();

				// Create teaching unity
				$this->createYear($old_data, $profile);
				//

				$new_data = $old_data;
				$new_data['profile_id'] = $profile;

				$app = JFactory::getApplication();
				$app->triggerEvent('onAfterCampaignUpdate', [$new_data, $old_data]);
				$app->triggerEvent('onCallEventHandler', ['onAfterCampaignUpdate', ['campaign' => $campaign]]);

				$updated = true;
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error at updating setup_profile of the campaign: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	/**
	 * Get campaigns without applicant files
	 *
	 * @return array|mixed
	 *
	 * @since version 1.0
	 */
	public function getCampaignsToAffect()
	{
		$campaigns = [];

		// Get campaigns that don't have applicant files
		$query = 'select sc.id,sc.label 
                  from jos_emundus_setup_campaigns as sc
                  where (
                    select count(cc.id)
                    from jos_emundus_campaign_candidature as cc
                    left join jos_emundus_users as u on u.id = cc.applicant_id
                    where cc.campaign_id = sc.id
                    and u.profile NOT IN (2,4,5,6)
                  ) = 0';


		try {
			$this->_db->setQuery($query);
			$campaigns = $this->_db->loadObjectList();
		} catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Error getting campaigns without setup_profiles associated: ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
		}

		return $campaigns;
	}

	/**
	 * @param $term
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function getCampaignsToAffectByTerm($term)
	{
		$campaigns_to_affect = [];

		$query = $this->_db->getQuery(true);
		$date  = new Date();

		// Get affected programs
		require_once(JPATH_SITE. '/components/com_emundus/models/programme.php');

		$m_programme = new EmundusModelProgramme;
		$programs    = $m_programme->getUserPrograms($this->_user->id);

		if (!empty($programs)) {
			$searchName = $this->_db->quoteName('label') . ' LIKE ' . $this->_db->quote('%' . $term . '%');

			$query->select('id,label')
				->from($this->_db->quoteName('#__emundus_setup_campaigns'))
				->where($this->_db->quoteName('profile_id') . ' IS NULL')
				->andWhere($this->_db->quoteName('end_date') . ' >= ' . $this->_db->quote($date))
				->andWhere($searchName)
				->andWhere($this->_db->quoteName('training') . ' IN (' . implode(',', $this->_db->quote($programs)) . ')');

			try {
				$this->_db->setQuery($query);
				$campaigns_to_affect = $this->_db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Error getting campaigns without setup_profiles associated with search terms : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $campaigns_to_affect;
	}

	/**
	 * @param $document
	 * @param $types
	 * @param $cid
	 * @param $pid
	 *
	 * @return array
	 *
	 * @since version 1.0
	 */
	public function createDocument($document, $types, $pid)
	{
		$created = [
			'status' => false,
			'msg'    => JText::_('ERROR_CANNOT_ADD_DOCUMENT')
		];

		if (empty($pid)) {
			$created['msg'] = 'Missing profile id';
		}
		else {
			$query          = $this->_db->getQuery(true);
			$lang           = JFactory::getLanguage();
			$actualLanguage = substr($lang->getTag(), 0, 2);
			$types          = implode(";", array_values($types));

			if (empty($document['name'][$actualLanguage]) || empty($types)) {
				$created['msg'] = 'Missing name or types';
			}
			else {
				$query
					->insert($this->_db->quoteName('#__emundus_setup_attachments'));

				$query
					->set($this->_db->quoteName('lbl') . ' = ' . $this->_db->quote('_em'))
					->set($this->_db->quoteName('value') . ' = ' . $this->_db->quote($document['name'][$actualLanguage]))
					->set($this->_db->quoteName('description') . ' = ' . $this->_db->quote($document['description'][$actualLanguage]))
					->set($this->_db->quoteName('allowed_types') . ' = ' . $this->_db->quote($types))
					->set($this->_db->quoteName('ordering') . ' = ' . $this->_db->quote(0))
					->set($this->_db->quoteName('nbmax') . ' = ' . $this->_db->quote($document['nbmax']));

				/// insert image resolution if image is found
				if ($document['minResolution'] != null and $document['maxResolution'] != null) {
					if (empty($document['minResolution']['width']) or (int) $document['minResolution']['width'] == 0) {
						$document['minResolution']['width'] = 'null';
					}

					if (empty($document['minResolution']['height']) or (int) $document['minResolution']['height'] == 0) {
						$document['minResolution']['height'] = 'null';
					}

					if (empty($document['maxResolution']['width']) or (int) $document['maxResolution']['width'] == 0) {
						$document['maxResolution']['width'] = 'null';
					}

					if (empty($document['maxResolution']['height']) or (int) $document['maxResolution']['height'] == 0) {
						$document['maxResolution']['height'] = 'null';
					}

					$query
						->set($this->_db->quoteName('min_width') . ' = ' . $document['minResolution']['width'])
						->set($this->_db->quoteName('min_height') . ' = ' . $document['minResolution']['height'])
						->set($this->_db->quoteName('max_width') . ' = ' . $document['maxResolution']['width'])
						->set($this->_db->quoteName('max_height') . ' = ' . $document['maxResolution']['height']);
				}

				try {
					require_once(JPATH_ROOT . '/components/com_emundus/models/falang.php');
					$m_falang = new EmundusModelFalang;
					$this->_db->setQuery($query);
					$this->_db->execute();
					$newdocument = $this->_db->insertid();
					$m_falang->insertFalang($document['name'], $newdocument, 'emundus_setup_attachments', 'value');
					$m_falang->insertFalang($document['description'], $newdocument, 'emundus_setup_attachments', 'description');

					$query
						->clear()
						->update($this->_db->quoteName('#__emundus_setup_attachments'))
						->set($this->_db->quoteName('lbl') . ' = ' . $this->_db->quote('_em' . $newdocument))
						->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($newdocument));
					$this->_db->setQuery($query);
					$this->_db->execute();
					$query->clear()
						->select('max(ordering)')
						->from($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
						->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid));
					$this->_db->setQuery($query);
					$ordering = $this->_db->loadResult();

					$query->clear()
						->insert($this->_db->quoteName('#__emundus_setup_attachment_profiles'));

					$query->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid))
						->set($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($newdocument))
						->set($this->_db->quoteName('mandatory') . ' = ' . $this->_db->quote($document['mandatory']))
						->set($this->_db->quoteName('ordering') . ' = ' . $this->_db->quote($ordering + 1));
					$this->_db->setQuery($query);
					$this->_db->execute();
					$created['status'] = $newdocument;
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/campaign | Cannot create a document : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
					$created['msg'] = $e->getMessage();
				}
			}
		}

		return $created;
	}

	/**
	 * @param $document
	 * @param $types
	 * @param $did
	 * @param $pid
	 *
	 * @return bool|string
	 *
	 * @since version 1.0
	 */
	public function updateDocument($document, $types, $did, $pid, $params = [])
	{
		$query = $this->_db->getQuery(true);

		$lang           = JFactory::getLanguage();
		$actualLanguage = substr($lang->getTag(), 0, 2);

		require_once(JPATH_ROOT . '/components/com_emundus/models/falang.php');
		$m_falang = new EmundusModelFalang;

		$types = array_values($types);
		// If video and mp4 in types check addpipe status : remove video if not enabled else remove mp4
		if (in_array('video', $types) || in_array('mp4', $types)) {
			$addpipe = ComponentHelper::getParams('com_emundus')->get('addpipe_activation', 0);
			if ($addpipe == 0) {
				$types = array_diff($types, ['video']);
			} else {
				$types = array_diff($types, ['mp4']);
			}
		}
		$types = implode(";", $types);

		$query->update($this->_db->quoteName('#__emundus_setup_attachments'));
		$query->set($this->_db->quoteName('value') . ' = ' . $this->_db->quote($document['name'][$actualLanguage]))
			->set($this->_db->quoteName('description') . ' = ' . $this->_db->quote($document['description'][$actualLanguage]))
			->set($this->_db->quoteName('allowed_types') . ' = ' . $this->_db->quote($types))
			->set($this->_db->quoteName('nbmax') . ' = ' . $this->_db->quote($document['nbmax']));

		/// many cases
		if (isset($document['minResolution'])) {

			/// isset + !empty - !is_null === !empty (just it)
			if (!empty($document['minResolution']['width'])) {
				$query->set($this->_db->quoteName('min_width') . ' = ' . $document['minResolution']['width']);
			}
			else {
				$query->set($this->_db->quoteName('min_width') . ' = null');
			}

			/// isset + !empty - !is_null === !empty (just it)
			if (!empty($document['minResolution']['height'])) {
				$query->set($this->_db->quoteName('min_height') . ' = ' . $document['minResolution']['height']);
			}
			else {
				$query->set($this->_db->quoteName('min_height') . ' = null');
			}
		}
		else {
			$query->set($this->_db->quoteName('min_width') . ' = null')
				->set($this->_db->quoteName('min_height') . ' = null');
		}

		if (isset($document['maxResolution'])) {
			/// isset + !empty - !is_null === !empty (just it)
			if (!empty($document['maxResolution']['width'])) {
				$query->set($this->_db->quoteName('max_width') . ' = ' . $document['maxResolution']['width']);
			}
			else {
				$query->set($this->_db->quoteName('max_width') . ' = null');
			}

			/// isset + !empty - !is_null === !empty (just it)
			if (!empty($document['maxResolution']['height'])) {
				$query->set($this->_db->quoteName('max_height') . ' = ' . $document['maxResolution']['height']);
			}
			else {
				$query->set($this->_db->quoteName('max_height') . ' = null');
			}
		}
		else {
			$query->set($this->_db->quoteName('max_width') . ' = null')
				->set($this->_db->quoteName('max_height') . ' = null');
		}

		$query->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($did));

		try {
			$this->_db->setQuery($query);
			$this->_db->execute();
			$query->clear()
				->update($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
				->set($this->_db->quoteName('mandatory') . ' = ' . $this->_db->quote($document['mandatory']))
				->where($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($did))
				->andWhere($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid));

			$this->_db->setQuery($query);
			$this->_db->execute();

			$m_falang->updateFalang($document['name'], $did, 'emundus_setup_attachments', 'value');
			$m_falang->updateFalang($document['description'], $did, 'emundus_setup_attachments', 'description');

			$query->clear()
				->select('count(id)')
				->from($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid))
				->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($did));
			$this->_db->setQuery($query);
			$assignations = $this->_db->loadResult();

			if (empty($assignations)) {
				$query->clear()
					->select('max(ordering)')
					->from($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
					->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid));
				$this->_db->setQuery($query);
				$ordering = $this->_db->loadResult();

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_setup_attachment_profiles'));
				$query->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid))
					->set($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($did))
					->set($this->_db->quoteName('mandatory') . ' = ' . $this->_db->quote($document['mandatory']))
					->set($this->_db->quoteName('ordering') . ' = ' . $this->_db->quote(($ordering + 1)))
					->set($this->_db->quoteName('has_sample') . ' = '. $this->_db->quote($params['has_sample']));

				if ($did === 20) {
					$query->set($this->_db->quoteName('displayed') . ' = ' . 0);
				}

				$this->_db->setQuery($query);
				$this->_db->execute();
			}

			if (!empty($params['file']) && $params['has_sample']) {
				$allowed_ext = array('jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf', 'xls', 'xlsx');
				$ext         = strtolower(pathinfo($params['file']['name'], PATHINFO_EXTENSION));
				if (in_array($ext, $allowed_ext)) {
					$filename  = $params['file']['name'];
					$directory = "/images/custom/attachments/$did/$pid/";

					if (!file_exists(JPATH_ROOT . '/images/custom/attachments')) {
						$created = mkdir(JPATH_ROOT . '/images/custom/attachments', 0775);
					}
					if (!file_exists(JPATH_ROOT . '/images/custom/attachments/' . $did)) {
						$created = mkdir(JPATH_ROOT . '/images/custom/attachments/' . $did, 0775);
					}
					if (!file_exists(JPATH_ROOT . '/images/custom/attachments/' . $did . '/' . $pid)) {
						$created = mkdir(JPATH_ROOT . '/images/custom/attachments/' . $did . '/' . $pid, 0775);
					}

					$filepath    = $directory . "$filename";
					$destination = JPATH_ROOT . $filepath;
					if (move_uploaded_file($params['file']['tmp_name'], $destination)) {
						$query->clear()
							->update($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
							->set($this->_db->quoteName('sample_filepath') . ' = ' . $this->_db->quote($filepath))
							->set('has_sample = 1')
							->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid))
							->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($did));

						$this->_db->setQuery($query);
						$this->_db->execute();
					}
					else {
						Log::add('component/com_emundus/models/campaign | Cannot upload a document model for ' . $did . ' and profile ' . $pid, Log::ERROR, 'com_emundus.error');

					}
				}
				else {
					Log::add(JFactory::getUser()->id . ' Cannot upload a document model for ' . $did . ' and profile ' . $pid, Log::INFO, 'com_emundus');
				}
			} else {
				$query->clear()
					->update($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
					->set('has_sample = 0')
					->where($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($pid))
					->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($did));

				$this->_db->setQuery($query);
				$this->_db->execute();
			}

			return true;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Cannot update a document ' . $did . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			return false;
		}
	}

	public function updatedDocumentMandatory($did, $pid, $mandatory = 1)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->update('#__emundus_setup_attachment_profiles')
				->set('mandatory = ' . $mandatory)
				->where('profile_id = ' . $pid)
				->andWhere('attachment_id = ' . $did);

			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @param $cid
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function getCampaignCategory($cid)
	{
		$campaign_dropfile_cat = false;

		if (!empty($cid)) {
			$query = $this->_db->getQuery(true);

			try {
				$query->select('id')
					->from($this->_db->quoteName('#__categories'))
					->where('json_valid(`params`)')
					->andWhere('json_extract(`params`, "$.idCampaign") LIKE ' . $this->_db->quote('"'.$cid.'"'))
					->andWhere($this->_db->quoteName('extension') . ' = ' . $this->_db->quote('com_dropfiles'));
				$this->_db->setQuery($query);
				$campaign_dropfile_cat = $this->_db->loadResult();

				if (!$campaign_dropfile_cat) {
					JPluginHelper::importPlugin('emundus', 'setup_category');
					$result = $this->app->triggerEvent('onAfterCampaignCreate', [$cid]);
					if ($result) {
						$campaign_dropfile_cat = $this->getCampaignCategory($cid);
					}
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Cannot get dropfiles category of the campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $campaign_dropfile_cat;
	}

	/**
	 * @param $campaign_cat
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function getCampaignDropfilesDocuments($campaign_cat)
	{
		$documents = [];

		if (!empty($campaign_cat)) {
			$query = $this->_db->getQuery(true);

			try {
				$query->select('*')
					->from($this->_db->quoteName('#__dropfiles_files'))
					->where($this->_db->quoteName('catid') . ' = ' . $this->_db->quote($campaign_cat))
					->order($this->_db->quoteName('ordering'))
					->group($this->_db->quoteName('ordering'));
				$this->_db->setQuery($query);

				$documents = $this->_db->loadObjectList();
			} catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Cannot get dropfiles documents of the category ' . $campaign_cat . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $documents;
	}

	/**
	 * @param $did
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function getDropfileDocument($did)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('*')
				->from($this->_db->quoteName('#__dropfiles_files'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($did));
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Cannot get the dropfile document ' . $did . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $did
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function deleteDocumentDropfile($did)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('file,catid')
				->from($this->_db->quoteName('#__dropfiles_files'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote(($did)));
			$this->_db->setQuery($query);
			$file = $this->_db->loadObject();
			unlink('media/com_dropfiles/' . $file->catid . '/' . $file->file);

			$query->clear()
				->delete($this->_db->quoteName('#__dropfiles_files'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote(($did)));
			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/campaign | Cannot delete the dropfile document ' . $did . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $did
	 * @param $name
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function editDocumentDropfile($did, $name)
	{
		$updated = false;

		if (!empty($did) && !empty($name)) {
			if (strlen($name) > 200) {
				$name = substr($name, 0, 200);
			}

			$query = $this->_db->getQuery(true);

			try {
				$query->update($this->_db->quoteName('#__dropfiles_files'))
					->set($this->_db->quoteName('title') . ' = ' . $this->_db->quote($name))
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote(($did)));
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Cannot update the dropfile document ' . $did . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	/**
	 * @param $documents
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function updateOrderDropfileDocuments($documents)
	{
		$updated = false;

		if (!empty($documents)) {
			$query = $this->_db->getQuery(true);
			try {
				$doc_order_updated = [];
				foreach ($documents as $document) {
					$query->clear()
						->update($this->_db->quoteName('#__dropfiles_files'))
						->set($this->_db->quoteName('ordering') . ' = ' . $this->_db->quote($document['ordering']))
						->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote(($document['id'])));
					$this->_db->setQuery($query);
					$doc_order_updated[] = $this->_db->execute();
				}

				$updated = !in_array(false, $doc_order_updated);
			} catch (Exception $e) {
				Log::add('component/com_emundus/models/campaign | Cannot reorder the dropfile documents : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	/**
	 * @param $pid
	 *
	 * @return array|false
	 *
	 * @since version 1.0
	 */
	public function getFormDocuments($pid)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('*')
				->from($this->_db->quoteName('#__modules'))
				->where('json_valid(`note`)')
				->where('json_extract(`note`, "$.pid") LIKE ' . $this->_db->quote('"' . $pid . '"'));
			$this->_db->setQuery($query);
			$form_module = $this->_db->loadObject();

			$files = array();

			if ($form_module != null) {
				// create the DOMDocument object, and load HTML from string
				$dochtml = new DOMDocument();
				$dochtml->loadHTML($form_module->content);

				// gets all DIVs
				$links = $dochtml->getElementsByTagName('a');
				foreach ($links as $link) {
					$file = new stdClass;
					if ($link->hasAttribute('href')) {
						$file->link = $link->getAttribute('href');
						$file->name = $link->textContent;
					}
					if ($link->parentNode->hasAttribute('id')) {
						$file->id = $link->parentNode->getAttribute('id');
					}
					$files[] = $file;
				}
			}

			return $files;
		}
		catch (Exception $e) {
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $did
	 * @param $name
	 * @param $pid
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function editDocumentForm($did, $name, $pid)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('*')
				->from($this->_db->quoteName('#__modules'))
				->where('json_valid(`note`)')
				->where('json_extract(`note`, "$.pid") LIKE ' . $this->_db->quote('"' . $pid . '"'));
			$this->_db->setQuery($query);
			$form_module = $this->_db->loadObject();

			if ($form_module != null) {
				// create the DOMDocument object, and load HTML from string
				$dochtml = new DOMDocument();
				$dochtml->loadHTML($form_module->content);

				// gets all DIVs
				$link_li           = $dochtml->getElementById($did);
				$link              = $link_li->firstChild;
				$link->textContent = $name;
				$link->parentNode->replaceChild($link, $link_li->firstChild);

				$newcontent = explode('</body>', explode('<body>', $dochtml->saveHTML())[1])[0];

				$query->clear()
					->update('#__modules')
					->set($this->_db->quoteName('content') . ' = ' . $this->_db->quote($newcontent))
					->where($this->_db->quoteName('id') . '=' . $this->_db->quote($form_module->id));
				$this->_db->setQuery($query);

				return $this->_db->execute();
			}
			else {
				return true;
			}
		}
		catch (Exception $e) {
			Log::add('Error updating form document in component/com_emundus/models/campaign: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $did
	 * @param $pid
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function deleteDocumentForm($did, $pid)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('*')
				->from($this->_db->quoteName('#__modules'))
				->where('json_valid(`note`)')
				->where('json_extract(`note`, "$.pid") LIKE ' . $this->_db->quote('"' . $pid . '"'));
			$this->_db->setQuery($query);
			$form_module = $this->_db->loadObject();

			// create the DOMDocument object, and load HTML from string
			$dochtml = new DOMDocument();
			$dochtml->loadHTML($form_module->content);

			// gets all DIVs
			$link = $dochtml->getElementById($did);
			unlink($link->firstChild->getAttribute('href'));
			$link->parentNode->removeChild($link);

			$newcontent = explode('</body>', explode('<body>', $dochtml->saveHTML())[1])[0];

			if (strpos($newcontent, '<li') === false) {
				$query->clear()
					->select('m.id')
					->from($this->_db->quoteName('#__menu', 'm'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'sp') . ' ON ' . $this->_db->quoteName('sp.menutype') . ' = ' . $this->_db->quoteName('m.menutype'))
					->where($this->_db->quoteName('sp.id') . ' = ' . $this->_db->quote($pid));
				$this->_db->setQuery($query);
				$mids = $this->_db->loadObjectList();

				foreach ($mids as $mid) {
					$query->clear()
						->delete($this->_db->quoteName('#__modules_menu'))
						->where($this->_db->quoteName('moduleid') . ' = ' . $this->_db->quote($form_module->id))
						->andWhere($this->_db->quoteName('menuid') . ' = ' . $this->_db->quote($mid->id));
					$this->_db->setQuery($query);
					$this->_db->execute();
				}

				$query->clear()
					->delete('#__modules')
					->where($this->_db->quoteName('id') . '=' . $this->_db->quote($form_module->id));
				$this->_db->setQuery($query);

				return $this->_db->execute();
			}
			else {
				$query->clear()
					->update('#__modules')
					->set($this->_db->quoteName('content') . ' = ' . $this->_db->quote($newcontent))
					->where($this->_db->quoteName('id') . '=' . $this->_db->quote($form_module->id));
				$this->_db->setQuery($query);

				return $this->_db->execute();
			}
		}
		catch (Exception $e) {
			Log::add('Error updating form document in component/com_emundus/models/campaign: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @deprecated Use getCurrentWorkflowStepFromFile of model workflow instead
	 *
	 * @param $emundusUser
	 *
	 * @return false|object False if error, object containing emundus_campaign_workflow id, start date and end_date if success
	 *
	 * @since version 1.30.0
	 */
	public function getCurrentCampaignWorkflow($fnum)
	{
		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
		$m_workflow = new EmundusModelWorkflow();
		return $m_workflow->getCurrentWorkflowStepFromFile($fnum);
	}

	/**
	 * @param $campaign_id int
	 * @param array $step_types if 1, only applicant steps, if 2, only admin steps, can be both
	 * @return array
	 */
	public function getAllCampaignWorkflows($campaign_id, $step_types = [1])
	{
		$steps = [];

		if (!empty($campaign_id)) {
			$program = $this->getProgrammeByCampaignID($campaign_id);

			if (!empty($program['id'])) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
				$m_workflow = new EmundusModelWorkflow();

				$workflows = $m_workflow->getWorkflows([], 0, 0, [$program['id']]);

				if (!empty($workflows)) {
					foreach($workflows as $workflow) {
						$wf_data = $m_workflow->getWorkflow($workflow->id);

						foreach ($wf_data['steps'] as $step) {
							if (in_array($step->type, $step_types) || in_array($m_workflow->getParentStepType($step->type), $step_types)) {
								$step->profile = $step->profile_id;
								$steps[] = $step;
							}
						}
					}
				}
			}
		}

		return $steps;
	}

	public function pinCampaign($cid): bool
	{
		$pinned = false;

		if (!empty($cid)) {
			// check if campaign exists
			$campaign = $this->getCampaignByID($cid);

			if (!empty($campaign)) {
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);

				try {
					$query->clear()
						->select('id')
						->from($db->quoteName('#__emundus_setup_campaigns'))
						->where($db->quoteName('pinned') . ' = 1');
					$db->setQuery($query);
					$campaigns_already_pinned = $db->loadColumn();

					if (!empty($campaigns_already_pinned)) {
						$this->unpinCampaign($campaigns_already_pinned);
					}

					$query->clear()
						->update($db->quoteName('#__emundus_setup_campaigns'))
						->set($db->quoteName('pinned') . ' = 1')
						->where($db->quoteName('id') . ' = ' . $db->quote($cid));
					$db->setQuery($query);

					$pinned = $db->execute();
				}
				catch (Exception $e) {
					Log::add('Error updating form document in component/com_emundus/models/campaign: ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $pinned;
	}

	/**
	 * @param $campaign_id
	 *
	 * @return bool
	 */
	public function unpinCampaign($campaign_id): bool
	{
		$unpinned = false;

		$campaign_id = is_array($campaign_id) ? $campaign_id : array($campaign_id);
		$campaign_id = array_filter($campaign_id, 'is_numeric');
		$campaign_id = array_filter($campaign_id);

		if (!empty($campaign_id)) {
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->update($db->quoteName('#__emundus_setup_campaigns'))
				->set($db->quoteName('pinned') . ' = 0')
				->where($db->quoteName('id') . ' IN (' . implode(',', $campaign_id) . ')');

			try {
				$db->setQuery($query);
				$unpinned = $db->execute();
			}
			catch (Exception $e) {
				Log::add('Error setting pinned = 0 for $cid ' . $campaign_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $unpinned;
	}

	/**
	 * @param $campaign_id int
	 * @return string
	 */
	public function getCampaignMoreFormUrl($campaign_id): string
	{
		$form_url = '';

		if (!empty($campaign_id)) {
			$query = $this->_db->getQuery(true);

			// get the form id where the table is jos_emundus_setup_campaigns_more
			$query->select('form_id')
				->from($this->_db->quoteName('#__fabrik_lists'))
				->where($this->_db->quoteName('db_table_name') . ' = ' . $this->_db->quote('jos_emundus_setup_campaigns_more'));

			$this->_db->setQuery($query);
			$form_id = $this->_db->loadResult();

			if (!empty($form_id)) {
				// check if there are more elements other than id, date_time and campaign_id
				// otherwhise, we don't need to display the form
				$query->clear()
					->select('COUNT(jfe.id)')
					->from($this->_db->quoteName('#__fabrik_elements', 'jfe'))
					->leftJoin($this->_db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $this->_db->quoteName('jffg.group_id') . ' = ' . $this->_db->quoteName('jfe.group_id'))
					->where($this->_db->quoteName('jffg.form_id') . ' = ' . $this->_db->quote($form_id))
					->andWhere('jfe.published = 1')
					->andWhere('jfe.name NOT IN ("id", "date_time", "campaign_id")');
				$this->_db->setQuery($query);
				$nb_elements = $this->_db->loadResult();

				if ($nb_elements > 0) {
					$query->clear()
						->select('id')
						->from($this->_db->quoteName('#__emundus_setup_campaigns_more'))
						->where('campaign_id = ' . $this->_db->quote($campaign_id));

					$this->_db->setQuery($query);
					$row_id = $this->_db->loadResult();

					if (!empty($row_id)) {
						$form_url = '/index.php?option=com_fabrik&view=form&formid=' . $form_id . '&rowid=' . $row_id . '&tmpl=component&iframe=1';
					} else {
						$form_url = '/index.php?option=com_fabrik&view=form&formid=' . $form_id . '&rowid=0&tmpl=component&iframe=1&jos_emundus_setup_campaigns_more___campaign_id=' . $campaign_id . '&Itemid=0';
					}
				}
			}
		}

		return $form_url;
	}

	public function getAllItemsAlias($cid)
	{
		$items = [];
		$query = $this->_db->getQuery(true);

		try
		{
			$menus_to_exclude = [];

			if(!empty($cid))
			{
				$query->select('id,params')
					->from($this->_db->quoteName('#__menu'))
					->where($this->_db->quoteName('menutype') . ' = ' . $this->_db->quote('campaigns'));
				$this->_db->setQuery($query);
				$campaigns_items = $this->_db->loadObjectList();
				foreach ($campaigns_items as $key => $item)
				{
					$params = json_decode($item->params);
					if (!empty($params->com_emundus_programme_campaign_id) && $params->com_emundus_programme_campaign_id == $cid)
					{
						$menus_to_exclude[] = $item->id;
					}
				}
			}

			$query->clear()
				->select('alias')
				->from($this->_db->quoteName('#__menu'))
				->where($this->_db->quoteName('client_id') . ' = 0');
			if(!empty($menus_to_exclude))
			{
				$query->where($this->_db->quoteName('id') . ' NOT IN (' . implode(',', $menus_to_exclude) . ')');
			}
			$this->_db->setQuery($query);
			$items = $this->_db->loadColumn();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $items;
	}

	public function createCampaignAlias($cid, $alias, $label)
	{
		$alias_created = false;

		try
		{
			$query = $this->_db->getQuery(true);
			require_once (JPATH_SITE.DS.'administrator/components/com_emundus/helpers/update.php');

			$modules_id =  [];

			$query->clear()
				->select('id,params')
				->from($this->_db->quoteName('#__modules'))
				->where($this->_db->quoteName('module') . ' LIKE ' . $this->_db->quote('mod_emundus_campaign'));
			$this->_db->setQuery($query);
			$modules = $this->_db->loadObjectList();
			foreach ($modules as $module) {
				$params = json_decode($module->params);
				if (!empty($params->mod_em_campaign_layout) && $params->mod_em_campaign_layout == 'tchooz_single_campaign') {
					$modules_id[] = $module->id;
				}
			}

			// Check again if alias already exists
			$query->clear()
				->select('id')
				->from($this->_db->quoteName('#__menu'))
				->where($this->_db->quoteName('alias') . ' LIKE ' . $this->_db->quote($alias));
			$this->_db->setQuery($query);
			$menu_id = $this->_db->loadResult();

			if(!empty($menu_id)) {
				$alias = $alias.'-'.$cid;
			}

			$params = [
				'menutype' => 'campaigns',
				'title'    => $label,
				'alias'    => $alias,
				'path'     => $alias,
				'type' => 'component',
				'link' => 'index.php?option=com_emundus&view=programme',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'   => [
					'com_emundus_programme_campaign_id' => $cid,
					'com_emundus_programme_candidate_link' => 'index.php?option=com_fabrik&view=form&formid=307&Itemid=2700'
				]
			];

			$alias_created = EmundusHelperUpdate::addJoomlaMenu($params, 1, 1, 'last-child', $modules_id)['status'];
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $alias_created;
	}

	public function getCampaignDetailsMenu($cid)
	{
		$details_menu = null;

		try
		{
			$query = $this->_db->getQuery(true);

			$query->clear()
				->select('id,alias,path,published,params')
				->from($this->_db->quoteName('#__menu'))
				->where($this->_db->quoteName('menutype') . ' LIKE ' . $this->_db->quote('campaigns'));
			$this->_db->setQuery($query);
			$menus = $this->_db->loadObjectList();

			foreach ($menus as $menu) {
				$params = json_decode($menu->params);
				if (!empty($params->com_emundus_programme_campaign_id) && $params->com_emundus_programme_campaign_id == $cid) {
					$details_menu = $menu;
					break;
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $details_menu;
	}

	/**
	 * Get profiles ids from campaign ids
	 *
	 * @param $campaign_ids
	 *
	 * @return array
	 *
	 * @since version 1.40.0
	 */
	function getProfilesFromCampaignId($campaign_ids) {
		$profile_ids = [];

		if (!empty($campaign_ids)) {
			$query = $this->_db->getQuery(true);

			$query->select('DISTINCT profile_id')
				->from($this->_db->quoteName('#__emundus_setup_campaigns'))
				->where($this->_db->quoteName('id') . ' IN (' . implode(',', $this->_db->quote($campaign_ids)) . ')');

			$this->_db->setQuery($query);
			$profiles = $this->_db->loadColumn();
			foreach ($profiles as $profile)
			{
				if (!in_array($profile, $profile_ids))
				{
					$profile_ids[] = $profile;
				}
			}

			// profiles from workflows
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_worfklow = new EmundusModelWorkflow();
			$workflows  = $m_worfklow->getWorkflows();

			if (!empty($workflows)) {
				$programme_codes = [];
				foreach ($campaign_ids as $cid) {
					$programme = $this->getProgrammeByCampaignID($cid);

					if (!in_array($programme['code'], $programme_codes)) {
						$programme_codes[] = $programme['code'];
					}
				}

				foreach ($workflows as $workflow)
				{
					if (!in_array($workflow->profile, $profile_ids) && !empty(array_intersect($workflow->programme_ids, $programme_codes)))
					{
						$profile_ids[] = $workflow->profile;
					}
				}
			}
		}

		return $profile_ids;
	}

	/**
	 * @param $campaign_id
	 *
	 * @return array|mixed
	 */
	public function getCampaignLanguagesValues($campaign_id)
	{
		$languages = [];

		if (!empty($campaign_id)) {
			$query = $this->_db->createQuery();

			$query->select('el.lang_id as value, el.title as label')
				->from($this->_db->quoteName('#__languages', 'el'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns_languages', 'esc_lang') . ' ON ' . $this->_db->quoteName('esc_lang.lang_id') . ' = ' . $this->_db->quoteName('el.lang_id'))
				->where('esc_lang.campaign_id = ' . $this->_db->quote($campaign_id));

			$this->_db->setQuery($query);
			$languages = $this->_db->loadObjectList();
		}

		return $languages;
	}


	/**
	 * @param $fnum
	 *
	 * @return void
	 * @throws Exception
	 */
	public function getCampaignLanguages($fnum): array
	{
		$languages = [];

		if (!empty($fnum)) {
			$query = $this->_db->getQuery(true);

			try {
				$query->clear()
					->select($this->_db->quoteName('escl.lang_id'))
					->from($this->_db->quoteName('#__emundus_setup_campaigns_languages', 'escl'))
					->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('escl.campaign_id'))
					->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));

				$this->_db->setQuery($query);
				$languages = $this->_db->loadColumn();

				if (empty($languages)) {
					// maybe the program has language restrictions
					$query->clear()
						->select($this->_db->quoteName('espl.lang_id'))
						->from($this->_db->quoteName('#__emundus_setup_programs_languages', 'espl'))
						->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('espl.program_id'))
						->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.training') . ' = ' . $this->_db->quoteName('esp.code'))
						->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
						->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum))
						->andWhere($this->_db->quoteName('espl.lang_id') . ' > 0');

					$this->_db->setQuery($query);
					$languages = $this->_db->loadColumn();
				}
			} catch (Exception $e) {
				Log::add('Error getting campaign languages ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $languages;
	}
}
