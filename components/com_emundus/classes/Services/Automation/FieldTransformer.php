<?php

namespace Tchooz\Services\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\FieldResearch;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\YesnoField;

class FieldTransformer
{
	CONST MAX_CHOICES_ITEMS = 100;

	public static function transformFabrikElementIntoField(object $fabrikElement): ?Field
	{
		$field = null;
		
		if (!empty($fabrikElement)) 
		{
			$fieldGroup = new FieldGroup($fabrikElement->form_id, Text::_( $fabrikElement->form_label ));

			$fieldId = $fabrikElement->form_id .  '.' . $fabrikElement->id;

			switch($fabrikElement->plugin)
			{
				case 'databasejoin':
					$params = json_decode($fabrikElement->params);
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->createQuery();

					// count to see if there is more than one hundred options.
					// if so, we won't return all of them by default
					$query->select('COUNT(DISTINCT ' . $db->quoteName($params->join_key_column) . ')')
						->from($db->quoteName($params->join_db_name));
					$db->setQuery($query);
					$count = $db->loadResult();

					$choices = self::getElementOptions($fabrikElement);
					$field = new ChoiceField($fieldId, Text::_( $fabrikElement->label ), $choices, false, true, $fieldGroup,  false);

					if ($count > 0)
					{
						$field->setResearch(new FieldResearch('condition', 'getConditionFieldValues'));
					}
					break;
				case 'dropdown':
				case 'checkbox':
				case 'radiobutton':
					$choices = self::getElementOptions($fabrikElement);
					$field = new ChoiceField($fieldId, Text::_( $fabrikElement->label ), $choices, false, true, $fieldGroup);
					break;
				case 'date':
				case 'jdate':
				case 'datetime':
				case 'birthday':
					$field = new DateField($fieldId, Text::_( $fabrikElement->label ), false, $fieldGroup);
					break;
				case 'yesno':
					$field = new YesnoField($fieldId, Text::_( $fabrikElement->label ), false, $fieldGroup);
					break;
				default:
					$field = new StringField($fieldId, Text::_( $fabrikElement->label ), false, $fieldGroup);
			}
		}
		
		return $field;
	}

	/**
	 * @param   object       $fabrikElement
	 * @param   string|null  $search
	 *
	 * @return array<ChoiceFieldValue>
	 */
	public static function getElementOptions(object $fabrikElement, ?string $search = null, array $ids = []): array
	{
		$choices = [];

		switch($fabrikElement->plugin)
		{
			case 'databasejoin':
				$params = json_decode($fabrikElement->params);
				if (!empty($params->join_db_name) && !empty($params->join_key_column) && !empty($params->join_val_column))
				{
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->createQuery();
					$query->clear()
						->select([
							$db->quoteName($params->join_key_column),
							$db->quoteName($params->join_val_column)
						])
						->from($db->quoteName($params->join_db_name));

					if (!empty($search))
					{
						$query->where($db->quoteName($params->join_val_column) . ' LIKE ' . $db->quote('%' . $search . '%'));
					}

					if (!empty($ids))
					{
						$query->where($db->quoteName($params->join_key_column) . ' IN (' . implode(',', $ids) . ')');
					}

					$query->setLimit(self::MAX_CHOICES_ITEMS);

					try {
						$db->setQuery($query);
						$rows = $db->loadObjectList();
					} catch (\Exception $e)
					{
						error_log($e->getMessage());
					}

					if (!empty($rows))
					{
						foreach ($rows as $row)
						{
							$choices[] = new ChoiceFieldValue($row->{$params->join_key_column}, $row->{$params->join_val_column});
						}
					}
				}
				break;
			case 'dropdown':
			case 'checkbox':
			case 'radiobutton':
				if (!empty($fabrikElement->params)) {
					$params = json_decode($fabrikElement->params);
					if (!empty($params->sub_options)) {
						// todo: handle options

						foreach ($params->sub_options->sub_values as $key => $value ) {
							$choices[] = new ChoiceFieldValue($value, Text::_($params->sub_options->sub_labels[$key]));
						}
					}
				}
			break;
		}

		return $choices;
	}
}