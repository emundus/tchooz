<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\MicrosoftDynamics\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\Logger\DatabaseLogger;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
require_once JPATH_SITE . '/components/com_emundus/models/application.php';

use classes\api\Api;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
//TODO: Move api calls to a CRON job
final class MicrosoftDynamics extends CMSPlugin implements SubscriberInterface
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
				// Get programme
				require_once JPATH_SITE . '/components/com_emundus/models/files.php';
				$m_files = new \EmundusModelFiles();

				$training  = null;
				$fnumInfos = $m_files->getFnumInfos($data['fnum']);
				if (!empty($fnumInfos))
				{
					$data['fnumInfos'] = $fnumInfos;
					$training          = $fnumInfos['training'];

					$configurations = $this->getConfigByEvent($name, $data, $training);

					if (!empty($configurations))
					{
						foreach ($configurations as $config)
						{
							if (!empty($config['action']) && !empty($config['collectionname']) && !empty($config['name']))
							{
								switch ($config['action'])
								{
									case 'create':
										if (!empty($config['fields']))
										{
											$this->createCRM($api, $config, $data);
										}
										break;
									case 'update':
										if (!empty($config['fields']))
										{
											$this->updateCRM($api, $config, $data);
										}
										break;
									case 'delete':
										$this->deleteCRM($api, $config, $data);
										break;
								}
							}
						}
					}
				}
			}
		}
	}

	private function createCRM($api, $config, $data): void
	{
		$status = false;
		$db     = $this->getDatabase();
		$query  = $db->getQuery(true);

		$rowid = 0;

		// Check if the record already exists
		if (!empty($config['lookupKeys']))
		{
			$filters = [];
			foreach ($config['lookupKeys'] as $lookupKey)
			{
				$value = $this->getFieldValue($lookupKey, $data, $api);
				if (!empty($value))
				{
					$filter    = [
						'attribute' => $lookupKey['attribute'],
						'value'     => strtolower($value)
					];
					$filters[] = $filter;
				}
			}

			$attribute_id = $config['name'] . 'id';
			if (!empty($filters))
			{
				$params = [
					'$top'    => 1,
					'$select' => $attribute_id,
					'$filter' => implode(' and ', array_map(function ($filter) {
						return $filter['attribute'] . ' eq \'' . $filter['value'] . '\'';
					}, $filters))
				];
				$m_sync = new \EmundusModelSync();
				$result = $m_sync->callApi($api, $config['collectionname'], 'get', $params);

				if ($result['status'] == 200)
				{
					if (!empty($result['data']->value))
					{
						$rowid = $result['data']->value[0]->{$attribute_id};
					}
				}
			}
		}

		$params = [];
		foreach ($config['fields'] as $field)
		{
			if ($field['type'] == 'join' && !empty($field['joinEntity']))
			{
				$query->clear()
					->select('collectionname,name')
					->from($db->quoteName('data_microsoft_dynamics_entities'))
					->where($db->quoteName('entityid') . ' = ' . $db->quote($field['joinEntity']));
				$db->setQuery($query);
				$joinEntity = $db->loadAssoc();

				$field['collectionname'] = $joinEntity['collectionname'];
				$field['name']           = $joinEntity['name'];
			}

			$value = $this->getFieldValue($field, $data, $api);
			if (!empty($value))
			{
				if ($field['type'] == 'join' && !empty($field['collectionname']) && !empty($field['name']))
				{
					$params[$field['attribute'] . '@odata.bind'] = '/' . $field['collectionname'] . '(' . $value . ')';
				}
				else
				{
					$params[$field['attribute']] = $value;
				}
			}
		}

		if (!empty($rowid))
		{
			// Update
			$eventName = 'onAfterMicrosoftDynamicsUpdate';

			$m_sync = new \EmundusModelSync();
			$result = $m_sync->callApi($api, $config['collectionname'] . '(' . $rowid . ')', 'patch', json_encode($params));
		}
		else
		{
			// Create
			$eventName = 'onAfterMicrosoftDynamicsCreate';

			$m_sync = new \EmundusModelSync();
			$result = $m_sync->callApi($api, $config['collectionname'], 'post', $params);
		}

		if ($result['status'] == 204)
		{
			$log_status = 'success';
			$message    = '';
		}
		else
		{
			$log_status = 'error';
			$message    = $result['message'];
		}

		$dispatcher = Factory::getApplication()->getDispatcher();

		$onAfterMicrosoftDynamicsEventHandler = new GenericEvent(
			'onCallEventHandler',
			[$eventName,
				// Datas to pass to the event
				['id' => $data['fnumInfos']['ccid'], 'data' => $params, 'config' => $config, 'status' => $log_status, 'message' => $message]
			]
		);
		$onAfterMicrosoftDynamics             = new GenericEvent(
			$eventName,
			// Datas to pass to the event
			['id' => $data['fnumInfos']['ccid'], 'data' => $params, 'config' => $config, 'status' => $log_status, 'message' => $message]
		);

		// Dispatch the event
		$dispatcher->dispatch('onCallEventHandler', $onAfterMicrosoftDynamicsEventHandler);
		$dispatcher->dispatch($eventName, $onAfterMicrosoftDynamics);
	}

	private function updateCRM($api, $config, $data): void
	{
	}

	private function deleteCRM($api, $config, $data): void
	{
	}

	private function getConfigByEvent($name, $data, $training = null): array
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

	private function getFieldValue($field, $data, $api)
	{
		$m_application = new \EmundusModelApplication();

		$value = null;
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		try
		{
			if (!empty($field['elementId']))
			{
				if (strpos($field['elementId'], 'jos_users') !== false || strpos($field['elementId'], 'jos_emundus_users') !== false)
				{
					$element    = explode('___', $field['elementId']);
					$primaryKey = $element[0] === 'jos_emundus_users' ? 'user_id' : 'id';

					$query->clear()
						->select($element[1])
						->from($db->quoteName($element[0]))
						->where($db->quoteName($primaryKey) . ' = ' . $db->quote($data['fnumInfos']['applicant_id']));
					$db->setQuery($query);
					$value = $db->loadResult();
				}
				elseif (strpos($field['elementId'], 'jos_emundus_campaign_candidature') !== false)
				{
					$element = explode('___', $field['elementId']);

					$query->clear()
						->select($element[1])
						->from($db->quoteName('jos_emundus_campaign_candidature'))
						->where($db->quoteName('fnum') . ' = ' . $db->quote($data['fnum']));
					$db->setQuery($query);
					$value = $db->loadResult();
				}
				else
				{
					$element = explode('___', $field['elementId']);

					$query->clear()
						->select('fe.id,ffg.form_id')
						->from($db->quoteName('#__fabrik_elements', 'fe'))
						->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $db->quoteName('ffg.group_id') . ' = ' . $db->quoteName('fe.group_id'))
						->leftJoin($db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $db->quoteName('fl.form_id') . ' = ' . $db->quoteName('ffg.form_id'))
						->where($db->quoteName('fl.db_table_name') . ' = ' . $db->quote($element[0]))
						->where($db->quoteName('fe.name') . ' = ' . $db->quote($element[1]));
					$db->setQuery($query);
					$elementDetails = $db->loadObject();

					$value = $m_application->getValuesByElementAndFnum($data['fnum'], $elementDetails->id, $elementDetails->form_id, 1);
				}
			}
			elseif (!empty($field['value']))
			{
				$value = $field['value'];
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		if (!empty($field['type']))
		{
			switch ($field['type'])
			{
				case 'list':
					if (!empty($value))
					{
						$value = $field['options'][$value];
					}
					break;
				case 'date':
					if (!empty($value))
					{
						if (strpos($value, '/') !== false)
						{
							$date = \DateTime::createFromFormat('d/m/Y', $value);
							if ($date)
							{
								$value = $date->format('Y-m-d');
							}
						}
						else
						{
							$date = strtotime($value);
							if ($date)
							{
								$value = date('Y-m-d', $date);
							}
						}
					}
					break;
				case 'join':
					if((empty($field['collectionname']) || empty($field['name'])) && !empty($field['joinEntity']))
					{
						$query->clear()
							->select('collectionname,name')
							->from($db->quoteName('data_microsoft_dynamics_entities'))
							->where($db->quoteName('entityid') . ' = ' . $db->quote($field['joinEntity']));
						$db->setQuery($query);
						$joinEntity = $db->loadAssoc();

						$field['collectionname'] = $joinEntity['collectionname'];
						$field['name']           = $joinEntity['name'];
					}

					if (!empty($field['collectionname']) && !empty($field['name']))
					{
						if (!is_array($field['searchBy']))
						{
							$field['searchBy'] =
								[
									[
										'attribute' => $field['searchBy'],
									]
								];
						}

						foreach ($field['searchBy'] as &$searchBy)
						{
							if (empty($value))
							{
								$searchBy['value'] = $this->getFieldValue($searchBy, $data, $api);
							}
							else
							{
								$searchBy['value'] = $value;
							}
						}

						$params = [
							'$top'    => 1,
							'$select' => $field['name'] . 'id',
							'$filter' => implode(' and ', array_map(function ($filter) {
								return $filter['attribute'] . ' eq \'' . $filter['value'] . '\'';
							}, $field['searchBy']))
						];

						$m_sync = new \EmundusModelSync();
						$result = $m_sync->callApi($api, $field['collectionname'], 'get', $params);

						if ($result['status'] == 200)
						{

							$value = $result['data']->value[0]->{$field['name'] . 'id'};
						}
					}
					break;
			}
		}

		return $value;
	}
}