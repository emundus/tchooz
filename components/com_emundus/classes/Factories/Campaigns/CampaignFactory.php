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

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): CampaignEntity
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		if($withRelations)
		{
			$programRepository = new ProgramRepository();
			$campaignRepository = new CampaignRepository();
		}
		
		$dbObject['more_properties'] = [];
		if(!empty($elements) && !empty($dbObject['more_data']))
		{
			foreach ($elements as $element)
			{
				if(isset($dbObject['more_data'][$element['name']]))
				{
					if(is_array($dbObject['more_data'][$element['name']])) {
						$formatted_value = [];
						foreach ($dbObject['more_data'][$element['name']] as $key => $value) {
							$formatted_value[] = \EmundusHelperFabrik::formatElementValue($element['name'], $value, $element['group_id']);
						}

						$formatted_value = implode(', ', $formatted_value);
					}
					else
					{
						$formatted_value = \EmundusHelperFabrik::formatElementValue($element['name'], $dbObject['more_data'][$element['name']]);
					}

					$dbObject['more_properties'][$element['name']] = [
						'id'              => $element['id'],
						'label'           => Text::_($element['label']),
						'value'           => $dbObject['more_data'][$element['name']],
						'hidden'          => $element['hidden'] == 1 || $element['plugin'] === 'internalid',
						'formatted_value' => $formatted_value
					];
				}
			}
		}

		return new CampaignEntity(
			label: $dbObject['label'],
			start_date: new DateTime($dbObject['start_date']),
			end_date: new DateTime($dbObject['end_date']),
			program: $withRelations ? $programRepository->getByCode($dbObject['training']) : null,
			year: $dbObject['year'],
			description: $dbObject['description'],
			short_description: $dbObject['short_description'],
			profile_id: (int) $dbObject['profile_id'],
			published: (bool) $dbObject['published'],
			pinned: (bool) $dbObject['pinned'],
			alias: $dbObject['alias'],
			visible: (bool) $dbObject['visible'],
			parent: (!empty($dbObject['parent_id']) && $withRelations) ? $campaignRepository->getById((int)$dbObject['parent_id']) : null,
			id: (int) $dbObject['id'],
			moreProperties: $dbObject['more_properties']
		);
	}
}