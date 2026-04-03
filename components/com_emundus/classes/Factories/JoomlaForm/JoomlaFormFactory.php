<?php


namespace Tchooz\Factories\JoomlaForm;

use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;

class JoomlaFormFactory
{
	/**
	 * @param   array  $fields
	 *
	 * @return string
	 */
	public function build(array $fields): string
	{
		$xml = "<form>\n";

		$grouped = [];

		foreach ($fields as $field)
		{
			$group = $field->getGroup();

			if ($group instanceof FieldGroup)
			{
				$grouped[spl_object_id($group)]['group']    = $group;
				$grouped[spl_object_id($group)]['fields'][] = $field;
			}
			else
			{
				if ($field instanceof ChoiceField && $field->getMultiple())
				{
					$group                                      = new FieldGroup($field->getName(), $field->getLabel(), true);
					$grouped[spl_object_id($group)]['group']    = $group;
					$grouped[spl_object_id($group)]['fields'][] = $field;
				}
				else
				{
					$xml .= $this->renderField($field, 1);
				}
			}
		}

		foreach ($grouped as $data)
		{
			$xml .= $this->renderGroup(
				$data['group'],
				$data['fields'],
				1
			);
		}

		$xml .= "</form>";

		return $xml;
	}

	/**
	 * @param   Field  $field
	 * @param   int    $indentLevel
	 *
	 * @return string
	 */
	private function renderField(Field $field, int $indentLevel): string
	{
		$indent = str_repeat("    ", $indentLevel);

		$type = $this->resolveType($field);

		$xml = "{$indent}<field";
		$xml .= " type=\"{$type}\"";
		$xml .= " name=\"{$field->getName()}\"";
		$xml .= " label=\"{$field->getLabel()}\"";

		if ($type === 'list')
		{
			$choices = $field instanceof ChoiceField ? $field->getChoices() : [];
			$xml .= ">\n";
			foreach ($choices as $choice) {
				$xml .= "{$indent}    <option value=\"{$choice->getValue()}\">{$choice->getLabel()}</option>\n";
			}
			$xml .= "{$indent}</field>\n";
			return $xml;
		}
		else if ($type === 'number')
		{
			if (!empty($field->getMin()))
			{
				$xml .= " min=\"{$field->getMin()}\"";
			}

			if (!empty($field->getMax()))
			{
				$xml .= " max=\"{$field->getMax()}\"";
			}

			$xml .= " />\n";
		}
		else
		{
			$xml .= " />\n";
		}

		return $xml;
	}

	/**
	 * @param   Field  $field
	 *
	 * @return string
	 */
	private function resolveType(Field $field): string
	{
		switch ($field::getType())
		{
			case NumericField::getType():
				$type = 'number';
				break;
			case ChoiceField::getType():
				// make there is not only one choice, with null value
				if ($field instanceof ChoiceField && count($field->getChoices()) === 1 && $field->getChoices()[0]->getValue() === null)
				{
					$type = 'text';
				} else if (!empty($field->getChoices()))
				{
					$type = 'list';
				}
				else
				{
					$type = 'text';
				}
				break;
			default:
				$type = 'text';
				break;
		}

		return $type;
	}

	/**
	 * @param   FieldGroup    $group
	 * @param   array<Field>  $fields
	 * @param   int           $indentLevel
	 *
	 * @return string
	 */
	private function renderGroup(
		FieldGroup $group,
		array      $fields,
		int        $indentLevel
	): string
	{

		$indent      = str_repeat("    ", $indentLevel);
		$innerIndent = str_repeat("    ", $indentLevel + 1);

		$xml = "{$indent}<field";
		$xml .= " type=\"subform\"";
		$xml .= " name=\"{$group->getName()}\"";
		$xml .= " label=\"{$group->getLabel()}\"";
		$xml .= " multiple=\"true\">\n";

		$xml .= "{$innerIndent}<form>\n";

		foreach ($fields as $field)
		{
			$xml .= $this->renderField($field, $indentLevel + 2);
		}

		$xml .= "{$innerIndent}</form>\n";
		$xml .= "{$indent}</field>\n";

		return $xml;
	}
}