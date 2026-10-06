<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL
 * @author      Hugo Moracchini
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Logs\LogEntity;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Logs\LogRepository;

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');

class EmundusModelLogs extends JModelList
{
	private $user;
	private $db;

	/**
	 * EmundusModelLogs constructor.
	 * @since 3.8.8
	 */
	public function __construct()
	{
		parent::__construct();

		// Assign values to class variables.
		$this->user = Factory::getApplication()->getIdentity();
		$this->db   = Factory::getContainer()->get('DatabaseDriver');

		// write log file
		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.logs.php'], Log::ERROR, 'com_emundus');
	}

	/**
	 * Writes a log entry of the action to/from the user.
	 *
	 * @param   int     $user_from
	 * @param   int     $user_to
	 * @param   string  $fnum
	 * @param   int|string     $action
	 * @param   string  $crud
	 * @param   string  $message
	 *
	 * @since 3.8.8
	 */
	static function log($user_from, $user_to, $fnum, int|string $action, $crud = '', $message = '', $params = '')
	{
		$applicants = empty($user_to) && !empty($fnum) ? self::getApplicantsByFnum([$fnum]) : [];

		return (new LogRepository())->add(self::buildLogEntity($user_from, $user_to, $fnum, $action, $crud, $message, $params, $applicants));
	}

	static function logs($user_from, $fnums, $action, $crud = '', $message = '', $params = '', $user_to = null)
	{
		if (empty($fnums))
		{
			Log::add('Error in action [' . $action . ' - ' . $crud . '] - ' . $message . ' fnums cannot be empty in EmundusModelLogs::logs', Log::WARNING, 'com_emundus');

			return false;
		}

		$fnums      = is_array($fnums) ? $fnums : [$fnums];
		$applicants = empty($user_to) ? self::getApplicantsByFnum($fnums) : [];

		$entities = [];
		foreach ($fnums as $fnum)
		{
			$entities[] = self::buildLogEntity($user_from, $user_to, $fnum, $action, $crud, $message, $params, $applicants);
		}

		return (new LogRepository())->addMany($entities);
	}

	/**
	 * Bridges the legacy signature onto LogEntity, which LogRepository is the single writer of.
	 *
	 * Without an explicit recipient, the entry goes to the applicant of the file.
	 *
	 * @param   array<string, int>  $applicants  applicant ids indexed by fnum
	 */
	private static function buildLogEntity($user_from, $user_to, $fnum, int|string $action, $crud, $message, $params, array $applicants = []): LogEntity
	{
		if (empty($user_to) && !empty($fnum))
		{
			$user_to = $applicants[$fnum] ?? null;
		}

		if (is_string($params) && $params !== '')
		{
			$decoded = json_decode($params, true);

			// Some callers pass plain text: kept as is, since setActionDetails never rendered it.
			$params = is_array($decoded) ? $decoded : ['raw' => $params];
		}

		return new LogEntity(
			userFrom: (int) $user_from,
			action: $action,
			crud: CrudEnum::tryFrom((string) $crud),
			message: (string) $message,
			params: is_array($params) ? $params : [],
			fnum: !empty($fnum) ? $fnum : null,
			userTo: !empty($user_to) ? (int) $user_to : null
		);
	}

