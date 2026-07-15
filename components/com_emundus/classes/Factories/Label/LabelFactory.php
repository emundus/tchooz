<?php
/**
 * @package     Tchooz\Factories\Label
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Label;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\DBFactory;

class LabelFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): LabelEntity
	{
		return new LabelEntity(
			label: $dbObject->label ?? '',
			class: $dbObject->class ?? '',
			ordering: $dbObject->ordering ?? 0,
			id: $dbObject->id ?? 0,
			category: $dbObject->category ?? '',
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		// No relation supported by this factory.
		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		// No relation supported by this factory.
		return spl_object_id($dbObject);
	}
}