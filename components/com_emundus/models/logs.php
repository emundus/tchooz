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

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');

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
	 * @param   int     $action
	 * @param   string  $crud
	 * @param   string  $message
	 *
	 * @since 3.8.8
	 */
	static function log($user_from, $user_to, $fnum, $action, $crud = '', $message = '', $params = '')
	{
		$logged = false;

		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.logs.php'], Log::ERROR, 'com_emundus');

		if (!empty($user_from)) {
			$eMConfig                 = ComponentHelper::getParams('com_emundus');
			$log_actions              = $eMConfig->get('log_actions', []);
			if (!empty($log_actions)) {
				$log_actions = explode(',', $log_actions);
			}

			$log_actions_exclude      = $eMConfig->get('log_actions_exclude', []);
			if (!empty($log_actions_exclude)) {
				$log_actions_exclude = explode(',', $log_actions_exclude);
			}
			$log_actions_exclude_user = $eMConfig->get('log_actions_exclude_user', 62);
			$log_actions_exclude_user = empty($log_actions_exclude_user) ? [] : explode(',', $log_actions_exclude_user);

			if ($eMConfig->get('logs', 0) && (empty($log_actions) || in_array($action, $log_actions))) {
				if (!in_array($action, $log_actions_exclude)) {
					if (!in_array($user_from, $log_actions_exclude_user)) {
						$db    = Factory::getContainer()->get('DatabaseDriver');
						$query = $db->getQuery(true);

						$ip      = Factory::getApplication()->input->server->get('REMOTE_ADDR', '');
						$user_to = empty($user_to) ? '' : $user_to;

						$now = EmundusHelperDate::getNow();

						$columns = ['timestamp', 'user_id_from', 'user_id_to', 'fnum_to', 'action_id', 'verb', 'message', 'params', 'ip_from'];
						$values  = [$db->quote($now), $db->quote($user_from), $db->quote($user_to), $db->quote($fnum), $action, $db->quote($crud), $db->quote($message), $db->quote($params), $db->quote($ip)];

						$query->insert($db->quoteName('#__emundus_logs'))
							->columns($db->quoteName($columns))
							->values(implode(',', $values));

						try {
							$db->setQuery($query);
							$logged = $db->execute();
						}
						catch (Exception $e) {
							Log::add('Error logging at the following query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
						}
					}
				}
			}
		}
		else {
			Log::add('Error in action [' . $action . ' - ' . $crud . '] - ' . $message . ' user_from cannot be null in EmundusModelLogs::log', Log::WARNING, 'com_emundus');
		}

		return $logged;
	}

	static function logs($user_from, $fnums, $action, $crud = '', $message = '', $params = '') {
		$logged = false;
		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.logs.php'], Log::ERROR, 'com_emundus');

		if (!empty($user_from) && !empty($fnums)) {
			if (!is_array($fnums)) {
				$fnums = [$fnums];
			}

			$eMConfig = ComponentHelper::getParams('com_emundus');
			$log_actions = $eMConfig->get('log_actions', null);
			$log_actions_exclude = $eMConfig->get('log_actions_exclude', null);
			$log_actions_exclude_user = $eMConfig->get('log_actions_exclude_user', 62);

			if ($eMConfig->get('logs', 0) && (empty($log_actions) || in_array($action, explode(',',$log_actions)))) {
				if (!in_array($action, explode(',', $log_actions_exclude))) {
					if (!in_array($user_from, explode(',', $log_actions_exclude_user))) {
						$db = Factory::getContainer()->get('DatabaseDriver');
						$query = $db->getQuery(true);

						$ip = Factory::getApplication()->input->server->get('REMOTE_ADDR','');
						$user_to = empty($user_to) ? null : $user_to;

						$now = EmundusHelperDate::getNow();

						$columns = ['timestamp', 'user_id_from', 'user_id_to', 'fnum_to', 'action_id', 'verb', 'message', 'params', 'ip_from'];
						$query->insert($db->quoteName('#__emundus_logs'))
							->columns($db->quoteName($columns));

						foreach($fnums as $fnum) {
							$query->values($db->quote($now) . ',' . $db->quote($user_from) . ', null,' . $db->quote($fnum) . ',' . $action . ',' . $db->quote($crud) . ',' . $db->quote($message). ',' . $db->quote($params) . ',' . $db->quote($ip));
						}

						try {
							$db->setQuery($query);
							$logged = $db->execute();
						} catch (Exception $e) {
							Log::add('Error logging at the following query: ' . preg_replace("/[\r\n]/"," ",$query->__toString().' -> '.$e->getMessage()), Log::ERROR, 'com_emundus.error');
						}
					}
				}
			}
		} else {
			Log::add('Error in action [' . $action . ' - ' . $crud . '] - ' . $message . ' user_from cannot be null in EmundusModelLogs::logs', Log::WARNING, 'com_emundus');
		}


		return $logged;
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

		$query->select('lg.*, us.firstname, us.lastname')
			->from($this->db->quoteName('#__emundus_logs', 'lg'))
			->leftJoin($this->db->quoteName('#__emundus_users', 'us') . ' ON ' . $this->db->QuoteName('us.user_id') . ' = ' . $this->db->QuoteName('lg.user_id_from'))
			->where($where)
			->order($this->db->quoteName('lg.timestamp').' '.$showTimeOrder.', '.$this->db->quoteName('lg.id').' '.$showTimeOrder);

		if (!is_null($offset)) {
			$query->setLimit($limit, $offset);
		}

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			foreach ($results as $result) {
				$result->date = EmundusHelperDate::displayDate($result->timestamp, 'DATE_FORMAT_LC2', (int) $showTimeFormat);
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
	 * @param   int     $action
	 * @param   string  $crud
	 * @param   string  $params
	 *
	 * @return Mixed Returns false on error and an array of strings on success.
	 * @since 3.8.8
	 */
	public function setActionDetails($action = null, $crud = null, $params = null)
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
				foreach ($params->created as $value) {
					if (is_object($value)) {
						if (!empty($value->element)) {
							$action_details .= '<span style="margin-bottom: 0.5rem"><b>' . $value->element . '</b></span>';
						}
						if (!empty($value->details)) {
							$action_details .= '<div class="tw-flew tw-items-center"><span class="em-red-500-color">' . $value->details . '</span></div>';
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
					$action_details = '<b>' . reset($params->updated)->description . '</b>';

					foreach ($params->updated as $value) {
						$action_details .= '<div class="tw-flex tw-items-center">';
						if(!empty($value->element)) {
							$action_details .= '<span>' . $value->element . '&nbsp</span>&nbsp';
						}
						$value->old     = !empty($value->old) ? $value->old : '';
						$value->new     = !empty($value->new) ? $value->new : '';

						$value->old = explode('<#>', $value->old);


						foreach ($value->old as $_old) {
							if (empty(trim($_old))) {
								$action_details .= '<span class="em-blue-500-color">' . Text::_('COM_EMUNDUS_EMPTY_OR_NULL_MODIF') . '</span>&nbsp';
							}
							else {
								$action_details .= '<span class="em-red-500-color" style="text-decoration: line-through">' . $_old . '</span>&nbsp';
							}
						}

						$action_details .= '<span>' . Text::_('COM_EMUNDUS_CHANGE_TO') . '</span>&nbsp';

						$value->new = explode('<#>', $value->new);
						foreach ($value->new as $_new) {
							if (empty(trim($_new))) {
								$action_details .= '<span class="em-blue-500-color">' . Text::_('COM_EMUNDUS_EMPTY_OR_NULL_MODIF') . '</span>&nbsp';
							}
							else {
								$action_details .= '<span class="em-main-500-color">' . $_new . '</span>&nbsp';
							}
						}

						$action_details .= '</div>';
					}
				}
				break;
			case ('d'):
				$action_name = $action_category . '_DELETE';
				foreach ($params->deleted as $value) {
					if (is_object($value)) {
						if (!empty($value->element)) {
							$action_details .= '<span style="margin-bottom: 0.5rem"><b>' . $value->element . '</b></span>';
						}
						if (!empty($value->details)) {
							$action_details .= '<div class="em-flex-row"><span class="em-red-500-color">' . $value->details . '</span></div>';
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

		// Translate with Text
		$action_category = Text::_($action_category);
		$action_name     = Text::_($action_name);

		// All action details are set, time to return them
		$details                    = [];
		$details['action_category'] = $action_category;
		$details['action_name']     = $action_name;
		$details['action_details']  = $action_details;

		return $details;
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
				$details        = $this->setActionDetails($action->action_id, $action->verb, $action->params);
				$action_details = str_replace('&nbsp', ' ', strip_tags($details['action_details']));
				$action_details = str_replace('\n', '', $action_details);
				$action_details = str_replace("arrow_forward", " -> ", $action_details);

				$lines[] = [
					HTMLHelper::_('date', $action->timestamp, Text::_('DATE_FORMAT_LC2')),
					$action->firstname . ' ' . $action->lastname,
					$fnum,
					Text::_($action->message),
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

				return Uri::base() . 'tmp/' . $fnum . '_logs.csv';
			}
			else {
				Log::add('Could not create csv file in model logs', Log::ERROR, 'com_emundus');
			}
		}

		return false;
	}

	public function getUsersLogsByFnum($fnum)
	{
		$logs  = [];
		$query = $this->db->getQuery(true);

		if (!empty($fnum)) {
			$query->clear()
				->select('distinct(ju.id) as uid, ju.name')
				->from($this->db->quoteName('jos_users', 'ju'))
				->leftJoin($this->db->quoteName('#__emundus_logs', 'jel') . ' ON ' . $this->db->quoteName('jel.user_id_from') . ' = ' . $this->db->quoteName('ju.id'))
				->where($this->db->quoteName('jel.fnum_to') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$logs = $this->db->loadObjectList();
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
	 * @param $date   DateTime  Date to export logs before
	 * @description             Exports logs before a given date.
	 * @return string
	 */
	public function exportLogsBeforeADate($date)
	{
		$csv_filename = '';

		if (!empty($date))
		{
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from($this->db->quoteName('#__emundus_logs'))
				->where($this->db->quoteName('timestamp') . ' < ' . $this->db->quote($date->format('Y-m-d H:i:s')));

			try
			{
				$this->db->setQuery($query);
				$logs = $this->db->loadAssocList();
			}
			catch (Exception $e)
			{
				Log::add('Could not fetch logs from jos_emundus_logs table in model logs at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

			if (!empty($logs))
			{
				$csv_filename = JPATH_SITE . '/tmp/backup_logs_' . date('Y-m-d_H-i-s') . '.csv';
				$csv_file     = fopen($csv_filename, 'w');
				fputcsv($csv_file, array_keys($logs[0]));
				foreach ($logs as $log)
				{
					fputcsv($csv_file, $log);
				}
				fclose($csv_file);
			}
		}

		return $csv_filename;
	}
}
