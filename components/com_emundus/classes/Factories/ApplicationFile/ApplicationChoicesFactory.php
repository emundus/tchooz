<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\ApplicationFile\ChoicesState;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationChoicesFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): ApplicationChoicesEntity
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		if($withRelations)
		{
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

		return new ApplicationChoicesEntity(
			fnum: $dbObject['fnum'],
			user: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject['user_id']),
			campaign: $withRelations ? $campaignRepository->getById((int) $dbObject['campaign_id']) : null,
			order: (int) $dbObject['order'],
			state: ChoicesState::tryFrom($dbObject['state']),
			id: (int) $dbObject['id'],
			moreProperties: $dbObject['more_properties']
		);
	}
}