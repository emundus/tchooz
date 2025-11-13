<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterAddUserToGroupDefinition extends EventDefinition
{
	public CONST GROUPS_KEY = 'groups';


	public function __construct()
	{
		parent::__construct(
			'onAfterAddUserToGroup',
			[
				new ChoiceField(self::GROUPS_KEY, Text::_('COM_EMUNDUS_GROUPS'), $this->getGroupsList(), true, true),
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::USER];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getGroupsList(): array
	{
		$options = [];

		if (!class_exists('EmundusModelGroups')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
		}
		$m_groups = new \EmundusModelGroups();
		$groups = $m_groups->getGroups();
		foreach ($groups as $group) {
			$options[] = new ChoiceFieldValue($group->id, Text::_($group->label));
		}

		return $options;

	}
}