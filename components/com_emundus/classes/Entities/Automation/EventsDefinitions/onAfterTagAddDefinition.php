<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterTagAddDefinition extends Defaults\EventDefinition
{
	public CONST TAGS_KEY = 'tags';

	public function __construct()
	{
		parent::__construct(
			'onAfterTagAdd',
			[
				new ChoiceField(self::TAGS_KEY, Text::_('COM_EMUNDUS_FILES_TAGS') , $this->getTagsList(), false, true),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [
			TargetTypeEnum::FILE
		];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getTagsList(): array
	{
		$options = [];

		if (!class_exists('EmundusModelSettings')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/settings.php');
		}
		$m_settings = new \EmundusModelSettings();
		$tags = $m_settings->getTags();

		foreach ($tags as $tag)
		{
			$options[] = new ChoiceFieldValue($tag->id, Text::_($tag->label));
		}

		return $options;
	}
}