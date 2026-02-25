<?php
/**
 * @package     Tchooz\Factories\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Campaigns;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Factories\DBFactory;
use DateTime;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

class CampaignFactory implements DBFactory
{
	public function fromDbObjects(
		array           $dbObjects,
		                $withRelations = true,
		                $exceptRelations = [],
		?DatabaseDriver $db = null,
		array           $elements = []
	): array
	{
		$programRepository  = null;
		$campaignRepository = null;
		if ($withRelations)
		{
			$programRepository  = new ProgramRepository();
			$campaignRepository = new CampaignRepository();
		}

		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$this->buildMoreProperties($dbObject, $elements);

			$entities[] = $this->buildEntity($dbObject, $programRepository, $campaignRepository);
		}

		return $entities;
	}

	public function fromDbObject(
		object|array    $dbObject,
		                $withRelations = true,
		                $exceptRelations = [],
		?DatabaseDriver $db = null,
		array           $elements = []
	): CampaignEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		$programRepository  = null;
		$campaignRepository = null;
		if ($withRelations)
		{
			$programRepository  = new ProgramRepository();
			$campaignRepository = new CampaignRepository();
		}

		$this->buildMoreProperties($dbObject, $elements);

		return $this->buildEntity($dbObject, $programRepository, $campaignRepository);
	}

	private function buildMoreProperties(object $dbObject, array $elements): void
	{
		$dbObject->more_properties = [];
		foreach ($elements as $element)
		{
			if (isset($dbObject->more_data[$element['name']]))
			{
				if (is_array($dbObject->more_data[$element['name']]))
				{
					$formatted_value = [];
					foreach ($dbObject->more_data[$element['name']] as $value)
					{
						$formatted_value[] = \EmundusHelperFabrik::formatElementValue($element['name'], $value, $element['group_id']);
					}

					$formatted_value = implode(', ', $formatted_value);
				}
				else
				{
					$formatted_value = \EmundusHelperFabrik::formatElementValue($element['name'], $dbObject->more_data[$element['name']]);
				}

				$dbObject->more_properties[$element['name']] = [
					'id'              => $element['id'],
					'label'           => Text::_($element['label']),
					'value'           => $dbObject->more_data[$element['name']],
					'hidden'          => $element['hidden'] == 1 || $element['plugin'] === 'internalid',
					'formatted_value' => $formatted_value
				];
			}
		}
	}

	public function buildEntity(object $dbObject, ?ProgramRepository $programRepository = null, ?CampaignRepository $campaignRepository = null): CampaignEntity
	{
		return new CampaignEntity(
			label: $dbObject->label,
			start_date: new DateTime($dbObject->start_date),
			end_date: new DateTime($dbObject->end_date),
			program: !empty($programRepository) ? $programRepository->getByCode($dbObject->training) : null,
			year: $dbObject->year,
			description: $dbObject->description,
			short_description: $dbObject->short_description,
			profile_id: (int) $dbObject->profile_id,
			published: (bool) $dbObject->published,
			pinned: (bool) $dbObject->pinned,
			alias: $dbObject->alias,
			visible: (bool) $dbObject->visible,
			parent: (!empty($dbObject->parent_id) && !empty($campaignRepository)) ? $campaignRepository->getById((int) $dbObject->parent_id) : null,
			id: (int) $dbObject->id,
			moreProperties: $dbObject->more_properties,
			files_count: isset($dbObject->files_count) ? (int) $dbObject->files_count : 0,
			createdBy: isset($dbObject->user) ? (int) $dbObject->user : 0
		);
	}
}