	/**
	 * @return array<string, int>
	 */
	private static function getApplicantsByFnum(array $fnums): array
	{
		$applicants = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName(['fnum', 'applicant_id']))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . ' IN (' . implode(',', $db->quote($fnums)) . ')');

		try
		{
			$db->setQuery($query);
			$applicants = array_map('intval', $db->loadAssocList('fnum', 'applicant_id'));
		}
		catch (Exception $e)
		{
			Log::add('Could not resolve the applicants of the logged files : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $applicants;
	}

	/**
	 * Gets the actions done by a user. Can be filtered by action and/or CRUD.
	 * If the user is not specified, use the currently signed in one.
	 *
	 * @param   int     $user_from
	 * @param   int     $action
	 * @param   string  $crud
	 *
	 * @return Mixed Returns false on error and an array of objects on success.
	 * @since 3.8.8
	 */
	public function getUserActions($user_from = null, $action = null, $crud = null)
	{

		if (empty($user_from))
			$user_from = $this->user->id;

		// If the user ID from is not a number, something is wrong.
		if (!is_numeric($user_from)) {
			Log::add('Getting user actions in model/logs with a user ID that isnt a number.', Log::ERROR, 'com_emundus');

			return false;
		}

		$query = $this->db->getQuery(true);

		// Build a where depending on what params are present.
		$where = $this->db->quoteName('user_id_from') . '=' . $user_from;
		if (!empty($action) && is_numeric($action))
			$where .= ' AND ' . $this->db->quoteName('action_id') . '=' . $action;
		if (!empty($crud))
			$where .= ' AND ' . $this->db->quoteName('verb') . ' LIKE ' . $this->db->quote($crud);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_logs'))
			->where($where);

		$this->db->setQuery($query);

		try {
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Could not getUserActions in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/**
	 * Gets the actions done on a user. Can be filtered by action and/or CRUD.
	 * If no user_id is sent: use the currently signed in user.
	 *
	 * @param   int     $user_to
	 * @param   int     $action
	 * @param   string  $crud
	 *
	 * @return Mixed Returns false on error and an array of objects on success.
	 * @since 3.8.8
	 */
	public function getActionsOnUser($user_to = null, $action = null, $crud = null)
	{

		if (empty($user_to))
			$user_to = $this->user->id;

		// If the user ID from is not a number, something is wrong.
		if (!is_numeric($user_to)) {
			Log::add('Getting actions on user in model/logs with a user ID that isnt a number.', Log::ERROR, 'com_emundus');

			return false;
		}

		$query = $this->db->getQuery(true);

		// Build a where depending on what params are present.
		$where = $this->db->quoteName('user_id_to') . '=' . $user_to;
		if (!empty($action) && is_numeric($action))
			$where .= ' AND ' . $this->db->quoteName('action_id') . '=' . $action;
		if (!empty($crud))
			$where .= ' AND ' . $this->db->quoteName('verb') . ' LIKE ' . $this->db->quote($crud);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_logs'))
			->where($where);

		$this->db->setQuery($query);

		try {
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Could not getActionsOnUser in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/**
	 * Gets the actions done on an fnum. Can be filtered by user doing the action, the action itself, CRUD and/or banned logs.
	 *
	 * @param   int    $fnum
	 * @param   array  $user_from  // optional
	 * @param   array  $action     // optional
	 * @param   array  $crud       // optional
	 * @param   int    $offset
	 * @param   int    $limit
	 *
	 * @return Mixed Returns false on error and an array of objects on success.
	 * @since 3.8.8
	 */
	public function getActionsOnFnum($fnum, $user_from = null, $action = null, $crud = null, $offset = null, $limit = 100)
	{
		$results = [];
		$query   = $this->db->getQuery(true);

		$user_from = is_array($user_from) ? implode(',', $user_from) : $user_from;
		$action    = is_array($action) ? implode(',', $action) : $action;
		if (is_array($crud)) {
			$crud =  implode(',', $this->db->quote($crud));
		} else if (!empty($crud)) {
			$crud = $this->db->quote($crud);
		}

		$eMConfig       = ComponentHelper::getParams('com_emundus');
		$showTimeFormat = $eMConfig->get('log_show_timeformat', 0);
		$showTimeOrder  = $eMConfig->get('log_show_timeorder', 'DESC');

		// Build a where depending on what params are present.
		$where = $this->db->quoteName('fnum_to') . ' LIKE ' . $this->db->quote($fnum);
		if (!empty($user_from))
			$where .= ' AND ' . $this->db->quoteName('user_id_from') . ' IN (' . $user_from . ')';
		if (!empty($action))
			$where .= ' AND ' . $this->db->quoteName('action_id') . ' IN (' . $action . ')';
		if (!empty($crud))
			$where .= ' AND ' . $this->db->quoteName('verb') . ' IN ( ' . $crud . ')';

		$query->select('lg.*, us.firstname, us.lastname, us.is_anonym, ecc.applicant_id, ecc.anonymous')
			->from($this->db->quoteName('#__emundus_logs', 'lg'))
			->leftJoin($this->db->quoteName('#__emundus_users', 'us') . ' ON ' . $this->db->QuoteName('us.user_id') . ' = ' . $this->db->QuoteName('lg.user_id_from'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quoteName('lg.fnum_to'))
			->where($where)
			->order($this->db->quoteName('lg.timestamp').' '.$showTimeOrder.', '.$this->db->quoteName('lg.id').' '.$showTimeOrder);

		if (!is_null($offset)) {
			$query->setLimit($limit, $offset);
		}

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			$masked_user_id = $this->getMaskedActorId($results);

			foreach ($results as $result) {
				$result->date = EmundusHelperDate::displayDate($result->timestamp, 'DATE_FORMAT_LC2', (int) $showTimeFormat);

				if ($result->is_anonym == 1 || ($masked_user_id > 0 && (int) $result->user_id_from === $masked_user_id)) {
					$result->firstname = Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
					$result->lastname  = $result->user_id_from;
					$result->ip_from   = '';
				}

				unset($result->applicant_id, $result->anonymous);
			}
		}
		catch (Exception $e) {
			Log::add('Could not getActionsOnFnum in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $results;
	}


	/**
	 * Gets the actions done by users on each other. In both directions.
	 *
	 * @param   int     $user1
	 * @param   int     $user2
	 * @param   int     $action
	 * @param   string  $crud
	 *
	 * @return Mixed Returns false on error and an array of objects on success.
	 * @since 3.8.8
	 */
	public function getActionsBetweenUsers($user1, $user2 = null, $action = null, $crud = null)
	{

		if (empty($user2))
			$user2 = $this->user->id;

		// If the user ID from is not a number, something is wrong.
		if (!is_numeric($user1) || !is_numeric($user2)) {
			Log::add('Getting actions between users in model/logs with a user ID that isnt a number.', Log::ERROR, 'com_emundus');

			return false;
		}

		$query = $this->db->getQuery(true);

		// Build a where depending on what params are present.
		// Actions are in both directions, this means that both users can be the user_to or user_from.
		$where = '(' . $this->db->quoteName('user_id_to') . '=' . $user1 . ' OR ' . $this->db->quoteName('user_id_from') . '=' . $user1 . ') AND (' . $this->db->quoteName('user_id_to') . '=' . $user2 . ' OR ' . $this->db->quoteName('user_id_from') . '=' . $user2 . ')';
		if (!empty($action) && is_numeric($action))
			$where .= ' AND ' . $this->db->quoteName('action_id') . '=' . $action;
		if (!empty($crud))
			$where .= ' AND ' . $this->db->quoteName('verb') . ' LIKE ' . $this->db->quote($crud);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_logs'))
			->where($where);

		$this->db->setQuery($query);

		try {
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Could not getActionsBetweenUsers in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/**
	 * Writes the details that will be shown in the logs menu.
	 *
	 * @param   int          $action
	 * @param   string       $crud
	 * @param   string       $params
	 * @param   string|null  $message  Language key stored on the entry. Omitted, the name falls
	 *                                 back to the key derived from the action label and the verb.
	 *
	 * @return Mixed Returns false on error and an array of strings on success.
	 * @since 3.8.8
	 */
	public function setActionDetails($action = null, $crud = null, $params = null, ?string $message = null)
	{
		// Get the action label
		$query = $this->db->getQuery(true);
		$query->select('label')
			->from($this->db->quoteName('#__emundus_setup_actions'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($action));
		$this->db->setQuery($query);
		$action_category = $this->db->loadResult();

		// Decode the json params string
		if ($params) {
			$params = json_decode($params);
		}

		// Define action_details
		$action_details = '';

		// Complete action name with crud
		switch ($crud) {
			case ('c'):
				$action_name = $action_category . '_CREATE';
				foreach ($params->created ?? [] as $value) {
					if (is_object($value)) {
						if (!empty($value->element)) {
							$action_details .= '<span style="margin-bottom: 0.5rem"><b>' . $value->element . '</b></span>';
						}
						if (!empty($value->details)) {
							$action_details .= '<div class="tw-flex tw-items-center"><span class="tw-text-green-600">' . $value->details . '</span></div>';
						}
					}
					else {
						$action_details .= '<p>' . $value . '</p>';
					}
				}
				break;
			case ('r'):
				$action_name = $action_category . '_READ';
				break;
			case ('u'):
				$action_name = $action_category . '_UPDATE';

				if (!empty($params->updated)) {
					$description    = reset($params->updated)->description ?? '';
					$action_details = !empty($description) ? '<b>' . $description . '</b>' : '';

					foreach ($params->updated as $value) {
						$action_details .= '<div class="tw-flex tw-items-center">';
						if(!empty($value->element)) {
							$action_details .= '<span>' . $value->element . '&nbsp</span>&nbsp';
						}
						$value->old     = !empty($value->old) ? $value->old : '';
						$value->new     = !empty($value->new) ? $value->new : '';

						if (!empty($value->old)) {
							$value->old = explode('<#>', $value->old);

							foreach ($value->old as $_old) {
								if (empty(trim($_old))) {
									$action_details .= '<span class="tw-text-blue-500">' . Text::_('COM_EMUNDUS_EMPTY_OR_NULL_MODIF') . '</span>&nbsp';
								}
								else {
									$action_details .= '<span class="tw-text-red-700" style="text-decoration: line-through">' . $_old . '</span>&nbsp';
								}
							}
						}

						if (!empty($value->new)) {
							$action_details .= '<span>' . Text::_('COM_EMUNDUS_CHANGE_TO') . '</span>&nbsp';

							$value->new = explode('<#>', $value->new);
							foreach ($value->new as $_new) {
								if (empty(trim($_new))) {
									$action_details .= '<span class="tw-text-blue-500">' . Text::_('COM_EMUNDUS_EMPTY_OR_NULL_MODIF') . '</span>&nbsp';
								}
								else {
									$action_details .= '<span class="tw-text-green-600">' . $_new . '</span>&nbsp';
								}
							}
						}

						$action_details .= '</div>';
					}
				}
				break;
			case ('d'):
				$action_name = $action_category . '_DELETE';
				foreach ($params->deleted ?? [] as $value) {
					if (is_object($value)) {
						if (!empty($value->element)) {
							$action_details .= '<span style="margin-bottom: 0.5rem"><b>' . $value->element . '</b></span>';
						}
						if (!empty($value->details)) {
							$action_details .= '<div class="tw-flex tw-flex-row"><span class="tw-text-red-700">' . $value->details . '</span></div>';
						}
					}
					else {
						$action_details .= '<p>' . $value . '</p>';
					}
				}
				break;
			default:
				$action_name = $action_category . '_READ';
				break;
		}

		// All action details are set, time to return them
		$details                    = [];
		$details['action_category'] = Text::_($action_category);
		$details['action_name']     = $this->getActionDisplayName($action_name, $message, $params);
		$details['action_details']  = $action_details;

		return $details;
	}

	/**
	 * What the history shows in the action column.
	 *
	 * An entry written by an automation names the automation, so a reader can tell which one
	 * acted on the file; otherwise the stored language key wins, and the key derived from the
	 * action label and the verb is the last resort.
	 */
	private function getActionDisplayName(string $derivedKey, ?string $message, $params): string
	{
		$automation = $params->automation ?? null;

		if (empty($automation->label))
		{
			return Text::_(!empty($message) ? $message : $derivedKey);
		}

		// Author-supplied text reaching a template that echoes action_name unescaped.
		$label = htmlspecialchars($automation->label, ENT_QUOTES, 'UTF-8');

		// Concatenated rather than interpolated: a missing key must never swallow the origin.
		return Text::_('COM_EMUNDUS_LOGS_AUTOMATION_ORIGIN') . ' - ' . $label;
	}

	public function exportLogs($fnum, $users, $actions, $crud)
	{
		$actions = $this->getActionsOnFnum($fnum, $users, $actions, $crud, null, null);
		if (!empty($actions)) {
			$lines = [
				[
					Text::_('DATE'),
					Text::_('USER'),
					"to User",
					Text::_('COM_EMUNDUS_LOGS_VIEW_ACTION'),
					Text::_('COM_EMUNDUS_LOGS_VIEW_ACTION_DETAILS')
				]
			];
			foreach ($actions as $action) {
				$details        = $this->setActionDetails($action->action_id, $action->verb, $action->params, $action->message);
				$action_details = str_replace('&nbsp', ' ', strip_tags($details['action_details']));
				$action_details = str_replace('\n', '', $action_details);
				$action_details = str_replace("arrow_forward", " -> ", $action_details);

				$lines[] = [
					HTMLHelper::_('date', $action->timestamp, Text::_('DATE_FORMAT_LC2')),
					$action->firstname . ' ' . $action->lastname,
					$fnum,
					trim(html_entity_decode(strip_tags($details['action_name']), ENT_QUOTES, 'UTF-8')),
					trim($action_details)
				];
			}

			$csv_file = '';
			foreach ($lines as $line) {
				$csv_file .= implode(';', $line) . "\n";
			}

			$file = JPATH_ROOT . '/tmp/' . $fnum . '_logs.csv';

			$fp = fopen($file, 'w');
			if ($fp) {
				fwrite($fp, $csv_file);
				fclose($fp);

				return Uri::base() . 'index.php?option=com_emundus&task=getfile&u=tmp/' . $fnum . '_logs.csv';
			}
			else {
				Log::add('Could not create csv file in model logs', Log::ERROR, 'com_emundus');
			}
		}

		return false;
	}

	/**
	 * Gets the id of the user whose identity must be hidden in log rows, 0 when nobody has to be.
	 *
	 * Only the applicant can be hidden by the file anonymity, and never to themselves. All the rows
	 * belong to the same fnum, so the applicant_id / anonymous columns carried by the join are read once.
	 *
	 * @param   array  $rows  log rows selecting ecc.applicant_id and ecc.anonymous, us.is_anonym
	 *
	 * @return int
	 */
	private function getMaskedActorId(array $rows): int
	{
		if (empty($rows)) {
			return 0;
		}

		$viewer_id    = (int) $this->user->id;
		$applicant_id = (int) ($rows[0]->applicant_id ?? 0);

		if (empty($applicant_id) || $applicant_id === $viewer_id) {
			return 0;
		}

		// The applicant account flag is already carried by us.is_anonym on their own rows.
		$anonymize = EmundusHelperFiles::shouldAnonymize($viewer_id, ($rows[0]->is_anonym ?? 0) === 1, ($rows[0]->anonymous ?? 0) === 1);

		return $anonymize ? $applicant_id : 0;
	}

	public function getUsersLogsByFnum($fnum)
	{
		$logs  = [];
		$query = $this->db->getQuery(true);

		if (!empty($fnum)) {
			$query->clear()
				->select('distinct(ju.id) as uid, ju.name, jeu.is_anonym, ecc.applicant_id, ecc.anonymous')
				->from($this->db->quoteName('jos_users', 'ju'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'jeu') . ' ON ' . $this->db->quoteName('jeu.user_id') . ' = ' . $this->db->quoteName('ju.id'))
				->leftJoin($this->db->quoteName('#__emundus_logs', 'jel') . ' ON ' . $this->db->quoteName('jel.user_id_from') . ' = ' . $this->db->quoteName('ju.id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quoteName('jel.fnum_to'))
				->where($this->db->quoteName('jel.fnum_to') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$logs = $this->db->loadObjectList();

				$masked_user_id = $this->getMaskedActorId($logs);

				foreach ($logs as $log) {
					if (($masked_user_id > 0 && (int) $log->uid === $masked_user_id)) {
						$log->name = Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
					}

					unset($log->applicant_id, $log->anonymous);
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/files | Error when get all affected user by fnum' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage() . '#fnum = ' . $fnum), Log::ERROR, 'com_emundus');
			}
		}

		return $logs;
	}

	/**
	 * @param $date   DateTime  Date to delete logs before
	 * @description             Deletes logs before a given date.
	 * @return int
	 */
	public function deleteLogsBeforeADate($date)
	{
		$deleted_logs = 0;

		if (!empty($date))
		{
			$query = $this->db->getQuery(true);

			$query->delete($this->db->quoteName('#__emundus_logs'))
				->where($this->db->quoteName('timestamp') . ' < ' . $this->db->quote($date->format('Y-m-d H:i:s')));

			try
			{
				$this->db->setQuery($query);
				$this->db->execute();
				$deleted_logs = $this->db->getAffectedRows();
			}
			catch (Exception $e)
			{
				Log::add('Could not delete logs from jos_emundus_logs table in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $deleted_logs;
	}

	/**
	 * TODO: Split with pagination in case of first run in order to avoid memory issues
	 * @param $date   DateTime  Date to export logs before
	 * @description             Exports logs before a given date.
	 * @return string
	 */
	public function exportLogsBeforeADate($date)
	{
		$csv_filename = '';

		if (!empty($date))
		{
			$limit = 10000;
			$offset = 0;
			$header_written = false;

			do {
				$query = $this->db->getQuery(true);

				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__emundus_logs'))
					->where($this->db->quoteName('timestamp') . ' < ' . $this->db->quote($date->format('Y-m-d H:i:s')))
					->setLimit($limit, $offset);

				try
				{
					$this->db->setQuery($query);
					$logs = $this->db->loadAssocList();
				}
				catch (Exception $e)
				{
					Log::add('Could not fetch logs from jos_emundus_logs table in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
					break;
				}

				if (!empty($logs))
				{
					if (!$header_written) {
						$csv_filename = JPATH_SITE . '/tmp/backup_logs_' . date('Y-m-d_H-i-s') . '.csv';
						$csv_file     = fopen($csv_filename, 'w');
						fputcsv($csv_file, array_keys($logs[0]));
						$header_written = true;
					}

					foreach ($logs as $log)
					{
						fputcsv($csv_file, $log);
					}
					$offset += $limit;
				}

			} while (count($logs) == $limit);

			if ($header_written) {
				fclose($csv_file);
			}
		}

		return $csv_filename;
	}

	public function getActionId($action_name)
	{
		$action_id = 0;

		if (!empty($action_name)) {
			$query = $this->db->getQuery(true);
			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_setup_actions'))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote($action_name));

			$this->db->setQuery($query);
			$action_id = $this->db->loadResult();
		}

		return $action_id;
	}
}
