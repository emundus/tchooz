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
use Tchooz\Entities\Fields\StringField;

class FieldTransformer
{
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
					$choices = [];

					$params = json_decode($fabrikElement->params);
					if (!empty($params->join_db_name) && !empty($params->join_key_column) && !empty($params->join_val_column))
					{
						$db = Factory::getContainer()->get('DatabaseDriver');
						$query = $db->createQuery();

						$query->select([
							$db->quoteName($params->join_key_column),
							$db->quoteName($params->join_val_column)
						])
							->from($db->quoteName($params->join_db_name));

						$db->setQuery($query);
						$rows = $db->loadObjectList();

						if (!empty($rows))
						{
							foreach ($rows as $row)
							{
								$choices[] = new ChoiceFieldValue($row->{$params->join_key_column}, $row->{$params->join_val_column});
							}
						}
					}
					$field = new ChoiceField($fieldId, Text::_( $fabrikElement->label ), $choices, false, true, $fieldGroup);
					break;
				case 'dropdown':
				case 'checkbox':
				case 'radiobutton':
					$choices = [];

					if (!empty($fabrikElement->params)) {
						$params = json_decode($fabrikElement->params);
						if (!empty($params->sub_options)) {
							foreach ($params->sub_options->sub_values as $key => $value ) {
								$choices[] = new ChoiceFieldValue($value, Text::_($params->sub_options->sub_labels[$key]));
							}
						}
					}

					$field = new ChoiceField($fieldId, Text::_( $fabrikElement->label ), $choices, false, true, $fieldGroup);
					break;
				case 'date':
				case 'jdate':
				case 'datetime':
				case 'birthday':
					$field = new DateField($fieldId, Text::_( $fabrikElement->label ), false, $fieldGroup);
					break;
				case 'yesno':
					$field = new BooleanField($fieldId, $fabrikElement->value, false, $fieldGroup);
					break;
				default:
					$field = new StringField($fieldId, Text::_( $fabrikElement->label ), false, $fieldGroup);
			}

		}
		
		return $field;
	}
}