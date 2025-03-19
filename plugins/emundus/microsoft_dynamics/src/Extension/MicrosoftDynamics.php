<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\MicrosoftDynamics\Extension;

require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
require_once JPATH_SITE . '/components/com_emundus/models/application.php';
require_once JPATH_SITE . '/components/com_emundus/models/files.php';

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Entity\MicrosoftDynamicsEntity;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Factory\MicrosoftDynamicsFactory;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Repository\MicrosoftDynamicsRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class MicrosoftDynamics extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterStatusChange' => 'process',
		];
	}

	public function process(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();

		$m_sync = new \EmundusModelSync();
		$api    = $m_sync->getApi(0, 'microsoft_dynamics');

		if (!empty($api) && $api->enabled == 1)
		{
			if (!empty($data['fnum']))
			{
				Log::addLogger(['text_file' => 'com_emundus.microsoft_dynamics.log.php'], Log::DEBUG, 'com_emundus.microsoft_dynamics');
				Log::addLogger(['text_file' => 'com_emundus.microsoft_dynamics.error.php'], Log::ERROR, 'com_emundus.microsoft_dynamics');

				$modelFiles = new \EmundusModelFiles();
				$modelApplication = new \EmundusModelApplication();
				$repository       = new MicrosoftDynamicsRepository($this->getDatabase());
				$crmFactory = new MicrosoftDynamicsFactory($this->getDatabase(), $modelApplication, $repository);

				$fnumInfos = $modelFiles->getFnumInfos($data['fnum']);
				if (!empty($fnumInfos))
				{
					$data['fnumInfos'] = $fnumInfos;
					$training          = $fnumInfos['training'];

					$configurations = $crmFactory->getMicrosoftDynamicsConfig($name, $data, $training);

					if (!empty($configurations))
					{
						foreach ($configurations as $config)
						{
							if (!empty($config['action']) && !empty($config['collectionname']) && !empty($config['name']))
							{
								if(!$crmFactory->prepareDatas($api, $config, $data, false)) {
									Log::add('Error while preparing datas for Microsoft Dynamics', Log::ERROR, 'com_emundus.microsoft_dynamics');
								}
							}
						}
					}
				}
			}
		}
	}

	private function getMicrosoftDynamicsConfig($name, $data, $training = null): array
	{
		$db = $this->getDatabase();

		$configurations = [];

		try
		{
			$query = $db->getQuery(true);

			if (!empty($training))
			{
				$query->clear()
					->select('params')
					->from($db->quoteName('#__emundus_setup_sync'))
					->where($db->quoteName('type') . ' = ' . $db->quote('microsoft_dynamics'));
				$db->setQuery($query);
				$params = $db->loadResult();

				if (!empty($params) && $params !== '{}')
				{
					$params = json_decode($params, true);
					if ($params['configurations'])
					{
						foreach ($params['configurations'] as $config)
						{
							if ($config['event'] == $name && (!empty($config['programs']) && in_array($training, $config['programs'])))
							{
								if ($config['event'] == 'onAfterStatusChange' && !empty($data['state']))
								{
									if (!empty($config['eventParams']) && !empty($config['eventParams']['state']) && $config['eventParams']['state'] == $data['state'])
									{
										if ((!empty($config['eventParams']['oldstate']) && $config['eventParams']['oldstate'] == $data['oldstate']) || empty($config['eventParams']['oldstate']))
										{
											$configurations[] = $config;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		return $configurations;
	}
}