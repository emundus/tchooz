<?php
/**
 * @package       eMundus
 * @version       6.6.5
 * @author        eMundus.fr
 * @copyright (C) 2019 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;

class plgEmundusCustom_event_handler extends CMSPlugin
{
	/**
	 * @var EmundusHelperEvents
	 * @since version 1.0.0
	 */
	private $hEvents = null;

	/**
	 * @var null
	 * @since version 1.40.0
	 */
	private $_searchData = null;

	private $automated_task_user = 1;

	private $form_categories = ['Form', 'Evaluation'];

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.custom_event_handler.php'), Log::ALL, array('com_emundus.custom_event_handler'));

		require_once(JPATH_SITE . '/components/com_emundus/helpers/events.php');
		$this->hEvents = new EmundusHelperEvents();

		$emundus_config = ComponentHelper::getParams('com_emundus');
		$this->automated_task_user = $emundus_config->get('automated_task_user', 1);
	}


	function onCallEventHandler(string $event, array $args = null): array
	{
		try
		{
			$events = [];
			$events_types = [];
			$event_config = [];
			$codes  = [];
			$params = json_decode($this->params);

			if (!empty($params) && !empty($params->event_handlers))
			{
				foreach ($params->event_handlers as $event_handler)
				{
					if ($event_handler->event == $event && $event_handler->published)
					{
						$events[] = $event_handler->event;
						$codes[]  = $event_handler->code;
						$event_config[] = $event_handler;
					}
				}
			}

			$returned_values = [];

			foreach ($events as $index => $caller_index)
			{
				try
				{
					if (!empty($event_config[$index]->type) && $event_config[$index]->type == 'options') {
						$event_category = $this->getEventCategory($event_config[$index]->event);

						if (in_array($event_category, $this->form_categories)) {
							if (!empty($args['formModel']->formDataWithTableName)) {
								$data = $args['formModel']->formDataWithTableName;
							} else  {
								$data = $args['formModel']->getData();
							}
						} else {
							$data = $args;
						}

						$returned_values[$caller_index] = $this->runEventSimpleAction($event_config[$index], $data);
					} else {
						$returned_values[$caller_index] = $this->_runPHP($codes[$index], $args);
					}
				}
				catch (ParseError $p)
				{
					Log::add('Error while running event ' . $caller_index . ' : "' . $p->getMessage() . '"', Log::ERROR, 'com_emundus.custom_event_handler');
					continue;
				}
			}

			if (method_exists($this->hEvents, $event))
			{
				$returned_values[$event] = $this->hEvents->{$event}($args);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while running event ' . $event . ' : "' . $e->getMessage() . '"', Log::ERROR, 'com_emundus.custom_event_handler');
		}

		return $returned_values;
	}

	private function _runPHP($code = '', $data = null)
	{
		$php_result = true;

		if (class_exists('FabrikWorker'))
		{
			$w    = new FabrikWorker;
			$code = $w->parseMessageForPlaceHolder($code, $data);
		}
		else
		{
			$code = $this->parseMessageForPlaceHolder($code, $data);
		}

		try
		{
			$php_result = eval($code);
		}
		catch (ParseError $p)
		{
			Log::add('Error while running event ' . $code . ' : "' . $p->getMessage() . '"', Log::ERROR, 'com_emundus.custom_event_handler');

			return false;
		}

		return $php_result;
	}

	private function parseMessageForPlaceHolder($msg, $searchData = null, $keepPlaceholders = true, $addSlashes = false, $theirUser = null, $unsafe = true)
	{
		$returnType = is_array($msg) ? 'array' : 'string';
		$messages   = (array) $msg;

		foreach ($messages as &$msg)
		{
			$this->parseAddSlashes = $addSlashes;

			if (!($msg == '' || is_array($msg) || \Joomla\String\StringHelper::strpos($msg, '{') === false))
			{
				$msg = str_replace(array('%7B', '%7D'), array('{', '}'), $msg);

				if (is_object($searchData))
				{
					$searchData = ArrayHelper::fromObject($searchData);
				}
				// Merge in request and specified search data
				$f                 = \Joomla\CMS\Filter\InputFilter::getInstance();
				$post              = $f->clean($_REQUEST, 'array');
				$this->_searchData = is_null($searchData) ? $post : array_merge($post, $searchData);

				// Enable users to use placeholder to insert session token
				$this->_searchData['JSession::getFormToken'] = \Joomla\CMS\Session\Session::getFormToken();

				// Replace with the user's data
				$msg = self::replaceWithUserData($msg);

				if (!is_null($theirUser))
				{
					// Replace with a specified user's data
					$msg = self::replaceWithUserData($msg, $theirUser, 'your');
				}

				$msg = self::replaceWithGlobals($msg);

				if (!$unsafe)
				{
					$msg = self::replaceWithUnsafe($msg);
					$msg = self::replaceWithSession($msg);
				}

				$msg = preg_replace("/{}/", "", $msg);

				// Replace {element name} with form data
				$msg = preg_replace_callback("/{([^}\s]+(\|\|[\w|\s]+|<\?php.*\?>)*)}/i", array($this, 'replaceWithFormData'), $msg);

				if (!$keepPlaceholders)
				{
					$msg = preg_replace("/{[^}\s]+}/i", '', $msg);
				}
			}
		}

		return $returnType === 'array' ? $messages : ArrayHelper::getValue($messages, 0, '');
	}

	private static function replaceWithUserData($msg, $user = null, $prefix = 'my')
	{
		$app = Factory::getApplication();

		if (is_null($user))
		{
			$user = Factory::getUser();
		}

		$user->levels = $user->getAuthorisedViewLevels();

		if (is_object($user))
		{
			foreach ($user as $key => $val)
			{
				if (substr($key, 0, 1) != '_')
				{
					if (!is_object($val) && !is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', $val, $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', $val, $msg);
					}
					elseif (is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', implode(',', $val), $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', implode(',', $val), $msg);
					}
				}
			}
		}
		/*
		 *  $$$rob parse another users data into the string:
		 *  format: is {$their->var->email} where var is the $app->input var to search for
		 *  e.g url - index.php?owner=62 with placeholder {$their->owner->id}
		 *  var should be an integer corresponding to the user id to load
		 */
		$matches = array();
		preg_match('/{\$their-\>(.*?)}/', $msg, $matches);

		foreach ($matches as $match)
		{
			$bits = explode('->', str_replace(array('{', '}'), '', $match));

			if (count($bits) !== 3)
			{
				continue;
			}

			$userId = $app->input->getInt(ArrayHelper::getValue($bits, 1));

			// things like user elements might be single entry arrays
			if (is_array($userId))
			{
				$userId = array_pop($userId);
			}

			if (!empty($userId))
			{
				$user = Factory::getUser($userId);
				$val  = $user->get(ArrayHelper::getValue($bits, 2));
				$msg  = str_replace($match, $val, $msg);
			}
		}

		return $msg;
	}

	private static function replaceWithGlobals($msg)
	{
		$replacements = self::globalReplacements();

		foreach ($replacements as $key => $value)
		{
			$msg = str_replace($key, $value, $msg);
		}

		return $msg;
	}

	private static function globalReplacements()
	{
		$app     = Factory::getApplication();
		$itemId  = self::itemId();
		$config  = Factory::getConfig();
		$session = Factory::getSession();
		$token   = $session->get('session.token');

		$replacements = array(
			'{$jConfig_live_site}' => COM_FABRIK_LIVESITE,
			'{$jConfig_offset}'    => $config->get('offset'),
			'{$Itemid}'            => $itemId,
			'{$jConfig_sitename}'  => $config->get('sitename'),
			'{$jConfig_mailfrom}'  => $config->get('mailfrom'),
			'{where_i_came_from}'  => $app->input->server->get('HTTP_REFERER', '', 'string'),
			'{date}'               => date('Ymd'),
			'{year}'               => date('Y'),
			'{mysql_date}'         => date('Y-m-d H:i:s'),
			'{session.token}'      => $token
		);

		foreach ($_SERVER as $key => $val)
		{
			if (!is_object($val) && !is_array($val))
			{
				$replacements['{$_SERVER->' . $key . '}']    = $val;
				$replacements['{$_SERVER-&gt;' . $key . '}'] = $val;
			}
		}

		if ($app->isClient('administrator'))
		{
			$replacements['{formview}']    = 'task=form.view';
			$replacements['{listview}']    = 'task=list.view';
			$replacements['{detailsview}'] = 'task=details.view';
		}
		else
		{
			$replacements['{formview}']    = 'view=form';
			$replacements['{listview}']    = 'view=list';
			$replacements['{detailsview}'] = 'view=details';
		}

		return array_merge($replacements, self::langReplacements());
	}

	public static function langReplacements()
	{
		$langtag   = Factory::getLanguage()->getTag();
		$lang      = str_replace('-', '_', $langtag);
		$shortlang = explode('_', $lang);
		$shortlang = $shortlang[0];
		$multilang = self::getMultiLangURLCode();

		$replacements = array(
			'{lang}'      => $lang,
			'{langtag}'   => $langtag,
			'{multilang}' => $multilang,
			'{shortlang}' => $shortlang,
		);

		return $replacements;
	}

	public static function replaceWithUnsafe($msg)
	{
		$replacements = self::unsafeReplacements();

		foreach ($replacements as $key => $value)
		{
			$msg = str_replace($key, $value, $msg);
		}

		return $msg;
	}

	public static function unsafeReplacements()
	{
		$config = Factory::getConfig();

		$replacements = array(
			'{$jConfig_absolute_path}' => JPATH_SITE,
			'{$jConfig_secret}'        => $config->get('secret')
		);

		return $replacements;
	}

	public static function replaceWithSession($msg)
	{
		if (strstr($msg, '{$session->'))
		{
			$session     = Factory::getSession();
			$sessionData = array(
				'id'        => $session->getId(),
				'token'     => $session->get('session.token'),
				'formtoken' => \Joomla\CMS\Session\Session::getFormToken()
			);

			foreach ($sessionData as $key => $value)
			{
				$msg = str_replace('{$session->' . $key . '}', $value, $msg);
			}

			$msg = preg_replace_callback(
				'/{\$session-\>(.*?)}/',
				function ($matches) use ($session) {
					$bits = explode(':', $matches[1]);

					if (count($bits) > 1)
					{
						$sessionKey = $bits[1];
						$nameSpace  = $bits[0];
					}
					else
					{
						$sessionKey = $bits[0];
						$nameSpace  = 'default';
					}

					$val = $session->get($sessionKey, '', $nameSpace);

					if (is_string($val))
					{
						return $val;
					}
					else
					{
						if (is_numeric($val))
						{
							return (string) $val;
						}
					}

					return '';
				},
				$msg
			);
		}

		return $msg;
	}

	public static function itemId($listId = null)
	{
		static $listIds = array();

		$app = Factory::getApplication();

		if (!$app->isAdmin())
		{
			// Attempt to get Itemid from possible list menu item.
			if (!is_null($listId))
			{
				if (!array_key_exists($listId, $listIds))
				{
					$db         = Factory::getDbo();
					$myLanguage = Factory::getLanguage();
					$myTag      = $myLanguage->getTag();
					$qLanguage  = !empty($myTag) ? ' AND ' . $db->q($myTag) . ' = ' . $db->qn('m.language') : '';
					$query      = $db->getQuery(true);
					$query->select('m.id AS itemId')->from('#__extensions AS e')
						->leftJoin('#__menu AS m ON m.component_id = e.extension_id')
						->where('e.name = "com_fabrik" and e.type = "component" and m.link LIKE "%listid=' . $listId . '"' . $qLanguage);
					$db->setQuery($query);

					if ($itemId = $db->loadResult())
					{
						$listIds[$listId] = $itemId;
					}
					else
					{
						$listIds[$listId] = false;
					}
				}
				else
				{
					if ($listIds[$listId] !== false)
					{
						return $listIds[$listId];
					}
				}
			}

			$itemId = (int) $app->input->getInt('itemId');

			if ($itemId !== 0)
			{
				return $itemId;
			}

			$menus = $app->getMenu();
			$menu  = $menus->getActive();

			if (is_object($menu))
			{
				return $menu->id;
			}
		}

		return null;
	}

	public static function getMultiLangURLCode()
	{
		$multiLang = false;

		if (JLanguageMultilang::isEnabled())
		{
			$lang      = Factory::getLanguage()->getTag();
			$languages = LanguageHelper::getLanguages();
			foreach ($languages as $language)
			{
				if ($language->lang_code === $lang)
				{
					$multiLang = $language->sef;
					break;
				}
			}
		}

		return $multiLang;
	}

	public function getEventCategory($event_label): string
	{
		$category = '';

		if (!empty($event_label))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('category')
				->from('#__emundus_plugin_events')
				->where('label = ' . $db->quote($event_label));

			try
			{
				$db->setQuery($query);
				$category = $db->loadResult();
			}
			catch (Exception $e)
			{
				Log::add('Failed to get category for event ' . $event_label . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.custom_event_handler');
			}
		}

		return $category;
	}

	private function runEventSimpleAction($event, $data): bool
	{
		$status = false;

		if ($event->type === 'options' && !empty($event->custom_actions))
		{
			$fnums = $this->retrieveFnumsFromEventData($event, $data);

			if (!empty($fnums))
			{
				foreach ($fnums as $fnum)
				{
					foreach ($event->custom_actions as $custom_action)
					{
						$event_category = $this->getEventCategory($event->event);
						if (in_array($event_category, $this->form_categories)) {
							if (empty($event->form_ids))
							{
								Log::add('No form_ids found for event ' . $event->event . '. This is a necessary parameter to launch actions on forms.', Log::WARNING, 'com_emundus.custom_event_handler');
								continue;
							} else if (!in_array($data['formid'], $event->form_ids)) {
								continue;
							}
						}

						if (!empty($custom_action->conditions))
						{
							$pass = $this->checkEventConditions($custom_action->conditions, $fnum);

							if ($pass)
							{
								$actions_status = [];

								foreach ($custom_action->actions as $action)
								{
									if (!empty($action->action_conditions)) {
										$pass = $this->checkEventConditions($action->action_conditions, $fnum);

										if (!$pass)
										{
											continue;
										}
									}

									$actions_status[] = $this->launchEventAction($action, $fnum);
								}

								$status = !empty($actions_status) && !in_array(false, $actions_status);
							}
						}
					}
				}
			}
		}

		return $status;
	}

	private function retrieveFnumsFromEventData($event, $data): array
	{
		$fnums = [];

		if (!empty($data['fnum']))
		{
			$fnums = [$data['fnum']];
		}
		else
		{
			if (!empty($data['fnums']))
			{
				$fnums = $data['fnums'];
			}
			else
			{
				$event_category = $this->getEventCategory($event->event);

				if (in_array($event_category, $this->form_categories))
				{
					foreach ($data as $key => $value)
					{
						if (str_ends_with($key, '___fnum'))
						{
							$fnums[] = $value;
						}
					}
				}
			}
		}

		return $fnums;
	}

	private function checkEventConditions($conditions, $fnum): bool
	{
		$pass = false;

		if (!empty($conditions))
		{
			$conditions_status = [];
			$db                = Factory::getContainer()->get('DatabaseDriver');
			$query             = $db->createQuery();

			foreach ($conditions as $condition)
			{
				if (!empty($condition->targeted_column) && isset($condition->targeted_value))
				{
					if (empty($condition->operator)) {
						$condition->operator = '=';
					}

					list($table, $column) = explode('.', $condition->targeted_column);

					if ($condition->targeted_value === '{current_user_id}') {
						$condition->targeted_value = Factory::getApplication()->getIdentity()->id;
					}

					require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
					$h_files = new EmundusHelperFiles();
					$table_name = str_replace('#_', 'jos', $table);
					$linked = $h_files->isTableLinkedToCampaignCandidature($table_name);

					if ($linked)
					{
						$query->clear()
							->select('id')
							->from($db->quoteName($table))
							->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
							->andWhere($db->quoteName($column) . ' ' . $condition->operator  . ' ' . $db->quote($condition->targeted_value));
					}
					else
					{
						if (in_array($table_name, ['jos_emundus_setup_campaigns', 'jos_emundus_setup_programmes', 'jos_emundus_users']))
						{
							$query->clear();

							$table_alias = 'ecc';
							switch ($table_name)
							{
								case 'jos_emundus_setup_campaigns':
									$query->leftJoin($db->quoteName('jos_emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.id') . ' = ' . $db->quoteName('ecc.campaign_id'));
									$table_alias = 'esc';
									break;
								case 'jos_emundus_setup_programmes':
									$query->leftJoin($db->quoteName('jos_emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.id') . ' = ' . $db->quoteName('ecc.campaign_id'))
										->leftJoin($db->quoteName('jos_emundus_setup_programmes', 'esp') . ' ON ' . $db->quoteName('esp.code') . ' = ' . $db->quoteName('esc.training'));
									$table_alias = 'esp';
									break;
								case 'jos_emundus_users':
									$query->leftJoin($db->quoteName('jos_emundus_users', 'eu') . ' ON ' . $db->quoteName('eu.id') . ' = ' . $db->quoteName('ecc.user_id'));
									$table_alias = 'eu';
									break;
							}

							$query->select($db->quoteName('ecc.id'))
								->from($db->quoteName('jos_emundus_campaign_candidature', 'ecc'))
								->where($db->quoteName('ecc.fnum') . ' LIKE ' . $db->quote($fnum))
								->andWhere($db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value));

						} else {
							$conditions_status[] = false;
							break;
						}
					}

					try
					{
						$db->setQuery($query);
						$row_id = $db->loadResult();

						if (!empty($row_id))
						{
							$conditions_status[] = true;
						}
						else
						{
							$conditions_status[] = false;
							break;
						}
					}
					catch (Exception $e)
					{
						Log::add('Failed to get value for condition ' . $condition->targeted_column . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.custom_event_handler');
						$conditions_status[] = false;
						break;
					}
				}
				else
				{
					$conditions_status[] = false;
					break;
				}
			}

			$pass = !empty($conditions_status) && !in_array(false, $conditions_status);
		}

		return $pass;
	}

	private function launchEventAction($action, $fnum): bool
	{
		$landed = false;

		if (!empty($action) && !empty($fnum))
		{
			$db	= Factory::getContainer()->get('DatabaseDriver');
			$query	= $db->createQuery();

			switch ($action->action_type)
			{
				case 'update_file_status':
					if (isset($action->new_file_status))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
						$m_files = new EmundusModelFiles();
						$res     = $m_files->updateState([$fnum], $action->new_file_status, $this->automated_task_user);

						if ($res && $res['status'])
						{
							$landed = true;
						}
					}

					break;
				case 'update_file_tags':
					if (!empty($action->file_tags))
					{
						if ($action->file_tags_action === 'add')
						{
							require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
							$m_files = new EmundusModelFiles();
							$landed  = $m_files->tagFile([$fnum], [$action->file_tags], $this->automated_task_user);
						}
						else
						{
							require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
							$m_application = new EmundusModelApplication();
							$landed        = $m_application->deleteTag($action->file_tags, $fnum, $this->automated_task_user);
						}
					}
					break;
				case 'send_email':
					if (!empty($action->email_to_send)) {
						require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
						$m_emails = new EmundusModelEmails();

						$sent_states = [];
						if ($action->send_to_applicant) {
							$sent_states[] = $m_emails->sendEmail($fnum, $action->email_to_send, null, [], false, $this->automated_task_user);
						}

						if ($action->send_to_triggering_user) {
							$current_user_id = Factory::getApplication()->getIdentity()->id;

							if (!empty($current_user_id)) {
								$query->clear()
									->select('email')
									->from('#__users')
									->where('id = ' . $db->quote($current_user_id));

								$db->setQuery($query);
								$user_email = $db->loadResult();

								$sent_states[] = $m_emails->sendEmailNoFnum($user_email, $action->email_to_send, [], null, [], null, true, [], $this->automated_task_user);
							} else {
								$sent_states[] = false;
							}
						}

						if (!empty($action->send_to_users_with_groups)) {
							$user_ids = EmundusHelperAccess::getUsersFromGroupsThatCanAccessToFile($action->send_to_users_with_groups, $fnum);

							$query->clear()
								->select('email')
								->from('#__users')
								->where('id IN (' . implode(',', $db->quote($user_ids)) . ')');

							$db->setQuery($query);
							$users_emails = $db->loadColumn();

							if (!empty($users_emails)) {
								foreach ($users_emails as $user_email) {
									$sent_states[] = $m_emails->sendEmailNoFnum($user_email, $action->email_to_send, ['fnum' => $fnum], null, [], $fnum, true, [], $this->automated_task_user);
								}
							} else {
								$sent_states[] = false;
							}
						}

						$landed = !empty($sent_states) && !in_array(false, $sent_states);
					}
					break;

				case 'redirect':
					if (!empty($action->redirect_url)) {
						$redirect_url = $action->redirect_url;
						$redirect_url = str_replace('{fnum}', $fnum, $redirect_url);

						$app = Factory::getApplication();

						if (!empty($action->redirect_message)) {
							$type = $action->redirect_message_type ?? 'message';
							$app->enqueueMessage($action->redirect_message, $type);
						}

						$app->redirect($redirect_url);

						$landed = true;
					}

					break;
				case 'generate_letter':
					if (!empty($action->letter_template)) {
						require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
						$m_evaluation = new EmundusModelEvaluation();
						$res = $m_evaluation->generateLetters($fnum, [$action->letter_template], 1, 0, 0);

						if ($res && $res->status) {
							$landed = true;
						}
					}
					break;
				default:
					// do nothing
					break;
			}
		}

		return $landed;
	}
}
