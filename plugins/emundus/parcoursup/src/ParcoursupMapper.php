<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\Emundus\Parcoursup\Entity\ParcoursupEntity;
use Joomla\Plugin\Emundus\Parcoursup\Factory\UserFactory;
use Joomla\Plugin\Emundus\Parcoursup\Helper\ArrayHelper;

class ParcoursupMapper
{
	public function __construct(
		private readonly array             $datas = [],
		private readonly array             $config = [],
		private readonly string            $basePath = '',
		private readonly string            $campaignAttribute = '',
		private readonly string            $applicantsAttribute = '',
		private readonly DatabaseInterface $database,
		private readonly ArrayHelper       $arrayHelper
	)
	{
	}

	public function mapDatas(): array
	{
		$query = $this->database->getQuery(true);

		$datas = [];

		$formations = $this->arrayHelper->getNestedValue($this->datas, $this->basePath);

		foreach ($formations as $formation)
		{
			$formationCode = $formation[$this->campaignAttribute];
			$programmeCode = $this->config['programme']['options'][$formationCode];

			if (!empty($programmeCode))
			{
				$cid = $this->getCampaignId($programmeCode);

				$applicants = $formation[$this->applicantsAttribute];

				foreach ($applicants as $applicant)
				{
					$application = [
						'campaign_id' => $cid,
					];
					foreach ($this->config['fields'] as $field)
					{
						if (is_array($field['attribute']))
						{
							$separator                        = $field['separator'] ?? ' ';
							$application[$field['elementId']] = [];
							foreach ($field['attribute'] as $attribute)
							{
								$value                              = $this->arrayHelper->getNestedValue($applicant, $attribute);
								$application[$field['elementId']][] = $this->setFieldValue($field, $value);
							}
							$application[$field['elementId']] = implode($separator, $application[$field['elementId']]);
						}
						else
						{
							$value = $this->arrayHelper->getNestedValue($applicant, $field['attribute']);

							if (!empty($field['condition']))
							{
								$value = $this->checkCondition($field, $value, $applicant);
							}

							$application[$field['elementId']] = $this->setFieldValue($field, $value);
						}
					}

					$datas[] = $application;
				}
			}
		}

		return $datas;
	}

	private function setFieldValue(array $field, mixed $value): mixed
	{
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
				case 'phonenumber':
					if (!empty($value))
					{
						if(!class_exists('EmundusHelperFabrik'))
						{
							require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/fabrik.php';
						}
						$value = \EmundusHelperFabrik::getFormattedPhoneNumberValue($value);
					}
					break;
			}
		}

		return $value;
	}

	private function checkCondition(array $field, mixed $value, array $datas): mixed
	{
		$condition          = $field['condition'];
		$conditionAttribute = $field['condition']['attribute'];

		if (!empty($conditionAttribute))
		{
			$conditionValue = $this->arrayHelper->getNestedValue($datas, $conditionAttribute);

			if (!empty($conditionValue))
			{
				if (is_array($value))
				{
					foreach ($value as $key => $val)
					{
						if ($conditionValue[$key] != $condition['value'])
						{
							unset($value[$key]);
						}
					}

					$value = array_values($value);
				}
			}
		}

		return $value;
	}

	private function getCampaignId(string $code): int
	{
		$query = $this->database->getQuery(true);

		$query->select('id')
			->from('#__emundus_setup_campaigns')
			->where('training = ' . $this->database->quote($code))
			->where('published = 1')
			->where('start_date <= NOW()')
			->where('end_date >= NOW()');
		$this->database->setQuery($query);

		return (int) $this->database->loadResult();
	}
}