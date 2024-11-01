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
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Actionlogs\Administrator\Helper\ActionlogsHelper;
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\DispatcherInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Emundus extends ActionLogPlugin
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

	public function onAfterCampaignUpdate($data, $old_data)
	{
		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CAMPAIGN';
		$context            = 'com_emundus.campaign';

		$cid            = $data['id'];
		$more_data['campaign_label'] = $data['label'];

		$this->setDiffData($data, $old_data);
		$message = $this->setMessage($cid, 'update', 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CAMPAIGN_TITLE', 'done', $old_data, $data, $more_data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	public function onAfterUpdateConfiguration($data, $old_data = [], $type = '', $status = 'done', $context= 'com_emundus.settings')
	{
		$jUser = $this->getApplication()->getIdentity();

		$messageLanguageKey = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION';

		$this->setDiffData($data, $old_data);

		$id = ComponentHelper::getComponent('com_emundus')->id;
		$title = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION_TITLE';
		if(!empty($type)) {
			$title .= '_' . strtoupper($type);
		}
		$message = $this->setMessage($id, 'update', $title, $status, $old_data, $data);

		$this->addLog([$message], $messageLanguageKey, $context, $jUser->id);
	}

	private function setMessage($id = 0, $action = 'update', $title = 'PLG_ACTIONLOG_EMUNDUS_UPDATE_CONFIGURATION_TITLE', $status = 'done', $old_data = [], $new_data = [], $more_data = [])
	{
		$jUser = $this->getApplication()->getIdentity();

		$message = [
			'id' => $id,
			'action'      => $action,
			'title'       => $title,
			'userid'      => $jUser->id,
			'username'    => $jUser->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $jUser->id,
			'status'      => $status,
			'old_data'    => json_encode($old_data),
			'new_data'    => json_encode($new_data),
		];

		if(!empty($more_data)) {
			$message = array_merge($message, $more_data);
		}

		return $message;
	}

	private function setDiffData(&$data, &$old_data)
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
