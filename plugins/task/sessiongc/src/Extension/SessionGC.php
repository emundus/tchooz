<?php

/**
 * @package     Joomla.Plugins
 * @subpackage  Task.sessiongc
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\SessionGC\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\MetadataManager;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. Session data purge task.
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class SessionGC extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	/**
	 * The meta data manager
	 *
	 * @var   MetadataManager
	 *
	 * @since 4.4.0
	 */
	private $metadataManager;

	private ?Container $container;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	private const TASKS_MAP = [
		'session.gc' => [
			'langConstPrefix' => 'PLG_TASK_SESSIONGC',
			'method'          => 'sessionGC',
			'form'            => 'sessionGCForm',
		],
	];

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher       The dispatcher
	 * @param   array                $config           An optional associative array of configuration settings
	 * @param   MetadataManager      $metadataManager  The user factory
	 *
	 * @since   4.4.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config, MetadataManager $metadataManager, ?Container $container = null)
	{
		parent::__construct($dispatcher, $config);

		$this->metadataManager = $metadataManager;
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @since  5.0.0
	 * @throws \Exception
	 */
	private function sessionGC(ExecuteTaskEvent $event): int
	{
		$enableGC = (int) $event->getArgument('params')->enable_session_gc ?? 1;

		$session = $this->getApplication()->getSession();
		if($this->getApplication()->getName() === 'cli' && !empty($this->container))
		{
			$session = $this->container->get('session.web.site');
		}

		if ($enableGC) {
			$session->gc();
		}

		$enableMetadata = (int) $event->getArgument('params')->enable_session_metadata_gc ?? 1;

		if ($this->getApplication()->get('session_handler', 'none') !== 'database' && $enableMetadata) {
			$this->metadataManager->deletePriorTo(time() - $session->getExpire());
		}

		$this->logTask('SessionGC end');

		return Status::OK;
	}
}