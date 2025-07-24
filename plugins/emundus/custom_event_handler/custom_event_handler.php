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
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Traits\TraitDispatcher;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Repositories\Payment\DiscountRepository;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\PaymentStepEntity;

require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');

class plgEmundusCustom_event_handler extends CMSPlugin
{
	use TraitDispatcher;

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

	private EmundusHelperCache $h_cache;

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.custom_event_handler.php'), Log::ALL, array('com_emundus.custom_event_handler'));

		require_once(JPATH_SITE . '/components/com_emundus/helpers/events.php');
		$this->hEvents = new EmundusHelperEvents();

		$emundus_config            = ComponentHelper::getParams('com_emundus');
		$this->automated_task_user = $emundus_config->get('automated_task_user', 1);

		$this->h_cache = new EmundusHelperCache();
	}


	function onCallEventHandler(string $event, array $args = null): array
	{
		try
		{
			$events       = [];
			$events_types = [];
			$event_config = [];
			$codes        = [];
			$params       = json_decode($this->params);

			if (!empty($params) && !empty($params->event_handlers))
			{
				foreach ($params->event_handlers as $event_handler)
				{
					if ($event_handler->event == $event && $event_handler->published)
					{
						$events[]       = $event_handler->event;
						$codes[]        = $event_handler->code;
						$event_config[] = $event_handler;
					}
				}
			}

			$returned_values = [];

			foreach ($events as $index => $caller_index)
			{
				try
				{
					if (!empty($event_config[$index]->type) && $event_config[$index]->type == 'options')
					{
						$event_category = $this->getEventCategory($event_config[$index]->event);

						if (in_array($event_category, $this->form_categories))
						{
							if (!empty($args['formModel']->formDataWithTableName))
							{
								$data = $args['formModel']->formDataWithTableName;
							}
							else
							{
								$data = $args['formModel']->getData();
							}
						}
						else
						{
							$data = $args;
						}

						$returned_values[$caller_index] = $this->runEventSimpleAction($event_config[$index], $data);
					}
					else
					{
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

		$app = Factory::getApplication();
		if (method_exists($app, 'isClient') && !($app->isClient('cli')))
		{
			if (class_exists('FabrikWorker'))
			{
				$w    = new FabrikWorker;
				$code = $w->parseMessageForPlaceHolder($code, $data);
			}
			else
			{
				$code = $this->parseMessageForPlaceHolder($code, $data);
			}
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
			if ($this->h_cache->isEnabled())
			{
				$categories = $this->h_cache->get('ceh_categories');

				if (!empty($categories) && isset($categories[$event_label]))
				{
					return $categories[$event_label];
				}

				if (!empty($categories))
				{
					$categories = [];
				}
			}

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('category')
				->from('#__emundus_plugin_events')
				->where('label = ' . $db->quote($event_label));

			try
			{
				$db->setQuery($query);
				$category = $db->loadResult();

				if ($this->h_cache->isEnabled())
				{
					$categories[$event_label] = $category;
					$this->h_cache->set('ceh_categories', $categories);
				}
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
			Log::add('Running custom event handler for event ' . $event->event, Log::DEBUG, 'com_emundus.custom_event_handler');
			$fnums = $this->retrieveFnumsFromEventData($event, $data);

			if (!empty($fnums))
			{
				foreach ($fnums as $fnum)
				{
					foreach ($event->custom_actions as $custom_action)
					{
						$event_category = $this->getEventCategory($event->event);
						if (in_array($event_category, $this->form_categories))
						{
							if (empty($event->form_ids))
							{
								Log::add('No form_ids found for event ' . $event->event . '. This is a necessary parameter to launch actions on forms.', Log::WARNING, 'com_emundus.custom_event_handler');
								continue;
							}
							else
							{
								if (!in_array($data['formid'], $event->form_ids))
								{
									continue;
								}
							}
						}

						if (!empty($custom_action->conditions))
						{
							$pass = $this->checkEventConditions($custom_action->conditions, $fnum, 0, $data);

							if ($pass)
							{
								$actions_status = [];

								foreach ($custom_action->actions as $action)
								{
									if (!empty($action->action_conditions))
									{
										$pass = $this->checkEventConditions($action->action_conditions, $fnum, 0, $data);

										if (!$pass)
										{
											Log::add('Conditions not met for custom action on event ' . $event->event . ' with fnum ' . $fnum, Log::DEBUG, 'com_emundus.custom_event_handler');
											continue;
										}
									}

									$actions_status[] = $this->launchEventAction($action, $fnum, $data);
								}

								$status = !empty($actions_status) && !in_array(false, $actions_status);
							}
							else
							{
								Log::add('Conditions not met for custom action on event ' . $event->event . ' with fnum ' . $fnum, Log::DEBUG, 'com_emundus.custom_event_handler');
							}
						}
					}
				}
			}
			else
			{
				$user_id = Factory::getApplication()->getIdentity()->id;

				if (!empty($user_id) && $user_id !== $this->automated_task_user)
				{
					foreach ($event->custom_actions as $custom_action)
					{
						$event_category = $this->getEventCategory($event->event);
						if (in_array($event_category, $this->form_categories))
						{
							if (empty($event->form_ids))
							{
								Log::add('No form_ids found for event ' . $event->event . '. This is a necessary parameter to launch actions on forms.', Log::WARNING, 'com_emundus.custom_event_handler');
								continue;
							}
							else
							{
								if (!in_array($data['formid'], $event->form_ids))
								{
									continue;
								}
							}
						}

						if (!empty($custom_action->conditions))
						{
							$pass = $this->checkEventConditions($custom_action->conditions, '', $user_id, $data);

							if ($pass)
							{
								$actions_status = [];

								foreach ($custom_action->actions as $action)
								{
									if (!empty($action->action_conditions))
									{
										$pass = $this->checkEventConditions($action->action_conditions, '', $user_id, $data);

										if (!$pass)
										{
											continue;
										}
									}

									$actions_status[] = $this->launchEventAction($action, '', $data);
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

	private function checkEventConditions(object $conditions, string $fnum, int $user_id = 0, ?array $data = []): bool
	{
		$pass = false;

		if (!empty($conditions))
		{
			$conditions_status = [];
			$db                = Factory::getContainer()->get('DatabaseDriver');
			$query             = $db->createQuery();

			if (!empty($fnum) && empty($user_id))
			{
				$query->clear()
					->select('applicant_id')
					->from($db->quoteName('jos_emundus_campaign_candidature'))
					->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));

				try
				{
					$db->setQuery($query);
					$user_id = $db->loadResult();
				}
				catch (Exception $e)
				{
					Log::add('Failed to get applicant_id for fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.custom_event_handler');
				}
			}

			foreach ($conditions as $condition)
			{
				if (!empty($condition->targeted_column) && isset($condition->targeted_value))
				{
					if (empty($condition->operator))
					{
						$condition->operator = '=';
					}
					if (empty($condition->used_object))
					{
						$condition->used_object = 'fnum';
					}

					// if $condition->targeted_column match "[NAME]", then it is a tag to interpret
					// else if $condition->targeted_column match "name.name", then it is table.column
					// else it can be an alias
					$pattern_tag   = '/\[(.*?)\]/';
					$pattern_table = '/\w+\.\w+/';
					// $data.<bar>.<foo> etc... means that it is using current event data, there can be multiple dots ( min 1 )
					$pattern_event_data = '/\.([a-zA-Z0-9_][a-zA-Z0-9_]*)/';

					if (str_starts_with($condition->targeted_column, '$data') && preg_match_all($pattern_event_data, $condition->targeted_column, $matches))
					{
						if (!empty($matches[1]))
						{
							$entries = $matches[1];

							$value = $data;
							foreach ($entries as $entry)
							{
								if (isset($value[$entry]))
								{
									$value = $value[$entry];
								}
								else
								{
									$value = null;
									break;
								}
							}

							$conditions_status[] = $this->operateCondition($condition, $value);
						}
						else
						{
							$conditions_status[] = false;
						}
					}
					else
					{
						if ($condition->used_object === 'fnum')
						{
							if (preg_match($pattern_tag, $condition->targeted_column, $matches))
							{
								require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
								$m_emails = new EmundusModelEmails();
								$tags     = $m_emails->setTags($this->automated_task_user, ['FNUM' => $fnum], $fnum, '', $condition->targeted_column);

								if (!empty($tags['replacements']))
								{
									$value               = preg_replace($tags['patterns'], $tags['replacements'], $condition->targeted_column);
									$conditions_status[] = $this->operateCondition($condition, $value);
								}
								else
								{
									$conditions_status[] = false;
								}

							}
							else
							{
								if (preg_match($pattern_table, $condition->targeted_column, $matches))
								{
									list($table, $column) = explode('.', $condition->targeted_column);
									$table_name = str_replace('#_', 'jos', $table);
									if ($condition->targeted_value === '{current_user_id}')
									{
										$condition->targeted_value = Factory::getApplication()->getIdentity()->id;
									}

									$query->clear()
										->select('jfe.plugin')
										->from($db->quoteName('#__fabrik_elements', 'jfe'))
										->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $db->quoteName('jffg.group_id') . ' = ' . $db->quoteName('jfe.group_id'))
										->leftJoin($db->quoteName('#__fabrik_lists', 'jfl') . ' ON ' . $db->quoteName('jfl.form_id') . ' = ' . $db->quoteName('jffg.form_id'))
										->where($db->quoteName('jfl.db_table_name') . ' = ' . $db->quote($table_name))
										->andWhere($db->quoteName('jfe.name') . ' = ' . $db->quote($column));

									$db->setQuery($query);
									$plugin = $db->loadResult();

									require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
									$h_files    = new EmundusHelperFiles();
									$linked     = false;
									if (!empty($table_name))
									{
										$linked = $h_files->isTableLinkedToCampaignCandidature($table_name);
									}

									// currency plugin is a special case, as it needs to extract the numeric value from the currency string
									if ($plugin === 'currency')
									{
										if ($linked) {
											$query->clear()
												->select($column)
												->from($db->quoteName($table_name))
												->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));

											$db->setQuery($query);
											$value = $db->loadResult();
											$value = EmundusHelperFabrik::extractNumericValue($value);
											$conditions_status[] = $this->operateCondition($condition, $value);
										}
									} else {
										if ($linked)
										{
											$query->clear()
												->select('id')
												->from($db->quoteName($table))
												->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));

											switch ($condition->operator)
											{
												case 'IN':
												case 'NOT IN':
													$values = explode('|', $condition->targeted_value);

													$query->andWhere($db->quoteName($column) . ' ' . $condition->operator . ' (' . implode(',', $db->quote($values)) . ')');
													break;
												case '=':
													$query->andWhere($db->quoteName($column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value));
													break;
												case '!=':
													$query->andWhere('(' . $db->quoteName($column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value) . ' OR ' . $db->quoteName($column) . ' IS NULL )');
													break;
												case 'inferior':
													if (is_numeric($condition->targeted_value))
													{
														$query->andWhere($db->quoteName($column) . ' < ' . (int) $condition->targeted_value);
													}
													else
													{
														$query->andWhere($db->quoteName($column) . ' < ' . $db->quote($condition->targeted_value));
													}
													break;
												case 'inferior_equal':
													if (is_numeric($condition->targeted_value))
													{
														$query->andWhere($db->quoteName($column) . ' <= ' . (int) $condition->targeted_value);
													}
													else
													{
														$query->andWhere($db->quoteName($column) . ' <= ' . $db->quote($condition->targeted_value));
													}
													break;
												case 'superior':
													if (is_numeric($condition->targeted_value))
													{
														$query->andWhere($db->quoteName($column) . ' > ' . (int) $condition->targeted_value);
													}
													else
													{
														$query->andWhere($db->quoteName($column) . ' > ' . $db->quote($condition->targeted_value));
													}
													break;
												case 'superior_equal':
													if (is_numeric($condition->targeted_value))
													{
														$query->andWhere($db->quoteName($column) . ' >= ' . (int) $condition->targeted_value);
													}
													else
													{
														$query->andWhere($db->quoteName($column) . ' >= ' . $db->quote($condition->targeted_value));
													}
													break;
												default:
													$conditions_status[] = false;
													break;
											}

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
													->where($db->quoteName('ecc.fnum') . ' LIKE ' . $db->quote($fnum));

												switch ($condition->operator)
												{
													case 'IN':
													case 'NOT IN':
														$values = explode('|', $condition->targeted_value);
														$query->andWhere($db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' (' . implode(',', $db->quote($values)) . ')');
														break;
													case '=':
														$query->andWhere($db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value));
														break;
													case '!=':
														$query->andWhere('(' . $db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value) . ' OR ' . $db->quoteName($table_alias . '.' . $column) . ' IS NULL )');
														break;
													default:
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
								}
								else
								{
									$value = EmundusHelperFabrik::getValueByAlias($condition->targeted_column, $fnum);

									if (isset($value['raw']))
									{
										$conditions_status[] = $this->operateCondition($condition, $value['raw']);
									}
									else
									{
										$conditions_status[] = false;
										break;
									}
								}
							}
						}
						else
						{
							if ($condition->used_object === 'user_id')
							{
								if (preg_match($pattern_table, $condition->targeted_column, $matches))
								{
									list($table, $column) = explode('.', $condition->targeted_column);
									$table = str_replace('#_', 'jos', $table);

									if (in_array($table, ['jos_emundus_users', 'jos_users', 'jos_emundus_users_profiles']))
									{
										// if table.column is equal to jos_emundus_users_profiles.profile_id or jos_emundus_users.profile, query will be specific
										$query->clear()
											->select('u.id')
											->from('#__users AS u')
											->where('1=1');

										$table_alias = 'u';

										if ($table . '.' . $column === 'jos_emundus_users_profiles.profile_id' || $table . '.' . $column === 'jos_emundus_users.profile')
										{
											$query->leftJoin('#__emundus_users_profiles AS eup ON eup.user_id = u.id')
												->leftJoin('#__emundus_users AS eu ON eu.user_id = u.id');

											switch ($condition->operator)
											{
												case '=':
												case 'IN':
													$values = explode('|', $condition->targeted_value);
													$query->andWhere('eup.profile_id IN (' . implode(',', $db->quote($values)) . ') OR eu.profile IN (' . implode(',', $db->quote($values)) . ')');
													break;
												case '!=':
												case 'NOT IN':
													$values = explode('|', $condition->targeted_value);
													$query->andWhere('eup.profile_id NOT IN (' . implode(',', $db->quote($values)) . ') AND eu.profile NOT IN (' . implode(',', $db->quote($values)) . ')');
													break;
											}
										}
										else
										{
											switch ($table)
											{
												case 'jos_emundus_users':
													$query->leftJoin('#__emundus_users AS eu ON eu.user_id = u.id');
													$table_alias = 'eu';
													break;
												case 'jos_emundus_users_profiles':
													$query->leftJoin('#__emundus_users_profiles AS eup ON eup.user_id = u.id');
													$table_alias = 'eup';
													break;
											}

											switch ($condition->operator)
											{
												case 'IN':
												case 'NOT IN':
													$values = explode('|', $condition->targeted_value);

													$query->andWhere($db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' (' . implode(',', $db->quote($values)) . ')');
													break;
												case '=':
													$query->andWhere($db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value));
													break;
												case '!=':
													$query->andWhere('(' . $db->quoteName($table_alias . '.' . $column) . ' ' . $condition->operator . ' ' . $db->quote($condition->targeted_value) . ' OR ' . $db->quoteName($table_alias . '.' . $column) . ' IS NULL )');
													break;
												default:
													$conditions_status[] = false;
													break;
											}
										}

										$query->andWhere($db->quoteName('u.id') . ' = ' . $db->quote($user_id));

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
								}
							}
						}
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

	private function operateCondition($condition, $value): bool
	{
		$result = false;

		if (!empty($condition) && isset($value))
		{
			$result = match ($condition->operator)
			{
				'=' => $value == $condition->targeted_value,
				'!=' => $value != $condition->targeted_value,
				'IN' => in_array($value, explode('|', $condition->targeted_value)),
				'NOT IN' => !in_array($value, explode('|', $condition->targeted_value)),
				'inferior' => $value < $condition->targeted_value,
				'inferior_equal' => $value <= $condition->targeted_value,
				'superior' => $value > $condition->targeted_value,
				'superior_equal' => $value >= $condition->targeted_value,
				'array_includes' => is_array($value) && in_array($condition->targeted_value, $value),
				default => false,
			};
		}

		return $result;
	}

	private function launchEventAction($action, string $fnum, $data = null): bool
	{
		$landed = false;

		if (!empty($action))
		{
			$actions_that_needs_fnum = ['update_file_status', 'update_file_tags', 'generate_letter', 'sign_flow', 'alter_cart'];

			if (in_array($action->action_type, $actions_that_needs_fnum) && empty($fnum))
			{
				return false;
			}

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			Log::add('Before launching action ' . $action->action_type . ' for fnum ' . $fnum, Log::DEBUG, 'com_emundus.custom_event_handler');

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
					if (!empty($action->email_to_send))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
						$m_emails = new EmundusModelEmails();

						$sent_states = [];
						if ($action->send_to_applicant && !empty($fnum))
						{
							$sent_states[] = $m_emails->sendEmail($fnum, $action->email_to_send, null, [], false, $this->automated_task_user);
						}

						if ($action->send_to_triggering_user)
						{
							$current_user_id = Factory::getApplication()->getIdentity()->id;

							if (!empty($current_user_id))
							{
								$query->clear()
									->select('email')
									->from('#__users')
									->where('id = ' . $db->quote($current_user_id));

								$db->setQuery($query);
								$user_email = $db->loadResult();

								$sent_states[] = $m_emails->sendEmailNoFnum($user_email, $action->email_to_send, [], null, [], null, true, [], $this->automated_task_user);
							}
							else
							{
								$sent_states[] = false;
							}
						}

						if (!empty($action->send_to_users_with_groups))
						{
							$users_emails = [];
							$user_ids     = EmundusHelperAccess::getUsersFromGroupsThatCanAccessToFile($action->send_to_users_with_groups, $fnum);

							if (!empty($user_ids))
							{
								$query->clear()
									->select('email')
									->from('#__users')
									->where('id IN (' . implode(',', $db->quote($user_ids)) . ')');

								$db->setQuery($query);
								$users_emails = $db->loadColumn();
							}

							if (!empty($users_emails))
							{
								foreach ($users_emails as $user_email)
								{
									$sent_states[] = $m_emails->sendEmailNoFnum($user_email, $action->email_to_send, ['fnum' => $fnum], null, [], $fnum, true, [], $this->automated_task_user);
								}
							}
							else
							{
								$sent_states[] = false;
							}
						}

						$landed = !empty($sent_states) && !in_array(false, $sent_states);
					}
					break;

				case 'redirect':
					if (!empty($action->redirect_url))
					{
						$redirect_url = $action->redirect_url;
						$redirect_url = !empty($fnum) ? str_replace('{fnum}', $fnum, $redirect_url) : $redirect_url;

						try
						{
							$app = Factory::getApplication();

							// if we are already on the redirect url, then do not redirect
							$current_uri   = Uri::getInstance();
							$current_path  = $current_uri->getPath();
							$redirect_uri  = Uri::getInstance($redirect_url);
							$redirect_path = $redirect_uri->getPath();

							if ($current_path === $redirect_path)
							{
								return true;
							}

							/*
							 * 0 = all
							 * 1 = only selected pages
							 * 2 = exclude selected pages
							 */
							if ($action->redirect_only_on_pages_rule != 0)
							{
								// get current menu, get the list of selected menus and check if rule is met
								$menu        = $app->getMenu();
								$active_menu = $menu->getActive();

								$selected_pages = $action->redirect_only_on_pages;

								switch ($action->redirect_only_on_pages_rule)
								{
									case 1:
										if (!in_array($active_menu->id, $selected_pages))
										{
											return false;
										}
										break;
									case 2:
										if (in_array($active_menu->id, $selected_pages))
										{
											return false;
										}
										break;
								}
							}

							if (!empty($action->redirect_message))
							{
								$type = $action->redirect_message_type ?? 'message';
								$app->enqueueMessage($action->redirect_message, $type);
							}

							$app->redirect($redirect_url);

							$landed = true;
						}
						catch (Exception $e)
						{
							Log::add('Failed to redirect to ' . $redirect_url . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.custom_event_handler');
						}
					}

					break;
				case 'generate_letter':
					if (!empty($action->letter_template))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
						$m_evaluation = new EmundusModelEvaluation();
						$res          = $m_evaluation->generateLetters($fnum, [$action->letter_template], 1, 0, 0, false, $this->automated_task_user);

						if ($res && $res->status)
						{
							$landed = true;

							$this->dispatchJoomlaEvent('onAfterGenerateLetters', [
								'letters' => $res
							]);
						}
						else
						{
							Log::add('Failed to generate letter for fnum ' . $fnum . ' : ' . json_encode($res), Log::ERROR, 'com_emundus.custom_event_handler');
						}
					}
					break;
				case 'letter_choice':
					if(!empty($action->letter_id))
					{
						if(!empty($data['letters']))
						{
							$data['letters'] = array_filter($data['letters'], function ($letter) use ($action) {
								return $action->letter_id == $letter->id;
							});
						}
					}
				case 'sign_flow':
					if (!empty($action->attachment_type))
					{
						try
						{
							$fabrik_aliases = EmundusHelperFabrik::getAllFabrikAliases();
							$user           = Factory::getApplication()->getIdentity();
							if ($user->guest)
							{
								$user_id = $this->automated_task_user;
							}
							else
							{
								$user_id = $user->id;
							}

							if (empty($user_id))
							{
								return false;
							}

							if (!class_exists('EmundusModelSign'))
							{
								require_once(JPATH_ROOT . '/components/com_emundus/models/sign.php');
							}
							if (!class_exists('EmundusHelperFiles'))
							{
								require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
							}
							if (!class_exists('EmundusHelperFabrik'))
							{
								require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
							}
							if (!class_exists('EmundusModelFiles'))
							{
								require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
							}
							$m_sign  = new EmundusModelSign([], null, Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id));
							$m_files = new EmundusModelFiles();

							$ccid = EmundusHelperFiles::getIdFromFnum($fnum);

							$signers         = [];
							$signers_actions = (array) $action->action_signers;
							foreach ($signers_actions as $signer)
							{
								$informations = [
									'email'     => $signer->signer_email,
									'firstname' => $signer->signer_firstname,
									'lastname'  => $signer->signer_lastname,
								];

								if ($signer->signer_type === 'element')
								{
									foreach ($informations as $key => $information)
									{
										if (is_int($information))
										{
											$fabrik_element = $m_files->getValueFabrikByIds($signer->signer_element);
											if (!empty($fabrik_element))
											{
												$raw_value          = $m_files->getFabrikValue([$fnum], $fabrik_element[0]['db_table_name'], $fabrik_element[0]['name']);
												$informations[$key] = EmundusHelperFabrik::formatElementValue($fabrik_element[0]['name'], $raw_value);
											}
										}
										elseif (in_array($information, $fabrik_aliases))
										{
											$informations[$key] = EmundusHelperFabrik::getValueByAlias($information, $fnum)['value'];
										}
									}
								}

								if ($signer->signer_type === 'applicant')
								{
									$query->clear()
										->select('u.email, eu.firstname, eu.lastname')
										->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
										->leftJoin($db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id = ecc.applicant_id')
										->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id = ecc.applicant_id')
										->where($db->quoteName('ecc.id') . ' = :ccid')
										->bind(':ccid', $ccid, ParameterType::INTEGER);
									$db->setQuery($query);
									$informations = $db->loadAssoc();
								}

								if (!empty($informations['email'] && !empty($informations['firstname']) && !empty($informations['lastname'])))
								{
									$contactRepository = new ContactRepository($db);
									$contact           = $contactRepository->getByEmail($informations['email']);
									if (empty($contact))
									{
										$contact = new ContactEntity($informations['email'], $informations['lastname'], $informations['firstname'], '');
										$contact->setId($contactRepository->flush($contact));
									}

									if (!empty($contact))
									{
										$signers[] = [
											'signer'               => $contact->getId(),
											'authentication_level' => $signer->signer_authentication_level ?? 0,
										];
									}
								}
							}

							if (!empty($signers))
							{
								if ($request_id = $m_sign->saveRequest(0, 'to_sign', $ccid, 0, $fnum, $action->attachment_type, $action->signer_connector, $signers))
								{
									$requestRepository = new RequestRepository($db);
									$requestEntity     = $requestRepository->loadRequestById($request_id);

									$this->dispatchJoomlaEvent('onAfterRequestSaved', [
										'request_id' => $request_id,
										'status'     => $requestEntity->getStatus()->value,
										'ccid'       => $requestEntity->getCcid(),
										'user_id'    => $requestEntity->getUserId(),
										'fnum'       => $requestEntity->getFnum(),
										'attachment' => $requestEntity->getAttachment()->getId(),
										'connector'  => $requestEntity->getConnector()->value,
										'signers'    => $signers
									]);
								}
							}
						}
						catch (Exception $e)
						{
							Log::add('Failed to create sign request for fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.custom_event_handler');
						}
					}
					break;
				case 'alter_cart':
					if (!empty($action->alter_cart_action))
					{
						$landed = $this->runCartAction($action, $fnum, $data);
					}
					break;

				case 'assoc':
					if (!empty($action->assoc_action))
					{
						$current_user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->automated_task_user);
						$actions = [['id' => 1, 'c' => 0, 'r' => 1, 'u' => 0, 'd' => 0]];

						if (!empty($action->assoc_action_groups) && !is_array($action->assoc_action_groups)) {
							$action->assoc_action_groups = [$action->assoc_action_groups];
						}
						if (!empty($action->assoc_action_users) && !is_array($action->assoc_action_users)) {
							$action->assoc_action_users = [$action->assoc_action_users];
						}

						switch($action->assoc_action) {
							case 'share_groups':
								if (!empty($action->assoc_action_groups)) {
									$m_files = new EmundusModelFiles();
									$landed = $m_files->shareGroups($action->assoc_action_groups, $actions, [$fnum]);
								}
								break;
							case 'unshare_groups':
								if (!empty($action->assoc_action_groups)) {
									$m_application = new EmundusModelApplication();

									foreach ($action->assoc_action_groups as $group_id)
									{
										$landed = $m_application->deleteGroupAccess($fnum, $group_id, $current_user);
									}
								}
								break;
							case 'share_users':
								if (!empty($action->assoc_action_users)) {
									$m_files = new EmundusModelFiles();
									$landed = $m_files->shareUsers($action->assoc_action_users, $actions, [$fnum], $current_user);
								}
								break;
							case 'unshare_users':
								if (!empty($action->assoc_action_users)) {
									$m_files = new EmundusModelFiles();
									$landed = $m_files->unshareUsers($action->assoc_action_users, [$fnum], $current_user);
								}
								break;
						}
					}
					break;
				default:
					// do nothing
					break;
			}

			if ($landed)
			{
				Log::add('Action ' . $action->action_type . ' for fnum ' . $fnum . ' has been successfully launched', Log::DEBUG, 'com_emundus.custom_event_handler');
			}
			else
			{
				Log::add('Action ' . $action->action_type . ' for fnum ' . $fnum . ' has failed', Log::ERROR, 'com_emundus.custom_event_handler');
			}
		}

		return $landed;
	}

	/**
	 * @param           $action
	 * @param   string  $fnum
	 *
	 * @return bool
	 */
	private function runCartAction($action, string $fnum, $data): bool
	{
		$ran = false;

		if ($action->alter_cart_action === 'alter_advance')
		{
			if (!empty($data['payment_step']) && $data['payment_step'] instanceof PaymentStepEntity)
			{
				$data['payment_step']->setAdvanceAmount($action->advance_amount);
			}
		}
		else
		{
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new EmundusModelWorkflow();
			$step       = $m_workflow->getPaymentStepFromFnum($fnum);

			if (!empty($step->id))
			{

				$cart_repository = new CartRepository();
				$cart            = $cart_repository->getCartByFnum($fnum, $step->id);
				if (!empty($cart))
				{
					$action->discount_id = (int) $action->discount_id;
					$action->product_id  = (int) $action->product_id;
					switch ($action->alter_cart_action)
					{
						case 'add_product':
							if (!empty($action->product_id))
							{
								// only add product if it is not already in the cart
								$already_in_cart = false;
								foreach ($cart->getProducts() as $product)
								{
									if ($product->getId() === $action->product_id)
									{
										$already_in_cart = true;
										break;
									}
								}

								if (!$already_in_cart)
								{
									$product_repository = new ProductRepository();
									$product            = $product_repository->getProductById($action->product_id);

									if (!empty($product->getId()))
									{
										$cart->addProduct($product);
										$ran = $cart_repository->saveCart($cart, $this->automated_task_user);
									}
								}
								else
								{
									$ran = true;
								}
							}
							break;
						case 'remove_product':
							if (!empty($action->product_id))
							{
								// only remove product if it is in the cart
								$still_in_cart = false;
								foreach ($cart->getProducts() as $product)
								{
									if ($product->getId() === $action->product_id)
									{
										$still_in_cart = true;
										break;
									}
								}

								if ($still_in_cart)
								{
									$product_repository = new ProductRepository();
									$product            = $product_repository->getProductById($action->product_id);

									if (!empty($product->getId()))
									{
										$cart->removeProduct($product);
										$ran = $cart_repository->saveCart($cart, $this->automated_task_user);
									}
								}
								else
								{
									$ran = true;
								}
							}
							break;
						case 'add_discount':
							if (!empty($action->discount_id))
							{
								$discount_repository = new DiscountRepository();
								$discount            = $discount_repository->getDiscountById($action->discount_id);

								if (!empty($discount))
								{
									// only add discount if it is not already in the cart
									$already_in_cart = false;
									foreach ($cart->getPriceAlterations() as $alteration)
									{
										if (!empty($alteration->getDiscount()) && $alteration->getDiscount()->getId() === $action->discount_id)
										{
											$already_in_cart = true;
											break;
										}
									}

									if (!$already_in_cart)
									{
										$alteration = new AlterationEntity(0, $cart->getId(), null, $discount, $discount->getDescription(), -$discount->getValue(), AlterationType::from($discount->getType()->value), $this->automated_task_user);
										$ran        = $cart_repository->addAlteration($cart, $alteration, $this->automated_task_user);
									}
									else
									{
										$ran = true;
									}
								}
							}
							break;
						case 'remove_discount':
							if (!empty($action->discount_id))
							{
								// only remove discount if it is in the cart
								$still_in_cart        = false;
								$alteration_to_remove = null;
								foreach ($cart->getPriceAlterations() as $alteration)
								{
									if (!empty($alteration->getDiscount()) && $alteration->getDiscount()->getId() === $action->discount_id)
									{
										$alteration_to_remove = $alteration;
										$still_in_cart        = true;
										break;
									}
								}
								if ($still_in_cart && !empty($alteration_to_remove))
								{
									$ran = $cart_repository->removeAlteration($cart, $alteration_to_remove, $this->automated_task_user);
								}
								else
								{
									$ran = true;
								}
							}
							break;
					}
				}
			}
		}

		return $ran;
	}
}
