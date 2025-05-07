<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Settings\AddonEntity;
use Tchooz\Entities\SMS\ReceiverEntity;
use Tchooz\Entities\SMS\SMSEntity;
use Tchooz\Exception\EmundusSMSException;
use Tchooz\Synchronizers\SMS\OvhSMS;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');


class EmundusModelSMS extends JModelList
{
	private $app;

	private $db;

	private int $action_id = 0;

	public bool $activated = false;

	private EmundusHelperCache $h_cache;

	private ?AddonEntity $smsAddon = null;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('AddonEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Settings/AddonEntity.php');
		}
		if (!class_exists('Tchooz\Exception\EmundusSMSException')) {
			require_once(JPATH_ROOT . '/components/com_emundus/Exception/EmundusSMSException.php');
		}

		$this->app = Factory::getApplication();
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->h_cache = new EmundusHelperCache();
		$this->setSmsActionId();
		$this->setSMSAddon();
		$this->activated = $this->smsAddon->enabled === 1 && $this->isSMSServiceActivated();

		Log::addLogger(['text_file' => 'com_emundus.sms.php'], Log::ALL, array('com_emundus.sms'));
	}

	/**
	 * Load the SMS addon
	 */
	private function setSMSAddon(): void
	{
		$cache_sms_addon =$this->h_cache->get('sms_addon');

		if (!empty($cache_sms_addon)) {
			$this->smsAddon = new AddonEntity(
				$cache_sms_addon->name,
				$cache_sms_addon->type,
				$cache_sms_addon->icon,
				$cache_sms_addon->description,
				$cache_sms_addon->configuration,
				$cache_sms_addon->enabled,
				$cache_sms_addon->displayed
			);
		}

		if (empty($this->smsAddon)) {
			$query = $this->db->createQuery();

			$query->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('sms'));
			try {
				$this->db->setQuery($query);
				$config = $this->db->loadResult();

				if (!empty($config)) {
					$config = json_decode($config, true);
					$this->smsAddon = new AddonEntity(
						'COM_EMUNDUS_ADDONS_SMS',
						'sms',
						'send_to_mobile',
						'COM_EMUNDUS_ADDONS_SMS_DESC',
						'',
						(bool)$config['enabled'],
						(bool)$config['displayed']
					);

					$configuration = [
						'encoding' => $config['params']['encoding'] ?? 'GSM-7',
					];

					$this->smsAddon->setConfiguration($configuration);

					$this->h_cache->set('sms_addon', $this->smsAddon);
				} else {
					$this->smsAddon = new AddonEntity(
						'COM_EMUNDUS_ADDONS_SMS',
						'sms',
						'send_to_mobile',
						'COM_EMUNDUS_ADDONS_SMS_DESC',
						'',
						false,
						false
					);
				}
			} catch (\Exception $e) {
				Log::add('Error on load sms addon : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}
	}

	public function getSMSAddon(): AddonEntity
	{
		return $this->smsAddon;
	}

	/**
	 * Set the sms action id
	 * @return void
	 */
	private function setSmsActionId(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from('#__emundus_setup_actions')
			->where('name like ' . $db->quote('sms'));

		try
		{
			$db->setQuery($query);
			$action_id = (int)$db->loadResult();

			if (!empty($action_id)) {
				$this->action_id = $action_id;
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get sms action id : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}
	}

	public function getSmsActionId(): int
	{
		return $this->action_id;
	}

	public function isSMSServiceActivated(): bool
	{
		$activated = $this->h_cache->get('sms_activated');

		if (is_null($activated)) {
			try {
				$query = $this->db->createQuery();
				$query->select('enabled')
					->from('#__emundus_setup_sync')
					->where('type = ' . $this->db->quote('ovh'));

				$activated = (bool)$this->db->setQuery($query)->loadResult();

				$this->h_cache->set('sms_activated', $activated);
			} catch (\Exception $e) {
				Log::add('Error on get sms activation : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		} else {
			$activated = (bool)$activated;
		}

		return $activated;
	}

	/**
	 * @param int $user_id
	 * @param string $label
	 * @param string $message
	 *
	 * @return int
	 */
	public function addTemplate(int $user_id, string $label = 'COM_EMUNDUS_NEW_SMS_LABEL', string $message = ''): int
	{
		$template_id = 0;

		if ($label == 'COM_EMUNDUS_NEW_SMS_LABEL') {
			$label = Text::_('COM_EMUNDUS_NEW_SMS_LABEL');
		}

		$query = $this->db->getQuery(true);
		$query->insert('#__emundus_setup_sms')
			->columns('label, message, created_by, created_date')
			->values($this->db->quote($label) . ', ' . $this->db->quote($message) . ', ' . $user_id . ', ' . $this->db->quote(date('Y-m-d H:i:s')));

		try {
			$this->db->setQuery($query);
			$inserted = $this->db->execute();

			if ($inserted) {
				$template_id = (int)$this->db->insertid();
			}
		} catch (\Exception $e) {
			Log::add('Error on add sms template : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $template_id;
	}

	/**
	 * @param   int  $id
	 *
	 * @return array
	 */
	public function getSMSTemplate(int $id): array
	{
		$template = [];

		if (!empty($id)) {
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from('#__emundus_setup_sms')
				->where('id = ' . $id);

			try {
				$this->db->setQuery($query);
				$template = $this->db->loadAssoc();
			} catch (\Exception $e) {
				Log::add('Error on get sms template : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}

			if (empty($template)) {
				$template = [];
			}
		}

		return $template;
	}

	public function getSMSTemplates(string $search = '', int $category = 0, string $order_by = '', string $order = 'ASC'): array
	{
		$templates = [];

		$query = $this->db->getQuery(true);

		$query->select('sms.id, sms.label, sms.message, sms.category_id, category.label as category')
			->from($this->db->quoteName('#__emundus_setup_sms', 'sms'))
			->leftJoin($this->db->quoteName('#__emundus_setup_category', 'category') . ' ON sms.category_id = category.id AND category.type = ' . $this->db->quote('sms'))
			->where('sms.published = 1');

		if (!empty($search)) {
			$query->where('sms.label LIKE ' . $this->db->quote('%' . $search . '%') . ' OR sms.message LIKE ' . $this->db->quote('%' . $search . '%'));
		}

		if (!empty($category)) {
			$query->where('sms.category_id = ' . $category);
		}

		if (!empty($order_by)) {
			$query->order($this->db->quoteName('sms.' . $order_by) . ' ' . $order);
		}

		try
		{
			$this->db->setQuery($query);
			$templates = $this->db->loadAssocList();

			foreach ($templates as $key => $template) {
				$templates[$key]['label'] = [
					'fr' => $template['label'],
					'en' => $template['label']
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get sms templates : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $templates;
	}

	/**
	 * @param   int     $template_id
	 * @param   string  $label
	 * @param   string  $message
	 * @param   int     $user_id
	 *
	 * @return bool
	 */
	public function updateTemplate(int $template_id, string $label, string $message, int $user_id, int $category_id = 0, array $tags = [], $allow_unicode = false): bool
	{
		$updated = false;

		if (!empty($template_id)) {
			$query = $this->db->getQuery(true);

			$sms_configuration = $this->smsAddon->getConfiguration();
			$encoding = $sms_configuration['encoding'] ?? 'GSM-7';

			if ($encoding === 'GSM-7' && !$allow_unicode) {
				if (!class_exists('SMSEncodingHelper')) {
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/SMSEncodingHelper.php');
				}
				$encodingHelper = new SMSEncodingHelper();

				if (!$encodingHelper->isGsm7bitCompatible($message)) {
					$compatibility = $encodingHelper->ensureSmsCompatibility($message);
					$replaced_message = $encodingHelper->replaceNonGsmChars($message);
					$invalid_chars = $encodingHelper->findInvalidGsmChars($message);

					throw EmundusSMSException::invalidEncoding($message, $invalid_chars, $compatibility, $replaced_message);
				}
			}

			$query->update('#__emundus_setup_sms')
				->set('label = ' . $this->db->quote($label))
				->set('message = ' . $this->db->quote($message))
				->set('modified_by = ' . $user_id)
				->set('modified_date = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set('category_id = ' . $category_id);

			if (!empty($tags['success_tag'])) {
				$query->set('success_tag = ' . $tags['success_tag']);
			}
			if (!empty($tags['failure_tag'])) {
				$query->set('failure_tag = ' . $tags['failure_tag']);
			}

			$query->where('id = ' . $this->db->quote($template_id));

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error on update sms template : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}

		return $updated;
	}

	/**
	 * @param   int  $template_id
	 * @param   int  $user_id
	 *
	 * @return bool
	 */
	public function deleteTemplate(int $template_id, int $user_id): bool
	{
		$deleted = false;

		if (!empty($template_id)) {
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_sms')
				->where('id = ' . $this->db->quote($template_id));

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error on delete sms template : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}

			if ($deleted) {
				Log::add('SMS template deleted by ' . $user_id . ' at ' . date('Y-m-d H:i:s'), Log::INFO, 'com_emundus.sms');
			}
		}

		return $deleted;
	}

	/**
	 * @param   array  $template_ids
	 * @param   int    $user_id
	 *
	 * @return bool
	 */
	public function deleteTemplates(array $template_ids, int $user_id): bool
	{
		$deleted = false;

		if (!empty($template_ids)) {
			$template_ids = array_map('intval', $template_ids);

			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_sms')
				->where('id IN (' . implode(',', $template_ids) . ')');

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error on delete sms template : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}

			if ($deleted) {
				Log::add('SMS templates ' . implode(',', $template_ids).  ' deleted by ' . $user_id . ' at ' . date('Y-m-d H:i:s'), Log::INFO, 'com_emundus.sms');
			}
		}

		return $deleted;
	}


	/**
	 * @param   array  $fnums
	 * @return  array $receivers
	 */
	public function createReceiversFromFnums(array $fnums): array
	{
		$receivers = [];

		if (!empty($fnums)) {
			$query = $this->db->getQuery(true);

			if (!class_exists('ReceiverEntity')) {
				require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/ReceiverEntity.php');
			}

			foreach ($fnums as $fnum) {
				$ccid = EmundusHelperFiles::getIdFromFnum($fnum);

				$query->clear()
					->select('eu.user_id, eu.tel')
					->from($this->db->quoteName('#__emundus_users', 'eu'))
					->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('ecc.applicant_id'))
					->where('ecc.fnum = ' . $this->db->quote($fnum));

				try
				{
					$this->db->setQuery($query);
					$user_infos = $this->db->loadAssoc();

					if (!empty($user_infos)) {
						$receivers[] = new ReceiverEntity($user_infos['tel'], $ccid, $user_infos['user_id']);
					} else {
						Log::add('No user found for fnum ' . $fnum, Log::ERROR, 'com_emundus.sms');
					}
				}
				catch (\Exception $e)
				{
					Log::add('Error on create receivers from fnums : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
				}
			}
		}

		return $receivers;
	}

	/**
	 * We store the sms to send in the queue, then a CRON Task will send them
	 * @param   string  $message
	 * @param   array   $receivers
	 * @param   int     $sender_id
	 *
	 * @return bool
	 */
	public function storeSmsToSend(string $message, array $receivers, int $sms_template_id, int $sender_id): bool
	{
		$stored = false;

		if (!empty($message) && !empty($receivers)) {
			$prepared_messages = $this->prepareSMS($message, $receivers, $sender_id);

			$inserts = [];
			foreach ($prepared_messages as $prepared_message) {
				$query = $this->db->getQuery(true);

				$query->insert('#__emundus_sms_queue')
					->columns('created_by, created_date, message, phone_number, fnum, user_id, status, template_id')
					->values($sender_id . ', ' . $this->db->quote(date('Y-m-d H:i:s')) . ', ' . $this->db->quote($prepared_message['message']) . ', ' . $this->db->quote($prepared_message['receiver']->phone_number) . ', ' . $this->db->quote($prepared_message['receiver']->getFnum()) . ', ' . $this->db->quote($prepared_message['receiver']->user_id) . ', ' . $this->db->quote('pending') . ', ' . $sms_template_id);

				try {
					$this->db->setQuery($query);
					$inserts[] = $this->db->execute();
				} catch (\Exception $e) {
					Log::add('Error on store sms to send : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
				}
			}
			$stored = !empty($inserts) && !in_array(false, $inserts);
		}

		return $stored;
	}

	public function prepareSMS(string $message, array $receivers, int $sender_id): array
	{
		$prepared = [];

		if (!class_exists('SMSEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/SMSEntity.php');
		}
		$sms = new SMSEntity($receivers, $message, $sender_id);

		if ($sms->doesMessageContainTags()) {
			foreach($sms->getReceivers() as $receiver) {
				$message = $sms->getMessage();
				$transformedMessage = $sms->transformMessage($message, $receiver);

				$prepared[] = [
					'receiver' => $receiver,
					'message' => $transformedMessage
				];
			}
		} else {
			foreach ($sms->getReceivers() as $receiver) {
				$prepared[] = [
					'receiver' => $receiver,
					'message' => $sms->getMessage()
				];
			}
		}

		return $prepared;
	}

	/**
	 * @param   string  $message
	 * @param   array   $receivers
	 * @param   int     $sender_id
	 *
	 * @return bool
	 */
	public function sendSMS(string $message, array $receivers, int $sender_id): bool
	{
		$sent = false;

		try {
			if (!empty($message) && !empty($receivers) && !empty($sender_id))
			{
				if (!class_exists('SMSEntity')) {
					require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/SMSEntity.php');
				}
				$sms          = new SMSEntity($receivers, $message, $sender_id);

				if (!class_exists('OvhSMS')) {
					require_once(JPATH_ROOT . '/components/com_emundus/classes/Synchronizers/SMS/OvhSMS.php');
				}
				$ovhSMS       = new OvhSMS();
				$sent         = $ovhSMS->sendSMS($sms);

				$phoneNumbers = $sms->getReceiversPhoneNumbers();
				if ($sent)
				{
					Log::add('SMS sent by ' . $sender_id . ' at ' . date('Y-m-d H:i:s') .  ' to ' . implode(', ', $phoneNumbers), Log::INFO, 'com_emundus.sms');
				}
				else
				{
					Log::add('Error on send sms : ' . $message . ' to ' . implode(', ', $phoneNumbers), Log::ERROR, 'com_emundus.sms');
				}
			}
		} catch (\Exception $e) {
			Log::add('Error on send sms : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $sent;
	}

	/**
	 * @param $page
	 * @param $limit
	 *
	 * @return array
	 */
	public function getGlobalSMSHistory(int $page = 0, int $limit = 20, string $search = '', array $status = []): array
	{
		if (empty($status)) {
			$status = ['sent', 'pending', 'failed'];
		}

		$history = [
			'count' => count($this->getStoredSMS('', $status, 0, 0, $search)),
			'datas' => []
		];

		$stored_sms = $this->getStoredSMS('', $status, $page, $limit, $search);

		if (!empty($stored_sms)) {
			$history['datas'] = $this->formatStoredSMS($stored_sms);
		}

		return $history;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array
	 */
	public function getSMSHistory(string $fnum): array
	{
		$history = [];

		if (!empty($fnum)) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
			$m_logs = new EmundusModelLogs();
			$history = $m_logs->getActionsOnFnum($fnum, null, $this->action_id, 'c');
			$history = array_filter($history, function($action) {
				return $action->message === 'COM_EMUNDUS_SEND_SMS_SUCCESS';
			});

			$stored_sms = $this->getStoredSMS($fnum, ['pending', 'failed']);
			$history = $this->formatStoredSMS($stored_sms, $history);
		}

		return $history;
	}

	private function formatStoredSMS(array $stored_sms, array $formatted_sms = []): array
	{
		array_map(function($sms) use (&$formatted_sms) {
			$sms->params = json_encode([
				'message' => $sms->message,
				'phone_number' => $sms->phone_number,
				'date' => $sms->created_date
			]);
			$sms->user_id_from = $sms->created_by;
			$sms->id = 'stored_' . $sms->id;

			$formatted_sms[] = $sms;
		}, $stored_sms);

		foreach ($formatted_sms as $sms)
		{
			$params = json_decode($sms->params, true);
			$sms->date_time = strtotime($params['date']);

			if (!empty($params['date']))
			{
				$params['date'] = EmundusHelperDate::displayDate($params['date'], 'DATE_FORMAT_LC2', 0);
			}

			$sms->params = $params;
			$sms->status = $sms->status ?? 'sent';
			$sms->message = strip_tags($sms->message, '<br><p><a>');
		}

		usort($formatted_sms, function($a, $b) {
			return $b->date_time - $a->date_time;
		});

		return $formatted_sms;
	}

	/**
	 * @param $fnum
	 * @param $status
	 *
	 * @return array
	 */
	public function getStoredSMS(string $fnum, array $status, int $page = 0, int $limit = 0, string $search = ''): array
	{
		$stored_sms = [];

		$query = $this->db->getQuery(true);

		$query->select('esq.*, eu.firstname, eu.lastname')
			->from($this->db->quoteName('#__emundus_sms_queue', 'esq'))
			->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON esq.created_by = eu.user_id')
			->where('1 = 1');

		if (!empty($fnum))
		{
			$query->andWhere('esq.fnum = ' . $this->db->quote($fnum));
		}

		if (!empty($status))
		{
			$query->andWhere('esq.status IN (' . implode(',', $this->db->quote($status)) . ')');
		}

		if (!empty($search)) {
			$query->andWhere('esq.message LIKE ' . $this->db->quote('%' . $search . '%') . ' OR esq.fnum LIKE ' . $this->db->quote('%' . $search . '%'));
		}

		$query->order('esq.updated_date DESC, esq.created_date DESC');

		try
		{
			if (!empty($limit)) {
				$offset = $page * $limit;

				$this->db->setQuery($query, $offset, $limit);
			} else {
				$this->db->setQuery($query);
			}

			$stored_sms = $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add('Error on get stored sms : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $stored_sms;
	}

	public function getSMSCategories(): array
	{
		$categories = [];

		$query = $this->db->getQuery(true);
		$query->select('id, label')
			->from('#__emundus_setup_category')
			->where('type = ' . $this->db->quote('sms'))
			->andWhere('published = 1');

		try
		{
			$this->db->setQuery($query);
			$categories = $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add('Error on get sms categories : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $categories;
	}

	/**
	 * @param   int     $id
	 * @param   string  $label
	 * @param   int     $user_id
	 *
	 * @return bool
	 */
	public function updateSMSCategory(int $id, string $label, int $user_id): bool
	{
		$updated = false;

		if (!empty($id) && !empty($label)) {
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_category'))
				->set('label = ' . $this->db->quote($label))
				->where('id = ' . $id);

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error on update sms category : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}

		return $updated;
	}

	/**
	 * @param   string  $label
	 * @param   int     $user_id
	 *
	 * @return int
	 */
	public function createSMSCategory(string $label, int $user_id): int
	{
		$category_id = 0;

		if (!empty($label)) {
			$query = $this->db->getQuery(true);
			$query->insert('#__emundus_setup_category')
				->columns('label, type')
				->values($this->db->quote($label) . ', ' . $this->db->quote('sms'));

			try {
				$this->db->setQuery($query);
				$inserted = $this->db->execute();

				if ($inserted) {
					$category_id = (int)$this->db->insertid();
				}
			} catch (\Exception $e) {
				Log::add('Error on add sms category : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}

		return $category_id;
	}

	/**
	 * @param   array  $message
	 * @return bool
	 */
	public function getPendingSMS(int $maximum_attempts = 3, $limit = 500): array
	{
		try {
			$query = $this->db->createQuery();
			$query->select('*')
				->from('#__emundus_sms_queue')
				->where('attempts < ' .$maximum_attempts)
				->andWhere('status = ' . $this->db->quote('pending'))
				->setLimit($limit)
				->order('created_date ASC');

			$sms = $this->db->setQuery($query)->loadAssocList();
		} catch (Exception $e) {
			Log::add('Error getting pending SMS: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			$sms = [];
		}

		return $sms;
	}

	/**
	 * @param        $synchronizer (OvhSMS for now, but can be another service in the future)
	 * @param   int  $maximum_attempts
	 * @param        $limit
	 *
	 * @return bool
	 */
	public function sendPendingSMS(OvhSMS $synchronizer, int $maximum_attempts = 3, int $limit = 500, bool $debug = false): bool
	{
		$sent = false;

		$messages = $this->getPendingSMS($maximum_attempts, $limit);

		if (!empty($messages)) {
			Log::add('Sending ' . count($messages) . ' pending SMS', Log::INFO, 'com_emundus.sms');
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');

			if (!class_exists('ReceiverEntity')) {
				require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/ReceiverEntity.php');
			}

			if (!class_exists('SMSEntity')) {
				require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/SMSEntity.php');
			}

			$sendings = [];
			foreach ($messages as $message) {
				$ccid = \EmundusHelperFiles::getIdFromFnum($message['fnum']);
				$receiver = new ReceiverEntity($message['phone_number'], $ccid, $message['user_id']);
				$smsEntity = new SMSEntity([$receiver], $message['message'], $message['created_by']);

				if ($debug) {
					$is_message_sent = true;
					Log::add('Sending SMS: ' . $message['id'] . ' to ' . $message['phone_number'] . ' - ' . $message['fnum'] . ' in debug mode.', Log::INFO, 'com_emundus.sms');
				} else {
					$is_message_sent = $synchronizer->sendSMS($smsEntity);
				}

				if ($is_message_sent) {
					$this->updateSMSStatus($message, 'sent', $maximum_attempts);
				} else {
					$this->updateSMSStatus($message, 'pending', $maximum_attempts);
				}

				if (!empty($message['template_id'])) {
					$this->addTagsAfterMessageSent($message['template_id'], $is_message_sent, $message['fnum']);
				}

				$sendings[] = $is_message_sent;
			}

			$sent = !in_array(false, $sendings);
		} else {
			$sent = true;
		}

		return $sent;
	}

	/**
	 * @param   array   $message
	 * @param   string  $status
	 * @param   int     $maximum_attempts
	 *
	 * @return bool
	 */
	private function updateSMSStatus(array $message, string $status = 'sent', int $maximum_attempts = 3): bool
	{
		$updated = false;

		$message['attempts'] = $message['attempts'] + 1;

		if ($status === 'pending' && $message['attempts'] >= $maximum_attempts) {
			$status = 'failed';
		}

		$query = $this->db->getQuery(true);

		$query->update('#__emundus_sms_queue')
			->set('status = ' . $this->db->quote($status))
			->set('attempts = ' . $message['attempts'])
			->set('updated_date = ' . $this->db->quote(date('Y-m-d H:i:s')))
			->where('id = ' . $message['id']);

		try {
			$this->db->setQuery($query)->execute();
			$updated = true;
		} catch (Exception $e) {
			Log::add('Error updating SMS status: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $updated;
	}

	/**
	 * @param   int     $template_id
	 * @param   bool    $is_message_sent
	 * @param   string  $fnum
	 *
	 * @return bool
	 */
	private function addTagsAfterMessageSent(int $template_id, bool $is_message_sent, string $fnum): bool
	{
		$tagged = false;

		$sms_template = $this->getSMSTemplate($template_id);
		if (!empty($sms_template) && !empty($fnum)) {
			$tags = [];

			if (!empty($sms_template['success_tag']) && $is_message_sent) {
				$tags[] = $sms_template['success_tag'];
			}

			if (!empty($sms_template['failure_tag']) && !$is_message_sent) {
				$tags[] = $sms_template['failure_tag'];
			}

			if (!empty($tags)) {
				$emundus_config = ComponentHelper::getParams('com_emundus');
				$user_id = $emundus_config->get('automated_task_user', 1);


				if (!class_exists('EmundusModelFiles')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
				}
				$m_files = new EmundusModelFiles();
				$tagged = $m_files->tagFile([$fnum], $tags, $user_id);

				if (!$tagged) {
					Log::add('Error on tag file ' . $fnum . ' with tags ' . implode(', ', $tags), Log::ERROR, 'com_emundus.sms');
				}
			}
		}

		return $tagged;
	}

	public function getRecipientsData(array $fnums): array
	{
		$data = [];

		if (!empty($fnums)) {
			$query = $this->db->createQuery();

			foreach ($fnums as $fnum) {
				$query->clear()
					->select('CONCAT(eu.firstname, " ", eu.lastname) as username, eu.tel')
					->from('#__emundus_users eu')
					->leftJoin('#__emundus_campaign_candidature ecc ON eu.user_id = ecc.applicant_id')
					->where('ecc.fnum = ' . $this->db->quote($fnum));

				try {
					$data[$fnum] = $this->db->setQuery($query)->loadAssoc();
				} catch (\Exception $e) {
					Log::add('Error on get recipients data : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
				}
			}
		}

		return $data;
	}

	public function getSMSTriggers(int $application_file_status, array $program_ids): array
	{
		$triggers = [];

		try {
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName('trigger') . '.*')
				->from($this->db->quoteName('#__emundus_setup_emails_trigger', 'trigger'))
				->leftJoin($this->db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id', 'trigger_program') . ' ON ' . $this->db->quoteName('trigger.id') . ' = ' . $this->db->quoteName('trigger_program.parent_id'))
				->where($this->db->quoteName('trigger.step') .' = ' . $this->db->quote($application_file_status))
				->andWhere('trigger_program.programme_id IN (' . implode(',', $this->db->quote($program_ids)) . ') OR ' . $this->db->quoteName('trigger.all_program') . ' = 1')
				->andWhere($this->db->quoteName('trigger.sms_id') . ' > 0');

			$this->db->setQuery($query);
			$triggers = $this->db->loadObjectList();
		} catch (Exception $e) {
			Log::add('Error on get sms triggers : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $triggers;
	}

	public function triggerSMS(array $fnums, int $application_file_status, array $program_codes, bool $triggered_by_applicant, int $user_id): bool
	{
		$triggered = false;

		if (!empty($fnums)) {
			$program_ids = $this->getProgramIdsFromCodes($program_codes);
			$triggers = $this->getSMSTriggers($application_file_status, $program_ids);

			if (!empty($triggers)) {
				foreach ($triggers as $trigger) {
					if ($this->isTriggerIntentedToApplicant($trigger, $triggered_by_applicant)) {
						$triggered_to_applicants = $this->triggerSMSToApplicants($trigger, $fnums, $user_id);
					} else {
						$triggered_to_applicants = true;
					}

					$triggered_to_others = $this->triggerSMSToOthers($trigger, $fnums, $user_id);
					$triggered = $triggered_to_applicants && $triggered_to_others;
				}
			} else {
				$triggered = true; // No triggers found, so we consider that the everything is fine
			}
		}

		return $triggered;
	}

	private function isTriggerIntentedToApplicant($trigger, $triggered_by_applicant): bool
	{
		$intended_to_applicant = false;

		if (!empty($trigger)) {
			// $sms_trigger->to_applicant means "On manager action, send to applicant"
			// $sms_trigger->to_current_user means "On applicant action, send to applicant"
			// if $triggered_by_applicant is false, it means that the action is done by a manager
			// if $triggered_by_applicant is true, it means that the action is done by the applicant

			if ($trigger->to_applicant && !$triggered_by_applicant) {
				$intended_to_applicant = true;
			} elseif ($trigger->to_current_user && $triggered_by_applicant) {
				$intended_to_applicant = true;
			} elseif ($trigger->to_applicant && $trigger->to_current_user) {
				$intended_to_applicant = true;
			}
		}

		return $intended_to_applicant;
	}

	private function triggerSMSToApplicants(object $trigger, array $fnums, int $user_id): bool
	{
		$triggered = false;

		if (!empty($trigger) && !empty($fnums)) {
			$receivers = $this->createReceiversFromFnums($fnums);
			$sms_template = $this->getSMSTemplate($trigger->sms_id);
			$triggered = $this->storeSmsToSend($sms_template['message'], $receivers, $trigger->sms_id, $user_id);
		}

		return $triggered;
	}

	private function triggerSMSToOthers(object $trigger, array $fnums, int $user_id): bool
	{
		$triggered = false;

		if (!empty($trigger) && !empty($fnums)) {
			$trigger_users = $this->getSMSTriggerUsers($trigger);

			if (!empty($trigger_users)) {
				if (!class_exists('EmundusHelperAccess')) {
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
				}

				foreach($fnums as $fnum) {
					$valid_users = [];
					foreach($trigger_users as $trigger_user_id) {
						if (EmundusHelperAccess::isUserAllowedToAccessFnum($trigger_user_id, $fnum)) {
							$valid_users[] = $trigger_user_id;
						}
					}

					$receivers = $this->createReceiversForUsers($valid_users, $fnum);
					$sms_template = $this->getSMSTemplate($trigger->sms_id);
					$triggered = $this->storeSmsToSend($sms_template['message'], $receivers, $trigger->sms_id, $user_id);
				}
			} else {
				$triggered = true; // No users found, so we consider that the everything is fine
			}
		}

		return $triggered;
	}

	/**
	 *
	 * The receivers are not the applicants, but other users, though they are linked to a fnum, to render the sms template content correctly
	 * @param   array   $user_ids
	 * @param   string  $fnum
	 *
	 * @return array
	 */
	private function createReceiversForUsers(array $user_ids, string $fnum): array
	{
		$receivers = [];

		foreach ($user_ids as $user_id) {
			$query = $this->db->getQuery(true);

			if (!class_exists('ReceiverEntity')) {
				require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/SMS/ReceiverEntity.php');
			}

			$ccid = EmundusHelperFiles::getIdFromFnum($fnum);

			$query->select('eu.user_id, eu.tel')
				->from($this->db->quoteName('#__emundus_users', 'eu'))
				->where('eu.user_id = ' . $this->db->quote($user_id));

			try
			{
				$this->db->setQuery($query);
				$user_infos = $this->db->loadAssoc();

				if (!empty($user_infos))
				{
					$receivers[] = new ReceiverEntity($user_infos['tel'], $ccid, $user_id);
				}
				else
				{
					Log::add('No user found for user_id ' . $user_id, Log::ERROR, 'com_emundus.sms');
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on create receivers from user ids : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}

		return $receivers;
	}

	private function getSMSTriggerUsers(object $trigger): array
	{
		$user_ids = [];

		if (!empty($trigger->id)) {
			try {
				$query = $this->db->createQuery();
				$query->select('user_id')
					->from('#__emundus_setup_emails_trigger_repeat_user_id')
					->where('parent_id = ' . $trigger->id);

				$this->db->setQuery($query);
				$user_ids = $this->db->loadColumn();

				$query->clear()
					->select('eu.id')
					->from($this->db->quoteName('#__emundus_users', 'eu'))
					->leftJoin($this->db->quoteName('#__emundus_groups', 'eg') .  ' ON eg.user_id = eu.user_id')
					->leftJoin($this->db->quoteName('#__emundus_setup_emails_trigger_repeat_group_id', 'et_groups') .  ' ON eg.group_id = et_groups.group_id')
					->where('et_groups.parent_id = ' . $trigger->id);

				$this->db->setQuery($query);
				$user_ids = array_merge($user_ids, $this->db->loadColumn());

				$query->clear()
					->select('eu.id')
					->from($this->db->quoteName('#__emundus_users', 'eu'))
					->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'ep') .  ' ON ep.user_id = eu.user_id')
					->leftJoin($this->db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id', 'et_profiles') .  ' ON ep.profile_id = et_profiles.profile_id OR eu.profile = et_profiles.profile_id')
					->where('et_profiles.parent_id = ' . $trigger->id);

				$this->db->setQuery($query);
				$user_ids = array_merge($user_ids, $this->db->loadColumn());

				$user_ids = array_unique($user_ids);
			} catch (Exception $e) {
				Log::add('Failed to get user ids from sms trigger ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}

		return $user_ids;
	}

	private function getProgramIdsFromCodes(array $codes): array
	{
		$ids = [];

		if (!empty($codes)) {
			$query = $this->db->createQuery();

			$query->select('id')
				->from('#__emundus_setup_programmes')
				->where('code IN (' . implode(',', $this->db->quote($codes)) . ')');

			try {
				$this->db->setQuery($query);
				$ids = $this->db->loadColumn();
			} catch (Exception $e) {

			}
		}

		return $ids;
	}
}
