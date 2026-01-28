<?php
/**
 * @package     Joomla\Plugin\Emundus\MicrosoftDynamics\Factory
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\MicrosoftDynamics\Factory;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Entity\MicrosoftDynamicsEntity;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Repository\MicrosoftDynamicsRepository;

class MicrosoftDynamicsFactory
{
	private ?\EmundusHelperFabrik $helperFabrik = null;

	public function __construct(
		private readonly DatabaseInterface           $database,
		private readonly \EmundusModelApplication    $modelApplication,
		private readonly MicrosoftDynamicsRepository $repository,
	)
	{}

	public function getMicrosoftDynamicsConfig($name, $data, $training = null, $check_status = true): array
	{
		$configurations = [];

		try
		{
			$query = $this->database->getQuery(true);

			if (!empty($training))
			{
				$query->clear()
					->select('params')
					->from($this->database->quoteName('#__emundus_setup_sync'))
					->where($this->database->quoteName('type') . ' = ' . $this->database->quote('microsoft_dynamics'));
				$this->database->setQuery($query);
				$params = $this->database->loadResult();

				if (!empty($params) && $params !== '{}')
				{
					$params = json_decode($params, true);
					if ($params['configurations'])
					{
						foreach ($params['configurations'] as $config)
						{
							if (($config['event'] == $name || $name === 'onMicrosftDynamicsSync') && (!empty($config['programs']) && in_array($training, $config['programs'])))
							{
								if($check_status)
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
								} else {
									$configurations[] = $config;
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

	public function prepareDatas(object $api, array $config, array $data, bool $withData = true): bool
	{
		$this->helperFabrik = new \EmundusHelperFabrik();
		switch ($config['action'])
		{
			case 'create':
				if (!empty($config['fields']))
				{
					return $this->createCRM($api, $config, $data, $withData);
				}
				break;
			default:
				return false;
		}
	}

	private function createCRM(object $api, array $config, array $data, bool $withData = true): bool
	{
		$query = $this->database->getQuery(true);

		$rowid = 0;

		$filters = [];
		if ($withData && !empty($config['lookupKeys']))
		{
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
		}

		$params = [];
		if ($withData)
		{
			foreach ($config['fields'] as $field)
			{
				if ($field['type'] == 'join' && !empty($field['joinEntity']))
				{
					$query->clear()
						->select('collectionname,name')
						->from($this->database->quoteName('data_microsoft_dynamics_entities'))
						->where($this->database->quoteName('entityid') . ' = ' . $this->database->quote($field['joinEntity']));
					$this->database->setQuery($query);
					$joinEntity = $this->database->loadAssoc();

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
		}

		$applicant = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($data['fnumInfos']['applicant_id']);

		$microsoftDynamicsEntity = new MicrosoftDynamicsEntity(
			$data['fnumInfos']['campaign_id'],
			$rowid,
			$applicant,
			$params,
			$filters,
			['event' => $config['event'], 'training' => $data['fnumInfos']['training'], 'data' => json_encode(['state' => $config['eventParams']['state'], 'oldstate' => $config['eventParams']['oldstate'], 'name' => $config['name'], 'collectionname' => $config['collectionname']])],
			$data['fnum']
		);

		return $this->repository->flush($microsoftDynamicsEntity, $config['collectionname'], $config['name'], $config['action']);
	}

	private function getFieldValue($field, $data, $api)
	{
		$value = null;
		$query = $this->database->getQuery(true);

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
						->from($this->database->quoteName($element[0]))
						->where($this->database->quoteName($primaryKey) . ' = ' . $this->database->quote($data['fnumInfos']['applicant_id']));
					$this->database->setQuery($query);
					$value = $this->database->loadResult();
				}
				elseif (strpos($field['elementId'], 'jos_emundus_campaign_candidature') !== false)
				{
					$element = explode('___', $field['elementId']);

					$query->clear()
						->select($element[1])
						->from($this->database->quoteName('jos_emundus_campaign_candidature'))
						->where($this->database->quoteName('fnum') . ' = ' . $this->database->quote($data['fnum']));
					$this->database->setQuery($query);
					$value = $this->database->loadResult();
				}
				else
				{
					$element = explode('___', $field['elementId']);
					if(count($element) > 1)
					{
						$query->clear()
							->select('fe.id,ffg.form_id')
							->from($this->database->quoteName('#__fabrik_elements', 'fe'))
							->leftJoin($this->database->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->database->quoteName('ffg.group_id') . ' = ' . $this->database->quoteName('fe.group_id'))
							->leftJoin($this->database->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->database->quoteName('fl.form_id') . ' = ' . $this->database->quoteName('ffg.form_id'))
							->where($this->database->quoteName('fl.db_table_name') . ' = ' . $this->database->quote($element[0]))
							->where($this->database->quoteName('fe.name') . ' = ' . $this->database->quote($element[1]));
						$this->database->setQuery($query);
						$elementDetails = $this->database->loadObject();

						$value = $this->modelApplication->getValuesByElementAndFnum($data['fnum'], $elementDetails->id, $elementDetails->form_id, 1);
					}
					else {
						$value = $this->helperFabrik->getValueByAlias($element[0], $data['fnum']);
						if(!empty($value))
						{
							$value = $value['raw'];
						}
					}
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

		if ($field['transform'])
		{
			// Search in class for the method
			$method = $field['transform'];
			if (method_exists($this, $method))
			{
				$reflection = new \ReflectionMethod($this, $method);
				$parameters = $reflection->getParameters();
				$apiParameter = false;
				foreach ($parameters as $parameter)
				{
					if ($parameter->getName() == 'api')
					{
						$apiParameter = true;
					}
				}

				if($apiParameter) {
					$value = $this->$method($value, $api);
				} else
				{
					$value = $this->$method($value);
				}
			}
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
				case 'datetime':
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
								if ($field['type'] == 'datetime')
								{
									$value = date('Y-m-d\TH:i', $date);
								}
								else
								{
									$value = date('Y-m-d', $date);
								}
							}
						}
					}
					break;
				case 'join':
					if ((empty($field['collectionname']) || empty($field['name'])) && !empty($field['joinEntity']))
					{
						$query->clear()
							->select('collectionname,name')
							->from($this->database->quoteName('data_microsoft_dynamics_entities'))
							->where($this->database->quoteName('entityid') . ' = ' . $this->database->quote($field['joinEntity']));
						$this->database->setQuery($query);
						$joinEntity = $this->database->loadAssoc();

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

							//TODO: Store value in a table to avoid calls in future
						}
					}
					break;
			}
		}

		if(!empty($field['length'])) {
			$value = substr($value, 0, $field['length']);
		}

		return $value;
	}

	private function communeCodeToName(string $code): string|null
	{
		$fullName = '';
		$query    = $this->database->getQuery(true);

		if (!empty($code))
		{
			$query->select('nom,codeDepartement')
				->from($this->database->quoteName('data_communes'))
				->where($this->database->quoteName('code') . ' = ' . $this->database->quote($code));
			$this->database->setQuery($query);
			$commune = $this->database->loadAssoc();

			if (!empty($commune))
			{
				$commune['nom'] = $this->remove_accents($commune['nom']);
				$commune['nom'] = strtoupper($commune['nom']);

				$fullName = $commune['nom'] . ' - ' . $commune['codeDepartement'];
			}
		}

		return $fullName;
	}

	private function paysInseeToIso2(string|int $insee): string|null
	{
		$iso2  = '';
		$query = $this->database->getQuery(true);

		if (!empty($insee))
		{
			$query->select('iso2')
				->from($this->database->quoteName('data_country_esa'))
				->where($this->database->quoteName('insee') . ' = ' . $this->database->quote($insee));
			$this->database->setQuery($query);
			$country = $this->database->loadResult();

			if (!empty($country))
			{
				$iso2 = $country;
			}
		}

		return $iso2;
	}

	private function serieToCRM(string $serie): string|null
	{
		$serieCrm = '';

		$query = $this->database->getQuery(true);

		if (!empty($serie))
		{
			$query->select('crm_name')
				->from($this->database->quoteName('data_serie_diplome'))
				->where($this->database->quoteName('label') . ' = ' . $this->database->quote($serie));
			$this->database->setQuery($query);

			$serieCrm = $this->database->loadResult();
		}

		return $serieCrm;
	}

	private function getbookingdate(int|string $availability): string|null
	{
		$bookingDate = '';
		$query       = $this->database->getQuery(true);

		if (!empty($availability))
		{
			$query->select('start_date')
				->from($this->database->quoteName('jos_emundus_setup_availabilities'))
				->where($this->database->quoteName('id') . ' = ' . $this->database->quote($availability));
			$this->database->setQuery($query);

			$bookingDate = $this->database->loadResult();
		}

		return $bookingDate;
	}

	private function getbookinglocation(int|string $event): string|null
	{
		$location = '';
		$query    = $this->database->getQuery(true);

		if (!empty($event))
		{
			$query->select('del.name')
				->from($this->database->quoteName('jos_emundus_setup_events', 'ese'))
				->leftJoin($this->database->quoteName('data_events_location', 'del') . ' ON ' . $this->database->quoteName('del.id') . ' = ' . $this->database->quoteName('ese.location'))
				->where($this->database->quoteName('ese.id') . ' = ' . $this->database->quote($event));
			$this->database->setQuery($query);
			$location = $this->database->loadResult();
		}

		return $location;
	}

	private function getCRMStatus(int|string $status): string|null
	{
		$crmStatus = '';
		$query       = $this->database->getQuery(true);

		if(!empty($status))
		{
			$query->select('id_crm')
				->from($this->database->quoteName('jos_emundus_setup_status'))
				->where($this->database->quoteName('step') . ' = ' . $this->database->quote($status));
			$this->database->setQuery($query);

			$crmStatus = $this->database->loadResult();
		}

		return $crmStatus;
	}

	private function getCRMParametre(string $parameter, object $api): string|null
	{
		$value = '';

		$params = [
			'$top'    => 1,
			'$select' => $parameter
		];

		$m_sync = new \EmundusModelSync();
		$result = $m_sync->callApi($api, 'esa_parametres', 'get', $params);

		if ($result['status'] == 200 && !empty($result['data']))
		{
			$value = $result['data']->value[0]->{$parameter};
		}

		return $value;
	}

	private function sanitizePhone(string $phone): string
	{
		if(!empty($phone))
		{
			return preg_replace('/^[a-zA-Z]{2}/', '', $phone);
		}
		else {
			return '';
		}
	}

	private function remove_accents(string $string): string
	{
		$string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

		return preg_replace('/[\'\-]/', ' ', $string);
	}
}