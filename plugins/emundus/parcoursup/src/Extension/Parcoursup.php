<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Emundus\Parcoursup\Factory\ParcoursupFactory;
use Joomla\Plugin\Emundus\Parcoursup\Helper\ArrayHelper;
use Joomla\Plugin\Emundus\Parcoursup\ParcoursupDataProvider;
use Joomla\Plugin\Emundus\Parcoursup\ParcoursupMapper;
use Joomla\Plugin\Emundus\Parcoursup\Repository\ParcoursupRepository;
use Joomla\Plugin\Emundus\Parcoursup\Repository\UserRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Parcoursup extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	private array $datas = [];

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
			'onWebhookCallbackProcess' => 'createFiles',
		];
	}

	public function createFiles(GenericEvent $event): array
	{
		$args = $event->getArguments();

		$webhookDatas = $args['webhook_datas'];
		$webhookFiles = $args['webhook_files'];
		$webhookType  = $args['type'];

		$results = ['status' => true, 'count' => 0, 'message' => ''];

		if (!empty($webhookType) && $webhookType == 'parcoursup')
		{
			$debugMode           = (bool) $this->params->get('debug_mode', 0);
			$baseJsonPath        = $this->params->get('base_path', 'exportDeDonnees.exportCandidats');
			$campaignAttribute   = $this->params->get('campaign_attribute', 'formationCode');
			$applicantsAttribute = $this->params->get('applicants_attribute', 'candidats');
			$skipActivation      = $this->params->get('skip_activation', true);

			try
			{
				$dispatcher = Factory::getApplication()->getDispatcher();

				Log::addLogger(['text_file' => 'com_emundus.parcoursup.log.php'], Log::DEBUG, 'com_emundus.parcoursup');
				Log::addLogger(['text_file' => 'com_emundus.parcoursup.error.php'], Log::ERROR, 'com_emundus.parcoursup');
				require_once JPATH_SITE . '/components/com_emundus/models/users.php';

				$arrayHelper       = new ArrayHelper();
				$parcoursupFactory = new ParcoursupFactory($this->getDatabase());

				$dataProvider = new ParcoursupDataProvider($baseJsonPath, $debugMode, $this->getDatabase(), $arrayHelper);
				if (!empty($webhookFiles))
				{
					$datas = $dataProvider->loadFromFile($webhookFiles);
				}

				if (!empty($datas))
				{
					$config = $this->getParcoursupConfig();

					$mapper      = new ParcoursupMapper($datas, $config, $baseJsonPath, $campaignAttribute, $applicantsAttribute, $this->getDatabase(), $arrayHelper);
					$mappedDatas = $mapper->mapDatas();

					$datas = null;

					foreach ($mappedDatas as $data)
					{
						if (!$parcoursupFactory->prepareDatas($data))
						{
							// Log error
							$onWebhookCallbackFailed = new GenericEvent(
								'onWebhookCallbackFailed',
								// Datas to pass to the event
								['type' => $webhookType, 'datas' => $data]
							);
							$dispatcher->dispatch('onWebhookCallbackFailed', $onWebhookCallbackFailed);
						}

						$results['count']++;
					}
				}
			}
			catch (\Exception $e)
			{
				return ['status' => false, 'message' => $e->getMessage()];
			}
		}

		$event->setArgument('results', $results);

		return $results;
	}

	private function getParcoursupConfig(): array
	{
		$config = [];
		$query  = $this->getDatabase()->getQuery(true);

		$query->select('params')
			->from('#__emundus_setup_sync')
			->where('type = ' . $this->getDatabase()->quote('parcoursup'));
		$this->getDatabase()->setQuery($query);
		$jsonConfig = $this->getDatabase()->loadResult();

		if (!empty($jsonConfig))
		{
			$config = json_decode($jsonConfig, true);
		}

		return $config;
	}
}