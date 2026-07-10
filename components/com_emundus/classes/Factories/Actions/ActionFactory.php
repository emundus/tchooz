<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Actions;

use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;
use Tchooz\Factories\AbstractFactory;

class ActionFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): ActionEntity
	{
		$crud = new CrudEntity($dbObject->multi, $dbObject->c, $dbObject->r, $dbObject->u, $dbObject->d);

		return new ActionEntity(
			id: (int) $dbObject->id,
			name: $dbObject->name,
			label: $dbObject->label,
			crud: $crud,
			ordering: (int) $dbObject->ordering,
			status: (bool) $dbObject->status,
			description: $dbObject->description ?? null,
			type: ActionTypeEnum::tryFrom($dbObject->type) ?? ActionTypeEnum::FILE
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return '';
	}
}