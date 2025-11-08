<?php

namespace Tchooz\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_groups', alias: 'esg')]
class GroupDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;


	/**
	 * @inheritDoc
	 */
	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::GROUP_DATA->value;
	}

	/**
	 * @inheritDoc
	 */
	public static function getAllowedActionTargetTypes(): array
	{
		return [TargetTypeEnum::GROUP];
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableFields(array $contextFilters): array
	{
		return [
			new ChoiceField('id', Text::_('COM_EMUNDUS_USER_FIELD_GROUP'), $this->getGroupList(), false, true),
			// todo: add more fields, like label, or program (course)
		];
	}

	/**
	 * @inheritDoc
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed
	{
		$foundValue = null;

		if (!empty($context->getFile()) || !empty($context->getUserId()))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			if (!empty($context->getFile()))
			{
				$query->select('DISTINCT ' . $db->quoteName('group_id'))
					->from($db->quoteName('#__emundus_group_assoc', 'ega'))
					->where($db->quoteName('ega.fnum') . ' = ' . $db->quote($context->getFile()));
			}
			else if (!empty($context->getUserId()))
			{
				$query->select('DISTINCT ' . $db->quoteName('group_id'))
					->from($db->quoteName('#__emundus_groups', 'eg'))
					->where($db->quoteName('eg.user_id') . ' = ' . $db->quote($context->getUserId()));
			}

			$db->setQuery($query);
			$foundValue = $db->loadColumn();
		}

		return $foundValue;
	}

	public function getColumnsForField(string $field): array
	{
		return [$this->getTableAlias(self::class) . '.' . $field];
	}

	/**
	 * @inheritDoc
	 */
	public function getJoins(string $field): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	public function searchable(): bool
	{
		return false;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getGroupList(): array
	{
		$options = [];

		if (!class_exists('\EmundusModelGroups'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/groups.php';
		}
		$m_groups  = new \EmundusModelGroups();
		$groups = $m_groups->getGroups();

		foreach ($groups as $group)
		{
			$options[] = new ChoiceFieldValue($group->id, $group->label);
		}

		return $options;
	}
}