<?php

namespace Tchooz\Entities\Automation\EventsDefinitions\Defaults;

use EmundusHelperCache;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;

class FabrikFormEventDefinition extends EventDefinition
{

	/**
	 * @inheritdoc
	 */
	public function __construct(string $name, array $parameters = [])
	{
		parent::__construct($name, [
			new ChoiceField('form', Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_FABRIK_FORM'), $this->getFormsList(), false, true),
			...$parameters,
		]);
	}

	private function getFormsList(): array
	{
		$options = [];

		$h_cache = new EmundusHelperCache();
		$forms = $h_cache->get('fabrik_forms_list_with_label');

		if (empty($forms))
		{
			$formIds = \EmundusHelperFabrik::getFabrikFormsListIntendedToFiles();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();
			$query->select('id, label')
				->from($db->quoteName('#__fabrik_forms'))
				->where('id IN (' . implode(',', $formIds) . ')')
				->order('label ASC');

			$db->setQuery($query);
			$forms = $db->loadObjectList();

			foreach ($forms as $form)
			{
				$query->clear()
					->select('parent_menu.title')
					->from('#__menu AS menu')
					->leftJoin('#__menu AS parent_menu ON parent_menu.id = menu.parent_id')
					->where('menu.menutype LIKE ' . $db->quote('menu-profile%'))
					->andWhere('menu.link LIKE ' . $db->quote('%formid=' . $form->id . '%'))
					->andWhere('menu.published = 1');

				$db->setQuery($query);
				$menuTitle = $db->loadResult();
				if (!empty($menuTitle))
				{
					$form->label = Text::_($form->label) . ' (' . Text::_($menuTitle) . ')';
				}
			}

			$h_cache->set('fabrik_forms_list_with_label', $forms);
		}

		foreach ($forms as $form)
		{
			$options[] = new ChoiceFieldValue($form->id, Text::_($form->label));
		}

		return $options;
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [];
	}
}