<?php

namespace Tchooz\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Services\Automation\FieldTransformer;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__users', alias: 'u')]
class UserDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::USERDATA->value;
	}

	public function getAvailableFields(array $contextFilters): array
	{
		$fields = [
			new ChoiceField('profile', Text::_('COM_EMUNDUS_USER_FIELD_PROFILE'), $this->getProfilesList(), false, true),
			new ChoiceField('group', Text::_('COM_EMUNDUS_USER_FIELD_GROUP'), $this->getGroupList(), false, true),
			new StringField('firstname', Text::_('COM_EMUNDUS_USER_FIELD_FIRSTNAME'), false),
			new StringField('lastname', Text::_('COM_EMUNDUS_USER_FIELD_LASTNAME'), false),
			new StringField('email', Text::_('COM_EMUNDUS_USER_FIELD_EMAIL'), false),
			new DateField('lastvisitDate', Text::_('COM_EMUNDUS_USER_FIELD_LASTVISITDATE'), false)
		];
		$formId = $this->getProfileAreaFormId();

		if (!empty($formId))
		{
			$excludeFieldNames = ['profile', 'group', 'firstname', 'lastname', 'email', 'id', 'time_date', 'date_time', 'user', 'user_id'];
			$formElements = \EmundusHelperEvents::getFormElements($formId, 0, false, $excludeFieldNames);

			if (!empty($formElements))
			{
				foreach ($formElements as $element)
				{
					$field = FieldTransformer::transformFabrikElementIntoField($element);
					if (!empty($field))
					{
						$fields[] = $field;
					}
				}
			}
		}

		return $fields;
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$value = null;
		$userId = $context->getUserId();

		if (!empty($userId))
		{
			try {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				switch ($fieldName)
				{
					case 'profile':
						if (!class_exists('\EmundusModelProfile'))
						{
							require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
						}
						$m_profile = new \EmundusModelProfile();
						$profiles = $m_profile->getUserProfiles($userId);
						$value = [];
						foreach ($profiles as $profile)
						{
							if ($profile->published == 1 && !in_array('applicant', $value))
							{
								$value[] = 'applicant';
							}
							else
							{
								$value[] = $profile->id;
							}
						}

						break;

					case 'group':
						$value = \EmundusHelperFiles::getUserGroups($userId);
						break;
					case 'email':
					case 'lastvisitDate':
						$query->select($db->quoteName('u.' . $fieldName))
							->from($db->quoteName('#__users', 'u'))
							->where($db->quoteName('u.id') . ' = ' . $db->quote($userId));

						$db->setQuery($query);
						$value = $db->loadResult();
						break;
					case 'id':
						$value = $userId;
						break;
					case 'firstname':
					case 'lastname':
						$query->select($db->quoteName('u.' . $fieldName))
							->from($db->quoteName('#__emundus_users', 'u'))
							->where($db->quoteName('u.user_id') . ' = ' . $db->quote($userId));

						$db->setQuery($query);
						$value = $db->loadResult();
						break;
					default:
						if (str_contains($fieldName, '.'))
						{
							list($formId, $elementId) = explode('.', $fieldName);

							if (!empty($formId) && !empty($elementId) && is_numeric($formId) && is_numeric($elementId)) {
								$formDataResolver = new FormDataConditionResolver();
								$value = $formDataResolver->resolveValue($context, $fieldName);
							}
						}
						break;
				}
			} catch (\Exception $e) {
				Log::add('Error fetching user data for user ID ' . $userId . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.resolver');
			}
		}

		return $value;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getProfilesList(): array
	{
		$options = [
			new ChoiceFieldValue('applicant', Text::_('COM_EMUNDUS_APPLICATION_APPLICANT')),
		];

		if (!class_exists('\EmundusModelUsers'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
		}
		$m_users  = new \EmundusModelUsers();
		$profiles = $m_users->getProfiles();

		foreach ($profiles as $profile)
		{
			$options[] = new ChoiceFieldValue($profile->id, $profile->label);
		}

		return $options;
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

	public function searchable(): bool
	{
		return false;
	}

	public function getColumnsForField(string $field): array
	{
		$columns = [];

		switch($field)
		{
			case 'firstname':
			case 'lastname':
				$columns = ['eu.' . $field];
				break;
			case 'profile':
				$columns = ['eu.' . $field, 'eup.profile_id'];
				break;
			case 'group':
				$columns = ['eug.group_id'];
				break;
			default:
				$columns = [$this->getTableAlias(self::class) . '.' . $field];
		}


		return $columns;
	}

	public function getJoins(string $field): array
	{
		$joins = [];

		switch($field)
		{
			case 'profile':
				$joins[] = new TableJoin(
					'#__emundus_users',
					'eu',
					 'user_id',
					 'id',
					$this->getTableAlias(self::class),
					 'INNER'
				);
				$joins[] = new TableJoin(
					'#__emundus_users_profiles',
					'eup',
					 'user_id',
					'id',
					 $this->getTableAlias(self::class),
					 'LEFT',
				);
				break;
			case 'firstname':
			case 'lastname':
				$joins[] = new TableJoin(
					'#__emundus_users',
					'eu',
					 'user_id',
					 'id',
					$this->getTableAlias(self::class),
					 'INNER'
				);
				break;
			case 'group':
				$joins[] = new TableJoin(
					'#__emundus_groups',
					'eug',
					 'user_id',
					'id',
					 $this->getTableAlias(self::class),
					 'LEFT',
				);
				break;
			default:
				// No joins needed
				break;
		}


		return $joins;
	}

	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		$joins = [];

		switch($targetType)
		{
			case TargetTypeEnum::FILE:
				$joins[] = new TableJoin(
					$this->getTableName(self::class),
					$this->getTableAlias(self::class),
					'id',
					'applicant_id',
					$targetType->getTableAlias(),
					'INNER'
				);
				return $joins;
			case TargetTypeEnum::USER:
			default:
				break;
		}

		return $joins;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [
			TargetTypeEnum::FILE,
			TargetTypeEnum::USER,
		];
	}

	/**
	 * @return int|null
	 */
	public function getProfileAreaFormId(): ?int
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->select('id')
			->from($db->quoteName('#__fabrik_forms'))
			->where($db->quoteName('label') . ' = ' . $db->quote('FORM_PROFILE_AREA'));
		$db->setQuery($query);
		return $db->loadResult();
	}
}