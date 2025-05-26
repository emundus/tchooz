<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Actionlog\Emundus\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Actionlogs\Administrator\Helper\ActionlogsHelper;
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Emundus extends ActionLogPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher
	 * @param   array                $config      An optional associative array of configuration settings
	 *
	 * @since   3.9.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterCampaignUpdate'          => 'onAfterCampaignUpdate',
			'onAfterCampaignCreate'          => 'onAfterCampaignCreate',
			'onAfterUpdateConfiguration'     => 'onAfterUpdateConfiguration',
			'onAfterMicrosoftDynamicsCreate' => 'onAfterMicrosoftDynamicsCreate',
			'onAfterMicrosoftDynamicsUpdate' => 'onAfterMicrosoftDynamicsUpdate',
			'onAfterAmmonApplicantCreate'    => 'onAfterAmmonApplicantCreate',
			'onAfterAmmonRegistration'       => 'onAfterAmmonRegistration',
			'onAmmonFoundSimilarName'        => 'onAmmonFoundSimilarName',
			'onAmmonSync'                    => 'onAmmonSync',
			'onWebhookCallbackFailed'        => 'onWebhookCallbackFailed',
			'onAfterImportRow'               => 'onAfterImportRow',
			'onYousignRequestInitiated'      => 'onYousignRequestInitiated',
			'onYousignDocumentAdded'         => 'onYousignDocumentAdded',
			'onYousignSignersUpdated'        => 'onYousignSignersUpdated',
			'onYousignRequestActivated'      => 'onYousignRequestActivated',
			'onYousignRequestCompleted'      => 'onYousignRequestCompleted',
			'onYousignError'                 => 'onYousignError',
			'onYousignSendReminder'          => 'onYousignSendReminder',
			'onYousignRequestCancelled'      => 'onYousignRequestCancelled'
		];
	}

	public function onAfterCampaignUpdate(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$data     = $arguments['data'];
		$old_data = $arguments['old_data'];
		$user_id  = $arguments['user_id'] ?? 0;
		if (empty($user_id))
		{
			$user_id = $this->getApplication()->getIdentity()->id;
		}

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CAMPAIGN';
		$context            = 'com_emundus.campaign';

		$cid                         = $data['id'];
		$more_data['campaign_label'] = $data['label'];

		$this->setDiffData($data, $old_data);
		$message = $this->setMessage($cid, 'update', 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CAMPAIGN_TITLE', 'done', $old_data, $data, $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $user_id);
	}

	public function onAfterCampaignCreate($data)
	{
		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_CREATE_CAMPAIGN';
		$context            = 'com_emundus.campaign';

		$cid                         = $data['id'];
		$more_data['campaign_label'] = $data['label'];

		$message = $this->setMessage($cid, 'update', 'PLG_ACTIONLOG_EMUNDUS_CAMPAIGN_CREATE', 'done', [], $data, $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterUpdateConfiguration(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$data     = $arguments['data'];
		$old_data = $arguments['old_data'];
		$status   = $arguments['status'];
		$context  = $arguments['context'] ?: 'com_emundus.configuration';

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION';

		$this->setDiffData($data, $old_data);

		$id    = ComponentHelper::getComponent('com_emundus')->id;
		$title = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION_TITLE';
		if (!empty($type))
		{
			$title .= '_' . strtoupper($type);
		}
		$message = $this->setMessage($id, 'update', $title, $status, $old_data, $data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterMicrosoftDynamicsCreate(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_MICROSOFT_DYNAMICS_CREATE';
		$context            = 'com_emundus.microsoftdynamics';

		$more_data['fnum']    = $arguments['fnum'];
		$more_data['entity']  = $arguments['config']['name'];
		$more_data['message'] = $arguments['message'];

		$message = $this->setMessage($arguments['id'], 'create', 'PLG_ACTIONLOG_EMUNDUS_MICROSOFT_DYNAMICS_CREATE_ACTION', $arguments['status'], [], $arguments['data'], $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterMicrosoftDynamicsUpdate(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_MICROSOFT_DYNAMICS_UPDATE';
		$context            = 'com_emundus.microsoftdynamics';

		$more_data['fnum']    = $arguments['fnum'];
		$more_data['entity']  = $arguments['config']['name'];
		$more_data['message'] = $arguments['message'];

		$message = $this->setMessage($arguments['id'], 'update', 'PLG_ACTIONLOG_EMUNDUS_MICROSOFT_DYNAMICS_UPDATE_ACTION', $arguments['status'], [], $arguments['data'], $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterAmmonRegistration(GenericEvent $event): void
	{
		$arguments = $event->getArguments();
		$jUser     = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_AMMON_REGISTRATION';
		$context            = 'com_emundus.ammon';

		$more_data['fnum']       = $arguments['fnum'];
		$more_data['session_id'] = $arguments['session_id'];
		$more_data['message']    = $arguments['message'];
		$title                   = 'PLG_ACTIONLOG_EMUNDUS_AMMON_REGISTRATION_SUCCESS';

		if ($arguments['status'] == 'error')
		{
			$title = 'PLG_ACTIONLOG_EMUNDUS_AMMON_REGISTRATION_ERROR';
		}

		$message = $this->setMessage('ammon', 'create', $title, $arguments['status'], [], $arguments['data'], $more_data);
		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterAmmonApplicantCreate(GenericEvent $event): void
	{
		$arguments          = $event->getArguments();
		$jUser              = $this->getApplication()->getIdentity();
		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_AMMON_APPLICANT';
		$context            = 'com_emundus.ammon';

		$more_data['fnum']       = $arguments['fnum'];
		$more_data['session_id'] = $arguments['session_id'];

		$message = $this->setMessage('ammon', 'create', 'PLG_ACTIONLOG_EMUNDUS_AMMON_APPLICANT_ACTION', $arguments['status'], [], $arguments['data'], $more_data);
		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAmmonFoundSimilarName(GenericEvent $event): void
	{
		$arguments          = $event->getArguments();
		$jUser              = $this->getApplication()->getIdentity();
		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_AMMON_FOUND_SIMILAR_NAME';
		$context            = 'com_emundus.ammon';

		$more_data['fnum']    = $arguments['fnum'];
		$more_data['name']    = $arguments['name'];
		$more_data['message'] = $arguments['message'];

		$message = $this->setMessage('ammon', 'create', 'PLG_ACTIONLOG_EMUNDUS_AMMON_FOUND_SIMILAR_NAME_ACTION', 'error', [], [], $more_data);
		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAmmonSync(GenericEvent $event): void
	{
		$arguments          = $event->getArguments();
		$jUser              = $this->getApplication()->getIdentity();
		$messageLanguageKey = $arguments['message_key'];
		$context            = 'com_emundus.ammon';

		$old_data  = $arguments['old_data'] ?? [];
		$new_data  = $arguments['new_data'] ?? [];
		$more_data = $arguments['more_data'] ?? [];
		$status    = $arguments['status'] ?? 'done';

		$message = $this->setMessage('ammon', 'create', $arguments['title'], $status, $old_data, $new_data, $more_data);
		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onWebhookCallbackFailed(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_EMUNDUS_WEBHOOK_CALLBACK_FAILED';
		$context            = 'com_emundus.webhook.' . $arguments['type'];

		$message = $this->setMessage(0, 'create', 'PLG_EMUNDUS_WEBHOOK_CALLBACK_FAILED', 'error', [], [], $arguments['datas']);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterImportRow(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_IMPORT_ROW';
		$context            = 'com_emundus.import';

		$more_data['fnum'] = $arguments['fnum'];

		$message = $this->setMessage($arguments['id'], 'create', 'PLG_ACTIONLOG_EMUNDUS_IMPORT_ROW', $arguments['status'], [], $arguments['data'], $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignRequestInitiated(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_INITIATED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_INITIATED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignDocumentAdded(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_DOCUMENT_ADDED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_DOCUMENT_ADDED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignSignersUpdated(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_SIGNERS_UPDATED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_SIGNERS_UPDATED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignRequestActivated(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_ACTIVATED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_ACTIVATED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignRequestCompleted(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_COMPLETED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_COMPLETED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignError(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_ERROR';
		$context            = 'com_emundus.yousign';

		$more_data['message'] = $arguments['message'];

		$message = $this->setMessage(0, 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_ERROR', 'error', [], $arguments['data'], $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignSendReminder(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_SEND_REMINDER';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_SEND_REMINDER', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onYousignRequestCancelled(GenericEvent $event)
	{
		$arguments = $event->getArguments();

		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_CANCELLED';
		$context            = 'com_emundus.yousign';

		$more_data['fnum'] = $arguments['application_file']['fnum'];

		$message = $this->setMessage($arguments['yousign_request']->getId(), 'create', 'PLG_ACTIONLOG_EMUNDUS_YOUSIGN_REQUEST_CANCELLED', $arguments['status'], [], $arguments['yousign_request']->__serialize(), $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	private function setMessage($id = 0, $action = 'update', $title = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION_TITLE', $status = 'done', $old_data = [], $new_data = [], $more_data = [])
	{
		$jUser = $this->getApplication()->getIdentity();

		$message = [
			'id'          => $id,
			'action'      => $action,
			'title'       => $title,
			'userid'      => $jUser->id,
			'username'    => $jUser->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $jUser->id,
			'status'      => $status,
			'old_data'    => json_encode($old_data),
			'new_data'    => json_encode($new_data),
		];

		if (!empty($more_data))
		{
			$message = array_merge($message, $more_data);
		}

		return $message;
	}

	private function setDiffData(&$data, &$old_data)
	{
		if (!empty($data))
		{
			$diff              = array_diff_assoc($data, $old_data);
			$columns_to_remove = array_diff_key($old_data, $diff);
			foreach ($columns_to_remove as $key => $value)
			{
				unset($old_data[$key]);
				unset($data[$key]);
			}
		}
	}
}
