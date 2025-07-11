<?php
/**
 * @version        $Id: query.php 14401 2010-01-26 14:10:00Z guillossou $
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\NumericSign\RequestRepository;

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.helper');

/**
 * Content Component Query Helper
 *
 * @static
 * @package        Joomla
 * @subpackage     Content
 * @since          1.5
 */
class EmundusHelperAccess
{

	static function isAllowed($usertype, $allowed)
	{
		return in_array($usertype, $allowed);
	}

	static function isAllowedAccessLevel($user_id, $current_menu_access)
	{
		$user_access_level = Access::getAuthorisedViewLevels($user_id);

		return in_array($current_menu_access, $user_access_level);
	}

	static function asAdministratorAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 8);
	}

	static function asCoordinatorAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 7);
	}

	static function asManagerAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 17);
	}

	static function asPartnerAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 6);
	}

	static function asEvaluatorAccessLevel($user_id)
	{
		return (EmundusHelperAccess::isAllowedAccessLevel($user_id, 5) ||
			EmundusHelperAccess::isAllowedAccessLevel($user_id, 3) ||
			EmundusHelperAccess::isAllowedAccessLevel($user_id, 12) ||
			EmundusHelperAccess::isAllowedAccessLevel($user_id, 13));
	}

	static function asApplicantAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 4);
	}

	static function asPublicAccessLevel($user_id)
	{
		return EmundusHelperAccess::isAllowedAccessLevel($user_id, 1);
	}

	static function check_group($user_id, $group, $inherited)
	{
		// 1:Public / 2:Registered / 3:Author / 4:Editor / 5:Publisher / 6:Manager / 7:Administrator / 8:Super Users / 9:Guest / 10:Nobody
		if ($inherited)
		{
			//include inherited groups
			jimport('joomla.access.access');
			$groups = JAccess::getGroupsByUser($user_id);
		}
		else
		{
			//exclude inherited groups
			$user   = JFactory::getUser($user_id);
			$groups = isset($user->groups) ? $user->groups : array();
		}

		return (in_array($group, $groups)) ? true : 0;
	}

	static function isAdministrator($user_id)
	{
		return EmundusHelperAccess::check_group($user_id, 8, false);
	}

	static function isCoordinator($user_id)
	{
		return EmundusHelperAccess::check_group($user_id, 7, false);
	}

	static function isPartner($user_id)
	{
		return (EmundusHelperAccess::check_group($user_id, 4, false) ||
			EmundusHelperAccess::check_group($user_id, 14, false) ||
			EmundusHelperAccess::check_group($user_id, 13, false));
	}

	static function isExpert($user_id)
	{
		return (EmundusHelperAccess::check_group($user_id, 14, false));
	}

	static function isEvaluator($user_id)
	{
		return (EmundusHelperAccess::check_group($user_id, 3, false) ||
			EmundusHelperAccess::check_group($user_id, 13, false));
	}

	static function isApplicant($user_id)
	{
		return (EmundusHelperAccess::check_group($user_id, 2, false) ||
			EmundusHelperAccess::check_group($user_id, 11, true));
	}

	static function isPublic($user_id)
	{
		return EmundusHelperAccess::check_group($user_id, 1, false);
	}

	/**
	 * Get the eMundus groups for a user.
	 *
	 *
	 * @param   int  $user  The user id.
	 *
	 * @return    array    The array of groups for user.
	 * @since    4.0
	 */
	static function getProfileAccess($user)
	{
		$db    = JFactory::getDBO();
		$query = 'SELECT esg.profile_id FROM #__emundus_setup_groups as esg
					LEFT JOIN #__emundus_groups as eg on esg.id=eg.group_id
					WHERE esg.published=1 AND eg.user_id=' . $user;
		$db->setQuery($query);

		return $db->loadResultArray();
	}

	/**
	 * Get action access right.
	 *
	 * @param   int     $action_id  Id of the action.
	 * @param   string  $crud       create/read/update/delete.
	 *
	 * @param   null    $user_id    The user id.
	 * @param   null    $fnum       File number of application
	 *
	 * @return    boolean    Has access or not
	 * @since    6.0
	 */
	static function asAccessAction($action_id, $crud, $user_id = null, $fnum = null)
	{
		require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		$m_users   = new EmundusModelUsers();

		if (!empty($fnum))
		{
			$canAccess = $m_users->getUserActionByFnum($action_id, $fnum, $user_id, $crud);
			if ($canAccess > 0)
			{
				return true;
			}
			elseif ($canAccess == 0 || $canAccess === null)
			{
				if (!empty($user_id)) {
                    $groups = $m_users->getUserGroups($user_id, 'Column');
                } else {
                    $groups = Factory::getApplication()->getSession()->get('emundusUser')->emGroups;
                }

				if (!empty($groups) && count($groups) > 0)
				{
					return EmundusHelperAccess::canAccessGroup($groups, $action_id, $crud, $fnum);
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			if (!empty($user_id)) {
                $groups = $m_users->getUserGroups($user_id, 'Column');
            } else {
                $groups = Factory::getApplication()->getSession()->get('emundusUser')->emGroups;
            }

			return EmundusHelperAccess::canAccessGroup($groups, $action_id, $crud);
		}
	}


	/**
	 * @param         $gids
	 * @param         $action_id
	 * @param         $crud
	 * @param   null  $fnum
	 *
	 * @return bool
	 *
	 * @since version
	 */
	static function canAccessGroup($gids, $action_id, $crud, $fnum = null)
	{

		require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		$m_users = new EmundusModelUsers();

		if (!is_null($fnum) && !empty($fnum))
		{
			$accessList = $m_users->getGroupActions($gids, $fnum, $action_id, $crud);
			$canAccess  = (!empty($accessList)) ? -1 : null;
			if (count($accessList) > 0)
			{
				foreach ($accessList as $access)
				{
					if ($canAccess < intval($access[$crud]))
					{
						$canAccess = $access[$crud];
					}
				}
			}
			if ($canAccess > 0)
			{
				return true;
			}
			elseif ($canAccess == 0 || $canAccess === null)
			{
				// We filter the list of groups to take into account only the groups attached to the fnum's programme OR who are attached to no programme.
				$gids = $m_users->getEffectiveGroupsForFnum($gids, $fnum);

				return EmundusHelperAccess::canAccessGroup($gids, $action_id, $crud);
			}
			else
			{
				return false;
			}
		}
		else
		{
			$groupsActions = $m_users->getGroupsAcl($gids);
			if (!empty($groupsActions))
			{
				foreach ($groupsActions as $action)
				{
					if ($action['action_id'] == $action_id && $action[$crud] == 1)
					{
						return true;
					}
				}
			}

			return false;
		}
	}

	/**
	 * @param $user_id
	 *
	 * @return array|bool
	 *
	 * @since version
	 */
	public static function getUserFabrikGroups($user_id)
	{
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/groups.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/users.php');
		$m_groups = new EmundusModelGroups();
		$m_users  = new EmundusModelUsers();

		$group_ids = $m_users->getUserGroups($user_id);

		// NOTE: The unorthodox array_keys_flip is actually faster than doing array_unique(). The first array_keys is because the function used returns an assoc array [id => name].
		return $m_groups->getFabrikGroupsAssignedToEmundusGroups(array_keys(array_flip(array_keys($group_ids))));
	}


	/**
	 * @param $user_id
	 *
	 * @return array|bool
	 *
	 * @since version
	 */
	public static function getUserAllowedAttachmentIDs($user_id)
	{
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/users.php');
		$m_files = new EmundusModelFiles();
		$m_users = new EmundusModelUsers();

		$group_ids = $m_users->getUserGroups($user_id);

		// NOTE: The unorthodox array_keys_flip is actually faster than doing array_unique(). The first array_keys is because the function used returns an assoc array [id => name].
		return $m_files->getAttachmentsAssignedToEmundusGroups(array_keys(array_flip(array_keys($group_ids))));
	}


	/**
	 * @param $user_id
	 *
	 * @return bool
	 *
	 * @since version
	 */
	public static function isDataAnonymized($user_id)
	{
		$is_data_anonymized = false;
		Log::addLogger(['text_file' => 'com_emundus.access.error.php'], Log::ERROR, 'com_emundus');

		if (!empty($user_id))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
			$m_users   = new EmundusModelUsers();
			$group_ids = $m_users->getUserGroups($user_id);
			if (!empty($group_ids))
			{
				// NOTE: The unorthodox array_keys_flip is actually faster than doing array_unique(). The first array_keys is because the function used returns an assoc array [id => name].
				$group_ids = array_keys(array_flip(array_keys($group_ids)));

				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('anonymize'))->from($db->quoteName('#__emundus_setup_groups'))->where($db->quoteName('id') . ' IN (' . implode(',', $group_ids) . ')');
				$db->setQuery($query);

				try
				{
					$is_data_anonymized = in_array('1', $db->loadColumn());
				}
				catch (Exception $e)
				{
					Log::add('Error seeing if user can access non anonymous data. -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

					$is_data_anonymized = false;
				}
			}
		}

		return $is_data_anonymized;
	}

	/**
	 * @param $user_id
	 * @param $fnum
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public static function isUserAllowedToAccessFnum($user_id, $fnum)
	{
		$allowed = false;

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!empty($user_id) && !empty($fnum))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			if (self::isFnumMine($user_id, $fnum)) {
				$allowed = true;
			}
			else
			{
				// does user is associated to the fnum directly?
				$query->clear()
					->select('id')
					->from('#__emundus_users_assoc')
					->where('user_id = ' . $db->quote($user_id))
					->andWhere('fnum LIKE ' . $db->quote($fnum))
					->andWhere('action_id = 1')
					->andWhere('r = 1');
				$db->setQuery($query);
				$allowed_to_read = $db->loadResult();

				if ($allowed_to_read)
				{
					$allowed = true;
				}
				else
				{
					// does the user have common groups associated to the fnum?
					$query->clear()
						->select('group_id')
						->from('#__emundus_groups')
						->where('user_id = ' . $db->quote($user_id));
					$db->setQuery($query);
					$user_groups = $db->loadColumn();

					// first, we check groups associated manually to the file
					$query->clear()
						->select($db->quoteName('group_id'))
						->from($db->quoteName('#__emundus_group_assoc'))
						->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
						->andWhere($db->quoteName('action_id') . ' = 1')
						->andWhere($db->quoteName('r') . ' = 1');
					$db->setQuery($query);
					$groups_assoc = $db->loadColumn();

					$groups_in_both_assoc = array_intersect($user_groups, $groups_assoc);

					if (!empty($groups_in_both_assoc))
					{
						$allowed = true;
					}
					else
					{
						// if there is none, we check files associated to the program
						require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
						$m_users     = new EmundusModelUsers();
						$file_groups = $m_users->getEffectiveGroupsForFnum($user_groups, $fnum, true);

						$groups_in_both_program = array_intersect($user_groups, $file_groups);
						if (!empty($groups_in_both_program))
						{
							$groups_actions = $m_users->getGroupsAcl($groups_in_both_program);

							foreach ($groups_actions as $action)
							{
								if ($action['action_id'] == 1 && $action['r'] == 1)
								{
									$allowed = true;
									break;
								}
							}
						}
					}
				}
			}
		}

		return $allowed;
	}

	/**
	 *
	 * @return JCrypt
	 *
	 * @since version
	 */
	public static function getCrypt()
	{
		jimport('joomla.crypt.crypt');
		jimport('joomla.crypt.key');
		$config = JFactory::getConfig();
		$secret = $config->get('secret', '');

		if (trim($secret) == '')
		{
			throw new RuntimeException('You must supply a secret code in your Joomla configuration.php file');
		}

		$key = new JCryptKey('simple', $secret, $secret);

		return new JCrypt(new JCryptCipherSimple, $key);
	}

	public static function buildFormUrl($link, $fnum): string
	{
		$url_params = [];
		$parsed_url = parse_url($link);
		parse_str($parsed_url['query'], $url_params);

		if (!empty($url_params['formid']))
		{
			$db_table_name = EmundusHelperFabrik::getDbTableName($url_params['formid']);
			$rowid         = EmundusHelperAccess::getRowIdByFnum($db_table_name, $fnum);

			if (!empty($rowid))
			{
				$url_params['rowid'] = $rowid;
			}
			if (!empty($fnum))
			{
				$url_params['fnum'] = $fnum;
			}

			$link = http_build_url($link, ['query' => http_build_query($url_params)]);
		}

		return $link;
	}

	public static function getRowIdByFnum($db_table_name, $fnum): int
	{
		$rowid = 0;
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		if (!empty($fnum))
		{
			try
			{
				$query->select('id')
					->from($db->quoteName($db_table_name))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
				$db->setQuery($query);
				$rowid = (int) $db->loadResult();
			}
			catch (Exception $e)
			{
				Log::add('Error getting row id by fnum. -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $rowid;
	}

	/**
	 * Check if the application file is mine
	 * 
	 * @param $user_id
	 * @param $fnum
	 *
	 * @return bool
	 *
	 * @since version 1.40.0
	 */
	public static function isFnumMine($user_id, $fnum) {
		$mine = false;

		if (!empty($user_id) && !empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where('applicant_id = ' . $db->quote($user_id))
				->andWhere('fnum LIKE ' . $db->quote($fnum));
			try {
				$db->setQuery($query);
				$ccid = $db->loadResult();

				if (!empty($ccid)) {
					$mine = true;
				}

			} catch (Exception $e) {
				Log::add('Error seeing if fnum is mine. -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}

			if (!$mine) {
				// maybe filed has been shared to me (collaboration)
				$query->clear()
					->select('efr.id')
					->from($db->quoteName('#__emundus_files_request', 'efr'))
					->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.id = efr.ccid')
					->where('ecc.fnum LIKE ' . $db->quote($fnum))
					->andWhere('efr.user_id = ' . $db->quote($user_id))
					->andWhere('efr.uploaded = 1');

				try {
					$db->setQuery($query);
					$collaboration_id = $db->loadResult();

					if (!empty($collaboration_id)) {
						$mine = true;
					}
				} catch (Exception $e) {
					Log::add('Error seeing if fnum is mine. -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}
			}
		}

		return $mine;
	}

	public static function isBookingMine($user_id, $booking_id): bool
	{
		$mine = false;

		if (!empty($user_id))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->clear()
				->select('user')
				->from($db->quoteName('#__emundus_registrants'))
				->where('id = ' . $db->quote($booking_id));
			$db->setQuery($query);
			$booking_user_id = $db->loadResult();

			if ($booking_user_id == $user_id)
			{
				$mine = true;
			}
		}

		return $mine;
	}

	public static function isRequestMine(int $request_id, int $user_id): bool
	{
		try
		{
			$mine = false;
			$db = Factory::getContainer()->get('DatabaseDriver');

			$requestRepository = new RequestRepository($db);
			$request = $requestRepository->loadRequestById($request_id);

			$query = $db->getQuery(true);
			$query->select('applicant_id')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where('id = ' . $db->quote($request->getCcid()));
			$db->setQuery($query);
			$applicant_id = (int)$db->loadResult();

			if ($applicant_id === $user_id)
			{
				$mine = true;
			}

			return $mine;
		}
		catch (Exception $e)
		{
			Log::add('Error seeing if request is mine. -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			return false;
		}
	}

	/**
	 * @param $ccid int campaign candidature id
	 * @param $step_data object step data, use getStepData from EmundusModelWorkflow
	 * @param $user_id int if not given, current user will be taken
	 * @param $profile_ids array if not given, only current session profile will be taken
	 *
	 * @return bool[] (can_see, can_edit)
	 * @throws Exception
	 */
	public static function getUserEvaluationStepAccess(int $ccid, object $step_data, ?int $user_id, bool $verify_campaign_infos = true): array
	{
		$can_see = false;
		$can_edit = false;
		$reason_cannot_edit = 'READONLY_ACCESS';

		if (!empty($ccid) && !empty($step_data->id)) {
			$app = Factory::getApplication();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			if (empty($user_id)) {
				$user_id = $app->getIdentity()->id;
			}

			// Verify if this step and this ccid are linked together by workflow
			$query->clear()
				->select('esp.id')
				->from($db->quoteName('#__emundus_setup_programmes', 'esp'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
				->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
				->where('ecc.id = ' . $ccid);

			$db->setQuery($query);
			$programme_id = $db->loadResult();

			if (!empty($programme_id) && in_array($programme_id, $step_data->programs)) {
				// verify if user can access to this evaluation form
				if (EmundusHelperAccess::asCoordinatorAccessLevel($user_id) || EmundusHelperAccess::asAdministratorAccessLevel($user_id)) {
					$can_see = true;
					$can_edit = true;
				} else if (EmundusHelperAccess::asPartnerAccessLevel($user_id)) {
					$fnum = EmundusHelperFiles::getFnumFromId($ccid);

					// it's the bare minimum to potentially see the evaluation form
					if (EmundusHelperAccess::asAccessAction(1, 'r', $user_id, $fnum) &&
						(EmundusHelperAccess::asAccessAction($step_data->action_id, 'r', $user_id) || EmundusHelperAccess::asAccessAction($step_data->action_id, 'c', $user_id)))
					{
						$can_see = true;
						if (EmundusHelperAccess::asAccessAction($step_data->action_id, 'c', $user_id, $fnum))
						{
							if ($verify_campaign_infos) {
								// verify step is not closed
								// file must be in one of the entry statuses and current date must be between start and end date of step
								$query->clear()
									->select('status')
									->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
									->where('ecc.id = ' . $ccid);

								$db->setQuery($query);
								$status = $db->loadResult();

								$respect_dates = true;
								if ($step_data->infinite != 1)
								{
									if (!empty($step_data->start_date) && $step_data->start_date > date('Y-m-d H:i:s'))
									{
										$respect_dates = false;
										$reason_cannot_edit = 'COM_EMUNDUS_WORKFLOW_STEP_ACCESS_DENIED_BECAUSE_NOT_STARTED';
									}

									if (!empty($step_data->end_date) && $step_data->end_date < date('Y-m-d H:i:s'))
									{
										$respect_dates = false;
										$reason_cannot_edit = 'COM_EMUNDUS_WORKFLOW_STEP_ACCESS_DENIED_BECAUSE_ENDED';
									}
								}

								if (in_array($status, $step_data->entry_status) && $respect_dates)
								{
									$can_edit = true;
								} else {
									$reason_cannot_edit = !in_array($status, $step_data->entry_status) ? 'COM_EMUNDUS_WORKFLOW_STEP_ACCESS_DENIED_BECAUSE_OF_STATUS' : $reason_cannot_edit;
								}
							} else {
								$can_edit = true;
							}
						}
					}
				}
			} else {
				throw new Exception(Text::_('ERROR_INCOHERENT_STEP_FOR_CCID'));
			}
		}

		return [
			'can_see' => $can_see,
			'can_edit' => $can_edit,
			'reason_cannot_edit' => $reason_cannot_edit
		];
	}

	public static function addAccessToGroup(int $action_id, int $group_id, $crud = ['c' => 0, 'r' => 0, 'u' => 0, 'd' => 0]): bool
	{
		$granted = false;

		if (!empty($action_id) && !empty($group_id)) {
			// sanitize $crud
			$crud = array_map(function($value) {
				return ($value == 1) ? 1 : 0;
			}, $crud);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->clear()
				->insert('#__emundus_acl')
				->columns('action_id, group_id, c, r, u, d')
				->values($action_id . ', ' . $group_id . ', ' . $crud['c'] . ', ' . $crud['r'] . ', ' . $crud['u'] . ', ' . $crud['d']);

			try {
				$db->setQuery($query);
				$inserted = $db->execute();

				if (!$inserted) {
					Log::add('Adding rights for action ' . $action_id . ' to group ' . $group_id . ' failed ', Log::WARNING, 'com_emundus');
				} else {
					$granted = true;
				}
			} catch (Exception $e) {
				Log::add('Error while adding ACL for action : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $granted;
	}

	public static function getUsersFromGroupsThatCanAccessToFile($filter_group_ids, $fnum): array
	{
		$user_ids = [];

		if (!empty($fnum) && !empty($filter_group_ids)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			try {
				$query->clear()
					->select('group_id')
					->from($db->quoteName('#__emundus_group_assoc'))
					->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
					->andWhere($db->quoteName('action_id') . ' = 1')
					->andWhere($db->quoteName('r') . ' = 1')
					->andWhere('group_id IN (' . implode(',', $db->quote($filter_group_ids)) . ')');
				$db->setQuery($query);
				$access_group_ids = $db->loadColumn();

				$query->clear()
					->select('esc.training')
					->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
					->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id = ecc.campaign_id')
					->where($db->quoteName('ecc.fnum') . ' LIKE ' . $db->quote($fnum));

				$db->setQuery($query);
				$program_code = $db->loadResult();

				$program_group_ids = [];
				if (!empty($program_code)) {
					$query->clear()
						->select('DISTINCT parent_id')
						->from($db->quoteName('#__emundus_setup_groups_repeat_course', 'esgrc'))
						->where($db->quoteName('esgrc.course') . ' LIKE ' . $db->quote($program_code))
						->andWhere($db->quoteName('esgrc.parent_id') . ' IN (' . implode(',', $db->quote($filter_group_ids)) . ')');

					$db->setQuery($query);
					$program_group_ids = $db->loadColumn();
				}

				$group_ids = array_unique(array_merge($access_group_ids, $program_group_ids));

				if (!empty($group_ids)) {
					$query->clear()
						->select('DISTINCT ' . $db->quoteName('eg.user_id'))
						->from($db->quoteName('#__emundus_groups', 'eg'))
						->where('eg.group_id IN (' . implode(',', $db->quote($group_ids)) . ')');

					$db->setQuery($query);
					$user_ids = $db->loadColumn();
				}

				$query->clear()
					->select('DISTINCT ' . $db->quoteName('eua.user_id'))
					->from($db->quoteName('#__emundus_users_assoc', 'eua'))
					->leftJoin($db->quoteName('#__emundus_groups', 'eg') . ' ON ' . $db->quoteName('eua.user_id') . ' = ' . $db->quoteName('eg.user_id'))
					->where($db->quoteName('eua.fnum') . ' LIKE ' . $db->quote($fnum))
					->andWhere($db->quoteName('eua.action_id') . ' = 1')
					->andWhere($db->quoteName('eua.r') . ' = 1')
					->andWhere('eg.group_id IN (' . implode(',', $db->quote($filter_group_ids)) . ')');

				$db->setQuery($query);
				$users_directly_associated = $db->loadColumn();

				if (!empty($users_directly_associated)) {
					$user_ids = array_unique(array_merge($user_ids, $users_directly_associated));
				}
			} catch (Exception $e) {
				Log::add('Error while getting users that can access file ' . $fnum . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $user_ids;
	}

	public static function getActionIdFromActionName(string $name): int
	{
		$action_id = 0;

		if (!empty($name)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id')
				->from($db->quoteName('#__emundus_setup_actions'))
				->where($db->quoteName('name') . ' LIKE ' . $db->quote($name));

			try {
				$db->setQuery($query);
				$action_id = (int) $db->loadResult();
			} catch (Exception $e) {
				Log::add('Error while getting action id from action name ' . $name . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $action_id;
	}
}
