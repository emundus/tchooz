<?php

namespace Tchooz\Factories\Field;

use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;

class ChoiceFieldFactory
{
	/**
	 * @param   object  $repository
	 * @param   string  $method
	 * @param   array   $args
	 * @param   string  $getLabelMethod
	 *
	 * @return array<ChoiceFieldValue>
	 */
	public static function makeOptions(object $repository, string $method, array $args = [],  string $getLabelMethod = ''): array
	{
		$options = [];
		$items = call_user_func_array([$repository, $method], $args);

		foreach ($items as $item)
		{
			if (!empty($getLabelMethod) && method_exists($item, $getLabelMethod))
			{
				$label = $item->{$getLabelMethod}();
			}
			else
			{
				if (method_exists($item, 'getLabel'))
				{
					$label = $item->getLabel();
				}
				else if (method_exists($item, 'getName'))
				{
					$label = $item->getName();
				}
				else
				{
					$label = $item->getId();
				}
			}

			$options[] = new ChoiceFieldValue($item->getId(), $label);
		}

		return $options;
	}

	/**
	 * @param   ConditionTargetTypeEnum  $type
	 * @param   string                   $field
	 *
	 * @return array<ChoiceFieldValue>
	 */
	public function makeOptionsFromType(ConditionTargetTypeEnum $type, string $field): array
	{
		$options = [];



		return $options;
	}
}